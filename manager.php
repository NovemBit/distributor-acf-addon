<?php

/* Require plug-in files */
require_once __DIR__ . '/includes/acf-hub.php';
require_once __DIR__ . '/includes/acf-spoke.php';


/* Call the setup functions */
\Distributor\AcfHub\setup();
\Distributor\AcfSpoke\setup();
