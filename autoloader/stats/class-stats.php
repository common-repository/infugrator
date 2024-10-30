<?php
/**
 * This class creates Statistics page.
 *
 * @link       http://infugrator.com/
 * @since      1.0.0
 * @package    Infugrator
 * @subpackage Infugrator/modules
 * @author     Cosmin Schiopu <sc.cosmin@gmail.com>
 */

class IFG_Statistics{

	/**
	 * @since    1.0.0
	 * @access   public
	 */
	public static $name = 'Statistics';


	/**
	 * @since    1.0.0
	 * @access   public
	 */
	public static $slug = 'stats';

	/**
	 * @since    1.0.0
	 * @access   protected
	 */
	protected $loader;


	/**
	 * @since    1.0.0
	 * @access   protected
	 */
	protected $settings;


	/**
	 * @since    1.0.0
	 * @access   protected
	 */
	protected $utility;


	/**
	 * @since    1.0.0
	 * @access   public
	 */
	public static $instance = null;



	/**
	 * Create a single instance of the class
	 *
	 * @since 1.0.0
	 * @return object
	 */
	public static function instance() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}
		return self::$instance;
	}



	public function __construct(){

		$this->loader = new IFG_Loader;
		$this->settings = new IFG_Settings;
		$this->utility = new IFG_Utility;

		$this->loader->add_action('admin_menu', $this, 'set_page_menu', 11);
		$this->loader->add_action('admin_enqueue_scripts', $this, 'admin_assets');

		$this->loader->add_action('wp_ajax_nopriv_ifg_stats_data', $this, 'process_stats_data');
		$this->loader->add_action('wp_ajax_ifg_stats_data', $this, 'process_stats_data');

		$this->loader->run();
	}



	/**
	 * List of widgets
	 *
	 * @since 1.0.0
	 */
	public function widgets() {

		$widgets = apply_filters('ifg_widgets_'.self::$slug, array(
			array(
				'id'      => 'ifg-sold_products',
				'title'   => __('Best Selling Products', 'infugrator'),
				'content' => '<i class="fa fa-spinner fa-spin"></i>',
			),
			array(
				'id'      => 'ifg-new_contacts',
				'title'   => __('New Contacts', 'infugrator'),
				'content' => '<i class="fa fa-spinner fa-spin"></i>',
			),
			// array(
			// 	'id'      => 'ifg-top_countries',
			// 	'title'   => __('Top Countries by Contacts', 'infugrator'),
			// 	'content' => '<i class="fa fa-spinner fa-spin"></i>',
			// ),
		));


		return $widgets;
	}



	/**
	 * Enqueue scripts/styles in admin area
	 *
	 * @since  1.0.0
	 */
	public function admin_assets(){

		if(isset($_GET['page']) && $_GET['page'] == 'ifg-'.self::$slug){

			wp_enqueue_script('google-loader', plugin_dir_url(__FILE__) . 'js/google-loader.js', null, IFG_VERSION, true);
			wp_enqueue_script('ifg-'.self::$slug, plugin_dir_url(__FILE__) . 'js/stats.js', array('google-loader', 'ifg-pubsub'), IFG_VERSION, true);
		}
	}



	/**
	 * Add submenu page
	 *
	 * @since 	1.0.0
	 */
	public function set_page_menu(){

		add_submenu_page(
			'infugrator',
			__('Statistics', 'infugrator'),
			__('Statistics', 'infugrator'),
			'moderate_comments',
			'ifg-'.self::$slug,
			array($this, 'output_page')
		);

	}



	/**
	 * Output HTML of page
	 *
	 * @since  1.0.0
	 */
	public function output_page() {
		require_once apply_filters('ifg_'.self::$slug.'_output_page', plugin_dir_path(__FILE__) . 'output-page.php');
	}



	/**
	 * Retrieve new registered contacts
	 *
	 * @since  1.0.0
	 * @param  boolean $sync
	 * @return array
	 */
	public function get_registered_contacts( $sync = false ){

		$data = array();
		$data[] = array('Country', 'Contacts', array('role' => 'style'));

		if($this->settings->get('application/name') =='' || $this->settings->get('application/key') ==''){
			$data[] = array('',0,'#79BE37');
			return $data;
		}

		$cached = get_transient('ifg-'.self::$slug.'-reg-contacts');

		if($sync === true || $cached == ''){

			$table = new Infusionsoft_Contact();

			$today      = Infusionsoft_DataService::count($table, array('DateCreated' => date('Y-m-d').'%', 'Email' => '%'));
			$yesterday  = Infusionsoft_DataService::count($table, array('DateCreated' => date('Y-m-d',strtotime("-1 days")).'%', 'Email' => '%'));
			$this_month = Infusionsoft_DataService::count($table, array('DateCreated' => date('Y-m-').'%', 'Email' => '%'));
			$last_month = Infusionsoft_DataService::count($table, array('DateCreated' => date('Y-m-',strtotime('-1 month')).'%', 'Email' => '%'));
			$this_year  = Infusionsoft_DataService::count($table, array('DateCreated' => date('Y-').'%', 'Email' => '%'));
			$last_year  = Infusionsoft_DataService::count($table, array('DateCreated' => (date('Y')-1).'%', 'Email' => '%'));

			$stats = array(
				'Last Year'  => $last_year,
				'This Year'  => $this_year,
				'Last Month' => $last_month,
				'This Month' => $this_month,
				'Yesterday'  => $yesterday,
				'Today'      => $today,
			);

			foreach($stats as $key => $val){
				$data[] = array($key, $val, "#79BE37");
			}

			set_transient( 'ifg-'.self::$slug.'-reg-contacts', $data, 60*60*24 );

		}else{
			$data = $cached;
		}

		return $data;

	}



	/**
	 * Retrieve most sold products
	 *
	 * @since  1.0.0
	 * @param  boolean $sync
	 * @return array
	 */
	public function get_most_sold_products( $sync = false ){

		$data = array();
		$data[] = array('Country', 'Sales', array('role' => 'style'));

		if($this->settings->get('application/name') =='' || $this->settings->get('application/key') ==''){
			$data[] = array('',0,'#79BE37');
			return $data;
		}

		$cached = get_transient('ifg-'.self::$slug.'-sold-products');

		if($sync === true || $cached == ''){

			$table = new Infusionsoft_Invoice();
			$sold_products = array();
			$limit = 1000;

			$count = Infusionsoft_DataService::count($table, array('Id' => '%'));
			$pages = ceil($count / $limit);

			for($i = 0; $i < $pages; $i++){

				try {
				    $results = Infusionsoft_DataService::query($table, array('Id' => '%'), $limit, $i);

					foreach($results as $item){
						$sold_products[] = $item->ProductSold;
					}

				} catch (Exception $e) {
				    // echo 'Caught exception: ',  $e->getMessage(), "\n";
				}
			}

			//count unique values
			$sold_products = array_count_values($sold_products);
			$get_products = $this->utility->get_products();

			arsort($sold_products);

			$top = array_slice($sold_products, 0, 5, true);
			$products = array();

			foreach($top as $key => $val){
				$products[] = array(
					'name' => $get_products[$key]['name'],
					'value' => $val
				);
			}

			foreach($products as $item){
				$data[] = array($item['name'], $item['value'], "#79BE37");
			}

			set_transient( 'ifg-'.self::$slug.'-sold-products', $data, 60*60*24 );

		}else{
			$data = $cached;
		}

		return $data;

	}



	/**
	 * Retrieve most registered countries
	 *
	 * @since  1.0.0
	 * @param  boolean $sync
	 * @return array
	 */
	public function get_most_registered_countries( $sync = false ){

		$data = array();
		$data[] = array('Country', 'Contacts', array('role' => 'style'));

		if($this->settings->get('application/name') =='' || $this->settings->get('application/key') ==''){
			$data[] = array('',0,'#79BE37');
			return $data;
		}

		$cached = get_transient('ifg-'.self::$slug.'-reg-countries');

		if($sync === true || $cached == ''){

			$table = new Infusionsoft_Contact();
			$countries = array();
			$limit = 1000;

			$count = Infusionsoft_DataService::count($table, array('Country' => '%'));
			$pages = ceil($count / $limit);

			for($i = 0; $i < $pages; $i++){

				try {
				    $results = Infusionsoft_DataService::query($table, array('Country' => '%'), $limit, $i);

					foreach($results as $item){
						//skip those who have abbreviation
						if(strlen($item->Country) > 3){
							$countries[] = $item->Country;
						}
					}

				} catch (Exception $e) {
				    // echo 'Caught exception: ',  $e->getMessage(), "\n";
				}

			}


			//remove empty values
			$countries = array_filter($countries);

			//count unique values
			$countries = array_count_values($countries);

			arsort($countries);

			$top = array_slice($countries, 0, 5);

			foreach($top as $key => $val){
				$data[] = array($key, $val, "#79BE37");
			}

			set_transient( 'ifg-'.self::$slug.'-reg-countries', $data, 60*60*24 );

		}else{
			$data = $cached;
		}

		return $data;

	}




	/**
	 * Processing data of widgets
	 *
	 * @since  1.0.0
	 * @return array
	 */
	public function process_stats_data(){

		if(!check_ajax_referer( 'ifg-nonce', 'security', false )){
			return;
		}


		$sync = isset($_POST['sync']) ? (bool) $_POST['sync'] : '';

		$data = apply_filters('ifg_stats_data', array(
			'newContacts' => array(
				'target' => 'ifg-new_contacts',
				'data' => $this->get_registered_contacts($sync)
			),
			'soldProducts' => array(
				'target' => 'ifg-sold_products',
				'data' => $this->get_most_sold_products($sync)
			),
			// 'topCountries' => array(
			// 	'target' => 'ifg-top_countries',
			// 	'data' => $this->get_most_registered_countries($sync)
			// ),
		));


		wp_send_json($data);
	}

}
IFG_Statistics::instance();
