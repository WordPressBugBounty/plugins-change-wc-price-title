<?php
/**
 * Plugin Name:          Change Price Title for WooCommerce
 * Plugin URI:           https://woocommerce.com/products/change-wc-price-title
 * Description:          Easily change WooCommerce price title, e.g. 'From: $100/- Only', or hide them site-wide.
 * Version:              3.1
 * Author:               Kartechify
 * Author URI:           https://woocommerce.com/vendor/kartechify/
 * Text Domain:          change-wc-price-title
 * Domain Path:          /i18n/languages/
 * Requires at least:    5.3
 * Requires PHP:         7.4
 * Tested up to:         7.1
 * WC requires at least: 3.0.0
 * WC tested up to:      11.2.1
 * License:              GPL v2 or later
 * Requires Plugins:     woocommerce
 *
 * @package Change_WooCommerce_Price_Title
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'CWPT_VERSION', '3.1' );

if ( ! class_exists( 'CWPT_Price' ) ) {

	/**
	 * CWPT_price class
	 */
	class CWPT_Price { // phpcs:ignore

		/**
		 * Parent products loaded for variations, keyed by parent ID.
		 *
		 * @var WC_Product[]
		 */
		private $parent_products = array();

		/**
		 * CWPT_price Constructor
		 *
		 * @since 1.0
		 */
		public function __construct() {

			register_activation_hook( __FILE__, array( $this, 'cwpt_price_activate' ) ); // Initialize settings.

			add_action( 'before_woocommerce_init', array( $this, 'cwpt_custom_order_tables_compatibility' ), 999 );

			// WooCommerce loads after this plugin, so wait until all plugins are loaded before checking for it.
			add_action( 'plugins_loaded', array( $this, 'cwpt_init' ) );

			add_action( 'init', array( $this, 'cwpt_load_textdomain' ) );
		}

		/**
		 * Load the bundled translations from i18n/languages.
		 *
		 * @since 3.1
		 */
		public function cwpt_load_textdomain() {
			load_plugin_textdomain( 'change-wc-price-title', false, dirname( plugin_basename( __FILE__ ) ) . '/i18n/languages' );
		}

		/**
		 * Register the plugin hooks once WooCommerce is available.
		 *
		 * @since 3.1
		 */
		public function cwpt_init() {

			if ( ! class_exists( 'WooCommerce' ) ) {
				add_action( 'admin_notices', array( $this, 'cwpt_disabled_notice' ) );
				return;
			}

			add_action( 'admin_init', array( $this, 'cwpt_check_migration' ) );
			add_action( 'admin_notices', array( $this, 'cwpt_migration_notice' ) );
			add_action( 'wp_ajax_cwpt_dismiss_migration_notice', array( $this, 'cwpt_dismiss_migration_notice' ) );

			add_action( 'woocommerce_product_options_advanced', array( $this, 'cwpt_adding_set_price_title_field' ) );
			add_action( 'woocommerce_admin_process_product_object', array( $this, 'cwpt_save_product_settings' ) );
			add_filter( 'woocommerce_get_price_html', array( $this, 'cwpt_change_woocommerce_price_title' ), 99, 2 );

			// Add settings to WooCommerce Settings.
			add_filter( 'woocommerce_get_settings_pages', array( $this, 'cwpt_add_settings_page' ) );

			// Including styles and scripts.
			add_action( 'wp_enqueue_scripts', array( $this, 'cwpt_add_scripts' ) );
			add_action( 'admin_enqueue_scripts', array( $this, 'cwpt_enqueue_admin_scripts' ) );

			// Settings link on plugins page.
			add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), array( $this, 'cwpt_plugin_settings_link' ) );
		}

		/**
		 * Including admin scripts and styles.
		 *
		 * @param string $hook Current admin page hook.
		 * @since 2.8
		 */
		public function cwpt_enqueue_admin_scripts( $hook ) {

			// Styles for the Upgrade to Pro box on the plugin's settings tab.
			if ( 'woocommerce_page_wc-settings' === $hook && isset( $_GET['tab'] ) && 'price_title' === sanitize_key( wp_unslash( $_GET['tab'] ) ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
				wp_enqueue_style( 'cwpt-admin', plugin_dir_url( __FILE__ ) . 'assets/css/cwpt-admin.css', array(), CWPT_VERSION );
			}

			// Load only when the migration notice is going to be displayed.
			if ( ! $this->cwpt_should_show_migration_notice() ) {
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
			// The filter can run more than once, so load the class once and reuse a single instance.
			static $settings_page = null;

			if ( null === $settings_page ) {
				require_once dirname( __FILE__ ) . '/includes/class-cwpt-settings.php';

				if ( ! class_exists( 'CWPT_WC_Settings' ) ) {
					return $settings;
				}

				$settings_page = new CWPT_WC_Settings();
			}

			$settings[] = $settings_page;
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
				// Product fields are only added to the classic product editor.
				\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'product_block_editor', __FILE__, false );
				\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'cart_checkout_blocks', __FILE__, true );
			}
		}

		/**
		 * Including script.
		 *
		 * @since 1.0
		 */
		public function cwpt_add_scripts() {

			if ( ! is_product() || ! $this->cwpt_is_option_enabled( 'cwpt_enable_multiplier' ) ) {
				return;
			}

			$_product = wc_get_product( get_queried_object_id() );

			if ( ! $_product || ! $_product->is_type( array( 'simple', 'variable' ) ) ) {
				return;
			}

			// Never expose the price on the frontend when it is hidden.
			if ( $this->cwpt_is_price_hidden( $_product ) ) {
				return;
			}

			wp_enqueue_script(
				'cwpt-price-title',
				plugin_dir_url( __FILE__ ) . 'assets/js/cwpt-price-title.js',
				array( 'jquery' ),
				CWPT_VERSION,
				true
			);

			wp_localize_script(
				'cwpt-price-title',
				'cwpt_settings_params',
				array(
					// Variable products get the price from the selected variation in JS.
					'product_price'   => $_product->is_type( 'simple' ) ? wc_get_price_to_display( $_product ) : '',
					'product_type'    => $_product->get_type(),
					'currency_symbol' => html_entity_decode( get_woocommerce_currency_symbol(), ENT_QUOTES, 'UTF-8' ),
					'currency_pos'    => get_option( 'woocommerce_currency_pos', 'left' ),
					'decimals'        => wc_get_price_decimals(),
					'decimal_sep'     => wc_get_price_decimal_separator(),
					'thousand_sep'    => wc_get_price_thousand_separator(),
					/* translators: 1: unit price, 2: quantity, 3: total price */
					'total_format'    => __( '%1$s x %2$s = %3$s', 'change-wc-price-title' ),
				)
			);
		}

		/**
		 * Check if migration is needed for existing users
		 *
		 * @since 2.8
		 */
		public function cwpt_check_migration() {
			$current_version = get_option( 'change_woocommerce_price_title_db_version', '0' );

			// Only run migration once.
			if ( version_compare( $current_version, '2.8', '<' ) ) {
				// Check if this is an existing user (has any settings saved).
				$has_existing_settings = '' !== get_option( 'cwpt_woocommerce_price_title', '' );

				foreach ( $this->cwpt_get_checkbox_options() as $checkbox ) {
					if ( false !== get_option( $checkbox, false ) ) {
						$has_existing_settings = true;
						break;
					}
				}

				// If existing user, set flag to show migration notice.
				if ( $has_existing_settings ) {
					update_option( 'cwpt_show_migration_notice', 'yes' );
				}

				$this->cwpt_migrate_checkbox_values();
				update_option( 'change_woocommerce_price_title_db_version', CWPT_VERSION );
			}
		}

		/**
		 * Display migration notice for existing users
		 *
		 * @since 2.8
		 */
		public function cwpt_migration_notice() {

			if ( ! $this->cwpt_should_show_migration_notice() ) {
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

			update_option( 'cwpt_show_migration_notice', 'no' );

			wp_send_json_success();
		}

		/**
		 * Whether the migration notice should be displayed to the current user.
		 *
		 * The notice is only flagged ('yes') for existing users during migration
		 * and is turned off ('no') once dismissed.
		 *
		 * @return bool
		 * @since 3.1
		 */
		private function cwpt_should_show_migration_notice() {
			return current_user_can( 'manage_woocommerce' ) && 'yes' === get_option( 'cwpt_show_migration_notice', 'no' );
		}

		/**
		 * Display a notice in the admin if this plugin is active while WooCommerce is not.
		 *
		 * @since 1.0
		 */
		public function cwpt_disabled_notice() {

			if ( ! current_user_can( 'activate_plugins' ) ) {
				return;
			}

			printf(
				'<div class="notice notice-error"><p>%s</p></div>',
				esc_html__( 'Change Price Title for WooCommerce requires WooCommerce to be installed and activated.', 'change-wc-price-title' )
			);
		}

		/**
		 * Version Saving and Migration
		 *
		 * @since 1.0
		 */
		public function cwpt_price_activate() {
			$current_version = get_option( 'change_woocommerce_price_title_db_version', '0' );

			// Migrate checkbox values from '1'/'0' to 'yes'/'no' for WooCommerce compatibility.
			if ( version_compare( $current_version, '2.8', '<' ) ) {
				$this->cwpt_migrate_checkbox_values();
			}

			update_option( 'change_woocommerce_price_title_db_version', CWPT_VERSION );
		}

		/**
		 * Global checkbox options of the plugin.
		 *
		 * @return string[]
		 * @since 3.1
		 */
		private function cwpt_get_checkbox_options() {
			return array(
				'cwpt_woocommerce_hide_price_title',
				'cwpt_apply_on_all_products',
				'cwpt_enable_multiplier',
			);
		}

		/**
		 * Migrate checkbox values from old format to WooCommerce format
		 *
		 * @since 2.8
		 */
		private function cwpt_migrate_checkbox_values() {
			foreach ( $this->cwpt_get_checkbox_options() as $checkbox ) {
				// Convert '1' to 'yes' and anything else to 'no'.
				update_option( $checkbox, $this->cwpt_is_option_enabled( $checkbox ) ? 'yes' : 'no' );
			}
		}

		/**
		 * Whether a global checkbox option is enabled. Accepts both the
		 * current 'yes'/'no' and the legacy '1'/'0' values. Does not rely on
		 * WooCommerce functions as it also runs on activation.
		 *
		 * @param string $option Option name.
		 * @return bool
		 * @since 3.1
		 */
		private function cwpt_is_option_enabled( $option ) {
			return in_array( strtolower( (string) get_option( $option, 'no' ) ), array( 'yes', '1', 'true' ), true );
		}

		/**
		 * Adding fields in the Advanced tab of Product data meta box.
		 *
		 * @since 1.0
		 */
		public function cwpt_adding_set_price_title_field() {

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
		 * Saving product level options. WooCommerce saves the product after this hook
		 * and has already verified the nonce and capability.
		 *
		 * @param WC_Product $product Product being saved.
		 * @since 3.1
		 */
		public function cwpt_save_product_settings( $product ) {
			// phpcs:disable WordPress.Security.NonceVerification.Missing
			if ( isset( $_POST['_cwpt_price_title'] ) ) {
				$product->update_meta_data( '_cwpt_price_title', wp_kses_post( wp_unslash( $_POST['_cwpt_price_title'] ) ) );
			}

			foreach ( array( '_cwpt_hide_price', '_cwpt_apply_on_all_wc_pages' ) as $checkbox ) {
				$enabled = isset( $_POST[ $checkbox ] ) && 'yes' === sanitize_text_field( wp_unslash( $_POST[ $checkbox ] ) );
				$product->update_meta_data( $checkbox, $enabled ? 'yes' : 'no' );
			}
			// phpcs:enable WordPress.Security.NonceVerification.Missing
		}

		/**
		 * Applying selected options on WooCommerce price title.
		 *
		 * @param string     $price       Price HTML.
		 * @param WC_Product $product_obj Product object.
		 * @return string
		 * @since 1.0
		 */
		public function cwpt_change_woocommerce_price_title( $price, $product_obj ) {

			// Variations use the settings saved on their parent product.
			$settings_product = $this->cwpt_get_settings_product( $product_obj );

			// Outside the single product page, only apply when enabled globally or for this product.
			if ( ! $this->cwpt_is_single_product_context( $product_obj )
				&& ! $this->cwpt_is_option_enabled( 'cwpt_apply_on_all_products' )
				&& ! wc_string_to_bool( $settings_product->get_meta( '_cwpt_apply_on_all_wc_pages' ) ) ) {
				return $price;
			}

			$original_price = $price;

			if ( $this->cwpt_is_price_hidden( $settings_product ) ) {
				$price = '';
			} else {
				// Product level title overrides the global one.
				$title = (string) $settings_product->get_meta( '_cwpt_price_title' );

				if ( '' === $title ) {
					$title = (string) get_option( 'cwpt_woocommerce_price_title', '' );
				}

				// PRICE in the title is replaced with the original price.
				if ( '' !== $title ) {
					$price = str_replace( 'PRICE', $original_price, $title );
				}
			}

			/**
			 * Filters the price HTML after the price title settings are applied.
			 *
			 * @param string     $price          Modified price HTML.
			 * @param string     $original_price Price HTML before this plugin changed it.
			 * @param WC_Product $product_obj    Product object.
			 * @since 3.1
			 */
			return apply_filters( 'cwpt_price_title_html', wp_kses_post( $price ), $original_price, $product_obj );
		}

		/**
		 * Get the product whose settings apply to the given product.
		 * Variations do not have their own settings, so the parent's are used.
		 *
		 * @param WC_Product $product_obj Product object.
		 * @return WC_Product
		 * @since 3.1
		 */
		private function cwpt_get_settings_product( $product_obj ) {
			$parent_id = $product_obj->get_parent_id();

			if ( ! $product_obj->is_type( 'variation' ) || ! $parent_id ) {
				return $product_obj;
			}

			if ( ! isset( $this->parent_products[ $parent_id ] ) ) {
				$this->parent_products[ $parent_id ] = wc_get_product( $parent_id );
			}

			return $this->parent_products[ $parent_id ] ? $this->parent_products[ $parent_id ] : $product_obj;
		}

		/**
		 * Whether the price is being rendered for the single product page.
		 * Also covers variation prices loaded via AJAX on that page.
		 *
		 * @param WC_Product $product_obj Product object.
		 * @return bool
		 * @since 3.1
		 */
		private function cwpt_is_single_product_context( $product_obj ) {
			if ( is_product() ) {
				return true;
			}

			return $product_obj->is_type( 'variation' )
				&& isset( $_GET['wc-ajax'] ) // phpcs:ignore WordPress.Security.NonceVerification.Recommended
				&& 'get_variation' === sanitize_key( wp_unslash( $_GET['wc-ajax'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		}

		/**
		 * Whether the price is hidden for a product at global or product level.
		 *
		 * @param WC_Product $settings_product Product holding the settings (parent for variations).
		 * @return bool
		 * @since 3.1
		 */
		private function cwpt_is_price_hidden( $settings_product ) {
			return $this->cwpt_is_option_enabled( 'cwpt_woocommerce_hide_price_title' )
				|| wc_string_to_bool( $settings_product->get_meta( '_cwpt_hide_price' ) );
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

			$setting_link['settings'] = '<a href="' . esc_url( admin_url( 'admin.php?page=wc-settings&tab=price_title' ) ) . '">' . esc_html__( 'Settings', 'change-wc-price-title' ) . '</a>';

			return $setting_link + $links;
		}
	}
	$cwpt_price = new CWPT_Price();
}
