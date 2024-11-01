<?php
/*
 * Description: Calculate profit for the month
 * Author: Hidayat Febiansyah Version: 1.0 Author URI: http://febiansyah.name
 * 
 * GPLv2
 */
if (! defined ( 'ABSPATH' ))
	exit (); // Exit if accessed directly
if (! class_exists ( 'WC_KSini_Profit_Report' )) {
	
	class WC_KSini_Profit_Report {
		public $plugin_name = "";
		public function __construct() {
			global $options;
			$this->plugin_name = "WooCommerce KSini Profit Report";
			
			if (is_admin ()) {
				
				wp_enqueue_script('jquery-ui-datepicker');
				wp_enqueue_style('jquery-style', plugins_url ( 'css/smoothness/jquery-ui-1.10.4.custom.min.css', __FILE__));
				
				add_action ( 'admin_menu', array (
						&$this,
						'wcksinireport_add_page' 
				) );
				
				if (isset ( $_GET ['page'] ) && $_GET ['page'] == "wcksinireport_page") {
					add_action ( 'admin_footer', array (
							&$this,
							'admin_footer' 
					) );
					$this->per_page = get_option ( 'wcksinireport_per_page', 5 );
					//$this->define_constant ();
				}
			}
		}
		function wcksinireport_add_page() {
			$main_page = add_menu_page ( $this->plugin_name, 'KSini Profit Report', 'manage_options', 'wcksinireport_page', array (
					$this,
					'wcksinireport_page' 
			), plugins_url ( 'images/menu_icons.png', __FILE__), '57.5' );
		}
		function admin_footer() {
			
		}
		function wcksinireport_page() {
			$start_date = isset($_GET['start_date'])?$_GET['start_date']:'';
			$end_date = isset($_GET['end_date'])?$_GET['end_date']:'';
			
			if(trim($start_date) == ''
				|| trim($end_date) == ''){
				$total_orders 		=	$this->wcksinireport_get_total_order_count();
				$total_sales  		=	$this->wcksinireport_get_total_order_amount();
	// 			$total_customer  	=	$this->wcksinireport_get_total_customer_count();
	// 			$total_categories  	=	$this->wcksinireport_get_total_categories_count();
	// 			$total_products  	=	$this->wcksinireport_get_total_products_count();
				$total_item_cost	=	$this->wcksinireport_get_total_item_cost();
				$total_shipping_cost=	$this->wcksinireport_get_total_shipping_cost();
			}else{
				$total_orders 		=	$this->wcksinireport_get_total_order_count($start_date,$end_date);
				$total_sales  		=	$this->wcksinireport_get_total_order_amount($start_date,$end_date);
				// 			$total_customer  	=	$this->wcksinireport_get_total_customer_count();
				// 			$total_categories  	=	$this->wcksinireport_get_total_categories_count();
				// 			$total_products  	=	$this->wcksinireport_get_total_products_count();
				$total_item_cost	=	$this->wcksinireport_get_total_item_cost($start_date,$end_date);
				$total_shipping_cost=	$this->wcksinireport_get_total_shipping_cost($start_date,$end_date);
			}
			
			?>
			<div>
			<form action="" method="get">
			<input type="hidden" name="page" value="wcksinireport_page"/>
			Start Date: <input type="text" id="start_date" name="start_date" value="<?=$start_date?>"/><br/>
			End Date: <input type="text" id="end_date" name="end_date" value="<?=$end_date?>"/><br/>
			
			<input type="submit" value="Show stats in range"/>
			</form>
			<script type="text/javascript">
			
			jQuery(document).ready(function() {
				jQuery('#start_date').datepicker({
					dateFormat : 'yy-mm-dd'
				});
				jQuery('#end_date').datepicker({
					dateFormat : 'yy-mm-dd'
				});
			});
			
			</script>
			<?php
			if(trim($start_date) == ''
				|| trim($end_date) == '')
				echo "<h2>Profit stats of all time</h2>";
			else
				echo "<h2>Sales in range of : <strong>".$start_date." to ".$end_date."</strong></h2>	";
			
			echo '<div id="sales_stats">';
			echo "Total orders: ".$total_orders."<br/>";
			echo "Total sales: ".woocommerce_price($total_sales)."<br/>";
			echo "Delivery cost: ".woocommerce_price($total_shipping_cost)."<br/>";
			echo "Total item cost: ".woocommerce_price($total_item_cost)."<br/>";
			
			echo "<h3>PROFIT (Total sales - Deliver cost - Total item cost): <strong>".woocommerce_price($total_sales-$total_shipping_cost-$total_item_cost)."</strong></h3><br/>";
			echo '</div>';
		}
		
		/* 14-Feb-2014 */
		function wcksinireport_get_total_categories_count() {
			global $wpdb, $sql, $Limit;
			$sql = "SELECT COUNT(*) As 'category_count' FROM {$wpdb->prefix}term_taxonomy as term_taxonomy
			LEFT JOIN  {$wpdb->prefix}terms as terms ON terms.term_id=term_taxonomy.term_id
			WHERE taxonomy ='product_cat'";
			return $wpdb->get_var ( $sql );
			// print_array($order_items);
		}
		function wcksinireport_get_total_products_count() {
			global $wpdb, $sql, $Limit;
			$sql = "SELECT COUNT(*) AS 'product_count'  FROM {$wpdb->prefix}posts as posts WHERE  post_type='product' AND post_status = 'publish'";
			return $wpdb->get_var ( $sql );
		}
		/* 13-Feb-2014 */
		/*total order count*/
		function wcksinireport_get_total_order_count($start_date='', $end_date='') {
			global $wpdb;
			$sql = " SELECT count(*) AS 'total_order_count'
		FROM {$wpdb->prefix}posts as posts
		WHERE  post_type='shop_order'";
			if(trim($start_date)!='' && trim($end_date)!='')
				$sql .= "and post_date between date('".$start_date."')
					and date_add(date('".$end_date."'), INTERVAL 1 DAY)";
			
			return $wpdb->get_var ( $sql );
		}
		/* total order amount */
		function wcksinireport_get_total_order_amount($start_date='', $end_date='') {
			global $wpdb;
			$sql = "SELECT
		SUM(meta_value) AS 'total_order_amount'
		FROM {$wpdb->prefix}posts as posts
		LEFT JOIN  {$wpdb->prefix}postmeta as postmeta ON posts.ID=postmeta.post_id
		WHERE  post_type='shop_order' AND meta_key='_order_total'";
			if(trim($start_date)!='' && trim($end_date)!='')
				$sql .= "and post_date between date('".$start_date."')
					and date_add(date('".$end_date."'), INTERVAL 1 DAY)";
			
			return $wpdb->get_var ( $sql );
		}
		/* Total Customer Count */
		function wcksinireport_get_total_customer_count() {
			$user_query = new WP_User_Query ( array (
					'role' => 'Customer' 
			) );
			return $user_query->total_users;
		}
		
		function wcksinireport_get_total_item_cost($start_date='', $end_date=''){
			global $wpdb;
			$sql = "SELECT
			SUM(meta_value) AS 'total_item_cost'
			FROM {$wpdb->prefix}posts as posts
			LEFT JOIN  {$wpdb->prefix}postmeta as postmeta ON posts.ID=postmeta.post_id
			WHERE  post_type='shop_order' AND meta_key='".COM_KSINI_FIELD_ITEM_COST."'";
			if(trim($start_date)!='' && trim($end_date)!='')
				$sql .= "and post_date between date('".$start_date."')
					and date_add(date('".$end_date."'), INTERVAL 1 DAY)";
				
			return $wpdb->get_var ( $sql );			
		}
		
		function wcksinireport_get_total_shipping_cost($start_date='', $end_date=''){
			global $wpdb;
			$sql = "SELECT
			SUM(meta_value) AS 'total_item_cost'
			FROM {$wpdb->prefix}posts as posts
			LEFT JOIN  {$wpdb->prefix}postmeta as postmeta ON posts.ID=postmeta.post_id
			WHERE  post_type='shop_order' AND meta_key='_order_shipping'";
			if(trim($start_date)!='' && trim($end_date)!='')
				$sql .= "and post_date between date('".$start_date."')
					and date_add(date('".$end_date."'), INTERVAL 1 DAY)";
		
			return $wpdb->get_var ( $sql );
		}
	}
	
	new WC_KSini_Profit_Report ();
}
