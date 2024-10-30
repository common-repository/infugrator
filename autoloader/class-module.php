<?php
/**
 * The base class for modules
 *
 * @link       http://infugrator.com/
 * @since      1.0.0
 * @package    Infugrator
 * @subpackage Infugrator/loader
 * @author     Cosmin Schiopu <sc.cosmin@gmail.com>
 */

abstract class IFG_Module{

	/**
	 * @since    1.0.0
	 * @access   public
	 */
	public $settings;


	/**
	 * @since    1.0.0
	 * @access   public
	 */
	public $utility;


	/**
	 * @since    1.0.0
	 * @access   protected
	 */
	protected $modules;


	/**
	 * @since    1.0.0
	 * @access   public
	 */
	public $loader;


	public function __construct(){

		$this->loader   = new IFG_Loader;
		$this->settings = new IFG_Settings;
		$this->utility  = new IFG_Utility;

		$this->modules = $this->utility->wp_option('get', 'ifg-modules') != '' ? $this->utility->wp_option('get', 'ifg-modules') : array();

		$this->initialize();

		$this->run();

	}


	/**
     * Get infusionsoft important fields
     *
     * @since  1.0.0
     * @return array
     */
    public function get_infusionsoft_fields(){

    	$fields = array(
    		'FirstName',
    		'LastName',
    		'Email',
    		'Phone1',
    		'Country',
    		'City',
    		'State',
    		'StreetAddress1',
    		'PostalCode',
    		'Company',
    		'Website',
		);

		return apply_filters('ifg_get_infusionsoft_fields', $fields);
    }



	/**
	 * Initialize things before running
	 *
	 * @since  1.0.0
	 * @return mixed
	 */
	abstract protected function initialize();



	/**
	 * Run the main processes
	 *
	 * @since  1.0.0
	 * @return bool
	 */
	abstract protected function run();



	/**
     * Check if the dependency is installed and has the required version
     *
     * @param  boolean $version
     * @return bool
     */
	protected function check_dependency($plugin, $version = false){

		$plugins = $this->utility->wp_option('get', 'active_plugins');

		foreach ($plugins as $item) {

			if(strpos($item, $plugin) !== false){

				if(!function_exists('get_plugin_data')){
					require_once ABSPATH . 'wp-admin/includes/plugin.php';
				}
				$plugin_data = get_plugin_data( plugin_dir_path( dirname( dirname( __FILE__ ) ) ) . $item, false, false);
				$plugin_version = (int)preg_replace('/[.]/', '', $plugin_data['Version']);

				if($version){
					if($plugin_version >= (int)$version){
						return true;
					}
					return false;
				}

				return true;
			}
		}
		return false;
	}



	/**
	 * Display dependency messages
	 *
	 * @since  1.0.0
	 * @param array $dependency
	 * @return string
	 */
	public function dependency_message($dependency = '') {

		$module  = $this->utility->rgar($dependency, 'module');
		$slug    = $this->utility->rgar($dependency, 'slug');
		$name    = $this->utility->rgar($dependency, 'name');
		$version = $this->utility->rgar($dependency, 'version');

		if(!$this->check_dependency($slug)){

			$msg = sprintf(
				__('%1$s plugin must be installed and activated before you can use <b>%2$s</b>.', 'infugrator'),
				$name,
				$module
			);

			echo '<div class="ifg-error notice">'.$msg.'</div>';

		}elseif(!$this->check_dependency($slug, $version)){

			$msg = sprintf(
				__('<b>%1$s</b> requires the latest version of %2$s installed. Please upgrade now.', 'infugrator'),
				$module,
				$name
			);

			echo '<div class="ifg-error notice">'.$msg.'</div>';
		}
    }



}