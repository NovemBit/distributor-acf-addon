<?php
/**
 * Handle media fields in post content
 *
 * @package Distributor
 */

namespace DT\NbAddon\Acf\Spoke;

/**
 * Setup actions
 */
function setup() {
	add_action(
		'init',
		function () {
			add_action( 'dt_process_distributor_attributes', __NAMESPACE__ . '\set_media_items', 10, 2 );
			add_action( 'dt_process_subscription_attributes', __NAMESPACE__ . '\set_media_items', 10, 2 );
			add_action( 'dt_process_distributor_attributes', __NAMESPACE__ . '\set_fields_map', 10, 2 );
			add_action( 'dt_process_subscription_attributes', __NAMESPACE__ . '\set_fields_map', 10, 2 );
		}
	);
}

/**
 * Handle post media attachments
 *
 * @param WP_Post         $post    Inserted or updated post object.
 * @param WP_REST_Request $request Request object.
 */
function set_media_items( $post, $request ) {
	if ( function_exists( 'get_field_object' ) ) {
		$post_metas = get_post_meta( $post->ID );
		foreach ( $post_metas as $key => $values ) {
			foreach ( $values as $value ) {
				$value = maybe_unserialize( $value );
				if ( ! empty( $value ) && array_key_exists( '_' . $key, $post_metas ) ) {
					$field = get_field_object( $key, $post->ID );
					if ( $field && 'image' === $field['type'] ) {
						if ( is_array( $value ) ) {
							$result = [];
							foreach ( $value as $id ) {
								$new_id = find_new_media( $id, $post->ID );
								if ( null !== $new_id ) {
									$result[] = (string) $new_id;
								} else {
									$result[] = $id;
								}
							}
						} else {
							$result = $value;
							$new_id = find_new_media( $value, $post->ID );
							if ( null !== $new_id ) {
								$result = $new_id;
							}
						}
						update_post_meta( $post->ID, $key, $result );
					}
				}
			}
		}
	}
}

/**
 * Find distributed media ids
 *
 * @param integer $origin Needle media id.
 * @param integer $post_id Current post id.
 *
 * @return null|int
 */
function find_new_media( $origin, $post_id ) {
	$media_array = get_attached_media( get_allowed_mime_types(), $post_id );
	if ( ! isset( $media_array ) || empty( $media_array ) ) {
		return null;
	}
	$result = null;
	foreach ( $media_array as $media ) {
		$origin_id = get_post_meta( $media->ID, 'dt_original_media_id', true );
		if ( (int) $origin_id === (int) $origin ) {
			$result = $media->ID;
			break;
		}
	}
	return $result;
}

/**
 * Replace acf fields values with correct ones on initial push and update
 *
 * @param WP_Post         $post    Inserted or updated post object.
 * @param WP_REST_Request $request Request object.
 */
function set_fields_map( $post, $request ) {
	if ( isset( $request['distributor_acf_terms_mapping'] ) ) {
		$terms_map = $request['distributor_acf_terms_mapping'];
		apply_term_map( $request['distributor_acf_terms_mapping'], $post->ID );
	}
	if ( isset( $request['distributor_acf_users_mapping'] ) ) {
		apply_users_map( $request['distributor_acf_users_mapping'], $post->ID );
	}
	if ( isset( $request['distributor_acf_relation_mapping'] ) ) {
		apply_relations_map( $request['distributor_acf_relation_mapping'], $post->ID );
	}
	if ( isset( $request['distributor_acf_attachments_mapping'] ) ) {
		apply_attachments_map( $request['distributor_acf_attachments_mapping'], $post->ID );
	}
}

/**
 * Replace term ids with correct ones
 *
 * @param array   $terms_map Array of terms slugs and meta keys.
 * @param integer $post_id Current post ID.
 */
function apply_term_map( $terms_map, $post_id ) {
	foreach ( $terms_map as $meta_key => $map ) {
		if ( is_array( $map['slug'] ) ) {
			$ids = array();
			foreach ( $map['slug'] as $item ) {
				$term = get_term_by( 'slug', $item, $map['taxonomy'] );
				if ( ! empty( $term ) ) {
					$ids[] = $term->term_id;
				}
			}
			if ( ! empty( $ids ) ) {
				update_post_meta( $post_id, $meta_key, $ids );
			}
		} else {
			$term = get_term_by( 'slug', $map['slug'], $map['taxonomy'] );
			if ( ! empty( $term ) ) {
				update_post_meta( $post_id, $meta_key, $term->term_id );
			}
		}
	}
}

/**
 * Replace user ids with correct ones
 *
 * @param array   $users_map Array of users logins and meta keys.
 * @param integer $post_id Current post ID.
 */
function apply_users_map( $users_map, $post_id ) {
	foreach ( $users_map as $key => $value ) {
		$result = null;
		if ( is_array( $value ) ) {
			$result = array();
			foreach ( $value as $login ) {
				$user = get_user_by( 'login', $login );
				if ( $user ) {
					$result[] = $user->ID;
				}
			}
		} else {
			$user = get_user_by( 'login', $value );
			if ( $user ) {
				$result = $user->ID;
			}
		}
		if ( ! empty( $result ) ) {
			update_post_meta( $post_id, $key, $result );
		}
	}
}

/**
 * Replace related object ids with correct ones
 *
 * @param array   $relations_map Array of related objects ids and meta keys.
 * @param integer $post_id Current post ID.
 */
function apply_relations_map( $relations_map, $post_id ) {
	foreach ( $relations_map as $key => $value ) {
		$result = null;
		if ( is_array( $value ) ) {
			$result = array();
			foreach ( $value as $object_id ) {
				$correct_id = \Distributor\Utils\get_post_id_from_original_id( $object_id );
				if ( ! empty( $correct_id ) ) {
					$result[] = $correct_id;
				} else {
					$result[] = $object_id;
				}
			}
		} else {
			$correct_id = \Distributor\Utils\get_post_id_from_original_id( $value );
			if ( ! empty( $correct_id ) ) {
				$result = $correct_id;
			}
		}
		if ( ! empty( $result ) ) {
			update_post_meta( $post_id, $key, $result );
		}
	}

}

/**
 * Replace attachment media ids with correct ones
 *
 * @param array   $attachments_map Array of attachment media ids and meta keys.
 * @param integer $post_id Current post ID.
 */
function apply_attachments_map( $attachments_map, $post_id ) {
	global $wpdb;
	$query = "SELECT post_id FROM $wpdb->postmeta WHERE meta_key = 'dt_original_media_id' AND meta_value =";

	foreach ( $attachments_map as $key => $value ) {
		$result = null;
		if ( is_array( $value ) ) {
			$result = array();
			foreach ( $value as $id ) {
				$correct_id = $wpdb->get_var( $query . $id ); //phpcs:ignore
				$result[]   = empty( $correct_id ) ? $id : $correct_id;
			}
		} else {
			$correct_id = $wpdb->get_var( $query . $value ); //phpcs:ignore
			$result     = empty( $correct_id ) ? $value : $correct_id;
		}
		if ( ! empty( $result ) ) {
			update_post_meta( $post_id, $key, $result );
		}
	}
}
