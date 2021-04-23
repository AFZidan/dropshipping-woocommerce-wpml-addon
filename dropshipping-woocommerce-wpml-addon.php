<?php
/**
 * Plugin Name:       Knawat WooCommerce DropShipping WPML Addon
 * Plugin URI:        https://wordpress.org/plugins/dropshipping-woocommerce-wpml-addon
 * Description:       Knawat WooCommerce DropShipping WPML Addon
 * Version:           1.0.0
 * Author:            Knawat
 * Author URI:        https://www.knawat.com/?utm_source=wordpress.org&utm_medium=social&utm_campaign=The%20WC%20Plugin
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       dropshipping-woocommerce-wpml-addon
 * Domain Path:       /languages
 * WC requires at least: 3.3.0
 * WC tested up to: 4.8.0
 *
 * @package     Dropshipping_Woocommerce_WPML_Addon
 */


// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'Dropshipping_Woocommerce_WPML_Addon' ) ) :

	class Dropshipping_Woocommerce_WPML_Addon {

		/** Singleton *************************************************************/
		/**
		 * Dropshipping_Woocommerce_WPML_Addon The one true Dropshipping_Woocommerce_WPML_Addon.
		 */
		private static $instance;

		/**
		 * Main Knawat WooCommerce DropShipping WPML Support Instance.
		 *
		 * Insure that only one instance of Dropshipping_Woocommerce_WPML_Addon exists in memory at any one time.
		 * Also prevents needing to define globals all over the place.
		 *
		 * @since 1.0.0
		 * @static object $instance
		 * @uses Dropshipping_Woocommerce_WPML_Addon::setup_constants() Setup the constants needed.
		 * @uses Dropshipping_Woocommerce_WPML_Addon::init_includes() Include the required files.
		 * @uses Dropshipping_Woocommerce_WPML_Addon::laod_textdomain() load the language files.
		 * @see run_knawat_dropshipwc_wpml_woocommerce()
		 * @return object| Knawat WooCommerce DropShipping WPML Support the one true Knawat WooCommerce DropShipping WPML Support.
		 */
		public static function instance() {
			if ( ! isset( self::$instance ) && ! ( self::$instance instanceof Dropshipping_Woocommerce_WPML_Addon ) ) {
				self::$instance = new Dropshipping_Woocommerce_WPML_Addon();
				self::$instance->setup_constants();

				add_action( 'plugins_loaded', array( self::$instance, 'load_textdomain' ) );
				add_action( 'init', array( self::$instance, 'init_includes' ) );
			}
			return self::$instance;

		}

		/** Magic Methods *********************************************************/

		/**
		 * A dummy constructor to prevent Knawat_Dropshipping_Woocommerce from being loaded more than once.
		 *
		 * @since 1.0.0
		 * @see Dropshipping_Woocommerce_WPML_Addon::instance()
		 * @see run_knawat_dropshipwc_wpml_woocommerce()
		 */
		private function __construct() {
			add_action( 'admin_notices', array( $this, 'is_recommended_plugin_activated' ) );
		}

		/**
		 * A dummy magic method to prevent Dropshipping_Woocommerce_WPML_Addon from being cloned.
		 *
		 * @since 1.0.0
		 */
		public function __clone() {
			_doing_it_wrong( __FUNCTION__, __( 'Cheatin&#8217; huh?', 'dropshipping-woocommerce-wpml-addon' ), '1.0.0' ); }

		/**
		 * A dummy magic method to prevent Dropshipping_Woocommerce_WPML_Addon from being unserialized.
		 *
		 * @since 1.0.0
		 */
		public function __wakeup() {
			_doing_it_wrong( __FUNCTION__, __( 'Cheatin&#8217; huh?', 'dropshipping-woocommerce-wpml-addon' ), '1.0.0' ); }

		private function setup_constants() {

			// Plugin version.
			if ( ! defined( 'KNAWAT_DROPWC_WPML_VERSION' ) ) {
				define( 'KNAWAT_DROPWC_WPML_VERSION', '1.0.0' );
			}

			// Plugin folder Path.
			if ( ! defined( 'KNAWAT_DROPWC_PLUGIN_WPML_DIR' ) ) {
				define( 'KNAWAT_DROPWC_PLUGIN_WPML_DIR', plugin_dir_path( __FILE__ ) );
			}

			// Plugin folder URL.
			if ( ! defined( 'KNAWAT_DROPWC_PLUGIN_WPML_URL' ) ) {
				define( 'KNAWAT_DROPWC_PLUGIN_WPML_URL', plugin_dir_url( __FILE__ ) );
			}

			// Plugin root file.
			if ( ! defined( 'KNAWAT_DROPWC_PLUGIN_WPML_FILE' ) ) {
				define( 'KNAWAT_DROPWC_PLUGIN_WPML_FILE', __FILE__ );
			}

			// Options
			if ( ! defined( 'KNAWAT_DROPWC_WPML_OPTIONS' ) ) {
				define( 'KNAWAT_DROPWC_WPML_OPTIONS', 'knawat_dropshipwc_wpml_options' );
			}

		}

		/**
		 * Include required files on init.
		 *
		 * @access public
		 * @since 1.0.0
		 * @return void
		 */
		public function init_includes() {
			if ( $this->is_woocommerce_activated() && $this->is_wpml_activated() && $this->is_woo_multilingual_activated() ) {
				require_once KNAWAT_DROPWC_PLUGIN_WPML_DIR . 'includes/class-dropshipping-woocommerce-wpml-importer.php';
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
				'dropshipping_wpml_woocommerce',
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
			$site_plugins = is_multisite() ? (array) maybe_unserialize( get_site_option( 'active_sitewide_plugins' ) ) : array();

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
			$site_plugins = is_multisite() ? (array) maybe_unserialize( get_site_option( 'active_sitewide_plugins' ) ) : array();

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
			$site_plugins = is_multisite() ? (array) maybe_unserialize( get_site_option( 'active_sitewide_plugins' ) ) : array();

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
			$site_plugins = is_multisite() ? (array) maybe_unserialize( get_site_option( 'active_sitewide_plugins' ) ) : array();

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

			$msg_string[] = ( ! $this->is_dropshipping_activated() ? '<a href="' . admin_url( 'plugin-install.php?tab=plugin-information&plugin=dropshipping-woocommerce&TB_iframe=true&width=600&height=550' ) . '" class="thickbox open-plugin-details-modal" > Knawat WooCommerce DropShipping</a>' : '' );

			$msg_string[] = ( ! $this->is_woocommerce_activated() ? '<a href="' . admin_url( 'plugin-install.php?tab=plugin-information&plugin=woocommerce&TB_iframe=true&width=600&height=550' ) . '" class="thickbox open-plugin-details-modal"> WooCommerce</a>' : '' );

			$msg_string[] = ( ! $this->is_woo_multilingual_activated() ? '<a href="' . admin_url( 'plugin-install.php?tab=plugin-information&plugin=woocommerce-multilingual&TB_iframe=true&width=600&height=550' ) . '" class="thickbox open-plugin-details-modal"> WooCommerce Multilingual</a>' : '' );

			$msg_string   = array_filter( $msg_string );
			$plugin_count = count( $msg_string );

			if ( $plugin_count != 0 ) {
				echo sprintf( '<div class="notice notice-error"><p>Knawat WooCommerce DropShipping WPML Addon needs %s plugins installed and activated</p></div>', implode( ',', $msg_string ) );
			}
		}

	}
endif;

/**
 * The main function for that returns Dropshipping_Woocommerce_WPML_Addon
 *
 * The main function responsible for returning the one true Dropshipping_Woocommerce_WPML_Addon
 * Instance to functions everywhere.
 *
 * Use this function like you would a global variable, except without needing
 * to declare the global.
 *
 * Example: <?php $knawat_dropshipwc_wpml = run_knawat_dropshipwc_wpml_woocommerce(); ?>
 *
 * @since 1.0.0
 * @return object|Dropshipping_Woocommerce_WPML_Addon The one true Dropshipping_Woocommerce_WPML_Addon Instance.
 */
function run_knawat_dropshipwc_wpml_woocommerce() {
	return Dropshipping_Woocommerce_WPML_Addon::instance();
}

// Get Dropshipping_Woocommerce_WPML_Addon Running.
$GLOBALS['knawat_dropshipwc_wpml'] = run_knawat_dropshipwc_wpml_woocommerce();

