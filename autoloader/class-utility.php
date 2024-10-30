<?php
/**
 * This is a utility class
 *
 * @link       http://infugrator.com/
 * @since      1.0.0
 * @package    Infugrator
 * @subpackage Infugrator/autoloader/utility
 * @author     Cosmin Schiopu <sc.cosmin@gmail.com>
 */

class IFG_Utility{


	public function settings(){

		$settings = new IFG_Settings;

		return $settings;
	}



	/**
	 * Get wp options working on both single and multisite
	 *
	 * @since  1.0.0
	 * @param  string  $action  e.g: get, update, delete
	 * @param  sting  $option   the option name
	 * @param  string $default 	the default value to return
	 * @param  int  $blog_id    blog id if is necessary
	 * @return bool
	 */
	public function wp_option($action, $option, $default = '', $blog_id = null){

		if(is_multisite()){

			$blog_id = is_null($blog_id) ? get_current_blog_id() : $blog_id;

			$data = $this->multi_site($action, $option, $default, $blog_id);

		}else{

			$data = $this->single_site($action, $option, $default);
		}

		return $data;

	}



	protected function single_site($action, $option, $default = false){

		switch($action){

			case 'get';

				return get_option($option, $default);

				break;

			case 'update';

				$value = $default; // use default paramenter as value
				return update_option($option, $value);

				break;

			case 'delete';
				return delete_option($option);

				break;

		}
	}


	protected function multi_site($action, $option, $default = false, $blog_id){

		switch($action){

			case 'get';

				return get_blog_option($blog_id, $option, $default);

				break;

			case 'update';

				$value = $default; // use default paramenter as value
				return update_blog_option($blog_id, $option, $value);

				break;

			case 'delete';

				return delete_blog_option($blog_id, $option);

				break;

		}
	}



	/**
	 * Validate an url
	 *
	 * @since  1.0.0
	 * @param  string $url
	 * @return string
	 */
	public function get_valid_url($url = ''){

		if(!empty($url)){

			if(in_array(parse_url($url, PHP_URL_SCHEME), array('http','https'))){

		    	if (filter_var($url, FILTER_VALIDATE_URL) !== false) {
			        //valid url
			        return $url;
			    }else{
			        //not valid url
			    }
			}else{
			    //no http or https
			    return 'http://'.$url;
			}
		}
	}



	/**
	 * Get all tags and cache them with wp transient
	 *
	 * @since 1.0.0
	 * @return array
	 */
	public function get_tags(){

		$cached = get_transient('ifg-tags');

		if($cached == ''){

			$table = new Infusionsoft_ContactGroup();
			$data  = array();
			$limit = 1000;
			$q     = isset($_POST['q']) && $_POST['q'] != '' ? '%'.$_POST['q'].'%' : '%';

			$count = Infusionsoft_DataService::count($table, array('GroupName' => $q));
			$pages = ceil($count / $limit);

			for($i = 0; $i < $pages; $i++){

				try {
				    $results = Infusionsoft_DataService::query($table, array('GroupName' => $q), $limit, $i);

					foreach($results as $item){
						$data[$item->Id] = array(
							'id' => $item->Id,
							'name' => $item->GroupName,
							'label' => '('.$item->Id.') '.$item->GroupName,
						);
					}

				} catch (Exception $e) {
				    // echo 'Caught exception: ',  $e->getMessage(), "\n";
				}

			}

			set_transient( 'ifg-tags', $data, 60*60*24 );

		}else{
			$data = $cached;
		}


		return $data;
	}



	/**
	 * Retrieve tag information (id, name, etc)
	 *
	 * @since  1.0.0
	 * @param  int $id
	 * @param  string  $prop
	 * @return array|string
	 */
	public function get_tag_data($id, $prop = ''){

		$tags = $this->get_tags();

		if( isset($tags[$id]) ){
			if(isset($tags[$id][$prop])){
				return $tags[$id][$prop];
			}

			return $tags[$id];
		}

	}



	/**
	 * Get all products and cache them with wp transient
	 *
	 * @since 1.0.0
	 * @return array
	 */
	public function get_products( $subscriptions = false){

		$cached = get_transient('ifg-products');

		if($cached == ''){

			$table = new Infusionsoft_Product();
			$data  = array();
			$limit = 1000;
			$q     = isset($_POST['q']) && $_POST['q'] != '' ? '%'.$_POST['q'].'%' : '%';

			$count = Infusionsoft_DataService::count($table, array('ProductName' => $q));
			$pages = ceil($count / $limit);

			for($i = 0; $i < $pages; $i++){

				try {
				    $results = Infusionsoft_DataService::query($table, array('ProductName' => $q), $limit, $i);

					foreach($results as $item){
						$data[$item->Id] = array(
							'id'            => $item->Id,
							'name'          => $item->ProductName,
							'label'         => '($'.$item->ProductPrice.') '.$item->ProductName,
							'price'         => $item->ProductPrice,
							'subscriptions' => $this->get_subscriptions($item->Id),
						);
					}

				} catch (Exception $e) {
				    // echo 'Caught exception: ',  $e->getMessage(), "\n";
				}

			}

			set_transient( 'ifg-products', $data, 60*60*24 );

		}else{
			$data = $cached;
		}


		if($subscriptions){

			foreach($data as $key => $product){
				if($product['subscriptions'] === false){
					unset($data[$key]);
				}
			}
		}


		return $data;
	}



	/**
	 * Retrieve product information (id, name, price, etc)
	 *
	 * @since  1.0.0
	 * @param  int $id
	 * @param  string  $prop
	 * @return array|string
	 */
	public function get_product_data($id, $prop = ''){

		$products = $this->get_products();

		if(isset($products[$id])){
			if(isset($products[$id][$prop])){
				return $products[$id][$prop];
			}

			return $products[$id];

		}

	}



	/**
	 * Get all subscriptions for a given product
	 *
	 * @since 1.0.0
	 * @param  int $product_id
	 * @return array
	 */
	public function get_subscriptions( $product_id ){

		$table = new Infusionsoft_SubscriptionPlan();
		$limit = 1000;

		$count = Infusionsoft_DataService::count($table, array('ProductId' => $product_id));
		$pages = ceil($count / $limit);

		$data = $count > 0 ? array() : false;

		for($i = 0; $i < $pages; $i++){
			try {
			    $results = Infusionsoft_DataService::query($table, array('ProductId' => $product_id), $limit, $i);

				foreach($results as $item){
					$data[$item->Id] = array(
						'id'    => $item->Id,
						'label' => $this->get_subscription_readable(array(
							'Cycle'          => $item->Cycle,
							'Frequency'      => $item->Frequency,
							'NumberOfCycles' => $item->NumberOfCycles,
							'PlanPrice'      => $item->PlanPrice,
						)),
					);
				}

			} catch (Exception $e) {
			    // echo 'Caught exception: ',  $e->getMessage(), "\n";
			}
		}


		return $data;
	}



	/**
	 * Make subscription readable
	 *
	 * @since  1.0.0
	 * @param  array $sub
	 * @return string
	 */
    public function get_subscription_readable($sub = null){

		if(is_array($sub) && count($sub) > 0){

			$num_cycles = $sub['NumberOfCycles']; //# of cycles
			$frequency  = $sub['Frequency']; //bill every...
			$cycle      = $sub['Cycle']; //year, month, week, day
			$price      = $sub['PlanPrice'];

			$period      = ucfirst($this->get_subscription_cycle($cycle));
			$periods     = ucfirst($this->get_subscription_cycle($cycle)).'s';
			$for_text    = ($num_cycles * $frequency) > 0 ? ' for '.($num_cycles * $frequency).' '.$periods : '';
			$freq_text   = $frequency > 1 ? $frequency.' '.$periods : $period;
			$cycles_text = $frequency > 1 ? $periods : $period;

			return 'Bill $'.$price.' Every '.$freq_text.$for_text;

		}else{
			return 'Bill $'.$price.' One Time Only';
		}
	}



	/**
	 * Make subscription cycles readable
	 *
	 * @since  1.0.0
	 * @param  string $freq
	 * @return string
	 */
	public function get_subscription_cycle($freq = null){

		switch ($freq) {
			case '1':
				return 'year';
				break;

			case '2':
				return 'month';
				break;

			case '3':
				return 'week';
				break;

			case '6':
				return 'day';

				break;

			default:
				return null;
				break;
		}
	}



	/**
	 * Display the trigger campaing information
	 *
	 * @since  1.0.0
	 * @param  int $id
	 * @param  string $field_name
	 * @return string
	 */
	public function output_trigger_campaign($id, $field_name, $salt = 'ifg'){

		$call_name = preg_replace('/[^ \w]+/', '', crypt($id, $salt));
		?>
		<div class="ifg-warning-msg">
			<p><?php _e('Create an API Goal in your Campaign with the following settings:', 'infugrator');?></p>
			<p><b><?php _e('Integration:', 'infugrator');?></b> <?php echo $this->settings()->get('application/name');?></p>
			<p><b><?php _e('Call Name:', 'infugrator');?></b> <?php echo $call_name;?></p>
		</div>
		<input type="hidden" name="<?php echo $field_name;?>" value="<?php echo $call_name;?>" />
		<?php
	}



	/**
	 * Get a specific property of an array without needing to check if that property exists.
	 *
	 * Provide a default value if you want to return a specific value if the property is not set.
	 *
	 * @since  1.0.0
	 * @param array  $array   Array from which the property's value should be retrieved.
	 * @param string $prop    Name of the property to be retrieved.
	 * @param string $default Optional. Value that should be returned if the property is not set or empty. Defaults to null.
	 *
	 * @return null|string|mixed The value
	 */
	public function rgar( $array, $prop, $default = null ) {

		if ( ! is_array( $array ) && ! ( is_object( $array ) && $array instanceof ArrayAccess ) ) {
			return $default;
		}

		if ( isset( $array[ $prop ] ) ) {
			$value = $array[ $prop ];
		} else {
			$value = '';
		}

		return empty( $value ) && $default !== null ? $default : $value;
	}



	/**
	 * Gets a specific property within a multidimensional array.
	 *
	 * @since  Unknown
	 * @access public
	 *
	 * @param array  $array   The array to search in.
	 * @param string $name    The name of the property to find.
	 * @param string $default Optional. Value that should be returned if the property is not set or empty. Defaults to null.
	 *
	 * @return null|string|mixed The value
	 */
	public function rgars( $array, $name, $default = null ) {

		if ( ! is_array( $array ) && ! ( is_object( $array ) && $array instanceof ArrayAccess ) ) {
			return $default;
		}

		$names = explode( '/', $name );
		$val   = $array;
		foreach ( $names as $current_name ) {
			$val = $this->rgar( $val, $current_name, $default );
		}

		return $val;
	}


}