<?php
/**
 * Plugin Name:       Infugrator
 * Plugin URI:        http://infugrator.com/
 * Description:       Easily integrates <strong>Infusionsoft®</strong> with your site.
 * Version:           1.0.3
 * Author:            Cosmin Schiopu
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       infugrator
 * Domain Path:       /languages
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

define('IFG_PLUGIN', 'infugrator');
define('IFG_VERSION', '1.0.3');
define('IFG_VENDORS', plugin_dir_url(__FILE__) . 'vendors');


/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */
require plugin_dir_path(__FILE__) . 'includes/core.php';


/**
 * The code that runs during plugin activation.
 * This action is documented in includes/class-ifg-activator.php
 */
function activate_infugrator() {
	require plugin_dir_path(__FILE__) . 'includes/activator.php';
	IFG_Activator::activate();
}

/**
 * The code that runs during plugin deactivation.
 * This action is documented in includes/class-ifg-deactivator.php
 */
function deactivate_infugrator() {
	require plugin_dir_path(__FILE__) . 'includes/deactivator.php';
	IFG_Deactivator::deactivate();
}

register_activation_hook( __FILE__, 'activate_infugrator' );
register_deactivation_hook( __FILE__, 'deactivate_infugrator' );

