<?php
/**
 * Fired during plugin activation
 *
 * @link       http://infugrator.com/
 * @since      1.0.0
 * @package    Infugrator
 * @subpackage Infugrator/includes
 * @author     Cosmin Schiopu <sc.cosmin@gmail.com>
 */

class IFG_Activator {

	/**
	 * @since    1.0.0
	 */
	public static function activate() {

		global $wp_version;

		$version     = '4.1';
		$php_version = '5.4';

		if ( version_compare( $wp_version, $version, '<' ) ) {
			wp_die('WordPress must be at least '.$version.' to activate this plugin!');
		}

		if ( version_compare( phpversion(), $php_version, '<' ) ) {
			wp_die('PHP version must be at least '.$php_version.' to activate this plugin!');
		}

		$plugin = new Infugrator();
		$plugin->run();
		$plugin->add_modules();
	}

}
