<?php
/**
 * SMLIFE Core - Module: Chan binh luan spam
 *
 * Bot hay vao bai viet blog gan link qua khung binh luan va pingback.
 * Module nay:
 * - Dong binh luan + ping cua moi loai bai TRU san pham (danh gia san pham
 *   cua WooCommerce cung la binh luan, phai giu).
 * - Chan bot gui thang vao wp-comments-post.php / wp-trackback.php.
 * - An binh luan cu tren blog, bo dem so binh luan.
 * - Tat pingback qua XML-RPC.
 * - Danh gia san pham: chan noi dung chua link, bay an (honeypot),
 *   gioi han so lan gui moi gio, tuy chon bat buoc gui tu form that.
 * Danh gia tao trong quan tri (nhap CSV, tra loi cua shop) khong di qua
 * wp-comments-post nen khong bi anh huong.
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

function smlife_cg_opt() {
	$def = array(
		'tat_blog'  => 1,
		'an_cu'     => 1,
		'chan_link' => 1,
		'bay'       => 1,
		'ky'        => 0,
		'gioi_han'  => 1,
	);
	$s = get_option( 'smlife_cg', array() );
	return is_array( $s ) ? array_merge( $def, $s ) : $def;
}

/* Loai bai duoc giu binh luan: chi san pham */
function smlife_cg_giu( $post_id ) {
	return 'product' === get_post_type( $post_id );
}

/* ===== 1. Dong binh luan + ping ngoai san pham ===== */
add_filter( 'comments_open', function ( $open, $post_id ) {
	$o = smlife_cg_opt();
	if ( ! empty( $o['tat_blog'] ) && $post_id && ! smlife_cg_giu( $post_id ) ) {
		return false;
	}
	return $open;
}, 99, 2 );

add_filter( 'pings_open', function ( $open, $post_id ) {
	$o = smlife_cg_opt();
	return ! empty( $o['tat_blog'] ) ? false : $open;
}, 99, 2 );

/* An binh luan cu va so dem tren blog (chi ngoai trang quan tri) */
add_filter( 'comments_array', function ( $comments, $post_id ) {
	$o = smlife_cg_opt();
	if ( ! is_admin() && ! empty( $o['tat_blog'] ) && ! empty( $o['an_cu'] ) && ! smlife_cg_giu( $post_id ) ) {
		return array();
	}
	return $comments;
}, 99, 2 );

add_filter( 'get_comments_number', function ( $n, $post_id ) {
	$o = smlife_cg_opt();
	if ( ! is_admin() && ! empty( $o['tat_blog'] ) && ! empty( $o['an_cu'] ) && $post_id && ! smlife_cg_giu( $post_id ) ) {
		return 0;
	}
	return $n;
}, 99, 2 );

add_filter( 'feed_links_show_comments_feed', function ( $show ) {
	$o = smlife_cg_opt();
	return ! empty( $o['tat_blog'] ) ? false : $show;
} );

/* ===== 2. Tat pingback qua XML-RPC va header X-Pingback ===== */
add_filter( 'xmlrpc_methods', function ( $m ) {
	$o = smlife_cg_opt();
	if ( ! empty( $o['tat_blog'] ) ) {
		unset( $m['pingback.ping'], $m['pingback.extensions.getPingbacks'] );
	}
	return $m;
} );
add_filter( 'wp_headers', function ( $h ) {
	$o = smlife_cg_opt();
	if ( ! empty( $o['tat_blog'] ) ) {
		unset( $h['X-Pingback'] );
	}
	return $h;
} );

/* ===== 3. O nhap URL trong form binh luan: bo di (bot hay dien link vao day) ===== */
add_filter( 'comment_form_default_fields', function ( $f ) {
	unset( $f['url'] );
	return $f;
}, 99 );

/* ===== 4. Truong an trong form danh gia ===== */
function smlife_cg_ky( $ts ) {
	return substr( hash_hmac( 'sha256', 'cg|' . (int) $ts, wp_salt( 'nonce' ) ), 0, 16 );
}
function smlife_cg_in_truong() {
	$o = smlife_cg_opt();
	if ( empty( $o['bay'] ) && empty( $o['ky'] ) ) {
		return;
	}
	$ts = time();
	echo '<p style="position:absolute!important;left:-9999px!important;top:-9999px!important;height:0;overflow:hidden" aria-hidden="true">';
	echo '<label>Để trống ô này<input type="text" name="smlife_cg_web" value="" tabindex="-1" autocomplete="off" /></label>';
	echo '<input type="hidden" name="smlife_cg_t" value="' . (int) $ts . '" />';
	echo '<input type="hidden" name="smlife_cg_k" value="' . esc_attr( smlife_cg_ky( $ts ) ) . '" />';
	echo '</p>';
}
add_action( 'comment_form_after_fields', 'smlife_cg_in_truong' );
add_action( 'comment_form_logged_in_after', 'smlife_cg_in_truong' );

/* Noi dung co link ngoai khong (link toi chinh website thi bo qua) */
function smlife_cg_co_link( $s ) {
	$s    = strtolower( (string) $s );
	$host = strtolower( (string) wp_parse_url( home_url(), PHP_URL_HOST ) );
	$host = preg_replace( '/^www\./', '', $host );
	if ( '' !== $host ) {
		$s = str_replace( $host, '', $s );
	}
	if ( preg_match( '#https?://|www\.|<a\s|\[url#i', $s ) ) {
		return true;
	}
	return (bool) preg_match( '/\b[a-z0-9-]{2,}\.(com|net|org|info|biz|xyz|top|ru|cn|io|co|online|site|shop|club|live|link|click|vip|pro|me)\b/i', $s );
}

/* ===== 5. Kiem tra moi lan gui qua wp-comments-post.php =====
 * pre_comment_on_post chi chay voi binh luan gui tu ngoai vao, khong chay
 * voi binh luan / danh gia tao trong quan tri. */
function smlife_cg_chan( $ly_do ) {
	wp_die( esc_html( $ly_do ), 'Không gửi được', array( 'response' => 403, 'back_link' => true ) );
}

add_action( 'pre_comment_on_post', function ( $post_id ) {
	$o = smlife_cg_opt();
	/* Nguoi co quyen duyet binh luan (quan tri, bien tap) khong bi chan */
	if ( current_user_can( 'moderate_comments' ) ) {
		return;
	}
	if ( ! smlife_cg_giu( $post_id ) ) {
		if ( ! empty( $o['tat_blog'] ) ) {
			smlife_cg_chan( 'Bình luận đã tắt.' );
		}
		return;
	}
	/* Tu day: danh gia san pham */
	if ( ! empty( $o['gioi_han'] ) && function_exists( 'smlife_gioi_han' ) ) {
		$ip = function_exists( 'smlife_ip' ) ? smlife_ip() : ( isset( $_SERVER['REMOTE_ADDR'] ) ? (string) $_SERVER['REMOTE_ADDR'] : '' ); // phpcs:ignore
		if ( ! smlife_gioi_han( 'cg', $ip, 5, HOUR_IN_SECONDS ) ) {
			smlife_cg_chan( 'Bạn đã gửi khá nhiều đánh giá. Vui lòng thử lại sau.' );
		}
	}
	if ( ! empty( $o['bay'] ) && ! empty( $_POST['smlife_cg_web'] ) ) { // phpcs:ignore
		smlife_cg_chan( 'Không gửi được đánh giá.' );
	}
	if ( ! empty( $o['ky'] ) ) {
		$ts = isset( $_POST['smlife_cg_t'] ) ? (int) $_POST['smlife_cg_t'] : 0; // phpcs:ignore
		$k  = isset( $_POST['smlife_cg_k'] ) ? (string) wp_unslash( $_POST['smlife_cg_k'] ) : ''; // phpcs:ignore
		if ( $ts < 1 || '' === $k || ! hash_equals( smlife_cg_ky( $ts ), $k ) ) {
			smlife_cg_chan( 'Vui lòng tải lại trang rồi gửi đánh giá.' );
		}
		/* Gui qua nhanh sau khi mo trang: may moc */
		if ( time() - $ts < 3 ) {
			smlife_cg_chan( 'Bạn gửi hơi nhanh, vui lòng thử lại.' );
		}
	}
	if ( ! empty( $o['chan_link'] ) ) {
		$nd  = isset( $_POST['comment'] ) ? (string) wp_unslash( $_POST['comment'] ) : ''; // phpcs:ignore
		$ten = isset( $_POST['author'] ) ? (string) wp_unslash( $_POST['author'] ) : ''; // phpcs:ignore
		$url = isset( $_POST['url'] ) ? trim( (string) wp_unslash( $_POST['url'] ) ) : ''; // phpcs:ignore
		if ( '' !== $url || smlife_cg_co_link( $nd ) || smlife_cg_co_link( $ten ) ) {
			smlife_cg_chan( 'Đánh giá không được chứa đường link.' );
		}
	}
}, 1 );

/* ===== Trang cai dat ===== */
add_action( 'admin_menu', function () {
	add_submenu_page( 'smlife-core', 'Chặn bình luận spam', 'Chặn bình luận spam', 'manage_options', 'smlife-chan-binh-luan', 'smlife_cg_page' );
}, 12 );

/* Dem binh luan cho duyet / spam dang nam tren bai khong phai san pham */
function smlife_cg_dem( $trang_thai ) {
	global $wpdb;
	return (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$wpdb->comments} c JOIN {$wpdb->posts} p ON p.ID = c.comment_post_ID WHERE c.comment_approved = %s AND p.post_type <> 'product'", $trang_thai ) ); // phpcs:ignore
}

function smlife_cg_page() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}
	$o = smlife_cg_opt();
	echo '<div class="wrap smlife-wrap"><div class="smc-hd"><h1>Chặn bình luận spam</h1>';
	echo '<p>Tắt hẳn bình luận và pingback trên bài viết blog, chặn bot gửi thẳng vào máy chủ. Đánh giá sản phẩm WooCommerce vẫn mở nhưng được lọc link và bẫy bot.</p></div>';
	if ( isset( $_GET['updated'] ) ) { // phpcs:ignore
		echo '<div class="notice notice-success is-dismissible"><p>Đã lưu.</p></div>';
	}
	$cho  = smlife_cg_dem( '0' );
	$spam = smlife_cg_dem( 'spam' );
	if ( $cho || $spam ) {
		echo '<div class="notice notice-info"><p>Đang có <strong>' . (int) $cho . '</strong> bình luận chờ duyệt và <strong>' . (int) $spam . '</strong> spam trên bài viết blog. Vào <a href="' . esc_url( admin_url( 'edit-comments.php?comment_status=moderated' ) ) . '">Bình luận</a> để xoá hàng loạt. Module đã chặn nên sẽ không phát sinh thêm.</p></div>';
	}
	$hang = function ( $k, $ten, $mo_ta ) use ( $o ) {
		echo '<tr><th scope="row">' . esc_html( $ten ) . '</th><td><label><input type="checkbox" name="cg[' . esc_attr( $k ) . ']" value="1" ' . checked( 1, $o[ $k ], false ) . ' /> Bật</label><p class="description">' . esc_html( $mo_ta ) . '</p></td></tr>';
	};
	echo '<div class="smlife-card"><form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '">';
	echo '<input type="hidden" name="action" value="smlife_cg_save" />';
	wp_nonce_field( 'smlife_cg_save', 'smlife_cg_nonce' );
	echo '<h2>Bài viết blog và trang</h2><table class="form-table" role="presentation">';
	$hang( 'tat_blog', 'Tắt bình luận', 'Đóng bình luận, pingback, trackback trên mọi bài viết và trang, kể cả bài cũ. Bot gửi thẳng vào máy chủ cũng bị từ chối. Không đụng tới đánh giá sản phẩm.' );
	$hang( 'an_cu', 'Ẩn bình luận cũ', 'Không hiện các bình luận đã có trên blog. Dữ liệu vẫn còn trong mục Bình luận.' );
	echo '</table><h2>Đánh giá sản phẩm</h2><table class="form-table" role="presentation">';
	$hang( 'chan_link', 'Chặn đánh giá có link', 'Từ chối đánh giá chứa đường link hoặc tên miền lạ. Link tới chính website vẫn được.' );
	$hang( 'bay', 'Bẫy bot ẩn', 'Thêm một ô ẩn khách không thấy. Bot tự điền vào sẽ bị chặn.' );
	$hang( 'gioi_han', 'Giới hạn số lần gửi', 'Mỗi địa chỉ IP gửi tối đa 5 đánh giá mỗi giờ.' );
	$hang( 'ky', 'Bắt buộc gửi từ form', 'Chặn bot gửi thẳng không qua trang sản phẩm. Bật xong hãy thử gửi một đánh giá thật trên trang sản phẩm để chắc giao diện có in ô ẩn.' );
	echo '</table>';
	submit_button( 'Lưu' );
	echo '</form></div></div>';
}

add_action( 'admin_post_smlife_cg_save', function () {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( 'Không đủ quyền.' );
	}
	check_admin_referer( 'smlife_cg_save', 'smlife_cg_nonce' );
	$in  = isset( $_POST['cg'] ) && is_array( $_POST['cg'] ) ? wp_unslash( $_POST['cg'] ) : array(); // phpcs:ignore
	$out = array();
	foreach ( array( 'tat_blog', 'an_cu', 'chan_link', 'bay', 'ky', 'gioi_han' ) as $k ) {
		$out[ $k ] = empty( $in[ $k ] ) ? 0 : 1;
	}
	update_option( 'smlife_cg', $out );
	if ( function_exists( 'smlife_xoa_cache' ) ) {
		smlife_xoa_cache( 'Chặn bình luận spam' );
	}
	wp_safe_redirect( admin_url( 'admin.php?page=smlife-chan-binh-luan&updated=1' ) );
	exit;
} );
