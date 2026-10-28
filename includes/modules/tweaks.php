<?php
/**
 * SMLIFE Core - Module: Tinh chinh he thong
 * Cac tinh chinh nho giup trang nhe va hien thi dung tai Viet Nam.
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

/* ===== Tắt emoji ảnh của WordPress =====
 * WordPress đổi emoji thành ảnh SVG tải từ CDN ngoài (jsdelivr/twemoji). CDN này hay bị
 * chặn hoặc chặn chậm ở VN nên icon hiện bị vỡ. Tắt đi để emoji dùng font hệ thống:
 * hiển thị đúng, không phụ thuộc mạng ngoài và bớt 1 file JS cho tốc độ trang.
 */
smlife_disable_emoji();
add_action( 'init', 'smlife_disable_emoji', 1 );
add_action( 'admin_init', 'smlife_disable_emoji', 1 );
function smlife_disable_emoji() {
	remove_action( 'wp_head', 'print_emoji_detection_script', 7 );
	remove_action( 'admin_print_scripts', 'print_emoji_detection_script' );
	remove_action( 'wp_print_styles', 'print_emoji_styles' );
	remove_action( 'admin_print_styles', 'print_emoji_styles' );
	remove_action( 'embed_head', 'print_emoji_detection_script' );
	remove_filter( 'the_content_feed', 'wp_staticize_emoji' );
	remove_filter( 'comment_text_rss', 'wp_staticize_emoji' );
	remove_filter( 'wp_mail', 'wp_staticize_emoji_for_email' );
}

add_filter( 'tiny_mce_plugins', function ( $plugins ) {
	return is_array( $plugins ) ? array_diff( $plugins, array( 'wpemoji' ) ) : array();
} );

add_filter( 'wp_resource_hints', function ( $urls, $relation_type ) {
	if ( 'dns-prefetch' === $relation_type && is_array( $urls ) ) {
		$urls = array_filter( $urls, function ( $u ) {
			$href = is_array( $u ) && isset( $u['href'] ) ? $u['href'] : $u;
			return false === strpos( (string) $href, 'emoji' );
		} );
	}
	return $urls;
}, 10, 2 );

/* ===== Cho phép thẻ <source> trong nội dung ACF in ra trang =====
 * Từ ACF 6.2.5 trở đi, giá trị field in ra bằng [acf field="..."] bị lọc qua
 * wp_kses với bộ thẻ riêng tên "acf" (hàm acf_esc_html). Bộ thẻ đó giữ lại
 * <video> nhưng BỎ <source>, nên field nào chứa sẵn đoạn
 *   <video ...><source src="....mp4" type="video/mp4" /></video>
 * sẽ in ra khung video rỗng, nhìn như một mảng trắng giữa bài.
 * Đây là lý do các shortcode [acf field="short_video_1"] tự nhiên ngừng chạy
 * sau khi ACF cập nhật, dù nội dung trong field vẫn còn nguyên.
 *
 * Mở lại đúng hai thẻ <source> và <track> cùng vài thuộc tính của <video>,
 * KHÔNG tắt toàn bộ cơ chế lọc của ACF, nên các thẻ nguy hiểm như <script>
 * vẫn bị chặn như cũ.
 */
add_filter( 'wp_kses_allowed_html', function ( $tags, $context ) {
	if ( 'acf' !== $context && 'post' !== $context ) {
		return $tags;
	}
	if ( ! is_array( $tags ) ) {
		return $tags;
	}
	$tags['source'] = array(
		'src'    => true,
		'type'   => true,
		'media'  => true,
		'srcset' => true,
		'sizes'  => true,
	);
	$tags['track'] = array(
		'src'     => true,
		'kind'    => true,
		'srclang' => true,
		'label'   => true,
		'default' => true,
	);
	$video = ( isset( $tags['video'] ) && is_array( $tags['video'] ) ) ? $tags['video'] : array();
	$tags['video'] = array_merge( $video, array(
		'autoplay'    => true,
		'controls'    => true,
		'loop'        => true,
		'muted'       => true,
		'playsinline' => true,
		'preload'     => true,
		'poster'      => true,
		'src'         => true,
		'width'       => true,
		'height'      => true,
		'class'       => true,
		'id'          => true,
		'style'       => true,
	) );
	return $tags;
}, 20, 2 );
