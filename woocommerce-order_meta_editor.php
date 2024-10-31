<?php

/**
 * The plugin bootstrap file
 *
 * This file is read by WordPress to generate the plugin information in the plugin
 * admin area. This file also includes all of the dependencies used by the plugin,
 * registers the activation and deactivation functions, and defines a function
 * that starts the plugin.
 *
 * @link              https://startnet.co.uk
 * @since             1.0.0
 * @package           order_meta_editor_for_woocommerce
 *
 * @wordpress-plugin
 * Plugin Name:       Order Meta Editor for Woocommerce
 * Plugin URI:        https://startnet.co.uk/woocommerce_order_meta_editor
 * Description:       This plugin allows you to edit Woocommerce order meta data.
 * Version:           2.0
 * Author:            Startnet Ltd
 * Author URI:        https://startnet.co.uk
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 */

/*
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
*/

/* Runs when plugin is activated */
register_activation_hook(__FILE__,'order_meta_editor_install'); 

/* Runs on plugin deactivation*/
register_deactivation_hook( __FILE__, 'order_meta_editor_remove' );

add_action( 'admin_enqueue_scripts', 'order_meta_editor_scripts');

function order_meta_editor_install() {
/* Creates new database field */

}

function order_meta_editor_remove() {
/* Deletes the database field */
}

// check is in the admin backend
if ( is_admin() ){

	/* Call the html code */
	add_action( 'admin_menu', 'register_order_meta_editor_menu_page' );

} // is_admin()

function order_meta_editor_scripts()
{
    // Register the script like this for a plugin:
    wp_register_script( 'omem-script', plugins_url( 'js/order_meta_editor_admin.js', __FILE__ ),array( 'jquery' ) );
	wp_register_style( 'omem-style', plugins_url( 'css/order_meta_editor_admin.css', __FILE__  ) );	

	wp_enqueue_script('jquery-ui-datepicker');
	wp_enqueue_script('jquery-ui-core');
	
    wp_enqueue_script( 'omem-script' );
	wp_enqueue_style( 'omem-style' );

}

function register_order_meta_editor_menu_page(){
// the "read" or "manage_options" parameters tell when to display the menu - 'read' will show the menu to all user roles, 'manage_options' only to 'admin'

	 add_submenu_page( 'woocommerce', 'Order Meta Editor', 'Order Meta Editor', 'manage_options', 'order_meta_editor_details', 'order_meta_editor_page');	

}

function order_meta_editor_page() {
	global $wpdb;
	global $woocommerce;
	if (current_user_can('manage_options')) :

		$oc=0;
		//var_dump($_POST);
		if (isset($_POST['edit_order_number']) && isset($_POST['order_num'])) {
			$order_num=intval(sanitize_text_field($_POST['order_num']));
		
			// check nonce in this case
			if ( !check_admin_referer( 'order_meta_editor_form_run', 'order_meta_editor_check_nonce' ) ) {
				print 'Sorry, your nonce did not verify.';
				exit;
			}
		}
		else { $order_num=false; }

		?>
		<div id="order_item_edit_page" class="main_term">
		<h2>Order Item Edit Page</h2>
		<p>You can edit individual order item meta data here. <strong>Please be careful!</strong></p>
		<form method="POST" id="order_item_edit">
		<label>Order number:</label><input type="text" name="order_num" id="order_num" value="<?php echo $order_num; ?>"> 
		<input type="submit" value="Edit order">
		<input type="hidden" name="edit_order_number" value="1">
		<?php

		if ($order_num!==false) {
			
			$aorder = wc_get_order($order_num);
			
			if ($aorder!==false) {
				print "<p><strong>Order by: </strong>".$aorder->get_billing_first_name()." ".$aorder->get_billing_last_name()."<br>";
				print "<strong>Date: </strong>".$aorder->get_date_created()."</p>";
			}

			// update meta if necessary
			if (isset($_POST['update_meta_item']) && $_POST['update_meta_item']=="1") {
				if (isset($_POST['meta_item'])) { $meta_id=sanitize_text_field($_POST['meta_item']); } else { $meta_id=""; }
				$meta_value=sanitize_text_field($_POST['meta_value']);
				$meta_name=sanitize_text_field($_POST['meta_name']);
				echo "<p class='updating_meta'><strong>Updating meta item: $meta_name with '$meta_value' (ID=$meta_id)</strong></p>";
				
				$sql="UPDATE ".$wpdb->prefix."woocommerce_order_itemmeta
				SET meta_value='%s'
				WHERE meta_id='%s'";
				$sql=$wpdb->prepare($sql,array($meta_value,$meta_id));
				//echo $sql;
				$res=$wpdb->query($sql);
				if ($res==0) { echo "<p class='success_meta'>Meta value was same as before!</p>"; }
				else
				if ($res>0) { echo "<p class='success_meta'>Successfully updated meta!</p>"; }
				
				if($wpdb->last_error !== '') :
					$str   = htmlspecialchars( $wpdb->last_result, ENT_QUOTES );
					$query = htmlspecialchars( $wpdb->last_query, ENT_QUOTES );
					print "<div id='error'>
					<p class='wpdberror'><strong>WordPress database error:</strong> [$str]<br />
					<code>$query</code></p>
					</div>";

				endif;	
			}

			$sql="select * from ".$wpdb->prefix."woocommerce_order_items 
			WHERE order_id=$order_num";
			//echo $sql."<br>";
			$order_items = $wpdb->get_results($sql);
			foreach ($order_items as $order_item) {
				?>
				<p><strong>Order item: <?php echo $order_item->order_item_name." (".$order_item->order_item_id.")"; ?></strong></p>
				<table id="edit_order_item">
				<?php
				//var_dump($order_item);
				$sql="select * from ".$wpdb->prefix."woocommerce_order_itemmeta 
				WHERE order_item_id=".$order_item->order_item_id;
				$order_item_meta = $wpdb->get_results($sql);
				$mitems=0;
				foreach ($order_item_meta as $meta) {
				 // do not display _ items
				 if (preg_match("/^_/",$meta->meta_key)) { continue; }
				 $mitems++;
				 $meta_id=esc_html($meta->meta_id);
				 $meta_key=esc_html($meta->meta_key);
				 $meta_value=esc_html($meta->meta_value);
				 echo "<tr><td class='tmeta_key'>".$meta_key."</td><td class='tmeta_value'><input type='text' value='".$meta_value."' id='meta_id_".$meta_id."'></td><td><input type='button' class='update_meta_button' value='Update' data-meta_name='".$meta_key."' data-meta_id='".$meta_id."'></td>
				 <td class='meta_id'>(ID=".$meta_id.")</td>
				 </tr>";
				}
				if ($mitems==0) { echo "No meta data found."; }
				?>
				</table>
				<?php
			} // foreach

		} // if order_num
				
		?>
		<input type="hidden" id="update_meta_item" name="update_meta_item" value="0">
		<input type="hidden" id="meta_item" name="meta_item" value="">
		<input type="hidden" id="meta_name" name="meta_name" value="">
		<input type="hidden" id="meta_value" name="meta_value" value="">

		<?php wp_nonce_field( 'order_meta_editor_form_run','order_meta_editor_check_nonce' );  ?>

		</form>
		<?php
		if (($order_num===false)|| ($aorder===false)) { echo "<p>Sorry, could not find that order number.</p>"; }
		?>
		<p>&nbsp;</p>
		<p class="copyright">&copy; <?php echo date('Y'); ?> Order Meta Editor for Woocommerce by <a href="" target="_blank">Startnet Ltd</a></p>
		<?php

	endif; // if can manage_options
} // order_item_edit_page

?>
