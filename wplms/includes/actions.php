<?php
/**
 * Action functions for WPLMS
 *
 * @author      VibeThemes
 * @category    Admin
 * @package     Initialization
 * @version     2.0
 */

if ( ! defined( 'ABSPATH' ) ) exit;


class WPLMS_Actions{

    public static $instance;
    
    public static function init(){

        if ( is_null( self::$instance ) )
            self::$instance = new WPLMS_Actions();

        return self::$instance;
    }

    private function __construct(){
    	
		add_action('init',array($this,'wplms_removeHeadLinks'));

		add_action('wp_head',array($this,'add_loading_css'));
		add_action('wp_head',array($this,'include_child_theme_styling'));

		add_action('template_redirect',array($this,'site_lock'),1);

		add_action( 'wp_ajax_reset_googlewebfonts',array($this,'reset_googlewebfonts' ));
          
		add_action( 'wp_ajax_import_data',array($this,'import_data' ));
		add_action('wplms_be_instructor_button',array($this,'wplms_be_instructor_button'));

		add_action( 'pre_get_posts', array($this,'course_search_results' ));

		add_action(	'template_redirect',array($this,'vibe_check_access_check'));
		add_action( 'template_redirect', array($this,'vibe_check_course_archive' ));
		add_action( 'template_redirect', array($this,'vibe_product_woocommerce_direct_checkout' ));
		add_action('woocommerce_order_item_name',array($this,'vibe_view_woocommerce_order_course_details'),2,100);
		
		add_action('woocommerce_share',array($this,'wplms_social_buttons_on_product'),1000);
		add_action('bp_core_activated_user',array($this,'vibe_redirect_after_registration'),99,3);

		// Course Actions 
		add_action('wplms_course_unit_meta',array($this,'vibe_custom_print_button'));
		add_action('wplms_course_start_after_time',array($this,'wplms_course_progressbar'),1,2);
		add_action('wp_ajax_record_course_progress',array($this,'wplms_course_progress_record'));

		/*=== Profile Layout 3 === */
		add_action('bp_before_member_body',array($this,'member_layout_3_before_item_tabs'));
		add_action('wplms_after_single_item_list_tabs',array($this,'member_layout_3_after_item_tabs'));
		add_action('bp_after_member_body',array($this,'member_layout_3_end_body'));

		add_action('wplms_before_single_group_item_list_tabs',array($this,'group_layout_3_before_item_tabs'));
		add_action('wplms_after_single_group_item_list_tabs',array($this,'group_layout_3_after_item_tabs'));
		add_action('bp_after_group_body',array($this,'group_layout_3_end_body'));

		if(class_exists('WPLMS_tips') && method_exists('WPLMS_tips', 'init')){
			$tips = WPLMS_tips::init(); // Use instead of get_option to avoid unnecessary sql call
			if(!empty($tips->settings) && !empty($tips->settings['woocommerce_account'])){
				/* ==== WooCommerce MY Orders ==== */
				if ( in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) )  || (function_exists('is_plugin_active') && is_plugin_active( 'woocommerce/woocommerce.php'))) {
					add_action( 'bp_setup_nav', array($this,'woo_setup_nav' ));
					add_action( 'bp_init', array($this, 'woo_save_account_details' ) ,999);
					add_action('woocommerce_save_account_details',array($this,'woo_myaccount_page'));
					//Remove WooCommerce wrappers
					remove_action( 'woocommerce_before_main_content', 'woocommerce_output_content_wrapper', 10);
					remove_action( 'woocommerce_after_main_content', 'woocommerce_output_content_wrapper_end', 10);
				}

				if ( in_array( 'paid-memberships-pro/paid-memberships-pro.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) )  || (function_exists('is_plugin_active') && is_plugin_active( 'paid-memberships-pro/paid-memberships-pro.php'))) {
					add_action( 'bp_setup_nav', array($this,'pmpro_setup_nav' ));
				}
			}
		}
 
		add_action( 'course-cat_add_form_fields', array( $this, 'add_category_fields' ));
		add_action( 'course-cat_edit_form_fields', array( $this, 'edit_category_fields' ));
		add_action( 'created_term', array($this,'save_category_meta'), 10, 2 );
		add_action( 'edited_term', array($this,'save_category_meta'), 10, 2 );
		//Transparent Header
		add_action('wp_head',array($this,'transparent_header_title_background'),99);
		
		add_action('wplms_certificate_before_full_content',array($this,'transparent_header_fix'));
		add_action('wplms_before_start_course_content',array($this,'transparent_header_fix'));

		// RESTRICT ACCESS
		add_action('wplms_before_members_directory',array($this,'wplms_before_members_directory'));
		add_action('wplms_before_activity_directory',array($this,'wplms_before_activity_directory'));
		add_action('wplms_before_groups_directory',array($this,'wplms_before_groups_directory'));
		add_action('wplms_before_member_profile',array($this,'wplms_before_member_profile'));

		//Profile settings radio button fix
		add_action('bp_activity_screen_notification_settings',array($this,'wrap_radio'));

		// My Courses search and filter : Also check filter.php function 
		add_action('bp_before_member_course_content',array($this,'mycourses_search'));

		add_action('wplms_before_single_course',array($this,'check_404_in_course'));

		//Related courses
		add_action('wplms_single_course_content_end',array($this,'show_related'));

		//BP Error in settings
		add_action('bp_template_content',array($this,'show_bp_error'),1);

		// Disable Controls on course status
		add_action('course_action_points',array($this,'course_action_points'));

		//Add hidden field for Course category/level/location detection
		add_action('wplms_after_course_directory',array($this,'detect_cat_level_location'));
    }
	

    function add_loading_css(){

    	$page_loader = vibe_get_option('page_loader');
   		if(!empty($page_loader) && !is_customize_preview()){
   			ob_start();
   			if($page_loader == 'pageloader1'){
	    	?>
	    	<style>
	    	body.loading .pusher:before{
				content:'';
				position:fixed;
				left:0;
				top:0;
				width:100%;
				height:100%;
				background:rgba(255,255,255,0.95);
				z-index:999;
			}

			body.loading.pageloader1 .global:before,
			body.loading.pageloader1 .global:after{
				content:'';
				position:fixed;
				left:50%;
				top:50%;
				margin:-20px 0 0 -20px;
				width:40px;
				height:40px;
				border-radius:50%;
				z-index:9999;
				border: 4px solid transparent;
				border-top-color:#009dd8;
			    z-index: 9999;
			    animation: rotate linear 1.5s infinite;
			}
			body.loading.pageloader1 .global:after{
				margin:-27px 0 0 -27px;
				width:54px;
				height:54px;
				border-top-color: transparent;
			    border-left-color: #009dd8;
			    animation: rotate linear 1s infinite;
			}
			
			@keyframes rotate {
			    0% {
			        transform: rotate(0deg);      
			    }
			    50% {
			        transform: rotate(180deg);
			    }
			    100% {
			        transform: rotate(360deg);
			    }
			}
			</style>
	    	<?php
	    	}else{

	    	?>
	    	<style>
	    	body.loading .pusher:before{
				content:'';
				position:fixed;
				left:0;
				top:0;
				width:100%;
				height:100%;
				background:rgba(255,255,255,0.95);
				z-index:999;
			}
	    	body.loading.pageloader2 .global:before,
			body.loading.pageloader2 .global:after{
				content:'';
				position:fixed;
				left:50%;
				top:50%;
				margin:-8px 0 0 -8px;
				width:15px;
				height:15px;
				border-radius:50%;
				z-index:9999;
				background:#009dd8;
			    z-index: 9999;
			    animation: flipzminus linear 1s infinite;
			}
			body.loading.pageloader2 .global:after{
			    animation: flipzplus linear 1s infinite;
			}
			@keyframes flipzminus {
			    0% {
			        transform: translateX(-30px);
			        opacity:1;
			    }
			    50% {
			        transform: translateX(0px);
			        opacity:0.5;
			    }
			    100% {
			        transform: translate(30px);
			        opacity:1;
			    }
			}
			@keyframes flipzplus {
			    0% {
			        transform: translate(30px);
			        opacity:1;
			    }
			    50% {
			        transform: translateX(0px);
			        opacity:0.5;
			    }
			    100% {
			        transform: translateX(-30px);
			        opacity:1;
			    }
			}
	    	</style>
	    	<?php
	    	}
	    	$css = ob_get_clean();
	        $buffer = preg_replace('!/\*[^*]*\*+([^/][^*]*\*+)*/!', '', $css);
	        $buffer = str_replace(': ', ':', $buffer);
	        $buffer = str_replace(array("\r\n", "\r", "\n", "\t", '  ', '    ', '    '), '', $buffer);
	        echo($buffer);
	    }
    }
	function check_404_in_course(){
	 	if(is_404()){
	   		$error404 = vibe_get_option('error404');
	   		if(isset($error404)){
	       		$page_id=  intval($error404);
	       		if(function_exists('icl_object_id')){
			        $page_id = icl_object_id($page_id, 'page', true);
			    }
	       		wp_redirect( get_permalink( $page_id ),301); 
	       		exit;
	   		}
	 	}
	}   

	function course_action_points(){

		$course_status_controls = vibe_get_option('course_status_controls');
		if(empty($course_status_controls)){
			return;
		}
		$action_controls = apply_filters('wplms_course_status_action_controls',array(
			'hide_timeline'=>array(
				'icon'=>'fa fa-exchange',
				'title'=>_x('Hide Timeline','vibe'),
				),
			'fullscreen'=>array(
				'icon'=>'fa fa-expand',
				'title'=>_x('Go fullscreen','vibe'),
				),
			));
		?>
		<div class="course_action_controls">
			<ul>
			<?php
				if(!empty($action_controls)){
					foreach($action_controls as $key => $control){
						?>
						<li class="<?php echo $key; ?>">
							<?php 
								if(!empty($control['html'])){echo $control['html'];}
								else{ ?>
								<a class="<?php echo $control['icon']; ?> action_control"></a>
							<?php } ?>
						</li>
						<?php
					}
				}
			?>
			</ul>
		</div>
		<?php
	}

    function mycourses_search(){
    	if ( bp_is_current_action( BP_COURSE_RESULTS_SLUG ) || bp_is_current_action( BP_COURSE_STATS_SLUG )/* || bp_is_current_action('instructor-courses')*/)
    		return;
    	?>
    	<div class="item-list-tabs" id="subnav" role="navigation">
		<ul>
			<?php do_action( 'bp_course_directory_course_types' ); ?>
			<li>
				<div class="dir-search" role="search">
					<?php bp_directory_course_search_form(); ?>
				</div><!-- #group-dir-search -->
			</li>
			<li class="switch_view">
				<div class="grid_list_wrapper">
					<a id="list_view" class="active"><i class="icon-list-1"></i></a>
					<a id="grid_view"><i class="icon-grid"></i></a>
				</div>
			</li>
			<li id="course-order-select" class="last filter">

				<label for="course-order-by"><?php _e( 'Order By:', 'vibe' ); ?></label>
				<select id="course-order-by">
					<?php
					?>
					<option value=""><?php _e( 'Select Order', 'vibe' ); ?></option>
					<?php
						if(bp_is_current_action('instructor-courses')){
							?>
							<option value="draft"><?php _e( 'Draft courses', 'vibe' ); ?></option>
							<option value="pending"><?php _e( 'Submitted for Approval', 'vibe' ); ?></option>
							<option value="published"><?php _e( 'Published Courses', 'vibe' ); ?></option>
							<?php
						}else{
							?>
							<option value="pursuing"><?php _ex( 'Pursuing courses','Course Status filter in Profile My courses section', 'vibe' ); ?></option>
							<option value="finished"><?php _ex( 'Finished Courses','Course Status filter in Profile My courses section','vibe' ); ?></option>
							<option value="active"><?php _ex( 'Active courses','Course Status filter in Profile My courses section','vibe' ); ?></option>
							<option value="expired"><?php _ex( 'Expired courses','Course Status filter in Profile My courses section','vibe' ); ?></option>
							<?php
						}
					?>
					<option value="newest"><?php _ex( 'Newly Published','filter in Profile My courses section','vibe' ); ?></option>
					<option value="alphabetical"><?php _ex( 'Alphabetical','filter in Profile My courses section', 'vibe' ); ?></option>
					<option value="start_date"><?php _ex( 'Start Date','filter in Profile My courses section', 'vibe' ); ?></option>
					<?php do_action( 'bp_course_directory_order_options' ); ?>
				</select>
			</li>
		</ul>
	</div>
    	<?php
    }
    function wrap_radio(){
    	?>
    	<script>
    		jQuery(document).ready(function($){
    			$('td.yes,td.no').each(function(){
    				var html = $(this).html();
    				$(this).html('<div class="radio">'+html+'</div>');
    			});
    		});
    	</script>
    	<?php
    }
    /*
    CSS BACKGROUND WHICH APPLIES WHEN TRANSPARENT HEADER IS ENABLED
     */
    function transparent_header_title_background(){ 
    	$header_style =  vibe_get_customizer('header_style');

    	if($header_style == 'transparent' || $header_style == 'generic'){ 
	    	if(is_page() || is_single() || (function_exists('bp_is_directory') &&  bp_is_directory()) || (function_exists('bp_current_component') &&  bp_current_component()) || is_archive() || is_search()){ 
	    		global $post;

	    		if(!is_archive() || bp_is_directory()){
	    			$title_bg = get_post_meta($post->ID,'vibe_title_bg',true);	
	    		}
	    		
	    		if(is_numeric($title_bg)){
    				$bg = wp_get_attachment_image_src($title_bg,'full');
    				
    				if(!empty($bg) && !empty($bg[0]))
    					$title_bg = $bg[0];
    			}	

    			if(empty($title_bg) || strlen($title_bg) < 5 ){
	    			$title_bg = vibe_get_option('title_bg');
	    			if(empty($title_bg)){
	    				$title_bg = VIBE_URL.'/assets/images/title_bg.jpg';
	    			}
	    		}

				if(!empty($title_bg)){
	    		?>
	    		<style>.course_header,.group_header{background:url(<?php echo $title_bg; ?>) !important;}#title{background:url(<?php echo $title_bg; ?>) !important;padding-bottom:30px !important; background-size: cover;}
	    		#title.dark h1,#title.dark h5,#title.dark a:not(.button),#title.dark,#title.dark #item-admins h3,#item-header.dark #item-header-content .breadcrumbs li+li:before,#title.dark .breadcrumbs li+li:before,.group_header.dark div#item-header-content,.group_header.dark #item-header-content h3 a,.bbpress.dark .bbp-breadcrumb .bbp-breadcrumb-sep:after,#item-header.dark #item-admins h3,#item-header.dark #item-admins h5,#item-header.dark #item-admins h3 a,#item-header.dark #item-admins h5 a,
	    		#item-header.dark #item-header-content a,#item-header.dark #item-header-content{color:#222 !important;}
	    		#title.light h1,#title.light h5,#title.light a:not(.button),#title.light,#title.light #item-admins h3,#item-header.light #item-header-content .breadcrumbs li+li:before,#item-header.light #item-admins h3,#item-header.light #item-admins h5,#item-header.light #item-admins h3 a,#item-header.light #item-admins h5 a,#title.light .breadcrumbs li+li:before,.group_header.light div#item-header-content,.group_header.light #item-header-content h3 a,.bbpress.light .bbp-breadcrumb .bbp-breadcrumb-sep:after,#item-header.light #item-header-content a,#item-header.light #item-header-content{color:#fff !important;}.bp-user div#global .pusher .member_header div#item-header{background:url(<?php echo $title_bg; ?>);}.group_header #item-header{background-color:transparent !important;}</style>
	    		<?php
	    		}
	    	}
    	}

		remove_action( 'woocommerce_before_main_content', 'woocommerce_output_content_wrapper', 10);
		remove_action( 'woocommerce_after_main_content', 'woocommerce_output_content_wrapper_end', 10);
    }

    function transparent_header_fix(){
    	$header_style =  vibe_get_customizer('header_style');

    	if($header_style == 'transparent' || $header_style == 'generic'){ 
    		?>
    		<section id="title"></section>
    		<?php
    	}
    }
    function include_child_theme_styling(){
    	if (get_template_directory() !== get_stylesheet_directory()) {
	      	wp_enqueue_style('wplms_child_theme_style',get_stylesheet_uri(),'wplms-style');
	    }
    }


    function site_lock(){
    	$site_lock = vibe_get_option('site_lock');
    	$register_page_id = vibe_get_directory_page('register');
    	$activate_page_id = vibe_get_directory_page('activate');

    	$exlusions = apply_filters('wplms_site_lock_exclusions',array($register_page_id,$activate_page_id));
    	$bypass = apply_filters('wplms_bp_page_site_lock_bypass',1);
    	global $post;
    	if(!empty($site_lock) && !is_user_logged_in() && !is_front_page() && !in_Array($post->ID,$exlusions) && (bp_current_component()!='activate') && $bypass){
    		wp_redirect( home_url() );
        	exit();
    	}
    }
    
	function wplms_removeHeadLinks(){
	  $xmlrpc = vibe_get_option('xmlrpc');
	  if(isset($xmlrpc) && $xmlrpc){
	    remove_action('wp_head', 'rsd_link');
	    remove_action('wp_head', 'wlwmanifest_link'); 
	    add_filter('xmlrpc_enabled','__return_false');
	  }
	}

	function reset_googlewebfonts(){ 
      	echo "reselecting..";
      	$r = get_option('google_webfonts');
      	if(isset($r)){
          	delete_option('google_webfonts');
      	}
	  	die();
	}

	function import_data(){
		if(!current_user_can('manage_options'))
  			die();

		$name = stripslashes($_POST['name']);
		$code = base64_decode(trim($_POST['code'])); 
		if(is_string($code))
    		$code = unserialize ($code);
		
		$value = get_option($name);
		if(isset($value)){
      		update_option($name,$code);
		}else{
			echo "Error, Option does not exist !";
		}
		die();
	}


	function wplms_be_instructor_button(){
		$teacher_form = vibe_get_option('teacher_form');

		if(isset($teacher_form) && is_numeric($teacher_form)){
			echo '<a href="'.(isset($teacher_form)?get_permalink($teacher_form):'#').'" class="button create-group-button full">'. __( 'Become an Instructor', 'vibe' ).'</a>';  
		}
	}

	function course_search_results($query){

	  if(!$query->is_search() && !$query->is_main_query())
	    return $query;

	  if(isset($_GET['course-cat']))
	      $course_cat = $_GET['course-cat'];

	  if(isset($_GET['instructor']))
	      $instructor = $_GET['instructor'];  

	  if ( function_exists('get_coauthors')) {
	    if(isset($instructor) && $instructor !='*' && $instructor !='' && is_numeric($instructor)){
	      $instructor_name = strtolower(get_the_author_meta('user_login',$instructor)); 
	      //$query->set('author_name', $instructor_name);
	      $query->query['author_name']=$instructor_name;
	    }
	  }else{
	    if(isset($instructor) && $instructor !='*' && $instructor !=''){
	      $query->set('author', $instructor);
	    }
	  }

	  if(isset($course_cat) && $course_cat !='*' && $course_cat !=''){
	    $query->set('course-cat', $course_cat);
	  }
	  return $query;
	}


	function vibe_check_access_check(){ 

	    if(!is_singular(array('unit','question')))
	      return;

	    $flag=0;
	    global $post;

		$free=get_post_meta(get_the_ID(),'vibe_free',true);
   		if(vibe_validate($free) || (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && is_user_logged_in())){
	        	$flag=0;
	        	return;
	    }else
	    	$flag = 1;

	    if(current_user_can('edit_posts')){
	        $flag=0;
	        $instructor_privacy = vibe_get_option('instructor_content_privacy');
	        $user_id=get_current_user_id();
	        if(isset($instructor_privacy) && $instructor_privacy && !current_user_can('manage_options')){
	            if($user_id != $post->post_author)
	              $flag=1;
	        }
	    }

	    if($post->post_type == 'unit'){
	      	$post_type = __('UNITS','vibe');
	    }else if($post->post_type == 'question'){
	      	$post_type = __('QUESTIONS','vibe');
	    }

	    $message = sprintf(__('DIRECT ACCESS TO %s IS NOT ALLOWED','vibe'),$post_type);
	    $flag = apply_filters('wplms_direct_access_to'.$post->post_type,$flag,$post);
	    if($flag){
	        wp_die($message,$message,array('back_link'=>true));
	    }
	}

	
	function vibe_check_course_archive(){

	    if(is_post_type_archive('course') && !is_search()){
	        $pages=get_site_option('bp-pages');
	        if(is_array($pages) && isset($pages['course'])){
	          $all_courses = get_permalink($pages['course']);
	          wp_redirect($all_courses);
	          exit();
	        }
	    }
	}

	// Course functions
	function vibe_custom_print_button(){
		$print_html='<a href="#" class="print_unit"><i class="icon-printer-1"></i></a>';
		echo apply_filters('wplms_unit_print_button',$print_html);  
	}


	function wplms_course_progressbar($course_id,$unit_id){
	    $user_id=get_current_user_id();

	    
	    $percentage = bp_course_get_user_progress($user_id,$course_id);

	    $units = array();
	    if(function_exists('bp_course_get_curriculum_units'))
	    	$units = bp_course_get_curriculum_units($course_id);

	    $total_units = count($units);
	    if(empty($total_units))
	    	$total_units = 1;
	   	if(empty($percentage)){
   			$percentage = 0;
	  	}
	    
	    if($percentage > 100)
	      $percentage= 100;

	    $unit_increase = round(((1/$total_units)*100),2);

	    echo '<div class="progress course_progressbar" data-increase-unit="'.$unit_increase.'" data-value="'.$percentage.'">
	             <div class="bar animate cssanim stretchRight load" style="width: '.$percentage.'%;"><span>'.$percentage.'%</span></div>
	           </div>';

	}


	function wplms_course_progress_record(){
	    $course_id = $_POST['course_id'];
	    if ( !isset($_POST['security']) || !wp_verify_nonce($_POST['security'],'security') || !is_numeric($course_id) ){
	       _e('Security check Failed. Contact Administrator.','vibe');
	       die();
	    }
	    $course_progress = $_POST['progress'];
	    $user_id = get_current_user_id();
	    $progress='progress'.$course_id;
	    update_user_meta($user_id,$progress,$course_progress);
	    die();
	}
	// END course Functions		
	function vibe_product_woocommerce_direct_checkout(){

	  	if(in_array('woocommerce/woocommerce.php', apply_filters('active_plugins', get_option('active_plugins'))) || (function_exists('is_plugin_active') && is_plugin_active( 'woocommerce/woocommerce.php'))){
	        $check=vibe_get_option('direct_checkout');
	        $check =intval($check);
	    	if(isset($check) &&  $check == 2){
	      		if( is_single() && get_post_type() == 'product' && isset($_GET['redirect'])){
	          		global $woocommerce;
	          		$found = false;
	          		$product_id = get_the_ID();
	          		$courses = vibe_sanitize(get_post_meta(get_the_ID(),'vibe_courses',false));
	          		if(isset($courses) && is_array($courses) && count($courses)){
	            		if ( sizeof( WC()->cart->get_cart() ) > 0 ) {
	              			foreach ( WC()->cart->get_cart() as $cart_item_key => $values ) {
	                			$_product = $values['data'];
	                			if ( $_product->id == $product_id )
	                  				$found = true;
	              			}
	              			// if product not found, add it
	              			if ( ! $found )
	                			WC()->cart->add_to_cart( $product_id );
	                		$checkout_url = $woocommerce->cart->get_checkout_url();
	                		wp_redirect( $checkout_url);  
        				}else{
	              			// if no products in cart, add it
	              			WC()->cart->add_to_cart( $product_id );
	              			$checkout_url = $woocommerce->cart->get_checkout_url();
	              			wp_redirect( $checkout_url);  
	            		}
	            		exit();
	          		}
	      		}
	    	}
	    	if(isset($check) &&  $check == 3){ 
	      		if( is_single() && get_post_type() == 'product' && isset($_GET['redirect'])){ 
	          		global $woocommerce; 
	          		$found = false;
	          		$product_id = get_the_ID();
	          		$courses = vibe_sanitize(get_post_meta(get_the_ID(),'vibe_courses',false));
	          
	          		if(isset($courses) && is_array($courses) && count($courses)){
	            		if ( sizeof( WC()->cart->get_cart() ) > 0 ) {
	              			foreach ( WC()->cart->get_cart() as $cart_item_key => $values ) {
	                		$_product = $values['data'];
	                			if ( $_product->id == $product_id )
	                  				$found = true;
	              			}
	              			// if product not found, add it
	              			if ( ! $found )
	                			WC()->cart->add_to_cart( $product_id );
	                		$cart_url = $woocommerce->cart->get_cart_url(); 
	                		wp_redirect( $cart_url); 
	            		}else{
			              	WC()->cart->add_to_cart( $product_id );
			              	$cart_url = $woocommerce->cart->get_cart_url(); 
			              	wp_redirect( $cart_url);
	            		}
	            		exit();
	          		}
	      		}
	    	}
	  	} // End if WooCommerce Installed
	}

	function vibe_view_woocommerce_order_course_details($html, $item ){
		 
	  	$product_id=$item['item_meta']['_product_id'][0];
	  	if(isset($product_id) && is_numeric($product_id)){
	      	$courses = get_post_meta($product_id,'vibe_courses',true);
	      	if(!empty($courses) && is_Array($courses)){
		        $html .= ' [ <i>'.__('COURSE : ','vibe');
	        	foreach($courses as $course){ 
	          		if(is_numeric($course)){ 
	           			$html .= '<a href="'.get_permalink($course).'"><strong><i>'.get_post_field('post_title',$course).'</i></strong></a> ';
	          		}
	        	}
	        	$html .=' </i> ]';
	      	}
	  	}
	  	return $html;

	}
	
	function wplms_social_buttons_on_product(){
	    echo do_shortcode('[social_buttons]');
	}


	function vibe_redirect_after_registration($user_id, $key, $user){
		
		$bp = buddypress();
		
		$bp->activation_complete = true;

		if(current_user_can('manage_options'))
			return;

		//do not redirect if doing ajax - @Buddydev - Brajesh Singh.
		if ( defined('DOING_AJAX') ) {
			return ;
		}

	    if ( is_multisite() )
	      $hashed_key = wp_hash( $key );
	    else
	      $hashed_key = wp_hash( $user_id );

	    if ( file_exists( BP_AVATAR_UPLOAD_PATH . '/avatars/signups/' . $hashed_key ) )
	      @rename( BP_AVATAR_UPLOAD_PATH . '/avatars/signups/' . $hashed_key, BP_AVATAR_UPLOAD_PATH . '/avatars/' . $user_id );

	     
	    
	    $pageid=vibe_get_option('activation_redirect');
	    if(empty($pageid)){
	   	  wp_set_auth_cookie( $user_id, true, false );
	      bp_core_add_message( __( 'Your account is now active!', 'vibe' ) );
	      bp_core_redirect( apply_filters ( 'wplms_registeration_redirect_url', bp_core_get_user_domain( $user_id ), $user_id ) );      
	    }else{
	    	wp_set_auth_cookie( $user_id, true, false );	
	    	if($pageid == 'dashboard'){
	    		if(defined('WPLMS_DASHBOARD_SLUG'))
	    			$link = bp_core_get_user_domain($user_id).WPLMS_DASHBOARD_SLUG;
	    	}else if($pageid == 'profile'){
	    		if(function_exists('bp_loggedin_user_domain'))
	    			$link = bp_core_get_user_domain($user_id);
	    	}else if($pageid == 'mycourses'){
	    		if(defined('BP_COURSE_SLUG'))
	    			$link = trailingslashit( bp_core_get_user_domain($user_id). BP_COURSE_SLUG );
	    	}else{
	    		$link = get_permalink($pageid);
	    	}
	      	bp_core_redirect( apply_filters ( 'wplms_registeration_redirect_url',$link, $user_id ) );      
	    }
	}

	/*=== Layout 3 ===*/
	function member_layout_3_before_item_tabs(){
		$layout = vibe_get_customizer('profile_layout');
		if($layout != 'p3')
			return;
		?>
			<div class="row">
				<div class="col-md-3">
		<?php
	}

	function member_layout_3_after_item_tabs(){
		$layout = vibe_get_customizer('profile_layout');
		if($layout != 'p3')
			return;
		?>
			</div>
			<div class="col-md-9">
		<?php
	}

	function member_layout_3_end_body(){
		$layout = vibe_get_customizer('profile_layout');
		if($layout != 'p3')
			return;
		?>
			</div>
		</div>
		<?php
	}

	function group_layout_3_before_item_tabs(){
		$layout = vibe_get_customizer('group_layout');
		if($layout != 'g3')
			return;
		?>
			<div class="row">
				<div class="col-md-3">
		<?php
	}
	function group_layout_3_after_item_tabs(){
		$layout = vibe_get_customizer('group_layout');
		if($layout != 'g3')
			return;
		?>
			</div>
			<div class="col-md-9">
		<?php
	}

	function group_layout_3_end_body(){
		$layout = vibe_get_customizer('profile_layout');
		if($layout != 'p3')
			return;
		?>
			</div>
		</div>
		<?php
	}


	function woo_setup_nav(){
		global $bp;
		$myaccount_pid = get_option('woocommerce_myaccount_page_id');

		if(is_numeric($myaccount_pid)){
			$slug = get_post_field('post_name',$myaccount_pid);
			bp_core_new_nav_item( array( 
	            'name' => __('My Orders', 'vibe' ), 
	            'slug' => $slug , 
	            'position' => 99,
	            'screen_function' => array($this,'woo_myaccount'), 
	            'default_subnav_slug' => '',
	            'show_for_displayed_user' => bp_is_my_profile(),
	            'default_subnav_slug'=> $slug
	      	) );


			$link = trailingslashit( bp_loggedin_user_domain() . $slug );

			bp_core_new_subnav_item( array(
				'name'            => __('My Orders', 'vibe' ), 
				'slug'            => $slug,
				'parent_slug'     => $slug,
				'parent_url'      => $link,
				'position'        => 10,
				'item_css_id'     => 'nav-' . $slug,
				'screen_function' => array( $this, 'woo_myaccount' ),
				'user_has_access' => bp_is_my_profile(),
				'no_access_url'   => home_url(),
			) );
			
			$endpoints = array(
				'edit-account' => get_option( 'woocommerce_myaccount_edit_account_endpoint', 'edit-account' ),
			);

			$i=20;
			foreach($endpoints as $key => $endpoint){
				switch ( $endpoint ) {
					case 'edit-account' :
						$title = __( 'Edit Account Details', 'vibe' );
					break;
					default :
						$title = __( 'My Orders', 'vibe' );
					break;
				}
				$function = str_replace('-','_',$key);
				
				bp_core_new_subnav_item( array(
					'name'            => $title,
					'slug'            => $key,
					'parent_slug'     => $slug,
					'parent_url'      => $link,
					'position'        => $i,
					'item_css_id'     => 'nav-' . $key,
					'screen_function' => array( $this, $function ),
					'user_has_access' => bp_is_my_profile(),
					'no_access_url'   => home_url(),
				) );
				$i = $i+10;
			}
		}
	}
	function woo_myaccount() {

		if(!is_user_logged_in() || !function_exists('bp_is_my_profile') || !bp_is_my_profile())
			wp_redirect(home_url());

		$this->myaccount_pid = get_option('woocommerce_myaccount_page_id');
		add_action('bp_template_title',array($this,'woo_myaccount_title'));
		add_action('bp_template_content',array($this,'woo_myaccount_content'));
		bp_core_load_template( apply_filters( 'bp_core_template_plugin', 'members/single/plugins' ) );		
		exit;
	}
	
	function edit_account(){
		if(!is_user_logged_in() || !function_exists('bp_is_my_profile') || !bp_is_my_profile())
			wp_redirect(home_url());

		add_query_arg($bp->current_action);
		
		if(empty($this->myaccount_pid))
			$this->myaccount_pid = get_option('woocommerce_myaccount_page_id');


		add_action('bp_template_title',array($this,'woo_myaccount_edit_title'));
		add_action('bp_template_content',array($this,'woo_myaccount_edit_content'));
		bp_core_load_template( apply_filters( 'bp_core_template_plugin', 'members/single/plugins' ) );
		exit;
	}

	function woo_myaccount_title(){
		echo '<h2>'.get_the_title($this->myaccount_pid).'</h2>';
	}

	function woo_myaccount_edit_title(){
		echo '<h2>'.__( 'Edit Account Details', 'vibe' ).'</h2>';
	}

	function woo_myaccount_content(){
		echo apply_filters('the_content',get_post_field('post_content',$this->myaccount_pid));
	}

	function woo_myaccount_edit_content(){
		ob_start();
		wc_get_template( 'myaccount/form-edit-account.php', array( 'user' => get_user_by( 'id', get_current_user_id() ) ) );
		$content = ob_get_clean();
		echo apply_filters('the_content',$content);
	}
	function woo_save_account_details(){
		if(isset($_POST)){
			WC_Form_Handler::save_account_details();
		}
	}

	function woo_myaccount_page(){
		$myaccount_pid = get_option('woocommerce_myaccount_page_id');
		if(is_numeric($myaccount_pid)){
			$slug = get_post_field('post_name',$myaccount_pid);
			$link = trailingslashit( bp_loggedin_user_domain() . $slug );
			wp_redirect($link);
			exit();
		}
	}

	/* === PMPRO ===== */
	function pmpro_setup_nav(){
		global $bp;
		if(empty($this->pmpro_account_pid))
			$this->pmpro_account_pid = get_option('pmpro_account_page_id');

		if(is_numeric($this->pmpro_account_pid)){
			$slug = get_post_field('post_name',$this->pmpro_account_pid);
			bp_core_new_nav_item( array( 
	            'name' => __('My Memberships', 'vibe' ), 
	            'slug' => $slug , 
	            'position' => 99,
	            'screen_function' => array($this,'pmpro_myaccount'), 
	            'default_subnav_slug' => '',
	            'show_for_displayed_user' => bp_is_my_profile(),
	            'default_subnav_slug'=> $slug
	      	) );


			$link = trailingslashit( bp_loggedin_user_domain() . $slug );

			bp_core_new_subnav_item( array(
				'name'            => __('My Memberships', 'vibe' ), 
				'slug'            => $slug,
				'parent_slug'     => $slug,
				'parent_url'      => $link,
				'position'        => 10,
				'item_css_id'     => 'nav-' . $slug,
				'screen_function' => array( $this, 'pmpro_myaccount' ),
				'user_has_access' => bp_is_my_profile(),
				'no_access_url'   => home_url(),
			) );
		}
	}
	function pmpro_myaccount() {

		if(!is_user_logged_in() || !function_exists('bp_is_my_profile') || !bp_is_my_profile())
			wp_redirect(home_url());
		
		if(empty($this->pmpro_account_pid))
			$this->pmpro_account_pid = get_option('pmpro_account_page_id');

		add_action('bp_template_title',array($this,'pmpro_myaccount_title'));
		add_action('bp_template_content',array($this,'pmpro_myaccount_content'));
		bp_core_load_template( apply_filters( 'bp_core_template_plugin', 'members/single/plugins' ) );		
		exit;
	}

	function pmpro_myaccount_title(){
		echo '<h2>'.get_the_title($this->pmpro_account_pid).'</h2>';
	}

	function pmpro_myaccount_content(){
		echo apply_filters('the_content',get_post_field('post_content',$this->pmpro_account_pid));
	}



    /*
    *	Add Course Category Featured thubmanils
    *	Use WP 4.4 Term meta for storing information
    * 	@reference : WooCommerce (GPLv2)
    */
    function add_category_fields(){
    	
    	$default = vibe_get_option('default_avatar');

    	?>
    	<div class="form-field">
    	<label><?php _e( 'Display Order', 'vibe' ); ?></label>
    	<input type="number" name="course_cat_order" id="course_cat_order" value="" />
    	</div>
    	<div class="form-field">
			<label><?php _e( 'Thumbnail', 'vibe' ); ?></label>
			<div id="course_cat_thumbnail" style="float: left; margin-right: 10px;"><img src="<?php echo esc_url( $default ); ?>" width="60px" height="60px" /></div>
			<div style="line-height: 60px;">
				<input type="hidden" id="course_cat_thumbnail_id" name="course_cat_thumbnail_id" />
				<button type="button" class="upload_image_button button"><?php _e( 'Upload/Add image', 'vibe' ); ?></button>
				<button type="button" class="remove_image_button button"><?php _e( 'Remove image', 'vibe' ); ?></button>
			</div>
			<script type="text/javascript">
				if ( ! jQuery( '#course_cat_thumbnail_id' ).val() ) {
					jQuery( '.remove_image_button' ).hide();
				}
				// Uploading files
				var file_frame;

				jQuery( document ).on( 'click', '.upload_image_button', function( event ) {
					event.preventDefault();
					// If the media frame already exists, reopen it.
					if ( file_frame ) {
						file_frame.open();
						return;
					}

					// Create the media frame.
					file_frame = wp.media.frames.downloadable_file = wp.media({
						title: '<?php _e( "Choose an image", "vibe" ); ?>',
						button: {
							text: '<?php _e( "Use image", "vibe" ); ?>'
						},
						multiple: false
					});
					file_frame.on( 'select', function() {
						var attachment = file_frame.state().get( 'selection' ).first().toJSON();
						jQuery( '#course_cat_thumbnail_id' ).val( attachment.id );
						if( attachment.sizes){
						    if(   attachment.sizes.thumbnail !== undefined  ) url_image=attachment.sizes.thumbnail.url; 
						    else if( attachment.sizes.medium !== undefined ) url_image=attachment.sizes.medium.url;
						    else url_image=attachment.sizes.full.url;
						}

						jQuery( '#course_cat_thumbnail' ).find( 'img' ).attr( 'src', url_image );
						
						jQuery( '.remove_image_button' ).show();
					});
					file_frame.open();
				});

				jQuery( document ).on( 'click', '.remove_image_button', function() {
					jQuery( '#course_cat_thumbnail' ).find( 'img' ).attr( 'src', '<?php echo esc_js( $default ); ?>' );
					jQuery( '#course_cat_thumbnail_id' ).val( '' );
					jQuery( '.remove_image_button' ).hide();
					return false;
				});

			</script>
			<div class="clear"></div>
		</div>
		<?php
    }
    /*
    *	Edit Course Category Featured thubmanils
    *	Use WP 4.4 Term meta for storing information
    * 	@reference : WooCommerce (GPLv2)
    */
    function edit_category_fields($term){


    	$thumbnail_id = absint( get_term_meta( $term->term_id, 'course_cat_thumbnail_id', true ) );
    	$order = get_term_meta( $term->term_id, 'course_cat_order', true ); 
		if ( $thumbnail_id ) {
			$image = wp_get_attachment_thumb_url( $thumbnail_id );
		} else {
			$default = vibe_get_option('default_avatar');
			$image = $default;
		}

    	?>
    	<tr class="form-field">
    		<th scope="row" valign="top"><label><?php _e( 'Display Order', 'vibe' ); ?></label></th>
			<td><input type="number" name="course_cat_order" id="course_cat_order" value="<?php echo (empty($order)?0:$order); ?>" /></td>
    	</tr>
    	<tr class="form-field">
			<th scope="row" valign="top"><label><?php _e( 'Thumbnail', 'vibe' ); ?></label></th>
			<td>
				<div id="course_cat_thumbnail" style="float: left; margin-right: 10px;"><img src="<?php echo esc_url( $image ); ?>" width="60px" height="60px" /></div>
				<div style="line-height: 60px;">
					<input type="hidden" id="course_cat_thumbnail_id" name="course_cat_thumbnail_id" value="<?php echo $thumbnail_id; ?>" />
					<button type="button" class="upload_image_button button"><?php _e( 'Upload/Add image', 'vibe' ); ?></button>
					<button type="button" class="remove_image_button button"><?php _e( 'Remove image', 'vibe' ); ?></button>
				</div>
				<script type="text/javascript">

					// Only show the "remove image" button when needed
					if ( '0' === jQuery( '#course_cat_thumbnail_id' ).val() ) {
						jQuery( '.remove_image_button' ).hide();
					}

					// Uploading files
					var file_frame;

					jQuery( document ).on( 'click', '.upload_image_button', function( event ) {

						event.preventDefault();

						// If the media frame already exists, reopen it.
						if ( file_frame ) {
							file_frame.open();
							return;
						}

						// Create the media frame.
						file_frame = wp.media.frames.downloadable_file = wp.media({
							title: '<?php _e( "Choose an image", "vibe" ); ?>',
							button: {
								text: '<?php _e( "Use image", "vibe" ); ?>'
							},
							multiple: false
						});

						// When an image is selected, run a callback.
						file_frame.on( 'select', function() {
							var attachment = file_frame.state().get( 'selection' ).first().toJSON();

							jQuery( '#course_cat_thumbnail_id' ).val( attachment.id );

							if( attachment.sizes){
							    if(   attachment.sizes.thumbnail !== undefined  ) url_image=attachment.sizes.thumbnail.url; 
							    else if( attachment.sizes.medium !== undefined ) url_image=attachment.sizes.medium.url;
							    else url_image=attachment.sizes.full.url;
							}

							jQuery( '#course_cat_thumbnail' ).find( 'img' ).attr( 'src', url_image );
							jQuery( '.remove_image_button' ).show();
						});

						// Finally, open the modal.
						file_frame.open();
					});

					jQuery( document ).on( 'click', '.remove_image_button', function() {
						jQuery( '#course_cat_thumbnail' ).find( 'img' ).attr( 'src', '<?php echo esc_js( $image ); ?>' );
						jQuery( '#course_cat_thumbnail_id' ).val( '' );
						jQuery( '.remove_image_button' ).hide();
						return false;
					});

				</script>
				<div class="clear"></div>
			</td>
		</tr>
		<?php
    }


	function save_category_meta( $term_id, $tt_id ){
		global $wpdb;
	    if( isset( $_POST['course_cat_thumbnail_id'] )){
	        $thumb_id = intval( $_POST['course_cat_thumbnail_id'] );
	        update_term_meta( $term_id, 'course_cat_thumbnail_id', $thumb_id );
	    }
	    if( isset( $_POST['course_cat_order'] ) &&is_numeric($_POST['course_cat_order'])){
	        update_term_meta( $term_id, 'course_cat_order', $_POST['course_cat_order'] );
	        $wpdb->update($wpdb->terms, array('term_group' => $_POST['course_cat_order']), array('term_id'=>$term_id));
	    }
	}

	/*
	RESTRICTI DIRECTORY & PROFILE ACCESS
	*/

	function wplms_before_members_directory(){

	  $flag=1;
	  $members_view=vibe_get_option('members_view');

	  if(isset($members_view) && $members_view){
	    $flag=0;
	    switch($members_view){
	      case 1:
	        if(is_user_logged_in())$flag=1;
	      break;
	      case 2:
	        if(current_user_can('edit_posts'))$flag=1;
	      break;
	      case 3:
	        if(current_user_can('manage_options'))$flag=1;
	      break;
	    }
	  }

	  if(!$flag){
	    $id=vibe_get_option('members_redirect');
	    if(isset($id))
	      wp_redirect(get_permalink($id));
	  	else
	  		wp_redirect(home_url());
	    exit();
	  }
	}

	function wplms_before_activity_directory(){
		$flag=1;
		$activity_view=vibe_get_option('activity_view');

	  	if(isset($activity_view) && $activity_view){
		    $flag=0;
		    switch($activity_view){
		      case 1:
		        if(is_user_logged_in())$flag=1;
		      break;
		      case 2:
		        if(current_user_can('edit_posts'))$flag=1;
		      break;
		      case 3:
		        if(current_user_can('manage_options'))$flag=1;
		      break;
		    }
	  	}

	  	if(!$flag){
		    $id=vibe_get_option('activity_redirect');
		    if(isset($id)){
		      wp_redirect(get_permalink($id));
		    }else{
		    	wp_redirect(home_url());
		    }
		    exit();
	  	}
	}

	function wplms_before_groups_directory(){
		$flag=1;
		$group_view=vibe_get_option('group_view');

	  	if(isset($group_view) && $group_view){
		    $flag=0;
		    switch($group_view){
		      case 1:
		        if(is_user_logged_in())$flag=1;
		      break;
		      case 2:
		        if(current_user_can('edit_posts'))$flag=1;
		      break;
		      case 3:
		        if(current_user_can('manage_options'))$flag=1;
		      break;
		    }
	  	}

	  	if(!$flag){
		    $id=vibe_get_option('group_redirect');
		    if(isset($id)){
		      wp_redirect(get_permalink($id));
		    }else{
		    	wp_redirect(home_url());
		    }
		    exit();
	  	}
	}

	function wplms_before_member_profile(){

	  $flag=1;
	  $members_view=vibe_get_option('single_member_view');

	  if(isset($members_view) && $members_view){
	    $flag=0;
	    switch($members_view){
	      case 1:
	        if(is_user_logged_in())$flag=1;
	      break;
	      case 2:
	        if(current_user_can('edit_posts'))$flag=1;
	      break;
	      case 3:
	        if(current_user_can('manage_options'))$flag=1;
	      break;
	    }
	  }

	  if(!$flag && !bp_is_my_profile()){
	    $id=vibe_get_option('members_redirect');
	    if(isset($id))
	      wp_redirect(get_permalink($id));
	    exit();
	  }
	}

	/*
	Related Courses
	 */
	function show_related(){
		
		$related_courses = vibe_get_option('related_courses');
		if(empty($related_courses))
			return;
		$style = vibe_get_option('default_course_block_style');
		$terms = wp_get_post_terms(get_the_ID(),'course-cat');
		$categories = array();
		if(!empty($terms)){
			foreach($terms as $term)
			$categories[] = $term->term_id;
		}
		$args = apply_filters('wplms_modern_related_courses',array(
			'post_type' => 'course',
			'posts_per_page'=>3,
			'post__not_in'=>array(get_the_ID()),
			'tax_query' => array(
					'relation' => 'OR',
					array(
						'taxonomy' => 'course-cat',
						'field'    => 'id',
						'terms'    => $categories,
					),
			),
			));
		$courses = new WP_Query($args);
		
		if($courses->have_posts()):
		?>
		<div class="related_courses">
		<h3 class="heading"><span><?php _e('Related Courses','vibe');?></span></h3>
		<?php
			
			?>
			<ul class="row">
			<?php	
			while($courses->have_posts()): $courses->the_post();
			global $post;
			echo '<li class="col-md-4">';
			

			if(empty($style))
				$style = 'course4';

			echo thumbnail_generator($post,$style,'medium');
			echo '</li>';
			endwhile;
			?>
			</ul>
		</div>
		<?php
			endif;
			wp_reset_postdata();
	}

	function get_course_unfinished_unit($course_id){
		
		if(!is_user_logged_in())
	    	return;

	  	$user_id = get_current_user_id();  

	  	if(isset($_COOKIE['course'])){
	      	$coursetaken=1;
	  	}else{
	      	$coursetaken=get_user_meta($user_id,$course_id,true);      
	  	}
	  	

	  	$course_curriculum = array();
	  	if(function_exists('bp_course_get_curriculum_units'))
	    	$course_curriculum=bp_course_get_curriculum_units($course_id);	

	  	$uid='';
	  	$key = $pre_unit_key = 0;
	  	if(isset($coursetaken) && $coursetaken){
	      	if(isset($course_curriculum) && is_array($course_curriculum) && count($course_curriculum)){
	        
	        	foreach($course_curriculum as $key => $uid){
	            	$unit_id = $uid; // Only number UIDS are unit_id
	            	//Check if User has taken the Unit
	            	if(defined('BP_COURSE_MOD_VERSION') && version_compare(BP_COURSE_MOD_VERSION,'2.3') >= 0){
	                	$unittaken=bp_course_get_user_unit_completion_time($user_id,$uid,$course_id);//
	            	}else{
	                	$unittaken=bp_course_get_user_unit_completion_time($user_id,$uid);//
	            	}
					
	            	if(!isset($unittaken) || !$unittaken){
	              		break; // If not taken, we've found the last unfinished unit.
	            	}
	        	}

	      	}else{
	          	echo '<div class="error"><p>'.__('Course Curriculum Not Set','vibe').'</p></div>';
	          	return;
	      	}    
	  	}
	  	
	  	$units = $course_curriculum;
	  	$unit_id = apply_filters('wplms_get_course_unfinished_unit',$unit_id,$course_id);
	  	$key = apply_filters('wplms_get_course_unfinished_unit_key',$key,$unit_id,$course_id);
	  	$unitkey = $key; // USE FOR BACKUP


	  	$flag = apply_filters('wplms_skip_course_status_page',false,$course_id);
	  	if($flag && (isset($_POST['start_course']) || isset($_POST['continue_course']))){
	  		return $unit_id;
	  	}

	  	/*=======
	  	* NON_AJAX COURSE USECASE
	  	* PROVIDE ACCESS IF CURRENT UNIT IS COMPLETE.
	  	=======*/
	    if(function_exists('bp_course_check_unit_complete')){ 
	        if(defined('BP_COURSE_MOD_VERSION') && version_compare(BP_COURSE_MOD_VERSION,'2.3') >= 0){
	            $x = bp_course_check_unit_complete($unit_id,$user_id,$course_id);            
	        }else{
	            $x = bp_course_check_unit_complete($unit_id,$user_id);
	        }
	    
	        if($x)
	           return $unit_id;
	    } //end function exists check
	    


	  	$flag=apply_filters('wplms_next_unit_access',true,$units[$pre_unit_key]);
	  	$drip_enable= apply_filters('wplms_course_drip_switch',get_post_meta($course_id,'vibe_course_drip',true),$course_id);


	  	if(vibe_validate($drip_enable)){


	  		// BY PASS 
	  		// DRIP FOR FIRST UNIT
	  		if($key == 0){ 
	  		//SET DRIP ACCESS TIME FOR FIRST UNIT
		  		if(defined('BP_COURSE_MOD_VERSION') && version_compare(BP_COURSE_MOD_VERSION,'2.3') >= 0){
	            	$x=bp_course_get_drip_access_time($units[$key],$user_id,$course_id);
	        	}else{
	            	$x=bp_course_get_drip_access_time($units[$key],$user_id);
	        	}
	        	// SET DRIP TIME IF NOT EXISTS
	        	if(empty($x)){	
			  		if(defined('BP_COURSE_MOD_VERSION') && version_compare(BP_COURSE_MOD_VERSION,'2.3') >= 0){
		            	bp_course_update_unit_user_access_time($units[$key],$user_id,time(),$course_id);
		        	}else{
		            	bp_course_update_unit_user_access_time($units[$key],$user_id,time());
		        	}	
		        }

		  		return $unit_id;
		  	}

	  		/*=======
		  	* NON_AJAX COURSE USECASE &  RANDOM UNIT ACCESS
		  	* GET CURRENT & PREVIOUS UNIT KEY
		  	=======*/
		    for($i=($key-1);$i>=0;$i--){
		    	if(function_exists('bp_course_check_unit_complete')){

		        	//CHECK IF PRE_UNIT MARKED COMPLETE
		        	//IF YES THEN RECALCULATE CURRENT UNIT AND PREV_UNIT
		            if(defined('BP_COURSE_MOD_VERSION') && version_compare(BP_COURSE_MOD_VERSION,'2.3') >= 0){
		                $x = bp_course_check_unit_complete($units[$i],$user_id,$course_id);
		            }else{
		                $x = bp_course_check_unit_complete($units[$i],$user_id);
		            }
		            // ABOVE IS REQUIRED BECAUSE INSTRUCTOR CAN 
		            // MARK THE UNIT COMPLETE FROM THE BACKEND
		            if(!empty($x)){
		                $pre_unit_key = $i;
		                // IF PREVIOUS UNIT IS COMPLETE
		                // CHECK IF DRIP TIME EXISTS
		                if(defined('BP_COURSE_MOD_VERSION') && version_compare(BP_COURSE_MOD_VERSION,'2.3') >= 0){
			            	$x=bp_course_get_drip_access_time($units[$i],$user_id,$course_id);
			        	}else{
			            	$x=bp_course_get_drip_access_time($units[$i],$user_id);
			        	}
			        	// SET DRIP TIME IF NOT EXISTS
			        	if(empty($x)){	
			        		if(defined('BP_COURSE_MOD_VERSION') && version_compare(BP_COURSE_MOD_VERSION,'2.3') >= 0){
				            	bp_course_update_unit_user_access_time($units[$pre_unit_key],$user_id,time(),$course_id);
				        	}else{
				            	bp_course_update_unit_user_access_time($units[$pre_unit_key],$user_id,time());
				        	}	
			        	}
		                
		                
		                $unitkey = $pre_unit_key+1;
		                break;
		            }else{
		            	//IF NOT MARKED COMPELTE, 
		            	//CHECK IF PRE-UNIT DRIP ACCESS TIME EXISTS
		            	if(defined('BP_COURSE_MOD_VERSION') && version_compare(BP_COURSE_MOD_VERSION,'2.3') >= 0){
			            	$x=bp_course_get_drip_access_time($units[$i],$user_id,$course_id);
			        	}else{
			            	$x=bp_course_get_drip_access_time($units[$i],$user_id);
			        	}

			        	if(!empty($x) && ($x < time())){ // NOT SET AS FUTURE FOR DRIP ORIGIN
			                $pre_unit_key = $i; // UNIT ACCESSED BUT NOT MARKED COMPLETE
			                $unitkey = $pre_unit_key+1;
			                break;
			            }
		            }
		        }
		    }//end for
			
			//Set the NEW KEY 
			if(!empty($unitkey)){
				$key = $unitkey;	
				$unit_id = $units[$key];
			}
			
			if(empty($pre_unit_key)){
				$pre_unit_key = 0;
			}
	
	      	$drip_duration_parameter = apply_filters('vibe_drip_duration_parameter',86400,$course_id);
	      	$drip_duration = get_post_meta($course_id,'vibe_course_drip_duration',true);
	      
	      	$total_drip_duration = apply_filters('vibe_total_drip_duration',($drip_duration*$drip_duration_parameter),$course_id,$unit_id,$units[$pre_unit_key]);

	      	$this->element = apply_filters('wplms_drip_feed_element_in_message',__('Unit','vibe'),$course_id);

	      	if($key > 0){

	        	if(defined('BP_COURSE_MOD_VERSION') && version_compare(BP_COURSE_MOD_VERSION,'2.3') >= 0){
	            	$pre_unit_time=bp_course_get_drip_access_time($units[$pre_unit_key],$user_id,$course_id);
	        	}else{
	            	$pre_unit_time=bp_course_get_drip_access_time($units[$pre_unit_key],$user_id);
	        	}
	        	
	        	if(!empty($pre_unit_time)){
	          
	            	$value = $pre_unit_time + $total_drip_duration;
	            
	            	$value = apply_filters('wplms_drip_value',$value,$units[$pre_unit_key],$course_id,$units[$key],$units);
	            	
	            	if($value > time()){
	                	$flag=0;
	                	$this->value = $value;
	                	add_action('wplms_before_start_course_content',function(){
	                    	$remaining = tofriendlytime($this->value - time());
	                    	echo '<div class="container top30"><div class="row"><div class="col-md-9"><div class="message"><p>'.sprintf(__('Next %s will be available in %s','vibe'),$this->element,$remaining).'</p></div></div></div></div>';
	                	});
	              		return $units[$pre_unit_key];
	            	}else{

	                	if(defined('BP_COURSE_MOD_VERSION') && version_compare(BP_COURSE_MOD_VERSION,'2.3') >= 0){
	                    	$cur_unit_time=bp_course_get_drip_access_time($units[$key],$user_id,$course_id);
	                	}else{
	                    	$cur_unit_time=bp_course_get_drip_access_time($units[$key],$user_id);
	                	}

	                	
	                	if(!isset($cur_unit_time) || $cur_unit_time ==''){

	                    	if(defined('BP_COURSE_MOD_VERSION') && version_compare(BP_COURSE_MOD_VERSION,'2.3') >= 0){
	                        	bp_course_update_unit_user_access_time($units[$key],$user_id,time(),$course_id);
	                    	}else{
	                        	bp_course_update_unit_user_access_time($units[$key],$user_id,time());      
	                    	}

	                    	//Parmas : Next Unit, Next timestamp, course_id, userid
	                    	do_action('wplms_start_unit',$units[$key],$course_id,$user_id,$units[$pre_unit_key],(time()+$total_drip_duration));
	                	}
	                	
	                	return $units[$pre_unit_key];
	                	
	            	} 
	        	}else{

	            	if(isset($pre_unit_key )){

	                	if(defined('BP_COURSE_MOD_VERSION') && version_compare(BP_COURSE_MOD_VERSION,'2.3') >= 0){
	                    	$completed = bp_course_get_user_unit_completion_time($user_id,$units[$pre_unit_key],$course_id);
	                	}else{
	                    	$completed = get_user_meta($user_id,$units[$pre_unit_key],true);
	                	}
	                
	                
	                	if(!empty($completed)){
	                    	if(defined('BP_COURSE_MOD_VERSION') && version_compare(BP_COURSE_MOD_VERSION,'2.3') >= 0){
	                        	bp_course_update_unit_user_access_time($units[$pre_unit_key],$user_id,time(),$course_id);  
	                    	}else{
	                        	bp_course_update_unit_user_access_time($units[$pre_unit_key],$user_id,time());  
	                    	}
	                    
	                    	$pre_unit_time = time();
	                    	$value = $pre_unit_time + $total_drip_duration;
	                    	$value = apply_filters('wplms_drip_value',$value,$units[$pre_unit_key],$course_id,$units[$key],$units);
	                    	
	                    	$this->value = $value-$pre_unit_time;
	                    	add_action('wplms_before_start_course_content',function(){
	                        	echo '<div class="container top30"><div class="row"><div class="col-md-9"><div class="message"><p>'.sprintf(__('Next %s will be available in %s','vibe'),$this->element,tofriendlytime($this->value)).'</p></div></div></div></div>';
	                    	});
	                   
	                    	return $units[$pre_unit_key];
	                	}else{
	                   		add_action('wplms_before_start_course_content',function(){
	                        	echo '<div class="container top30"><div class="row"><div class="col-md-9"><div class="message"><p>'.sprintf(__('Requested %s can not be accessed.','vibe'),$this->element).'</p></div></div></div></div>';
	                    	});
	                  
	                  		return $units[$pre_unit_key];
	                	}
	            	}else{
	            		add_action('wplms_before_start_course_content',function(){  
	                        echo '<div class="container top30"><div class="row"><div class="col-md-9"><div class="message"><p>'.sprintf(__('Requested %s can not be accessed.','vibe'),$this->element).'</p></div></div></div></div>';
	                    });
	                 
	                    return $units[$pre_unit_key];
	            	}
	            	die();
	        	} //Empty pre-unit time

	    	}
	    }  // End Drip Enable check

  
	  	if(isset($unit_id) && $flag && isset($key)){// Should Always be set 
		    if($key == 0){
		      	$unit_id =''; //Show course start if first unit has not been started
		    }else{
		      	$unit_id=$unit_id; // Last un finished unit
		    }
	  	}else{
		    if(isset($key) && $key > 0){ 
		       $unit_id=$units[($key-1)];
		    }else{
		      	$unit_id = '' ;
		    }
	  	} 
		return $unit_id;
	}

	function show_bp_error(){
		global $bp;
    	if(!empty($bp->template_message)){
        	echo '<div class="message '.$bp->template_message_type.'">'.$bp->template_message.'</div>';
    	}
	}

	/*
	DETECT COURSE CATEGORY / LEVEL / LOCATION REDIRECT 
	 */
	function detect_cat_level_location(){
		
		if(is_tax(array('course-cat','level','location'))){
			$tax = get_query_var( 'taxonomy' );
			$term = get_query_var( 'term' );
			echo '<input type="hidden" class="current-course-cat" data-cat="'.$tax.'" data-slug="'.$term.'" value="'.$term.'"/>';
		}
		
	}
}

WPLMS_Actions::init();