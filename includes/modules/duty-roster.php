<?php
/**
 * SMLIFE Core - Module: Lich Truc Sales
 *
 * Hai thu quyet dinh ai dang truc:
 *   1. Cong tac nghi  - ai bam "Nghi" thi hotline va Zalo cua nguoi do tat, di lam lai thi bam mo.
 *   2. Lich cuoi tuan - moi tuan chon 1 nguoi truc Chu nhat, nguoi do tu dong nghi Thu 7.
 *
 * So hotline tong (danh dau "Hotline tong") luon sang, khong bao gio bi tat.
 *
 * Trang thai truc duoc tinh o may chu va in thang vao HTML (hotline-header,
 * mobile-menu), nen moi lan trang thai doi phai xoa cache WP Rocket:
 *   - Admin gat Nghi/Truc hoac luu lich: xoa ngay trong luot luu.
 *   - Doi ngay luc nua dem: nhuong cho module Lich xoa cache (00:05) neu dang bat.
 *   - Cac moc khac (het ca Chu nhat): smlife_duty_day_guard tu xoa.
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

/* ============================================================
 * 1. CAU HINH
 * ============================================================ */
function smlife_duty_opt() {
	$def = array(
		'enabled'    => 1,
		'off_style'  => 'dim',
		'lock_click' => 1,
		'show_badge' => 1,
		'badge_head' => 0,
		'badge_mobi' => 1,
		'on_text'    => 'Đang trực',
		'off_text'   => 'Ngoài ca',
		'on_color'   => '#16a34a',
		'auto_purge' => 1,
		'sat_off'    => 1,
		'end_sun'    => '17:30',
		'moc_ngay'   => '00:00',
		'plan'       => array(),
		'staff'      => array(
			array( 'on' => 1, 'name' => 'Hải',   'desc' => 'Trách Nhiệm', 'phone' => '0914913179', 'zalo' => '', 'main' => 1, 'off' => 0 ),
			array( 'on' => 1, 'name' => 'Phúc',  'desc' => 'Tin Tưởng',   'phone' => '0909553363', 'zalo' => '', 'main' => 0, 'off' => 0 ),
			array( 'on' => 1, 'name' => 'Phong', 'desc' => 'Tử Tế',       'phone' => '0909303363', 'zalo' => '', 'main' => 0, 'off' => 0 ),
			array( 'on' => 0, 'name' => '',      'desc' => '',            'phone' => '',           'zalo' => '', 'main' => 0, 'off' => 0 ),
			array( 'on' => 0, 'name' => '',      'desc' => '',            'phone' => '',           'zalo' => '', 'main' => 0, 'off' => 0 ),
		),
	);
	$s = get_option( 'smlife_duty', array() );
	if ( ! is_array( $s ) ) {
		return $def;
	}
	$out = array_merge( $def, $s );
	if ( empty( $out['staff'] ) || ! is_array( $out['staff'] ) ) {
		$out['staff'] = $def['staff'];
	}
	if ( ! is_array( $out['plan'] ) ) {
		$out['plan'] = array();
	}
	return $out;
}

function smlife_duty_norm( $p ) {
	$p = preg_replace( '/[^0-9]/', '', (string) $p );
	if ( '' === $p ) {
		return '';
	}
	if ( 0 === strpos( $p, '84' ) && strlen( $p ) > 9 ) {
		$p = '0' . substr( $p, 2 );
	}
	return $p;
}

function smlife_duty_days() {
	return array(
		1 => 'Thứ 2',
		2 => 'Thứ 3',
		3 => 'Thứ 4',
		4 => 'Thứ 5',
		5 => 'Thứ 6',
		6 => 'Thứ 7',
		7 => 'Chủ nhật',
	);
}

/* Danh sach nhan su dang bat, giu nguyen chi so goc */
function smlife_duty_staff() {
	$o   = smlife_duty_opt();
	$out = array();
	foreach ( $o['staff'] as $i => $s ) {
		if ( empty( $s['on'] ) ) {
			continue;
		}
		$ph = smlife_duty_norm( isset( $s['phone'] ) ? $s['phone'] : '' );
		if ( '' === $ph ) {
			continue;
		}
		$out[ $i ] = array(
			'name'  => (string) $s['name'],
			'desc'  => isset( $s['desc'] ) ? (string) $s['desc'] : '',
			'phone' => $ph,
			'zalo'  => isset( $s['zalo'] ) ? trim( (string) $s['zalo'] ) : '',
			'main'  => empty( $s['main'] ) ? 0 : 1,
			'off'   => empty( $s['off'] ) ? 0 : 1,
		);
	}
	return $out;
}

/* ============================================================
 * 2. NGAY THANG
 * ============================================================ */

/* Gio doi ngay cua lich truc, mac dinh 00:00. Tra ve so giay tinh tu nua dem. */
function smlife_duty_moc_giay() {
	$o = smlife_duty_opt();
	$m = isset( $o['moc_ngay'] ) ? (string) $o['moc_ngay'] : '00:00';
	if ( ! preg_match( '/^([01]\d|2[0-3]):([0-5]\d)$/', $m, $k ) ) {
		return 0;
	}
	return (int) $k[1] * HOUR_IN_SECONDS + (int) $k[2] * MINUTE_IN_SECONDS;
}

/* Moc thoi gian dung de xac dinh "hom nay" cua lich truc.
 * Lui lai dung bang gio doi ngay: moc 00:00 thi chinh la time(). */
function smlife_duty_ngay_ts() {
	return time() - smlife_duty_moc_giay();
}

/* Chu nhat cua tuan chua ngay $ts (tuan bat dau Thu 2) */
function smlife_duty_sunday_of( $ts ) {
	$dow = (int) wp_date( 'N', $ts );
	return wp_date( 'Y-m-d', $ts + ( 7 - $dow ) * DAY_IN_SECONDS );
}

/* $n chu nhat ke tiep tinh tu hom nay (gom ca chu nhat tuan nay) */
function smlife_duty_next_sundays( $n = 4 ) {
	$now = smlife_duty_ngay_ts();
	$out = array();
	$sun = smlife_duty_sunday_of( $now );
	for ( $i = 0; $i < $n; $i++ ) {
		$out[] = $sun;
		$sun   = wp_date( 'Y-m-d', strtotime( $sun ) + 7 * DAY_IN_SECONDS );
	}
	return $out;
}

function smlife_duty_vn_date( $ymd ) {
	$ts = strtotime( $ymd );
	return $ts ? wp_date( 'd/m/Y', $ts ) : $ymd;
}

/* Thu 7 ngay truoc chu nhat $sun */
function smlife_duty_sat_of( $sun ) {
	$ts = strtotime( $sun );
	return $ts ? wp_date( 'Y-m-d', $ts - DAY_IN_SECONDS ) : '';
}

/* ============================================================
 * 3. TINH TRANG THAI HOM NAY
 * ============================================================ */
/* ===== 1.20.0: BAN CHOT =====
 * Website KHONG tinh lai lich truc o moi luot dung trang nua. Moi ngay chot
 * mot lan dung luc xoa cache dem (00:05), luu vao smlife_duty_snap, va moi
 * trang deu doc ban chot nay. Nho vay:
 * - Gat Nghi luc 9h sang chi luu lai, tren web chua doi, khong xoa cache.
 * - Trang nao dung lai trong ngay (Preload, sua san pham) van hien dung bo so
 *   da chot, khong co chuyen trang nay so cu trang kia so moi.
 * - Thu 7, Chu nhat, Thu 2 deu doi dung mot luc 00:05.
 */
function smlife_duty_hom_nay() {
	return wp_date( 'Y-m-d', smlife_duty_ngay_ts() );
}

/* Tinh lai theo lich hien tai va luu thanh ban chot */
function smlife_duty_chot() {
	$st   = smlife_duty_tinh();
	$snap = array(
		'ngay' => smlife_duty_hom_nay(),
		't'    => time(),
		'st'   => $st,
	);
	/* Ghi ban chot khong duoc tinh la "nguoi dung doi cai dat", nen tra co
	   smlife_co_doi ve nhu cu, tranh mot ham luu khac xoa cache oan. */
	$co = ! empty( $GLOBALS['smlife_co_doi'] );
	update_option( 'smlife_duty_snap', $snap, true );
	$GLOBALS['smlife_co_doi'] = $co;
	$GLOBALS['smlife_duty_rt'] = $st;
	return $snap;
}

/* Trang thai DANG HIEN tren website = ban chot */
function smlife_duty_state() {
	if ( isset( $GLOBALS['smlife_duty_rt'] ) && is_array( $GLOBALS['smlife_duty_rt'] ) ) {
		return $GLOBALS['smlife_duty_rt'];
	}
	$s = get_option( 'smlife_duty_snap' );
	if ( ! is_array( $s ) || ! isset( $s['st'] ) || ! is_array( $s['st'] ) ) {
		$s = smlife_duty_chot();
	}
	$GLOBALS['smlife_duty_rt'] = $s['st'];
	return $s['st'];
}

/* So hai trang thai: cung nguoi trac, cung so dang sang thi coi la giong */
function smlife_duty_giong( $a, $b ) {
	$k = function ( $s ) {
		return json_encode( array( ! empty( $s['apply'] ), array_values( (array) $s['on'] ), (int) $s['duty'], array_keys( (array) $s['off'] ) ) );
	};
	return $k( $a ) === $k( $b );
}

/* Tinh SONG theo cai dat hien tai. Chi dung de chot va de xem truoc trong admin. */
function smlife_duty_tinh() {
	$o     = smlife_duty_opt();
	$staff = smlife_duty_staff();
	$st    = array( 'apply' => false, 'on' => array(), 'off' => array(), 'why' => 'Tắt', 'duty' => -1 );

	if ( empty( $o['enabled'] ) || empty( $staff ) ) {
		return $st;
	}

	/* time() la moc UTC that; wp_date tu doi sang mui gio cua site.
	   KHONG dung current_time('timestamp') o day: no da cong san do lech
	   mui gio, dua vao wp_date se bi cong lan hai, lich chay som 7 tieng. */
	$ngay = smlife_duty_ngay_ts();
	$dow  = (int) wp_date( 'N', $ngay );
	$sun  = smlife_duty_sunday_of( $ngay );
	$days = smlife_duty_days();

	$plan = isset( $o['plan'][ $sun ] ) ? (int) $o['plan'][ $sun ] : -1;
	if ( $plan >= 0 && ! isset( $staff[ $plan ] ) ) {
		$plan = -1;
	}

	/* --- Ai dang nghi --- */
	$off = array();
	foreach ( $staff as $i => $s ) {
		if ( ! empty( $s['off'] ) ) {
			$off[ $i ] = 'Đang nghỉ';
		}
	}
	/* Thu 7: nguoi nhan truc Chu nhat duoc nghi */
	if ( 6 === $dow && $plan >= 0 && ! empty( $o['sat_off'] ) && ! isset( $off[ $plan ] ) ) {
		$off[ $plan ] = 'Nghỉ Thứ 7, trực Chủ nhật ' . smlife_duty_vn_date( $sun );
	}

	/* --- Ai truc --- */
	$duty = -1;
	$why  = '';
	/* 1.20.0: bo gio het ca Chu nhat. Nguoi truc Chu nhat giu ca ca ngay,
	   0:05 Thu 2 lich xoa cache chot lai thi moi ve ca nhom. */
	if ( 7 === $dow && $plan >= 0 ) {
		$duty = $plan;
		$why  = 'Chủ nhật ' . smlife_duty_vn_date( $sun ) . ': ' . $staff[ $plan ]['name'] . ' trực cả ngày';
	} else {
		$why = $days[ $dow ] . ': cả nhóm trực';
	}

	if ( $duty >= 0 && isset( $off[ $duty ] ) ) {
		$duty = -1;
		$why .= ' (người trực đang nghỉ nên mở lại cả nhóm)';
	}
	if ( ! empty( $off ) ) {
		$names = array();
		foreach ( $off as $i => $r ) {
			$names[] = $staff[ $i ]['name'];
		}
		$why .= ' - nghỉ: ' . implode( ', ', $names );
	}

	if ( $duty < 0 && empty( $off ) ) {
		return array( 'apply' => false, 'on' => array(), 'off' => array(), 'why' => $why, 'duty' => -1 );
	}

	$on = array();
	foreach ( $staff as $i => $s ) {
		if ( ! empty( $s['main'] ) ) {
			$on[] = $s['phone'];
			continue;
		}
		if ( isset( $off[ $i ] ) ) {
			continue;
		}
		if ( $duty >= 0 && $i !== $duty ) {
			continue;
		}
		$on[] = $s['phone'];
	}

	return array( 'apply' => true, 'on' => $on, 'off' => $off, 'why' => $why, 'duty' => $duty );
}

/* ============================================================
 * 4. API CHO CAC MODULE KHAC
 * ============================================================ */
function smlife_duty_active() {
	$st = smlife_duty_state();
	return ! empty( $st['apply'] );
}

function smlife_duty_is_on( $phone ) {
	$st = smlife_duty_state();
	if ( empty( $st['apply'] ) ) {
		return true;
	}
	$p = smlife_duty_norm( $phone );
	if ( '' === $p ) {
		return true;
	}
	return in_array( $p, $st['on'], true );
}

function smlife_duty_is_main( $phone ) {
	$p = smlife_duty_norm( $phone );
	if ( '' === $p ) {
		return false;
	}
	foreach ( smlife_duty_staff() as $s ) {
		if ( $s['phone'] === $p && ! empty( $s['main'] ) ) {
			return true;
		}
	}
	return false;
}

function smlife_duty_info( $s ) {
	$z = isset( $s['zalo'] ) ? trim( (string) $s['zalo'] ) : '';
	if ( '' === $z && ! empty( $s['phone'] ) ) {
		$z = 'https://zalo.me/' . $s['phone'];
	}
	return array(
		'name'  => isset( $s['name'] ) ? (string) $s['name'] : '',
		'desc'  => isset( $s['desc'] ) ? (string) $s['desc'] : '',
		'phone' => isset( $s['phone'] ) ? $s['phone'] : '',
		'zalo'  => $z,
	);
}

function smlife_duty_person_by_phone( $phone ) {
	$p = smlife_duty_norm( $phone );
	if ( '' === $p ) {
		return null;
	}
	foreach ( smlife_duty_staff() as $s ) {
		if ( $s['phone'] === $p ) {
			return smlife_duty_info( $s );
		}
	}
	return null;
}

/* Nguoi dang truc, de Hotline tong doi link Zalo theo nguoi truc */
function smlife_duty_on_person() {
	$st = smlife_duty_state();
	if ( empty( $st['apply'] ) ) {
		return null;
	}
	$staff = smlife_duty_staff();
	if ( $st['duty'] >= 0 && isset( $staff[ $st['duty'] ] ) ) {
		return smlife_duty_info( $staff[ $st['duty'] ] );
	}
	foreach ( $staff as $i => $s ) {
		if ( ! empty( $s['main'] ) ) {
			continue;
		}
		if ( isset( $st['off'][ $i ] ) ) {
			continue;
		}
		return smlife_duty_info( $s );
	}
	return null;
}

function smlife_duty_class( $phone ) {
	$st = smlife_duty_state();
	if ( empty( $st['apply'] ) ) {
		return '';
	}
	$p = smlife_duty_norm( $phone );
	if ( '' === $p ) {
		return '';
	}
	$known = false;
	foreach ( smlife_duty_staff() as $s ) {
		if ( $s['phone'] === $p ) {
			$known = true;
			break;
		}
	}
	if ( ! $known ) {
		return '';
	}
	return in_array( $p, $st['on'], true ) ? ' smlife-duty-on' : ' smlife-duty-off';
}

/* $where: 'header' = khoi hotline dau trang (desktop), 'mobile' = thanh menu duoi dien thoai.
   De trong thi chi phu thuoc cong tac tong show_badge. */
function smlife_duty_badge( $phone, $where = '' ) {
	$o = smlife_duty_opt();
	if ( empty( $o['show_badge'] ) ) {
		return '';
	}
	if ( 'header' === $where && empty( $o['badge_head'] ) ) {
		return '';
	}
	if ( 'mobile' === $where && empty( $o['badge_mobi'] ) ) {
		return '';
	}
	$c = smlife_duty_class( $phone );
	if ( '' === $c ) {
		return '';
	}
	if ( false !== strpos( $c, 'duty-on' ) ) {
		return '<span class="smlife-duty-tag is-on"><i></i>' . esc_html( $o['on_text'] ) . '</span>';
	}
	return '<span class="smlife-duty-tag is-off">' . esc_html( $o['off_text'] ) . '</span>';
}

/* ===== CSS ===== */
add_action( 'wp_head', function () {
	if ( ! smlife_duty_active() ) {
		return;
	}
	$o = smlife_duty_opt();
	$c = '';
	if ( 'hide' === $o['off_style'] ) {
		$c .= '.smlife-duty-off{display:none !important}';
	} else {
		$c .= '.smlife-duty-off{opacity:.34;filter:grayscale(1)}';
		if ( ! empty( $o['lock_click'] ) ) {
			$c .= '.smlife-duty-off a,.smlife-duty-off button,a.smlife-duty-off,button.smlife-duty-off{pointer-events:none !important;cursor:default !important}';
		}
	}
	$c .= '.smlife-duty-tag{display:inline-flex;align-items:center;gap:4px;font-size:10px;font-weight:700;line-height:1;';
	$c .= 'padding:2px 6px;border-radius:20px;margin-left:6px;vertical-align:middle;white-space:nowrap;letter-spacing:.2px}';
	$c .= '.smlife-duty-tag.is-on{background:' . esc_attr( $o['on_color'] ) . ';color:#fff}';
	$c .= '.smlife-duty-tag.is-off{background:#e5e7eb;color:#6b7280}';
	$c .= '.smlife-duty-tag.is-on i{width:5px;height:5px;border-radius:50%;background:#fff;display:inline-block;animation:smlifeDutyDot 1.4s ease-in-out infinite}';
	$c .= '@keyframes smlifeDutyDot{0%,100%{opacity:1}50%{opacity:.25}}';
	echo '<style id="smlife-duty-css">' . $c . '</style>'; // phpcs:ignore
}, 21 );

/* ============================================================
 * 5. XOA CACHE KHI SANG NGAY MOI
 * ============================================================ */
/* Tra ve true neu co it nhat mot plugin cache duoc xoa */
function smlife_duty_purge() {
	$ok = false;
	if ( function_exists( 'rocket_clean_domain' ) ) {
		rocket_clean_domain();
		$ok = true;
	}
	if ( has_action( 'litespeed_purge_all' ) ) {
		do_action( 'litespeed_purge_all' );
		$ok = true;
	}
	if ( function_exists( 'wp_cache_clear_cache' ) ) {
		wp_cache_clear_cache();
		$ok = true;
	}
	if ( function_exists( 'w3tc_flush_all' ) ) {
		w3tc_flush_all();
		$ok = true;
	}
	return $ok;
}

/* 1.19.0: bo lich rieng 'smlife_duty_daily' luc 00:05.
 * Lich do chi goi lai smlife_duty_day_guard, ma ham nay da chay o moi luot
 * cron that (15 phut mot lan) nen lich rieng la thua, lai trung gio voi
 * module Lich xoa cache. Go lich cu con sot tren website. */
function smlife_duty_cron_setup() {
	if ( wp_next_scheduled( 'smlife_duty_daily' ) ) {
		wp_clear_scheduled_hook( 'smlife_duty_daily' );
	}
}

/* Dau van tay cua trang thai truc: ai dang hien, ai bi an, ai la nguoi truc.
 * Chi doi khi so dien thoai hien tren web thuc su doi. */
function smlife_duty_sig() {
	$st = smlife_duty_state();
	return md5( json_encode( array(
		! empty( $st['apply'] ),
		array_values( (array) $st['on'] ),
		(int) $st['duty'],
		array_keys( (array) $st['off'] ),
	) ) );
}

/* 1.20.0: Chot lich truc cung luc voi lich xoa cache dem (va nut Xoa ngay). */
add_action( 'smlife_xc_truoc_xoa', function () {
	smlife_duty_chot();
} );

/* Duong an toan khi lich xoa cache dem khong chay (cron hong, module tat,
 * Redis day...). Sang ngay moi ma ban chot van la cua hom qua thi o luot truy
 * cap dau tien:
 * - Neu lich xoa cache dang bat va sap chay / vua toi han trong 30 phut thi
 *   cho no, khong lam gi.
 * - Qua 30 phut van chua chay thi tu chot. Bo so tren web co doi thi xoa cache
 *   (neu bat "Tu lam moi cache"), khong doi thi chi cap nhat ngay cua ban chot.
 */
function smlife_duty_day_guard() {
	$s = get_option( 'smlife_duty_snap' );
	if ( ! is_array( $s ) || ! isset( $s['ngay'] ) ) {
		return; /* chua co ban chot, smlife_duty_state se tu tao */
	}
	if ( $s['ngay'] === smlife_duty_hom_nay() ) {
		return;
	}
	if ( function_exists( 'smlife_xc_sap_chay' ) && smlife_xc_sap_chay( 30 * MINUTE_IN_SECONDS ) ) {
		return;
	}
	/* 1.24.0: ban chot trong object cache co the cu (Redis day khong ghi duoc).
	   Doc lai thang tu DB truoc khi quyet dinh, va khoa bang DB chu khong bang
	   transient, de khong chot + xoa cache lap lai o moi request. */
	$s_db = smlife_opt_db( 'smlife_duty_snap' );
	if ( is_array( $s_db ) && isset( $s_db['ngay'] ) && $s_db['ngay'] === smlife_duty_hom_nay() ) {
		wp_cache_delete( 'alloptions', 'options' );
		return;
	}
	if ( ! smlife_khoa_db( 'duty', 5 * MINUTE_IN_SECONDS ) ) {
		return;
	}
	$s   = is_array( $s_db ) ? $s_db : $s;
	$cu  = isset( $s['st'] ) && is_array( $s['st'] ) ? $s['st'] : array();
	$moi = smlife_duty_chot();
	$o   = smlife_duty_opt();
	if ( ! smlife_duty_giong( $cu, $moi['st'] ) && ! empty( $o['auto_purge'] ) ) {
		smlife_duty_purge();
		error_log( '[SMLIFE Core] Lich Truc - lich xoa cache dem khong chay, tu chot lich ngay ' . $moi['ngay'] . ' va xoa cache' );
	}
}
add_action( 'init', 'smlife_duty_day_guard', 5 );
add_action( 'init', 'smlife_duty_cron_setup', 20 );

/* Don lich da qua, giu du lieu gon */
function smlife_duty_tidy() {
	$o     = smlife_duty_opt();
	$today = wp_date( 'Y-m-d', smlife_duty_ngay_ts() );
	$ch    = false;
	foreach ( $o['plan'] as $d => $v ) {
		if ( $d < $today ) {
			unset( $o['plan'][ $d ] );
			$ch = true;
		}
	}
	if ( $ch ) {
		update_option( 'smlife_duty', $o );
	}
}

/* ============================================================
 * 6. TRANG QUAN TRI
 * ============================================================ */
add_action( 'admin_menu', function () {
	add_submenu_page(
		'smlife-core',
		'Lịch Trực Sales',
		'Lịch Trực Sales',
		'manage_options',
		'smlife-lich-truc',
		'smlife_duty_page'
	);
}, 58 );

function smlife_duty_page() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}
	smlife_duty_tidy();
	$o     = smlife_duty_opt();
	$staff = smlife_duty_staff();
	$days  = smlife_duty_days();
	$st    = smlife_duty_state();
	/* 1.28.0: xep truoc ca quy (14 tuan: tuan nay + 13 tuan toi), nhan su set 1 lan 3 thang */
	$suns  = smlife_duty_next_sundays( 14 );

	echo '<div class="wrap dt-wrap">';
	echo '<div class="dt-hd"><h1>Lịch Trực Sales</h1>';
	echo '<p>Ai nghỉ thì gạt nút Nghỉ, đi làm lại thì gạt về Trực. Thêm một lịch cho cuối tuần: mỗi tuần chọn một người trực Chủ nhật, người đó tự động được nghỉ Thứ 7. Ai ngoài ca thì hotline và Zalo của người đó tự mờ đi trên website, riêng số hotline tổng luôn sáng. Nhãn Đang trực / Ngoài ca chọn được hiện ở đầu trang hay chỉ trong menu mobile.</p></div>';

	/* Lan xoa cache dem toi = luc lich moi len website */
	$len_web = 'lượt truy cập đầu tiên của ngày mới';
	if ( function_exists( 'smlife_xc_opt' ) ) {
		$xo = smlife_xc_opt();
		$xn = wp_next_scheduled( 'smlife_xc_chay' );
		if ( ! empty( $xo['bat'] ) && $xn ) {
			$len_web = wp_date( 'H:i - d/m/Y', $xn ) . ' (lần xoá cache đêm)';
		}
	}
	$xc_url = admin_url( 'admin.php?page=smlife-lich-xoa-cache' );

	if ( isset( $_GET['saved'] ) ) {
		$cho = isset( $_GET['cho'] ) ? sanitize_key( wp_unslash( $_GET['cho'] ) ) : '0';
		if ( '1' === $cho ) {
			echo '<div class="notice notice-success is-dismissible"><p><b>Đã lưu. Website chưa đổi ngay</b>, lịch mới sẽ lên website lúc <b>' . esc_html( $len_web ) . '</b>. Cần áp dụng gấp thì vào <a href="' . esc_url( $xc_url ) . '">Lịch xoá cache</a> bấm "Xoá toàn bộ cache".</p></div>';
		} else {
			echo '<div class="notice notice-info is-dismissible"><p>Không có gì thay đổi.</p></div>';
		}
	}

	/* Canh bao mui gio: lich truc tinh theo gio cua WordPress, de UTC la lech 7 tieng */
	$tzs = (string) get_option( 'timezone_string' );
	$gmt = (float) get_option( 'gmt_offset' );
	if ( '' === $tzs && 0.0 === $gmt ) {
		echo '<div class="notice notice-error"><p><b>Múi giờ WordPress đang là UTC+0</b>, lệch 7 tiếng so với Việt Nam nên lịch trực đổi ngày lệch 7 tiếng so với giờ đã đặt. Vào <a href="' . esc_url( admin_url( 'options-general.php' ) ) . '">Cài đặt &gt; Tổng quan</a>, mục Múi giờ chọn <b>Asia/Ho_Chi_Minh</b> rồi Lưu.</p></div>';
	}

	/* --- Hom nay --- */
	$ngay_ts = smlife_duty_ngay_ts();
	echo '<div class="dt-now"><b>Hôm nay ' . esc_html( wp_date( 'd/m/Y', $ngay_ts ) ) . ' - ' . esc_html( $days[ (int) wp_date( 'N', $ngay_ts ) ] ) . '</b> <span style="color:#6b7280">(giờ website ' . esc_html( wp_date( 'H:i' ) ) . ')</span><br />';
	$snap = get_option( 'smlife_duty_snap' );
	echo '<b>Đang hiện trên website</b>' . ( is_array( $snap ) && ! empty( $snap['t'] ) ? ' <span style="color:#6b7280">(chốt lúc ' . esc_html( wp_date( 'H:i d/m', (int) $snap['t'] ) ) . ')</span>' : '' ) . ': ' . esc_html( $st['why'] ) . '<br />';
	foreach ( $staff as $i => $s ) {
		$is_on = smlife_duty_is_on( $s['phone'] );
		echo '<span class="tag' . ( $is_on ? '' : ' off' ) . '">' . esc_html( $s['name'] ) . ' ' . ( $is_on ? 'trực' : 'nghỉ' ) . '</span> ';
	}
	/* Xem truoc: lich vua sua khac voi ban dang hien thi */
	$moi = smlife_duty_tinh();
	if ( ! smlife_duty_giong( $st, $moi ) ) {
		echo '<div style="margin-top:10px;padding:8px 10px;background:#fff7ed;border-left:3px solid #f59e0b">';
		echo '<b>Chờ lên website lúc ' . esc_html( $len_web ) . ':</b> ' . esc_html( $moi['why'] ) . '<br />';
		foreach ( $staff as $i => $s ) {
			$on2 = ! $moi['apply'] || in_array( $s['phone'], (array) $moi['on'], true );
			echo '<span class="tag' . ( $on2 ? '' : ' off' ) . '">' . esc_html( $s['name'] ) . ' ' . ( $on2 ? 'trực' : 'nghỉ' ) . '</span> ';
		}
		echo '<br /><span style="color:#6b7280">Gấp thì vào <a href="' . esc_url( $xc_url ) . '">Lịch xoá cache</a> bấm "Xoá toàn bộ cache" để áp dụng ngay.</span></div>';
	}
	echo '</div>';

	echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '" id="smlife-duty-form">';
	echo '<input type="hidden" name="action" value="smlife_duty_save" />';
	wp_nonce_field( 'smlife_duty_save', 'smlife_duty_nonce' );

	/* --- 1. Cong tac nghi --- */
	echo '<div class="dt-card"><h2>1. Ai đang nghỉ</h2><div class="in">';
	echo '<p class="description" style="margin:0 0 4px">Gạt sang Nghỉ là lưu lại ngay, hotline và Zalo của người đó sẽ tắt trên website ở lần xoá cache đêm 00:05. Đi làm lại thì gạt về. Gạt xong trang tự lưu.</p>';
	$any = false;
	foreach ( $o['staff'] as $i => $s ) {
		if ( empty( $s['on'] ) || '' === trim( (string) $s['name'] ) ) {
			continue;
		}
		$any = true;
		$off = empty( $s['off'] ) ? 0 : 1;
		echo '<div class="dt-row"><label class="dt-sw"><input type="checkbox" class="smlife-duty-auto" name="duty[staff][' . $i . '][off]" value="1" ' . checked( 1, $off, false ) . ' /><i></i></label>';
		echo '<span class="tx"><strong>' . esc_html( $s['name'] ) . ( $off ? ' - đang nghỉ' : ' - đang trực' ) . '</strong>';
		echo '<em>' . esc_html( $s['phone'] ) . ( ! empty( $s['main'] ) ? ' - hotline tổng, số này luôn sáng dù có nghỉ' : '' ) . '</em></span></div>';
	}
	if ( ! $any ) {
		echo '<p class="description">Chưa có nhân sự nào được bật ở mục 3.</p>';
	}
	echo '</div></div>';

	/* --- 2. Lich cuoi tuan --- */
	echo '<div class="dt-card"><h2>2. Lịch cuối tuần - ai trực Chủ nhật</h2><div class="in">';
	echo '<p class="description" style="margin:0 0 12px">Mỗi tuần chọn một người trực Chủ nhật. Người đó tự động được nghỉ Thứ 7 liền trước. Bảng hiện sẵn ' . count( $suns ) . ' tuần (khoảng 3 tháng) để xếp một lần cho cả quý. Tuần đã qua tự biến mất, tuần mới tự thêm vào cuối bảng.</p>';
	/* Nut xep xoay vong: chi dien cac tuan dang trong, xoay vong theo thu tu nhan su,
	   tiep noi nguoi cuoi cung da xep. Chi dien vao o, phai bam Luu moi ghi. */
	$ds_on = array_map( 'intval', array_keys( $staff ) );
	echo '<div class="dt-xv" style="display:flex;gap:8px;flex-wrap:wrap;align-items:center;margin:0 0 10px">';
	echo '<button type="button" class="button" id="dt-xv" data-ds="' . esc_attr( wp_json_encode( $ds_on ) ) . '"' . ( count( $ds_on ) < 1 ? ' disabled' : '' ) . '>↻ Xếp xoay vòng các tuần còn trống</button>';
	echo '<button type="button" class="button" id="dt-xv-xoa">Bỏ trống tất cả</button>';
	echo '<span class="description">Chỉ điền vào bảng, bấm <b>Lưu</b> ở cuối trang mới ghi lại.</span></div>';
	echo '<table class="dt-tb" id="dt-plan"><tr><th style="width:150px">Chủ nhật</th><th style="width:150px">Thứ 7 nghỉ</th><th>Người trực Chủ nhật</th></tr>';
	$thang = '';
	foreach ( $suns as $sun ) {
		$cur = isset( $o['plan'][ $sun ] ) ? (int) $o['plan'][ $sun ] : -1;
		$th  = wp_date( 'm/Y', strtotime( $sun . ' 12:00:00' ) );
		if ( $th !== $thang ) {
			$thang = $th;
			echo '<tr class="dt-th"><td colspan="3" style="padding:10px 0 4px;font-weight:700;color:#475569;border-bottom:1px solid #e5e7eb">Tháng ' . esc_html( $th ) . '</td></tr>';
		}
		echo '<tr>';
		echo '<td><b>' . esc_html( smlife_duty_vn_date( $sun ) ) . '</b></td>';
		echo '<td>' . esc_html( smlife_duty_vn_date( smlife_duty_sat_of( $sun ) ) ) . '</td>';
		echo '<td><select name="duty[plan][' . esc_attr( $sun ) . ']">';
		echo '<option value="-1" ' . selected( $cur, -1, false ) . '>Chưa xếp - cả nhóm trực</option>';
		foreach ( $staff as $i => $s ) {
			echo '<option value="' . (int) $i . '" ' . selected( $cur, (int) $i, false ) . '>' . esc_html( $s['name'] ) . '</option>';
		}
		echo '</select></td></tr>';
	}
	echo '</table>';
	echo '<div class="dt-dem description" id="dt-dem" style="margin-top:8px"></div>';
	/* JS: xep xoay vong + dem so Chu nhat moi nguoi */
	echo '<script>(function(){var tb=document.getElementById("dt-plan");if(!tb)return;var sel=[].slice.call(tb.querySelectorAll("select"));';
	echo 'function dem(){var c={},trong=0;sel.forEach(function(s){var v=s.value;if(v==="-1"){trong++;return;}var t=s.options[s.selectedIndex].text;c[t]=(c[t]||0)+1;});';
	echo 'var p=[];for(var k in c)p.push(k+": "+c[k]);var el=document.getElementById("dt-dem");if(el)el.textContent="Số Chủ nhật mỗi người: "+(p.length?p.join(" · "):"chưa xếp ai")+(trong?" · Còn trống: "+trong+" tuần":"");}';
	echo 'sel.forEach(function(s){s.addEventListener("change",dem);});dem();';
	echo 'var b=document.getElementById("dt-xv");if(b)b.addEventListener("click",function(){var ds=[];try{ds=JSON.parse(b.getAttribute("data-ds"))||[];}catch(e){}if(!ds.length)return;';
	echo 'var cuoi=-1;sel.forEach(function(s){if(s.value!=="-1")cuoi=parseInt(s.value,10);});var k=ds.indexOf(cuoi);';
	echo 'sel.forEach(function(s){if(s.value!=="-1")return;k=(k+1)%ds.length;s.value=String(ds[k]);});dem();});';
	echo 'var x=document.getElementById("dt-xv-xoa");if(x)x.addEventListener("click",function(){if(!confirm("Bỏ trống toàn bộ lịch Chủ nhật trong bảng?"))return;sel.forEach(function(s){s.value="-1";});dem();});';
	echo '})();</script>';
	echo '<div class="dt-row" style="margin-top:10px"><label class="dt-sw"><input type="checkbox" name="duty[sat_off]" value="1" ' . checked( 1, (int) $o['sat_off'], false ) . ' /><i></i></label>';
	echo '<span class="tx"><strong>Người trực Chủ nhật được nghỉ Thứ 7</strong><em>Tắt đi nếu chỉ muốn xếp trực Chủ nhật mà Thứ 7 vẫn đủ người.</em></span></div>';
	$moc_v = isset( $o['moc_ngay'] ) && preg_match( '/^([01]\d|2[0-3]):[0-5]\d$/', (string) $o['moc_ngay'] ) ? $o['moc_ngay'] : '00:00';
	echo '<div class="dt-fld" style="margin-top:10px"><label>Giờ đổi ngày</label><input type="time" name="duty[moc_ngay]" value="' . esc_attr( $moc_v ) . '" /> <span class="description">Mặc định 00:00. Trước giờ này lịch trực vẫn tính là ngày hôm trước.</span></div>';
	echo '</div></div>';

	/* --- 3. Nhan su --- */
	echo '<div class="dt-card"><h2>3. Nhân sự Sales</h2><div class="in">';
	echo '<table class="dt-tb"><tr><th style="width:46px">Bật</th><th style="width:130px">Tên</th><th style="width:130px">Mô tả</th><th style="width:140px">Số điện thoại</th><th>Link Zalo</th><th style="width:110px">Hotline tổng</th></tr>';
	for ( $i = 0; $i < 5; $i++ ) {
		$s = isset( $o['staff'][ $i ] ) ? $o['staff'][ $i ] : array( 'on' => 0, 'name' => '', 'desc' => '', 'phone' => '', 'zalo' => '', 'main' => 0 );
		echo '<tr>';
		echo '<td><label class="dt-sw"><input type="checkbox" name="duty[staff][' . $i . '][on]" value="1" ' . checked( 1, (int) $s['on'], false ) . ' /><i></i></label></td>';
		echo '<td><input type="text" name="duty[staff][' . $i . '][name]" value="' . esc_attr( $s['name'] ) . '" /></td>';
		echo '<td><input type="text" name="duty[staff][' . $i . '][desc]" value="' . esc_attr( isset( $s['desc'] ) ? $s['desc'] : '' ) . '" /></td>';
		echo '<td><input type="text" name="duty[staff][' . $i . '][phone]" value="' . esc_attr( $s['phone'] ) . '" /></td>';
		echo '<td><input type="text" name="duty[staff][' . $i . '][zalo]" value="' . esc_attr( isset( $s['zalo'] ) ? $s['zalo'] : '' ) . '" placeholder="Để trống = tự tạo từ số điện thoại" /></td>';
		echo '<td><label class="dt-sw"><input type="checkbox" name="duty[staff][' . $i . '][main]" value="1" ' . checked( 1, (int) ( isset( $s['main'] ) ? $s['main'] : 0 ), false ) . ' /><i></i></label></td>';
		echo '</tr>';
	}
	echo '</table>';
	echo '<div class="dt-note" style="margin-top:12px"><b>Hotline tổng</b> là số mà khách gọi vào sẽ đổ về máy bàn của người đang trực, nên số này luôn sáng. Khi người trực đổi, link Zalo của dòng hotline tổng tự đổi theo người đang trực.</div>';
	echo '</div></div>';

	/* --- 4. Hien thi --- */
	echo '<div class="dt-card"><h2>4. Hiển thị trên website</h2><div class="in">';
	echo '<div class="dt-row"><label class="dt-sw"><input type="checkbox" name="duty[enabled]" value="1" ' . checked( 1, (int) $o['enabled'], false ) . ' /><i></i></label>';
	echo '<span class="tx"><strong>Bật Lịch Trực Sales</strong><em>Tắt là mọi hotline đều sáng như bình thường.</em></span></div>';
	echo '<div class="dt-row"><label class="dt-sw"><input type="checkbox" name="duty[show_badge]" value="1" ' . checked( 1, (int) $o['show_badge'], false ) . ' /><i></i></label>';
	echo '<span class="tx"><strong>Hiện nhãn Đang trực / Ngoài ca</strong><em>Nhãn nhỏ nằm cạnh tên nhân viên. Tắt ở đây là tắt mọi chỗ. Bật rồi thì chọn tiếp hiện ở đâu bên dưới.</em></span></div>';
	echo '<div class="dt-row"><label class="dt-sw"><input type="checkbox" name="duty[badge_head]" value="1" ' . checked( 1, (int) $o['badge_head'], false ) . ' /><i></i></label>';
	echo '<span class="tx"><strong>Hiện nhãn ở Hotline Header</strong><em>Khối hotline trên đầu trang, chỉ thấy trên máy tính. Mặc định tắt cho đầu trang gọn.</em></span></div>';
	echo '<div class="dt-row"><label class="dt-sw"><input type="checkbox" name="duty[badge_mobi]" value="1" ' . checked( 1, (int) $o['badge_mobi'], false ) . ' /><i></i></label>';
	echo '<span class="tx"><strong>Hiện nhãn ở Menu Mobile</strong><em>Bảng gọi điện dưới màn hình điện thoại. Ở đây có chỗ nên để bật cho khách biết ai đang trực.</em></span></div>';
	echo '<div class="dt-row"><label class="dt-sw"><input type="checkbox" name="duty[lock_click]" value="1" ' . checked( 1, (int) $o['lock_click'], false ) . ' /><i></i></label>';
	echo '<span class="tx"><strong>Khoá bấm vào người ngoài ca</strong><em>Khách bấm nhầm số đang nghỉ thì không gọi đi.</em></span></div>';
	echo '<div class="dt-row"><label class="dt-sw"><input type="checkbox" name="duty[auto_purge]" value="1" ' . checked( 1, (int) $o['auto_purge'], false ) . ' /><i></i></label>';
	echo '<span class="tx"><strong>Dự phòng khi lịch xoá cache đêm không chạy</strong><em>Bình thường lịch trực chốt lúc 00:05 cùng lần xoá cache đêm. Nếu qua 00:35 mà lần đó chưa chạy (cron lỗi, module tắt) thì lượt truy cập đầu tiên sẽ tự chốt, bộ số có đổi thì xoá cache.</em></span></div>';

	echo '<div class="dt-fld" style="margin-top:12px"><label>Kiểu người ngoài ca</label><select name="duty[off_style]">';
	$sts = array( 'dim' => 'Làm mờ và xám đi', 'hide' => 'Ẩn hẳn' );
	foreach ( $sts as $k => $lb ) {
		echo '<option value="' . esc_attr( $k ) . '" ' . selected( $o['off_style'], $k, false ) . '>' . esc_html( $lb ) . '</option>';
	}
	echo '</select></div>';
	echo '<div class="dt-fld"><label>Chữ đang trực</label><input type="text" name="duty[on_text]" value="' . esc_attr( $o['on_text'] ) . '" style="width:140px" /></div>';
	echo '<div class="dt-fld"><label>Chữ ngoài ca</label><input type="text" name="duty[off_text]" value="' . esc_attr( $o['off_text'] ) . '" style="width:140px" /></div>';
	echo '<div class="dt-fld"><label>Màu nhãn đang trực</label><input type="color" name="duty[on_color]" value="' . esc_attr( $o['on_color'] ) . '" /></div>';
	echo '</div></div>';

	submit_button( 'Lưu lịch trực' );
	echo '</form>';

	/* Gat nut Nghi la luu luon, khong phai keo xuong bam Luu */
	echo '<script>document.querySelectorAll(".smlife-duty-auto").forEach(function(c){c.addEventListener("change",function(){document.getElementById("smlife-duty-form").submit();});});</script>';
	echo '</div>';
}

/* ===== Luu ===== */
add_action( 'admin_post_smlife_duty_save', function () {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( 'Không đủ quyền.' );
	}
	check_admin_referer( 'smlife_duty_save', 'smlife_duty_nonce' );
	$in = isset( $_POST['duty'] ) && is_array( $_POST['duty'] ) ? wp_unslash( $_POST['duty'] ) : array();
	$o  = smlife_duty_opt();

	$o['enabled']    = empty( $in['enabled'] ) ? 0 : 1;
	$o['show_badge'] = empty( $in['show_badge'] ) ? 0 : 1;
	$o['badge_head'] = empty( $in['badge_head'] ) ? 0 : 1;
	$o['badge_mobi'] = empty( $in['badge_mobi'] ) ? 0 : 1;
	$o['lock_click'] = empty( $in['lock_click'] ) ? 0 : 1;
	$o['auto_purge'] = empty( $in['auto_purge'] ) ? 0 : 1;
	$o['sat_off']    = empty( $in['sat_off'] ) ? 0 : 1;
	$o['moc_ngay']   = ( isset( $in['moc_ngay'] ) && preg_match( '/^([01]\d|2[0-3]):[0-5]\d$/', (string) $in['moc_ngay'] ) ) ? (string) $in['moc_ngay'] : ( isset( $o['moc_ngay'] ) ? (string) $o['moc_ngay'] : '00:00' );
	$o['off_style']  = ( isset( $in['off_style'] ) && 'hide' === $in['off_style'] ) ? 'hide' : 'dim';
	$o['on_text']    = isset( $in['on_text'] ) ? sanitize_text_field( $in['on_text'] ) : 'Đang trực';
	$o['off_text']   = isset( $in['off_text'] ) ? sanitize_text_field( $in['off_text'] ) : 'Ngoài ca';
	$c               = isset( $in['on_color'] ) ? sanitize_hex_color( $in['on_color'] ) : '';
	$o['on_color']   = $c ? $c : '#16a34a';

	/* Lich cuoi tuan */
	$plan = is_array( $o['plan'] ) ? $o['plan'] : array();
	if ( isset( $in['plan'] ) && is_array( $in['plan'] ) ) {
		foreach ( $in['plan'] as $d => $v ) {
			if ( ! preg_match( '/^\d{4}-\d{2}-\d{2}$/', (string) $d ) ) {
				continue;
			}
			$v = (int) $v;
			if ( $v >= 0 && $v < 5 ) {
				$plan[ $d ] = $v;
			} else {
				unset( $plan[ $d ] );
			}
		}
	}
	ksort( $plan );
	$o['plan'] = $plan;

	/* Nhan su - nut Nghi gat nhanh nam o the 1, cac o khac nam o the 3.
	 * Khi gat nhanh thi the 3 khong gui gi, nen phai giu lai gia tri cu. */
	$stf  = array();
	$full = isset( $in['staff'] ) && is_array( $in['staff'] ) ? $in['staff'] : array();
	for ( $i = 0; $i < 5; $i++ ) {
		$old = isset( $o['staff'][ $i ] ) && is_array( $o['staff'][ $i ] ) ? $o['staff'][ $i ] : array();
		$r   = isset( $full[ $i ] ) && is_array( $full[ $i ] ) ? $full[ $i ] : array();
		$has = array_key_exists( 'name', $r );
		$stf[] = array(
			'on'    => $has ? ( empty( $r['on'] ) ? 0 : 1 ) : ( empty( $old['on'] ) ? 0 : 1 ),
			'name'  => $has ? sanitize_text_field( $r['name'] ) : ( isset( $old['name'] ) ? $old['name'] : '' ),
			'desc'  => $has ? sanitize_text_field( isset( $r['desc'] ) ? $r['desc'] : '' ) : ( isset( $old['desc'] ) ? $old['desc'] : '' ),
			'phone' => $has ? sanitize_text_field( isset( $r['phone'] ) ? $r['phone'] : '' ) : ( isset( $old['phone'] ) ? $old['phone'] : '' ),
			'zalo'  => $has ? esc_url_raw( isset( $r['zalo'] ) ? $r['zalo'] : '' ) : ( isset( $old['zalo'] ) ? $old['zalo'] : '' ),
			'main'  => $has ? ( empty( $r['main'] ) ? 0 : 1 ) : ( empty( $old['main'] ) ? 0 : 1 ),
			'off'   => empty( $r['off'] ) ? 0 : 1,
		);
	}
	$o['staff'] = $stf;

	/* Bo cac truong cua ban cu, khong dung nua */
	unset( $o['leave'], $o['pin'], $o['self_on'], $o['week'], $o['mode'], $o['pick'], $o['pick_until'] );

	/* 1.20.0: Luu xong KHONG xoa cache. Lich moi chi len website o lan xoa
	   cache dem 00:05 (hoac khi bam Xoa toan bo cache o trang Lich xoa cache). */
	$GLOBALS['smlife_co_doi'] = false;
	update_option( 'smlife_duty', $o );
	$cho = empty( $GLOBALS['smlife_co_doi'] ) ? '0' : '1';
	wp_safe_redirect( admin_url( 'admin.php?page=smlife-lich-truc&saved=1&cho=' . $cho ) );
	exit;
} );
