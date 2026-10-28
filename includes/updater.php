<?php
/**
 * SMLIFE Core - Tu cap nhat tu GitHub (1.25.0)
 *
 * Moi website cai SMLIFE Core tu hoi kho GitHub (mac dinh caotuanhiep/smlife-core)
 * xem co ban moi tren nhanh main khong. Co thi hien "Co ban cap nhat" o trang Plugin nhu plugin
 * tu wordpress.org, bam Cap nhat la xong. Cai dat cua tung web nam trong database
 * nen khong bi anh huong.
 *
 * Kho rieng tu: them vao wp-config.php cua tung web
 *     define( 'SMLIFE_CORE_GH_TOKEN', 'github_pat_...' );
 * Ma chi can quyen Contents: Read-only cho dung kho smlife-core. Ma chi duoc gui
 * toi api.github.com, khong bao gio in ra man hinh.
 *
 * Phat hanh = day code len nhanh main (doi SMLIFE_CORE_GH_BRANCH neu can). Plugin doc
 * dong Version: cua smlife-core.php tren main, lon hon ban dang chay la bao cap nhat,
 * goi tai la zipball cua dung commit do. Khong can Release hay tag.
 *
 * Tu viet, khong dung thu vien ngoai.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function smlife_gh_repo() {
	$r = defined( 'SMLIFE_CORE_GH_REPO' ) ? (string) SMLIFE_CORE_GH_REPO : 'caotuanhiep/smlife-core';
	return preg_match( '#^[A-Za-z0-9_.-]+/[A-Za-z0-9_.-]+$#', $r ) ? $r : 'caotuanhiep/smlife-core';
}

function smlife_gh_token() {
	return defined( 'SMLIFE_CORE_GH_TOKEN' ) ? trim( (string) SMLIFE_CORE_GH_TOKEN ) : '';
}

function smlife_gh_headers( $accept = 'application/vnd.github+json' ) {
	$h = array(
		'Accept'               => $accept,
		'User-Agent'           => 'SMLIFE-Core/' . SMLIFE_CORE_VER . '; ' . home_url( '/' ),
		'X-GitHub-Api-Version' => '2022-11-28',
	);
	if ( '' !== smlife_gh_token() ) {
		$h['Authorization'] = 'Bearer ' . smlife_gh_token();
	}
	return $h;
}

function smlife_gh_nhanh() {
	$b = defined( 'SMLIFE_CORE_GH_BRANCH' ) ? (string) SMLIFE_CORE_GH_BRANCH : 'main';
	return preg_match( '#^[A-Za-z0-9_./-]+$#', $b ) ? $b : 'main';
}

/* Ban moi nhat tren nhanh main: doc dong "Version:" cua smlife-core.php tai commit
 * moi nhat, goi tai la zipball cua dung commit do (khong dung Release/tag).
 * Nho 6 gio (loi thi 1 gio). Tra ve mang hoac null. $moi = true: hoi lai ngay. */
function smlife_gh_release( $moi = false ) {
	$k = 'smlife_gh_rel';
	if ( ! $moi ) {
		$c = get_site_transient( $k );
		if ( is_array( $c ) ) {
			return empty( $c['ver'] ) ? null : $c;
		}
	}
	$api = 'https://api.github.com/repos/' . smlife_gh_repo();
	$out = array( 'ver' => '', 'loi' => '', 't' => time() );
	$res = wp_remote_get( $api . '/commits/' . rawurlencode( smlife_gh_nhanh() ), array( 'timeout' => 10, 'headers' => smlife_gh_headers() ) );
	$code = is_wp_error( $res ) ? 0 : (int) wp_remote_retrieve_response_code( $res );
	$j    = 200 === $code ? json_decode( (string) wp_remote_retrieve_body( $res ), true ) : null;
	$sha  = ( is_array( $j ) && ! empty( $j['sha'] ) && preg_match( '/^[0-9a-f]{40}$/', $j['sha'] ) ) ? $j['sha'] : '';
	if ( '' === $sha ) {
		$out['loi'] = is_wp_error( $res ) ? $res->get_error_message() : 'HTTP ' . $code;
	} else {
		$r2   = wp_remote_get( $api . '/contents/smlife-core.php?ref=' . $sha, array( 'timeout' => 10, 'headers' => smlife_gh_headers( 'application/vnd.github.raw' ) ) );
		$noi  = is_wp_error( $r2 ) ? '' : (string) wp_remote_retrieve_body( $r2 );
		if ( preg_match( '/^[ \t\/*#@]*Version:\s*([0-9]+(?:\.[0-9]+){1,3})\s*$/mi', $noi, $m ) ) {
			$out = array(
				'ver'   => $m[1],
				'sha'   => $sha,
				'zip'   => $api . '/zipball/' . $sha,
				'url'   => 'https://github.com/' . smlife_gh_repo() . '/commits/' . smlife_gh_nhanh(),
				'notes' => isset( $j['commit']['message'] ) ? (string) $j['commit']['message'] : '',
				'ngay'  => isset( $j['commit']['committer']['date'] ) ? (string) $j['commit']['committer']['date'] : '',
				'loi'   => '',
				't'     => time(),
			);
		} else {
			$out['loi'] = 'Không đọc được dòng Version trong smlife-core.php';
		}
	}
	set_site_transient( $k, $out, $out['ver'] ? 6 * HOUR_IN_SECONDS : HOUR_IN_SECONDS );
	return $out['ver'] ? $out : null;
}

/* Nhat ky thay doi (CHANGELOG.md) cho cua so Xem chi tiet, chi tai khi mo cua so */
function smlife_gh_changelog( $sha ) {
	$k = 'smlife_gh_log_' . substr( (string) $sha, 0, 12 );
	$c = get_site_transient( $k );
	if ( is_string( $c ) ) {
		return $c;
	}
	$r = wp_remote_get( 'https://api.github.com/repos/' . smlife_gh_repo() . '/contents/CHANGELOG.md?ref=' . rawurlencode( (string) $sha ), array( 'timeout' => 10, 'headers' => smlife_gh_headers( 'application/vnd.github.raw' ) ) );
	$t = ( ! is_wp_error( $r ) && 200 === (int) wp_remote_retrieve_response_code( $r ) ) ? (string) wp_remote_retrieve_body( $r ) : '';
	set_site_transient( $k, $t, DAY_IN_SECONDS );
	return $t;
}

/* 1. Bao WordPress co ban moi */
add_filter( 'pre_set_site_transient_update_plugins', function ( $t ) {
	if ( ! is_object( $t ) ) {
		return $t;
	}
	$base = plugin_basename( SMLIFE_CORE_FILE );
	$r    = smlife_gh_release();
	$item = (object) array(
		'id'           => 'github.com/' . smlife_gh_repo(),
		'slug'         => 'smlife-core',
		'plugin'       => $base,
		'new_version'  => $r ? $r['ver'] : SMLIFE_CORE_VER,
		'url'          => $r ? $r['url'] : '',
		'package'      => $r ? $r['zip'] : '',
		'requires_php' => '7.4',
		'icons'        => array(),
		'banners'      => array(),
	);
	if ( $r && version_compare( $r['ver'], SMLIFE_CORE_VER, '>' ) ) {
		$t->response[ $base ] = $item;
		unset( $t->no_update[ $base ] );
	} else {
		/* Co muc no_update thi WordPress moi hien nut Bat tu dong cap nhat */
		$t->no_update[ $base ] = $item;
		unset( $t->response[ $base ] );
	}
	return $t;
} );

/* 2. Tai file: GitHub tra ve chuyen huong sang codeload.github.com. Tu lam 2 buoc:
 * hoi GitHub kem ma (khong theo chuyen huong) lay dia chi that, roi tai dia chi do
 * KHONG kem ma. Gui ma sang may chu khac vua sai vua lo ma. */
add_filter( 'upgrader_pre_download', function ( $reply, $package ) {
	if ( false !== $reply || ! is_string( $package ) || 0 !== strpos( $package, 'https://api.github.com/repos/' . smlife_gh_repo() . '/zipball/' ) ) {
		return $reply;
	}
	$res = wp_remote_get( $package, array(
		'timeout'     => 30,
		'redirection' => 0,
		'headers'     => smlife_gh_headers( 'application/octet-stream' ),
	) );
	if ( is_wp_error( $res ) ) {
		return $res;
	}
	$code = (int) wp_remote_retrieve_response_code( $res );
	if ( in_array( $code, array( 301, 302, 303, 307, 308 ), true ) ) {
		$that = (string) wp_remote_retrieve_header( $res, 'location' );
		if ( 0 !== strpos( $that, 'https://' ) ) {
			return new WP_Error( 'smlife_gh', 'GitHub trả về địa chỉ tải không hợp lệ.' );
		}
		if ( ! function_exists( 'download_url' ) ) {
			require_once ABSPATH . 'wp-admin/includes/file.php';
		}
		return download_url( $that, 300 );
	}
	if ( 200 === $code ) {
		/* Mot so truong hop GitHub tra thang noi dung file */
		if ( ! function_exists( 'wp_tempnam' ) ) {
			require_once ABSPATH . 'wp-admin/includes/file.php';
		}
		$tmp = wp_tempnam( 'smlife-core.zip' );
		if ( false === file_put_contents( $tmp, wp_remote_retrieve_body( $res ) ) ) { // phpcs:ignore
			return new WP_Error( 'smlife_gh', 'Không ghi được file tạm.' );
		}
		return $tmp;
	}
	return new WP_Error( 'smlife_gh', 'Không tải được bản cập nhật từ GitHub (HTTP ' . $code . '). Kiểm tra SMLIFE_CORE_GH_TOKEN trong wp-config.php.' );
}, 10, 2 );

/* 3. Cua so "Xem chi tiet phien ban" lay noi dung CHANGELOG.md */
add_filter( 'plugins_api', function ( $res, $action, $args ) {
	if ( 'plugin_information' !== $action || empty( $args->slug ) || 'smlife-core' !== $args->slug ) {
		return $res;
	}
	$r = smlife_gh_release();
	if ( ! $r ) {
		return $res;
	}
	$log   = smlife_gh_changelog( $r['sha'] );
	$notes = esc_html( '' !== trim( $log ) ? $log : $r['notes'] );
	$notes = preg_replace( '/^### (.*)$/m', '<h4>$1</h4>', $notes );
	$notes = preg_replace( '/^## (.*)$/m', '<h3>$1</h3>', $notes );
	$notes = preg_replace( '/^[-*] (.*)$/m', '<li>$1</li>', $notes );
	return (object) array(
		'name'          => 'SMLIFE Core',
		'slug'          => 'smlife-core',
		'version'       => $r['ver'],
		'author'        => 'Cao Tuấn Hiệp',
		'homepage'      => $r['url'],
		'requires_php'  => '7.4',
		'last_updated'  => $r['ngay'],
		'download_link' => $r['zip'],
		'sections'      => array( 'changelog' => nl2br( $notes ) ),
	);
}, 10, 3 );

/* 3b. Zipball cua GitHub giai nen ra thu muc ten "caotuanhiep-smlife-core-<sha>".
 * Doi lai thanh "smlife-core" de WordPress ghi de dung cho, khong sinh plugin thu hai. */
add_filter( 'upgrader_source_selection', function ( $source, $remote_source, $upgrader, $extra = array() ) {
	global $wp_filesystem;
	$la_minh = isset( $extra['plugin'] ) && plugin_basename( SMLIFE_CORE_FILE ) === $extra['plugin'];
	$ten     = basename( untrailingslashit( (string) $source ) );
	$mau     = str_replace( '/', '-', smlife_gh_repo() ) . '-';
	if ( ! $la_minh && 0 !== stripos( $ten, $mau ) ) {
		return $source;
	}
	if ( 'smlife-core' === $ten || ! is_object( $wp_filesystem ) ) {
		return $source;
	}
	$dich = trailingslashit( $remote_source ) . 'smlife-core/';
	if ( $wp_filesystem->exists( $dich ) ) {
		$wp_filesystem->delete( $dich, true );
	}
	if ( ! $wp_filesystem->move( untrailingslashit( $source ), untrailingslashit( $dich ) ) ) {
		return new WP_Error( 'smlife_gh', 'Không đổi được tên thư mục bản cập nhật thành smlife-core.' );
	}
	return $dich;
}, 10, 4 );

/* 4. Sau khi cap nhat xong: xoa bo nho de lan kiem tra sau dung */
add_action( 'upgrader_process_complete', function ( $up, $opt ) {
	if ( isset( $opt['type'], $opt['plugins'] ) && 'plugin' === $opt['type'] && in_array( plugin_basename( SMLIFE_CORE_FILE ), (array) $opt['plugins'], true ) ) {
		delete_site_transient( 'smlife_gh_rel' );
	}
}, 10, 2 );

/* 5. Nut "Kiem tra cap nhat ngay" o Bang dieu khien */
add_action( 'admin_post_smlife_gh_kiem', function () {
	if ( ! current_user_can( 'update_plugins' ) ) {
		wp_die( 'Không đủ quyền.' );
	}
	check_admin_referer( 'smlife_gh_kiem' );
	smlife_gh_release( true );
	delete_site_transient( 'update_plugins' );
	wp_update_plugins();
	wp_safe_redirect( admin_url( 'admin.php?page=smlife-core&gh=1' ) );
	exit;
} );

/* Dong trang thai cap nhat, in o Bang dieu khien */
function smlife_gh_trang_thai() {
	if ( ! current_user_can( 'update_plugins' ) ) {
		return '';
	}
	$c   = get_site_transient( 'smlife_gh_rel' );
	$r   = is_array( $c ) && ! empty( $c['ver'] ) ? $c : null;
	$url = wp_nonce_url( admin_url( 'admin-post.php?action=smlife_gh_kiem' ), 'smlife_gh_kiem' );
	$h   = '<div class="notice notice-info inline" style="margin:12px 0"><p>';
	if ( $r && version_compare( $r['ver'], SMLIFE_CORE_VER, '>' ) ) {
		$h .= '🆕 Có bản <b>' . esc_html( $r['ver'] ) . '</b> trên GitHub. <a href="' . esc_url( admin_url( 'plugins.php?plugin_status=upgrade' ) ) . '">Vào trang Plugin để cập nhật</a>.';
	} elseif ( $r ) {
		$h .= '✅ Đang dùng bản mới nhất (' . esc_html( SMLIFE_CORE_VER ) . '). Kiểm tra lần cuối ' . esc_html( wp_date( 'H:i d/m', (int) $c['t'] ) ) . '.';
	} elseif ( is_array( $c ) ) {
		$h .= '⚠ Chưa kết nối được kho GitHub <code>' . esc_html( smlife_gh_repo() ) . '</code> (' . esc_html( (string) $c['loi'] ) . ').' . ( '' === smlife_gh_token() ? ' Kho riêng tư cần thêm <code>SMLIFE_CORE_GH_TOKEN</code> vào wp-config.php.' : '' );
	} else {
		$h .= 'Chưa kiểm tra cập nhật từ GitHub.';
	}
	$h .= ' <a class="button button-small" href="' . esc_url( $url ) . '">Kiểm tra cập nhật ngay</a></p></div>';
	return $h;
}
