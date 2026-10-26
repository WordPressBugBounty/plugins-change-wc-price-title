<?php
/**
 * Change Price Title for WooCommerce
 *
 * Uninstalling Change Price Title for WooCommerce Plugin deletes options and product meta.
 *
 * @author      Kartik Parmar
 * @category    Core
 * @package     Change_WooCommerce_Price_Title/Uninstall
 * @version     3.1
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

/**
 * Delete the plugin's options and product meta for the current site.
 */
function cwpt_uninstall_site_data() {
	// Deleting all the settings added at product level.
	delete_post_meta_by_key( '_cwpt_price_title' );
	delete_post_meta_by_key( '_cwpt_hide_price' );
	delete_post_meta_by_key( '_cwpt_apply_on_all_wc_pages' );

	// Deleting options added from global level.
	delete_option( 'cwpt_woocommerce_price_title' );
	delete_option( 'cwpt_woocommerce_hide_price_title' );
	delete_option( 'cwpt_apply_on_all_products' );
	delete_option( 'cwpt_enable_multiplier' );
	delete_option( 'change_woocommerce_price_title_db_version' );
	delete_option( 'cwpt_show_migration_notice' );
}

if ( is_multisite() ) {
	$cwpt_site_ids = get_sites(
		array(
			'fields' => 'ids',
			'number' => 0,
		)
	);

	foreach ( $cwpt_site_ids as $cwpt_site_id ) {
		switch_to_blog( $cwpt_site_id );
		cwpt_uninstall_site_data();
		restore_current_blog();
	}
} else {
	cwpt_uninstall_site_data();
}
