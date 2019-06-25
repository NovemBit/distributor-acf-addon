<?php

/* Require plug-in files */
require_once __DIR__ . '/includes/acf-hub.php';
require_once __DIR__ . '/includes/acf-spoke.php';


/* Call the setup functions */
\DT\NbAddon\Acf\Hub\setup();
\DT\NbAddon\Acf\Spoke\setup();
