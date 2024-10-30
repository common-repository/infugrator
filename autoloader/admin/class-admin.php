<?php
/**
 * This class creates admin area.
 *
 * @link       http://infugrator.com/
 * @since      1.0.0
 * @package    Infugrator
 * @subpackage Infugrator/admin
 * @author     Cosmin Schiopu <sc.cosmin@gmail.com>
 */

class IFG_Admin {

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
	 * @access   protected
	 */
	protected $modules;


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



	public function __construct() {

      add_action('admin_notices', function(){
         echo '<div class="error notice"><p><b>Infugrator Error:</b> This plugin version is no longer developed, please check the new version <a href="http://fusedpress.com" target="_blank">here</a></p></div>';
      });

      return;

		$this->loader = new IFG_Loader;
		$this->settings = new IFG_Settings;
		$this->utility = new IFG_Utility;

		$this->modules = $this->utility->wp_option('get', 'ifg-modules');

		$this->loader->add_action( 'admin_enqueue_scripts', $this, 'admin_assets' );
		$this->loader->add_action( 'admin_menu', $this, 'set_page_menu' );
		$this->loader->add_action( 'parent_file', $this, 'set_current_parent_menu' );
		$this->loader->add_action( 'submenu_file', $this, 'set_current_submenu' );
		$this->loader->add_action( 'plugins_loaded', $this, 'load_plugin_textdomain' );
		$this->loader->run();

		$this->process_modules();

	}



	/**
	 * Enqueue scripts/styles in admin area
	 *
	 * @since    1.0.0
	 */
	public function admin_assets() {

		//deregister
		wp_deregister_style('yoast-seo-select2');
		wp_deregister_script('yoast-seo-select2');
		wp_deregister_script('admin-global');
		wp_deregister_script('yoast-seo-replacevar-plugin');
		wp_deregister_script('yoast-seo-shortcode-plugin');


		//register
		wp_register_style( 'ifg-select2', plugin_dir_url( dirname(dirname(__FILE__)) ) . 'vendors/assets/css/select2.css', array(), '4.0.2', 'all' );
		wp_register_script( 'ifg-select2', plugin_dir_url( dirname(dirname(__FILE__)) ) . 'vendors/assets/js/select2.min.js', array('jquery'), '4.0.2', true );

		wp_register_style( 'ifg-codemirror', plugin_dir_url( dirname(dirname(__FILE__)) ) . 'vendors/assets/css/codemirror.css', array(), IFG_VERSION, 'all' );
		wp_register_script( 'ifg-codemirror', plugin_dir_url( dirname(dirname(__FILE__)) ) . 'vendors/assets/js/codemirror/codemirror.js', array( 'jquery' ), IFG_VERSION, true );
		wp_register_script( 'ifg-codemirror-mode-xml', plugin_dir_url( dirname(dirname(__FILE__)) ) . 'vendors/assets/js/codemirror/mode-xml.js', array( 'ifg-codemirror' ), IFG_VERSION, true );
		wp_register_script( 'ifg-codemirror-mode-javascript', plugin_dir_url( dirname(dirname(__FILE__)) ) . 'vendors/assets/js/codemirror/mode-javascript.js', array( 'ifg-codemirror-mode-xml' ), IFG_VERSION, true );
		wp_register_script( 'ifg-codemirror-mode-css', plugin_dir_url( dirname(dirname(__FILE__)) ) . 'vendors/assets/js/codemirror/mode-css.js', array( 'ifg-codemirror-mode-javascript' ), IFG_VERSION, true );
		wp_register_script( 'ifg-codemirror-mode-htmlmixed', plugin_dir_url( dirname(dirname(__FILE__)) ) . 'vendors/assets/js/codemirror/mode-htmlmixed.js', array( 'ifg-codemirror-mode-css' ), IFG_VERSION, true );


		//enqueue
		wp_enqueue_style( 'wp-color-picker' );
		wp_enqueue_script( 'wp-color-picker' );

		wp_enqueue_style( 'ifg-grid', plugin_dir_url( dirname(dirname(__FILE__)) ) . 'vendors/assets/css/grid.css', array(), IFG_VERSION, 'all' );
		wp_enqueue_style( 'ifg-fontawesome', plugin_dir_url( dirname(dirname(__FILE__)) ) . 'vendors/assets/css/font-awesome.min.css', array(), '4.7.0', 'all' );
		wp_enqueue_style( 'ifg-admin', plugin_dir_url( __FILE__ ) . 'css/admin.css', array(), IFG_VERSION, 'all' );

		wp_enqueue_script( 'ifg-pubsub', plugin_dir_url( dirname(dirname(__FILE__)) ) . 'vendors/assets/js/pubsub.js', array( 'jquery' ), IFG_VERSION, true );
		wp_enqueue_script( 'ifg-admin', plugin_dir_url( __FILE__ ) . 'js/admin.js', array( 'jquery' ), IFG_VERSION, true );

		wp_localize_script( 'ifg-admin', 'ifg', array(
			'ajaxUrl' => admin_url( 'admin-ajax.php' ),
			'ajaxNonce' => wp_create_nonce( 'ifg-nonce' )
		));
	}



	/**
	 * Load the plugin text domain for translation.
	 *
	 * @since    1.0.0
	 */
	public function load_plugin_textdomain() {

		load_plugin_textdomain(
			'infugrator',
			false,
			dirname( dirname( plugin_basename( __FILE__ ) ) ) . '/languages/'
		);

	}



	/**
	 * Show message if application details are not filled
	 *
	 * @since  1.0.0
	 * @return string
	 */
	public function app_settings_notice() {

		$msg = sprintf(
			__('Please provide the %s details!', 'infugrator'),
			'<a href="'.admin_url('admin.php?page=ifg-settings&ref='.rand(1,666).'#ifg-app-config').'">Infusionsoft Application</a>'
		);

	   	echo '<div class="ifg-error notice">'.$msg.'</div>';
	}



	/**
	 * Add menu page and overwrite with a submenu
	 *
	 * @since 	1.0.0
	 */
	public function set_page_menu(){

		add_menu_page(
			__('Infugrator', 'infugrator'),
			__('Infugrator', 'infugrator'),
			'moderate_comments',
			'infugrator',
			array($this, 'output_page'),
			plugin_dir_url( __FILE__ ).'images/menu-icon.png'
			// 'dashicons-screenoptions'
		);

		add_submenu_page(
    		'infugrator',
    		__('Dashboard', 'infugrator'),
    		__('Dashboard', 'infugrator'),
    		'moderate_comments',
    		'infugrator',
    		array($this, 'output_page')
		);
	}



	/**
	 * Output HTML of page
	 *
	 * @since 1.0.0
	 * @return striing
	 */
	public function output_page() {
		?>
		<div class="wrap">
			<div class="header">
				<div class="header__title">
					<?php
					echo sprintf(
						__('Welcome to Infugrator %s', 'infugrator'),
						'<span class="ifg-plugin-version">'.IFG_VERSION.'</span>'
					);
					?>
				</div>
				<div class="header__msg"><?php _e('Thank you for activating this plugin!', 'infugrator');?></div>
			</div>

			<?php echo $this->available_modules();?>
		</div>

		<?php
	}



	/**
	 * Processing modules
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function process_modules(){

		//if app name/key are empty, disable all modules
		if($this->settings->get('application/name') =='' || $this->settings->get('application/key') ==''){
			$this->disable_modules();
		}

		//enable/disable a module
		$this->toggle_module_status();
	}



	/**
	 * Disable all modules
	 *
	 * @since  1.0.0
	 */
	private function disable_modules(){

		$modules = $this->modules;

		if(isset($modules['active'])){

			unset($modules['active']);

			$this->utility->wp_option('update', 'ifg-modules', $modules);
		}
	}



	/**
	 * Enable/disable a module
	 *
	 * @since 1.0.0
	 */
	public function toggle_module_status(){

		if(isset($_POST['ifg-activate-module'])){

			$modules = $this->modules;
			$path    = $_POST['ifg-activate-module'];

			//make sure we have infusionsoft details filled first
			if($this->settings->get('application/name') == '' || $this->settings->get('application/key') == ''){

				add_action( 'admin_notices', array($this, 'app_settings_notice') );

				return;
			}

			if(isset($modules['active'])){

				//if it's already there, then remove it.
				if(in_array($path, $modules['active'])){
					$key = array_search($path, $modules['active']);
					unset($modules['active'][$key]);
				}else{
					array_push($modules['active'], $path);
				}

			}else{
				$modules['active'] = array($path);
			}

			$this->utility->wp_option('update', 'ifg-modules', $modules);

		}

	}



	/**
	 * Generate the list of modules
	 *
	 * @since  1.0.0
	 * @return string
	 */
	public function available_modules(){

		if(isset($this->modules['list']) && is_array($this->modules['list'])): ?>

			<div class="row">
				<?php foreach($this->modules['list'] as $path):

					if(!file_exists($path)){
						continue;
					}

					$data = get_file_data($path, array(
						'name' => 'Module Name',
						'desc' => 'Description',
						'premium' => 'Premium',
					));

					$title = $data['name'];
					$desc = $data['desc'];

					$status = (isset($this->modules['active']) && in_array($path, $this->modules['active'])) ? 'activated' : '';
					$button = isset($this->modules['active']) && in_array($path, $this->modules['active']) ? 'Disable' : 'Enable';

					//this helps us to show when a module is enabled or disabled
					if(isset($_POST['ifg-activate-module']) && $_POST['ifg-activate-module'] == $path){
						$modules = $this->utility->wp_option('get', 'ifg-modules');

						if(isset($modules['active']) && in_array($path, $modules['active'])){
							$status = 'activated';
							$button = 'Disable';
						}else{
							$status = '';
							$button = 'Enable';
						}
					}

					if(!empty($title)):
					?>
		        		<div class="col-md-6 col-lg-4">
			            	<div class="module <?php echo $status;?>">
			            		<div class="module__title"><?php echo $title;?></div>

			            		<div class="module__desc">
			            			<?php echo $desc;?>
		            			</div>

		            			<form action="" method="post">
            						<input type="hidden" name="ifg-activate-module" value="<?php echo $path;?>">
            						<button type="submit" class="button button-secondary button-large"><span><?php echo $button;?></span></button>
            					</form>
		            		</div>
		        		</div>
		        	<?php endif;?>
	        	<?php endforeach;?>
        	</div>

    	<?php endif;
	}



		/**
	 * Set current the parent of current menu
	 *
	 * @since  1.0.0
	 * @param string $parent_menu
	 */
	public function set_current_parent_menu( $parent_menu ) {

		global $current_screen, $pagenow;

		$menu_items = apply_filters('ifg_set_current_menu_items', array());

		if ( in_array($current_screen->post_type, $menu_items)) {

            $parent_menu = 'infugrator';

            if ( $pagenow == 'post.php' || $pagenow == 'post-new.php' ) {
                $parent_menu = 'infugrator';
            }

        	return $parent_menu;
        }

    }



    /**
	 * Set current the parent of current submenu
	 *
	 * @since  1.0.0
	 * @param string $submenu
	 */
    public function set_current_submenu( $submenu ) {

        global $current_screen, $pagenow;

		$menu_items = apply_filters('ifg_set_current_menu_items', array());

		foreach($menu_items as $item){
			if ( $current_screen->post_type == $item) {

				$submenu = $item;

				if ( $pagenow == 'post.php' || $pagenow == 'post-new.php' ) {
					$submenu = $item;
				}

				return $submenu;
			}
		}

    }


}
IFG_Admin::instance();
