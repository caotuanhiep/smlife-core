<?php
/**
 * SMLIFE - Snippet 09: Promo Bar (thanh chay chu tren cung)
 * ---------------------------------------------------------
 * Quan ly tai: SMLIFE Core > Promo Bar
 * Shortcode: [smlife_promo]
 *
 * WPCode: chon "Run everywhere". Khi dan vao trinh soan: KHONG copy dong <?php.
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

/* ===== Tuy chon ===== */
function smlife_pb_opt() {
	$def = array(
		'enabled'     => 1,
		'position'    => 'fixed',
		'bg'          => '#0f1115',
		'text_color'  => '#9aa0a6',
		'ok_color'    => '#34d399',
		'height'      => 32,
		'font_size'   => 12,
		'speed'       => 26,
		'gap'         => 22,
		'pause'       => 1,
		'show_desktop'=> 1,
		'show_mobile' => 1,
		'hide_topbar' => 1,
		'items'       => array(
			array( 'on' => 1, 'icon' => 'palette', 'q' => 'Đổi màu sắc theo ý bạn',   'ok' => 'Được' ),
			array( 'on' => 1, 'icon' => 'ruler',   'q' => 'Làm theo kích thước riêng', 'ok' => 'Được' ),
			array( 'on' => 1, 'icon' => 'truck',   'q' => 'Giao lắp tận nơi tại HCM',  'ok' => 'Miễn phí' ),
			array( 'on' => 0, 'icon' => 'shield',  'q' => '', 'ok' => '' ),
			array( 'on' => 0, 'icon' => 'clock',   'q' => '', 'ok' => '' ),
			array( 'on' => 0, 'icon' => 'tag',     'q' => '', 'ok' => '' ),
		),
	);
	$s = get_option( 'smlife_pb', array() );
	if ( ! is_array( $s ) ) {
		return $def;
	}
	$out = array_merge( $def, $s );
	if ( empty( $out['items'] ) || ! is_array( $out['items'] ) ) {
		$out['items'] = $def['items'];
	}
	return $out;
}

/* ===== Bo icon ===== */
function smlife_pb_icons() {
	return array(
		'palette' => array(
			'Bảng màu',
			'<circle cx="13.5" cy="6.5" r="1.2" fill="currentColor"/><circle cx="17.5" cy="10.5" r="1.2" fill="currentColor"/><circle cx="8.5" cy="7.5" r="1.2" fill="currentColor"/><circle cx="6.5" cy="12.5" r="1.2" fill="currentColor"/><path d="M12 2C6.5 2 2 6.5 2 12s4.5 10 10 10c.93 0 1.65-.75 1.65-1.69 0-.44-.18-.83-.44-1.12-.29-.29-.44-.65-.44-1.13 0-.93.75-1.69 1.69-1.69H16.5c3.04 0 5.5-2.46 5.5-5.5C22 6.1 17.5 2 12 2z"/>',
		),
		'ruler' => array(
			'Thước đo',
			'<path d="M21.3 8.7 8.7 21.3a1 1 0 0 1-1.4 0l-4.6-4.6a1 1 0 0 1 0-1.4L15.3 2.7a1 1 0 0 1 1.4 0l4.6 4.6a1 1 0 0 1 0 1.4z"/><path d="M7.5 10.5l2 2M10.5 7.5l2 2M13.5 4.5l2 2M4.5 13.5l2 2"/>',
		),
		'truck' => array(
			'Xe giao hàng',
			'<rect x="1" y="5" width="13" height="11" rx="1"/><path d="M14 9h4l3 3v4h-7"/><circle cx="6" cy="18" r="2"/><circle cx="17" cy="18" r="2"/>',
		),
		'shield' => array(
			'Bảo hành',
			'<path d="M12 2l8 4v6c0 5-3.4 8.9-8 10-4.6-1.1-8-5-8-10V6l8-4z"/><polyline points="9 12 11 14 15 10"/>',
		),
		'clock' => array(
			'Thời gian',
			'<circle cx="12" cy="12" r="9"/><polyline points="12 7 12 12 15.5 14"/>',
		),
		'tag' => array(
			'Giá tốt',
			'<path d="M20.6 13.4 13 21a1.4 1.4 0 0 1-2 0l-8-8V3h10l7.6 7.6a1.4 1.4 0 0 1 0 2z"/><circle cx="7.5" cy="7.5" r="1.3" fill="currentColor"/>',
		),
		'star' => array(
			'Chất lượng',
			'<polygon points="12 2.5 14.9 8.4 21.5 9.3 16.7 13.9 17.9 20.4 12 17.3 6.1 20.4 7.3 13.9 2.5 9.3 9.1 8.4"/>',
		),
		'phone' => array(
			'Hỗ trợ',
			'<path d="M22 16.9v2.6a2 2 0 0 1-2.2 2 19.8 19.8 0 0 1-8.6-3.1 19.5 19.5 0 0 1-6-6A19.8 19.8 0 0 1 2.1 3.7 2 2 0 0 1 4.1 1.5h2.6a2 2 0 0 1 2 1.7c.1 1 .4 1.9.7 2.8a2 2 0 0 1-.5 2.1L7.8 9.3a16 16 0 0 0 6 6l1.2-1.1a2 2 0 0 1 2.1-.5c.9.3 1.8.6 2.8.7a2 2 0 0 1 1.7 2z"/>',
		),
		'box' => array(
			'Đóng gói',
			'<path d="M21 8 12 3 3 8v8l9 5 9-5z"/><path d="M3 8l9 5 9-5M12 13v8"/>',
		),
		'wrench' => array(
			'Lắp đặt',
			'<path d="M21 4.5a5.5 5.5 0 0 1-7.3 7.3L5 20.5 3.5 19l8.7-8.7A5.5 5.5 0 0 1 19.5 3l-3 3 1.5 1.5 3-3z"/>',
		),
		'leaf' => array(
			'Thân thiện',
			'<path d="M20 4c0 9-5 14-12 14H4c0-9 5-14 12-14h4z"/><path d="M4 20c3-5 7-8 12-10"/>',
		),
		'check' => array(
			'Dấu tích',
			'<polyline points="20 6 9 17 4 12"/>',
		),
	);
}

function smlife_pb_icon( $key ) {
	$ic = smlife_pb_icons();
	if ( ! isset( $ic[ $key ] ) ) {
		$key = 'check';
	}
	return '<svg class="sml-ic" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">' . $ic[ $key ][1] . '</svg>';
}

/* ===== Dung HTML ===== */
function smlife_pb_render() {
	$o = smlife_pb_opt();
	if ( empty( $o['enabled'] ) ) {
		return '';
	}
	$rows = array();
	foreach ( $o['items'] as $it ) {
		if ( ! empty( $it['on'] ) && '' !== trim( (string) $it['q'] ) ) {
			$rows[] = $it;
		}
	}
	if ( empty( $rows ) ) {
		return '';
	}

	$check = '<svg class="sml-ck" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>';

	$one = '';
	foreach ( $rows as $it ) {
		$one .= '<span class="sml-item">' . smlife_pb_icon( $it['icon'] );
		$one .= '<span class="sml-q">' . esc_html( $it['q'] ) . '</span>';
		$ok = trim( (string) $it['ok'] );
		if ( '' !== $ok ) {
			$one .= '<span class="sml-ok">' . esc_html( $ok ) . ' ' . $check . '</span>';
		}
		$one .= '</span><span class="sml-sep"></span>';
	}

	$pos = ( 'static' === $o['position'] ) ? 'pb-static' : 'pb-fixed';
	return '<div id="sml-promo-bar" class="' . $pos . '"><div class="sml-track">' . $one . $one . '</div></div>';
}

/* ===== CSS ===== */
function smlife_pb_css() {
	$o     = smlife_pb_opt();
	$h     = max( 20, (int) $o['height'] );
	$fs    = max( 9, (int) $o['font_size'] );
	$sp    = max( 5, (int) $o['speed'] );
	$gap   = max( 4, (int) $o['gap'] );
	$fixed = ( 'static' !== $o['position'] );

	$c  = '#sml-promo-bar{height:' . $h . 'px;min-height:' . $h . 'px;overflow:hidden;box-sizing:border-box;';
	$c .= 'background:' . esc_attr( $o['bg'] ) . ';border-bottom:1px solid rgba(255,255,255,.06);display:flex;align-items:center;';
	$c .= 'font-family:inherit;';
	$c .= '-webkit-mask-image:linear-gradient(90deg,transparent,#000 8%,#000 92%,transparent);mask-image:linear-gradient(90deg,transparent,#000 8%,#000 92%,transparent)}';
	$c .= '#sml-promo-bar.pb-fixed{position:fixed;top:0;left:0;right:0;z-index:99999}';
	$c .= '#sml-promo-bar.pb-static{position:relative;width:100%}';
	$c .= 'body.admin-bar #sml-promo-bar.pb-fixed{top:32px}';
	$c .= '@media(max-width:782px){body.admin-bar #sml-promo-bar.pb-fixed{top:46px}}';
	$c .= '#sml-promo-bar .sml-track{display:flex;align-items:center;white-space:nowrap;will-change:transform;animation:smlScroll ' . $sp . 's linear infinite}';
	if ( ! empty( $o['pause'] ) ) {
		$c .= '#sml-promo-bar:hover .sml-track{animation-play-state:paused}';
	}
	$c .= '@keyframes smlScroll{from{transform:translateX(0)}to{transform:translateX(-50%)}}';
	$c .= '#sml-promo-bar .sml-item{display:inline-flex;align-items:center;gap:7px;padding:0 ' . $gap . 'px;font-size:' . $fs . 'px;line-height:' . $h . 'px;letter-spacing:.2px}';
	$c .= '#sml-promo-bar .sml-q{color:' . esc_attr( $o['text_color'] ) . ';font-weight:400}';
	$c .= '#sml-promo-bar .sml-ic{width:14px;height:14px;flex:0 0 14px;color:' . esc_attr( $o['text_color'] ) . '}';
	$c .= '#sml-promo-bar .sml-ok{color:' . esc_attr( $o['ok_color'] ) . ';font-weight:600;display:inline-flex;align-items:center;gap:4px}';
	$c .= '#sml-promo-bar .sml-ck{width:13px;height:13px}';
	$c .= '#sml-promo-bar .sml-sep{flex:0 0 auto;width:4px;height:4px;border-radius:50%;background:#3a3f46;margin:0 2px;align-self:center}';

	if ( $fixed ) {
		$c .= '#wrapper{padding-top:' . $h . 'px}';
	}
	if ( empty( $o['show_mobile'] ) ) {
		$c .= '@media(max-width:849px){#sml-promo-bar{display:none}';
		if ( $fixed ) {
			$c .= '#wrapper{padding-top:0}';
		}
		$c .= '}';
	}
	if ( empty( $o['show_desktop'] ) ) {
		$c .= '@media(min-width:850px){#sml-promo-bar{display:none}';
		if ( $fixed ) {
			$c .= '#wrapper{padding-top:0}';
		}
		$c .= '}';
	}
	if ( ! empty( $o['hide_topbar'] ) ) {
		$c .= '@media(max-width:849px){#top-bar.header-top{display:none !important}}';
	}
	return $c;
}

add_action( 'wp_head', function () {
	$o = smlife_pb_opt();
	if ( empty( $o['enabled'] ) ) {
		return;
	}
	echo '<style id="smlife-pb-css">' . smlife_pb_css() . '</style>'; // phpcs:ignore
}, 20 );

/* ===== Shortcode + tu chen dau trang ===== */
add_shortcode( 'smlife_promo', 'smlife_pb_render' );

add_action( 'wp_body_open', function () {
	if ( is_admin() ) {
		return;
	}
	echo smlife_pb_render(); // phpcs:ignore
}, 5 );

/* ===== Submenu: SMLIFE Core > Promo Bar ===== */
add_action( 'admin_menu', function () {
	add_submenu_page(
		'smlife-core',
		'Promo Bar',
		'Promo Bar',
		'manage_options',
		'smlife-promo-bar',
		'smlife_pb_page'
	);
}, 44 );

function smlife_pb_page() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}
	$o     = smlife_pb_opt();
	$icons = smlife_pb_icons();

	echo '<div class="wrap pb-wrap">';
	

	echo '<div class="pb-hd"><h1>Promo Bar</h1>';
	echo '<p>Thanh chạy chữ trên cùng website, dùng để nhấn các cam kết bán hàng. Nội dung được lặp 2 lần để chạy liền mạch, không cần nhập 2 lần.</p></div>';

	if ( isset( $_GET['saved'] ) ) {
		echo '<div class="notice notice-success is-dismissible"><p>Đã lưu.</p></div>';
	}

	echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '">';
	echo '<input type="hidden" name="action" value="smlife_pb_save" />';
	wp_nonce_field( 'smlife_pb_save', 'smlife_pb_nonce' );

	/* Bat tat */
	echo '<div class="pb-card"><h2>Bật và vị trí</h2><div class="in">';

	echo '<div class="pb-row"><label class="pb-sw"><input type="checkbox" name="pb[enabled]" value="1" ' . checked( 1, (int) $o['enabled'], false ) . ' /><i></i></label>';
	echo '<span class="tx"><strong>Bật Promo Bar</strong><em>Tắt là ẩn hoàn toàn khỏi website.</em></span></div>';

	echo '<div class="pb-row"><label class="pb-sw"><input type="checkbox" name="pb[pause]" value="1" ' . checked( 1, (int) $o['pause'], false ) . ' /><i></i></label>';
	echo '<span class="tx"><strong>Dừng khi rê chuột</strong><em>Khách rê chuột vào thanh thì chữ dừng lại để đọc.</em></span></div>';

	echo '<div class="pb-row"><label class="pb-sw"><input type="checkbox" name="pb[show_desktop]" value="1" ' . checked( 1, (int) $o['show_desktop'], false ) . ' /><i></i></label>';
	echo '<span class="tx"><strong>Hiện trên máy tính</strong><em>Màn hình rộng từ 850px trở lên.</em></span></div>';

	echo '<div class="pb-row"><label class="pb-sw"><input type="checkbox" name="pb[show_mobile]" value="1" ' . checked( 1, (int) $o['show_mobile'], false ) . ' /><i></i></label>';
	echo '<span class="tx"><strong>Hiện trên điện thoại</strong><em>Màn hình nhỏ hơn 850px. Tắt cả hai là thanh không hiện ở đâu cả.</em></span></div>';

	echo '<div class="pb-row"><label class="pb-sw"><input type="checkbox" name="pb[hide_topbar]" value="1" ' . checked( 1, (int) $o['hide_topbar'], false ) . ' /><i></i></label>';
	echo '<span class="tx"><strong>Ẩn thanh top-bar trống của Flatsome trên điện thoại</strong><em>Khắc phục dải màu trống nằm ngay dưới Promo Bar khi xem bằng điện thoại.</em></span></div>';

	echo '<div class="pb-fld" style="margin-top:12px"><label>Kiểu hiển thị</label><select name="pb[position]">';
	$poss = array( 'fixed' => 'Cố định trên cùng (luôn thấy khi cuộn)', 'static' => 'Nằm đầu trang (cuộn thì mất)' );
	foreach ( $poss as $k => $lb ) {
		echo '<option value="' . esc_attr( $k ) . '" ' . selected( $o['position'], $k, false ) . '>' . esc_html( $lb ) . '</option>';
	}
	echo '</select></div>';

	echo '</div></div>';

	/* Noi dung */
	echo '<div class="pb-card"><h2>Nội dung (tối đa 6 mục)</h2><div class="in">';
	echo '<table class="pb-tb"><tr><th style="width:46px">Bật</th><th style="width:150px">Biểu tượng</th><th>Nội dung</th><th style="width:150px">Kết quả (màu nhấn)</th></tr>';
	for ( $i = 0; $i < 6; $i++ ) {
		$it = isset( $o['items'][ $i ] ) ? $o['items'][ $i ] : array( 'on' => 0, 'icon' => 'check', 'q' => '', 'ok' => '' );
		echo '<tr>';
		echo '<td><label class="pb-sw"><input type="checkbox" name="pb[items][' . $i . '][on]" value="1" ' . checked( 1, (int) $it['on'], false ) . ' /><i></i></label></td>';
		echo '<td><select name="pb[items][' . $i . '][icon]">';
		foreach ( $icons as $k => $v ) {
			echo '<option value="' . esc_attr( $k ) . '" ' . selected( $it['icon'], $k, false ) . '>' . esc_html( $v[0] ) . '</option>';
		}
		echo '</select></td>';
		echo '<td><input type="text" name="pb[items][' . $i . '][q]" value="' . esc_attr( $it['q'] ) . '" placeholder="Ví dụ: Giao lắp tận nơi tại HCM" /></td>';
		echo '<td><input type="text" name="pb[items][' . $i . '][ok]" value="' . esc_attr( $it['ok'] ) . '" placeholder="Miễn phí" /></td>';
		echo '</tr>';
	}
	echo '</table>';
	echo '</div></div>';

	/* Giao dien */
	echo '<div class="pb-card"><h2>Giao diện</h2><div class="in">';
	echo '<div class="pb-fld"><label>Màu nền</label><input type="text" class="pb-color" name="pb[bg]" value="' . esc_attr( $o['bg'] ) . '" /></div>';
	echo '<div class="pb-fld"><label>Màu chữ</label><input type="text" class="pb-color" name="pb[text_color]" value="' . esc_attr( $o['text_color'] ) . '" /></div>';
	echo '<div class="pb-fld"><label>Màu nhấn</label><input type="text" class="pb-color" name="pb[ok_color]" value="' . esc_attr( $o['ok_color'] ) . '" /></div>';
	echo '<br />';
	echo '<div class="pb-fld"><label>Chiều cao (px)</label><input type="number" min="20" max="80" name="pb[height]" value="' . (int) $o['height'] . '" /></div>';
	echo '<div class="pb-fld"><label>Cỡ chữ (px)</label><input type="number" min="9" max="20" name="pb[font_size]" value="' . (int) $o['font_size'] . '" /></div>';
	echo '<div class="pb-fld"><label>Thời gian chạy 1 vòng (giây)</label><input type="number" min="5" max="120" name="pb[speed]" value="' . (int) $o['speed'] . '" /></div>';
	echo '<div class="pb-fld"><label>Khoảng cách giữa các mục (px)</label><input type="number" min="4" max="60" name="pb[gap]" value="' . (int) $o['gap'] . '" /></div>';
	echo '</div></div>';

	/* Xem truoc */
	echo '<div class="pb-card"><h2>Xem trước</h2><div class="in">';
	echo '<style>' . smlife_pb_css() . '</style>'; // phpcs:ignore
	echo '<div class="pb-prev">' . smlife_pb_render() . '</div>'; // phpcs:ignore
	echo '<p style="margin:10px 0 0;color:#646970;font-size:12px">Bản xem trước dùng đúng màu và tốc độ đang cài. Lưu rồi tải lại trang để thấy thay đổi.</p>';
	echo '</div></div>';

	echo '<div class="pb-note"><b>Ghi chú</b><ul style="margin:8px 0 0 18px;list-style:disc">';
	echo '<li>Chạy chậm lại thì tăng số giây, chạy nhanh thì giảm số giây.</li>';
	echo '<li>Muốn đặt thanh này ở chỗ khác trong trang thì dùng shortcode <code>[smlife_promo]</code>.</li>';
	echo '<li>Kiểu "Cố định trên cùng" tự chừa chỗ cho nội dung website, không bị che header.</li>';
	echo '</ul></div>';

	submit_button( 'Lưu cài đặt' );
	echo '</form></div>';

	echo '<script>jQuery(function($){ if($.fn.wpColorPicker){ $(".pb-color").wpColorPicker(); } });</script>';
}

add_action( 'admin_enqueue_scripts', function ( $hook ) {
	if ( false !== strpos( (string) $hook, 'smlife-promo-bar' ) ) {
		wp_enqueue_style( 'wp-color-picker' );
		wp_enqueue_script( 'wp-color-picker' );
	}
} );

/* ===== Luu ===== */
add_action( 'admin_post_smlife_pb_save', function () {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( 'Không đủ quyền.' );
	}
	check_admin_referer( 'smlife_pb_save', 'smlife_pb_nonce' );

	$in    = isset( $_POST['pb'] ) && is_array( $_POST['pb'] ) ? wp_unslash( $_POST['pb'] ) : array();
	$icons = smlife_pb_icons();

	$items = array();
	for ( $i = 0; $i < 6; $i++ ) {
		$r    = isset( $in['items'][ $i ] ) && is_array( $in['items'][ $i ] ) ? $in['items'][ $i ] : array();
		$icon = isset( $r['icon'] ) && isset( $icons[ $r['icon'] ] ) ? $r['icon'] : 'check';
		$items[] = array(
			'on'   => empty( $r['on'] ) ? 0 : 1,
			'icon' => $icon,
			'q'    => sanitize_text_field( isset( $r['q'] ) ? $r['q'] : '' ),
			'ok'   => sanitize_text_field( isset( $r['ok'] ) ? $r['ok'] : '' ),
		);
	}

	$col = function ( $v, $fb ) {
		$c = sanitize_hex_color( is_string( $v ) ? trim( $v ) : '' );
		return $c ? $c : $fb;
	};

	$save = array(
		'enabled'     => empty( $in['enabled'] ) ? 0 : 1,
		'pause'       => empty( $in['pause'] ) ? 0 : 1,
		'show_desktop' => empty( $in['show_desktop'] ) ? 0 : 1,
		'show_mobile' => empty( $in['show_mobile'] ) ? 0 : 1,
		'hide_topbar' => empty( $in['hide_topbar'] ) ? 0 : 1,
		'position'    => ( isset( $in['position'] ) && 'static' === $in['position'] ) ? 'static' : 'fixed',
		'bg'          => $col( isset( $in['bg'] ) ? $in['bg'] : '', '#0f1115' ),
		'text_color'  => $col( isset( $in['text_color'] ) ? $in['text_color'] : '', '#9aa0a6' ),
		'ok_color'    => $col( isset( $in['ok_color'] ) ? $in['ok_color'] : '', '#34d399' ),
		'height'      => min( 80, max( 20, (int) ( isset( $in['height'] ) ? $in['height'] : 32 ) ) ),
		'font_size'   => min( 20, max( 9, (int) ( isset( $in['font_size'] ) ? $in['font_size'] : 12 ) ) ),
		'speed'       => min( 120, max( 5, (int) ( isset( $in['speed'] ) ? $in['speed'] : 26 ) ) ),
		'gap'         => min( 60, max( 4, (int) ( isset( $in['gap'] ) ? $in['gap'] : 22 ) ) ),
		'items'       => $items,
	);

	update_option( 'smlife_pb', $save );
	/* 1.24.0: truoc day luu xong khong xoa cache, so moi chi len tung trang. Gio vao danh sach cho dem */
	smlife_xoa_cache( 'Promo Bar' );
	wp_safe_redirect( admin_url( 'admin.php?page=smlife-promo-bar&saved=1' ) );
	exit;
} );
