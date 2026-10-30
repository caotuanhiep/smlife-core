<?php
/**
 * Plugin Name:       SMLIFE Core
 * Plugin URI:        https://github.com/caotuanhiep/smlife-core
 * Update URI:        https://github.com/caotuanhiep/smlife-core
 * Description:       Bộ công cụ vận hành website cho hệ sinh thái SMLIFE. Gồm các module bán hàng, liên hệ và hệ thống, bật tắt từng cái trong Bảng điều khiển.
 * Version:     1.28.0
 * Requires at least: 5.8
 * Requires PHP:      7.4
 * Author:            Cao Tuấn Hiệp
 * Text Domain:       smlife-core
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'SMLIFE_CORE_VER', '1.28.0' );
define( 'SMLIFE_CORE_FILE', __FILE__ );
define( 'SMLIFE_CORE_DIR', plugin_dir_path( __FILE__ ) );
define( 'SMLIFE_CORE_URL', plugin_dir_url( __FILE__ ) );

require_once SMLIFE_CORE_DIR . 'includes/core.php';
require_once SMLIFE_CORE_DIR . 'includes/updater.php'; /* 1.25.0: tu cap nhat tu GitHub */

/* 1.24.0: khai bao tuong thich HPOS (luu don hang hieu suat cao). Moi cho doc/ghi
   don hang trong plugin deu qua API cua WooCommerce, khong dung get_post_meta.
   Khong khai bao thi WooCommerce bao "plugin khong tuong thich" va chan bat HPOS. */
add_action( 'before_woocommerce_init', function () {
	if ( class_exists( '\\Automattic\\WooCommerce\\Utilities\\FeaturesUtil' ) ) {
		\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', __FILE__, true );
	}
} );

/* Tat plugin thi go cac lich WP-Cron cua plugin */
register_deactivation_hook( __FILE__, function () {
	wp_clear_scheduled_hook( 'smlife_xc_chay' );
	wp_clear_scheduled_hook( 'smlife_duty_daily' );
	/* 1.24.0: go khoi ngoai le PHP trong .htaccess, khong de lai dau vet */
	if ( function_exists( 'smlife_xc_ht_go_khoi' ) ) {
		smlife_xc_ht_go_khoi();
	}
} );
