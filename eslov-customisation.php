<?php

/**
 * Plugin Name:       Eslöv Customisation
 * Description:       DB migration CLI and runtime shims for Eslöv Municipio deployment
 * Version:           0.1.0
 * Author:            Municipio
 * Text Domain:       eslov-customisation
 * Domain Path:       /languages
 */

namespace EslovCustomisation;

if (!defined('ABSPATH')) {
    exit;
}

define('ESLOV_CUSTOMISATION_PATH', plugin_dir_path(__FILE__));
define('ESLOV_CUSTOMISATION_URL', plugin_dir_url(__FILE__));
define('ESLOV_CUSTOMISATION_VERSION', '0.1.0');

if (file_exists(ESLOV_CUSTOMISATION_PATH . 'vendor/autoload.php')) {
    require_once ESLOV_CUSTOMISATION_PATH . 'vendor/autoload.php';
}

new App();

if (defined('WP_CLI') && WP_CLI) {
    CliBootstrap::register();
}
