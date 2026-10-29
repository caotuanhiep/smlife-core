<?php
/**
 * SMLIFE Core - Module: Canh duong dan (1.27.0)
 *
 * Dem 29/10/2026 ca smlife.vn va socozy.vn cung bi: sitemap_index.xml tra 404,
 * trang danh muc san pham bi chuyen ve trang chu hoac mo nham chuyen muc blog.
 * Nguyen nhan: bang quy tac duong dan cua WordPress (option rewrite_rules) bi tao
 * lai trong luc cac quy tac cua Rank Math (sitemap, bo /danh-muc/) chua nap.
 * Tat bat Rank Math la WordPress tao lai bang dung, nen het.
 *
 * Module nay:
 *  1. Moi 10 phut, trong luot cron (khong lam cham khach), goi thu that
 *     sitemap_index.xml va mot trang danh muc san pham (kem tham so vuot cache).
 *  2. Hong thi bat co. Luot truy cap thuong ke tiep (luc Rank Math da nap du
 *     quy tac) tao lai bang (giong bam Luu o Duong dan tinh) va xoa cache.
 *     Cron ke tiep kiem lai: on thi gui email bao da sua; sua 2 lan van hong
 *     thi dung tu sua va gui email bao xu ly tay.
 *  3. Ghi lai moi lan bang quy tac bi tao lai: gio, kieu truy cap, URL, plugin
 *     nao goi. Lan sau bi hong la biet thu pham.
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

function smlife_cdd_opt() {
	$def = array( 'enabled' => 1, 'email' => 1 );
	$s   = get_option( 'smlife_cdd', array() );
	return is_array( $s ) ? array_merge( $def, $s ) : $def;
}

/* ---------- Ghi nhat ky ---------- */
function smlife_cdd_ghi( $loai, $noi ) {
	$log = get_option( 'smlife_cdd_log', array() );
	$log = is_array( $log ) ? $log : array();
	array_unshift( $log, array( 't' => time(), 'loai' => $loai, 'noi' => (string) $noi ) );
	update_option( 'smlife_cdd_log', array_slice( $log, 0, 40 ), false );
}

/* Kieu truy cap hien tai, de biet ai tao lai bang quy tac */
function smlife_cdd_ngu_canh() {
	$k = array();
	if ( function_exists( 'wp_doing_cron' ) && wp_doing_cron() ) { $k[] = 'cron'; }
	if ( defined( 'WP_CLI' ) && WP_CLI ) { $k[] = 'wp-cli'; }
	if ( function_exists( 'wp_doing_ajax' ) && wp_doing_ajax() ) { $k[] = 'ajax'; }
	if ( defined( 'REST_REQUEST' ) && REST_REQUEST ) { $k[] = 'rest'; }
	if ( is_admin() && ! in_array( 'ajax', $k, true ) ) { $k[] = 'admin'; }
	if ( ! $k ) { $k[] = 'frontend'; }
	$uri = isset( $_SERVER['REQUEST_URI'] ) ? substr( sanitize_text_field( wp_unslash( $_SERVER['REQUEST_URI'] ) ), 0, 150 ) : '';
	/* Plugin/theme nao nam trong chuoi goi ham */
	$ai = array();
	foreach ( debug_backtrace( DEBUG_BACKTRACE_IGNORE_ARGS, 40 ) as $f ) { // phpcs:ignore
		if ( empty( $f['file'] ) ) { continue; }
		if ( preg_match( '#/(plugins|mu-plugins|themes)/([^/]+)/#', str_replace( '\\', '/', $f['file'] ), $m ) && 'smlife-core' !== $m[2] ) {
			$ai[ $m[1] . '/' . $m[2] ] = 1;
		}
	}
	return implode( '+', $k ) . ' ' . $uri . ( $ai ? ' | goi tu: ' . implode( ', ', array_keys( $ai ) ) : '' );
}

/* Moi lan option rewrite_rules bi ghi/xoa thi ghi lai (khong ghi khi chinh module nay sua) */
$GLOBALS['smlife_cdd_dang_sua'] = false;
foreach ( array( 'update_option_rewrite_rules', 'add_option_rewrite_rules', 'delete_option_rewrite_rules' ) as $smlife_cdd_hook ) {
	add_action( $smlife_cdd_hook, function () use ( $smlife_cdd_hook ) {
		if ( ! empty( $GLOBALS['smlife_cdd_dang_sua'] ) ) { return; }
		$lan = get_option( 'smlife_cdd_viet', array() );
		$lan = is_array( $lan ) ? $lan : array();
		/* Plugin nao tao lai bang lien tuc thi chi ghi 1 lan moi 30 giay cho nhe */
		if ( ! empty( $lan[0]['t'] ) && time() - (int) $lan[0]['t'] < 30 ) { return; }
		array_unshift( $lan, array( 't' => time(), 'kieu' => str_replace( '_option_rewrite_rules', '', $smlife_cdd_hook ), 'nc' => smlife_cdd_ngu_canh() ) );
		update_option( 'smlife_cdd_viet', array_slice( $lan, 0, 25 ), false );
	} );
}

/* ---------- Kiem tra ---------- */
/* Lay 1 danh muc san pham co hang de thu (luu lai 1 ngay) */
function smlife_cdd_term_thu() {
	$c = get_transient( 'smlife_cdd_term' );
	if ( is_array( $c ) && ! empty( $c['path'] ) ) { return $c; }
	if ( ! taxonomy_exists( 'product_cat' ) ) { return array(); }
	$ts = get_terms( array( 'taxonomy' => 'product_cat', 'hide_empty' => true, 'orderby' => 'count', 'order' => 'DESC', 'number' => 1 ) );
	if ( is_wp_error( $ts ) || empty( $ts ) ) { return array(); }
	$link = get_term_link( $ts[0] );
	if ( is_wp_error( $link ) ) { return array(); }
	$path = (string) wp_parse_url( $link, PHP_URL_PATH );
	$home = (string) wp_parse_url( home_url( '/' ), PHP_URL_PATH );
	if ( '' !== $home && '/' !== $home && 0 === strpos( $path, $home ) ) { $path = substr( $path, strlen( $home ) ); }
	$c = array( 'slug' => $ts[0]->slug, 'path' => trim( $path, '/' ), 'link' => $link );
	set_transient( 'smlife_cdd_term', $c, DAY_IN_SECONDS );
	return $c;
}

function smlife_cdd_rm_sitemap_on() {
	if ( class_exists( '\RankMath\Helper' ) && method_exists( '\RankMath\Helper', 'is_module_active' ) ) {
		return (bool) \RankMath\Helper::is_module_active( 'sitemap' );
	}
	return false;
}

/* Goi thu URL that (nhu Google goi). Them tham so de vuot cache trang.
   Tra ve mang loi (rong = on), null = khong goi duoc (mang loi, bo qua lan nay). */
function smlife_cdd_kiem() {
	if ( '' === (string) get_option( 'permalink_structure' ) ) { return array(); }
	$tests = array();
	if ( smlife_cdd_rm_sitemap_on() ) {
		$tests[] = array( 'ten' => 'sitemap_index.xml', 'url' => home_url( '/sitemap_index.xml' ), 'can' => 'sitemapindex' );
	}
	$t = smlife_cdd_term_thu();
	if ( $t ) {
		$tests[] = array( 'ten' => 'danh mục /' . $t['path'], 'url' => $t['link'], 'can' => 'tax-product_cat' );
	}
	$loi = array();
	foreach ( $tests as $x ) {
		$u = add_query_arg( 'smlife_cdd', time(), $x['url'] );
		$r = wp_remote_get( $u, array( 'timeout' => 15, 'redirection' => 3, 'sslverify' => false, 'user-agent' => 'SMLIFE-Core-Canh-Duong-Dan', 'headers' => array( 'Cache-Control' => 'no-cache' ) ) );
		if ( is_wp_error( $r ) ) { return null; }
		$code = (int) wp_remote_retrieve_response_code( $r );
		if ( in_array( $code, array( 403, 429, 500, 502, 503, 504, 520, 521, 522, 524 ), true ) ) { return null; } /* tuong lua / may chu ban: khong ket luan */
		$body = (string) wp_remote_retrieve_body( $r );
		if ( 200 !== $code ) {
			$loi[] = $x['ten'] . ' trả mã ' . $code;
		} elseif ( false === strpos( $body, $x['can'] ) ) {
			$loi[] = $x['ten'] . ' mở ra sai trang (bị chuyển về trang chủ, sang trang khác hoặc trang blog)';
		}
	}
	return $loi;
}

/* Tao lai bang quy tac. Chi goi o luot truy cap thuong (Rank Math da nap du quy tac). */
function smlife_cdd_sua( $ly_do ) {
	$GLOBALS['smlife_cdd_dang_sua'] = true;
	delete_transient( 'smlife_cdd_term' );
	flush_rewrite_rules( false ); /* chi tao lai bang trong database, khong dong toi .htaccess */
	$GLOBALS['smlife_cdd_dang_sua'] = false;
	if ( function_exists( 'rocket_clean_domain' ) ) { rocket_clean_domain(); }
	do_action( 'litespeed_purge_all' );
	smlife_cdd_ghi( 'sua', 'Đã tạo lại bảng đường dẫn. Lý do: ' . $ly_do );
}

function smlife_cdd_mail( $tieu_de, $noi ) {
	$o = smlife_cdd_opt();
	if ( empty( $o['email'] ) || ! smlife_khoa_db( 'cdd_mail', 6 * HOUR_IN_SECONDS ) ) { return; }
	$dong = '';
	foreach ( array_slice( (array) get_option( 'smlife_cdd_viet', array() ), 0, 5 ) as $v ) {
		$dong .= '- ' . wp_date( 'H:i d/m/Y', (int) $v['t'] ) . ' ' . $v['kieu'] . ': ' . $v['nc'] . "\n";
	}
	wp_mail( get_option( 'admin_email' ), '[' . wp_parse_url( home_url(), PHP_URL_HOST ) . '] ' . $tieu_de, $noi . "\n\nCác lần bảng quy tắc bị tạo lại gần nhất:\n" . $dong . "\nXem chi tiết: " . admin_url( 'admin.php?page=smlife-canh-duong-dan' ) );
}

/* 1. Trong cron (khong lam cham khach): goi thu URL moi 10 phut, hong thi bat co */
add_action( 'init', function () {
	$o = smlife_cdd_opt();
	if ( empty( $o['enabled'] ) || ! function_exists( 'wp_doing_cron' ) || ! wp_doing_cron() ) { return; }
	if ( ! smlife_khoa_db( 'cdd_kiem', 9 * MINUTE_IN_SECONDS ) ) { return; }
	$loi = smlife_cdd_kiem();
	if ( null === $loi ) { return; }
	$tt = get_option( 'smlife_cdd_tt', array() );
	$tt = is_array( $tt ) ? $tt : array();
	$lan_sua = isset( $tt['lan_sua'] ) ? (int) $tt['lan_sua'] : 0;
	if ( $loi ) {
		if ( $lan_sua >= 2 ) {
			/* Da tu sua 2 lan van hong: dung tu sua, bao nguoi */
			if ( empty( $tt['bao'] ) ) {
				smlife_cdd_ghi( 'loi', 'Tự tạo lại 2 lần vẫn lỗi: ' . implode( '; ', $loi ) . '. Đã dừng tự sửa, cần xử lý tay.' );
				smlife_cdd_mail( 'Đường dẫn vẫn lỗi, cần xử lý tay', 'SMLIFE Core đã tự tạo lại bảng đường dẫn 2 lần nhưng vẫn lỗi: ' . implode( '; ', $loi ) . ".\nVào Cài đặt > Đường dẫn tĩnh bấm Lưu, hoặc tắt bật Rank Math." );
			}
			$tt = array( 't' => time(), 'loi' => $loi, 'can_sua' => 0, 'lan_sua' => $lan_sua, 'bao' => 1 );
		} else {
			$tt = array( 't' => time(), 'loi' => $loi, 'can_sua' => 1, 'lan_sua' => $lan_sua, 'bao' => 0 );
		}
	} else {
		if ( $lan_sua > 0 ) {
			smlife_cdd_ghi( 'ok', 'Kiểm tra lại sau khi tự sửa: đã bình thường.' );
			smlife_cdd_mail( 'Đã tự sửa lỗi đường dẫn', 'SMLIFE Core phát hiện sitemap hoặc danh mục sản phẩm bị lỗi và đã tự tạo lại bảng đường dẫn. Kiểm tra lại: bình thường.' );
		}
		$tt = array( 't' => time(), 'loi' => array(), 'can_sua' => 0, 'lan_sua' => 0, 'bao' => 0 );
	}
	update_option( 'smlife_cdd_tt', $tt, false );
}, 99 );

/* 2. Luot truy cap thuong ke tiep: co co thi tao lai bang (nhanh, khong goi mang) */
add_action( 'wp_loaded', function () {
	$tt = get_option( 'smlife_cdd_tt', array() );
	if ( empty( $tt['can_sua'] ) ) { return; }
	if ( ( function_exists( 'wp_doing_cron' ) && wp_doing_cron() ) || ( function_exists( 'wp_doing_ajax' ) && wp_doing_ajax() ) || ( defined( 'REST_REQUEST' ) && REST_REQUEST ) || ( defined( 'WP_CLI' ) && WP_CLI ) ) { return; }
	if ( ! smlife_khoa_db( 'cdd_sua', 5 * MINUTE_IN_SECONDS ) ) { return; }
	smlife_cdd_sua( implode( '; ', (array) $tt['loi'] ) );
	$tt['can_sua'] = 0;
	$tt['lan_sua'] = (int) $tt['lan_sua'] + 1;
	update_option( 'smlife_cdd_tt', $tt, false );
}, 99 );

/* ===== Submenu ===== */
add_action( 'admin_menu', function () {
	add_submenu_page( 'smlife-core', 'Canh đường dẫn', 'Canh đường dẫn', 'manage_options', 'smlife-canh-duong-dan', 'smlife_cdd_page' );
}, 12 );

function smlife_cdd_page() {
	if ( ! current_user_can( 'manage_options' ) ) { return; }
	$o   = smlife_cdd_opt();
	$loi = smlife_cdd_kiem();
	$t   = smlife_cdd_term_thu();
	$tt  = get_option( 'smlife_cdd_tt', array() );
	echo '<div class="wrap smlife-wrap"><div class="smc-hd"><h1>Canh đường dẫn</h1>';
	echo '<p>Mỗi 10 phút kiểm tra sitemap của Rank Math và đường dẫn danh mục sản phẩm. Hỏng thì tự tạo lại (giống bấm Lưu ở Đường dẫn tĩnh), xoá cache và gửi email. Đồng thời ghi lại mỗi lần bảng quy tắc bị tạo lại để tìm ra plugin gây lỗi.</p></div>';
	if ( isset( $_GET['updated'] ) ) { echo '<div class="notice notice-success is-dismissible"><p>Đã lưu.</p></div>'; }
	if ( isset( $_GET['sua'] ) ) { echo '<div class="notice notice-' . ( '1' === $_GET['sua'] ? 'success' : 'error' ) . ' is-dismissible"><p>' . ( '1' === $_GET['sua'] ? 'Đã tạo lại bảng quy tắc, kiểm tra đạt.' : 'Đã tạo lại nhưng vẫn lỗi. Thử tắt bật Rank Math.' ) . '</p></div>'; }

	echo '<div class="smlife-card"><h2>Tình trạng ngay lúc này</h2><ul style="list-style:disc;padding-left:20px">';
	echo '<li>Sitemap Rank Math: ' . ( smlife_cdd_rm_sitemap_on() ? '<a href="' . esc_url( home_url( '/sitemap_index.xml' ) ) . '" target="_blank">sitemap_index.xml</a>' : 'module Sitemap của Rank Math đang tắt, bỏ qua' ) . '</li>';
	echo '<li>Danh mục dùng để thử: ' . ( $t ? '<a href="' . esc_url( $t['link'] ) . '" target="_blank">/' . esc_html( $t['path'] ) . '/</a>' : 'chưa có danh mục sản phẩm' ) . '</li>';
	if ( null === $loi ) {
		echo '<li><strong>Không gọi thử được (mạng hoặc tường lửa chặn máy chủ tự gọi chính nó). Module sẽ thử lại ở lượt sau.</strong></li>';
	} else {
		echo '<li><strong>' . ( $loi ? '<span style="color:#b32d2e">Lỗi: ' . esc_html( implode( '; ', $loi ) ) . '</span>' : '<span style="color:#1a7f37">Bình thường</span>' ) . '</strong></li>';
	}
	echo '<li>Lần kiểm tra tự động gần nhất: ' . ( ! empty( $tt['t'] ) ? esc_html( wp_date( 'H:i d/m/Y', (int) $tt['t'] ) ) : 'chưa có (cần WP-Cron chạy)' ) . '</li></ul>';
	echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '"><input type="hidden" name="action" value="smlife_cdd_sua" />';
	wp_nonce_field( 'smlife_cdd_sua', 'smlife_cdd_n2' );
	submit_button( 'Tạo lại bảng đường dẫn ngay', 'secondary', 'submit', false );
	echo '</form></div>';

	echo '<div class="smlife-card"><form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '"><input type="hidden" name="action" value="smlife_cdd_save" />';
	wp_nonce_field( 'smlife_cdd_save', 'smlife_cdd_n' );
	echo '<table class="form-table" role="presentation">';
	echo '<tr><th scope="row">Tự kiểm tra và sửa</th><td><label><input type="checkbox" name="cd[enabled]" value="1" ' . checked( 1, $o['enabled'], false ) . ' /> Bật</label></td></tr>';
	echo '<tr><th scope="row">Gửi email</th><td><label><input type="checkbox" name="cd[email]" value="1" ' . checked( 1, $o['email'], false ) . ' /> Gửi tới ' . esc_html( get_option( 'admin_email' ) ) . ' khi tự sửa (tối đa 1 email mỗi 6 giờ)</label></td></tr>';
	echo '</table>';
	submit_button( 'Lưu' );
	echo '</form></div>';

	$log = get_option( 'smlife_cdd_log', array() );
	echo '<div class="smlife-card"><h2>Nhật ký tự sửa</h2>';
	if ( ! $log ) { echo '<p>Chưa có lần nào.</p>'; } else {
		echo '<table class="widefat striped"><tbody>';
		foreach ( (array) $log as $l ) { echo '<tr><td style="white-space:nowrap">' . esc_html( wp_date( 'H:i d/m/Y', (int) $l['t'] ) ) . '</td><td>' . esc_html( $l['noi'] ) . '</td></tr>'; }
		echo '</tbody></table>';
	}
	echo '</div>';

	$viet = get_option( 'smlife_cdd_viet', array() );
	echo '<div class="smlife-card"><h2>Các lần bảng quy tắc bị tạo lại</h2><p class="description">Lần nào ngay trước lúc hỏng thì cột cuối cho biết plugin hoặc kiểu truy cập đã gây ra.</p>';
	if ( ! $viet ) { echo '<p>Chưa ghi nhận lần nào.</p>'; } else {
		echo '<table class="widefat striped"><tbody>';
		foreach ( (array) $viet as $v ) { echo '<tr><td style="white-space:nowrap">' . esc_html( wp_date( 'H:i:s d/m/Y', (int) $v['t'] ) ) . '</td><td>' . esc_html( $v['kieu'] ) . '</td><td>' . esc_html( $v['nc'] ) . '</td></tr>'; }
		echo '</tbody></table>';
	}
	echo '</div></div>';
}

add_action( 'admin_post_smlife_cdd_save', function () {
	if ( ! current_user_can( 'manage_options' ) ) { wp_die( 'Không đủ quyền.' ); }
	check_admin_referer( 'smlife_cdd_save', 'smlife_cdd_n' );
	$in = isset( $_POST['cd'] ) && is_array( $_POST['cd'] ) ? wp_unslash( $_POST['cd'] ) : array();
	update_option( 'smlife_cdd', array( 'enabled' => empty( $in['enabled'] ) ? 0 : 1, 'email' => empty( $in['email'] ) ? 0 : 1 ) );
	wp_safe_redirect( admin_url( 'admin.php?page=smlife-canh-duong-dan&updated=1' ) );
	exit;
} );

add_action( 'admin_post_smlife_cdd_sua', function () {
	if ( ! current_user_can( 'manage_options' ) ) { wp_die( 'Không đủ quyền.' ); }
	check_admin_referer( 'smlife_cdd_sua', 'smlife_cdd_n2' );
	smlife_cdd_sua( 'Bấm tay trong trang Canh đường dẫn' );
	update_option( 'smlife_cdd_tt', array( 't' => time(), 'loi' => array(), 'can_sua' => 0, 'lan_sua' => 0, 'bao' => 0 ), false );
	$con = smlife_cdd_kiem();
	wp_safe_redirect( admin_url( 'admin.php?page=smlife-canh-duong-dan&sua=' . ( is_array( $con ) && ! $con ? '1' : '0' ) ) );
	exit;
} );
