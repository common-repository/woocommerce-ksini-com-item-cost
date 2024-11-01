<?php
/**
 * @package Woocommerce_Ksini.com_Item_Cost
 * @version 1.3
 * 
 */
/*
Plugin Name: Woocommerce Ksini.com Item Cost
Plugin URI: http://febiansyah.name
Description: Adding item cost field to general tab in product editing, and add stats for profit calculation
Author: Hidayat Febiansyah
Version: 1.3
Author URI: http://febiansyah.name
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html
*/
if (! defined ( 'ABSPATH' ))
	exit (); // Exit if accessed directly

if (! defined ( 'COM_KSINI_PLUGIN_ITEM_COST' )){

	define('COM_KSINI_PLUGIN_ITEM_COST',1);

	define('COM_KSINI_FIELD_ITEM_COST',"_ksini_item_cost");
	
	// Display Fields
	add_action( 'woocommerce_product_options_general_product_data', 'woo_add_item_cost_fields' );
	
	// Save Fields
	add_action( 'woocommerce_process_product_meta', 'woo_add_item_cost_fields_save' );
	
	//implementation
	function woo_add_item_cost_fields() {
	
		global $woocommerce, $post;
	
		echo '<div class="options_group">';
	
		// Number Field
		woocommerce_wp_text_input( 
			array( 
				'id'                => COM_KSINI_FIELD_ITEM_COST, 
				'label'             => __( 'Item Cost','woocommerce' ), 
				'placeholder'       => '', 
				'description'       => __( 'Enter item cost', 'woocommerce' ),
				'type'              => 'text',
				'data_type'			=> 'price',
				'custom_attributes' => array(
						'step' 	=> 'any',
						'min'	=> '0'
					) 
			)
		);
	
		echo '</div>';
	
	}
	
	//saving values
	function woo_add_item_cost_fields_save($post_id){
		if ( defined('DOING_AUTOSAVE') && DOING_AUTOSAVE )
			return $post_id;
		
		if(!isset($_POST[COM_KSINI_FIELD_ITEM_COST]))
			return;
		
		// Number Field
		$woocommerce_number_field = $_POST[COM_KSINI_FIELD_ITEM_COST];
		if( !empty( $woocommerce_number_field ) ){
			global $wpdb;
			
			//previous value
			$prev_item_cost = get_post_meta( $post_id, COM_KSINI_FIELD_ITEM_COST, true );
			
			update_post_meta( $post_id, COM_KSINI_FIELD_ITEM_COST, esc_attr( $woocommerce_number_field ) );
		
			if(trim($prev_item_cost) == ''){
				//cascade item cost update
				$sql = "select order_id
					from {$wpdb->prefix}postmeta meta join
					(
						select orders.*, meta1.meta_value AS _product_id, meta2.meta_value AS _qty
						from {$wpdb->prefix}woocommerce_order_items orders
						left join {$wpdb->prefix}woocommerce_order_itemmeta meta1 on meta1.order_item_id=orders.order_item_id
						left join {$wpdb->prefix}woocommerce_order_itemmeta meta2 on meta2.order_item_id = orders.order_item_id
						and meta1.meta_key = '_product_id'
						and meta2.meta_key = '_qty'
					) orders ON meta.post_id = orders._product_id
							where meta.meta_key = '".COM_KSINI_FIELD_ITEM_COST."'
					and _product_id = %s";
				
				$order_ids = $wpdb->get_col($wpdb->prepare($sql, $post_id));
				
				foreach($order_ids as $order_id){
					add_ksini_item_cost_checkout($order_id);
				}
				
			}
		}else{
             update_post_meta( $post_id, COM_KSINI_FIELD_ITEM_COST, '' );
        }
	}
	
	//custom column in product list
	add_filter( 'manage_edit-product_columns', 'add_ksini_item_column' );
	function add_ksini_item_column($columns){
		$new_columns[COM_KSINI_FIELD_ITEM_COST] = "Item Cost";
	
		$new_columns['cb'] = $columns['cb'];
		
		return $new_columns;
	}
	
	add_action( 'manage_product_posts_custom_column', 'add_ksini_item_column_value',10,2);
	function add_ksini_item_column_value($column_name, $post_id){
		if ( $column_name == COM_KSINI_FIELD_ITEM_COST ) {
			echo woocommerce_price(get_post_meta( $post_id, COM_KSINI_FIELD_ITEM_COST, true )) ;
		}
	}	
		
	/**
	 * insert item cost to order meta on checkout
	 **/
	add_action('woocommerce_checkout_update_order_meta', 'add_ksini_item_cost_checkout');
	 
	/**
	 * @param unknown $order_id
	 */
	function add_ksini_item_cost_checkout( $order_id ) {
		global $wpdb;
		
		/*
		 * select _product_id, _qty, meta_value AS _ksini_item_cost,
				_qty * meta_value as total_cost
			 from wp_postmeta meta join
			(select orders.*, meta1.meta_value AS _product_id, meta2.meta_value AS _qty
			 from {$wpdb->prefix}woocommerce_order_items orders
			left join {$wpdb->prefix}woocommerce_order_itemmeta meta1 on meta1.order_item_id=orders.order_item_id
			left join {$wpdb->prefix}woocommerce_order_itemmeta meta2 on meta2.order_item_id = orders.order_item_id
			where orders.order_id=30 
			and meta1.meta_key = '_product_id'
			and meta2.meta_key = '_qty'
			) orders ON meta.post_id = orders._product_id
			where meta.meta_key = '_ksini_item_cost'
		 */
		$sql = "select SUM(_qty * meta_value) as total_cost
			 from {$wpdb->prefix}postmeta meta join
			(select orders.*, meta1.meta_value AS _product_id, meta2.meta_value as _qty
			 from {$wpdb->prefix}woocommerce_order_items orders
			left join {$wpdb->prefix}woocommerce_order_itemmeta meta1 on meta1.order_item_id=orders.order_item_id
			left join {$wpdb->prefix}woocommerce_order_itemmeta meta2 on meta2.order_item_id = orders.order_item_id
			where orders.order_id=".$order_id."
			and meta1.meta_key = '_product_id'
			and meta2.meta_key = '_qty'
			) orders ON meta.post_id = orders._product_id
			where meta.meta_key = '".COM_KSINI_FIELD_ITEM_COST."'";
		
		$total_cost =  $wpdb->get_var( $sql );
		
		update_post_meta( $order_id, COM_KSINI_FIELD_ITEM_COST, $total_cost);
	}

	
	// Add to our admin_init function
	add_action('quick_edit_custom_box',  'add_ksini_item_quick_edit', 10, 2);
	
	function add_ksini_item_quick_edit($column_name, $post_type) {
		if ($column_name != COM_KSINI_FIELD_ITEM_COST) return;
		?>
	    <fieldset class="inline-edit-col-left">
	    <div class="inline-edit-col">
	    <?php
	      woocommerce_wp_text_input( 
			array( 
				'id'                => COM_KSINI_FIELD_ITEM_COST, 
				'label'             => __( 'Item Cost','woocommerce' ), 
				'placeholder'       => '', 
				'description'       => __( 'Enter item cost', 'woocommerce' ),
				'type'              => 'text',
				'data_type'			=> 'price',
				'custom_attributes' => array(
						'step' 	=> 'any',
						'min'	=> '0'
					) 
			)
		);
	    ?>
	    </div>
	    </fieldset>
	    <?php
	}
	
	// Add to our admin_init function
	add_action('save_post', 'woo_add_item_cost_fields_save');
	
	// Add to our admin_init function
	add_action('admin_footer', 'add_ksini_item_quick_edit_javascript');
	
	function add_ksini_item_quick_edit_javascript() {
		global $current_screen;
		if (($current_screen->id != 'edit-post') || ($current_screen->post_type != 'post')) return;
		 
		?>
	    <script type="text/javascript">
	    <!--
	    function set_inline_item_cost(itemCost, nonce) {
	        // revert Quick Edit menu so that it refreshes properly
	        inlineEditPost.revert();
	        var widgetInput = document.getElementById('<?=COM_KSINI_FIELD_ITEM_COST?>');
	        // check option manually
	        widgetInput.value = itemCost;
	    }
	    //-->
	    </script>
	    <?php
	}
	// Add to our admin_init function
	add_filter('post_row_actions', 'add_ksini_item_quick_edit_link', 10, 2);
	
	function add_ksini_item_quick_edit_link($actions, $post) {
		global $current_screen;
		if (($current_screen->id != 'edit-post') || ($current_screen->post_type != 'post')) return $actions;
		
		$item_total = get_post_meta( $post->ID, COM_KSINI_FIELD_ITEM_COST, TRUE);
		$actions['inline hide-if-no-js'] = '<a href="#" class="editinline" title="';
		$actions['inline hide-if-no-js'] .= esc_attr( __( 'Edit this item inline' ) ) . '" ';
		$actions['inline hide-if-no-js'] .= " onclick=\"alert('oke');set_inline_item_cost('{$item_total}')\">";
		$actions['inline hide-if-no-js'] .= __( 'Quick&nbsp;Edit' );
		$actions['inline hide-if-no-js'] .= '</a>';
				return $actions;
	}
	
	include 'profit-month.php';
}
