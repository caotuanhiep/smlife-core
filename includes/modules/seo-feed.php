<?php
/**
 * SMLIFE Core - Module: Tat feed san pham
 *
 * WordPress sinh mot feed RSS cho moi san pham, moi danh muc va moi the
 * (duoi dang /ten-san-pham/feed). Chung khong co gia tri SEO nhung Googlebot
 * van bo cong bo di crawl, va tren smlife.vn nhieu duong trong so do dang tra
 * loi 500 trong Search Console.
 *
 * Module nay chuyen huong 301 moi feed ve trang chu, tru feed bai viet chinh,
 * va go cac the <link rel="alternate" type="application/rss+xml"> khoi <head>
 * de khong con duong dan toi chung.
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

function smlife_feed_opt() {
	$def = array(
		'enabled'   => 1,
		'giu_blog'  => 1,  /* Giu feed bai viet chinh (/feed) cho cac dich vu doc tin */
	);
	$s = get_option( 'smlife_feed', array() );
	return is_array( $s ) ? array_merge( $def, $s ) : $def;
}

/* Chuyen huong feed khong can thiet */
function smlife_feed_chan() {
	$o = smlife_feed_opt();
	if ( empty( $o['enabled'] ) || ! is_feed() ) {
		return;
	}
	/* Bo qua feed comment cua WordPress o cap cao nhat neu chu web muon giu blog */
	if ( ! empty( $o['giu_blog'] ) && ( is_home() || is_front_page() ) ) {
		return;
	}
	if ( ! empty( $o['giu_blog'] ) && is_singular( 'post' ) ) {
		return;
	}
	wp_safe_redirect( home_url( '/' ), 301 );
	exit;
}
add_action( 'template_redirect', 'smlife_feed_chan', 1 );

/* Go link feed khoi <head> */
add_action( 'wp_head', function () {
	/* Chay som de kip go truoc khi WordPress in ra */
}, 1 );

add_action( 'init', function () {
	$o = smlife_feed_opt();
	if ( empty( $o['enabled'] ) ) {
		return;
	}
	remove_action( 'wp_head', 'feed_links_extra', 3 );
	if ( empty( $o['giu_blog'] ) ) {
		remove_action( 'wp_head', 'feed_links', 2 );
	}
} );

/* ===== Submenu: SMLIFE Core > Tắt feed ===== */
add_action( 'admin_menu', function () {
	add_submenu_page(
		'smlife-core',
		'Tắt feed sản phẩm',
		'Tắt feed sản phẩm',
		'manage_options',
		'smlife-tat-feed',
		'smlife_feed_page'
	);
}, 12 );

function smlife_feed_page() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}
	$o = smlife_feed_opt();
	echo '<div class="wrap smlife-wrap">';
	echo '<div class="smc-hd"><h1>Tắt feed sản phẩm</h1>';
	echo '<p>WordPress tạo một feed RSS riêng cho mỗi sản phẩm và mỗi danh mục. Chúng không mang lại lượt truy cập nào nhưng vẫn ngốn lượt crawl của Google, và nhiều đường đang báo lỗi 500 trong Search Console.</p></div>';
	if ( isset( $_GET['updated'] ) ) {
		echo '<div class="notice notice-success is-dismissible"><p>Đã lưu.</p></div>';
	}
	echo '<div class="smlife-card">';
	echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '">';
	echo '<input type="hidden" name="action" value="smlife_feed_save" />';
	wp_nonce_field( 'smlife_feed_save', 'smlife_feed_nonce' );
	echo '<table class="form-table" role="presentation">';
	echo '<tr><th scope="row">Bật tính năng</th><td><label><input type="checkbox" name="fd[enabled]" value="1" ' . checked( 1, $o['enabled'], false ) . ' /> Chuyển hướng 301 mọi feed không cần thiết về trang chủ</label></td></tr>';
	echo '<tr><th scope="row">Giữ feed blog</th><td><label><input type="checkbox" name="fd[giu_blog]" value="1" ' . checked( 1, $o['giu_blog'], false ) . ' /> Giữ lại feed bài viết chính tại <code>' . esc_html( home_url( '/feed' ) ) . '</code></label><br><span class="description">Bỏ chọn nếu không dùng feed cho bất kỳ dịch vụ nào</span></td></tr>';
	echo '</table>';
	submit_button( 'Lưu' );
	echo '</form></div></div>';
}

add_action( 'admin_post_smlife_feed_save', function () {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( 'Không đủ quyền.' );
	}
	check_admin_referer( 'smlife_feed_save', 'smlife_feed_nonce' );
	$in  = isset( $_POST['fd'] ) && is_array( $_POST['fd'] ) ? wp_unslash( $_POST['fd'] ) : array();
	update_option( 'smlife_feed', array(
		'enabled'  => empty( $in['enabled'] ) ? 0 : 1,
		'giu_blog' => empty( $in['giu_blog'] ) ? 0 : 1,
	) );
	wp_safe_redirect( admin_url( 'admin.php?page=smlife-tat-feed&updated=1' ) );
	exit;
} );
