<?php 
if ( !defined('ABSPATH') )
	define('ABSPATH', dirname(__FILE__) . '/');

include( 'inc/crypt.func.php');

/**
Class for extend woocommerce functionality
**/

class WooClothingSizes{

	public $plugin_name = 'woo_clothes_sizes';
	public $max_address_allow = 6; //Only can store 3 address book
	public $address_option_name = 'woo_clothes_sizes_';
	public $address_current_option_name = 'WOO_CLO_SIZES_address_current_';
	public $current_address;
	public $multi_slug = 'multi-address';
	public $user_id;
	public $nonce_name = 'submit_form_billing';

	public function __construct(){
		@session_start();
		$this->run();	 

	}

	public static function init() {
		$class = __CLASS__;
		new $class;
	}
	public function run(){

		date_default_timezone_set('America/Santo_Domingo');

		add_action('wp_enqueue_scripts',array( $this, 'plugin_scripts'),0);
		add_action('admin_enqueue_scripts',array( $this, 'plugin_scripts'),0);
		add_action('admin_head', array( $this,'fix_svg_thumb_display'));
		//add_action( 'init', array( $this,'save_fields_checkout') );

		if( function_exists('acf_add_options_page') ) {
			
			acf_add_options_page(array(
				'page_title' 	=> 'Woo Clothing Sizes Settings',
				'menu_title'	=> 'Woo Sizes',
				'menu_slug' 	=> 'woo-clothing-sizes',
				'capability'	=> 'edit_posts',
				'redirect'		=> false
			));
			
		}
	/**-----
	Options Setting Panel 
	------**/ 
	add_filter('upload_mimes', array( $this,'cc_mime_types'));
		/**---------------
		Activating a new my sizes tab in the account page
		----------------------**/

		add_action( 'init', array( $this,'my_sizes_item_account_endpoint' ));

		add_filter( 'woocommerce_account_menu_items',  array( $this,'my_sizes_item_account' ));

		add_action( 'woocommerce_account_my-sizes_endpoint', array( $this,'my_sizes_item_account_content' ));

		//add_filter( "woocommerce_get_query_vars",  array($this, 'declare_query_vars_endpoint'),1,1);


	}
	public function fix_svg_thumb_display() {
		echo '
		td.media-icon img[src$=".svg"], img[src$=".svg"].attachment-post-thumbnail { 
			width: 100% !important; 
			height: auto !important; 
		}
		';
	}

	public function cc_mime_types($mimes) {
		$mimes['svg'] = 'image/svg+xml';
		return $mimes;
	}

	public function my_sizes_item_account( $items ) {

		$items['my-sizes'] = __( 'My Sizes', 'woo_clothes_sizes' );

		return $items;

	}
	public function my_sizes_item_account_endpoint() {

		add_rewrite_endpoint( 'my-sizes', EP_PAGES );

	}
	public function my_sizes_item_account_content() {
		$clothing  = get_field('clothing', 'option') ;
		//echo '<pre>',print_r($clothing);

		?>
		<section class="my-sizes-ray">
			<div class="sizes-menu">
				<ul>
					<?php foreach ($clothing as $cloth) :?>
						<li data-anchor="tab-<?php echo sanitize_title(esc_html($cloth['name'])) ?>">
							<div class="wrap-icon">
								<img src="<?php echo $cloth['icon'] ?>" width="30" alt="<?php echo $cloth['name'] ?>">
							</div>
							<div class="wrap-text">
								<?php echo $cloth['name'] ?>
							</div>
						</li>  
					<?php endforeach; ?>
					

				</ul>
			</div>
			<div class="sizes-tabs">
			<?php $active = 'active' ; foreach ($clothing as $cloth) : ?>
	<aside class="tab <?php echo $active; ?>" id="tab-<?php echo sanitize_title(esc_html($cloth['name'])) ?>">
					<div class="wrap-media">
						<div class="wrap-img">
							<img src="http://placehold.it/600x600" width="100%" alt="" />
						</div>
						<div class="wrap-video-info">
							<div class="icon-video">
								<img src="http://placehold.it" alt="">
							</div>
							<div class="label"></div>
						</div>
						<div class="wrap-desc">
							Lorem ipsum dolor sit amet, consectetur adipisicing elit. Mollitia corporis eos eligendi! Porro facere ipsa libero aliquid adipisci commodi? Voluptates!
						</div>
					</div>
					<div class="wrap-form">
						<form action="#">
							<?php  foreach ($cloth['measures'] as $measures) :
								$name_filtered = sanitize_title(esc_html($measures['name'])) ;
							 ?>
								<div class="form-col">
									<div class="form-group">
										<label for="<?php echo $name_filtered ?>"><?php echo $measures['name'] ?></label>						
										<input type="number" id="<?php echo $name_filtered ?>" name="<?php echo $name_filtered ?>" placeholder="<?php _e('inch', 'woo_clothing_sizes') ?>" data-img="<?php echo $measures['image']['sizes']['medium'] ?>" data-img-gif="<?php echo $measures['video_gif'] ?>" data-video="<?php echo $measures['video_id'] ?>" data-desc="<?php echo strip_tags($measures['desc']) ?>" required />
									</div>								
								</div> 
							<?php endforeach; ?>
							<div class="form-col-full">
								<div class="form-group">
									<button type="submit" name="save_btn"><?php _e('Guardar medidas', 'woo_clothing_sizes') ?></button>
								</div>								
							</div>
						</form>
					</div>
				</aside>

		<?php $active=''; endforeach; ?>
			</div>
		</section>

		<?php

	}





	public function  declare_query_vars_endpoint($vars) {

		foreach ([$this->multi_slug] as $e) {
			$vars[$e] = $e;
		}

		return $vars;

	}

	// ------------------
	// Registering Account Menu Panel
	public function multiaddress_endpoint_title( $title, $id ) {
		global $wp_query;

		if ( is_wc_endpoint_url($this->multi_slug )   ) {
			$title = __("Mis Direcciones", $this->plugin_name);
		}
		return $title;
	}


	public function multiaddress_pane() {



		add_rewrite_endpoint( $this->multi_slug, EP_ROOT | EP_PAGES );

	}
	
	public function multiaddress_queryvar( $vars ) {
		$vars[] = $this->multi_slug;
		return $vars;
	}
	
	public function multiaddress_add_item( $items ) {
		$items[$this->multi_slug] = __('Direcciones', $this->plugin_name);
		return $items;
	}
	
	public function get_address(){
		return get_user_meta( $this->user_id, $this->address_option_name, true );
	}

	public function multiadress_content() {

		$action = esc_html( $_GET['action'] );
		$address =  $this->get_address()  ;
		$current_address = get_user_meta( $this->user_id,  $this->address_current_option_name )[0];

		//Ordering array put as first element the current address
		if(!is_array($address) and empty($address)){
			$this->save_first_address();
		}  

		if(array_key_exists($current_address, $address)){
			
			$address = array($current_address => $address[$current_address]) + $address;
			
		}

		?>
		<section class="woo-multi-address"> 

			<div class="section-address"> 

				<?php if($action == 'add'): 

					/*wc_add_notice( __( 'Address changed successfully.', 'woocommerce' ) );

					do_action( 'woocommerce_customer_save_address', $user_id, $load_address ); **/
					if(count($address) < $max_address_allow){
						wp_safe_redirect(  $this->get_multi_address_link()  ); 
						die;
					} 

					$this->get_fields_checkout();

				elseif($action == 'edit'):

					$this->get_fields_checkout();

				else:

					
					/** If have address then show them **/
					if($address):


						foreach((array) $address as $key => $curr): 

							$this->generate_address_card($key, maybe_unserialize($curr), $current_address, $this->plugin_name);

						endforeach;


    // Only have a limit of address for creating

						if(count($address) >= $this->max_address_allow) return;

						?>

						<aside class="empty woo-sec-address">
							<a href="<?php echo  $this->get_multi_address_link(); ?>?action=add" class="add trans-3">
								<span class="plus">
									&plus;
								</span>
								<div class="label">
									<?php printf(__('Direccion %s', $this->plugin_name), count($address)+1 ) ?> 
								</div>
							</a>
						</aside> 
						<?php   
					endif;
					?>

				</div>
				<!-- end section address -->

			</section>
			<?php
		endif;
	}

	public function delete_address(){


	}

	public function save_first_address(){

		if($this->user_id == 0) return;

		$meta  = get_user_meta( $this->user_id);
		$billing_arr = [
			'billing_address_name' => __('Predeterminada', $this->plugin_name)
		];

		foreach ($meta as $key => $curmeta) {

			if(strpos($key, 'billing_') !== false){
				$billing_arr[$key] = $curmeta[0];
			}

		}

		delete_user_meta( $this->user_id,  $this->address_current_option_name );
		add_user_meta( $this->user_id,  $this->address_current_option_name, 0 );

		add_user_meta( $this->user_id,  $this->address_option_name, array(maybe_serialize( wc_clean($billing_arr))) );	 
		wp_safe_redirect(  $this->get_multi_address_link()  ); 
	}

	public function save_fields_checkout(){

		$this->user_id = get_current_user_id();



		if($_GET['force_delete'] == 1){
			delete_user_meta( $this->user_id,  $this->address_option_name);
			delete_user_meta( $this->user_id,  $this->address_current_option_name);

		}



		if( $_GET['action'] == 'set_default'){

			$address_id = $this->decryptData('SET_DEFAULT-',$_GET['address_id'])[1] ; 
			$addr = get_user_meta( $this->user_id,  $this->address_option_name)[0][$address_id];

			if($addr and is_numeric($address_id)){

				$addr = maybe_unserialize( $addr );

				foreach ($addr as $meta_key => $meta_value) {

					if(strpos($meta_key, 'billing_') === 0){

						update_user_meta(  $this->user_id, $meta_key, $meta_value );
					}

				}
				delete_user_meta( $this->user_id,  $this->address_current_option_name );	
				add_user_meta( $this->user_id,  $this->address_current_option_name, $address_id );	

				wp_safe_redirect(  wp_get_referer()  ); 
				wc_add_notice(  __('Dirección cambiada exitosamente.', $this->plugin_name), 'success' );	
				die;


			}


		} else 	if( $_GET['action'] == 'delete'){

			$address_id = $this->decryptData('DELETE-',$_GET['address_id'])[1] ; 

			if( is_numeric($address_id)){

				$address = $this->get_address();

				unset($address[$address_id]);

				delete_user_meta( $this->user_id, $this->address_option_name );
				add_user_meta( $this->user_id,  $this->address_option_name, $address);

				wp_safe_redirect(  $this->get_multi_address_link()  ); 
				wc_add_notice(  __('Dirección eliminada exitosamente.', $this->plugin_name), 'success' );	
				die;



			}


		}



		if(!empty($_POST['save_address_field']) and isset($_POST['save_address_field'])){



			if ( !isset( $_POST[$this->nonce_name.'_field'] ) or !wp_verify_nonce( $_POST[$this->nonce_name.'_field'], $this->nonce_name ) ) {
				print 'Nonce disable';
				return;  
			}



			unset($_POST['openinghours_time'],
				$_POST['activecampaign_for_woocommerce_accepts_marketing']);

			if ( preg_match( '/\\d/', $_POST[ 'billing_first_name' ] ) || preg_match( '/\\d/', $_POST[ 'billing_last_name' ] )  ){

				wc_add_notice(  __('Nombres y Apellidos no deben contener números.', $this->plugin_name), 'error' );

			} 

			if ( !preg_match('/^[0-9]*$/', $_POST['billing_company'])   ){

				wc_add_notice(  __('RNC sólo son 9 dígitos y no debe contener números.', $this->plugin_name), 'error' );

			}


			unset($_POST['save_address_field']); 

			$address = $this->get_address();

			if($_POST['action_do'] == 'edit'){

				$address_id = $this->decryptData('EDIT-',$_GET['address_id'])[1];

				$address[$address_id] = maybe_serialize( wc_clean($_POST));

			} else {

				$address[] = maybe_serialize( wc_clean($_POST));

			}

			update_user_meta( $this->user_id,  $this->address_option_name, $address );	 

			wp_safe_redirect(  $this->get_multi_address_link()  ); 
			wc_add_notice(  __('Guardado exitosamente.', $this->plugin_name), 'success' );
			die;
		}


	}

	public function decryptData($delimiter, $data) { 
		return explode($delimiter, decryptIt(wc_clean($data)));
	}
	/** Retrieve checkout form **/
	public function get_fields_checkout() { 

		if ( !is_user_logged_in() ) return;

	if(($user_id = get_current_user_id()) == 0 ) return; //Only works for Users logged.
	//if(!is_checkout()) return;

	$countries = new WC_Checkout(); 
	$address_id =   $this->decryptData('EDIT',$_GET['address_id'])[1] ;

	/**
	Setting address default
	***/

	?>
	<a href="<?php echo $this->get_multi_address_link(); ?>"><?php _e('< Ir a Direcciones', $this->plugin_name); ?></a> <br> <br>
	<form action="" class="form-checkout" method="post" accept-charset="utf-8"> 
		<p class="form-row" id="billing_address_field" >
			<label for="billing_address_name"  ><?php _e('Indica el nombre de esta dirección', $this->plugin_name) ?>&nbsp;<abbr class="required" title="<?php _e('obligatorio', $this->plugin_name) ?>">*</abbr></label>
			<span class="woocommerce-input-wrapper"><input type="text" value="<?php echo $_POST['billing_address_name'] ?>" class="input-text" maxlength="20" required name="billing_address_name" id="billing_address_name"  ></span></p>

			<?php echo $countries->checkout_form_billing() ?>

			<input type="hidden" name="save_address_field" value="save_checkout">

			<?php if($_GET['action'] == 'edit'): ?>

				<input type="hidden" name="action_do" value="edit">
				<input type="hidden" name="field_id" value="<?php echo $address_id ?>">

			<?php endif; ?>

			<?php  wp_nonce_field( $this->nonce_name , $this->nonce_name.'_field'); ?>

			<button type="submit" name="save_address" class="save_address" value="save" data-after-send="<?php _e('Guardando...', $this->plugin_name) ?>"><?php _e('Guardar', $this->plugin_name) ?></button>
		</form>
		<?php 

	}

	private function get_multi_address_link(){
		return get_permalink( get_option('woocommerce_myaccount_page_id') ).''.$this->multi_slug;  	
	}

	public function generate_address_card($current_key, $arr, $current_address, $plugin_name){

	//echo $current_address;
	//var_dump($current_key , $current_address);
		?>
		<aside class="woo-sec-address <?php echo ($current_key == $current_address) ? 'default': ""; ?>">
			<div class="title"><?php echo $arr['billing_address_name'] ?></div>
			<div class="desc">
				<?php echo $arr['billing_first_name'] ?> <?php echo $arr['billing_last_name'] ?><br>
				<?php echo $arr['billing_address_1'] ?>, <?php echo $arr['billing_address_2'] ?>, <?php echo $arr['billing_state'] ?><br>
				Tel:  <?php echo $arr['billing_phone'] ?> <br>
				<?php if(!empty( $arr['billing_company'])): ?>
					RNC: <?php echo $arr['billing_company'] ?>, <?php echo $arr['billing_company_2'] ?> 
				<?php endif; ?> 
			</div>
			<div class="actions">

				<div class="wrap-link-action">
					<?php if($current_key == $current_address): ?>
						<a href="javascript:;" class="trans-3 btn current" data-oso="<?php echo $current_key; ?>"><?php _e('Actual',  $plugin_name) ?></a>
						<?php else: ?>
							<a href="<?php echo  $this->get_multi_address_link(); ?>?action=set_default&address_id=<?php echo encryptIt('SET_DEFAULT-'.$current_key);?>" class="trans-3 btn" data-oso="<?php echo $current_key; ?>"><?php _e('Predeterminar',  $plugin_name) ?></a>
						<?php endif; ?>
					</div>			 
					<a href="<?php echo  $this->get_multi_address_link(); ?>?action=edit&address_id=<?php echo encryptIt('EDIT-'.$current_key);?>" class="edit"><?php _e('Editar',  $plugin_name) ?></a>
					<?php if($current_key != $current_address): ?> &nbsp;|&nbsp;
					<a href="<?php echo  $this->get_multi_address_link(); ?>?action=delete&address_id=<?php echo encryptIt('DELETE-'.$current_key);?>" onClick="return confirm('<?php _e('¿Estás seguro/a de eliminar esta dirección?',  $plugin_name) ?>');" class="delete"><?php _e('Eliminar',  $plugin_name) ?></a>
				<?php endif; ?>

			</div>
		</aside>


		<?php
		$current_key++;
	}

	public function plugin_scripts()
	{

		/** CSS **/

		wp_register_style( $this->plugin_name.'-css', WOO_CLO_SIZES_CURRENT_URL . 'assets/css/style.css');  
		//wp_register_style( $this->plugin_name.'-jquery-ui-css', WOO_CLO_SIZES_CURRENT_URL . 'assets/css/jquery-ui.css');  


		/** JS **/ 

		wp_register_script( $this->plugin_name.'-js', WOO_CLO_SIZES_CURRENT_URL . 'assets/js/main.js', '', '', true);

		wp_enqueue_script(  $this->plugin_name.'-js' );
		wp_enqueue_style ( $this->plugin_name.'-css');
		//wp_enqueue_style ( $this->plugin_name.'-jquery-ui-css');


		$args =  array(
			'ajax_url' => admin_url( 'admin-ajax.php' ),
			'site_url' => get_site_url(),
			'title' => get_bloginfo('name'),
	       	//'ajax_nonce' => wp_create_nonce('hola'),
		);

		if($_GET['action'] == 'edit' and $_GET['address_id'] != ''){
			
			$address_id =  $this->decryptData('EDIT-',$_GET['address_id']);

			$args['form_data'] = maybe_unserialize( $this->get_address()[$address_id[1]]);
			
		}
		
		wp_localize_script( $this->plugin_name.'-js', 'wooMultiData', $args);
	}




} 