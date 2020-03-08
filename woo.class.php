<?php 
if ( !defined('ABSPATH') )
	define('ABSPATH', dirname(__FILE__) . '/');

include( 'inc/crypt.func.php');

/**
Class for extend woocommerce functionality
**/

class WooClothingSizes{

	public $plugin_name = 'woo_clothes_sizes';    
	public $user_id;
	public $nonce_name = 'submit_form_billing';
	public $clothing_var = 'clothing-sizes';
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


		if( function_exists('acf_add_options_page') ) {
			
			acf_add_options_page(array(
				'page_title' 	=> 'Woo Clothing Sizes Settings',
				'menu_title'	=> 'Woo Sizes',
				'menu_slug' 	=> 'woo-clothing-sizes',
				'capability'	=> 'edit_posts',
				'redirect'		=> false
			));
			
		}

	/** -----
	Options Setting Panel 
	------**/ 
	add_action('wp_enqueue_scripts',array( $this, 'plugin_scripts'),0);
	add_action('admin_enqueue_scripts',array( $this, 'plugin_scripts'),0);
	add_action('admin_head', array( $this,'fix_svg_thumb_display')); 
	add_action( 'wp_ajax_nopriv_save_sizes', array( $this,'save_fields' ));
	add_action( 'wp_ajax_save_sizes',array( $this, 'save_fields' ));


	add_filter('upload_mimes', array( $this,'cc_mime_types'));
		/**---------------
		Activating a new my sizes tab in the account page
		----------------------**/

		add_action( 'init', array( $this,'my_sizes_item_account_endpoint' ));

		add_filter( 'woocommerce_account_menu_items',  array( $this,'my_sizes_item_account' ));

		add_action( 'woocommerce_account_my-sizes_endpoint', array( $this,'my_sizes_item_account_content' ));

		//add_filter( "woocommerce_get_query_vars",  array($this, 'declare_query_vars_endpoint'),1,1);

		add_filter( 'woocommerce_add_cart_item_data', array($this,'add_size_to_cart_item'), 10, 3 );
		add_filter( 'woocommerce_get_item_data', array($this,'display_size_cart'), 10, 2 );
		add_action( 'woocommerce_checkout_create_order_line_item', array($this,'add_text_to_order_items'), 10, 4 );
		add_action( 'woocommerce_before_add_to_cart_form', array($this,'size_chart_product_page' ));
		add_action( 'woocommerce_before_add_to_cart_button', array($this,'display_fields_on_product_page' ));


	}

	public function display_fields_on_product_page(){
		$cloth_name =  get_field('size_chart_type' ) ;

		$saved_data =  maybe_unserialize( get_user_meta( get_current_user_id(),  $this->plugin_name.'_'.$cloth_name, true)) ;

		if(!$saved_data) {
				//getting default fields 
			$saved_data = $this->get_measument_fields();
		}

		?>
		<input type="hidden" name="cloth_name" value="<?php echo $cloth_name; ?>"  />
		<?php
		foreach ((array) $saved_data as $key=>$value):
			$value = ($value === true) ? '': $value;
			?>
			<input type="hidden" name="<?php echo $key; ?>" value="<?php echo $value; ?>"  />
			<?php	
		endforeach;

	}
	public function display_size_cart( $item_data, $cart_item ) {
		$cloth_name = $_SESSION['woo_cloth_name'];


		if ( empty( $cart_item[$cloth_name] ) ) {
			return $item_data;
		}
		
		$item_data[] = array(
			'key'     => $cloth_name,
			'value'   => wc_clean( $cart_item[$cloth_name] ),
			'display' => '',
		);
		
		return $item_data;
	}
	

	public function add_size_to_cart_item( $cart_item_data, $product_id, $variation_id ) {
		$cloth_name = ucwords(esc_html( $_POST['cloth_name'] ));

		if(empty($cloth_name)) return;

		$data_sizes = []; 

		$_SESSION['woo_cloth_name'] =  $cloth_name ;

		foreach ($_POST as $key => $value) {
			if(empty($value)) continue;
			if(strpos($key, 'size-') !== false ){
				$key = str_replace('size-', '', esc_html($key));
				$data_sizes[] = ucwords(str_replace('-', ' ', $key)).': '. esc_html( $value );
			}

		}

		$cart_item_data[$cloth_name] = implode(', ', $data_sizes);

		return $cart_item_data;
	}

/**
 * Add  text to order.
 *
 * @param WC_Order_Item_Product $item
 * @param string                $cart_item_key
 * @param array                 $values
 * @param WC_Order              $order
 */
public function add_text_to_order_items( $item, $cart_item_key, $values, $order ) {

	$cloth_name = $_SESSION['woo_cloth_name'];

	if ( empty( $values[$cloth_name] ) ) {
		return;
	}

	$item->add_meta_data($cloth_name, wc_clean( $values[$cloth_name] ) );
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
public function get_measument_fields(){

	$clothing_type = get_field('size_chart_type');
	$clothing = get_field('clothing', 'option');
	$arr_fields = [];
	
	if(empty($clothing_type)) return;

	foreach ($clothing as $cloth) :

		$cloth_name = sanitize_title(esc_attr( $cloth['name'] ));

		if(!empty($current_clothing)){

			if($cloth_name != $current_clothing) continue;
		}


		foreach ($cloth['measures'] as $measures) :

			$name_filtered = 'size-'.sanitize_title(esc_html($measures['name'])) ;	
			$arr_fields[$name_filtered] = true;
		
		endforeach;

	endforeach;
	
	return $arr_fields;

}
public function my_sizes_item_account( $items ) {

	$items['my-sizes'] = __( 'My Sizes', 'woo_clothes_sizes' );
	return $items;

}
public function my_sizes_item_account_endpoint() {

	add_rewrite_endpoint( 'my-sizes', EP_PAGES );

}
public function my_sizes_item_account_content($current_clothing) {
	
	$this->user_id = get_current_user_id();
	$form_cls = ($this->user_id == 0) ? 'no-logged' : '';
	$clothing  = get_field('clothing', 'option') ;
	$general_sets  = get_field('clothing_general_sets', 'option') ;

		//echo '<pre>',print_r($clothing);

	?>
	<section class="my-sizes-ray">

		<div class="loading">
			<div class="text">
				<?php _e('Procesando...', 'woo_clothing_sizes'); ?>
			</div>
		</div>
		<?php if(empty($current_clothing)): ?>
			<div class="sizes-menu">
				<ul>
					<?php 
					$active  = 'active';
					foreach ($clothing as $cloth) :?>
						<li class=" <?php echo $active; ?>" data-anchor="#tab-<?php echo sanitize_title(esc_html($cloth['name'])) ?>">
							<div class="wrap-icon">
								<img src="<?php echo $cloth['icon'] ?>" width="30" alt="<?php echo $cloth['name'] ?>">
							</div>
							<div class="wrap-text">
								<?php echo $cloth['name'] ?>
							</div>
						</li>  
						<?php $active  = '';  endforeach; ?>


					</ul>
				</div>
			<?php endif; ?>
			<div class="sizes-tabs">
				<?php 
				$active = 'active' ; 
				foreach ($clothing as $cloth) :

					$cloth_name = sanitize_title(esc_attr( $cloth['name'] ));

					if(!empty($current_clothing)){
						
						if($cloth_name != $current_clothing) continue;
					}

					$saved_data =  maybe_unserialize( get_user_meta( $this->user_id,  $this->plugin_name.'_'.$cloth_name, true)) ;	

					?>
					<aside class="tab <?php echo $active; ?>" id="tab-<?php echo sanitize_title(esc_html($cloth['name'])) ?>">
						<div class="wrap-media">
							<div class="wrap-img">
								<img src="http://placehold.it/600x600" width="100%" alt="" />
							</div>
							<a href="https://www.youtube.com/watch?v=OjQ4GHyh4NA" data-lity class="wrap-video-info">
								<div class="wrap-gif">
									<img src="http://placehold.it/100x100" width="100" height="100" alt="" />
								</div>
								<div class="btn-video">
									<i class="iconsizes_play"></i>
								</div>
								<div class="label"><?php echo $general_sets['btn_video_label'] ?></div>
							</a>
							<div class="wrap-desc"> </div>
						</div>
						<div class="wrap-form">
							<div class="before-form">
								<?php _e('Escribe las medidas en pulgadas. <strong>Ej: 25</strong>'); ?>
							</div>
							<form action="#" class="woo_sizes_form <?php echo $form_cls; ?>" method="POST" data-name="<?php echo $cloth_name;?>">
								<?php  
								
								foreach ($cloth['measures'] as $measures) :
									$name_filtered = 'size-'.sanitize_title(esc_html($measures['name'])) ;								
									?>
									<div class="form-col">
										<div class="form-group">
											<label for="<?php echo $name_filtered ?>"><?php echo $measures['name'] ?></label>						
											<input type="number"   oninput="maxLengthCheck(this)"  id="<?php echo $name_filtered ?>" class="input-meter" name="<?php echo $name_filtered ?>"  data-img="<?php echo $measures['image']['sizes']['medium'] ?>" data-img-gif="<?php echo $measures['video_gif'] ?>"  step="0.01" data-video="<?php echo $measures['video_id'] ?>" data-desc="<?php echo strip_tags($measures['desc']) ?>"   maxlength="5" min="2" max="100" value="<?php echo $saved_data[$name_filtered]; ?>" required />

										</div>								
									</div> 
									<?php $focus = false; endforeach; ?>
									<div class="form-col-full">
										<div class="form-group">
											<input type="hidden" name="cloth_name" value="<?php echo $cloth_name; ?>">
											<input type="hidden" name="action" value="update_sizes" />


											<?php  	if($this->user_id == 0): ?>
												Necesitas <a href="<?php echo get_permalink( wc_get_page_id( 'myaccount' ) ); ?>" id="btn-create-account"><?php _e('crear una cuenta', 'woo_clothing_sizes') ?></a> o <a href="<?php echo get_permalink( wc_get_page_id( 'myaccount' ) ); ?>" id="btn-login"><?php _e('iniciar sesión', 'woo_clothing_sizes') ?></a> para guardar estas medidas permanentemente y reutilizarla en otras compras.
												<?php else: ?>
													<button type="submit" name="save_btn"><?php _e('Guardar medidas', 'woo_clothing_sizes') ?></button>
													<?php echo $general_sets['text_after_save_btn'] ?>
												<?php endif; ?>
												<!-- showing info about form sumitted -->
												<div class="msg-size"></div>


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



	public function save_fields(){


		$this->user_id = get_current_user_id();

		    // Check for nonce security
		$nonce = sanitize_text_field( $_POST['nonce'] );
		$data = wc_clean($_POST['data'] );

		if ( ! wp_verify_nonce( $nonce, 'save_data' ) ) {
			
			$status = [
				'code'=>400,
				'status'=> 'error',
				'body'=> 'Nonce error found!'
			];

			echo json_encode($status);
			wp_die();
		}	


		if( $data['action'] == 'update_sizes'){

			//$address_id = $this->decryptData('SET_DEFAULT-',$_GET['address_id'])[1] ; 
			//$addr = get_user_meta( $this->user_id,  $this->address_option_name)[0][$address_id];

			$cloth_name = esc_attr( $data['cloth_name'] );

 			//echo '<pre>', print_r($sizes); return;
			$data_sizes = [];


				// Filtering only the size
			foreach ($data as $key => $value) {

				if(strpos($key, 'size-') !== false ){
					$data_sizes[esc_attr($key)] = esc_attr( $value ) ;
				}

			}


			delete_user_meta( $this->user_id,  $this->plugin_name.'_'.$cloth_name );	
			add_user_meta( $this->user_id,  $this->plugin_name.'_'.$cloth_name, maybe_serialize( $data_sizes ) );	 

			$status = [
				'code'=>200,
				'status'=> 'success',
				'body'=> __('¡Guardado con éxito!','woo_clothing_sizes')
			];
			echo json_encode($status);



		} 
		wp_die();



	}

	public function size_chart_product_page(){

		$clothing_type = get_field('size_chart_type');

		if(empty($clothing_type)) return;

		return 	$this->my_sizes_item_account_content($clothing_type);
	}
	public function decryptData($delimiter, $data) { 
		return explode($delimiter, decryptIt(wc_clean($data)));
	}


	private function get_multi_address_link(){
		return get_permalink( get_option('woocommerce_myaccount_page_id') ).''.$this->multi_slug;  	
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

		wp_enqueue_style($this->plugin_name.'lity-css',   WOO_CLO_SIZES_CURRENT_URL . 'assets/css/lity.min.css'); 
		wp_enqueue_script( $this->plugin_name.'-lity-js',  WOO_CLO_SIZES_CURRENT_URL . 'assets/js/lity.min.js', '', '', true);


		//wp_enqueue_style ( $this->plugin_name.'-jquery-ui-css');


		$args =  array(
			'ajax_url' => admin_url( 'admin-ajax.php' ),
			'site_url' => get_site_url(),
			'title' => get_bloginfo('name'),
			'ajax_nonce' => wp_create_nonce('save_data'),
		);


		wp_localize_script( $this->plugin_name.'-js', 'wooSizes', $args);
	}




} 