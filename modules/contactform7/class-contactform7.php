<?php
/**
 * Module Name: Contact Form 7 Add-on
 * Description: Collects form submissions from <a href="https://contactform7.com/" target="_blank">Contact Form 7</a> plugin and send them to your Infusionsoft account. Applies tags and triggers campaign goals.
 *
 * @link       http://infugrator.com/
 * @since      1.0.0
 * @package    Infugrator
 * @subpackage Infugrator/modules
 * @author     Cosmin Schiopu <sc.cosmin@gmail.com>
 */

class IFG_Contact_Form7 extends IFG_Module{

	/**
	 * @since    1.0.0
	 * @access   public
	 */
	public static $name = 'Contact Form 7 Add-on';


	/**
	 *	The slug of module, must be unique and as short as possible
	 *
	 * @since    1.0.0
	 * @access   public
	 */
	public static $slug = 'contactform7';


	/**
	 * @since    1.0.0
	 * @access   public
	 */
	public static $dependency_name = '<a href="https://contactform7.com/" target="_blank">Contact Form 7</a>';


	/**
	 * @since    1.0.0
	 * @access   public
	 */
	public static $dependency_version = 45;


	/**
	 * @since    1.0.0
	 * @access   protected
	 */
	protected $inf_fields;


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

		$this->inf_fields = array(
			'FirstName'      => 'First Name',
			'LastName'       => 'Last Name',
			'Email'          => 'Email',
			'Phone1'         => 'Phone Number',
			'Country'        => 'Country',
			'City'           => 'City',
			'State'          => 'State',
			'StreetAddress1' => 'Street Address',
			'PostalCode'     => 'Postal Code',
			'Company'        => 'Company',
			'Website'        => 'Website',
		);

		$this->loader->add_filter( 'ifg_widgets_stats', $this, 'insert_stats_widget');
		$this->loader->add_filter( 'ifg_stats_data', $this, 'add_stats_data');

		//this will catch the sync stats process and update the data
		$this->loader->add_action( 'wp_ajax_nopriv_ifg_select_search_data', $this, 'add_stats_data');
		$this->loader->add_action( 'wp_ajax_ifg_select_search_data', $this, 'add_stats_data');

		$this->loader->add_action( 'wp_ajax_nopriv_ifg_contactform7_search_data', $this, 'search_tags');
		$this->loader->add_action( 'wp_ajax_ifg_contactform7_search_data', $this, 'search_tags');

		$this->loader->add_action('admin_enqueue_scripts', $this, 'admin_assets');
		$this->loader->add_action('admin_init', $this, 'add_tag_generator');
		$this->loader->add_action('wpcf7_editor_panels', $this, 'add_tag_page_panels');
		$this->loader->add_action('wpcf7_after_save', $this, 'save_contact_form');
		$this->loader->add_action('wpcf7_mail_sent', $this, 'form_submitted');
	}



	/**
	 * Run the main processes
	 *
	 * @since 1.0.0
	 */
	protected function run(){

		if($this->check_dependency('contact-form-7', self::$dependency_version) ) {

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
			'slug'    => 'contact-form-7',
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
				'title'   => __('Captured Contacts by Contact Form 7', 'infugrator'),
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

	    if(isset($_GET['page']) && ($_GET['page'] == 'wpcf7' || $_GET['page'] == 'wpcf7-new')) {

		   	wp_deregister_style('select2');
			wp_deregister_script('select2');

			wp_enqueue_style('ifg-select2', plugin_dir_url(dirname(dirname(__FILE__))) . 'vendors/assets/css/select2.css', array(), '4.0.2', 'all' );
			wp_enqueue_script('ifg-select2', plugin_dir_url(dirname(dirname(__FILE__))) . 'vendors/assets/js/select2.min.js', array('ifg-pubsub'), '4.0.2', true);

		   	wp_enqueue_script('ifg-'.self::$slug.'-dep', plugin_dir_url(__FILE__) . 'js/'.self::$slug.'-dep.js', array('jquery', 'wpcf7-admin-taggenerator', 'wpcf7-admin'), null, true);
	    }


	    if(isset($_GET['page']) && $_GET['page'] == 'ifg-stats') {
	    	wp_enqueue_script('ifg-'.self::$slug, plugin_dir_url(__FILE__) . 'js/'.self::$slug.'.js', array('jquery', 'ifg-pubsub'), null, true);
    	}
	}



	/**
	 * Add tag generator button
	 *
	 * @since 1.0.0
	 */
	public function add_tag_generator() {

		if( function_exists('wpcf7_add_tag_generator') ) {
			wpcf7_add_tag_generator( 'infusionsoft', 'Infusionsoft', 'wpcf7-tg-pane-infusionsoft', array($this, 'output_tag_generator') );
		}
	}


	/**
	 * Output HTML of tag generator
	 *
	 * @since 1.0.0
	 */
	public function output_tag_generator($form, $args = ''){

		require_once plugin_dir_path(__FILE__) . 'tag-generator.php';
	}



	/**
	 * Add tab options.
	 *
	 * CF7 >= 4.2
	 *
	 * @since 1.0.0
	 */
	public function add_tag_page_panels($panels) {

	    $panels['infusionsoft-panel'] = array( 'title' => 'Infusionsoft Options', 'callback' => array($this, 'output_tag_panel_meta') );
	    return $panels;
	}



	/**
	 * Create the panel inputs (CF7 >= 4.2)
	 *
	 * @since  1.0.0
	 */
	public function output_tag_panel_meta( $post ) {

	    $tags = get_post_meta( $post->id(), 'ifg-'.self::$slug.'-tags', true );
	    ?>

	    <h3><?php _e('Apply Tags', 'infugrator');?></h3>
	    <p><?php _e('Select what tags to be applied to the contact.', 'infugrator');?></p>

	    <select style="display: none;width: 100%;" multiple name="ifg-<?php echo self::$slug;?>-tags[]" data-remote='{"type": "tags"}'>
	    	<?php if(is_array($tags) && count($tags) > 0): ?>
				<?php foreach($tags as $tag): ?>
					<option <?php selected($tag, $this->utility->get_tag_data($tag, 'id'));?> value="<?php echo $this->utility->get_tag_data($tag, 'id');?>"><?php echo $this->utility->get_tag_data($tag, 'label');?></option>
				<?php endforeach; ?>
			<?php endif; ?>
	    </select>

		<h3><?php _e('Trigger Campaign Goal on Success', 'infugrator');?></h3>
	    <?php $this->utility->output_trigger_campaign($post->id(), 'ifg-'.self::$slug.'-apicallname');?>

	    <?php wp_nonce_field( plugin_basename(__FILE__), 'ifg-'.self::$slug.'-nonce' );
	}



	/**
	 * Store Infusionsoft tag
	 */
	public function save_contact_form( $form ) {

		$form_id = $form->id();

	    if ( isset($_POST['ifg-'.self::$slug.'-nonce']) && !wp_verify_nonce( $_POST['ifg-'.self::$slug.'-nonce'], plugin_basename(__FILE__) )) {
	        return;
	    }

	    // Is the user allowed to edit the post or page?
		if ( !current_user_can( 'edit_post', $form_id )){
			return;
		}

		if ( isset( $_POST['ifg-'.self::$slug.'-tags'] ) ) {
	        update_post_meta( $form_id, 'ifg-'.self::$slug.'-tags', $_POST['ifg-'.self::$slug.'-tags'] );
	    }

	    if ( isset( $_POST['ifg-'.self::$slug.'-apicallname'] ) ) {
	        update_post_meta( $form_id, 'ifg-'.self::$slug.'-apicallname', $_POST['ifg-'.self::$slug.'-apicallname'] );
	    }
	}



	/**
	 * Get tags in searching select field
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
	 * This iss running while the form is submitted
	 *
	 * @since  1.0.0
	 * @param  obj $form
	 */
	public function form_submitted( $form ) {

		$form_id = $form->id();
		$submission = WPCF7_Submission::get_instance();
	  	$posted_data = $submission->get_posted_data();

	  	//add contact in infusionsoft
		$this->insert_contact($form_id, $posted_data);
	}



	/**
     * Insert contact in infusionsoft account
     *
     * @since  1.0.0
     * @param  int $form_id
     * @param  array $posted_data
     */
	public function insert_contact($form_id, $posted_data) {

		if(is_array($this->inf_fields) && count($this->inf_fields) > 0){

			//check if there are filled at least first name & email fields
			if( (isset($posted_data['infusionsoft-FirstName']) && $posted_data['infusionsoft-FirstName'] != '') && (isset($posted_data['infusionsoft-Email']) && $posted_data['infusionsoft-Email'] != '') ) {

				$merge_vars = array();
				$email = $this->utility->rgar( $posted_data, 'infusionsoft-Email' );

				if(!is_email($email)){
    				return;
    			}

				foreach($this->inf_fields as $field_name => $label){

					if(isset($posted_data['infusionsoft-'.$field_name]) && $posted_data['infusionsoft-'.$field_name] != ''){
						$merge_vars[$field_name] = $posted_data['infusionsoft-'.$field_name];
					}
				}
				$merge_vars['ReferralCode'] = 'added_by_infugrator_'.self::$slug.'_addon';


	    		//add contact in infusionsoft
    			$contactId = Infusionsoft_ContactService::addWithDupCheck($merge_vars, 'Email');

    			// Set opt-in marketing status
				// Infusionsoft requires a "reason" for setting the opt-in marketing status
				$reason = get_bloginfo('name'). ' - Website Contact Form';
				// And allow them to receive email marketing
				Infusionsoft_EmailService::optIn($email, $reason);

				$tags = get_post_meta( $form_id, 'ifg-'.self::$slug.'-tags', true );
				$apicallname = get_post_meta( $form_id, 'ifg-'.self::$slug.'-apicallname', true );
				$integration = $this->settings->get('application/name');

				if(is_array($tags) && count($tags) > 0){
					foreach($tags as $tag){
						Infusionsoft_ContactService::addToGroup($contactId, $tag);
					}
				}

				//trigger campaign goal
				if($apicallname !=''){
					Infusionsoft_FunnelServiceBase::achieveGoal($integration, $apicallname, $contactId);
				}
			}
		}

	}


}
IFG_Contact_Form7::instance();