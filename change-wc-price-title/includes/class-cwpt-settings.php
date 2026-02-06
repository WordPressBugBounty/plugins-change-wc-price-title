<?php
/**
 * WooCommerce Settings Integration for Change Price Title
 *
 * @package Change_WooCommerce_Price_Title
 * @since 2.8
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( class_exists( 'WC_Settings_Page' ) ) {

	/**
	 * CWPT Settings Page
	 */
	class CWPT_WC_Settings extends WC_Settings_Page {

		/**
		 * Constructor.
		 */
		public function __construct() {
			$this->id    = 'price_title';
			$this->label = __( 'Price Title', 'change-wc-price-title' );

			parent::__construct();
		}

		/**
		 * Get settings array.
		 *
		 * @return array
		 */
		public function get_settings() {
			$settings = array(
				array(
					'title' => __( 'WooCommerce Price Title Settings', 'change-wc-price-title' ),
					'type'  => 'title',
					'desc'  => __( 'Configure the price title settings for WooCommerce Product Price.', 'change-wc-price-title' ),
					'id'    => 'cwpt_settings_section',
				),

				array(
					'title'    => __( 'Change Price Title For All Products', 'change-wc-price-title' ),
					'desc'     => __( 'Here you can set price title for all your products. Also you can use PRICE shortcode as per your requirement. E.g From: PRICE Only/-', 'change-wc-price-title' ),
					'id'       => 'cwpt_woocommerce_price_title',
					'type'     => 'text',
					'default'  => '',
					'css'      => 'min-width:400px;',
					'desc_tip' => true,
				),

				array(
					'title'   => __( 'Hide Price Title', 'change-wc-price-title' ),
					'desc'    => __( 'You can hide price title for all WooCommerce products.', 'change-wc-price-title' ),
					'id'      => 'cwpt_woocommerce_hide_price_title',
					'type'    => 'checkbox',
					'default' => 'no',
				),

				array(
					'title'   => __( 'Apply Above Options On All WooCommerce Pages', 'change-wc-price-title' ),
					'desc'    => __( 'Enable this if you wish to apply above setting on all WooCommerce Pages.', 'change-wc-price-title' ),
					'id'      => 'cwpt_apply_on_all_products',
					'type'    => 'checkbox',
					'default' => 'no',
				),

				array(
					'title'   => __( 'Enable to show price by multiplying with quantity', 'change-wc-price-title' ),
					'desc'    => __( 'Enable this if you wish to show price as per the multiply by quantity.', 'change-wc-price-title' ),
					'id'      => 'cwpt_enable_multiplier',
					'type'    => 'checkbox',
					'default' => 'no',
				),

				array(
					'type' => 'sectionend',
					'id'   => 'cwpt_settings_section',
				),

				// Upgrade to Pro Section
				array(
					'title' => __( 'Upgrade to Pro', 'change-wc-price-title' ),
					'type'  => 'title',
					'desc'  => $this->get_pro_upgrade_content(),
					'id'    => 'cwpt_pro_upgrade_section',
				),

				array(
					'type' => 'sectionend',
					'id'   => 'cwpt_pro_upgrade_section',
				),
			);

			return apply_filters( 'cwpt_settings', $settings );
		}

		/**
		 * Get Pro upgrade section content.
		 *
		 * @return string
		 * @since 2.8
		 */
		private function get_pro_upgrade_content() {
			$pro_url = 'https://woocommerce.com/products/change-price-title-pro';

			ob_start();
			?>
			<div style="background: #f0f6fc; border: 1px solid #c3e0f7; border-radius: 4px; padding: 20px; margin: 10px 0;">
				<h3 style="margin-top: 0; color: #0073aa;">
					<span class="dashicons dashicons-star-filled" style="color: #f1c40f;"></span><?php esc_html_e( 'Change Price Title Pro for WooCommerce', 'change-wc-price-title' ); ?>
				</h3>
				
				<p style="font-size: 14px; line-height: 1.6;">
					<?php esc_html_e( 'Unlock advanced features and take complete control of your product pricing display with the Pro version!', 'change-wc-price-title' ); ?>
				</p>
				
				<div style="margin: 20px 0;">
					<h4 style="margin-bottom: 10px;"><?php esc_html_e( 'Pro Features Include:', 'change-wc-price-title' ); ?></h4>
					<ul style="list-style: none; padding: 0; margin: 0;">
						<li style="padding: 8px 0; border-bottom: 1px solid #ddd;">
							<span class="dashicons dashicons-yes" style="color: #46b450;"></span><?php esc_html_e( 'Advanced price formatting options with multiple templates', 'change-wc-price-title' ); ?>
						</li>
						<li style="padding: 8px 0; border-bottom: 1px solid #ddd;">
							<span class="dashicons dashicons-yes" style="color: #46b450;"></span><?php esc_html_e( 'Category-specific price titles', 'change-wc-price-title' ); ?>
						</li>
						<li style="padding: 8px 0; border-bottom: 1px solid #ddd;">
							<span class="dashicons dashicons-yes" style="color: #46b450;"></span><?php esc_html_e( 'User role-based price display', 'change-wc-price-title' ); ?>
						</li>
						<li style="padding: 8px 0; border-bottom: 1px solid #ddd;">
							<span class="dashicons dashicons-yes" style="color: #46b450;"></span><?php esc_html_e( 'Custom price suffixes and prefixes', 'change-wc-price-title' ); ?>
						</li>
						<li style="padding: 8px 0; border-bottom: 1px solid #ddd;">
							<span class="dashicons dashicons-yes" style="color: #46b450;"></span><?php esc_html_e( 'Conditional pricing rules', 'change-wc-price-title' ); ?>
						</li>
						<li style="padding: 8px 0; border-bottom: 1px solid #ddd;">
							<span class="dashicons dashicons-yes" style="color: #46b450;"></span><?php esc_html_e( 'Priority support and regular updates', 'change-wc-price-title' ); ?>
						</li>
						<li style="padding: 8px 0;">
							<span class="dashicons dashicons-yes" style="color: #46b450;"></span><?php esc_html_e( 'And much more...', 'change-wc-price-title' ); ?>
						</li>
					</ul>
				</div>
				
				<p style="margin-top: 20px;">
					<a href="<?php echo esc_url( $pro_url ); ?>" class="button button-primary button-large" target="_blank" style="background: #0073aa; border-color: #0073aa; text-shadow: none; box-shadow: none;"><span class="dashicons dashicons-external" style="margin-top: 3px;"></span><?php esc_html_e( 'Upgrade to Pro Now', 'change-wc-price-title' ); ?></a>
				</p>
			</div>
			<?php
			return ob_get_clean();
		}

		/**
		 * Save settings.
		 */
		public function save() {
			$settings = $this->get_settings();
			WC_Admin_Settings::save_fields( $settings );
		}
	}

	return new CWPT_WC_Settings();
}