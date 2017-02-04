<?php
/**
 * Plugin Name: Search order by product SKU for WooCommerce
 * Plugin URI: http://ldav.it/shop/
 * Description: Allows WooCommerce orders to be searched by product SKU - Stock Keeping Unit.
 * Version: 0.1
 * Author: laboratorio d'Avanguardia
 * Author URI: http://ldav.it/
 * Requires at least: 4.4
 * Tested up to: 4.7.2
 *
 * Text Domain: ldav_woosbsku
 * Domain Path: /languages/
 * License: GPLv2 or later
 * License URI: http://www.opensource.org/licenses/gpl-license.php
*/

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly
if ( !class_exists( 'Woo_Search_by_SKU' ) ) :
define('Woo_SBSKU_DOMAIN', 'ldav_woosbsku');
	
class Woo_Search_by_SKU {
	public $plugin_basename;
	public $plugin_url;
	public $plugin_path;
	public $version = '0.1';
	protected static $instance = null;

	public static function instance() {
		if ( is_null( self::$instance ) ) self::$instance = new self();
		return self::$instance;
	}

	public function __construct() {
		$this->plugin_basename = plugin_basename(__FILE__);
		$this->plugin_url = plugin_dir_url($this->plugin_basename);
		$this->plugin_path = trailingslashit(dirname(__FILE__));
		$this->init_hooks();
	}

	public function init() {
		$locale = apply_filters( 'plugin_locale', get_locale(), Woo_SBSKU_DOMAIN );
		load_plugin_textdomain( Woo_SBSKU_DOMAIN, FALSE, basename( dirname( __FILE__ ) ) . '/languages/' );
	}

	private function init_hooks() {
		add_action( 'plugins_loaded', array( $this, 'init' ), 0 );
		if ($this->is_wc_active()) {
			add_action( 'restrict_manage_posts', array( $this, 'search_by_sku_order_filter_in_order') , 50  );
			add_action( 'posts_where', array( $this, 'search_by_sku_order_filter_where') );
		} else {
			add_action( 'admin_notices', array ( $this, 'check_wc' ) );
		}
	}

	public function is_wc_active() {
		$plugins = get_site_option( 'active_sitewide_plugins', array());
		if (in_array('woocommerce/woocommerce.php', get_option( 'active_plugins', array())) || isset($plugins['woocommerce/woocommerce.php'])) {
			return true;
		} else {
			return false;
		}
	}

	public function check_wc( $fields ) {
		$class = "error";
		$message = sprintf( __( 'Search order by product SKU requires %sWooCommerce%s to be installed and activated!' , Woo_SBSKU_DOMAIN ), '<a href="https://wordpress.org/plugins/woocommerce/">', '</a>' );
		echo"<div class=\"$class\"> <p>$message</p></div>";
	}	

	public function search_by_sku_order_filter_in_order(){
		global $typenow, $wpdb;
		if ( 'shop_order' != $typenow ) {return;}
		$filtro = "";
		if (isset( $_GET['search_by_sku_order_type_filter'] ) && !empty( $_GET['search_by_sku_order_type_filter'] ) ) {
			$filtro = $_GET['search_by_sku_order_type_filter'];
		}
	
	?>
	<span id="search_by_sku_order_type_filter_wrap">
	<input type="search" name="search_by_sku_order_type_filter" id="search_by_sku_order_type_filter" placeholder="<?php _e('Search by SKU', Woo_SBSKU_DOMAIN); ?>" value="<?php echo $filtro ?>">
	</span>
	<?php
	}
	
	public function search_by_sku_order_filter_where( $where ) {
		global $typenow, $wpdb;
		if( is_search() && 'shop_order' == $typenow ) {
			if ( isset( $_GET['search_by_sku_order_type_filter'] ) && !empty( $_GET['search_by_sku_order_type_filter'] ) ) {
				$filtro = trim($_GET['search_by_sku_order_type_filter']);
				$filtro = str_replace("*", "%", $filtro);
				$filtro = $wpdb->_escape($filtro);
				$where .= " AND ($wpdb->posts.ID IN(
				SELECT $wpdb->posts.ID FROM $wpdb->posts
				INNER JOIN " . $wpdb->prefix . "woocommerce_order_items ON $wpdb->posts.ID = " . $wpdb->prefix . "woocommerce_order_items.order_id
				INNER JOIN " . $wpdb->prefix . "woocommerce_order_itemmeta ON " . $wpdb->prefix . "woocommerce_order_items.order_item_id = " . $wpdb->prefix . "woocommerce_order_itemmeta.order_item_id
				INNER JOIN $wpdb->postmeta ON " . $wpdb->prefix . "woocommerce_order_itemmeta.meta_value = $wpdb->postmeta.post_id
				WHERE $wpdb->posts.post_type = 'shop_order'
				AND " . $wpdb->prefix . "woocommerce_order_items.order_item_type = 'line_item'
				AND " . $wpdb->prefix . "woocommerce_order_itemmeta.meta_key = '_product_id'
				AND $wpdb->postmeta.meta_key = '_sku'
				AND $wpdb->postmeta.meta_value LIKE '" . $filtro . "') )";
				echo $where;
			}
		}
		return $where;
	}
	
}
endif;

$WC_SrcBySKU = new Woo_Search_by_SKU();


?>