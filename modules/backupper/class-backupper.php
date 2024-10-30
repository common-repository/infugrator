<?php
/**
 * Module Name: Backupper
 * Description: A backup is always important, so keep safe your Infusionsoft account data by scheduling backups of contacts and tags.
 *
 * @link       http://infugrator.com/
 * @since      1.0.0
 * @package    Infugrator
 * @subpackage Infugrator/modules
 * @author     Cosmin Schiopu <sc.cosmin@gmail.com>
 */

class IFG_Backupper extends IFG_Module{

	/**
	 * @since    1.0.0
	 * @access   public
	 */
	public static $name = 'Backupper';


	/**
	 *	The slug of module, must be unique and as short as possible
	 *
	 * @since    1.0.0
	 * @access   public
	 */
	public static $slug = 'backupper';


	/**
	 * This is the number of the maximum backups that are available for download
	 *
	 * @since    1.0.0
	 * @access   public
	 */
	public static $max_backups = 20;


	/**
	 * @since    1.0.0
	 * @access   public
	 */
	public static $instance = null;



	/**
	 * Create a single instance of the class
	 *
	 * @since  1.0.0
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

		$this->loader->add_filter('ifg_widgets_settings', $this, 'insert_settings', 20);
		$this->loader->add_action('admin_menu', $this, 'set_page_menu', 20);

		$this->loader->add_filter('cron_schedules', $this, 'cron_schedules');
		$this->loader->add_action('infugrator_'.self::$slug.'_cron', $this, 'schedule_backup');

		$this->loader->add_filter('ifg_drop_db_tables_list', $this, 'add_drop_db_tables');

		$this->loader->add_action('init', $this, 'manual_backup');
	}



	/**
	 * Run the main processes
	 *
	 * @since 1.0.0
	 */
	protected function run(){

		$this->loader->run();

		$this->create_db_table();
		$this->create_folder(dirname(__FILE__).'/backups/');
		$this->schedule_event();
		$this->download_backup();

	}



	/**
	 * Return the base path of backups folder for each blog id
	 *
	 * @since  1.0.0
	 * @param  boolean $url
	 * @return string
	 */
	public static function base_path($url = false){

		$blog_id = get_current_blog_id();

		if($url){
			return plugin_dir_url(__FILE__).'backups/'.$blog_id.'/';
		}

		return plugin_dir_path(__FILE__).'backups/'.$blog_id.'/';
	}



	/**
	 * Add our cron schedules
	 *
	 * @since  1.0.0
	 * @param  array $schedules
	 * @return array
	 */
	public function cron_schedules($schedules){

	    if(!isset($schedules["ifg_daily"])){
	        $schedules["ifg_daily"] = array(
				'interval'    => 24*60*60,
				'display'     => __('Once Daily', 'infugrator_')
            );
	    }
	    if(!isset($schedules["ifg_weekly"])){
	        $schedules["ifg_weekly"] = array(
				'interval' => 168*60*60,
				'display'  => __('Once Weekly', 'infugrator_')
            );
	    }
	    return $schedules;
	}



	/**
	 * Schedule the event that will run the backup
	 *
	 * @since  1.0.0
	 * @return void
	 */
	private function schedule_event(){

		$recurrence = $this->settings->get(self::$slug.'/schedule') != false ? $this->settings->get(self::$slug.'/schedule') : false;
		$tables   = $this->settings->get(self::$slug.'/tables');

		if($recurrence !== false && is_array($tables) && count($tables) > 0){

			$schedule_event = wp_get_schedule('infugrator_'.self::$slug.'_cron');

			if(!$schedule_event){
				wp_schedule_event(time(), $recurrence, 'infugrator_'.self::$slug.'_cron');
			}else{
				if($recurrence != $schedule_event){
					wp_clear_scheduled_hook( 'infugrator_'.self::$slug.'_cron' );
					wp_schedule_event(time(), $recurrence, 'infugrator_'.self::$slug.'_cron');
				}
			}
		}
	}



	/**
	 * Create the backup on schedule event
	 *
	 * @since  1.0.0
	 * @return void
	 */
	public function schedule_backup(){

	    $this->create_backup();
	}



	/**
	 * Create a manual backup
	 *
	 * @since  1.0.0
	 * @return void
	 */
	public function manual_backup(){

		$nonce = $this->utility->rgar($_POST, 'ifg-nonce');

		if(wp_verify_nonce($nonce, 'ifg-nonce')){

			$user = wp_get_current_user();

			$this->create_backup($user->ID);
		}

	}



	/**
	 * Instert widget in settings page
	 *
	 * @since  1.0.0
	 * @param  array $widgets
	 * @return array
	 */
	public function insert_settings($widgets){

		ob_start();

	    include apply_filters('ifg_'.self::$slug.'_output_widgets_settings', plugin_dir_path(__FILE__) . 'widgets-settings.php');

	    $content = ob_get_clean();


		$output = array_merge($widgets, array(
			array(
				'id'      => 'ifg-'.self::$slug.'',
				'title'   => __(self::$name, 'infugrator'),
				'content' => $content,
			),
		));

		return $output;
	}



	/**
	 * Add submenu page
	 *
	 * @since 1.0.0
	 */
	public function set_page_menu(){

		add_submenu_page(
			'infugrator',
			__('Backupper', 'infugrator'),
			__('Backupper', 'infugrator'),
			'moderate_comments',
			'ifg-'.self::$slug,
			array($this, 'output_page')
		);

	}
	public function output_page(){
		require_once apply_filters('ifg_'.self::$slug.'_output_page', plugin_dir_path(__FILE__) . 'output-page.php');
	}



	/**
	 * Create folders and add index.php for avoiding directly access of the files
	 *
	 * @since  1.0.0
	 * @param  string $path
	 * @return bool
	 */
	public function create_folder($path){

		if(is_writable(dirname(__FILE__))){
			if(!file_exists($path)){
	            mkdir($path, 0777, true);
	        }
	        if(!file_exists($path.'index.php')){
	        	fopen($path.'index.php', 'w');
	        }

	        return true;

        }else{
        	add_action('admin_notices', array($this, 'create_folder_notice'));

        	return false;
        }
	}



	/**
	 * Display notice message on creating folder action
	 *
	 * @since  1.0.0
	 * @return string
	 */
	public function create_folder_notice(){

		$msg = sprintf(
			__('This path %s must be writable!', 'infugrator'),
			'<b>'.dirname(__FILE__).'</b>'
		);

		echo '<div class="ifg-error notice">'.$msg.'</div>';
	}



	/**
	 * Create zip archive of backup
	 *
	 * @since  1.0.0
	 * @param  string $archive_path
	 * @param  string $csv_path
	 * @param  string $csv_name
	 * @return void
	 */
	public function create_archive($archive_path, $csv_path, $csv_name){

		if(class_exists('ZipArchive')){

			$zip = new ZipArchive();
			if ($zip->open($archive_path, ZIPARCHIVE::CREATE )!== false) {
				$zip->addFile($csv_path, $csv_name);
				$zip->close();

				unlink($csv_path);
			}
		}else{
			add_action('admin_notices', function(){
				echo '<div class="ifg-error notice">Class <b>ZipArchive</b> is not available, please contact your hosting team to install zip extension on your PHP version.</div>';
			});
		}
	}



	/**
	 * Create the backup
	 *
	 * @since  1.0.0
	 * @return void
	 */
	public function create_backup($by = '0'){

		$tables = $this->settings->get(self::$slug.'/tables');

		foreach($tables as $table_name){

	        $backup_path = self::base_path().strtolower($table_name).'/';

	        //if the folder has been successfully created
			if($this->create_folder($backup_path)){

				$class_name = "Infusionsoft_" . $table_name;
				$object = null;
		        $reflection = new ReflectionObject(new $class_name());

		        if($reflection->hasProperty('customFieldFormId')){
		            $custom_fields = Infusionsoft_DataService::getCustomFields(new $class_name());
		        	$class_name = new $class_name();
		            $class_name->addCustomFields(array_keys($custom_fields));
		        }

				$object   = new $class_name();
				$datetime = date('Y-m-d_H-i');

		        $archive_name = 'backup-'.$datetime.'.zip';
		        $archive_path = $backup_path.$archive_name;

		        $csv_name = 'backup-'.$datetime.'.csv';
		        $csv_path = $backup_path.$csv_name;
		        $csv_file = fopen($csv_path, 'w');

		        fputcsv($csv_file, $object->getFields(), ",", "\"");

		        $page = 0;

		        do{
		    		// flush();

		            $object_fields = $object->getFields();

		        	if(in_array('Email', $object_fields)){
		                $results = Infusionsoft_DataService::queryWithOrderBy(new $class_name(), array('Email' => '%'), 'Email', true, 100, $page );
		            }else {
		                $results = Infusionsoft_DataService::queryWithOrderBy(new $class_name(), array($object_fields[0] => '%'), $object_fields[0], true, 100, $page );
		            }

		            foreach($results as $result){
		                fputcsv($csv_file, $result->toArray(), ",", "\"");
		            }

		    	 	$page++;

		        }while(count($results) > 0);

		        fclose($csv_file);

		        $this->create_archive($archive_path, $csv_path, $csv_name);
		        $this->save_backup($archive_name, $table_name, $by);

		        $this->remove_old_backups();
			}
		}

	}



	/**
	 * Save backup information in DB
	 *
	 * @since  1.0.0
	 * @param  string $filename
	 * @param  string $table_name
	 * @param  string $by
	 * @return void
	 */
	public function save_backup($filename, $table_name, $by = 'system'){

		global $wpdb;

      	$wpdb->insert(
			$wpdb->prefix.'ifg_backupper',
			array(
				'date_time'  => strtotime(date('Y-m-d H:i')),
				'filename'   => $filename,
				'created_by' => $by,
				'table_name' => strtolower($table_name)
			),
			array(
				'%s',
				'%s',
				'%s',
				'%s'
			)
		);
	}



	/**
	 * Remove old backups. This will make sure the maximum number of backups will not be exceeded.
	 *
	 * @since  1.0.0
	 * @return void
	 */
	protected function remove_old_backups(){

		global $wpdb;

		$tables = $this->settings->get(self::$slug.'/tables');

		if(is_array($tables) && count($tables) > 0){

			foreach($tables as $table_name){

				$count = $wpdb->get_var("
					SELECT COUNT(*)
					FROM {$wpdb->prefix}ifg_backupper
					WHERE table_name = '{$table_name}'
				");

				//remove backup if the number of backups has been exceeded the maximum number of available backups
				if($count > self::$max_backups){
					$result = $wpdb->get_row("
						SELECT *
						FROM {$wpdb->prefix}ifg_backupper
						WHERE table_name = '{$table_name}'
						ORDER BY id ASC
					");

					if($result !== null){
						if(file_exists(self::base_path().$result->table_name.'/'.$result->filename)){
							unlink(self::base_path().$result->table_name.'/'.$result->filename);
						}

						$wpdb->query("DELETE FROM {$wpdb->prefix}ifg_backupper ORDER BY id ASC LIMIT 1");
					}
				}

			}
		}
	}



	/**
	 * Create a list of existing backups
	 *
	 * @since  1.0.0
	 * @param  string $table_name
	 * @return array
	 */
	public function list_backups($table_name){

		global $wpdb;


		$results = $wpdb->get_results("
			SELECT *
			FROM {$wpdb->prefix}ifg_backupper
			WHERE table_name = '{$table_name}'
			ORDER BY id DESC
		");

		return $results;
	}



	/**
	 * Force download backup
	 *
	 * @since  1.0.0
	 * @return string|void
	 */
	public function download_backup(){

		if(isset($_GET['backup'])){

			$file_path = self::base_path().$_GET['backup'];
			$file_url = self::base_path(true).$_GET['backup'];
			$file_name = substr($file_url, strrpos($file_url, '/')+1 );

			if(file_exists($file_path)){

				header("Content-type: application/zip");
				header("Content-Disposition: attachment; filename={$file_name}");
				header("Pragma: no-cache");
				header("Expires: 0");

				readfile("$file_path");
    			exit;
			}else{
				add_action('admin_notices', array($this, 'download_message'));
			}
		}
	}



	/**
	 * Display error on download process
	 *
	 * @since  1.0.0
	 * @return string
	 */
	public function download_message(){
		echo '<div class="ifg-error notice">'.__('The file you\'re trying to download does not exist.', 'infugrator').'</div>';
	}




	/**
	 * Create the db table for storing backup information
	 *
	 * @since  1.0.0
	 * @return void
	 */
	public function create_db_table(){

		global $wpdb, $charset_collate;

		$table_name = $wpdb->prefix.'ifg_backupper';

		if($wpdb->get_var("SHOW TABLES LIKE '$table_name'") != $table_name) {

			$table = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}ifg_backupper (
				id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
				date_time varchar(191) NOT NULL,
				filename varchar(191) NOT NULL,
				created_by varchar(191) NOT NULL,
				table_name varchar(191) NOT NULL,
				PRIMARY KEY (`id`)
			) $charset_collate ";

			require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );

			dbDelta( $table );
		}
	}



	/**
	 * Add table name in the drop tables list for deleting
	 *
	 * @since  1.0.0
	 * @param array $tables
	 */
	public function add_drop_db_tables($tables){

		global $wpdb;

		return array_merge($tables, array(
			$wpdb->prefix . 'ifg_backupper'
		));
	}




}
IFG_Backupper::instance();