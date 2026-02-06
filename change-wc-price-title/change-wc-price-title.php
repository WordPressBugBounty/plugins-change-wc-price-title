<?php 
/**
 * Plugin Name:          Change Price Title for WooCommerce
 * Plugin URI:           https://woocommerce.com/products/change-wc-price-title
 * Description:          Easily change WooCommerce price title, e.g. 'From: $100/- Only', or hide them site-wide.
 * Version:              2.8
 * Author:               Kartechify
 * Author URI:           https://woocommerce.com/vendor/kartechify/
 * Text Domain:          change-wc-price-title
 * Domain Path:          /i18n/languages/
 * Requires PHP:         7.3
 * Tested up to:         6.9
 * WC requires at least: 3.0.0
 * WC tested up to:      10.4.3
 * License:              GPL v2 or later
 * Requires Plugins:     woocommerce
 *
 * @package Change_WooCommerce_Price_Title
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Automattic\WooCommerce\Utilities\OrderUtil;

define( 'CWPT_VERSION', '2.8' );

if ( ! class_exists( 'CWPT_Price' ) ) {

	/**
	 * CWPT_price class
	 */
	class CWPT_Price { // phpcs:ignore

		/**
		 * CWPT_price Constructor
		 *
		 * @since 1.0
		 */
		public function __construct() {

			add_action( 'admin_init', array( &$this, 'cwpt_check_compatibility' ) );
			add_action( 'admin_init', array( &$this, 'cwpt_check_migration' ) );
			add_action( 'admin_notices', array( &$this, 'cwpt_migration_notice' ) );
			add_action( 'wp_ajax_cwpt_dismiss_migration_notice', array( &$this, 'cwpt_dismiss_migration_notice' ) );

			add_action( 'woocommerce_product_options_advanced', array( &$this, 'cwpt_adding_set_price_title_field' ), 10, 1 );
			add_action( 'woocommerce_process_product_meta', array( &$this, 'cwpt_woocommerce_process_product_meta_simple' ), 10, 1 );
			add_filter( 'woocommerce_get_price_html', array( &$this, 'cwpt_change_woocommerce_price_title' ), 99, 2 );

			register_activation_hook( __FILE__, array( &$this, 'cwpt_price_activate' ) ); // Initialize settings.

			// Add settings to WooCommerce Settings.
			add_filter( 'woocommerce_get_settings_pages', array( &$this, 'cwpt_add_settings_page' ) );

			// Including styles and scripts.
			add_action( 'wp_enqueue_scripts', array( &$this, 'cwpt_add_scripts' ) );
			// Settings link on plugins page.
			add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), array( &$this, 'cwpt_plugin_settings_link' ) );

			add_action( 'before_woocommerce_init', array( &$this, 'cwpt_custom_order_tables_compatibility' ), 999 );

			add_action( 'admin_enqueue_scripts', array( $this, 'cwpt_enqueue_admin_scripts' ) );

		}

		public function cwpt_enqueue_admin_scripts( $hook ) {

			// Load only in admin.
			if ( ! is_admin() ) {
				return;
			}

			if ( '1' === get_option( 'cwpt_show_migration_notice', '0' ) ) {
				return;
			}

			wp_enqueue_script(
				'cwpt-admin-notice',
				plugin_dir_url( __FILE__ ) . 'assets/js/cwpt-admin-notice.js',
				array( 'jquery' ),
				CWPT_VERSION,
				true
			);

			wp_localize_script(
				'cwpt-admin-notice',
				'cwptNotice',
				array(
					'ajaxUrl' => admin_url( 'admin-ajax.php' ),
					'nonce'   => wp_create_nonce( 'cwpt_dismiss_notice' ),
				)
			);
		}

		/**
		 * Add custom settings page to WooCommerce Settings.
		 *
		 * @param array $settings Existing settings pages.
		 * @return array Modified settings pages.
		 * @since 2.8
		 */
		public function cwpt_add_settings_page( $settings ) {
			$settings[] = include dirname( __FILE__ ) . '/includes/class-cwpt-settings.php';
			return $settings;
		}

		/**
		 * HPOS Compatibility.
		 *
		 * @since 2.2
		 */
		public function cwpt_custom_order_tables_compatibility() {
			if ( class_exists( '\Automattic\WooCommerce\Utilities\FeaturesUtil' ) ) {
				\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', __FILE__, true );
				\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'orders_cache', __FILE__, true );
				\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'product_block_editor', __FILE__, true );
				\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'cart_checkout_blocks', __FILE__, true );
			}
		}

		/**
		 * Including script.
		 *
		 * @since 1.0
		 */
		public function cwpt_add_scripts() {

			global $post;

			if ( is_null( $post ) ) {
				return;
			}

			$post_id = $post->ID;

			if ( 'product' === get_post_type( $post_id ) ) {
				$cwpt_enable_multiplier = get_option( 'cwpt_enable_multiplier', '' );
				if ( 'yes' !== $cwpt_enable_multiplier && '1' !== $cwpt_enable_multiplier ) {
					return;
				}

				$ajax_url        = get_admin_url() . 'admin-ajax.php';
				$_product        = wc_get_product( $post_id );
				$price           = $_product->get_price();
				$product_type    = $_product->get_type();
				$cur_symbol      = get_woocommerce_currency_symbol();
				$variation_price = array();

				if ( 'variable' === $product_type ) {

					foreach ( $_product->get_available_variations() as $variation ) {
						// Variation ID.
						$variation_id  = $variation['variation_id'];
						$active_price  = floatval( $variation['display_price'] ); // Active price.
						$regular_price = floatval( $variation['display_regular_price'] ); // Regular Price.

						if ( $active_price != $regular_price ) {
							$sale_price                       = $active_price; // Sale Price.
							$variation_price[ $variation_id ] = $sale_price;
						} else {
							$variation_price[ $variation_id ] = $regular_price;
						}
					}

					$price = $variation_price;
				}

				$cwpt_plugin_version_number = get_option( 'change_woocommerce_price_title_db_version' );
				$cwpt_enable_multiplier     = get_option( 'cwpt_enable_multiplier' );

				wp_enqueue_script( 'jquery' );

				wp_deregister_script( 'jqueryui' );

				wp_register_script(
					'cwpt-price-title',
					plugin_dir_url( __FILE__ ) . 'assets/js/cwpt-price-title.js',
					array(),
					$cwpt_plugin_version_number,
					false
				);

				wp_localize_script(
					'cwpt-price-title',
					'cwpt_settings_params',
					array(
						'ajax_url'      => $ajax_url,
						'post_id'       => $post_id,
						'title_color'   => __( 'red', 'change-wc-price-title' ),
						'product_price' => $price,
						'wc_currency'   => $cur_symbol,
						'product_type'  => $product_type,
						'multiplier'    => $cwpt_enable_multiplier,
					)
				);

				wp_enqueue_script( 'cwpt-price-title' );
			}
		}

		/**
		 * Ensure that the plugin is deactivated when WooCommerce is deactivated.
		 *
		 * @since 1.0
		 */
		public static function cwpt_check_compatibility() {

			if ( ! self::cwpt_check_woo_installed() ) {

				if ( is_plugin_active( plugin_basename( __FILE__ ) ) ) {
					deactivate_plugins( plugin_basename( __FILE__ ) );

					add_action( 'admin_notices', array( 'CWPT_Price', 'cwpt_disabled_notice' ) );
					if ( isset( $_GET['activate'] ) ) { // phpcs:ignore
						unset( $_GET['activate'] );
					}
				}
			}
		}

		/**
		 * Check if migration is needed for existing users
		 *
		 * @since 2.8
		 */
		public function cwpt_check_migration() {
			$current_version = get_option( 'change_woocommerce_price_title_db_version', '0' );

			// Only run migration once
			if ( version_compare( $current_version, '2.8', '<' ) ) {
				// Check if this is an existing user (has any settings saved)
				$has_existing_settings = false;
				$checkboxes = array(
					'cwpt_woocommerce_hide_price_title',
					'cwpt_apply_on_all_products',
					'cwpt_enable_multiplier',
				);

				foreach ( $checkboxes as $checkbox ) {
					if ( get_option( $checkbox, false ) !== false ) {
						$has_existing_settings = true;
						break;
					}
				}

				// Also check if price title text is set
				if ( get_option( 'cwpt_woocommerce_price_title', '' ) !== '' ) {
					$has_existing_settings = true;
				}

				// If existing user, set flag to show migration notice
				if ( $has_existing_settings ) {
					update_option( 'cwpt_show_migration_notice', '1' );
				}
				
				$this->cwpt_migrate_checkbox_values();
				update_option( 'change_woocommerce_price_title_db_version', '2.8' );
			}
		}

		/**
		 * Display migration notice for existing users
		 *
		 * @since 2.8
		 */
		public function cwpt_migration_notice() {

			// Check if user has permission to see this notice
			if ( ! current_user_can( 'manage_woocommerce' ) ) {
				return;
			}

			// Check if notice should be shown
			$show_notice = get_option( 'cwpt_show_migration_notice', '0' );

			if ( '1' === $show_notice ) {
				return;
			}

			$settings_url = admin_url( 'admin.php?page=wc-settings&tab=price_title' );
			?>
			<div class="notice notice-info is-dismissible" id="cwpt-migration-notice">
				<p>
					<strong><?php esc_html_e( 'Change Price Title for WooCommerce', 'change-wc-price-title' ); ?></strong>
				</p>
				<p>
					<?php
					printf(
						/* translators: %s: Settings page URL */
						esc_html__( 'The plugin settings are now available under %s.', 'change-wc-price-title' ),
						'<a href="' . esc_url( $settings_url ) . '"><strong>' . esc_html__( 'WooCommerce → Settings → Price Title', 'change-wc-price-title' ) . '</strong></a>'
					);
					?>
				</p>
			</div>
			<?php
		}

		/**
		 * AJAX handler to dismiss migration notice
		 *
		 * @since 2.8
		 */
		public function cwpt_dismiss_migration_notice() {

			check_ajax_referer( 'cwpt_dismiss_notice', 'nonce' );

			if ( ! current_user_can( 'manage_woocommerce' ) ) {
				wp_send_json_error();
			}

			update_option( 'cwpt_show_migration_notice', '1' );

			wp_send_json_success();
		}

		/**
		 * Check if WooCommerce is active.
		 */
		public static function cwpt_check_woo_installed() {

			if ( class_exists( 'WooCommerce' ) ) {
				return true;
			} else {
				return false;
			}
		}

		/**
		 * Display a notice in the admin Plugins page if this plugin is
		 * activated while WooCommerce is deactivated.
		 */
		public static function cwpt_disabled_notice() {

			$class   = 'notice notice-error';
			$message = __( 'Change Price Title for WooCommerce requires WooCommerce to be installed and activated.', 'change-wc-price-title' );

			printf(
				'<div class="%1$s"><p>%2$s</p></div>',
				esc_attr( $class ),
				esc_html( $message )
			);
		}

		/**
		 * Version Saving and Migration
		 *
		 * @since 1.0
		 */
		public function cwpt_price_activate() {
			// Activation code here.
			$current_version = get_option( 'change_woocommerce_price_title_db_version', '0' );

			// Migrate checkbox values from '1'/'0' to 'yes'/'no' for WooCommerce compatibility.
			if ( version_compare( $current_version, '2.8', '<' ) ) {
				$this->cwpt_migrate_checkbox_values();
			}

			update_option( 'change_woocommerce_price_title_db_version', '2.8' );
		}

		/**
		 * Migrate checkbox values from old format to WooCommerce format
		 *
		 * @since 2.8
		 */
		private function cwpt_migrate_checkbox_values() {
			$checkboxes = array(
				'cwpt_woocommerce_hide_price_title',
				'cwpt_apply_on_all_products',
				'cwpt_enable_multiplier',
			);

			foreach ( $checkboxes as $checkbox ) {
				$value = get_option( $checkbox, '' );
				
				// Convert '1' to 'yes' and anything else to 'no'
				if ( '1' === $value || 'yes' === $value ) {
					update_option( $checkbox, 'yes' );
				} else {
					update_option( $checkbox, 'no' );
				}
			}
		}

		/**
		 * Adding fields in the Advanced tab of Product data meta box.
		 *
		 * @since 1.0
		 */
		public function cwpt_adding_set_price_title_field() {

			global $post;

			$product_obj = wc_get_product( $post->ID );

			// Set Price Title Input in Product data metabox.
			woocommerce_wp_text_input(
				array(
					'id'          => '_cwpt_price_title',
					'label'       => __( 'Set price title', 'change-wc-price-title' ),
					'desc_tip'    => true,
					'description' => __( 'Here you can set the text for WooCommerce Price title as per your requirement. Use PRICE shortcode to include original price in title. E.g From PRICE', 'change-wc-price-title' ),
				)
			);

			// Hide Title Checkbox in Product data meta box.
			woocommerce_wp_checkbox(
				array(
					'id'          => '_cwpt_hide_price',
					'label'       => __( 'Hide Price?', 'change-wc-price-title' ),
					'desc_tip'    => true,
					'description' => __( 'Hide Price WooCommerce Product Page', 'change-wc-price-title' ),
				)
			);

			// Applicable on all WooCommerce Pages checkbox in Product data metabox.
			woocommerce_wp_checkbox(
				array(
					'id'          => '_cwpt_apply_on_all_wc_pages',
					'label'       => __( 'Applicable on all WooCommerce Pages', 'change-wc-price-title' ),
					'desc_tip'    => true,
					'description' => __( 'Enable to apply the set and hide price option to all WooCommerce pages.', 'change-wc-price-title' ),
				)
			);
		}

		/**
		 * Saving options in database.
		 *
		 * @param int $product_id Product ID.
		 * @since 1.0
		 */
		public function cwpt_woocommerce_process_product_meta_simple( $product_id ) {

			$cwpt_hide_price            = 'no';
			$cwpt_apply_on_all_wc_pages = 'no';

			if ( isset( $_POST['_cwpt_price_title'] ) ) { // phpcs:ignore
				update_post_meta( $product_id, '_cwpt_price_title', wp_kses_post( wp_unslash( $_POST['_cwpt_price_title'] ) ) ); // phpcs:ignore
			}

			if ( isset( $_POST['_cwpt_hide_price'] ) && 'yes' === $_POST['_cwpt_hide_price'] ) { // phpcs:ignore
				$cwpt_hide_price = 'yes';
			}
			update_post_meta( $product_id, '_cwpt_hide_price', $cwpt_hide_price );

			if ( isset( $_POST['_cwpt_apply_on_all_wc_pages'] ) && 'yes' === $_POST['_cwpt_apply_on_all_wc_pages'] ) { // phpcs:ignore
				$cwpt_apply_on_all_wc_pages = 'yes';
			}
			update_post_meta( $product_id, '_cwpt_apply_on_all_wc_pages', $cwpt_apply_on_all_wc_pages );
		}

		/**
		 * Applying selected options on WooCommerce price title.
		 *
		 * @param string $price Price.
		 * @param obj    $product_obj Price.
		 *
		 * @since 1.0
		 */
		public function cwpt_change_woocommerce_price_title( $price, $product_obj ) {

			// Getting product id from the product object.
			$product_id = $product_obj->get_id();

			// Getting option for applicable on all WooCommerce Pages.
			$cwpt_apply_on_all_wc_pages_value = get_post_meta( $product_id, '_cwpt_apply_on_all_wc_pages', true );
			$cwpt_apply_on_all_wc_pages       = ( isset( $cwpt_apply_on_all_wc_pages_value ) && '' !== $cwpt_apply_on_all_wc_pages_value ) ? $cwpt_apply_on_all_wc_pages_value : 'no';

			// Getting value of Apply on all wc pages from Global Level.
			$cwpt_apply_on_all_products_value = get_option( 'cwpt_apply_on_all_products' );
			if ( ! is_product() && ( '' === $cwpt_apply_on_all_wc_pages || 'no' === $cwpt_apply_on_all_wc_pages ) ) {
				if ( 'yes' !== $cwpt_apply_on_all_products_value && '1' !== $cwpt_apply_on_all_products_value ) {
					return $price;
				}
			}

			$original_price = $price;

			// Getting product id from the product object.
			$product_id = $product_obj->get_id();

			// Getting value of WooCommerce Hide Price from Global Level.
			$global_hide_price = get_option( 'cwpt_woocommerce_hide_price_title' );

			// If Hide Price is enabled then hide all product's prices from WooCommerce Product Page.
			if ( 'yes' === $global_hide_price || '1' === $global_hide_price ) {
				$price = '';
				return $price;
			}

			// Getting option for hide price at product level.
			$product_hide_price = get_post_meta( $product_id, '_cwpt_hide_price', true );

			// If Hide Price is enabled then hide all product's prices from WooCommerce Product Page.
			if ( 'yes' === $product_hide_price ) {
				$price = '';
				return $price;
			}

			// Getting Price title at Product Level.
			$cwpt_price = get_post_meta( $product_id, '_cwpt_price_title', true );

			// Getting Price title at Global Level.
			$global_level_set_title = get_option( 'cwpt_woocommerce_price_title' );

			// Setting $price to the text as per the set text in Set price title field at global level.
			if ( isset( $global_level_set_title ) && '' !== $global_level_set_title ) {

				if ( strpos( $global_level_set_title, 'PRICE' ) !== false ) {
					$price = str_replace( 'PRICE', $original_price, $global_level_set_title );
				} else {
					$price = $global_level_set_title;
				}
			}

			// Setting $price to the text as per the set text in Set price title field at product level.
			if ( isset( $cwpt_price ) && '' !== $cwpt_price ) {
				if ( strpos( $cwpt_price, 'PRICE' ) !== false ) {
					$price = str_replace( 'PRICE', $original_price, $cwpt_price );
				} else {
					$price = $cwpt_price;
				}
			}

			return wp_kses_post( $price );
		}

		/**
		 * Settings link on Plugins page
		 *
		 * @param array $links Exisiting Links present on Plugins information section.
		 *
		 * @return array Modified array containing the settings link added
		 *
		 * @since 1.4
		 */
		public function cwpt_plugin_settings_link( $links ) {

			$settings_text            = __( 'Settings', 'change-wc-price-title' );
			$setting_link['settings'] = '<a href="' . esc_url( admin_url( 'admin.php?page=wc-settings&tab=price_title' ) ) . '">' . $settings_text . '</a>';
			$links                    = $setting_link + $links;
			return $links;
		}
	}
	$cwpt_price = new CWPT_Price();
}
