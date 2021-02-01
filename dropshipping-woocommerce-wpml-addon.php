<?php
/**
 * Plugin Name:       Knawat WooCommerce DropShipping WPML Support
 * Plugin URI:        https://wordpress.org/plugins/dropshipping-woocommerce/
 * Description:       Knawat WooCommerce DropShipping WPML Support
 * Version:           1.0.0
 * Author:            Knawat
 * Author URI:        https://www.knawat.com/?utm_source=wordpress.org&utm_medium=social&utm_campaign=The%20WC%20Plugin
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       dropshipping-wmpl-woocommerce
 * Domain Path:       /languages
 * WC requires at least: 3.3.0
 * WC tested up to: 4.8.0
 *
 * @package     dropshipping_woocommerce_wpml_addon
 */


// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) exit;

if( ! class_exists( 'dropshipping_woocommerce_wpml_addon' ) ):

class dropshipping_woocommerce_wpml_addon{

    /** Singleton *************************************************************/
	/**
	 * dropshipping_woocommerce_wpml_addon The one true dropshipping_woocommerce_wpml_addon.
	 */
	private static $instance;

    /**
     * Main Knawat Dropshipping Woocommerce Instance.
     * 
     * Insure that only one instance of dropshipping_woocommerce_wpml_addon exists in memory at any one time.
     * Also prevents needing to define globals all over the place.
     *
     * @since 1.0.0
     * @static object $instance
     * @uses dropshipping_woocommerce_wpml_addon::setup_constants() Setup the constants needed.
     * @uses dropshipping_woocommerce_wpml_addon::includes() Include the required files.
     * @uses dropshipping_woocommerce_wpml_addon::laod_textdomain() load the language files.
     * @see run_knawat_dropshipwc_woocommerce()
     * @return object| Knawat Dropshipping Woocommerce the one true Knawat Dropshipping Woocommerce.
     */
	public static function instance() {
		if( ! isset( self::$instance ) && ! (self::$instance instanceof dropshipping_woocommerce_wpml_addon ) ) {
			self::$instance = new dropshipping_woocommerce_wpml_addon;
			self::$instance->setup_constants();

			add_action( 'plugins_loaded', array( self::$instance, 'load_textdomain' ) );
			add_action( 'init', array( self::$instance, 'init_includes' ) );

			self::$instance->includes();
			self::$instance->is_dropshipping_activated();
			self::$instance->is_woo_multilingual_activated();
			self::$instance->is_wpml_activated();
			self::$instance->is_woocommerce_activated().'sd';
		
		}
		return self::$instance;	
		
    }
    
    /** Magic Methods *********************************************************/

	/**
	 * A dummy constructor to prevent Knawat_Dropshipping_Woocommerce from being loaded more than once.
	 *
	 * @since 1.0.0
	 * @see dropshipping_woocommerce_wpml_addon::instance()
	 * @see run_knawat_dropshipwc_wpml_woocommerce()
	 */
	private function __construct() {
		
			add_action( 'admin_notices',array($this,'is_recommended_plugin_activated'));
			
	}

	/**
	 * A dummy magic method to prevent dropshipping_woocommerce_wpml_addon from being cloned.
	 *
	 * @since 1.0.0
	 */
	public function __clone() { _doing_it_wrong( __FUNCTION__, __( 'Cheatin&#8217; huh?', 'dropshipping-wmpl-woocommerce-support' ), '1.0.0' ); }

	/**
	 * A dummy magic method to prevent dropshipping_woocommerce_wpml_addon from being unserialized.
	 *
	 * @since 1.0.0
	 */
	public function __wakeup() { _doing_it_wrong( __FUNCTION__, __( 'Cheatin&#8217; huh?', 'dropshipping-wmpl-woocommerce-support' ), '1.0.0' ); }
    
    private function setup_constants() {

		// Plugin version.
		if( ! defined( 'KNAWAT_DROPWC_WMPL_VERSION' ) ){
			define( 'KNAWAT_DROPWC_WMPL_VERSION', '1.0.0' );
		}

		// Plugin folder Path.
		if( ! defined( 'KNAWAT_DROPWC_PLUGIN_WMPL_DIR' ) ){
			define( 'KNAWAT_DROPWC_PLUGIN_WMPL_DIR', plugin_dir_path( __FILE__ ) );
		}

		// Plugin folder URL.
		if( ! defined( 'KNAWAT_DROPWC_PLUGIN_WMPL_URL' ) ){
			define( 'KNAWAT_DROPWC_PLUGIN_WMPL_URL', plugin_dir_url( __FILE__ ) );
		}

		// Plugin root file.
		if( ! defined( 'KNAWAT_DROPWC_PLUGIN_WMPL_FILE' ) ){
			define( 'KNAWAT_DROPWC_PLUGIN_WMPL_FILE', __FILE__ );
		}

		// Options
		if( ! defined( 'KNAWAT_DROPWC_WMPL_OPTIONS' ) ){
			define( 'KNAWAT_DROPWC_WMPL_OPTIONS', 'knawat_dropshipwc_wmpl_options' );
		}

	}

    private function includes() {

        
	}

	/**
	 * Include required files on init.
	 *
	 * @access public
	 * @since 1.0.0
	 * @return void
	 */
	public function init_includes() {
		if( $this->is_woocommerce_activated() && $this->is_wpml_activated() && $this->is_woo_multilingual_activated()){
			require_once KNAWAT_DROPWC_PLUGIN_WMPL_DIR . 'includes/class-dropshipping-woocommerce-wpml-importer.php';
		}
	}
	

    /**
	 * Loads the plugin language files.
	 * 
	 * @access public
	 * @since 1.0.0
	 * @return void
	 */
	public function load_textdomain(){
		
		load_plugin_textdomain(
			'dropshipping-wmpl-woocommerce',
			false,
			basename( dirname( __FILE__ ) ) . '/languages'
		);
	
    }
    

    /**
	 * Check if woocommerce is activated
	 *
	 * @access public
	 * @since 1.0.0
	 * @return void
	 */
	public function is_woocommerce_activated() {
		$blog_plugins = get_option( 'active_plugins', array() );
		$site_plugins = is_multisite() ? (array) maybe_unserialize( get_site_option('active_sitewide_plugins' ) ) : array();
		
		if ( in_array( 'woocommerce/woocommerce.php', $blog_plugins ) || isset( $site_plugins['woocommerce/woocommerce.php'] ) ) {
			return true;
		}

		return false;
	}


	 /**
	 * Check if Knawat WooCommerce DropShipping is activated
	 *
	 * @access public
	 * @since 1.0.0
	 * @return void
	 */

	public function is_dropshipping_activated() {
		$blog_plugins = get_option( 'active_plugins', array() );
		$site_plugins = is_multisite() ? (array) maybe_unserialize( get_site_option('active_sitewide_plugins' ) ) : array();

		if ( in_array( 'dropshipping-woocommerce/dropshipping-woocommerce.php', $blog_plugins ) || isset( $site_plugins['dropshipping-woocommerce/dropshipping-woocommerce.php'] ) ) {
			return true;
		}

		return false;
	}

	 /**
	 * Check if woocommerce multilingual is activated 
	 *
	 * @access public
	 * @since 1.0.0
	 * @return void
	 */

	public function is_woo_multilingual_activated() {
		$blog_plugins = get_option( 'active_plugins', array() );
		$site_plugins = is_multisite() ? (array) maybe_unserialize( get_site_option('active_sitewide_plugins' ) ) : array();
		
		if ( in_array( 'woocommerce-multilingual/wpml-woocommerce.php', $blog_plugins ) || isset( $site_plugins['woocommerce-multilingual/wpml-woocommerce.php'] ) ) {
			return true;
		}
		
		return false;
	}

	/**
	 * Check if WPML is activated 
	 *
	 * @access public
	 * @since 1.0.0
	 * @return void
	 */

	public function is_wpml_activated() {
		$blog_plugins = get_option( 'active_plugins', array() );
		$site_plugins = is_multisite() ? (array) maybe_unserialize( get_site_option('active_sitewide_plugins' ) ) : array();

		if ( in_array( 'sitepress-multilingual-cms/sitepress.php', $blog_plugins ) || isset( $site_plugins['sitepress-multilingual-cms/sitepress.php'] ) ) {
			return true;
		}

		return false;
	}

	/**
	 * Check if recommended plugin is activated or not
	 *
	 * @access public
	 * @since 1.0.0
	 * @return void
	 */
	public function is_recommended_plugin_activated() {

		$knawat_drp_plugin 		 	= 'https://wordpress.org/plugins/dropshipping-woocommerce';
		$woo_plugin 	 			= 'https://wordpress.org/plugins/woocommerce';
		$woo_multi_plugin 			= 'https://wordpress.org/plugins/woocommerce-multilingual';
		

		if ( !is_plugin_active('dropshipping-woocommerce/dropshipping-woocommerce.php') || !is_plugin_active('woocommerce-multilingual/wpml-woocommerce.php') || !is_plugin_active('woocommerce/woocommerce.php') ) {
			?>
				<div class="notice notice-error">
					<p><?php _e( 'Knawat WooCommerce DropShipping WPML Addon needs <a href="'.$knawat_drp_plugin.'" target="_blank">Knawat WooCommerce DropShipping</a>, <a href="'.$woo_plugin.'" target="_blank"> WooCommerce </a> and <a href="'.$woo_multi_plugin.'" target="_blank">WooCommerce Multilingual</a> plugins installed and activated.' );?></p>
				</div>
			<?php 

		}
	}

}
endif;

/**
 * The main function for that returns dropshipping_woocommerce_wpml_addon
 *
 * The main function responsible for returning the one true dropshipping_woocommerce_wpml_addon
 * Instance to functions everywhere.
 *
 * Use this function like you would a global variable, except without needing
 * to declare the global.
 *
 * Example: <?php $knawat_dropshipwc_wmpl = run_knawat_dropshipwc_wpml_woocommerce(); ?>
 *
 * @since 1.0.0
 * @return object|dropshipping_woocommerce_wpml_addon The one true dropshipping_woocommerce_wpml_addon Instance.
 */
function run_knawat_dropshipwc_wpml_woocommerce() {
	return dropshipping_woocommerce_wpml_addon::instance();
}

// Get dropshipping_woocommerce_wpml_addon Running.
global $knawatdswc_wmpl_errors, $knawatdswc_wmpl_success, $knawatdswc_wmpl_warnings;
$GLOBALS['knawat_dropshipwc_wmpl'] = run_knawat_dropshipwc_wpml_woocommerce();
$knawatdswc_wmpl_errors = $knawatdswc_wmpl_success = $knawatdswc_wmpl_warnings = array();
