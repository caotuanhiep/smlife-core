<?php
/**
 * SMLIFE Core - Module: Lich xoa cache
 *
 * Xoa toan bo cache WP Rocket vao mot gio co dinh moi dem (mac dinh 00:05,
 * theo mui gio cua website). Thay cho Cache Lifespan cua WP Rocket, vi
 * Lifespan tinh tu luc tung trang duoc tao nen trang het han rai rac ca ngay,
 * con lich nay gom ve mot moc luc vang khach, Preload nap lai truoc gio sang.
 *
 * Nguyen tac:
 * - Chi dung WP-Cron, hook 'smlife_xc_chay', lap lai 'daily'.
 * - Gio chay tinh bang wp_timezone(), khong dung current_time('timestamp').
 * - Doi gio hoac tat: wp_clear_scheduled_hook roi hen lai.
 * - Moi lan chay ghi nhat ky vao option smlife_xc_log (khong autoload) va error_log.
 * - Khong dung toi Redis Object Cache: cache doi tuong khong chua HTML trang,
 *   xoa no chi lam website cham them.
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

/* ============================================================
 * 1. CAU HINH
 * ============================================================ */
function smlife_xc_opt() {
	$def = array(
		'bat'   => 1,
		'gio'   => '00:05',
		'redis' => 1,
	);
	$s = get_option( 'smlife_xc', array() );
	if ( ! is_array( $s ) ) {
		return $def;
	}
	$o = array_merge( $def, $s );
	if ( ! preg_match( '/^([01]\d|2[0-3]):[0-5]\d$/', (string) $o['gio'] ) ) {
		$o['gio'] = '00:05';
	}
	$o['bat']   = empty( $o['bat'] ) ? 0 : 1;
	$o['redis'] = empty( $o['redis'] ) ? 0 : 1;
	return $o;
}

/* 1.24.0: cac module khac hoi "co job dem de doi khong" (xem smlife_xoa_cache) */
function smlife_xc_dem_bat() {
	$o = smlife_xc_opt();
	return ! empty( $o['bat'] ) && function_exists( 'rocket_clean_domain' );
}
function smlife_xc_gio_chay() {
	$o = smlife_xc_opt();
	return (string) $o['gio'];
}
/* So phut tu moc gio chay gan nhat (<= bay gio) toi bay gio */
function smlife_xc_tre_phut( $now = null ) {
	$now = null === $now ? time() : (int) $now;
	$o   = smlife_xc_opt();
	$tz  = wp_timezone();
	$d   = new DateTime( '@' . $now );
	$d->setTimezone( $tz );
	list( $h, $m ) = array_map( 'intval', explode( ':', $o['gio'] ) );
	$moc = clone $d;
	$moc->setTime( $h, $m, 0 );
	if ( $moc->getTimestamp() > $now ) {
		$moc->modify( '-1 day' );
	}
	return (int) floor( ( $now - $moc->getTimestamp() ) / 60 );
}

/* Moc UTC cua lan chay ke tiep, theo gio cua website */
function smlife_xc_lan_toi( $gio, $now = null ) {
	$tz  = wp_timezone();
	$now = null === $now ? time() : (int) $now;
	$bh  = new DateTime( '@' . $now );
	$bh->setTimezone( $tz );
	list( $h, $m ) = array_map( 'intval', explode( ':', $gio ) );
	$hen = clone $bh;
	$hen->setTime( $h, $m, 0 );
	if ( $hen <= $bh ) {
		$hen->modify( '+1 day' );
	}
	return $hen->getTimestamp();
}

/* Doc loi tra ve tu wp_schedule_event thanh mot dong chu */
function smlife_xc_loi_tx( $r ) {
	if ( is_wp_error( $r ) ) {
		return $r->get_error_code() . ': ' . $r->get_error_message();
	}
	return false === $r ? 'wp_schedule_event tra ve false' : 'hen xong nhung doc lai khong thay lich';
}

/* Xoa lich cu, hen lai theo cau hinh hien tai.
 * 1.19.1: bat ma loi that cua WordPress (tham so wp_error). Neu hen hong thi
 * xoa bo nho tam cua option cron trong object cache (Redis co the giu ban cu)
 * roi thu lai 1 lan. Van hong thi ghi lai ly do vao smlife_xc_loi_hen de
 * trang quan tri hien ra, khong de lich trong ma khong ai biet. */
function smlife_xc_hen_lai() {
	wp_clear_scheduled_hook( 'smlife_xc_chay' );
	$o = smlife_xc_opt();
	if ( empty( $o['bat'] ) ) {
		delete_option( 'smlife_xc_loi_hen' );
		return false;
	}
	$ts = smlife_xc_lan_toi( $o['gio'] );
	$r  = wp_schedule_event( $ts, 'daily', 'smlife_xc_chay', array(), true );
	if ( true === $r && wp_next_scheduled( 'smlife_xc_chay' ) ) {
		delete_option( 'smlife_xc_loi_hen' );
		return true;
	}
	$loi1 = smlife_xc_loi_tx( $r );

	wp_cache_delete( 'cron', 'options' );
	wp_cache_delete( 'alloptions', 'options' );
	wp_cache_delete( 'notoptions', 'options' );
	wp_clear_scheduled_hook( 'smlife_xc_chay' );
	$r = wp_schedule_event( $ts, 'daily', 'smlife_xc_chay', array(), true );
	if ( true === $r && wp_next_scheduled( 'smlife_xc_chay' ) ) {
		update_option( 'smlife_xc_loi_hen', array( 't' => time(), 'lan1' => $loi1, 'lan2' => 'OK sau khi xoa bo nho tam cron', 'ok' => 1 ), false );
		error_log( '[SMLIFE Core] Lich xoa cache - lan 1 hong (' . $loi1 . '), thu lai sau khi xoa bo nho tam cron thi OK' );
		return true;
	}
	$loi2 = smlife_xc_loi_tx( $r );
	update_option( 'smlife_xc_loi_hen', array( 't' => time(), 'lan1' => $loi1, 'lan2' => $loi2, 'ok' => 0 ), false );
	error_log( '[SMLIFE Core] Lich xoa cache - KHONG hen duoc lich. Lan 1: ' . $loi1 . ' | Lan 2: ' . $loi2 );
	return false;
}

/* Tu sua lich neu bi mat hoac lech gio (vd sau khi doi mui gio website).
 * Chi chay trong trang quan tri va luot cron, khong ton gi o trang khach. */
add_action( 'init', function () {
	if ( ! is_admin() && ! wp_doing_cron() ) {
		return;
	}
	$o    = smlife_xc_opt();
	$next = wp_next_scheduled( 'smlife_xc_chay' );
	if ( empty( $o['bat'] ) ) {
		if ( $next ) {
			wp_clear_scheduled_hook( 'smlife_xc_chay' );
		}
		return;
	}
	if ( ! $next || wp_date( 'H:i', $next ) !== $o['gio'] ) {
		/* Vua hong trong 10 phut thi khong thu lai o moi luot admin,
		   tru khi dang mo chinh trang Lich xoa cache. */
		$loi        = get_option( 'smlife_xc_loi_hen' );
		$tren_trang = isset( $_GET['page'] ) && 'smlife-lich-xoa-cache' === $_GET['page']; // phpcs:ignore
		if ( is_array( $loi ) && empty( $loi['ok'] ) && ! $tren_trang && ( time() - (int) $loi['t'] ) < 10 * MINUTE_IN_SECONDS ) {
			return;
		}
		smlife_xc_hen_lai();
	}
}, 20 );

/* Cac module khac hoi: lich xoa cache co sap chay trong khoang $giay quanh bay gio khong.
 * Lich Truc dung ham nay de nhuong viec xoa cache luc nua dem cho lich nay,
 * tranh xoa hai lan lien tiep. Qua han qua $giay ma cron chua chay thi tra ve
 * false de Lich Truc tu xoa, khong cho mai. */
function smlife_xc_sap_chay( $giay = 1800 ) {
	$o = smlife_xc_opt();
	if ( empty( $o['bat'] ) || ! function_exists( 'rocket_clean_domain' ) ) {
		return false;
	}
	$next = wp_next_scheduled( 'smlife_xc_chay' );
	if ( ! $next ) {
		return false;
	}
	$d = (int) $next - time();
	return ( $d <= (int) $giay && $d >= -1 * (int) $giay );
}

/* ============================================================
 * 1b. REDIS (1.20.0)
 * Redis cua hosting dat che do noeviction: day bo nho la tu choi ghi, con
 * WP_REDIS_GRACEFUL nuot loi nen WordPress doc du lieu cu ma khong ai biet.
 * Plugin do muc dung bo nho, qua 90% hoac co loi OOM thi tu xoa.
 * Xoa bang flushDb: sach ca 6 website dung chung socket, chu y dinh vi bo
 * nho cung la dung chung.
 * ============================================================ */
function smlife_xc_redis() {
	if ( ! function_exists( 'wp_using_ext_object_cache' ) || ! wp_using_ext_object_cache() ) {
		return null;
	}
	global $wp_object_cache;
	if ( ! is_object( $wp_object_cache ) || ! method_exists( $wp_object_cache, 'redis_instance' ) ) {
		return null;
	}
	try {
		$r = $wp_object_cache->redis_instance();
	} catch ( Throwable $e ) {
		return null;
	}
	return is_object( $r ) ? $r : null;
}

/* Tra ve array( dung, max, pol, oom, tl ) hoac null neu khong co Redis */
function smlife_xc_redis_mem() {
	$r = smlife_xc_redis();
	if ( ! $r ) {
		return null;
	}
	$oom = false;
	global $wp_object_cache;
	if ( isset( $wp_object_cache->errors ) && is_array( $wp_object_cache->errors ) ) {
		foreach ( $wp_object_cache->errors as $e ) {
			if ( false !== stripos( (string) $e, 'OOM' ) ) {
				$oom = true;
			}
		}
	}
	try {
		$i = $r->info( 'memory' );
	} catch ( Throwable $e ) {
		return array( 'dung' => 0, 'max' => 0, 'pol' => '', 'oom' => $oom, 'tl' => 0, 'loi' => $e->getMessage() );
	}
	if ( is_array( $i ) && isset( $i['Memory'] ) && is_array( $i['Memory'] ) ) {
		$i = $i['Memory']; /* Predis tra ve theo nhom */
	}
	$i    = is_array( $i ) ? $i : array();
	$dung = isset( $i['used_memory'] ) ? (int) $i['used_memory'] : 0;
	$max  = isset( $i['maxmemory'] ) ? (int) $i['maxmemory'] : 0;
	return array(
		'dung' => $dung,
		'max'  => $max,
		'pol'  => isset( $i['maxmemory_policy'] ) ? (string) $i['maxmemory_policy'] : '',
		'oom'  => $oom,
		'tl'   => $max > 0 ? $dung / $max : 0,
	);
}

function smlife_xc_redis_xoa( $ly_do ) {
	$r = smlife_xc_redis();
	if ( ! $r ) {
		return false;
	}
	$t0 = microtime( true );
	try {
		$ok = (bool) $r->flushDb();
	} catch ( Throwable $e ) {
		smlife_xc_ghi( 'redis', false, 0, 'Không xoá được Redis (' . $ly_do . '): ' . $e->getMessage() );
		return false;
	}
	smlife_xc_ghi( 'redis', $ok, (int) round( ( microtime( true ) - $t0 ) * 1000 ), 'Đã xoá Redis object cache - ' . $ly_do );
	return $ok;
}

/* Canh moi 15 phut, chi chay trong luot cron (cron that goi 15 phut/lan) */
add_action( 'init', function () {
	if ( ! function_exists( 'wp_doing_cron' ) || ! wp_doing_cron() ) {
		return;
	}
	if ( ! smlife_khoa_db( 'xc_redis_kt', 14 * MINUTE_IN_SECONDS ) ) {
		return;
	}
	$m = smlife_xc_redis_mem();
	if ( ! $m ) {
		return;
	}
	if ( $m['oom'] ) {
		smlife_xc_redis_xoa( 'canh 15 phút: có lỗi OOM' );
	} elseif ( $m['tl'] >= 0.9 ) {
		smlife_xc_redis_xoa( 'canh 15 phút: dùng ' . round( $m['tl'] * 100 ) . '% bộ nhớ' );
	}
}, 25 );

/* ============================================================
 * 1c. CANH .HTACCESS (1.23.2)
 * Sang 28/10/2026 khoi quy tac WordPress trong .htaccess bi mat, moi duong
 * dan dep tra 404 cua LiteSpeed trong khi trang chu va wp-admin van chay.
 * Moi luot cron (15 phut): neu .htaccess khong con quy tac WordPress thi tu
 * ghi lai bang save_mod_rewrite_rules() (giong bam Luu o Duong dan tinh, chi
 * chen khoi WordPress, giu nguyen cac khoi khac), ghi nhat ky, gui email.
 * Dong thoi ghi lai moi lan file bi sua (gio, kich thuoc) de truy thu pham.
 * ============================================================ */
function smlife_xc_ht_file() {
	return ABSPATH . '.htaccess';
}

/* Doc tinh trang .htaccess: co file, gio sua, kich thuoc, con quy tac WordPress khong */
function smlife_xc_ht_tt() {
	$f = smlife_xc_ht_file();
	clearstatcache( true, $f );
	if ( ! file_exists( $f ) ) {
		return array( 'co' => 0, 'wp' => 0, 't' => 0, 'kb' => 0 );
	}
	$noi = (string) @file_get_contents( $f ); // phpcs:ignore
	return array(
		'co' => 1,
		'wp' => ( false !== strpos( $noi, '# BEGIN WordPress' ) && false !== strpos( $noi, 'index.php' ) ) ? 1 : 0,
		't'  => (int) @filemtime( $f ),
		'kb' => strlen( $noi ),
		/* 1.25.1: dau van tay noi dung, bo qua dong trong. Solid Security ghi lai file
		   moi gio (luot quet xx:30), chi doi dong trong, khong tinh la "bi sua". */
		'md' => md5( preg_replace( '/\R\s*/', "\n", trim( $noi ) ) ),
	);
}

function smlife_xc_ht_kiem( $sua = true ) {
	$tt = smlife_xc_ht_tt();
	$cu = get_option( 'smlife_xc_ht', array() );
	$cu = is_array( $cu ) ? $cu : array();
	$f  = smlife_xc_ht_file();

	/* File bi sua tu lan kiem truoc: ghi nhat ky de truy thu pham */
	$doi = ! empty( $cu['md'] ) ? ( $cu['md'] !== ( isset( $tt['md'] ) ? $tt['md'] : '' ) ) : ( ! empty( $cu['t'] ) && ( (int) $cu['t'] !== $tt['t'] || (int) $cu['kb'] !== $tt['kb'] ) );
	if ( $doi ) {
		$ghi = $tt['co'] ? 'File .htaccess bị sửa lúc ' . wp_date( 'H:i:s d/m/Y', $tt['t'] ) . ', ' . (int) $cu['kb'] . ' → ' . (int) $tt['kb'] . ' byte' . ( $tt['wp'] ? '' : ', MẤT quy tắc WordPress' ) : 'File .htaccess bị XOÁ';
		smlife_xc_ghi( 'htaccess', (bool) $tt['wp'], 0, $ghi );
	}

	$tot = get_option( 'smlife_xc_ht_tot', array() ); /* 1.24.0: ban .htaccess tot gan nhat */
	$tot = ( is_array( $tot ) && ! empty( $tot['noi'] ) ) ? $tot : array();

	/* Duong dan dang Mac dinh (?p=123) thi khong can quy tac rewrite, bo qua */
	if ( $sua && '' !== (string) get_option( 'permalink_structure' ) && ( ! $tt['co'] || ! $tt['wp'] ) ) {
		$ok   = false;
		$cach = '';
		/* 1.24.0: file bi xoa hoac con duoi mot nua ban tot = bi xoa trang/cat cut.
		   Ghi lai NGUYEN ban tot (gom ca khoi Solid Security, WP Rocket, chan bot...).
		   File con nhieu noi dung ma chi thieu khoi WordPress thi chi chen khoi WordPress. */
		if ( $tot && ( ! $tt['co'] || $tt['kb'] < 0.5 * strlen( $tot['noi'] ) ) ) {
			if ( smlife_xc_ht_ghi_nguyen( $tot['noi'] ) ) {
				$cach = 'ghi lại nguyên bản tốt lúc ' . wp_date( 'H:i d/m/Y', (int) $tot['t'] );
				$ok   = smlife_xc_ht_tt()['wp'] ? true : false;
			}
		}
		/* Chay qua PHP CLI (cron that goi php wp-cron.php) thi WordPress tuong khong
		   phai Apache/LiteSpeed, save_mod_rewrite_rules va WP Rocket deu bo qua.
		   May chu nay la LiteSpeed nen ep nhan dang trong luc sua. */
		$ap_cu = isset( $GLOBALS['is_apache'] ) ? $GLOBALS['is_apache'] : null;
		$GLOBALS['is_apache'] = true;
		add_filter( 'got_rewrite', '__return_true', 999 );
		if ( ! $ok ) {
			if ( ! function_exists( 'save_mod_rewrite_rules' ) ) {
				require_once ABSPATH . 'wp-admin/includes/file.php';
				require_once ABSPATH . 'wp-admin/includes/misc.php';
			}
			if ( function_exists( 'save_mod_rewrite_rules' ) ) {
				$ok   = (bool) save_mod_rewrite_rules();
				$cach = 'ghi lại quy tắc WordPress';
			}
		}
		/* Sang 28/10 ca file bi xoa trang, mat luon khoi WP Rocket (gzip, cache trinh duyet).
		 * WP Rocket dang chay ma file khong con khoi cua no thi goi ham cua WP Rocket ghi lai. */
		$wpr = '';
		if ( $ok && function_exists( 'flush_rocket_htaccess' ) && false === strpos( (string) @file_get_contents( $f ), '# BEGIN WP Rocket' ) ) { // phpcs:ignore
			flush_rocket_htaccess();
			clearstatcache( true, $f );
			$wpr = false !== strpos( (string) @file_get_contents( $f ), '# BEGIN WP Rocket' ) ? ' + khối WP Rocket' : ' (khối WP Rocket chưa ghi lại được, vào WP Rocket bấm Lưu)'; // phpcs:ignore
		}
		remove_filter( 'got_rewrite', '__return_true', 999 );
		if ( null === $ap_cu ) {
			unset( $GLOBALS['is_apache'] );
		} else {
			$GLOBALS['is_apache'] = $ap_cu;
		}
		$tt2 = smlife_xc_ht_tt();
		$ok  = $ok && $tt2['wp'];
		$sol = ( $ok && $tot && smlife_xc_ht_co_solid( $tot['noi'] ) && ! smlife_xc_ht_co_solid( (string) @file_get_contents( $f ) ) ) ? ' CHÚ Ý: khối Solid Security chưa có lại, vào Security > Settings > Advanced bỏ tích rồi tích lại 1 ô System Tweaks, bấm Save.' : ''; // phpcs:ignore
		/* That bai thi moi gio ghi 1 dong, khong de day nhat ky 30 dong (che mat lich dem) */
		if ( $ok || smlife_khoa_db( 'xc_ht_log_loi', HOUR_IN_SECONDS ) ) {
			smlife_xc_ghi( 'htaccess', $ok, 0, $ok ? 'Đã tự khôi phục .htaccess (' . $cach . ')' . $wpr . '.' . $sol : 'KHÔNG tự khôi phục được .htaccess, vào Cài đặt > Đường dẫn tĩnh bấm Lưu' );
		}
		/* Email: loi thi toi da 1 lan / 6 gio, sua duoc thi toi da 1 lan / gio. Khoa trong DB. */
		$mail = (string) get_option( 'admin_email' );
		if ( is_email( $mail ) && smlife_khoa_db( $ok ? 'xc_ht_mail_ok' : 'xc_ht_mail', $ok ? HOUR_IN_SECONDS : 6 * HOUR_IN_SECONDS ) ) {
			wp_mail( $mail, '[' . wp_specialchars_decode( get_bloginfo( 'name' ), ENT_QUOTES ) . '] .htaccess mất quy tắc WordPress' . ( $ok ? ', đã tự khôi phục' : ', CẦN XỬ LÝ' ),
				'Lúc ' . wp_date( 'H:i d/m/Y' ) . " SMLIFE Core phát hiện file .htaccess không còn quy tắc WordPress, các trang sản phẩm và danh mục sẽ trả 404.\n\n"
				. ( $ok ? 'Đã tự khôi phục (' . $cach . ')' . $wpr . ', website chạy bình thường.' . $sol . "\nXem nhật ký ở SMLIFE Core > Lịch xoá cache để biết file bị sửa lúc nào." : 'Không tự sửa được. Vào wp-admin > Cài đặt > Đường dẫn tĩnh, bấm Lưu thay đổi.' ) );
		}
		$tt = $tt2;
	}
	/* 1.24.0: file van chay (con khoi WordPress) nhung thieu khoi khac */
	if ( $sua && $tt['co'] && $tt['wp'] ) {
		$noi = (string) @file_get_contents( $f ); // phpcs:ignore
		/* WP Rocket dang bat ma khong co khoi cua no: goi ham cua WP Rocket ghi lai
		   (giong bam Luu o WP Rocket). Toi da 1 lan / gio de khong danh nhau neu
		   WP Rocket co chu y khong ghi. */
		if ( function_exists( 'flush_rocket_htaccess' ) && false === strpos( $noi, '# BEGIN WP Rocket' ) && smlife_khoa_db( 'xc_ht_wpr', HOUR_IN_SECONDS ) ) {
			$ap_cu = isset( $GLOBALS['is_apache'] ) ? $GLOBALS['is_apache'] : null;
			$GLOBALS['is_apache'] = true;
			flush_rocket_htaccess();
			if ( null === $ap_cu ) {
				unset( $GLOBALS['is_apache'] );
			} else {
				$GLOBALS['is_apache'] = $ap_cu;
			}
			clearstatcache( true, $f );
			$noi = (string) @file_get_contents( $f ); // phpcs:ignore
			$co  = false !== strpos( $noi, '# BEGIN WP Rocket' );
			smlife_xc_ghi( 'htaccess', $co, 0, $co ? 'Khối WP Rocket bị mất, đã tự ghi lại' : 'Khối WP Rocket bị mất, chưa ghi lại được, vào WP Rocket bấm Lưu' );
			$tt = smlife_xc_ht_tt();
		}
		/* Khoi Solid Security bien mat so voi ban tot: co the do tat tinh nang ben
		   Solid nen KHONG tu ghi lai, chi bao. Moi ban tot chi bao 1 lan. */
		if ( $tot && smlife_xc_ht_co_solid( $tot['noi'] ) && ! smlife_xc_ht_co_solid( $noi ) ) {
			$md = (string) $tot['md5']; /* bao 1 lan cho moi ban tot */
			if ( get_option( 'smlife_xc_ht_solid_bao' ) !== $md ) {
				update_option( 'smlife_xc_ht_solid_bao', $md, false );
				smlife_xc_ghi( 'htaccess', false, 0, 'Khối Solid Security không còn trong .htaccess (chặn PHP trong uploads/plugins đang tắt). Nếu không phải tự tắt: vào Security > Settings > Advanced bỏ tích rồi tích lại 1 ô System Tweaks, bấm Save.' );
			}
		}
	}
	if ( $sua && $tt['co'] && smlife_xc_ht_dau() ) {
		$tt = smlife_xc_ht_tt();
	}
	/* Luu ban tot: co khoi WordPress, khong nho hon mot nua ban tot cu */
	if ( $tt['co'] && $tt['wp'] ) {
		$noi = (string) @file_get_contents( $f ); // phpcs:ignore
		if ( strlen( $noi ) === (int) $tt['kb'] && ( empty( $tot['md5'] ) || md5( $noi ) !== $tot['md5'] ) ) {
			/* Nho hon mot nua ban tot cu (vd vua bi xoa trang roi ai do bam Luu Duong dan
			   tinh, chi con khoi WordPress): chua nhan ngay, giu ban tot cu 24 gio.
			   Qua 24 gio van vay thi coi la thay doi co chu y, nhan ban moi. */
			$nho = $tot && strlen( $noi ) < 0.5 * strlen( $tot['noi'] );
			$tu  = (int) get_option( 'smlife_xc_ht_nho', 0 );
			if ( $nho && ! $tu ) {
				update_option( 'smlife_xc_ht_nho', time(), false );
			}
			if ( ! $nho || ( $tu && time() - $tu > DAY_IN_SECONDS ) ) {
				update_option( 'smlife_xc_ht_tot', array( 'noi' => $noi, 'md5' => md5( $noi ), 't' => time() ), false );
				delete_option( 'smlife_xc_ht_nho' );
			}
		}
	}
	update_option( 'smlife_xc_ht', array( 't' => $tt['t'], 'kb' => $tt['kb'], 'md' => isset( $tt['md'] ) ? $tt['md'] : '', 'kt' => time() ), false );
	return $tt;
}

function smlife_xc_ht_co_solid( $noi ) {
	return false !== strpos( (string) $noi, '# BEGIN Solid Security' ) || false !== strpos( (string) $noi, '# BEGIN iThemes Security' );
}

/* Ghi nguyen noi dung vao .htaccess an toan: ghi file tam roi doi ten (nguyen tu),
   giu quyen file cu. Tra ve true neu thanh cong. */
function smlife_xc_ht_ghi_nguyen( $noi ) {
	$f   = smlife_xc_ht_file();
	$dir = dirname( $f );
	if ( '' === (string) $noi || ! is_writable( $dir ) || ( file_exists( $f ) && ! is_writable( $f ) ) ) {
		return false;
	}
	$tam = $dir . '/.htaccess.smlife-' . wp_generate_password( 8, false );
	if ( false === @file_put_contents( $tam, $noi, LOCK_EX ) ) { // phpcs:ignore
		@unlink( $tam ); // phpcs:ignore
		return false;
	}
	@chmod( $tam, file_exists( $f ) ? ( fileperms( $f ) & 0777 ) : 0644 ); // phpcs:ignore
	if ( ! @rename( $tam, $f ) ) { // phpcs:ignore
		@unlink( $tam ); // phpcs:ignore
		return false;
	}
	clearstatcache( true, $f );
	return true;
}

/* ============================================================
 * 1d. NGOAI LE PHP TRONG PLUGINS (1.23.4)
 * Solid Security "Disable PHP in Plugins" chan moi file .php goi thang trong
 * wp-content/plugins. Mot so plugin can goi thang 1 file (Woo VN Checkout tai
 * phuong/xa qua get-address.php) nen bi 403, trang thanh toan khong ra phuong.
 * Giai phap: khoi "# BEGIN SMLIFE Core" o DAU .htaccess, cho phep dung tung
 * file duoc liet ke ([L] dung xu ly, quy tac chan cua Solid ben duoi khong chay).
 * Moi file khac trong plugins van bi chan nhu cu.
 * ============================================================ */
function smlife_xc_ht_ngoai_le() {
	if ( ! defined( 'WP_PLUGIN_DIR' ) ) {
		return array();
	}
	$ds = array(
		'woo-vietnam-checkout/get-address.php', // Woo VN Checkout: tai phuong/xa o trang thanh toan
	);
	$ds = (array) apply_filters( 'smlife_xc_ht_ngoai_le', $ds );
	$goc = trim( str_replace( '\\', '/', substr( WP_PLUGIN_DIR, strlen( untrailingslashit( ABSPATH ) ) ) ), '/' );
	$ra  = array();
	foreach ( $ds as $f ) {
		$f = trim( (string) $f, '/' );
		if ( '' === $f || false !== strpos( $f, '..' ) || ! preg_match( '/\.php$/i', $f ) || ! file_exists( WP_PLUGIN_DIR . '/' . $f ) ) {
			continue;
		}
		$ra[] = '^' . preg_quote( $goc . '/' . $f, '' ) . '$';
	}
	return $ra;
}

function smlife_xc_ht_khoi() {
	$ds = smlife_xc_ht_ngoai_le();
	if ( ! $ds ) {
		return '';
	}
	$k  = "# BEGIN SMLIFE Core\n# Cho phep goi thang file PHP plugin can thiet (Solid Security chan PHP trong plugins). Khoi nay phai nam DAU file.\n";
	$k .= "<IfModule mod_rewrite.c>\nRewriteEngine On\n";
	foreach ( $ds as $r ) {
		$k .= 'RewriteRule ' . $r . " - [L]\n";
	}
	return $k . "</IfModule>\n# END SMLIFE Core\n";
}

/* Dat khoi SMLIFE Core len dau file. Tra ve true neu da ghi file. */
function smlife_xc_ht_dau() {
	$f = smlife_xc_ht_file();
	if ( ! file_exists( $f ) || ! is_writable( $f ) ) {
		return false;
	}
	$h = @fopen( $f, 'c+' ); // phpcs:ignore
	if ( ! $h ) {
		return false;
	}
	if ( ! flock( $h, LOCK_EX ) ) {
		fclose( $h );
		return false;
	}
	clearstatcache( true, $f );
	$co  = (int) filesize( $f );
	$noi = $co > 0 ? (string) fread( $h, $co ) : '';
	/* Doc hong (file co noi dung ma doc ra rong/thieu) thi dung lai, khong bao gio ghi de */
	if ( strlen( $noi ) !== $co ) {
		flock( $h, LOCK_UN );
		fclose( $h );
		return false;
	}
	/* 1.24.0: file chua co khoi WordPress (dang bi ghi do, bi cat) thi khong dong vao */
	if ( false === strpos( $noi, '# BEGIN WordPress' ) ) {
		flock( $h, LOCK_UN );
		fclose( $h );
		return false;
	}
	$khoi = smlife_xc_ht_khoi();
	$bo   = preg_replace( '/[ \t]*# BEGIN SMLIFE Core.*?# END SMLIFE Core[ \t]*\r?\n?/s', '', $noi );
	$moi  = '' === $khoi ? $bo : $khoi . "\n" . ltrim( $bo, "\r\n" );
	/* 1.25.1: khoi da dung noi dung, chi co 1 khoi, va dung TRUOC khoi Solid Security
	   thi coi nhu dat, khong ghi lai chi vi lech dong trong (truoc day moi gio giang
	   co voi Solid 1 lan, nhat ky day dong .htaccess). */
	$vt_sm = strpos( $noi, '# BEGIN SMLIFE Core' );
	$vt_so = false;
	foreach ( array( '# BEGIN Solid Security', '# BEGIN iThemes Security' ) as $mk ) {
		$x = strpos( $noi, $mk );
		if ( false !== $x && ( false === $vt_so || $x < $vt_so ) ) {
			$vt_so = $x;
		}
	}
	$dat = '' !== $khoi && 1 === substr_count( $noi, '# BEGIN SMLIFE Core' ) && false !== strpos( $noi, trim( $khoi ) ) && ( false === $vt_so || $vt_sm < $vt_so );
	if ( $moi === $noi || $dat ) {
		flock( $h, LOCK_UN );
		fclose( $h );
		return false;
	}
	rewind( $h );
	ftruncate( $h, 0 );
	$ok = false !== fwrite( $h, $moi );
	fflush( $h );
	flock( $h, LOCK_UN );
	fclose( $h );
	if ( $ok ) {
		smlife_xc_ghi( 'htaccess', true, 0, '' === $khoi ? 'Đã gỡ khối ngoại lệ SMLIFE Core trong .htaccess' : 'Đã đặt khối ngoại lệ SMLIFE Core lên đầu .htaccess (' . count( smlife_xc_ht_ngoai_le() ) . ' file PHP plugin được phép gọi thẳng)' );
	}
	return $ok;
}

/* Tat plugin: go khoi ngoai le SMLIFE Core (smlife-core.php goi) */
function smlife_xc_ht_go_khoi() {
	add_filter( 'smlife_xc_ht_ngoai_le', '__return_empty_array', 999 );
	smlife_xc_ht_dau();
	remove_filter( 'smlife_xc_ht_ngoai_le', '__return_empty_array', 999 );
}

/* Vua cai/nang cap plugin: dat khoi ngay khi admin mo trang, khong cho cron 15 phut */
add_action( 'admin_init', function () {
	if ( get_option( 'smlife_xc_ht_ver' ) === SMLIFE_CORE_VER || ! current_user_can( 'manage_options' ) ) {
		return;
	}
	update_option( 'smlife_xc_ht_ver', SMLIFE_CORE_VER, false );
	smlife_xc_ht_kiem( true );
} );

add_action( 'init', function () {
	if ( ! function_exists( 'wp_doing_cron' ) || ! wp_doing_cron() ) {
		return;
	}
	if ( ! smlife_khoa_db( 'xc_ht_kt', 10 * MINUTE_IN_SECONDS ) ) {
		return;
	}
	smlife_xc_ht_kiem( true );
}, 30 );

/* ============================================================
 * 2. CHAY
 * ============================================================ */
function smlife_xc_ghi( $kieu, $ok, $ms, $note ) {
	$log = get_option( 'smlife_xc_log', array() );
	if ( ! is_array( $log ) ) {
		$log = array();
	}
	array_unshift( $log, array(
		't'    => time(),
		'kieu' => (string) $kieu,
		'ok'   => $ok ? 1 : 0,
		'ms'   => (int) $ms,
		'note' => (string) $note,
	) );
	$log = array_slice( $log, 0, 30 );
	update_option( 'smlife_xc_log', $log, false );
	error_log( '[SMLIFE Core] Lich xoa cache - ' . $kieu . ' - ' . ( $ok ? 'OK' : 'LOI' ) . ' - ' . $note . ' - ' . (int) $ms . 'ms' ); // phpcs:ignore
}

/* $kieu: 'tu-dong' (cron) hoac 'bam-tay' (nut Xoa ngay) */
function smlife_xc_chay( $kieu = 'tu-dong' ) {
	if ( ! is_string( $kieu ) || '' === $kieu ) {
		$kieu = 'tu-dong';
	}
	/* Khoa 5 phut: cron that va WP-Cron cung goi mot luc thi chi chay mot lan.
	   1.24.0: khoa trong DB, Redis day hay vua bi xoa cung khong mat khoa. */
	if ( 'tu-dong' === $kieu && ! smlife_khoa_db( 'xc_chay', 5 * MINUTE_IN_SECONDS ) ) {
		return false;
	}

	/* 1.24.0: cron hong roi chay bu luc 10h sang thi khong duoc xoa sach cache
	   + Redis giua gio dong khach. Tre qua 60 phut: chi chot du lieu ngay moi,
	   lich truc co doi thi xoa rieng cache trang (de so truc dung), con lai de dem sau. */
	$tre = 'tu-dong' === $kieu ? smlife_xc_tre_phut() : 0;
	$duty_truoc = $tre > 60 ? smlife_opt_db( 'smlife_duty_snap' ) : null;

	/* 1.20.0: cac module chot du lieu cua ngay moi truoc khi xoa cache
	   (Lich Truc chot ai truc hom nay). */
	do_action( 'smlife_xc_truoc_xoa', $kieu );

	if ( $tre > 60 ) {
		$duty_sau = smlife_opt_db( 'smlife_duty_snap' );
		$doi      = is_array( $duty_truoc ) && is_array( $duty_sau ) && isset( $duty_truoc['st'], $duty_sau['st'] ) && wp_json_encode( $duty_truoc['st'] ) !== wp_json_encode( $duty_sau['st'] );
		if ( $doi && function_exists( 'rocket_clean_domain' ) ) {
			rocket_clean_domain();
		}
		smlife_xc_ghi( $kieu, true, 0, 'Cron chạy trễ ' . $tre . ' phút: chỉ chốt dữ liệu ngày mới' . ( $doi ? ', lịch trực đổi nên đã xoá cache trang' : '' ) . '. Không xoá Redis giữa ngày, phần còn lại để lịch đêm sau.' );
		return true;
	}

	/* 1.20.0: lich dem xoa luon Redis object cache, giai phong bo nho */
	$o_xc = smlife_xc_opt();
	if ( 'tu-dong' === $kieu && ! empty( $o_xc['redis'] ) ) {
		smlife_xc_redis_xoa( 'lịch đêm' );
	}

	if ( ! function_exists( 'rocket_clean_domain' ) ) {
		smlife_xc_ghi( $kieu, false, 0, 'Không tìm thấy WP Rocket (rocket_clean_domain)' );
		return false;
	}
	$t0 = microtime( true );
	rocket_clean_domain();
	$ms = (int) round( ( microtime( true ) - $t0 ) * 1000 );

	$note = 'Đã xoá toàn bộ cache';
	if ( function_exists( 'get_rocket_option' ) && get_rocket_option( 'manual_preload' ) ) {
		$note .= ', Preload sẽ nạp lại';
	}
	/* 1.24.0: thay doi cai dat dang cho da len web */
	$cho = get_option( 'smlife_cho_xoa', array() );
	if ( ! empty( $cho['ds'] ) && is_array( $cho['ds'] ) ) {
		$note .= ', đưa lên web: ' . implode( ', ', array_keys( $cho['ds'] ) );
	}
	delete_option( 'smlife_cho_xoa' );
	smlife_xc_ghi( $kieu, true, $ms, $note );
	return true;
}
add_action( 'smlife_xc_chay', function () {
	smlife_xc_chay( 'tu-dong' );
} );

/* ============================================================
 * 3. TRANG QUAN TRI
 * ============================================================ */
add_action( 'admin_menu', function () {
	add_submenu_page(
		'smlife-core',
		'Lịch xoá cache',
		'Lịch xoá cache',
		'manage_options',
		'smlife-lich-xoa-cache',
		'smlife_xc_page'
	);
}, 96 );

function smlife_xc_tg( $ts ) {
	return $ts ? wp_date( 'H:i - d/m/Y', (int) $ts ) : '-';
}

function smlife_xc_page() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}
	$o    = smlife_xc_opt();
	$next = wp_next_scheduled( 'smlife_xc_chay' );
	$log  = get_option( 'smlife_xc_log', array() );
	$log  = is_array( $log ) ? $log : array();

	echo '<div class="wrap smlife-wrap">';
	echo '<div class="smc-hd"><h1>Lịch xoá cache</h1>';
	echo '<p>Xoá toàn bộ cache WP Rocket vào một giờ cố định mỗi đêm, Preload tự nạp lại trước giờ khách vào. Thay cho mục Cache Lifespan của WP Rocket.</p></div>';

	if ( isset( $_GET['saved'] ) ) {
		echo '<div class="notice notice-success is-dismissible"><p>Đã lưu và hẹn lại lịch.</p></div>';
	}
	if ( isset( $_GET['ran'] ) ) {
		$ok = '1' === $_GET['ran'];
		echo '<div class="notice ' . ( $ok ? 'notice-success' : 'notice-error' ) . ' is-dismissible"><p>' . ( $ok ? 'Đã xoá toàn bộ cache.' : 'Chưa xoá được. Xem dòng nhật ký mới nhất bên dưới.' ) . '</p></div>';
	}

	/* --- Kiem tra moi truong --- */
	$canh = array();
	if ( ! function_exists( 'rocket_clean_domain' ) ) {
		$canh[] = 'Không tìm thấy WP Rocket. Lịch vẫn chạy nhưng không có gì để xoá.';
	}
	if ( function_exists( 'get_rocket_option' ) ) {
		$ls = (int) get_rocket_option( 'purge_cron_interval' );
		if ( $ls > 0 ) {
			$canh[] = 'WP Rocket vẫn đang đặt Cache Lifespan = ' . $ls . ' ' . esc_html( (string) get_rocket_option( 'purge_cron_unit' ) ) . '. Nên đặt về 0 để không chồng hai lịch.';
		}
	}
	$tzs = (string) get_option( 'timezone_string' );
	if ( '' === $tzs && 0.0 === (float) get_option( 'gmt_offset' ) ) {
		$canh[] = 'Múi giờ WordPress đang là UTC+0, lịch sẽ chạy lệch 7 tiếng. Vào Cài đặt > Tổng quan chọn Asia/Ho_Chi_Minh.';
	}
	if ( ! empty( $o['bat'] ) && $next && ( time() - $next ) > 20 * MINUTE_IN_SECONDS ) {
		$canh[] = 'Lần chạy hẹn lúc ' . smlife_xc_tg( $next ) . ' đã quá hạn hơn 20 phút mà chưa chạy. Kiểm tra cron thật trên hosting (wp-cron.php mỗi 15 phút).';
	}
	foreach ( $canh as $c ) {
		echo '<div class="notice notice-warning"><p>' . esc_html( $c ) . '</p></div>';
	}

	/* --- 1.19.1: Chan doan khi lich dang bat ma khong hen duoc --- */
	$loi_hen = get_option( 'smlife_xc_loi_hen' );
	if ( ! empty( $o['bat'] ) && ( ! $next || ( is_array( $loi_hen ) && empty( $loi_hen['ok'] ) ) ) ) {
		$cron  = function_exists( '_get_cron_array' ) ? _get_cron_array() : array();
		$cron  = is_array( $cron ) ? $cron : array();
		$tong  = 0;
		$tre   = 0;
		foreach ( $cron as $ts => $hooks ) {
			foreach ( (array) $hooks as $h ) {
				$tong += count( (array) $h );
				if ( (int) $ts < time() - HOUR_IN_SECONDS ) {
					$tre += count( (array) $h );
				}
			}
		}
		$dong = array(
			'Lần thử 1'                  => is_array( $loi_hen ) ? $loi_hen['lan1'] : '(chưa ghi nhận)',
			'Lần thử 2 (xoá cache cron)' => is_array( $loi_hen ) ? $loi_hen['lan2'] : '(chưa ghi nhận)',
			'Thời điểm thử'              => is_array( $loi_hen ) ? smlife_xc_tg( $loi_hen['t'] ) : '-',
			'Object cache ngoài (Redis)' => wp_using_ext_object_cache() ? 'Có' : 'Không',
			'DISABLE_WP_CRON'            => ( defined( 'DISABLE_WP_CRON' ) && DISABLE_WP_CRON ) ? 'true' : 'false',
			'ALTERNATE_WP_CRON'          => ( defined( 'ALTERNATE_WP_CRON' ) && ALTERNATE_WP_CRON ) ? 'true' : 'false',
			'Tổng sự kiện cron'          => (string) $tong,
			'Sự kiện trễ hơn 1 giờ'      => (string) $tre,
			'Plugin chặn pre_schedule_event' => has_filter( 'pre_schedule_event' ) ? 'Có' : 'Không',
		);
		echo '<div class="notice notice-error"><p><b>Lịch đang bật nhưng chưa hẹn được với WordPress.</b> Trong lúc chờ sửa, WP Rocket không tự hết hạn cache (Lifespan đang 0), nên mỗi ngày bấm "Xoá toàn bộ cache" bên dưới một lần, hoặc chụp bảng này gửi Claude.</p>';
		echo '<table class="widefat striped" style="max-width:720px;margin:6px 0 10px"><tbody>';
		foreach ( $dong as $k => $v ) {
			echo '<tr><td style="width:240px">' . esc_html( $k ) . '</td><td><code>' . esc_html( (string) $v ) . '</code></td></tr>';
		}
		echo '</tbody></table></div>';
	} elseif ( is_array( $loi_hen ) && ! empty( $loi_hen['ok'] ) ) {
		echo '<div class="notice notice-info"><p>Lần hẹn lịch lúc ' . esc_html( smlife_xc_tg( $loi_hen['t'] ) ) . ' phải thử lại mới được (lần 1: <code>' . esc_html( $loi_hen['lan1'] ) . '</code>). Hiện lịch đã ổn.</p></div>';
	}

	/* --- Trang thai --- */
	$cuoi = isset( $log[0] ) ? $log[0] : null;
	echo '<div class="smlife-card"><h2>Trạng thái</h2>';
	echo '<table class="form-table" role="presentation">';
	echo '<tr><th scope="row">Lịch</th><td>' . ( ! empty( $o['bat'] ) ? '<b style="color:#16a34a">Đang bật</b>, chạy lúc <b>' . esc_html( $o['gio'] ) . '</b> mỗi ngày' : '<b style="color:#b91c1c">Đang tắt</b>' ) . '</td></tr>';
	echo '<tr><th scope="row">Lần chạy tới</th><td>' . esc_html( smlife_xc_tg( $next ) ) . '</td></tr>';
	echo '<tr><th scope="row">Lần chạy gần nhất</th><td>' . ( $cuoi ? esc_html( smlife_xc_tg( $cuoi['t'] ) . ' - ' . ( $cuoi['ok'] ? 'thành công' : 'lỗi' ) . ' - ' . $cuoi['note'] ) : 'Chưa chạy lần nào' ) . '</td></tr>';
	echo '<tr><th scope="row">Giờ website</th><td>' . esc_html( wp_date( 'H:i:s - d/m/Y' ) ) . ' (' . esc_html( wp_timezone_string() ) . ')</td></tr>';
	$ht = smlife_xc_ht_kiem( true );
	echo '<tr><th scope="row">.htaccess</th><td>' . ( $ht['co'] && $ht['wp'] ? '<b style="color:#16a34a">Còn quy tắc WordPress</b>' : '<b style="color:#b91c1c">Mất quy tắc WordPress</b>' ) . ( $ht['co'] ? ' · sửa lần cuối ' . esc_html( wp_date( 'H:i d/m/Y', $ht['t'] ) ) . ' · ' . (int) $ht['kb'] . ' byte' : ' · không có file' ) . '<br /><span class="description">' . ( ( $tot_ht = get_option( 'smlife_xc_ht_tot' ) ) && ! empty( $tot_ht['noi'] ) ? 'Bản sao an toàn lưu lúc ' . esc_html( wp_date( 'H:i d/m/Y', (int) $tot_ht['t'] ) ) . ' (' . strlen( $tot_ht['noi'] ) . ' byte' . ( smlife_xc_ht_co_solid( $tot_ht['noi'] ) ? ', có khối Solid Security' : '' ) . '). File bị xoá trắng thì ghi lại nguyên bản này. ' : '' ) . 'Mỗi 15 phút plugin tự kiểm tra, mất quy tắc thì tự ghi lại và gửi email về ' . esc_html( (string) get_option( 'admin_email' ) ) . '. Mọi lần file bị sửa đều được ghi vào nhật ký bên dưới.</span></td></tr>';
	$rm = smlife_xc_redis_mem();
	if ( $rm ) {
		$mb  = function ( $b ) {
			return number_format( $b / 1048576, 1, ',', '.' ) . ' MB';
		};
		$txt = 'Dùng ' . $mb( $rm['dung'] ) . ( $rm['max'] > 0 ? ' / ' . $mb( $rm['max'] ) . ' (' . round( $rm['tl'] * 100 ) . '%)' : ' (không giới hạn)' );
		if ( '' !== $rm['pol'] ) {
			$txt .= ' - chính sách: ' . $rm['pol'];
		}
		$mau = ( $rm['oom'] || $rm['tl'] >= 0.9 ) ? '#b91c1c' : ( $rm['tl'] >= 0.7 ? '#b45309' : '#16a34a' );
		echo '<tr><th scope="row">Redis</th><td><b style="color:' . esc_attr( $mau ) . '">' . esc_html( $txt ) . '</b>' . ( $rm['oom'] ? ' <b style="color:#b91c1c">- đang lỗi OOM</b>' : '' );
		if ( 'noeviction' === $rm['pol'] ) {
			echo '<br /><span class="description">Chế độ noeviction: đầy bộ nhớ là Redis từ chối ghi, WordPress đọc dữ liệu cũ. Nên nhờ AZDIGI đổi sang allkeys-lru. Trong lúc chờ, plugin tự xoá Redis khi dùng quá 90%.</span>';
		}
		echo '</td></tr>';
	}
	echo '</table></div>';

	/* --- Cai dat --- */
	echo '<div class="smlife-card"><h2>Cài đặt</h2>';
	echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '">';
	echo '<input type="hidden" name="action" value="smlife_xc_save" />';
	wp_nonce_field( 'smlife_xc_save', 'smlife_xc_nonce' );
	echo '<table class="form-table" role="presentation">';
	echo '<tr><th scope="row">Bật lịch</th><td><label><input type="checkbox" name="xc[bat]" value="1" ' . checked( 1, (int) $o['bat'], false ) . ' /> Tự xoá toàn bộ cache mỗi đêm</label></td></tr>';
	echo '<tr><th scope="row">Xoá cả Redis</th><td><label><input type="checkbox" name="xc[redis]" value="1" ' . checked( 1, (int) $o['redis'], false ) . ' /> Lịch đêm xoá luôn Redis object cache (giải phóng bộ nhớ Redis dùng chung 6 website)</label></td></tr>';
	echo '<tr><th scope="row">Giờ chạy</th><td><input type="time" name="xc[gio]" value="' . esc_attr( $o['gio'] ) . '" /> <span class="description">Mặc định 00:05. Cron thật chạy 15 phút một lần nên lệnh thực tế chạy trong khoảng 00:05 đến 00:20.</span></td></tr>';
	echo '</table>';
	submit_button( 'Lưu và hẹn lại lịch' );
	echo '</form></div>';

	/* --- Xoa ngay --- */
	echo '<div class="smlife-card"><h2>Xoá ngay</h2>';
	echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '" onsubmit="return confirm(\'Xoá toàn bộ cache ngay bây giờ?\');">';
	echo '<input type="hidden" name="action" value="smlife_xc_ngay" />';
	wp_nonce_field( 'smlife_xc_ngay', 'smlife_xc_nonce' );
	echo '<p class="description" style="margin:0 0 10px">Dùng để thử lịch, khi vừa sửa UX Block, menu, hoặc khi cần áp dụng Lịch Trực ngay (bấm nút này là chốt luôn lịch trực). Cả website sẽ chậm vài phút đầu cho tới khi Preload nạp xong.</p>';
	submit_button( 'Xoá toàn bộ cache', 'secondary', 'submit', false );
	echo '</form></div>';

	/* --- Nhat ky --- */
	echo '<div class="smlife-card"><h2>Nhật ký 30 lần gần nhất</h2>';
	if ( empty( $log ) ) {
		echo '<p class="description">Chưa có.</p>';
	} else {
		echo '<table class="widefat striped"><thead><tr><th style="width:170px">Thời điểm</th><th style="width:90px">Kiểu</th><th style="width:80px">Kết quả</th><th style="width:70px">Thời gian</th><th>Ghi chú</th></tr></thead><tbody>';
		foreach ( $log as $r ) {
			echo '<tr><td>' . esc_html( smlife_xc_tg( $r['t'] ) ) . '</td>';
			echo '<td>' . ( 'bam-tay' === $r['kieu'] ? 'Bấm tay' : ( 'redis' === $r['kieu'] ? 'Redis' : ( 'htaccess' === $r['kieu'] ? '.htaccess' : 'Tự động' ) ) ) . '</td>';
			echo '<td>' . ( $r['ok'] ? '<span style="color:#16a34a">OK</span>' : '<span style="color:#b91c1c">Lỗi</span>' ) . '</td>';
			echo '<td>' . (int) $r['ms'] . ' ms</td>';
			echo '<td>' . esc_html( $r['note'] ) . '</td></tr>';
		}
		echo '</tbody></table>';
	}
	echo '</div>';

	echo '</div>';
}

/* ===== Luu ===== */
add_action( 'admin_post_smlife_xc_save', function () {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( 'Không đủ quyền.' );
	}
	check_admin_referer( 'smlife_xc_save', 'smlife_xc_nonce' );
	$in  = isset( $_POST['xc'] ) && is_array( $_POST['xc'] ) ? wp_unslash( $_POST['xc'] ) : array();
	$gio = isset( $in['gio'] ) ? (string) $in['gio'] : '';
	$o   = array(
		'bat'   => empty( $in['bat'] ) ? 0 : 1,
		'gio'   => preg_match( '/^([01]\d|2[0-3]):[0-5]\d$/', $gio ) ? $gio : '00:05',
		'redis' => empty( $in['redis'] ) ? 0 : 1,
	);
	update_option( 'smlife_xc', $o, true );
	/* Doi gio hoac tat deu xoa lich cu truoc, roi moi hen lai */
	smlife_xc_hen_lai();
	wp_safe_redirect( admin_url( 'admin.php?page=smlife-lich-xoa-cache&saved=1' ) );
	exit;
} );

/* ===== Xoa ngay ===== */
add_action( 'admin_post_smlife_xc_ngay', function () {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( 'Không đủ quyền.' );
	}
	check_admin_referer( 'smlife_xc_ngay', 'smlife_xc_nonce' );
	$ok = smlife_xc_chay( 'bam-tay' );
	wp_safe_redirect( admin_url( 'admin.php?page=smlife-lich-xoa-cache&ran=' . ( $ok ? '1' : '0' ) ) );
	exit;
} );
