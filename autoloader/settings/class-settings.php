<?php
/**
 * This class creates settings page.
 *
 * @link       http://infugrator.com/
 * @since      1.0.0
 * @package    Infugrator
 * @subpackage Infugrator/autoloader/settings
 * @author     Cosmin Schiopu <sc.cosmin@gmail.com>
 */

class IFG_Settings{

	/**
	 * @since    1.0.0
	 * @access   protected
	 */
	protected $settings;


	/**
	 * @since    1.0.0
	 * @access   protected
	 */
	protected $loader;


	/**
	 * @since    1.0.0
	 * @access   protected
	 */
	protected $utility;


	public function __construct(){

		$this->loader = new IFG_Loader;
		$this->utility = new IFG_Utility;

		$this->settings = $this->utility->wp_option('get', 'ifg-settings');
	}


	public function run(){

		$this->loader->add_action( 'admin_menu', $this, 'set_page_menu', 12 );
		$this->loader->add_action( 'admin_init', $this, 'save_fields' );
		$this->loader->run();
	}



	/**
	 * Get settings
	 *
	 * @param  string $prop
	 * @return mixed
	 */
	public function get($key, $default = '') {

		return $this->utility->rgars($this->settings, $key, $default);
	}



	/**
	 * List of widgets for settings page
	 *
	 * @since  1.0.0
	 * @return array
	 */
	public function widgets() {

		ob_start();

	    include apply_filters('ifg_default_output_widgets_settings', plugin_dir_path(__FILE__) . 'widgets-settings.php');

	    $content = ob_get_clean();

		$widgets = apply_filters('ifg_widgets_settings', array(
			array(
				'id'      => 'ifg-app-config',
				'title'   => __('Infusionsoft Details', 'infugrator'),
				'content' => $content,
			),
		));


		return $widgets;
	}



	/**
	 * Add submenu page
	 *
	 * @since 	1.0.0
	 */
	public function set_page_menu(){

		add_submenu_page(
			'infugrator',
			__('Settings', 'infugrator'),
			__('Settings', 'infugrator'),
			'moderate_comments',
			'ifg-settings',
			array($this, 'output_page')
		);

	}



	/**
	 * Output HTML of page
	 *
	 * @since  1.0.0
	 */
	public function output_page() {
		require_once apply_filters('ifg_default_output_page_settings', plugin_dir_path(__FILE__) . 'output-page.php');
	}



	/**
	 * Save settings fields
	 *
	 * @since 1.0.0
	 */
	public function save_fields(){
		//this will save the option in the wp_options table as 'ifg-settings'
    	//the third parameter is a function that will validate your input values
    	register_setting( 'ifg-settings-group', 'ifg-settings', array( $this, 'validate_fields') );
	}



	/**
	 * Validate settings fields
	 *
	 * @since 1.0.0
	 */
	public function validate_fields($args){

	    Infusionsoft_AppPool::clearApps();
	    Infusionsoft_AppPool::addApp(new Infusionsoft_App($args['application']['name'], $args['application']['key'], 443));

		$app = Infusionsoft_AppPool::getApp();
		$message = '';

		try {
	        Infusionsoft_WebFormService::getMap($app);
	    }
	    catch(Exception $e){

	        unset($args['application']['name'], $args['application']['key']);

	        add_settings_error('ifg-settings-group', 'invalid_app', 'Could not connect to Infusionsoft application. Pelase check <b>Your Application Name/Encrypted Key</b> and try again!');
	    }


	    return $args;
	}

}
$settings = new IFG_Settings();
$settings->run();