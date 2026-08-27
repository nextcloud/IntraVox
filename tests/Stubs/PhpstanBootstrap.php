<?php
declare(strict_types=1);

/**
 * Constants the Nextcloud server defines at runtime.
 *
 * nextcloud/ocp ships the OCP interfaces but not the server bootstrap, so a few
 * globals that lib/ legitimately uses are undefined during analysis. Defining
 * them here is narrower than adding ignoreErrors patterns, which would also
 * hide real typos.
 */
if (!defined('OC_APP_ROOT')) {
    define('OC_APP_ROOT', __DIR__ . '/../..');
}
