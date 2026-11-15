<?php
/**
 * SMLIFE Core - Module: Zalo ZNS xac nhan don hang
 *
 * Khach dat hang qua form Mua ngay hoac qua gio hang thi gui ngay mot tin Zalo
 * tu OA SMLIFE toi so dien thoai cua khach: "SMLIFE da nhan don, se lien he som,
 * can gap goi hotline". Gui bang ZNS (tin theo mau Zalo da duyet) nen khach
 * chua quan tam OA van nhan duoc.
 *
 * Luong ket noi (OAuth v4 cua Zalo, co PKCE):
 * 1. Quan tri bam "Ket noi Zalo OA" -> tao code_verifier + state, chuyen sang
 *    trang cap quyen cua Zalo.
 * 2. Zalo chuyen ve /smlife-zns-callback?code=...&oa_id=...&state=...
 * 3. Doi code lay access token (song 25 gio) + refresh token (3 thang, dung 1 lan).
 * 4. Moi gio kiem tra, token sap het thi tu lam moi. Refresh token chi dung duoc
 *    mot lan nen co khoa DB, hai tien trinh khong bao gio cung lam moi.
 *
 * Bao ve chi phi: che do Chi gui so thu, gioi han tin moi ngay, moi so toi da
 * 3 tin/ngay, moi don chi gui mot lan.
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

define( 'SMLIFE_ZNS_OAUTH', 'https://oauth.zaloapp.com/v4/oa/' );
define( 'SMLIFE_ZNS_API', 'https://business.openapi.zalo.me/message/template' );
define( 'SMLIFE_ZNS_CB', '/smlife-zns-callback' );

function smlife_zns_opt() {
	$def = array(
		'che_do'      => 'tat',          /* tat | thu | bat */
		'app_id'      => '',
		'secret'      => '',
		'template_id' => '',
		'so_thu'      => '',
		'k_ten'       => 'customer_name',
		'k_ma'        => 'order_code',
		'k_sp'        => 'product_name',
		'gui_qo'      => 1,
		'gui_wc'      => 1,
		'toi_da_ngay' => 300,
	);
	$s = get_option( 'smlife_zns', array() );
	return is_array( $s ) ? array_merge( $def, $s ) : $def;
}

function smlife_zns_cb_url() {
	return home_url( SMLIFE_ZNS_CB );
}

/* So dien thoai ve dang 84xxxxxxxxx (Zalo nhan dang nay). Sai thi tra rong. */
function smlife_zns_sdt( $p ) {
	$p = preg_replace( '/\D/', '', (string) $p );
	if ( 0 === strpos( $p, '0' ) ) {
		$p = '84' . substr( $p, 1 );
	} elseif ( 0 !== strpos( $p, '84' ) ) {
		$p = '84' . $p;
	}
	return preg_match( '/^84[35789]\d{8}$/', $p ) ? $p : '';
}

/* Che so trong nhat ky: 84914***179 */
function smlife_zns_che( $p ) {
	$p = (string) $p;
	return strlen( $p ) > 7 ? substr( $p, 0, 5 ) . '***' . substr( $p, -3 ) : $p;
}

function smlife_zns_cat( $s, $n ) {
	$s = trim( preg_replace( '/\s+/u', ' ', wp_strip_all_tags( (string) $s ) ) );
	return function_exists( 'mb_substr' ) ? mb_substr( $s, 0, $n ) : substr( $s, 0, $n );
}

/* ===== Nhat ky 50 dong gan nhat ===== */
function smlife_zns_log( $loai, $ref, $sdt, $kq, $ghi ) {
	$l = get_option( 'smlife_zns_log', array() );
	if ( ! is_array( $l ) ) {
		$l = array();
	}
	array_unshift( $l, array(
		't'   => time(),
		'l'   => (string) $loai,
		'r'   => (string) $ref,
		's'   => smlife_zns_che( $sdt ),
		'k'   => $kq ? 1 : 0,
		'g'   => smlife_zns_cat( $ghi, 200 ),
	) );
	update_option( 'smlife_zns_log', array_slice( $l, 0, 50 ), false );
}

/* Bao loi qua email, toi da 1 thu moi 12 gio */
function smlife_zns_bao_loi( $noi_dung ) {
	if ( function_exists( 'smlife_gioi_han' ) && ! smlife_gioi_han( 'zns_mail', 'loi', 1, 12 * HOUR_IN_SECONDS ) ) {
		return;
	}
	wp_mail( get_option( 'admin_email' ), '[SMLIFE Core] Zalo ZNS gặp lỗi', $noi_dung . "\n\nVào SMLIFE Core > Zalo ZNS để xem nhật ký và kết nối lại nếu cần." );
}

/* ===== Token ===== */
function smlife_zns_tok() {
	$t = function_exists( 'smlife_opt_db' ) ? smlife_opt_db( 'smlife_zns_tok', array() ) : get_option( 'smlife_zns_tok', array() );
	return is_array( $t ) ? $t : array();
}

/* Goi may chu OAuth cua Zalo. Tra mang token hoac WP_Error. */
function smlife_zns_goi_oauth( $body ) {
	$o = smlife_zns_opt();
	if ( '' === $o['app_id'] || '' === $o['secret'] ) {
		return new WP_Error( 'thieu', 'Chưa nhập App ID hoặc Khóa bí mật.' );
	}
	$body['app_id'] = $o['app_id'];
	$r = wp_remote_post( SMLIFE_ZNS_OAUTH . 'access_token', array(
		'timeout' => 15,
		'headers' => array(
			'secret_key'   => $o['secret'],
			'Content-Type' => 'application/x-www-form-urlencoded',
		),
		'body'    => $body,
	) );
	if ( is_wp_error( $r ) ) {
		return $r;
	}
	$j = json_decode( (string) wp_remote_retrieve_body( $r ), true );
	if ( ! is_array( $j ) || empty( $j['access_token'] ) ) {
		$m = is_array( $j ) ? ( $j['error_description'] ?? ( $j['error_name'] ?? ( $j['message'] ?? '' ) ) ) : '';
		$c = is_array( $j ) && isset( $j['error'] ) ? (string) $j['error'] : 'rong';
		return new WP_Error( 'zalo_' . $c, '' !== $m ? $m : 'Zalo không trả token (mã ' . $c . ').' );
	}
	return $j;
}

function smlife_zns_luu_tok( $j, $oa_id = null ) {
	$cu = smlife_zns_tok();
	update_option( 'smlife_zns_tok', array(
		'access'  => (string) $j['access_token'],
		'refresh' => (string) ( $j['refresh_token'] ?? ( $cu['refresh'] ?? '' ) ),
		'het'     => time() + max( 600, (int) ( $j['expires_in'] ?? 90000 ) ),
		'oa_id'   => null !== $oa_id ? (string) $oa_id : (string) ( $cu['oa_id'] ?? '' ),
		'luc'     => time(),
		'loi'     => '',
	), false );
}

/* Lay access token con han; sap het thi lam moi. $ep = bat buoc lam moi. */
function smlife_zns_lay_token( $ep = false ) {
	$t = smlife_zns_tok();
	if ( ! $ep && ! empty( $t['access'] ) && (int) ( $t['het'] ?? 0 ) > time() + 120 ) {
		return $t['access'];
	}
	if ( empty( $t['refresh'] ) ) {
		return new WP_Error( 'chua_ket_noi', 'Chưa kết nối Zalo OA.' );
	}
	/* Refresh token dung 1 lan: chi mot tien trinh duoc lam moi */
	if ( function_exists( 'smlife_khoa_db' ) && ! smlife_khoa_db( 'zns_rf', 30 ) ) {
		sleep( 2 );
		$t2 = smlife_zns_tok();
		if ( ! empty( $t2['access'] ) && ( $t2['access'] !== ( $t['access'] ?? '' ) ) ) {
			return $t2['access'];
		}
		return new WP_Error( 'dang_lam_moi', 'Đang làm mới token, thử lại sau.' );
	}
	$j = smlife_zns_goi_oauth( array(
		'refresh_token' => $t['refresh'],
		'grant_type'    => 'refresh_token',
	) );
	if ( is_wp_error( $j ) ) {
		$t['loi'] = $j->get_error_message();
		update_option( 'smlife_zns_tok', $t, false );
		if ( function_exists( 'smlife_mo_khoa_db' ) ) {
			smlife_mo_khoa_db( 'zns_rf' );
		}
		smlife_zns_log( 'token', '', '', false, 'Làm mới token lỗi: ' . $j->get_error_message() );
		smlife_zns_bao_loi( 'Không làm mới được token Zalo: ' . $j->get_error_message() . "\nTin xác nhận đơn hàng đang tạm ngừng gửi." );
		return $j;
	}
	smlife_zns_luu_tok( $j );
	if ( function_exists( 'smlife_mo_khoa_db' ) ) {
		smlife_mo_khoa_db( 'zns_rf' );
	}
	return (string) $j['access_token'];
}

/* ===== Gui tin =====
 * $du_lieu: mang 3 gia tri ten / ma / sp. Tra array( ok, ghi_chu ). */
function smlife_zns_gui( $sdt_goc, $du_lieu, $ref, $loai, $la_thu = false ) {
	$o   = smlife_zns_opt();
	$sdt = smlife_zns_sdt( $sdt_goc );
	if ( ! $la_thu && 'tat' === $o['che_do'] ) {
		return array( false, 'Đang tắt' );
	}
	if ( '' === $sdt ) {
		smlife_zns_log( $loai, $ref, $sdt_goc, false, 'Số điện thoại không hợp lệ' );
		return array( false, 'Số điện thoại không hợp lệ' );
	}
	if ( '' === trim( (string) $o['template_id'] ) ) {
		smlife_zns_log( $loai, $ref, $sdt, false, 'Chưa nhập Template ID' );
		return array( false, 'Chưa nhập Template ID' );
	}
	if ( ! $la_thu && 'thu' === $o['che_do'] && $sdt !== smlife_zns_sdt( $o['so_thu'] ) ) {
		return array( false, 'Chế độ chỉ gửi số thử' );
	}
	if ( ! $la_thu && function_exists( 'smlife_gioi_han' ) ) {
		if ( ! smlife_gioi_han( 'zns_ngay', wp_date( 'Ymd' ), max( 1, (int) $o['toi_da_ngay'] ), DAY_IN_SECONDS ) ) {
			smlife_zns_log( $loai, $ref, $sdt, false, 'Đã chạm giới hạn tin trong ngày' );
			return array( false, 'Đã chạm giới hạn tin trong ngày' );
		}
		if ( ! smlife_gioi_han( 'zns_sdt', $sdt, 3, DAY_IN_SECONDS ) ) {
			smlife_zns_log( $loai, $ref, $sdt, false, 'Số này đã nhận 3 tin hôm nay' );
			return array( false, 'Số này đã nhận 3 tin hôm nay' );
		}
	}

	$data = array();
	$keys = array( 'ten' => array( $o['k_ten'], 30 ), 'ma' => array( $o['k_ma'], 30 ), 'sp' => array( $o['k_sp'], 100 ) );
	foreach ( $keys as $k => $cf ) {
		$ten_truong = sanitize_key( (string) $cf[0] );
		if ( '' === $ten_truong || ! isset( $du_lieu[ $k ] ) ) {
			continue;
		}
		$v = smlife_zns_cat( $du_lieu[ $k ], $cf[1] );
		$data[ $ten_truong ] = '' !== $v ? $v : '-';
	}
	$body = array(
		'phone'         => $sdt,
		'template_id'   => trim( (string) $o['template_id'] ),
		'template_data' => $data,
		'tracking_id'   => substr( preg_replace( '/[^A-Za-z0-9_-]/', '', (string) $ref ), 0, 48 ),
	);

	$thu_lai = false;
	for ( $lan = 0; $lan < 2; $lan++ ) {
		$tok = smlife_zns_lay_token( $thu_lai );
		if ( is_wp_error( $tok ) ) {
			smlife_zns_log( $loai, $ref, $sdt, false, $tok->get_error_message() );
			return array( false, $tok->get_error_message() );
		}
		$r = wp_remote_post( SMLIFE_ZNS_API, array(
			'timeout' => 8,
			'headers' => array(
				'access_token' => $tok,
				'Content-Type' => 'application/json',
			),
			'body'    => wp_json_encode( $body ),
		) );
		if ( is_wp_error( $r ) ) {
			smlife_zns_log( $loai, $ref, $sdt, false, 'Lỗi mạng: ' . $r->get_error_message() );
			return array( false, 'Lỗi mạng: ' . $r->get_error_message() );
		}
		$j   = json_decode( (string) wp_remote_retrieve_body( $r ), true );
		$err = is_array( $j ) && isset( $j['error'] ) ? (int) $j['error'] : -9999;
		if ( 0 === $err ) {
			$mid = $j['data']['msg_id'] ?? '';
			smlife_zns_log( $loai, $ref, $sdt, true, 'Đã gửi' . ( $mid ? ' (msg ' . $mid . ')' : '' ) );
			return array( true, 'Đã gửi' );
		}
		$msg = is_array( $j ) && ! empty( $j['message'] ) ? (string) $j['message'] : 'Zalo trả lỗi không rõ';
		/* Token bi Zalo tu choi: lam moi bat buoc roi gui lai dung mot lan */
		if ( ! $thu_lai && ( -124 === $err || false !== stripos( $msg, 'access token' ) || false !== stripos( $msg, 'access_token' ) ) ) {
			$thu_lai = true;
			continue;
		}
		smlife_zns_log( $loai, $ref, $sdt, false, 'Lỗi ' . $err . ': ' . $msg );
		return array( false, 'Lỗi ' . $err . ': ' . $msg );
	}
	return array( false, 'Không gửi được' );
}

/* ===== Gan vao form Mua ngay ===== */
add_action( 'smlife_qo_da_gui', function ( $extra, $ma ) {
	$o = smlife_zns_opt();
	if ( 'tat' === $o['che_do'] || empty( $o['gui_qo'] ) || ! is_array( $extra ) ) {
		return;
	}
	if ( get_transient( 'smlife_zns_qo_' . md5( (string) $ma ) ) ) {
		return;
	}
	set_transient( 'smlife_zns_qo_' . md5( (string) $ma ), 1, DAY_IN_SECONDS );
	$sp = isset( $extra['ghichu'] ) && '' !== trim( (string) $extra['ghichu'] ) ? $extra['ghichu'] : 'Sản phẩm nội thất';
	smlife_zns_gui( $extra['sdt'] ?? '', array(
		'ten' => $extra['ten'] ?? '',
		'ma'  => $ma,
		'sp'  => $sp,
	), $ma, 'mua-ngay' );
}, 10, 2 );

/* ===== Gan vao don WooCommerce ===== */
function smlife_zns_don( $order ) {
	if ( is_numeric( $order ) && function_exists( 'wc_get_order' ) ) {
		$order = wc_get_order( $order );
	}
	if ( ! is_object( $order ) || ! method_exists( $order, 'get_meta' ) ) {
		return;
	}
	$o = smlife_zns_opt();
	if ( 'tat' === $o['che_do'] || empty( $o['gui_wc'] ) ) {
		return;
	}
	/* Moi don mot lan: danh dau truoc khi gui de hai hook khong gui doi */
	if ( $order->get_meta( '_smlife_zns' ) ) {
		return;
	}
	$order->update_meta_data( '_smlife_zns', 'dang-gui' );
	$order->save();

	$ten = trim( $order->get_billing_last_name() . ' ' . $order->get_billing_first_name() );
	$sp  = '';
	$n   = 0;
	foreach ( $order->get_items() as $item ) {
		if ( '' === $sp ) {
			$sp = $item->get_name();
		}
		$n++;
	}
	if ( $n > 1 ) {
		$sp .= ' và ' . ( $n - 1 ) . ' sản phẩm khác';
	}
	$ma = (string) $order->get_order_number();
	$kq = smlife_zns_gui( $order->get_billing_phone(), array(
		'ten' => '' !== $ten ? $ten : 'Quý khách',
		'ma'  => $ma,
		'sp'  => '' !== $sp ? $sp : 'Đơn hàng nội thất',
	), 'WC-' . $ma, 'gio-hang' );
	$order->update_meta_data( '_smlife_zns', $kq[0] ? 'da-gui' : 'loi: ' . $kq[1] );
	$order->save();
}
add_action( 'woocommerce_checkout_order_processed', 'smlife_zns_don', 50, 1 );
add_action( 'woocommerce_store_api_checkout_order_processed', 'smlife_zns_don', 50, 1 );

/* ===== Lam moi token dinh ky ===== */
add_action( 'init', function () {
	if ( ! wp_next_scheduled( 'smlife_zns_cron' ) ) {
		wp_schedule_event( time() + 300, 'hourly', 'smlife_zns_cron' );
	}
} );
add_action( 'smlife_zns_cron', function () {
	$t = smlife_zns_tok();
	if ( empty( $t['refresh'] ) ) {
		return;
	}
	/* Con duoi 4 gio thi lam moi som, de luc khach dat hang khong phai cho */
	if ( (int) ( $t['het'] ?? 0 ) < time() + 4 * HOUR_IN_SECONDS ) {
		$r = smlife_zns_lay_token( true );
		if ( ! is_wp_error( $r ) ) {
			smlife_zns_log( 'token', '', '', true, 'Đã làm mới token' );
		}
	}
} );

/* ===== Callback OAuth: /smlife-zns-callback ===== */
add_action( 'init', function () {
	$uri  = isset( $_SERVER['REQUEST_URI'] ) ? (string) wp_unslash( $_SERVER['REQUEST_URI'] ) : ''; // phpcs:ignore
	$path = untrailingslashit( (string) wp_parse_url( $uri, PHP_URL_PATH ) );
	$goc  = untrailingslashit( (string) wp_parse_url( smlife_zns_cb_url(), PHP_URL_PATH ) );
	if ( '' === $path || $path !== $goc ) {
		return;
	}
	if ( ! defined( 'DONOTCACHEPAGE' ) ) {
		define( 'DONOTCACHEPAGE', true );
	}
	nocache_headers();
	$ve = admin_url( 'admin.php?page=smlife-zalo-zns' );
	if ( ! is_user_logged_in() || ! current_user_can( 'manage_options' ) ) {
		wp_die( 'Hãy đăng nhập quản trị smlife.vn trên trình duyệt này rồi bấm Kết nối Zalo OA lại.' );
	}
	$luu   = get_transient( 'smlife_zns_pkce_' . get_current_user_id() );
	$state = isset( $_GET['state'] ) ? sanitize_text_field( wp_unslash( $_GET['state'] ) ) : ''; // phpcs:ignore
	$code  = isset( $_GET['code'] ) ? sanitize_text_field( wp_unslash( $_GET['code'] ) ) : ''; // phpcs:ignore
	$oa    = isset( $_GET['oa_id'] ) ? preg_replace( '/\D/', '', (string) wp_unslash( $_GET['oa_id'] ) ) : ''; // phpcs:ignore
	if ( ! is_array( $luu ) || '' === $state || ! hash_equals( (string) $luu['s'], $state ) || '' === $code ) {
		wp_safe_redirect( add_query_arg( 'zns', 'sai-phien', $ve ) );
		exit;
	}
	delete_transient( 'smlife_zns_pkce_' . get_current_user_id() );
	$j = smlife_zns_goi_oauth( array(
		'code'          => $code,
		'grant_type'    => 'authorization_code',
		'code_verifier' => (string) $luu['v'],
	) );
	if ( is_wp_error( $j ) ) {
		smlife_zns_log( 'token', '', '', false, 'Kết nối lỗi: ' . $j->get_error_message() );
		wp_safe_redirect( add_query_arg( 'zns', 'loi', $ve ) );
		exit;
	}
	smlife_zns_luu_tok( $j, $oa );
	smlife_zns_log( 'token', '', '', true, 'Đã kết nối OA ' . $oa );
	wp_safe_redirect( add_query_arg( 'zns', 'ok', $ve ) );
	exit;
}, 1 );

/* PKCE: code_challenge = base64url( sha256( code_verifier ) ), bo dau = */
function smlife_zns_challenge( $verifier ) {
	return rtrim( strtr( base64_encode( hash( 'sha256', $verifier, true ) ), '+/', '-_' ), '=' );
}

function smlife_zns_chuoi( $n ) {
	$ky  = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';
	$out = '';
	for ( $i = 0; $i < $n; $i++ ) {
		$out .= $ky[ random_int( 0, 61 ) ];
	}
	return $out;
}

add_action( 'admin_post_smlife_zns_ket_noi', function () {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( 'Không đủ quyền.' );
	}
	check_admin_referer( 'smlife_zns_ket_noi' );
	$o = smlife_zns_opt();
	if ( '' === $o['app_id'] || '' === $o['secret'] ) {
		wp_safe_redirect( admin_url( 'admin.php?page=smlife-zalo-zns&zns=thieu' ) );
		exit;
	}
	$v = smlife_zns_chuoi( 43 );
	$s = smlife_zns_chuoi( 24 );
	set_transient( 'smlife_zns_pkce_' . get_current_user_id(), array( 'v' => $v, 's' => $s ), 15 * MINUTE_IN_SECONDS );
	$url = add_query_arg( array(
		'app_id'         => rawurlencode( $o['app_id'] ),
		'redirect_uri'   => rawurlencode( smlife_zns_cb_url() ),
		'code_challenge' => rawurlencode( smlife_zns_challenge( $v ) ),
		'state'          => rawurlencode( $s ),
	), SMLIFE_ZNS_OAUTH . 'permission' );
	wp_redirect( $url ); // phpcs:ignore -- chuyen sang trang cap quyen cua Zalo
	exit;
} );

add_action( 'admin_post_smlife_zns_ngat', function () {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( 'Không đủ quyền.' );
	}
	check_admin_referer( 'smlife_zns_ngat' );
	delete_option( 'smlife_zns_tok' );
	smlife_zns_log( 'token', '', '', true, 'Đã ngắt kết nối' );
	wp_safe_redirect( admin_url( 'admin.php?page=smlife-zalo-zns&zns=ngat' ) );
	exit;
} );

add_action( 'admin_post_smlife_zns_thu', function () {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( 'Không đủ quyền.' );
	}
	check_admin_referer( 'smlife_zns_thu' );
	$o  = smlife_zns_opt();
	$kq = smlife_zns_gui( $o['so_thu'], array(
		'ten' => 'Nguyễn Văn A',
		'ma'  => 'TEST-' . wp_date( 'His' ),
		'sp'  => 'Giường ngủ gỗ công nghiệp (tin thử)',
	), 'TEST-' . time(), 'gui-thu', true );
	set_transient( 'smlife_zns_kq_thu', $kq, 120 );
	wp_safe_redirect( admin_url( 'admin.php?page=smlife-zalo-zns&zns=thu' ) );
	exit;
} );

add_action( 'admin_post_smlife_zns_save', function () {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( 'Không đủ quyền.' );
	}
	check_admin_referer( 'smlife_zns_save', 'smlife_zns_nonce' );
	$cu = smlife_zns_opt();
	$in = isset( $_POST['zn'] ) && is_array( $_POST['zn'] ) ? wp_unslash( $_POST['zn'] ) : array(); // phpcs:ignore
	$cd = isset( $in['che_do'] ) ? (string) $in['che_do'] : 'tat';
	$secret = isset( $in['secret'] ) ? trim( (string) $in['secret'] ) : '';
	update_option( 'smlife_zns', array(
		'che_do'      => in_array( $cd, array( 'tat', 'thu', 'bat' ), true ) ? $cd : 'tat',
		'app_id'      => isset( $in['app_id'] ) ? preg_replace( '/\D/', '', (string) $in['app_id'] ) : '',
		/* De trong = giu khoa cu, khoa bi mat khong bao gio in ra trang */
		'secret'      => '' !== $secret ? sanitize_text_field( $secret ) : $cu['secret'],
		'template_id' => isset( $in['template_id'] ) ? preg_replace( '/[^A-Za-z0-9]/', '', (string) $in['template_id'] ) : '',
		'so_thu'      => isset( $in['so_thu'] ) ? preg_replace( '/[^0-9+]/', '', (string) $in['so_thu'] ) : '',
		'k_ten'       => isset( $in['k_ten'] ) ? sanitize_key( $in['k_ten'] ) : 'customer_name',
		'k_ma'        => isset( $in['k_ma'] ) ? sanitize_key( $in['k_ma'] ) : 'order_code',
		'k_sp'        => isset( $in['k_sp'] ) ? sanitize_key( $in['k_sp'] ) : 'product_name',
		'gui_qo'      => empty( $in['gui_qo'] ) ? 0 : 1,
		'gui_wc'      => empty( $in['gui_wc'] ) ? 0 : 1,
		'toi_da_ngay' => isset( $in['toi_da_ngay'] ) ? max( 1, min( 5000, (int) $in['toi_da_ngay'] ) ) : 300,
	), false );
	wp_safe_redirect( admin_url( 'admin.php?page=smlife-zalo-zns&zns=luu' ) );
	exit;
} );

/* ===== Trang cai dat ===== */
add_action( 'admin_menu', function () {
	add_submenu_page( 'smlife-core', 'Zalo ZNS', 'Zalo ZNS', 'manage_options', 'smlife-zalo-zns', 'smlife_zns_page' );
}, 12 );

function smlife_zns_page() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}
	$o  = smlife_zns_opt();
	$t  = smlife_zns_tok();
	$tb = isset( $_GET['zns'] ) ? sanitize_key( wp_unslash( $_GET['zns'] ) ) : ''; // phpcs:ignore
	$msg = array(
		'luu'      => array( 'success', 'Đã lưu.' ),
		'ok'       => array( 'success', 'Đã kết nối Zalo OA thành công.' ),
		'ngat'     => array( 'success', 'Đã ngắt kết nối.' ),
		'thieu'    => array( 'error', 'Nhập App ID và Khóa bí mật rồi bấm Lưu trước khi kết nối.' ),
		'sai-phien' => array( 'error', 'Phiên kết nối không khớp hoặc đã quá 15 phút. Bấm Kết nối Zalo OA lại.' ),
		'loi'      => array( 'error', 'Zalo từ chối kết nối. Xem dòng lỗi trong Nhật ký bên dưới.' ),
	);
	echo '<div class="wrap smlife-wrap"><div class="smc-hd"><h1>Zalo ZNS xác nhận đơn hàng</h1>';
	echo '<p>Khách đặt qua form Mua ngay hoặc giỏ hàng sẽ nhận ngay tin Zalo từ OA SMLIFE: đã nhận đơn, sẽ liên hệ sớm, cần gấp gọi hotline. Khách chưa quan tâm OA vẫn nhận được. Mỗi tin thành công khoảng 250đ, gửi lỗi không mất phí.</p></div>';
	if ( isset( $msg[ $tb ] ) ) {
		echo '<div class="notice notice-' . esc_attr( $msg[ $tb ][0] ) . ' is-dismissible"><p>' . esc_html( $msg[ $tb ][1] ) . '</p></div>';
	}
	if ( 'thu' === $tb ) {
		$kq = get_transient( 'smlife_zns_kq_thu' );
		if ( is_array( $kq ) ) {
			echo '<div class="notice notice-' . ( $kq[0] ? 'success' : 'error' ) . ' is-dismissible"><p>Gửi thử: ' . esc_html( $kq[1] ) . '</p></div>';
		}
	}

	/* Ket noi */
	$ket_noi = ! empty( $t['refresh'] );
	echo '<div class="smlife-card"><h2>1. Kết nối Zalo OA</h2>';
	if ( $ket_noi ) {
		echo '<p><span class="dashicons dashicons-yes-alt" style="color:#2e7d32"></span> Đã kết nối OA <strong>' . esc_html( $t['oa_id'] ?? '' ) . '</strong>. Token hiện tại hết hạn lúc ' . esc_html( wp_date( 'H:i d/m/Y', (int) ( $t['het'] ?? 0 ) ) ) . ', hệ thống tự làm mới trước khi hết.</p>';
		if ( ! empty( $t['loi'] ) ) {
			echo '<p style="color:#c62828">Lần làm mới gần nhất lỗi: ' . esc_html( $t['loi'] ) . '. Nếu lỗi kéo dài, bấm Kết nối lại.</p>';
		}
	} else {
		echo '<p>Chưa kết nối. Nhập App ID, Khóa bí mật ở mục 2, bấm Lưu, rồi bấm nút dưới đây và chọn OA SMLIFE trên trang của Zalo.</p>';
	}
	echo '<p>Callback URL dán vào Zalo for Developers (Official Account > Thiết lập chung): <code>' . esc_html( smlife_zns_cb_url() ) . '</code></p>';
	echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '" style="display:inline-block;margin-right:8px">';
	echo '<input type="hidden" name="action" value="smlife_zns_ket_noi" />';
	wp_nonce_field( 'smlife_zns_ket_noi' );
	submit_button( $ket_noi ? 'Kết nối lại Zalo OA' : 'Kết nối Zalo OA', 'primary', 'submit', false );
	echo '</form>';
	if ( $ket_noi ) {
		echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '" style="display:inline-block">';
		echo '<input type="hidden" name="action" value="smlife_zns_ngat" />';
		wp_nonce_field( 'smlife_zns_ngat' );
		submit_button( 'Ngắt kết nối', 'secondary', 'submit', false );
		echo '</form>';
	}
	echo '</div>';

	/* Cai dat */
	echo '<div class="smlife-card"><h2>2. Cài đặt</h2>';
	echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '">';
	echo '<input type="hidden" name="action" value="smlife_zns_save" />';
	wp_nonce_field( 'smlife_zns_save', 'smlife_zns_nonce' );
	echo '<table class="form-table" role="presentation">';
	echo '<tr><th scope="row">Chế độ</th><td><select name="zn[che_do]">';
	foreach ( array( 'tat' => 'Tắt', 'thu' => 'Chỉ gửi số thử (khách thật không nhận)', 'bat' => 'Bật, gửi cho khách' ) as $k => $v ) {
		echo '<option value="' . esc_attr( $k ) . '" ' . selected( $k, $o['che_do'], false ) . '>' . esc_html( $v ) . '</option>';
	}
	echo '</select><p class="description">Nên chạy "Chỉ gửi số thử" vài đơn để xem tin hiển thị ra sao, rồi mới bật cho khách.</p></td></tr>';
	echo '<tr><th scope="row">App ID</th><td><input type="text" name="zn[app_id]" value="' . esc_attr( $o['app_id'] ) . '" class="regular-text" inputmode="numeric" /></td></tr>';
	echo '<tr><th scope="row">Khóa bí mật</th><td><input type="password" name="zn[secret]" value="" class="regular-text" autocomplete="new-password" placeholder="' . ( '' !== $o['secret'] ? 'Đã lưu, để trống nếu không đổi' : 'Dán Khóa bí mật của ứng dụng' ) . '" /></td></tr>';
	echo '<tr><th scope="row">Template ID</th><td><input type="text" name="zn[template_id]" value="' . esc_attr( $o['template_id'] ) . '" class="regular-text" /><p class="description">Mã mẫu tin sau khi Zalo duyệt, xem trong trang Quản lý mẫu ZNS.</p></td></tr>';
	echo '<tr><th scope="row">Tên tham số trong mẫu</th><td>';
	echo 'Tên khách <input type="text" name="zn[k_ten]" value="' . esc_attr( $o['k_ten'] ) . '" style="width:150px" /> ';
	echo 'Mã đơn <input type="text" name="zn[k_ma]" value="' . esc_attr( $o['k_ma'] ) . '" style="width:150px" /> ';
	echo 'Sản phẩm <input type="text" name="zn[k_sp]" value="' . esc_attr( $o['k_sp'] ) . '" style="width:150px" />';
	echo '<p class="description">Phải trùng tên biến trong mẫu, ví dụ mẫu có &lt;customer_name&gt; thì điền customer_name. Để trống ô nào thì không gửi tham số đó.</p></td></tr>';
	echo '<tr><th scope="row">Gửi khi</th><td><label><input type="checkbox" name="zn[gui_qo]" value="1" ' . checked( 1, $o['gui_qo'], false ) . ' /> Khách gửi form Mua ngay</label><br><label><input type="checkbox" name="zn[gui_wc]" value="1" ' . checked( 1, $o['gui_wc'], false ) . ' /> Khách đặt hàng qua giỏ hàng</label></td></tr>';
	echo '<tr><th scope="row">Số điện thoại thử</th><td><input type="text" name="zn[so_thu]" value="' . esc_attr( $o['so_thu'] ) . '" class="regular-text" style="max-width:200px" /><p class="description">Số có dùng Zalo, dùng cho nút Gửi thử và chế độ Chỉ gửi số thử.</p></td></tr>';
	echo '<tr><th scope="row">Tối đa mỗi ngày</th><td><input type="number" name="zn[toi_da_ngay]" value="' . esc_attr( $o['toi_da_ngay'] ) . '" min="1" max="5000" class="small-text" /> tin <span class="description">Chặn chi phí tăng vọt nếu bị spam. Mỗi số cũng chỉ nhận tối đa 3 tin/ngày.</span></td></tr>';
	echo '</table>';
	submit_button( 'Lưu' );
	echo '</form>';
	echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '">';
	echo '<input type="hidden" name="action" value="smlife_zns_thu" />';
	wp_nonce_field( 'smlife_zns_thu' );
	submit_button( 'Gửi thử tới số điện thoại thử', 'secondary', 'submit', false );
	echo '</form></div>';

	/* Nhat ky */
	$l = get_option( 'smlife_zns_log', array() );
	echo '<div class="smlife-card"><h2>3. Nhật ký gần nhất</h2>';
	if ( empty( $l ) || ! is_array( $l ) ) {
		echo '<p>Chưa có tin nào.</p>';
	} else {
		echo '<table class="widefat striped"><thead><tr><th>Lúc</th><th>Nguồn</th><th>Mã</th><th>Số</th><th>Kết quả</th></tr></thead><tbody>';
		foreach ( array_slice( $l, 0, 30 ) as $d ) {
			echo '<tr><td>' . esc_html( wp_date( 'H:i d/m', (int) $d['t'] ) ) . '</td><td>' . esc_html( $d['l'] ) . '</td><td>' . esc_html( $d['r'] ) . '</td><td>' . esc_html( $d['s'] ) . '</td><td style="color:' . ( $d['k'] ? '#2e7d32' : '#c62828' ) . '">' . esc_html( $d['g'] ) . '</td></tr>';
		}
		echo '</tbody></table>';
	}
	echo '</div></div>';
}
