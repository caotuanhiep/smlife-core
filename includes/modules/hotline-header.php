<?php
/**
 * SMLIFE Core - Module: Hotline Header
 * Khoi so hotline tren dau trang.
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

/**
 * SMLIFE CORE - 08. Hotline Header
 * ---------------------------------------------
 * Gop 2 snippet cu thanh 1:
 * - "HTML - So Hotline top menu Website"
 * - "CSS - Giao dien Header website"
 * Quan ly so hotline hien o header: ten, so, link Zalo, mau, co chu, duong ngan cach.
 * Tu chen vao o header cua Flatsome (mac dinh topbar_left),
 * ngoai ra co shortcode [smlife_hotline] de dat o bat ky dau.
 * Cau hinh trong SMLIFE Core > Hotline Header.
 *
 * WPCode: Run everywhere. Khi dan: KHONG copy dong mo PHP o tren cung.
 */

/* ===== Cau hinh ===== */
function smlife_hh_opt() {
	$def = array(
		'enabled'      => 1,
		'slot'         => 'topbar_left',
		'align'        => 'flex-end',
		'label_size'   => 10,
		'label_color'  => '#333333',
		'phone_size'   => 22,
		'phone_size_m' => 14,
		'divider'      => 1,
		'divider_color'=> '#e1e1e1',
		'gap'          => 20,
		'gap_m'        => 10,
		'items'        => array(
			array( 'on' => 1, 'label' => 'Hải Trách Nhiệm (Zalo)', 'phone' => '0914.913.179', 'link' => 'https://zalo.me/0914913179', 'color' => '#ee2468' ),
			array( 'on' => 1, 'label' => 'Phúc Tin Tưởng (Zalo)',  'phone' => '0909.553.363', 'link' => 'https://zalo.me/0909553363', 'color' => '#169cbe' ),
			array( 'on' => 0, 'label' => '', 'phone' => '', 'link' => '', 'color' => '#2bbf5e' ),
			array( 'on' => 0, 'label' => '', 'phone' => '', 'link' => '', 'color' => '#8c54d6' ),
		),
	);
	$s = get_option( 'smlife_hh', array() );
	if ( ! is_array( $s ) ) {
		return $def;
	}
	$out = array_merge( $def, $s );
	if ( empty( $out['items'] ) || ! is_array( $out['items'] ) ) {
		$out['items'] = $def['items'];
	}
	return $out;
}

function smlife_hh_slots() {
	return array(
		'topbar_left'  => 'Thanh trên cùng - bên trái',
		'topbar_right' => 'Thanh trên cùng - bên phải',
		'none'         => 'Không tự chèn (chỉ dùng shortcode)',
	);
}

/* ===== Dung HTML ===== */
function smlife_hh_render() {
	$o = smlife_hh_opt();
	if ( empty( $o['enabled'] ) ) {
		return '';
	}
	$rows = array();
	foreach ( $o['items'] as $it ) {
		if ( ! empty( $it['on'] ) && '' !== trim( (string) $it['phone'] ) ) {
			$rows[] = $it;
		}
	}
	if ( empty( $rows ) ) {
		return '';
	}

	$h = '<div class="header-contact-group">';
	foreach ( $rows as $it ) {
		/* Lich Truc: o mang so hotline tong se doi ten va link Zalo theo nguoi dang truc. */
		$lbl  = (string) $it['label'];
		$main = function_exists( 'smlife_duty_is_main' ) ? smlife_duty_is_main( $it['phone'] ) : false;
		$src  = null;
		if ( $main && function_exists( 'smlife_duty_on_person' ) ) {
			$src = smlife_duty_on_person();
		}
		if ( null === $src && function_exists( 'smlife_duty_person_by_phone' ) ) {
			$src = smlife_duty_person_by_phone( $it['phone'] );
		}
		if ( $src ) {
			$lbl = str_replace( array( '{ten}', '{mota}' ), array( $src['name'], $src['desc'] ), $lbl );
		}

		$link = trim( (string) $it['link'] );
		if ( $main && $src && '' !== $src['zalo'] ) {
			$link = $src['zalo'];
		}
		if ( '' === $link ) {
			$link = 'tel:' . preg_replace( '/[^0-9+]/', '', (string) $it['phone'] );
			$tgt  = '';
		} else {
			$tgt = ' target="_blank" rel="nofollow noopener"';
		}
		$col = sanitize_hex_color( $it['color'] ) ? $it['color'] : '#333333';

		$dcls = function_exists( 'smlife_duty_class' ) ? smlife_duty_class( $it['phone'] ) : '';
		$dbdg = function_exists( 'smlife_duty_badge' ) ? smlife_duty_badge( $it['phone'], 'header' ) : '';

		$h .= '<div class="contact-item' . $dcls . '">';
		if ( '' !== trim( $lbl ) ) {
			$h .= '<div class="contact-label">' . esc_html( $lbl ) . $dbdg . '</div>';
		}
		$h .= '<div class="contact-phone"><a href="' . esc_url( $link ) . '"' . $tgt . ' style="color:' . esc_attr( $col ) . '">' . esc_html( $it['phone'] ) . '</a></div>';
		$h .= '</div>';
	}
	$h .= '</div>';
	return $h;
}

/* ===== CSS ===== */
add_action( 'wp_head', function () {
	$o = smlife_hh_opt();
	if ( empty( $o['enabled'] ) ) {
		return;
	}
	$align = in_array( $o['align'], array( 'flex-start', 'center', 'flex-end' ), true ) ? $o['align'] : 'flex-end';
	$gap   = (int) $o['gap'];
	$gapm  = (int) $o['gap_m'];

	$css  = '.header-contact-group{display:flex;align-items:center;justify-content:' . $align . ';margin-left:auto}';
	$css .= '.header-contact-group .contact-item{text-align:center;line-height:1.3}';
	if ( ! empty( $o['divider'] ) ) {
		$css .= '.header-contact-group .contact-item:not(:last-child){border-right:1px solid ' . esc_attr( $o['divider_color'] ) . ';padding-right:' . $gap . 'px;margin-right:' . $gap . 'px}';
	} else {
		$css .= '.header-contact-group .contact-item:not(:last-child){padding-right:' . $gap . 'px;margin-right:' . $gap . 'px}';
	}
	$css .= '.header-contact-group .contact-label{font-size:' . (int) $o['label_size'] . 'px;font-weight:700;text-transform:uppercase;color:' . esc_attr( $o['label_color'] ) . '}';
	$css .= '.header-contact-group .contact-phone a{font-size:' . (int) $o['phone_size'] . 'px;font-weight:800;white-space:nowrap;text-decoration:none}';
	$css .= '@media(max-width:550px){';
	$css .= '.header-contact-group{margin-left:0;justify-content:center}';
	$css .= '.header-contact-group .contact-phone a{font-size:' . (int) $o['phone_size_m'] . 'px}';
	$css .= '.header-contact-group .contact-item:not(:last-child){padding-right:' . $gapm . 'px;margin-right:' . $gapm . 'px}';
	$css .= '}';

	echo '<style id="smlife-hh-css">' . $css . '</style>'; // phpcs:ignore
}, 20 );

/* ===== Shortcode ===== */
add_shortcode( 'smlife_hotline', 'smlife_hh_render' );

/* ===== Tu chen vao o header cua Flatsome ===== */
function smlife_hh_hook_slot() {
	static $done = false;
	if ( $done ) {
		return;
	}
	$o = smlife_hh_opt();
	if ( empty( $o['enabled'] ) || 'none' === $o['slot'] ) {
		return;
	}
	$slots = smlife_hh_slots();
	if ( ! isset( $slots[ $o['slot'] ] ) ) {
		return;
	}
	$done = true;
	$slot = $o['slot'];

	add_filter( 'theme_mod_' . $slot, function ( $v ) {
		if ( is_admin() ) {
			return $v;
		}
		$html = smlife_hh_cached_html();
		return '' !== $html ? $html : $v;
	}, 20 );

	$mods_option = 'option_theme_mods_' . get_option( 'stylesheet' );
	add_filter( $mods_option, function ( $mods ) use ( $slot ) {
		if ( is_admin() || ! is_array( $mods ) ) {
			return $mods;
		}
		$html = smlife_hh_cached_html();
		if ( '' !== $html ) {
			$mods[ $slot ] = $html;
		}
		return $mods;
	}, 20 );
}

function smlife_hh_cached_html() {
	static $html = null;
	if ( null === $html ) {
		$html = smlife_hh_render();
	}
	return $html;
}
smlife_hh_hook_slot();
add_action( 'init', 'smlife_hh_hook_slot', 1 );

/* ===== Chen shortcode vao vi tri header cua Flatsome =====
 * Flatsome doc noi dung khoi HTML tu theme mod va chay do_shortcode() khi render,
 * nen cach chac chan nhat la ghi [smlife_hotline] vao dung o do mot lan.
 * Gia tri cu duoc sao luu trong option smlife_hh_backup de co the khoi phuc.
 */
function smlife_hh_ensure_slot() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}
	$o    = smlife_hh_opt();
	$sc   = '[smlife_hotline]';
	$all  = get_option( 'smlife_hh', array() );
	$all  = is_array( $all ) ? $all : array();
	$prev = isset( $all['applied_slot'] ) ? (string) $all['applied_slot'] : '';

	$slot  = ( empty( $o['enabled'] ) || 'none' === $o['slot'] ) ? '' : (string) $o['slot'];
	$slots = smlife_hh_slots();
	if ( '' !== $slot && ! isset( $slots[ $slot ] ) ) {
		$slot = '';
	}
	if ( $prev === $slot ) {
		if ( '' === $slot ) {
			return;
		}
		$cur = (string) get_theme_mod( $slot, '' );
		if ( false !== strpos( $cur, $sc ) ) {
			return;
		}
	}

	$bak = get_option( 'smlife_hh_backup', array() );
	$bak = is_array( $bak ) ? $bak : array();

	if ( '' !== $prev && $prev !== $slot ) {
		$old = (string) get_theme_mod( $prev, '' );
		if ( false !== strpos( $old, $sc ) ) {
			set_theme_mod( $prev, isset( $bak[ $prev ] ) ? $bak[ $prev ] : '' );
		}
	}

	if ( '' !== $slot ) {
		$cur = (string) get_theme_mod( $slot, '' );
		if ( ! isset( $bak[ $slot ] ) && false === strpos( $cur, $sc ) ) {
			$bak[ $slot ] = $cur;
			update_option( 'smlife_hh_backup', $bak, false ); /* du lieu sao luu, chi admin doc */
		}
		if ( false === strpos( $cur, $sc ) ) {
			set_theme_mod( $slot, $sc );
		}
	}

	$all['applied_slot'] = $slot;
	update_option( 'smlife_hh', $all );
}
add_action( 'admin_init', 'smlife_hh_ensure_slot', 20 );

/* ===== Submenu: SMLIFE Core > Hotline Header ===== */
add_action( 'admin_menu', function () {
	add_submenu_page(
		'smlife-core',
		'Hotline Header',
		'Hotline Header',
		'manage_options',
		'smlife-hotline-header',
		'smlife_hh_page'
	);
}, 52 );

function smlife_hh_page() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}
	$o     = smlife_hh_opt();
	$slots = smlife_hh_slots();

	echo '<div class="wrap smlife-wrap">';
	

	echo '<div class="hh-hd"><h1>Hotline Header</h1>';
	echo '<p>Quản lý dãy số hotline hiện trên header website. Gộp từ 2 snippet cũ "HTML - Số Hotline top menu" và "CSS - Giao diện Header website".</p></div>';

	if ( isset( $_GET['updated'] ) ) {
		echo '<div class="notice notice-success is-dismissible"><p>Đã lưu.</p></div>';
	}

	echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '">';
	echo '<input type="hidden" name="action" value="smlife_hh_save" />';
	wp_nonce_field( 'smlife_hh_save', 'smlife_hh_nonce' );

	echo '<div class="hh-card"><h2>Bật và vị trí</h2><div class="in">';
	echo '<div class="hh-opt"><label class="hh-sw"><input type="checkbox" name="hh[enabled]" value="1" ' . checked( 1, $o['enabled'], false ) . ' /><i></i></label>';
	echo '<span class="tx"><strong>Bật hiển thị hotline trên header</strong><em>Tắt đi là header sạch, không còn dãy số.</em></span></div>';
	echo '<table class="form-table" role="presentation">';
	echo '<tr><th scope="row" style="width:170px">Vị trí trong header</th><td><select name="hh[slot]">';
	foreach ( $slots as $k => $lab ) {
		echo '<option value="' . esc_attr( $k ) . '" ' . selected( $k, $o['slot'], false ) . '>' . esc_html( $lab ) . '</option>';
	}
	echo '</select><br><span class="description">Đang dùng ô "Thanh trên cùng, bên trái" giống cấu hình cũ. Chọn "Không tự chèn" nếu muốn tự đặt bằng shortcode.</span></td></tr>';
	echo '<tr><th scope="row">Căn hàng</th><td><select name="hh[align]">';
	foreach ( array( 'flex-end' => 'Canh phải', 'center' => 'Canh giữa', 'flex-start' => 'Canh trái' ) as $k => $lab ) {
		echo '<option value="' . esc_attr( $k ) . '" ' . selected( $k, $o['align'], false ) . '>' . esc_html( $lab ) . '</option>';
	}
	echo '</select></td></tr>';
	echo '</table></div></div>';

	echo '<div class="hh-card"><h2>Danh sách số (tối đa 4)</h2><div class="in">';
	echo '<table class="hh-tb"><tr><th style="width:52px">Bật</th><th style="width:230px">Tên hiển thị</th><th style="width:160px">Số điện thoại</th><th>Link khi bấm</th><th style="width:60px">Màu số</th></tr>';
	for ( $i = 0; $i < 4; $i++ ) {
		$it = isset( $o['items'][ $i ] ) ? $o['items'][ $i ] : array( 'on' => 0, 'label' => '', 'phone' => '', 'link' => '', 'color' => '#333333' );
		echo '<tr>';
		echo '<td><label class="hh-sw" style="transform:scale(.85)"><input type="checkbox" name="hh[items][' . $i . '][on]" value="1" ' . checked( 1, isset( $it['on'] ) ? $it['on'] : 0, false ) . ' /><i></i></label></td>';
		echo '<td><input type="text" name="hh[items][' . $i . '][label]" value="' . esc_attr( isset( $it['label'] ) ? $it['label'] : '' ) . '" /></td>';
		echo '<td><input type="text" name="hh[items][' . $i . '][phone]" value="' . esc_attr( isset( $it['phone'] ) ? $it['phone'] : '' ) . '" /></td>';
		echo '<td><input type="url" name="hh[items][' . $i . '][link]" value="' . esc_attr( isset( $it['link'] ) ? $it['link'] : '' ) . '" placeholder="Để trống = bấm vào gọi luôn" /></td>';
		echo '<td><input type="color" name="hh[items][' . $i . '][color]" value="' . esc_attr( isset( $it['color'] ) ? $it['color'] : '#333333' ) . '" /></td>';
		echo '</tr>';
	}
	echo '</table></div></div>';

	echo '<div class="hh-card"><h2>Định dạng chữ</h2><div class="in"><table class="form-table" role="presentation">';
	echo '<tr><th scope="row" style="width:220px">Cỡ chữ tên (px)</th><td><input type="number" name="hh[label_size]" value="' . esc_attr( $o['label_size'] ) . '" min="8" max="20" /></td></tr>';
	echo '<tr><th scope="row">Màu chữ tên</th><td><input type="color" name="hh[label_color]" value="' . esc_attr( $o['label_color'] ) . '" /></td></tr>';
	echo '<tr><th scope="row">Cỡ số trên máy tính (px)</th><td><input type="number" name="hh[phone_size]" value="' . esc_attr( $o['phone_size'] ) . '" min="12" max="40" /></td></tr>';
	echo '<tr><th scope="row">Cỡ số trên điện thoại (px)</th><td><input type="number" name="hh[phone_size_m]" value="' . esc_attr( $o['phone_size_m'] ) . '" min="10" max="30" /></td></tr>';
	echo '</table></div></div>';

	echo '<div class="hh-card"><h2>Đường ngăn cách và khoảng cách</h2><div class="in">';
	echo '<div class="hh-opt"><label class="hh-sw"><input type="checkbox" name="hh[divider]" value="1" ' . checked( 1, $o['divider'], false ) . ' /><i></i></label>';
	echo '<span class="tx"><strong>Hiện đường kẻ dọc giữa các số</strong><em>Kẻ mảnh phân tách từng người, bỏ tick nếu muốn liền mạch.</em></span></div>';
	echo '<table class="form-table" role="presentation">';
	echo '<tr><th scope="row" style="width:220px">Màu đường kẻ</th><td><input type="color" name="hh[divider_color]" value="' . esc_attr( $o['divider_color'] ) . '" /></td></tr>';
	echo '<tr><th scope="row">Khoảng cách trên máy tính (px)</th><td><input type="number" name="hh[gap]" value="' . esc_attr( $o['gap'] ) . '" min="4" max="60" /></td></tr>';
	echo '<tr><th scope="row">Khoảng cách trên điện thoại (px)</th><td><input type="number" name="hh[gap_m]" value="' . esc_attr( $o['gap_m'] ) . '" min="2" max="40" /></td></tr>';
	echo '</table></div></div>';

	echo '<div class="hh-card"><h2>Xem trước</h2><div class="in">';
	echo '<div class="hh-prev">' . smlife_hh_render() . '</div>'; // phpcs:ignore
	echo '<p class="description" style="margin-top:10px">Bản xem trước dùng đúng mã HTML và CSS đang chạy ngoài web. Lưu rồi tải lại trang để thấy thay đổi.</p>';
	echo '</div></div>';

	echo '<div class="hh-note"><b>Ghi chú</b><ul>';
	echo '<li>Ô <b>Link khi bấm</b> để trống thì bấm vào số sẽ gọi điện luôn. Điền link Zalo nếu muốn mở Zalo.</li>';
	echo '<li>Có thể đặt thêm ở bất kỳ đâu bằng shortcode <code>[smlife_hotline]</code>.</li>';
	echo '<li>Snippet này thay cho 2 snippet cũ, nhớ tắt "HTML - Số Hotline top menu Website" và "CSS - Giao diện Header website".</li>';
	echo '</ul></div>';

	submit_button( 'Lưu cài đặt' );
	echo '</form></div>';
}

add_action( 'admin_post_smlife_hh_save', function () {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( 'Không đủ quyền.' );
	}
	check_admin_referer( 'smlife_hh_save', 'smlife_hh_nonce' );
	$in    = isset( $_POST['hh'] ) && is_array( $_POST['hh'] ) ? wp_unslash( $_POST['hh'] ) : array();
	$def   = smlife_hh_opt();
	$slots = smlife_hh_slots();

	$items = array();
	$rows  = isset( $in['items'] ) && is_array( $in['items'] ) ? $in['items'] : array();
	for ( $i = 0; $i < 4; $i++ ) {
		$r   = isset( $rows[ $i ] ) && is_array( $rows[ $i ] ) ? $rows[ $i ] : array();
		$col = isset( $r['color'] ) ? sanitize_hex_color( $r['color'] ) : '';
		$items[] = array(
			'on'    => empty( $r['on'] ) ? 0 : 1,
			'label' => isset( $r['label'] ) ? sanitize_text_field( $r['label'] ) : '',
			'phone' => isset( $r['phone'] ) ? sanitize_text_field( $r['phone'] ) : '',
			'link'  => isset( $r['link'] ) ? esc_url_raw( $r['link'] ) : '',
			'color' => $col ? $col : '#333333',
		);
	}

	$hexf = function ( $k, $d ) use ( $in ) {
		$v = isset( $in[ $k ] ) ? sanitize_hex_color( $in[ $k ] ) : '';
		return $v ? $v : $d;
	};

	$new = array(
		'enabled'      => empty( $in['enabled'] ) ? 0 : 1,
		'slot'         => ( isset( $in['slot'] ) && isset( $slots[ $in['slot'] ] ) ) ? $in['slot'] : 'topbar_left',
		'align'        => ( isset( $in['align'] ) && in_array( $in['align'], array( 'flex-start', 'center', 'flex-end' ), true ) ) ? $in['align'] : 'flex-end',
		'label_size'   => isset( $in['label_size'] ) ? max( 8, min( 20, absint( $in['label_size'] ) ) ) : 10,
		'label_color'  => $hexf( 'label_color', $def['label_color'] ),
		'phone_size'   => isset( $in['phone_size'] ) ? max( 12, min( 40, absint( $in['phone_size'] ) ) ) : 22,
		'phone_size_m' => isset( $in['phone_size_m'] ) ? max( 10, min( 30, absint( $in['phone_size_m'] ) ) ) : 14,
		'divider'      => empty( $in['divider'] ) ? 0 : 1,
		'divider_color'=> $hexf( 'divider_color', $def['divider_color'] ),
		'gap'          => isset( $in['gap'] ) ? max( 4, min( 60, absint( $in['gap'] ) ) ) : 20,
		'gap_m'        => isset( $in['gap_m'] ) ? max( 2, min( 40, absint( $in['gap_m'] ) ) ) : 10,
		'items'        => $items,
	);
	update_option( 'smlife_hh', $new );
	/* 1.24.0: truoc day luu xong khong xoa cache, so moi chi len tung trang. Gio vao danh sach cho dem */
	smlife_xoa_cache( 'Hotline Header' );
	wp_safe_redirect( admin_url( 'admin.php?page=smlife-hotline-header&updated=1' ) );
	exit;
} );
