<?php
/**
 * Module Name: WP Forms Add-on
 * Description: Collects form submissions from <a href="https://wpforms.com/" target="_blank">WPForms</a> plugin and send them to your Infusionsoft account. Applies tags and triggers campaign goals.
 *
 * @link       http://infugrator.com/
 * @since      1.0.0
 * @package    Infugrator
 * @subpackage Infugrator/modules
 * @author     Cosmin Schiopu <sc.cosmin@gmail.com>
 */

class IFG_WP_Forms extends IFG_Module{

	/**
	 * @since    1.0.0
	 * @access   public
	 */
	public static $name = 'WPForms Add-on';


	/**
	 *	The slug of module, must be unique and as short as possible
	 *
	 * @since    1.0.0
	 * @access   public
	 */
	public static $slug = 'wpforms';


	/**
	 * @since    1.0.0
	 * @access   public
	 */
	public static $dependency_name = '<a href="https://wpforms.com/" target="_blank">WPForms</a>';


	/**
	 * @since    1.0.0
	 * @access   public
	 */
	public static $dependency_version = 45;


	/**
	 * @since    1.0.0
	 * @access   protected
	 */
	protected $mapped_fields = null;


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



	/**
	 * Initialize things before running
	 *
	 * @since  1.0.0
	 * @return mixed
	 */
	public function initialize(){

		$this->mapped_fields = $this->utility->wp_option('get', 'ifg-'.self::$slug.'-fields');

		$this->loader->add_filter( 'ifg_widgets_stats', $this, 'insert_stats_widget');
		$this->loader->add_filter( 'ifg_stats_data', $this, 'add_stats_data');

		//this will catch the sync stats process and update the data
		$this->loader->add_action( 'wp_ajax_nopriv_ifg_select_search_data', $this, 'add_stats_data');
		$this->loader->add_action( 'wp_ajax_ifg_select_search_data', $this, 'add_stats_data');

		$this->loader->add_action( 'wp_ajax_nopriv_ifg_wpforms_search_data', $this, 'search_tags');
		$this->loader->add_action( 'wp_ajax_ifg_wpforms_search_data', $this, 'search_tags');

		$this->loader->add_action( 'wp_ajax_nopriv_ifg_wpforms_save_mapped_fields', $this, 'process_save_mapped_fields');
		$this->loader->add_action( 'wp_ajax_ifg_wpforms_save_mapped_fields', $this, 'process_save_mapped_fields');

		$this->loader->add_action('admin_enqueue_scripts', $this, 'admin_assets');

		$this->loader->add_filter( 'wpforms_builder_settings_sections', $this, 'set_form_settings_menu', 20, 2 );
		$this->loader->add_filter( 'wpforms_form_settings_panel_content', $this, 'output_form_settings_tab', 20 );
		$this->loader->add_filter( 'wpforms_process_complete', $this, 'insert_contact', 10, 4);

	}



	/**
	 * Run the main processes
	 *
	 * @since 1.0.0
	 */
	protected function run(){

		if($this->check_dependency('wpforms', self::$dependency_version) ) {

			$this->loader->run();

		}else{

			add_action('admin_notices', array($this, 'dependency_notice'));
		}
	}



	/**
	 * Display dependency messages
	 *
	 * @since  1.0.0
	 * @return string
	 */
	public function dependency_notice() {

		$this->dependency_message(array(
			'module'  => self::$name,
			'slug'    => 'wpforms',
			'name'    => self::$dependency_name,
			'version' => self::$dependency_version,
		));
    }



	/**
	 * Instert widget in statistics page
	 *
	 * @since  1.0.0
	 * @param  array $widgets
	 * @return array
	 */
	public function insert_stats_widget($widgets){

		$output = array_merge($widgets, array(
			array(
				'id'      => 'ifg-'.self::$slug,
				'title'   => __('Captured Contacts by WPForms', 'infugrator'),
				'content' => '<i class="fa fa-spinner fa-spin"></i>',
			),
		));

		return $output;
	}



	/**
	 * Add data for the widget
	 *
	 * @since 1.0.0
	 * @param array
	 * @return array
	 */
	public function add_stats_data($data_stats){

		$cached = get_transient('ifg-'.self::$slug.'-contacts');
		$sync = isset($_POST['sync']) ? (bool) $_POST['sync'] : '';

		if($sync === true || $cached == ''){

			$data    = array();
			$data[]  = array('Country', 'Contacts', array('role' => 'style'));
			$contact = new Infusionsoft_Contact();

			$today      = Infusionsoft_DataService::count($contact, array('DateCreated' => date('Y-m-d').'%', 'ReferralCode'=> 'added_by_infugrator_'.self::$slug.'_addon'));
			$yesterday  = Infusionsoft_DataService::count($contact, array('DateCreated' => date('Y-m-d',strtotime("-1 days")).'%', 'ReferralCode'=> 'added_by_infugrator_'.self::$slug.'_addon'));
			$this_month = Infusionsoft_DataService::count($contact, array('DateCreated' => date('Y-m-').'%', 'ReferralCode'=> 'added_by_infugrator_'.self::$slug.'_addon'));
			$last_month = Infusionsoft_DataService::count($contact, array('DateCreated' => date('Y-m-',strtotime('-1 month')).'%', 'ReferralCode'=> 'added_by_infugrator_'.self::$slug.'_addon'));
			$this_year  = Infusionsoft_DataService::count($contact, array('DateCreated' => date('Y-').'%', 'ReferralCode'=> 'added_by_infugrator_'.self::$slug.'_addon'));
			$last_year  = Infusionsoft_DataService::count($contact, array('DateCreated' => (date('Y')-1).'%', 'ReferralCode'=> 'added_by_infugrator_'.self::$slug.'_addon'));

			$stats = array(
				'Last Year'  => $last_year,
				'This Year'  => $this_year,
				'Last Month' => $last_month,
				'This Month' => $this_month,
				'Yesterday'  => $yesterday,
				'Today'      => $today,
			);

			foreach($stats as $key => $val){
				$data[] = array($key, $val, '#79BE37');
			}

			set_transient( 'ifg-'.self::$slug.'-contacts', $data, 60*60*24 );

		}else{
			$data = $cached;
		}

		$data_stats = array_merge($data_stats, array(
			self::$slug => array(
				'target' => 'ifg-'.self::$slug,
				'data'   => $data
			)
		));

		return $data_stats;
	}



	/**
	 * Enqueue scripts/styles in admin area
	 *
	 * @since 1.0.0
	 */
	public function admin_assets() {

	    if(isset($_GET['page']) && $_GET['page'] == 'wpforms-builder') {

		   	wp_deregister_style('select2');
			wp_deregister_script('select2');

			wp_enqueue_style('ifg-select2', plugin_dir_url(dirname(dirname(__FILE__))) . 'vendors/assets/css/select2.css', array(), '4.0.2', 'all' );
			wp_enqueue_script('ifg-select2', plugin_dir_url(dirname(dirname(__FILE__))) . 'vendors/assets/js/select2.min.js', array('ifg-pubsub'), '4.0.2', true);

			wp_enqueue_script( 'ifg-'.self::$slug.'-select', plugin_dir_url(__FILE__) . 'js/'.self::$slug.'-select.js', array('jquery', 'ifg-pubsub'), null, true );

			wp_enqueue_style('ifg-'.self::$slug, plugin_dir_url(__FILE__) . 'css/'.self::$slug.'.css', array() );
	    }

	    if(isset($_GET['page']) && $_GET['page'] == 'ifg-stats') {
	    	wp_enqueue_script('ifg-'.self::$slug, plugin_dir_url(__FILE__) . 'js/'.self::$slug.'.js', array('jquery', 'ifg-pubsub'), null, true);
    	}
	}


	/**
	 * Save the mapped fields
	 *
	 * @since  1.0.0
	 * @return array
	 */
	public function process_save_mapped_fields(){

		if(!check_ajax_referer( 'ifg-nonce', 'security', false )){
			return;
		}

		$form_id = $_POST['id'];
		$data = array();
		parse_str($_POST['data'], $data);

		$fields = $data['ifg-wpforms'];
		$save_fields = array($form_id => $fields);

		if(is_array($this->mapped_fields) && count($this->mapped_fields) > 0){
			$save_fields = $this->mapped_fields;
			$save_fields[$form_id] = $fields;
		}

		$this->utility->wp_option('update', 'ifg-'.self::$slug.'-fields', $save_fields);

		$this->mapped_fields = $save_fields; //refresh the mapped fields (kind of hack)

		wp_send_json(array(
			'data' => $this->mapped_fields,
		));
	}



	/**
	 * Get data results in searching select field
	 *
	 * @since  1.0.0
	 * @return array
	 */
	public function search_tags(){

		if(!check_ajax_referer( 'ifg-nonce', 'security', false )){
			return;
		}

		$items = array();
		$results = null;
		$data = $_POST['remote'];

		if(isset($data['type'])){

			switch($data['type']){

				case 'tags':
					$results = $this->utility->get_tags();
					break;
			}

			if( is_array($results) && count($results) > 0 ){
				foreach($results as $result){
					$items[] = $result;
				}
			}
		}

		wp_send_json(array(
			'items' => $items
		));
	}



	/**
     * Add menu item to the Form Settings page menu
     *
     * @since 1.0.0
     * @param array $sections
     * @param obj $form_data
     * @return array
     */
	public function set_form_settings_menu( $sections, $form_data ) {
	    $sections['infusionsoft'] = __( 'Infusionsoft', 'infugrator' );
	    return $sections;
	}



	/**
	 * Get form fields
	 *
	 * @since  1.0.0
	 * @param  [integer] $form_id
	 * @return [array]
	 */
    public function get_form_fields( $form_id ){
    	$get_form    = wpforms()->form->get($form_id);
		$form   = wpforms_decode( $get_form->post_content );
		$fields = array();
		$allowed = array('name', 'text', 'textarea', 'email', 'select', 'number', 'phone', 'url');

		if(is_array($form["fields"])){

            foreach($form["fields"] as $field){
            	if($field['type'] == 'name' && isset($field['format'])){
            		$names = explode('-', $field['format']);
            		foreach($names as $name){
            			$field['label'] = 'Name ('.ucfirst($name).')';
            			$fields[$field['id'].'.'.$name] = $field;
            		}
            	}elseif(in_array($field['type'], $allowed)){
            		$fields[$field['id']] = $field;
            	}
            }
        }


		return $fields;
	}



	/**
	 * Displaying content for our menu item when is selected
	 *
	 * @since 1.0.0
	 * @return html
	 */
	public function output_form_settings_tab( $instance ) {
		require_once plugin_dir_path( __FILE__ ) . 'output-form-settings-tab.php';
	}



	/**
     * Generate the HTML output of values for mapping fields
     *
     * @since  1.0.0
     * @param  string $field_name
     * @param  string  $selected_field
     * @param  array $fields
     * @return string
     */
	public function output_mapped_fields($field_name, $selected_field, $fields = array()){

		$str = '<select name="ifg-'.self::$slug.'[fields]['.$field_name.']"><option value="">-</option>';

        if(is_array($fields)){
        	foreach($fields as $key => $field){
	            $field_id = $field['id'];
	            $field_label = $field['label'];
	            $str .= '<option value="'.$key.'" '.selected(($key == $selected_field), true, false).'>' . $field_label . '</option>';
	        }
        }

        $str .= '</select>';

        return $str;
	}



	/**
     * Insert contact in infusionsoft account
     *
     * @since 1.0.0
     * @param  array $entry
     * @param  obj $form
     */
	public function insert_contact( $form_fields, $entry, $form_data, $entry_id ) {

		$form_id = $entry['id'];

    	if(isset($this->mapped_fields[$form_id]['fields'])){

    		$mapped_fields = $this->mapped_fields[$form_id]['fields'];
    		$entry_fields = $entry['fields'];

    		//check if there are filled at least first name & email fields
			if( (isset($mapped_fields['FirstName']) && $mapped_fields['FirstName'] != '') && (isset($mapped_fields['Email']) && $mapped_fields['Email'] != '') ) {

    			$merge_vars = array();
    			$email = $this->utility->rgar( $entry_fields, $mapped_fields['Email'] );

    			if(!is_email($email)){
    				return;
    			}

	    		foreach($mapped_fields as $fieldname => $val){

	    			if(strpos($val, '.') !== false){
	    				$arr = explode('.', $val);

	    				$merge_vars[$fieldname] = $this->utility->rgars( $entry_fields, $arr[0].'/'.$arr[1] );
	    			}else{
	    				$merge_vars[$fieldname] = $this->utility->rgar($entry_fields, $val);
    				}
	    		}

	    		$merge_vars['ReferralCode'] = 'added_by_infugrator_'.self::$slug.'_addon';

	    		//add contact
    			$contactId = Infusionsoft_ContactService::addWithDupCheck($merge_vars, 'Email');

				// Set opt-in marketing status
				// Infusionsoft requires a "reason" for setting the opt-in marketing status
				$reason = get_bloginfo('name'). ' - Website Contact Form';
				// And allow them to receive email marketing
				Infusionsoft_EmailService::optIn($email, $reason);

				$tags = isset($this->mapped_fields[$form_id]['tags']) ? $this->mapped_fields[$form_id]['tags'] : '';
    			$apicallname = isset($this->mapped_fields[$form_id]['apicallname']) ? $this->mapped_fields[$form_id]['apicallname'] : '';
    			$integration = $this->settings->get('application/name');

				if(is_array($tags) && count($tags) > 0){
					foreach($tags as $tag){
						Infusionsoft_ContactService::addToGroup($contactId, $tag);
					}
				}

				//trigger campaign goal
				if($apicallname != ''){
					Infusionsoft_FunnelServiceBase::achieveGoal($integration, $apicallname, $contactId);
				}
    		}

    	}

	}


}
IFG_WP_Forms::instance();