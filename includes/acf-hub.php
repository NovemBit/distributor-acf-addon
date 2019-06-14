<?php
/**
 * Handle media fields in post content
 *
 * @package Distributor
 */

namespace Distributor\AcfHub;

/**
 * Setup actions
 */
function setup() {
	add_action(
		'init',
		function () {
			add_filter( 'dt_push_post_args', __NAMESPACE__ . '\map_acf_fields', 10, 2 );
			add_filter( 'dt_subscription_post_args', __NAMESPACE__ . '\map_acf_fields', 10, 2 );
		}
	);
}

/**
 * Map acf fields by pushing slugs|logins instead of ids
 *
 * @param array  $post_body Array of pushing post body.
 * @param object $post WP Post object.
 *
 * @return array
 */
function map_acf_fields( $post_body, $post ) {
	if ( function_exists( 'get_field_object' ) ) {
		$post_metas   = get_post_meta( $post->ID );
		$terms_map    = array();
		$users_map    = array();
		$relation_map = array();
		$gallery_map  = array();
		foreach ( $post_metas as $key => $values ) {
			foreach ( $values as $value ) {
				$value = maybe_unserialize( $value );
				if ( ! empty( $value ) && array_key_exists( '_' . $key, $post_metas ) ) {
					$field = get_field_object( $key, $post->ID );

					if ( isset( $field ) ) {
						if ( 'taxonomy' === $field['type'] ) {
							$taxonomy = $field['taxonomy'];
							$slug     = array(
								'taxonomy' => $taxonomy,
							);
							if ( is_array( $value ) ) {
								$slug['slug'] = array();
								foreach ( $value as $id ) {
									$term = get_term_by( 'id', $id, $taxonomy );
									if ( $term ) {
										$slug['slug'][] = $term->slug;
									}
								}
							} else {
								$term = get_term_by( 'id', $value, $taxonomy );
								if ( $term ) {
									$slug['slug'] = $term->slug;
								}
							}
							$terms_map[ $key ] = $slug;
						} elseif ( 'user' === $field['type'] ) {
							if ( is_array( $value ) ) {
								$user_data = array();
								foreach ( $value as $user_id ) {
									$user_data[] = get_user_by( 'id', $user_id )->user_login;
								}
							} else {
								$user_data = get_user_by( 'id', $value )->user_login;
							}
							$users_map[ $key ] = $user_data;
						} elseif ( 'relationship' === $field['type'] ) {
							$relation_map[ $key ] = $value;
						} elseif ( 'gallery' === $field['type'] ) {
							$gallery_map[ $key ] = $value;
						}
					}
				}
			}
		}
		if ( ! empty( $terms_map ) ) {
			$post_body['distributor_acf_terms_mapping'] = $terms_map;
		}
		if ( ! empty( $users_map ) ) {
			$post_body['distributor_acf_users_mapping'] = $users_map;
		}
		if ( ! empty( $relation_map ) ) {
			$post_body['distributor_acf_relation_mapping'] = $relation_map;
		}
		if ( ! empty( $gallery_map ) ) {
			$post_body['distributor_acf_gallery_mapping'] = $gallery_map;
		}

		return $post_body;
	}
}
