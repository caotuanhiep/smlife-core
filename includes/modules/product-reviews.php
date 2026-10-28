<?php
/**
 * SMLIFE Core - Module: Danh gia san pham
 *
 * Thay cho plugin "wordpress-review-mh". Plugin do luu danh gia o BANG RIENG
 * cua no, dong thoi day mot ban sao sang bang comment chuan cua WordPress.
 * Hau qua: noi dung va so sao nam ca hai noi, nhung ANH, LUOT THICH, SO DIEN
 * THOAI va NHAN DA MUA HANG thi chi nam trong bang rieng. Go plugin ra la mat.
 *
 * Module nay lam hai viec:
 *
 *   1. DI CHUYEN: doc bang rieng cua plugin cu, chep phan con thieu sang
 *      comment meta chuan cua WordPress. Chay theo tung lo nho nen khong bao
 *      gio treo, co trang doi chieu so lieu truoc va sau.
 *   2. HIEN THI: dung lai khoi danh gia kieu Shopee, doc thang tu comment
 *      chuan cua WooCommerce. Khong goi dich vu ngoai, khong muon icon cua CDN
 *      nao, khong phu thuoc plugin cu nua.
 *
 * Dat ra trang bang shortcode [smlife_dg_sp], thay cho [WP_REVIEWS_MH].
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/* Khoa comment meta module tu quan ly */
function smlife_dgsp_k_anh() { return 'smlife_dg_anh'; }
function smlife_dgsp_k_dt()  { return 'smlife_dg_dt'; }
function smlife_dgsp_k_mua() { return 'smlife_dg_mua'; }

/* ===== 1. Cai dat ===== */

function smlife_dgsp_defaults() {
	return array(
		'enabled'  => 1,
		'so_dau'   => 5,
		'so_toi_da'=> 20,
		'loc'      => 1,
		'anh_dai'  => 1,
		'nhan_mua' => 'Đã mua hàng tại SMLIFE',
		'ten_shop' => 'Chủ Shop',
		'tra_loi'  => 1,
		'db_hien'  => 1,
		'db_nhan'  => 'Đã bán',
		'db_khoa'  => '',
		'db_the'   => 1,
		'db_vitri' => 'ten',
	);
}

function smlife_dgsp_opt() {
	$o = get_option( 'smlife_dgsp', array() );
	return is_array( $o ) ? array_merge( smlife_dgsp_defaults(), $o ) : smlife_dgsp_defaults();
}

/* Nhan theo so sao, dung kieu san thuong mai dien tu */
function smlife_dgsp_nhan( $sao ) {
	$ds = array( 5 => 'Cực kì hài lòng', 4 => 'Hài lòng', 3 => 'Bình thường', 2 => 'Không hài lòng', 1 => 'Rất không hài lòng' );
	$sao = (int) $sao;
	return isset( $ds[ $sao ] ) ? $ds[ $sao ] : '';
}

/* Mau avatar suy ra tu ten, cung mot ten thi luon cung mot mau */
function smlife_dgsp_mau_ten( $ten ) {
	$ds = array( '#e8734a', '#4a90d9', '#5aa469', '#b06ab3', '#d9534f', '#3f9fa5', '#c9a227', '#7a79c9' );
	$n  = 0;
	$t  = (string) $ten;
	for ( $i = 0; $i < strlen( $t ); $i++ ) {
		$n += ord( $t[ $i ] );
	}
	return $ds[ $n % count( $ds ) ];
}

/* Hai chu cai dau cua ten */
function smlife_dgsp_chu_dau( $ten ) {
	$ten = trim( (string) $ten );
	if ( '' === $ten ) {
		return '?';
	}
	$phan = preg_split( '/\s+/', $ten );
	if ( count( $phan ) >= 2 ) {
		$a = function_exists( 'mb_substr' ) ? mb_substr( $phan[0], 0, 1, 'UTF-8' ) : substr( $phan[0], 0, 1 );
		$b = function_exists( 'mb_substr' ) ? mb_substr( $phan[ count( $phan ) - 1 ], 0, 1, 'UTF-8' ) : substr( $phan[ count( $phan ) - 1 ], 0, 1 );
		return smlife_dgsp_hoa( $a . $b );
	}
	$a = function_exists( 'mb_substr' ) ? mb_substr( $ten, 0, 1, 'UTF-8' ) : substr( $ten, 0, 1 );
	return smlife_dgsp_hoa( $a );
}

function smlife_dgsp_hoa( $s ) {
	return function_exists( 'mb_strtoupper' ) ? mb_strtoupper( $s, 'UTF-8' ) : strtoupper( $s );
}

/* Che bot so dien thoai: 0981281234 -> 0981281xxx */
function smlife_dgsp_che_dt( $dt ) {
	$dt = preg_replace( '/[^0-9]/', '', (string) $dt );
	if ( strlen( $dt ) < 7 ) {
		return '';
	}
	return substr( $dt, 0, strlen( $dt ) - 3 ) . 'xxx';
}

/* ===== 2. Doc danh gia tu comment chuan ===== */

/* Mot danh gia da chuan hoa */
function smlife_dgsp_doc( $c ) {
	$sao = (int) get_comment_meta( $c->comment_ID, 'rating', true );
	$anh = get_comment_meta( $c->comment_ID, smlife_dgsp_k_anh(), true );
	if ( ! is_array( $anh ) ) {
		$anh = array();
	}
	$dt = (string) get_comment_meta( $c->comment_ID, smlife_dgsp_k_dt(), true );
	if ( '' === $dt ) {
		$dt = (string) get_comment_meta( $c->comment_ID, 'smlife_dg_phone', true );
	}
	$mua = get_comment_meta( $c->comment_ID, smlife_dgsp_k_mua(), true );
	if ( '' === $mua ) {
		$mua = get_comment_meta( $c->comment_ID, 'verified', true );
	}
	return array(
		'id'   => (int) $c->comment_ID,
		'ten'  => $c->comment_author,
		'noi'  => $c->comment_content,
		'ngay' => $c->comment_date_gmt,
		'sao'  => max( 0, min( 5, $sao ) ),
		'anh'  => array_values( array_filter( array_map( 'esc_url_raw', $anh ) ) ),
		'dt'   => smlife_dgsp_che_dt( $dt ),
		'mua'  => ! empty( $mua ) ? 1 : 0,
		'tl'   => array(),
	);
}

/* Toan bo danh gia cua mot san pham, kem tra loi cua shop */
function smlife_dgsp_lay( $pid, $gioi_han = 0 ) {
	$args = array(
		'post_id' => (int) $pid,
		'status'  => 'approve',
		'type'    => 'review',
		'parent'  => 0,
		'orderby' => 'comment_date_gmt',
		'order'   => 'DESC',
	);
	if ( $gioi_han > 0 ) {
		$args['number'] = (int) $gioi_han;
	}
	$ds = get_comments( $args );
	if ( ! $ds ) {
		/* Mot so cau hinh luu danh gia duoi dang comment thuong */
		$args['type'] = 'comment';
		$ds           = get_comments( $args );
	}

	$out = array();
	$map = array();
	foreach ( $ds as $c ) {
		$r                  = smlife_dgsp_doc( $c );
		$map[ $r['id'] ]    = count( $out );
		$out[]              = $r;
	}
	if ( ! $out ) {
		return $out;
	}

	/* Tra loi cua shop la comment con */
	$con = get_comments( array(
		'post_id' => (int) $pid,
		'status'  => 'approve',
		'parent__in' => array_keys( $map ),
		'orderby' => 'comment_date_gmt',
		'order'   => 'ASC',
	) );
	foreach ( $con as $c ) {
		$p = (int) $c->comment_parent;
		if ( ! isset( $map[ $p ] ) ) {
			continue;
		}
		$out[ $map[ $p ] ]['tl'][] = array(
			'ten'  => $c->comment_author,
			'noi'  => $c->comment_content,
			'ngay' => $c->comment_date_gmt,
		);
	}
	return $out;
}

/* Tong ket tinh tren TOAN BO danh gia cua san pham, khong phu thuoc so
   danh gia da tai ra HTML. Mot truy van, bam chi muc comment_post_ID. */
function smlife_dgsp_tk_sp( $pid ) {
	static $nho = array();
	$pid = (int) $pid;
	if ( isset( $nho[ $pid ] ) ) {
		return $nho[ $pid ];
	}
	global $wpdb;
	$rows = $wpdb->get_results( $wpdb->prepare( // phpcs:ignore
		"SELECT cm.meta_value AS sao, COUNT(*) AS n
		 FROM {$wpdb->comments} c
		 INNER JOIN {$wpdb->commentmeta} cm ON cm.comment_id = c.comment_ID AND cm.meta_key = 'rating'
		 WHERE c.comment_post_ID = %d AND c.comment_approved = '1' AND c.comment_parent = 0
		 GROUP BY cm.meta_value",
		$pid
	) );
	$muc  = array( 5 => 0, 4 => 0, 3 => 0, 2 => 0, 1 => 0 );
	$tong = 0;
	$diem = 0;
	foreach ( (array) $rows as $r ) {
		$sao = (int) $r->sao;
		if ( $sao < 1 || $sao > 5 ) {
			continue;
		}
		$muc[ $sao ] += (int) $r->n;
		$tong        += (int) $r->n;
		$diem        += $sao * (int) $r->n;
	}
	$nho[ $pid ] = array(
		'tong' => $tong,
		'tb'   => $tong > 0 ? round( $diem / $tong, 1 ) : 0,
		'muc'  => $muc,
	);
	return $nho[ $pid ];
}

/* Tong ket: diem trung binh, tong so, so luong tung muc sao */
function smlife_dgsp_tom_tat( $ds ) {
	$muc = array( 5 => 0, 4 => 0, 3 => 0, 2 => 0, 1 => 0 );
	$tong = 0;
	$diem = 0;
	foreach ( $ds as $r ) {
		if ( $r['sao'] < 1 ) {
			continue;
		}
		$muc[ $r['sao'] ]++;
		$diem += $r['sao'];
		$tong++;
	}
	return array(
		'tong' => $tong,
		'tb'   => $tong > 0 ? round( $diem / $tong, 1 ) : 0,
		'muc'  => $muc,
	);
}

/* URL anh dat vao background-image:url(...). esc_url van cho qua dau ngoac
   va nhay don, du de pha khoi url() va chen CSS la. Ma hoa them cho chac. */
function smlife_dgsp_url_css( $u ) {
	return str_replace( array( '(', ')', "'", '"', ' ' ), array( '%28', '%29', '%27', '%22', '%20' ), esc_url( $u ) );
}

/* ===== 3. Ve giao dien ===== */

function smlife_dgsp_sao( $sao, $co = 15 ) {
	$h = '<span class="smdg-sao" style="--s:' . (int) $co . 'px">';
	for ( $i = 1; $i <= 5; $i++ ) {
		$h .= '<i class="' . ( $i <= (int) $sao ? 'on' : '' ) . '"></i>';
	}
	return $h . '</span>';
}

function smlife_dgsp_truoc( $gmt ) {
	$t = strtotime( $gmt . ' GMT' );
	if ( ! $t ) {
		return '';
	}
	return human_time_diff( $t, current_time( 'timestamp', true ) ) . ' trước';
}

add_shortcode( 'smlife_dg_sp', 'smlife_dgsp_sc' );
function smlife_dgsp_sc( $atts ) {
	if ( is_admin() && ! ( defined( 'DOING_AJAX' ) && DOING_AJAX ) ) { return ''; }
	$o = smlife_dgsp_opt();
	if ( empty( $o['enabled'] ) ) { return ''; }

	$a   = shortcode_atts( array( 'id' => 0 ), $atts, 'smlife_dg_sp' );
	$pid = (int) $a['id'] > 0 ? (int) $a['id'] : (int) get_the_ID();
	if ( ! $pid ) { return ''; }

	$ds = smlife_dgsp_lay( $pid, max( 5, (int) $o['so_toi_da'] ) );
	if ( ! $ds ) { return ''; }

	$tk  = smlife_dgsp_tk_sp( $pid );
	if ( $tk['tong'] < 1 ) { $tk = smlife_dgsp_tom_tat( $ds ); }
	$dau = max( 1, (int) $o['so_dau'] );

	smlife_dgsp_nap();

	/* Gom anh de lam dai anh that */
	$dai = array();
	foreach ( $ds as $r ) {
		foreach ( $r['anh'] as $u ) {
			$dai[] = $u;
		}
	}

	$h = '<div class="smdg" id="smdg-khoi" data-dau="' . (int) $dau . '">';

	/* --- Tong ket --- */
	$h .= '<div class="smdg-tk"><div class="smdg-tk-l">';
	$h .= '<div class="smdg-diem">' . esc_html( number_format_i18n( $tk['tb'], ( floor( $tk['tb'] ) == $tk['tb'] ) ? 1 : 1 ) ) . '</div>';
	$h .= smlife_dgsp_sao( floor( $tk['tb'] + 0.5 ), 18 );
	$h .= '<div class="smdg-tong">' . esc_html( number_format_i18n( $tk['tong'] ) ) . ' đánh giá</div>';
	$h .= '<div class="smdg-thanh">';
	for ( $i = 5; $i >= 1; $i-- ) {
		$n  = (int) $tk['muc'][ $i ];
		$pc = $tk['tong'] > 0 ? round( $n * 100 / $tk['tong'] ) : 0;
		$h .= '<div class="smdg-d">' . smlife_dgsp_sao( $i, 11 );
		$h .= '<span class="smdg-bar"><b style="width:' . (int) $pc . '%"></b></span>';
		$h .= '<em>' . esc_html( number_format_i18n( $n ) ) . '</em></div>';
	}
	$h .= '</div></div>';

	if ( ! empty( $o['anh_dai'] ) && $dai ) {
		$h .= '<div class="smdg-tk-r"><div class="smdg-dai-t">Hình ảnh đánh giá</div><div class="smdg-dai">';
		$i = 0;
		foreach ( $dai as $u ) {
			$h .= '<span class="smdg-o' . ( $i >= 6 ? ' du' : '' ) . '" data-xem="' . esc_url( $u ) . '" style="background-image:url(' . smlife_dgsp_url_css( $u ) . ')"></span>';
			$i++;
		}
		if ( count( $dai ) > 6 ) {
			$h .= '<span class="smdg-o smdg-them" data-mo="6">+' . esc_html( number_format_i18n( count( $dai ) - 6 ) ) . '</span>';
		}
		$h .= '</div></div>';
	}
	$h .= '</div>';

	/* --- Hang loc --- */
	if ( ! empty( $o['loc'] ) ) {
		$h .= '<div class="smdg-loc"><span class="smdg-loc-t">Lọc xem theo:</span>';
		$h .= '<button type="button" class="smdg-n" data-loc="anh">Có hình ảnh</button>';
		$h .= '<button type="button" class="smdg-n" data-loc="mua">Đã mua hàng</button>';
		for ( $i = 5; $i >= 1; $i-- ) {
			if ( empty( $tk['muc'][ $i ] ) ) { continue; }
			$h .= '<button type="button" class="smdg-n" data-loc="' . $i . '">' . $i . ' <i class="smdg-s1"></i></button>';
		}
		$h .= '</div>';
	}

	/* --- Danh sach --- */
	$h .= '<div class="smdg-ds">';
	$k = 0;
	foreach ( $ds as $r ) {
		$an  = ( $k >= $dau ) ? ' an' : '';
		$cls = 'smdg-i' . $an;
		$h  .= '<div class="' . $cls . '" data-sao="' . (int) $r['sao'] . '" data-anh="' . ( $r['anh'] ? 1 : 0 ) . '" data-mua="' . (int) $r['mua'] . '">';

		$h .= '<div class="smdg-ng"><span class="smdg-av" style="background:' . esc_attr( smlife_dgsp_mau_ten( $r['ten'] ) ) . '">' . esc_html( smlife_dgsp_chu_dau( $r['ten'] ) ) . '</span>';
		$h .= '<span class="smdg-ten">' . esc_html( $r['ten'] ) . '</span>';
		if ( '' !== $r['dt'] ) {
			$h .= '<span class="smdg-dt">' . esc_html( $r['dt'] ) . '</span>';
		}
		$h .= '</div>';

		$h .= '<div class="smdg-nd">';
		$h .= '<div class="smdg-h">' . smlife_dgsp_sao( $r['sao'] ) . '<span class="smdg-nhan">' . esc_html( smlife_dgsp_nhan( $r['sao'] ) ) . '</span></div>';
		if ( $r['mua'] && '' !== trim( (string) $o['nhan_mua'] ) ) {
			$h .= '<div class="smdg-mua">' . esc_html( $o['nhan_mua'] ) . ' <i></i></div>';
		}
		$h .= '<div class="smdg-txt">' . nl2br( esc_html( wp_strip_all_tags( $r['noi'] ) ) ) . '</div>';
		if ( $r['anh'] ) {
			$h .= '<div class="smdg-anh">';
			foreach ( $r['anh'] as $u ) {
				$h .= '<span class="smdg-o" data-xem="' . esc_url( $u ) . '" style="background-image:url(' . smlife_dgsp_url_css( $u ) . ')"></span>';
			}
			$h .= '</div>';
		}
		$h .= '<div class="smdg-ngay">Nhận xét vào ' . esc_html( smlife_dgsp_truoc( $r['ngay'] ) ) . '</div>';

		if ( ! empty( $o['tra_loi'] ) ) {
			foreach ( $r['tl'] as $t ) {
				$h .= '<div class="smdg-tl"><div class="smdg-tl-h">' . esc_html( '' !== trim( (string) $t['ten'] ) ? $t['ten'] : $o['ten_shop'] ) . ' <i></i><span>' . esc_html( smlife_dgsp_truoc( $t['ngay'] ) ) . '</span></div>';
				$h .= '<div class="smdg-tl-n">' . nl2br( esc_html( wp_strip_all_tags( $t['noi'] ) ) ) . '</div></div>';
			}
		}
		$h .= '</div></div>';
		$k++;
	}
	$h .= '</div>';

	if ( count( $ds ) > $dau ) {
		$h .= '<div class="smdg-mo"><button type="button" class="smdg-mo-n">Xem thêm đánh giá</button></div>';
	}
	$h .= '<div class="smdg-trong">Không có đánh giá nào khớp bộ lọc.</div>';
	$h .= '</div>';
	return $h;
}

/* ===== 3b. So luong da ban ===== */

function smlife_dgsp_k_db() { return '_smlife_dg_daban'; }

/* So da ban cua mot san pham. Uu tien khoa cua module, thieu thi doc khoa cu. */
function smlife_dgsp_daban( $pid ) {
	$n = get_post_meta( (int) $pid, smlife_dgsp_k_db(), true );
	if ( '' === $n || null === $n ) {
		$o = smlife_dgsp_opt();
		$k = trim( (string) $o['db_khoa'] );
		if ( '' !== $k ) {
			$n = get_post_meta( (int) $pid, $k, true );
		}
	}
	$n = (int) $n;
	return $n > 0 ? $n : 0;
}

/* Dong meta duoi ten san pham: sao, so danh gia, so da ban */
add_shortcode( 'smlife_dg_meta', 'smlife_dgsp_meta_sc' );
function smlife_dgsp_meta_sc( $atts ) {
	if ( is_admin() && ! ( defined( 'DOING_AJAX' ) && DOING_AJAX ) ) { return ''; }
	$o = smlife_dgsp_opt();
	if ( empty( $o['enabled'] ) ) { return ''; }

	$a   = shortcode_atts( array( 'id' => 0 ), $atts, 'smlife_dg_meta' );
	$pid = (int) $a['id'] > 0 ? (int) $a['id'] : (int) get_the_ID();
	if ( ! $pid ) { return ''; }

	/* Dung chung nguon voi khoi danh gia de hai cho khong bao gio lech nhau */
	$tk = smlife_dgsp_tk_sp( $pid );
	$so = (int) $tk['tong'];
	$tb = (float) $tk['tb'];
	if ( $so < 1 ) {
		$so = (int) get_post_meta( $pid, '_wc_review_count', true );
		$tb = (float) get_post_meta( $pid, '_wc_average_rating', true );
	}
	$db = ! empty( $o['db_hien'] ) ? smlife_dgsp_daban( $pid ) : 0;

	if ( $so < 1 && $db < 1 ) { return ''; }

	smlife_dgsp_nap();

	$h = '<div class="smdg-meta">';
	if ( $so > 0 ) {
		$h .= smlife_dgsp_sao( floor( $tb + 0.5 ), 15 );
		$h .= '<a href="#smdg-khoi" class="smdg-m-sl">( <strong>' . esc_html( number_format_i18n( $so ) ) . '</strong> đánh giá)</a>';
	}
	if ( $db > 0 ) {
		$h .= '<span class="smdg-m-db"><strong>' . esc_html( number_format_i18n( $db ) ) . '</strong> ' . esc_html( $o['db_nhan'] ) . '</span>';
	}
	$h .= '</div>';
	return $h;
}

/* Chi rieng con so da ban, dung khi muon dat o cho khac */
add_shortcode( 'smlife_dg_daban', function ( $atts ) {
	if ( is_admin() && ! ( defined( 'DOING_AJAX' ) && DOING_AJAX ) ) { return ''; }
	$a   = shortcode_atts( array( 'id' => 0 ), $atts, 'smlife_dg_daban' );
	$pid = (int) $a['id'] > 0 ? (int) $a['id'] : (int) get_the_ID();
	$n   = $pid ? smlife_dgsp_daban( $pid ) : 0;
	return $n > 0 ? esc_html( number_format_i18n( $n ) ) : '';
} );


/* ===== 3c. So da ban trong the san pham o trang danh muc ===== */

function smlife_dgsp_the_db() {
	if ( ! function_exists( 'wc_get_product' ) ) { return; }
	$o = smlife_dgsp_opt();
	if ( empty( $o['enabled'] ) || empty( $o['db_the'] ) || empty( $o['db_hien'] ) ) { return; }
	$pid = get_the_ID();
	if ( ! $pid ) { return; }
	$n = smlife_dgsp_daban( $pid );
	if ( $n < 1 ) { return; }
	smlife_dgsp_nap( 'the' );
	echo '<div class="smdg-l-db">' . esc_html( $o['db_nhan'] ) . ' ' . esc_html( number_format_i18n( $n ) ) . '</div>';
}

add_action( 'init', function () {
	$o = smlife_dgsp_opt();
	if ( empty( $o['enabled'] ) || empty( $o['db_the'] ) ) { return; }
	if ( 'gia_truoc' === $o['db_vitri'] ) {
		add_action( 'woocommerce_after_shop_loop_item_title', 'smlife_dgsp_the_db', 6 );
	} elseif ( 'gia_sau' === $o['db_vitri'] ) {
		add_action( 'woocommerce_after_shop_loop_item_title', 'smlife_dgsp_the_db', 20 );
	} else {
		add_action( 'woocommerce_shop_loop_item_title', 'smlife_dgsp_the_db', 20 );
	}
}, 20 );

/* ===== 3d. Tinh lai diem trung binh tu comment =====
 *
 * WooCommerce luu san diem trung binh va so danh gia vao thuoc tinh san pham.
 * Neu plugin cu tung tu tay ghi hai con so do thi khi tat no di, con so co the
 * dung yen khong cap nhat. Cong cu nay bat WooCommerce tinh lai tu comment that.
 */
function smlife_dgsp_tinh_lai( $off, $size = 100 ) {
	$ids = get_posts( array(
		'post_type'           => 'product',
		'post_status'         => array( 'publish', 'private' ),
		'fields'              => 'ids',
		'posts_per_page'      => (int) $size,
		'offset'              => (int) $off,
		'orderby'             => 'ID',
		'order'               => 'ASC',
		'ignore_sticky_posts' => true,
		'no_found_rows'       => true,
		'suppress_filters'    => true,
	) );
	$kq = array( 'doc' => 0, 'ghi' => 0 );
	foreach ( (array) $ids as $id ) {
		$kq['doc']++;
		if ( ! class_exists( 'WC_Comments' ) ) { continue; }
		$cu = (string) get_post_meta( $id, '_wc_average_rating', true );
		WC_Comments::get_average_rating_for_product( wc_get_product( $id ) );
		WC_Comments::get_review_count_for_product( wc_get_product( $id ) );
		$moi = (string) get_post_meta( $id, '_wc_average_rating', true );
		if ( $cu !== $moi ) { $kq['ghi']++; }
	}
	return $kq;
}

add_action( 'wp_ajax_smlife_dgsp_tinh', function () {
	if ( ! current_user_can( 'manage_options' ) ) { wp_send_json_error(); }
	check_ajax_referer( 'smlife_dgsp_db', '_n' );
	$off = isset( $_POST['off'] ) ? max( 0, (int) $_POST['off'] ) : 0;
	wp_send_json_success( smlife_dgsp_tinh_lai( $off, 100 ) );
} );

/* ===== 4. CSS + JS ===== */

/* Chi nap dung phan can dung.
 *
 *   'the'    - chi mot dong CSS cho so da ban trong the san pham, dung o
 *              trang danh muc. Truoc day trang danh muc phai tai ca bo CSS
 *              khoi danh gia va toan bo JS thu vien anh cho mot dong chu.
 *   'day_du' - ca bo, chi khi trang that su co khoi danh gia.
 */
function smlife_dgsp_nap( $muc = 'day_du' ) {
	static $da = array();
	if ( 'day_du' === $muc ) {
		if ( isset( $da['day_du'] ) ) { return; }
		$da['day_du'] = 1;
		add_action( 'wp_footer', 'smlife_dgsp_css', 5 );
		add_action( 'wp_footer', 'smlife_dgsp_js', 99 );
		return;
	}
	if ( isset( $da['the'] ) || isset( $da['day_du'] ) ) { return; }
	$da['the'] = 1;
	add_action( 'wp_footer', 'smlife_dgsp_css_the', 5 );
}

/* Vai chuc byte, thay vi 12 KB */
function smlife_dgsp_css_the() {
	echo '<style id="smlife-dgsp-the">.smdg-l-db{font-size:12.5px;color:#8d8d8d;line-height:1.3;margin:3px 0 0}</style>'; // phpcs:ignore
}

function smlife_dgsp_css() {
	$mau  = function_exists( 'smlife_mau' ) ? smlife_mau() : '#ee2468';
	$nhat = function_exists( 'smlife_mau_nhat' ) ? smlife_mau_nhat( 0.96 ) : '#fef2f6';
	$vang = '#f5a623';
	$c  = '.smdg{font-size:14px;color:#3a3a3a}';
	$c .= '.smdg-sao{display:inline-flex;gap:1px;vertical-align:middle;line-height:0}';
	$c .= '.smdg-sao i,.smdg-s1{display:inline-block;width:var(--s,15px);height:var(--s,15px);background:#dcdcdc;';
	$c .= '-webkit-mask:var(--m) center/contain no-repeat;mask:var(--m) center/contain no-repeat;';
	$c .= '--m:url("data:image/svg+xml;charset=utf8,%3Csvg xmlns=\'http://www.w3.org/2000/svg\' viewBox=\'0 0 24 24\'%3E%3Cpath d=\'M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z\'/%3E%3C/svg%3E")}';
	$c .= '.smdg-sao i.on{background:' . $vang . '}';
	$c .= '.smdg-s1{width:13px;height:13px;background:' . $vang . ';vertical-align:middle}';
	/* Tong ket */
	$c .= '.smdg-tk{display:flex;gap:26px;flex-wrap:wrap;border:1px solid #ececec;border-radius:12px;padding:18px 20px;background:#fff}';
	$c .= '.smdg-tk-l{flex:0 0 250px}.smdg-tk-r{flex:1 1 260px;min-width:0}';
	$c .= '.smdg-diem{font-size:34px;font-weight:700;color:' . $mau . ';line-height:1.1;display:inline-block;margin-right:9px;vertical-align:middle}';
	$c .= '.smdg-tong{font-size:13px;color:#8d8d8d;margin:5px 0 11px}';
	$c .= '.smdg-d{display:flex;align-items:center;gap:8px;margin:0 0 5px}';
	$c .= '.smdg-bar{flex:1 1 auto;height:7px;border-radius:4px;background:#f0f0f0;overflow:hidden}';
	$c .= '.smdg-bar b{display:block;height:100%;background:' . $vang . ';border-radius:4px}';
	$c .= '.smdg-d em{font-style:normal;font-size:12.5px;color:#8d8d8d;min-width:26px;text-align:right}';
	$c .= '.smdg-dai-t{font-size:13.5px;font-weight:600;color:#6a6a6a;margin:0 0 9px}';
	$c .= '.smdg-dai{display:flex;gap:8px;flex-wrap:wrap}';
	$c .= '.smdg-o{display:block;width:62px;height:62px;border-radius:8px;background:#f4f4f4 center/cover no-repeat;cursor:zoom-in;border:1px solid #efefef}';
	$c .= '.smdg-them{display:flex;align-items:center;justify-content:center;font-size:13px;font-weight:600;color:#8d8d8d;cursor:default}';
	/* Loc */
	$c .= '.smdg-loc{display:flex;gap:8px;align-items:center;flex-wrap:wrap;border:1px solid #ececec;border-radius:12px;padding:13px 16px;background:#fff;margin:14px 0 0}';
	$c .= '.smdg-loc-t{font-size:13.5px;color:#8d8d8d;margin-right:2px}';
	$c .= '.smdg-n{background:#fff;border:1px solid #e2e2e2;border-radius:6px;font-size:13px;font-weight:500;color:#4a4a4a;line-height:1;padding:8px 13px;margin:0;cursor:pointer;min-height:0;transition:all .15s}';
	$c .= '.smdg-n:hover{border-color:' . $mau . ';color:' . $mau . '}';
	$c .= '.smdg-n.on{border-color:' . $mau . ';color:' . $mau . ';background:' . $nhat . '}';
	/* Danh sach */
	$c .= '.smdg-i{display:flex;gap:16px;padding:18px 0;border-bottom:1px solid #f2f2f2}';
	$c .= '.smdg-i.an,.smdg-i.loc{display:none}';
	$c .= '.smdg-ng{flex:0 0 160px;min-width:0}';
	$c .= '.smdg-av{display:inline-flex;width:34px;height:34px;border-radius:50%;color:#fff;font-size:13px;font-weight:600;align-items:center;justify-content:center;vertical-align:middle;margin-right:8px}';
	$c .= '.smdg-ten{font-size:13.5px;font-weight:600;color:#3a3a3a;vertical-align:middle}';
	$c .= '.smdg-dt{display:block;font-size:12px;color:#a0a0a0;margin:4px 0 0 42px}';
	$c .= '.smdg-nd{flex:1 1 auto;min-width:0}';
	$c .= '.smdg-h{display:flex;align-items:center;gap:9px;margin:0 0 5px}';
	$c .= '.smdg-nhan{font-size:13.5px;font-weight:600;color:#4a4a4a}';
	$c .= '.smdg-mua{font-size:12.5px;color:#1a9c57;margin:0 0 7px;display:flex;align-items:center;gap:5px}';
	$c .= '.smdg-mua i{display:inline-block;width:13px;height:13px;border-radius:50%;background:#1a9c57;position:relative}';
	$c .= '.smdg-mua i::after{content:"";position:absolute;left:4px;top:2px;width:3px;height:6px;border-right:2px solid #fff;border-bottom:2px solid #fff;transform:rotate(45deg)}';
	$c .= '.smdg-txt{font-size:14px;line-height:1.6;color:#3a3a3a;margin:0 0 9px;word-break:break-word}';
	$c .= '.smdg-anh{display:flex;gap:8px;flex-wrap:wrap;margin:0 0 9px}';
	$c .= '.smdg-ngay{font-size:12.5px;color:#a0a0a0}';
	$c .= '.smdg-tl{background:#f7f8f9;border-radius:9px;padding:11px 13px;margin:11px 0 0}';
	$c .= '.smdg-tl-h{font-size:13px;font-weight:600;color:#3a3a3a;display:flex;align-items:center;gap:6px;margin:0 0 4px}';
	$c .= '.smdg-tl-h i{display:inline-block;width:13px;height:13px;border-radius:50%;background:#2d7ff9;position:relative;flex:0 0 auto}';
	$c .= '.smdg-tl-h i::after{content:"";position:absolute;left:4px;top:2px;width:3px;height:6px;border-right:2px solid #fff;border-bottom:2px solid #fff;transform:rotate(45deg)}';
	$c .= '.smdg-tl-h span{font-weight:400;color:#a0a0a0;font-size:12px}';
	$c .= '.smdg-tl-n{font-size:13.5px;line-height:1.6;color:#5a5a5a}';
	$c .= '.smdg-mo{text-align:center;padding:16px 0 0}';
	$c .= '.smdg-mo-n{background:#fff;border:1px solid #e2e2e2;border-radius:999px;font-size:14px;font-weight:600;color:#4a4a4a;padding:11px 30px;margin:0;cursor:pointer;min-height:0}';
	$c .= '.smdg-mo-n:hover{border-color:' . $mau . ';color:' . $mau . '}';
	$c .= '.smdg-trong{display:none;text-align:center;color:#8d8d8d;font-size:13.5px;padding:26px 0}';
	$c .= '.smdg.rong .smdg-trong{display:block}';
	/* So da ban trong the san pham */
	$c .= '.smdg-l-db{font-size:12.5px;color:#8d8d8d;line-height:1.3;margin:3px 0 0}';
	/* Dong meta duoi ten san pham */
	$c .= '.smdg-meta{display:flex;align-items:center;gap:11px;flex-wrap:wrap;font-size:13.5px;color:#6a6a6a;margin:0}';
	$c .= '.smdg-m-sl{color:#6a6a6a !important;text-decoration:none !important;border-bottom:1px dashed #c9c9c9;transition:color .15s,border-color .15s}';
	$c .= '.smdg-m-sl:hover{color:' . $mau . ' !important;border-color:' . $mau . '}';
	$c .= '.smdg-m-sl strong,.smdg-m-db strong{font-weight:700;color:#3a3a3a}';
	$c .= '.smdg-m-db{position:relative;padding-left:12px}';
	$c .= '.smdg-m-db::before{content:"";position:absolute;left:0;top:50%;width:1px;height:13px;margin-top:-6px;background:#dcdcdc}';
	/* Thu vien anh */
	$c .= '.smdg-o.du{display:none}.smdg-them{cursor:pointer}';
	$c .= '.smdg-lb{position:fixed;inset:0;z-index:99999;background:rgba(10,10,12,.97);-webkit-backdrop-filter:blur(5px);backdrop-filter:blur(5px);display:none;flex-direction:column;align-items:center;justify-content:center;padding:58px 16px 112px}';
	$c .= '.smdg-lb.mo{display:flex}';
	$c .= '.smdg-lb-f{margin:0;max-width:100%;max-height:100%;display:flex;flex-direction:column;align-items:center;gap:12px}';
	$c .= '.smdg-lb-f img{max-width:100%;max-height:calc(100vh - 210px);border-radius:10px;display:block;object-fit:contain;background:#1b1b1f}';
	$c .= '.smdg-lb-c{font-size:13px;color:rgba(255,255,255,.72);text-align:center;letter-spacing:.02em}';
	$c .= '.smdg-lb-x,.smdg-lb-p,.smdg-lb-n{position:absolute;border:0;padding:0;margin:0;cursor:pointer;background:rgba(255,255,255,.12);border-radius:50%;transition:background .15s;min-height:0}';
	$c .= '.smdg-lb-x:hover,.smdg-lb-p:hover,.smdg-lb-n:hover{background:rgba(255,255,255,.26)}';
	$c .= '.smdg-lb-x{top:14px;right:16px;width:40px;height:40px}';
	$c .= '.smdg-lb-x::before,.smdg-lb-x::after{content:"";position:absolute;left:12px;top:19px;width:16px;height:2px;background:#fff;border-radius:2px}';
	$c .= '.smdg-lb-x::before{transform:rotate(45deg)}.smdg-lb-x::after{transform:rotate(-45deg)}';
	$c .= '.smdg-lb-p,.smdg-lb-n{top:50%;margin-top:-44px;width:46px;height:46px}';
	$c .= '.smdg-lb-p{left:16px}.smdg-lb-n{right:16px}';
	$c .= '.smdg-lb-p::after,.smdg-lb-n::after{content:"";position:absolute;top:16px;width:12px;height:12px;border-right:2.5px solid #fff;border-bottom:2.5px solid #fff}';
	$c .= '.smdg-lb-p::after{left:19px;transform:rotate(135deg)}.smdg-lb-n::after{right:19px;transform:rotate(-45deg)}';
	$c .= '.smdg-lb.don .smdg-lb-p,.smdg-lb.don .smdg-lb-n,.smdg-lb.don .smdg-lb-t{display:none}';
	$c .= '.smdg-lb-t{position:absolute;left:0;right:0;bottom:20px;display:flex;gap:8px;justify-content:flex-start;overflow-x:auto;padding:0 16px;scrollbar-width:none}';
	$c .= '.smdg-lb-t::-webkit-scrollbar{display:none}';
	$c .= '.smdg-lb-t span{flex:0 0 58px;height:58px;border-radius:7px;background:#2a2a30 center/cover no-repeat;cursor:pointer;opacity:.5;outline:2px solid transparent;transition:opacity .15s,outline-color .15s}';
	$c .= '.smdg-lb-t span.on{opacity:1;outline-color:#fff}';
	$c .= '@media (min-width:700px){.smdg-lb-t{justify-content:center}}';
	$c .= '@media (max-width:549px){.smdg-lb{padding:52px 10px 96px}.smdg-lb-p,.smdg-lb-n{width:38px;height:38px;margin-top:-40px}';
	$c .= '.smdg-lb-p{left:8px}.smdg-lb-n{right:8px}.smdg-lb-t span{flex:0 0 48px;height:48px}}';
	$c .= '@media (max-width:849px){.smdg-tk{gap:16px;padding:14px}.smdg-tk-l{flex:1 1 100%}.smdg-i{flex-direction:column;gap:8px;padding:15px 0}';
	$c .= '.smdg-ng{flex:1 1 auto}.smdg-dt{display:inline;margin:0 0 0 7px}.smdg-o{width:54px;height:54px}}';
	echo '<style id="smlife-dgsp">' . $c . '</style>'; // phpcs:ignore
}

function smlife_dgsp_js() {
	$j  = '(function(){';
	/* --- Thu vien anh, dung duoc ca khi khong co khoi danh gia --- */
	$j .= 'var lb=document.createElement("div");lb.className="smdg-lb";';
	$j .= 'lb.innerHTML=\'<button type="button" class="smdg-lb-x" aria-label="Dong"></button>\'';
	$j .= '+\'<button type="button" class="smdg-lb-p" aria-label="Anh truoc"></button>\'';
	$j .= '+\'<button type="button" class="smdg-lb-n" aria-label="Anh sau"></button>\'';
	$j .= '+\'<figure class="smdg-lb-f"><img alt=""><figcaption class="smdg-lb-c"></figcaption></figure>\'';
	$j .= '+\'<div class="smdg-lb-t"></div>\';';
	$j .= 'document.body.appendChild(lb);';
	$j .= 'var A=[],vt=0,cap="";var im=lb.querySelector("img"),ct=lb.querySelector(".smdg-lb-c"),th=lb.querySelector(".smdg-lb-t");';
	$j .= 'function ve(){if(!A.length)return;if(vt<0)vt=A.length-1;if(vt>=A.length)vt=0;im.src=A[vt];';
	$j .= 'ct.textContent=(cap?cap+" · ":"")+(vt+1)+" / "+A.length;';
	$j .= 'th.textContent="";for(var i=0;i<A.length;i++){var sp=document.createElement("span");if(i===vt)sp.className="on";';
	$j .= 'sp.setAttribute("data-i",i);sp.style.backgroundImage="url(\\""+String(A[i]).replace(/["\\\\]/g,"")+"\\")";th.appendChild(sp);}';
	$j .= 'var cu=th.querySelector(".on");if(cu&&cu.scrollIntoView)cu.scrollIntoView({inline:"center",block:"nearest"});';
	$j .= 'lb.classList.toggle("don",A.length<2);}';
	$j .= 'function mo(nh,i,ten){A=[];var ds=nh.querySelectorAll("[data-xem]");for(var k=0;k<ds.length;k++)A.push(ds[k].getAttribute("data-xem"));';
	$j .= 'if(!A.length)return;vt=i;cap=ten||"";lb.classList.add("mo");document.body.style.overflow="hidden";ve();}';
	$j .= 'function dong(){lb.classList.remove("mo");document.body.style.overflow="";im.removeAttribute("src");}';
	$j .= 'lb.addEventListener("click",function(e){var t=e.target;';
	$j .= 'if(t.closest(".smdg-lb-p")){vt--;ve();return;}if(t.closest(".smdg-lb-n")){vt++;ve();return;}';
	$j .= 'var tb=t.closest(".smdg-lb-t span");if(tb){vt=parseInt(tb.getAttribute("data-i"),10)||0;ve();return;}';
	$j .= 'if(t.closest(".smdg-lb-x")||!t.closest(".smdg-lb-f"))dong();});';
	$j .= 'document.addEventListener("keydown",function(e){if(!lb.classList.contains("mo"))return;';
	$j .= 'if(e.key==="Escape"){dong();}else if(e.key==="ArrowLeft"){vt--;ve();}else if(e.key==="ArrowRight"){vt++;ve();}});';
	$j .= 'var x0=null;lb.addEventListener("touchstart",function(e){x0=e.touches[0].clientX;},{passive:true});';
	$j .= 'lb.addEventListener("touchend",function(e){if(x0===null)return;var d=e.changedTouches[0].clientX-x0;';
	$j .= 'if(Math.abs(d)>40){vt+=(d<0?1:-1);ve();}x0=null;},{passive:true});';
	/* --- Loc va xem them, chi khi co khoi danh gia --- */
	$j .= 'var w=document.querySelector(".smdg");if(!w)return;';
	$j .= 'var dau=parseInt(w.getAttribute("data-dau"),10)||5;var loc={sao:0,anh:0,mua:0};var het=false;';
	$j .= 'function hien(){var ds=w.querySelectorAll(".smdg-i");var n=0;';
	$j .= 'for(var i=0;i<ds.length;i++){var e=ds[i];var ok=true;';
	$j .= 'if(loc.sao&&parseInt(e.getAttribute("data-sao"),10)!==loc.sao)ok=false;';
	$j .= 'if(loc.anh&&e.getAttribute("data-anh")!=="1")ok=false;';
	$j .= 'if(loc.mua&&e.getAttribute("data-mua")!=="1")ok=false;';
	$j .= 'e.classList.toggle("loc",!ok);if(ok){n++;e.classList.toggle("an",!het&&n>dau);}}';
	$j .= 'var nut=w.querySelector(".smdg-mo");if(nut)nut.style.display=(!het&&n>dau)?"":"none";';
	$j .= 'w.classList.toggle("rong",n===0);}';
	$j .= 'w.addEventListener("click",function(e){var t=e.target;';
	$j .= 'var o=t.closest(".smdg-o[data-xem]");';
	$j .= 'if(o){var nh=o.closest(".smdg-anh")||o.closest(".smdg-dai");if(!nh)return;';
	$j .= 'var ds=nh.querySelectorAll("[data-xem]");var k=0;for(var i=0;i<ds.length;i++){if(ds[i]===o){k=i;break;}}';
	$j .= 'var it=o.closest(".smdg-i");var tn=it&&it.querySelector(".smdg-ten")?it.querySelector(".smdg-ten").textContent:"";';
	$j .= 'mo(nh,k,tn);return;}';
	$j .= 'var tm=t.closest(".smdg-them");';
	$j .= 'if(tm){var nh2=tm.closest(".smdg-dai");if(nh2)mo(nh2,parseInt(tm.getAttribute("data-mo"),10)||0,"");return;}';
	$j .= 'var m=t.closest(".smdg-mo-n");if(m){het=true;hien();return;}';
	$j .= 'var b=t.closest(".smdg-n");if(!b)return;var v=b.getAttribute("data-loc");if(!v)return;';
	$j .= 'var bat=!b.classList.contains("on");';
	$j .= 'if(v==="anh"||v==="mua"){loc[v]=bat?1:0;b.classList.toggle("on",bat);}';
	$j .= 'else{loc.sao=bat?parseInt(v,10):0;var ns=w.querySelectorAll(".smdg-n[data-loc]");';
	$j .= 'for(var i=0;i<ns.length;i++){var vv=ns[i].getAttribute("data-loc");if(vv!=="anh"&&vv!=="mua")ns[i].classList.remove("on");}';
	$j .= 'b.classList.toggle("on",bat);}hien();});';
	$j .= 'hien();})();';
	/* Cuon toi khoi danh gia khi bam vao so danh gia o dong meta */
	$j .= '(function(){document.addEventListener("click",function(e){var a=e.target.closest?e.target.closest(".smdg-m-sl"):null;if(!a)return;';
	$j .= 'var k=document.getElementById("smdg-khoi");if(!k)return;e.preventDefault();';
	$j .= 'var y=k.getBoundingClientRect().top+window.pageYOffset-90;window.scrollTo({top:y,behavior:"smooth"});});})();';
	echo '<script>' . $j . '</script>'; // phpcs:ignore
}

/* ===== 5. Di chuyen du lieu tu plugin cu ===== */

/* Tim bang rieng cua plugin cu. Phai co du ba cot dac trung moi nhan. */
function smlife_dgsp_bang() {
	global $wpdb;
	$cache = get_option( 'smlife_dgsp_bang', '' );
	if ( ! preg_match( '/^[A-Za-z0-9_]{1,64}$/', (string) $cache ) ) {
		$cache = '';
	}
	if ( $cache && $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $wpdb->esc_like( $cache ) ) ) === $cache ) {
		return $cache;
	}
	$ds = $wpdb->get_col( 'SHOW TABLES' ); // phpcs:ignore
	if ( ! is_array( $ds ) ) {
		return '';
	}
	foreach ( $ds as $t ) {
		if ( ! preg_match( '/^[A-Za-z0-9_]{1,64}$/', (string) $t ) || ! preg_match( '/rv|review/i', $t ) ) {
			continue;
		}
		$cot = $wpdb->get_col( 'SHOW COLUMNS FROM `' . esc_sql( $t ) . '`' ); // phpcs:ignore
		if ( ! is_array( $cot ) ) {
			continue;
		}
		$cot = array_map( 'strtolower', $cot );
		if ( in_array( 'comment_id', $cot, true ) && in_array( 'stars', $cot, true ) && in_array( 'attactments', $cot, true ) ) {
			update_option( 'smlife_dgsp_bang', $t, false );
			return $t;
		}
	}
	return '';
}

/* Doc cot anh ve thanh mang URL, du luu kieu JSON hay serialize hay ngan cach dau phay */
function smlife_dgsp_tach_anh( $raw ) {
	$raw = trim( (string) $raw );
	if ( '' === $raw || 'null' === $raw || '[]' === $raw ) {
		return array();
	}
	$v = json_decode( $raw, true );
	if ( ! is_array( $v ) ) {
		$v = ( 0 === strpos( $raw, 'a:' ) ) ? @unserialize( $raw, array( 'allowed_classes' => false ) ) : false; // phpcs:ignore
	}
	if ( ! is_array( $v ) ) {
		$v = explode( ',', $raw );
	}
	$out = array();
	foreach ( $v as $u ) {
		if ( is_array( $u ) ) {
			$u = isset( $u['url'] ) ? $u['url'] : reset( $u );
		}
		$u = trim( (string) $u );
		if ( '' !== $u && preg_match( '#^https?://#i', $u ) ) {
			$out[] = $u;
		}
	}
	return array_values( array_unique( $out ) );
}

/* Thong ke truoc khi chay */
function smlife_dgsp_khao_sat() {
	global $wpdb;
	$t = smlife_dgsp_bang();
	$r = array( 'bang' => $t, 'tong' => 0, 'co_cmt' => 0, 'co_anh' => 0, 'tra_loi' => 0, 'thieu_cmt' => 0 );
	if ( ! $t ) {
		return $r;
	}
	$b           = '`' . esc_sql( $t ) . '`';
	$r['tong']   = (int) $wpdb->get_var( "SELECT COUNT(*) FROM $b" ); // phpcs:ignore
	$r['co_cmt'] = (int) $wpdb->get_var( "SELECT COUNT(*) FROM $b WHERE comment_id > 0" ); // phpcs:ignore
	$r['co_anh'] = (int) $wpdb->get_var( "SELECT COUNT(*) FROM $b WHERE attactments IS NOT NULL AND attactments <> '' AND attactments <> '[]'" ); // phpcs:ignore
	$r['tra_loi']= (int) $wpdb->get_var( "SELECT COUNT(*) FROM $b WHERE parent_id > 0" ); // phpcs:ignore
	$r['thieu_cmt'] = $r['tong'] - $r['co_cmt'];
	return $r;
}

/* Chay mot lo. Tra ve so dong da xu ly va so viec da lam. */
function smlife_dgsp_lo( $offset, $size = 200 ) {
	global $wpdb;
	$t = smlife_dgsp_bang();
	$kq = array( 'doc' => 0, 'anh' => 0, 'dt' => 0, 'mua' => 0, 'tl' => 0, 'bo' => 0 );
	if ( ! $t ) {
		return $kq;
	}
	$b    = '`' . esc_sql( $t ) . '`';
	$rows = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM $b ORDER BY id ASC LIMIT %d OFFSET %d", (int) $size, (int) $offset ) ); // phpcs:ignore
	if ( ! $rows ) {
		return $kq;
	}

	foreach ( $rows as $r ) {
		$kq['doc']++;
		$cid = isset( $r->comment_id ) ? (int) $r->comment_id : 0;

		/* Dong la tra loi cua shop */
		if ( ! empty( $r->parent_id ) && (int) $r->parent_id > 0 ) {
			$cha = $wpdb->get_var( $wpdb->prepare( "SELECT comment_id FROM $b WHERE id = %d", (int) $r->parent_id ) ); // phpcs:ignore
			$cha = (int) $cha;
			if ( $cha > 0 && get_comment( $cha ) ) {
				$da = get_comments( array(
					'parent'     => $cha,
					'count'      => true,
					'status'     => 'approve',
					'meta_key'   => 'smlife_dg_tu',   // phpcs:ignore
					'meta_value' => (string) $r->id,  // phpcs:ignore
				) );
				if ( ! $da ) {
					$p   = get_comment( $cha );
					$new = wp_insert_comment( array(
						'comment_post_ID'  => (int) $p->comment_post_ID,
						'comment_parent'   => $cha,
						'comment_author'   => '' !== trim( (string) $r->customer_name ) ? $r->customer_name : 'Chủ Shop',
						'comment_content'  => (string) $r->message,
						'comment_date_gmt' => ! empty( $r->created_at ) ? gmdate( 'Y-m-d H:i:s', strtotime( $r->created_at ) ) : current_time( 'mysql', 1 ),
						'comment_date'     => ! empty( $r->created_at ) ? gmdate( 'Y-m-d H:i:s', strtotime( $r->created_at ) + ( (int) get_option( 'gmt_offset' ) * 3600 ) ) : current_time( 'mysql' ),
						'comment_approved' => 1,
						'comment_type'     => 'comment',
					) );
					if ( $new ) {
						add_comment_meta( $new, 'smlife_dg_tu', (string) $r->id );
						$kq['tl']++;
					}
				}
			}
			continue;
		}

		if ( $cid < 1 || ! get_comment( $cid ) ) {
			$kq['bo']++;
			continue;
		}

		/* Anh */
		$anh = smlife_dgsp_tach_anh( isset( $r->attactments ) ? $r->attactments : '' );
		if ( $anh ) {
			update_comment_meta( $cid, smlife_dgsp_k_anh(), $anh );
			$kq['anh']++;
		}
		/* So dien thoai */
		$dt = isset( $r->customer_phone_hidden ) ? trim( (string) $r->customer_phone_hidden ) : '';
		if ( '' !== $dt ) {
			update_comment_meta( $cid, smlife_dgsp_k_dt(), $dt );
			$kq['dt']++;
		}
		/* Nhan da mua hang */
		if ( ! empty( $r->buyed ) ) {
			update_comment_meta( $cid, smlife_dgsp_k_mua(), 1 );
			$kq['mua']++;
		}
		/* Bao hiem: neu comment chua co so sao thi lay tu bang cu */
		$sao = (int) get_comment_meta( $cid, 'rating', true );
		if ( $sao < 1 && ! empty( $r->stars ) ) {
			update_comment_meta( $cid, 'rating', (int) $r->stars );
		}
		update_comment_meta( $cid, 'smlife_dg_tu', (string) $r->id );
	}
	return $kq;
}


/* ===== 7. Cong cu so luong da ban ===== */

/* Do khoa meta cua plugin cu: doc toan bo meta cua DUNG MOT san pham roi
   doi chieu voi con so dang hien ngoai trang. Mot truy van theo post_id,
   co chi muc, nen nhe. */
function smlife_dgsp_db_do( $pid, $so ) {
	global $wpdb;
	$rows = $wpdb->get_results( $wpdb->prepare( "SELECT meta_key, meta_value FROM {$wpdb->postmeta} WHERE post_id = %d", (int) $pid ) ); // phpcs:ignore
	$out  = array();
	foreach ( (array) $rows as $r ) {
		if ( (string) trim( (string) $r->meta_value ) === (string) trim( (string) $so ) ) {
			$out[] = $r->meta_key;
		}
	}
	return $out;
}

/* Chep gia tri tu khoa cu sang khoa cua module, theo tung lo */
function smlife_dgsp_db_chep( $off, $size = 300 ) {
	global $wpdb;
	$o = smlife_dgsp_opt();
	$k = trim( (string) $o['db_khoa'] );
	$kq = array( 'doc' => 0, 'ghi' => 0 );
	if ( '' === $k || $k === smlife_dgsp_k_db() ) {
		return $kq;
	}
	$rows = $wpdb->get_results( $wpdb->prepare( // phpcs:ignore
		"SELECT pm.post_id AS pid, pm.meta_value AS v
		 FROM {$wpdb->postmeta} pm
		 INNER JOIN {$wpdb->posts} p ON p.ID = pm.post_id AND p.post_type = 'product'
		 WHERE pm.meta_key = %s
		 ORDER BY pm.post_id ASC LIMIT %d OFFSET %d",
		$k, (int) $size, (int) $off
	) );
	foreach ( (array) $rows as $r ) {
		$kq['doc']++;
		$n = (int) $r->v;
		if ( $n > 0 ) {
			update_post_meta( (int) $r->pid, smlife_dgsp_k_db(), $n );
			$kq['ghi']++;
		}
	}
	return $kq;
}

/* Sinh so ngau nhien theo tung lo */
function smlife_dgsp_db_sinh( $off, $size, $min, $max, $cat, $de ) {
	$min = max( 0, (int) $min );
	$max = max( $min, (int) $max );
	$args = array(
		'post_type'           => 'product',
		'post_status'         => 'publish',
		'fields'              => 'ids',
		'posts_per_page'      => (int) $size,
		'offset'              => (int) $off,
		'orderby'             => 'ID',
		'order'               => 'ASC',
		'ignore_sticky_posts' => true,
		'no_found_rows'       => true,
		'suppress_filters'    => true,
	);
	if ( (int) $cat > 0 ) {
		$args['tax_query'] = array( // phpcs:ignore
			array( 'taxonomy' => 'product_cat', 'field' => 'term_id', 'terms' => array( (int) $cat ), 'include_children' => true ),
		);
	}
	$ids = get_posts( $args );
	$kq  = array( 'doc' => 0, 'ghi' => 0 );
	foreach ( (array) $ids as $id ) {
		$kq['doc']++;
		if ( empty( $de ) ) {
			$cu = (int) get_post_meta( $id, smlife_dgsp_k_db(), true );
			if ( $cu > 0 ) {
				continue;
			}
		}
		update_post_meta( $id, smlife_dgsp_k_db(), wp_rand( $min, $max ) );
		$kq['ghi']++;
	}
	return $kq;
}

add_action( 'wp_ajax_smlife_dgsp_db', function () {
	if ( ! current_user_can( 'manage_options' ) ) { wp_send_json_error(); }
	check_ajax_referer( 'smlife_dgsp_db', '_n' );
	$viec = isset( $_POST['viec'] ) ? sanitize_key( wp_unslash( $_POST['viec'] ) ) : '';
	$off  = isset( $_POST['off'] ) ? max( 0, (int) $_POST['off'] ) : 0;
	if ( 'chep' === $viec ) {
		wp_send_json_success( smlife_dgsp_db_chep( $off, 300 ) );
	}
	if ( 'sinh' === $viec ) {
		wp_send_json_success( smlife_dgsp_db_sinh(
			$off, 300,
			isset( $_POST['min'] ) ? (int) $_POST['min'] : 10,
			isset( $_POST['max'] ) ? (int) $_POST['max'] : 300,
			isset( $_POST['cat'] ) ? (int) $_POST['cat'] : 0,
			! empty( $_POST['de'] ) ? 1 : 0
		) );
	}
	wp_send_json_error();
} );

add_action( 'admin_post_smlife_dgsp_do', function () {
	if ( ! current_user_can( 'manage_options' ) ) { wp_die( 'Không đủ quyền.' ); }
	check_admin_referer( 'smlife_dgsp_do', 'smlife_dgsp_do_nonce' );
	$pid = isset( $_POST['pid'] ) ? (int) $_POST['pid'] : 0;
	$so  = isset( $_POST['so'] ) ? (int) $_POST['so'] : 0;
	$ds  = ( $pid > 0 && $so > 0 ) ? smlife_dgsp_db_do( $pid, $so ) : array();
	set_transient( 'smlife_dgsp_do_kq', array( 'pid' => $pid, 'so' => $so, 'ds' => $ds ), 300 );
	wp_safe_redirect( admin_url( 'admin.php?page=smlife-dg-san-pham&do=1#db' ) );
	exit;
} );

/* ===== 6. Trang quan tri ===== */

add_action( 'admin_menu', function () {
	add_submenu_page( 'smlife-core', 'Đánh giá sản phẩm', 'Đánh giá sản phẩm', 'manage_options', 'smlife-dg-san-pham', 'smlife_dgsp_page' );
}, 14 );

/* ===== 8. NHAP DANH GIA TU FILE CSV (1.22.0) =====
 * Dung cho danh gia that thu thap tu kenh khac (Shopee, Zalo, form giay...).
 * Quy trinh: tai file mau -> dien -> tai len -> xem truoc tung dong -> bam Nhap.
 * - Tim san pham theo product_id, khong co thi theo SKU. Bien the tu doi ve san pham cha.
 * - Moi dong danh dau ma bam (san pham + ten + noi dung) de nhap lai file khong bi trung.
 * - Nhap xong tu tinh lai diem trung binh va lam moi cache dung trang san pham do.
 */
function smlife_dgsp_csv_cot() {
	return array(
		'product_id'    => 'ID sản phẩm (hoặc để trống và điền SKU)',
		'sku'           => 'Mã SKU, dùng khi không có ID',
		'ten_khach'     => 'Tên người đánh giá (bắt buộc)',
		'so_sao'        => 'Số sao 1 đến 5 (bắt buộc)',
		'noi_dung'      => 'Nội dung đánh giá (bắt buộc)',
		'ngay'          => 'Ngày đánh giá: 2026-10-28 hoặc 28/10/2026, có thể kèm giờ. Để trống là ngày nhập',
		'so_dien_thoai' => 'Số điện thoại, ngoài trang tự che 3 số cuối',
		'da_mua'        => '1 nếu khách đã mua hàng (hiện nhãn đã mua), 0 hoặc trống nếu không',
		'anh'           => 'Link ảnh, nhiều ảnh cách nhau bằng dấu |',
		'tra_loi_shop'  => 'Câu trả lời của shop, để trống nếu không có',
	);
}

function smlife_dgsp_csv_ngay( $s ) {
	$s = trim( (string) $s );
	if ( '' === $s ) {
		return time();
	}
	$tz = wp_timezone();
	foreach ( array( 'Y-m-d H:i:s', 'Y-m-d H:i', 'Y-m-d', 'd/m/Y H:i:s', 'd/m/Y H:i', 'd/m/Y', 'd-m-Y', 'd.m.Y' ) as $f ) {
		$d = DateTime::createFromFormat( '!' . $f, $s, $tz );
		if ( $d && $d->format( $f ) === $s ) {
			return $d->getTimestamp();
		}
	}
	return false;
}

function smlife_dgsp_csv_co( $v ) {
	$v = strtolower( trim( (string) $v ) );
	return in_array( $v, array( '1', 'x', 'co', 'có', 'yes', 'true', 'y' ), true );
}

/* Doc file CSV thanh danh sach dong da kiem tra */
function smlife_dgsp_csv_doc( $file ) {
	$out = array( 'rows' => array(), 'loi' => '' );
	$fh  = fopen( $file, 'r' );
	if ( ! $fh ) {
		$out['loi'] = 'Không đọc được file.';
		return $out;
	}
	$dau = fgets( $fh );
	if ( false === $dau ) {
		fclose( $fh );
		$out['loi'] = 'File rỗng.';
		return $out;
	}
	$dau = preg_replace( '/^\xEF\xBB\xBF/', '', $dau );
	$sep = substr_count( $dau, ';' ) > substr_count( $dau, ',' ) ? ';' : ',';
	$cot = array_map( function ( $c ) {
		return strtolower( trim( $c ) );
	}, str_getcsv( trim( $dau ), $sep ) );
	foreach ( array( 'ten_khach', 'so_sao', 'noi_dung' ) as $bb ) {
		if ( ! in_array( $bb, $cot, true ) ) {
			fclose( $fh );
			$out['loi'] = 'File thiếu cột bắt buộc "' . $bb . '". Hãy dùng đúng file mẫu.';
			return $out;
		}
	}
	if ( ! in_array( 'product_id', $cot, true ) && ! in_array( 'sku', $cot, true ) ) {
		fclose( $fh );
		$out['loi'] = 'File phải có cột product_id hoặc sku.';
		return $out;
	}
	$dong = 1;
	$bam  = array();
	while ( false !== ( $r = fgetcsv( $fh, 0, $sep ) ) ) {
		$dong++;
		if ( count( $out['rows'] ) >= 2000 ) {
			$out['loi'] = 'File quá 2.000 dòng, chỉ đọc 2.000 dòng đầu. Chia nhỏ file để nhập tiếp.';
			break;
		}
		if ( ! array_filter( array_map( 'trim', (array) $r ) ) ) {
			continue;
		}
		$v = array();
		foreach ( $cot as $i => $c ) {
			$v[ $c ] = isset( $r[ $i ] ) ? trim( (string) $r[ $i ] ) : '';
		}
		$x = array(
			'dong' => $dong,
			'pid'  => 0,
			'sp'   => '',
			'ten'  => sanitize_text_field( isset( $v['ten_khach'] ) ? $v['ten_khach'] : '' ),
			'sao'  => (int) ( isset( $v['so_sao'] ) ? $v['so_sao'] : 0 ),
			'noi'  => sanitize_textarea_field( isset( $v['noi_dung'] ) ? $v['noi_dung'] : '' ),
			'ts'   => smlife_dgsp_csv_ngay( isset( $v['ngay'] ) ? $v['ngay'] : '' ),
			'dt'   => preg_replace( '/[^0-9+]/', '', isset( $v['so_dien_thoai'] ) ? $v['so_dien_thoai'] : '' ),
			'mua'  => smlife_dgsp_csv_co( isset( $v['da_mua'] ) ? $v['da_mua'] : '' ) ? 1 : 0,
			'anh'  => array(),
			'tl'   => sanitize_textarea_field( isset( $v['tra_loi_shop'] ) ? $v['tra_loi_shop'] : '' ),
			'loi'  => array(),
			'bam'  => '',
		);
		/* San pham */
		$pid = isset( $v['product_id'] ) ? absint( $v['product_id'] ) : 0;
		if ( ! $pid && ! empty( $v['sku'] ) && function_exists( 'wc_get_product_id_by_sku' ) ) {
			$pid = (int) wc_get_product_id_by_sku( $v['sku'] );
		}
		$p = $pid ? wc_get_product( $pid ) : null;
		if ( $p && $p->get_parent_id() ) {
			$p = wc_get_product( $p->get_parent_id() );
		}
		if ( ! $p ) {
			$x['loi'][] = 'không tìm thấy sản phẩm';
		} else {
			$x['pid'] = (int) $p->get_id();
			$x['sp']  = $p->get_name();
		}
		if ( '' === $x['ten'] ) {
			$x['loi'][] = 'thiếu tên';
		}
		if ( $x['sao'] < 1 || $x['sao'] > 5 ) {
			$x['loi'][] = 'số sao phải từ 1 đến 5';
		}
		if ( '' === $x['noi'] ) {
			$x['loi'][] = 'thiếu nội dung';
		}
		if ( false === $x['ts'] ) {
			$x['loi'][] = 'ngày không đúng định dạng';
		} elseif ( $x['ts'] > time() + HOUR_IN_SECONDS ) {
			$x['loi'][] = 'ngày ở tương lai';
		}
		foreach ( preg_split( '/[|\n]+/', isset( $v['anh'] ) ? $v['anh'] : '' ) as $u ) {
			$u = esc_url_raw( trim( $u ) );
			if ( '' !== $u && preg_match( '#^https?://#i', $u ) ) {
				$x['anh'][] = $u;
			}
		}
		/* Chong trung: trong file va voi danh gia da nhap truoc */
		$x['bam'] = md5( $x['pid'] . '|' . mb_strtolower( $x['ten'] ) . '|' . mb_strtolower( $x['noi'] ) );
		if ( ! $x['loi'] ) {
			if ( isset( $bam[ $x['bam'] ] ) ) {
				$x['loi'][] = 'trùng dòng ' . $bam[ $x['bam'] ];
				$x['trung'] = 1;
			} elseif ( get_comments( array( 'post_id' => $x['pid'], 'count' => true, 'status' => 'all', 'meta_key' => 'smlife_dg_csv', 'meta_value' => $x['bam'] ) ) ) { // phpcs:ignore
				$x['loi'][] = 'đã nhập trước đây';
				$x['trung'] = 1;
			}
			$bam[ $x['bam'] ] = $dong;
		}
		$out['rows'][] = $x;
	}
	fclose( $fh );
	return $out;
}

function smlife_dgsp_csv_khoa() {
	return 'smlife_dgsp_csv_' . get_current_user_id();
}

/* Tai file mau */
add_action( 'admin_post_smlife_dgsp_mau', function () {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( 'Không đủ quyền.' );
	}
	check_admin_referer( 'smlife_dgsp_mau' );
	nocache_headers();
	header( 'Content-Type: text/csv; charset=utf-8' );
	header( 'Content-Disposition: attachment; filename="mau-nhap-danh-gia.csv"' );
	$f = fopen( 'php://output', 'w' );
	fwrite( $f, "\xEF\xBB\xBF" );
	fputcsv( $f, array_keys( smlife_dgsp_csv_cot() ) );
	fputcsv( $f, array( '12345', '', 'Nguyễn Văn A', '5', 'Nội dung khách viết', '2026-10-20', '0901234567', '1', 'https://smlife.vn/wp-content/uploads/anh-1.jpg|https://smlife.vn/wp-content/uploads/anh-2.jpg', 'Cảm ơn anh đã ủng hộ SMLIFE' ) );
	fputcsv( $f, array( '', 'SKU-MAU', 'Trần Thị B', '4', 'Nội dung khách viết', '20/10/2026 14:30', '', '0', '', '' ) );
	fclose( $f );
	exit;
} );

/* Tai len va kiem tra */
add_action( 'admin_post_smlife_dgsp_csv', function () {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( 'Không đủ quyền.' );
	}
	check_admin_referer( 'smlife_dgsp_csv', 'smlife_dgsp_csv_nonce' );
	$back = admin_url( 'admin.php?page=smlife-dg-san-pham#nhap' );
	if ( empty( $_FILES['csv']['tmp_name'] ) || ! is_uploaded_file( $_FILES['csv']['tmp_name'] ) ) { // phpcs:ignore
		set_transient( smlife_dgsp_csv_khoa(), array( 'rows' => array(), 'loi' => 'Chưa chọn file.' ), HOUR_IN_SECONDS );
		wp_safe_redirect( $back );
		exit;
	}
	$ten = isset( $_FILES['csv']['name'] ) ? sanitize_file_name( wp_unslash( $_FILES['csv']['name'] ) ) : ''; // phpcs:ignore
	if ( ! preg_match( '/\.csv$/i', $ten ) ) {
		set_transient( smlife_dgsp_csv_khoa(), array( 'rows' => array(), 'loi' => 'Chỉ nhận file .csv. Trong Excel chọn Lưu thành > CSV UTF-8.' ), HOUR_IN_SECONDS );
		wp_safe_redirect( $back );
		exit;
	}
	$kq         = smlife_dgsp_csv_doc( $_FILES['csv']['tmp_name'] ); // phpcs:ignore
	$kq['file'] = $ten;
	set_transient( smlife_dgsp_csv_khoa(), $kq, HOUR_IN_SECONDS );
	wp_safe_redirect( $back );
	exit;
} );

/* Nhap cac dong hop le */
add_action( 'admin_post_smlife_dgsp_csv_nhap', function () {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( 'Không đủ quyền.' );
	}
	check_admin_referer( 'smlife_dgsp_csv_nhap', 'smlife_dgsp_csv_nonce' );
	$kq = get_transient( smlife_dgsp_csv_khoa() );
	delete_transient( smlife_dgsp_csv_khoa() );
	$n    = 0;
	$sp   = array();
	$o    = smlife_dgsp_opt();
	$shop = '' !== trim( (string) $o['ten_shop'] ) ? $o['ten_shop'] : 'Chủ Shop';
	foreach ( is_array( $kq ) && ! empty( $kq['rows'] ) ? $kq['rows'] : array() as $x ) {
		if ( $x['loi'] || ! $x['pid'] ) {
			continue;
		}
		/* Kiem tra trung lan nua phong khi bam Nhap 2 lan */
		if ( get_comments( array( 'post_id' => $x['pid'], 'count' => true, 'status' => 'all', 'meta_key' => 'smlife_dg_csv', 'meta_value' => $x['bam'] ) ) ) { // phpcs:ignore
			continue;
		}
		$ts  = (int) $x['ts'];
		$cid = wp_insert_comment( array(
			'comment_post_ID'  => $x['pid'],
			'comment_author'   => $x['ten'],
			'comment_content'  => $x['noi'],
			'comment_type'     => 'review',
			'comment_approved' => 1,
			'comment_date'     => wp_date( 'Y-m-d H:i:s', $ts ),
			'comment_date_gmt' => gmdate( 'Y-m-d H:i:s', $ts ),
		) );
		if ( ! $cid ) {
			continue;
		}
		update_comment_meta( $cid, 'rating', (int) $x['sao'] );
		update_comment_meta( $cid, 'verified', $x['mua'] ? 1 : 0 );
		update_comment_meta( $cid, smlife_dgsp_k_mua(), $x['mua'] ? 1 : 0 );
		if ( $x['anh'] ) {
			update_comment_meta( $cid, smlife_dgsp_k_anh(), $x['anh'] );
		}
		if ( '' !== $x['dt'] ) {
			update_comment_meta( $cid, smlife_dgsp_k_dt(), $x['dt'] );
		}
		update_comment_meta( $cid, 'smlife_dg_csv', $x['bam'] );
		if ( '' !== $x['tl'] ) {
			$tts = min( time(), $ts + 2 * HOUR_IN_SECONDS );
			wp_insert_comment( array(
				'comment_post_ID'  => $x['pid'],
				'comment_parent'   => $cid,
				'comment_author'   => $shop,
				'comment_content'  => $x['tl'],
				'comment_type'     => 'comment',
				'comment_approved' => 1,
				'comment_date'     => wp_date( 'Y-m-d H:i:s', $tts ),
				'comment_date_gmt' => gmdate( 'Y-m-d H:i:s', $tts ),
			) );
		}
		$sp[ $x['pid'] ] = true;
		$n++;
	}
	/* Tinh lai diem, lam moi cache dung cac trang san pham co danh gia moi */
	foreach ( array_keys( $sp ) as $pid ) {
		if ( class_exists( 'WC_Comments' ) ) {
			WC_Comments::clear_transients( $pid );
		}
		if ( function_exists( 'rocket_clean_post' ) ) {
			rocket_clean_post( $pid );
		}
	}
	wp_safe_redirect( admin_url( 'admin.php?page=smlife-dg-san-pham&nhap_xong=' . $n . '&nhap_sp=' . count( $sp ) . '#nhap' ) );
	exit;
} );

add_action( 'admin_post_smlife_dgsp_csv_huy', function () {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( 'Không đủ quyền.' );
	}
	check_admin_referer( 'smlife_dgsp_csv_huy' );
	delete_transient( smlife_dgsp_csv_khoa() );
	wp_safe_redirect( admin_url( 'admin.php?page=smlife-dg-san-pham#nhap' ) );
	exit;
} );

/* Khoi giao dien nhap file trong trang quan tri */
function smlife_dgsp_csv_khoi() {
	echo '<div class="smlife-card" id="nhap"><h2>Nhập đánh giá từ file</h2><div class="in">';
	if ( isset( $_GET['nhap_xong'] ) ) {
		echo '<div class="notice notice-success"><p>Đã nhập <b>' . (int) $_GET['nhap_xong'] . '</b> đánh giá vào <b>' . (int) ( isset( $_GET['nhap_sp'] ) ? $_GET['nhap_sp'] : 0 ) . '</b> sản phẩm. Điểm trung bình và cache các trang đó đã được làm mới.</p></div>';
	}
	echo '<div class="smdg-buoc">';
	echo '<div><b>1. Tải file mẫu</b><a class="button" href="' . esc_url( wp_nonce_url( admin_url( 'admin-post.php?action=smlife_dgsp_mau' ), 'smlife_dgsp_mau' ) ) . '" style="margin-top:6px">Tải file mẫu .csv</a></div>';
	echo '<div><b>2. Điền đánh giá</b>Mở bằng Excel hoặc Google Sheets, mỗi dòng một đánh giá. Lưu lại dạng <b>CSV UTF-8</b>.</div>';
	echo '<div><b>3. Tải lên và xem trước</b>Hệ thống kiểm tra từng dòng, báo dòng lỗi và dòng trùng. Bấm Nhập mới ghi vào website.</div>';
	echo '</div>';

	$kq = get_transient( smlife_dgsp_csv_khoa() );
	if ( is_array( $kq ) && ( ! empty( $kq['rows'] ) || ! empty( $kq['loi'] ) ) ) {
		$ok = array_filter( $kq['rows'], function ( $x ) {
			return ! $x['loi'];
		} );
		$trung = array_filter( $kq['rows'], function ( $x ) {
			return ! empty( $x['trung'] );
		} );
		$loi = count( $kq['rows'] ) - count( $ok ) - count( $trung );
		if ( ! empty( $kq['loi'] ) ) {
			echo '<div class="notice notice-warning"><p>' . esc_html( $kq['loi'] ) . '</p></div>';
		}
		if ( $kq['rows'] ) {
			echo '<p style="display:flex;gap:8px;flex-wrap:wrap;align-items:center;margin:0 0 12px"><b>' . esc_html( isset( $kq['file'] ) ? $kq['file'] : 'File' ) . '</b>';
			echo '<span class="smc-pill ok">' . count( $ok ) . ' dòng hợp lệ</span>';
			echo $loi ? '<span class="smc-pill bad">' . (int) $loi . ' dòng lỗi</span>' : ''; // phpcs:ignore
			echo $trung ? '<span class="smc-pill mute">' . count( $trung ) . ' dòng trùng, bỏ qua</span>' : ''; // phpcs:ignore
			echo '</p>';
			echo '<div style="max-height:420px;overflow:auto;margin:0 0 14px"><table class="widefat"><thead><tr><th style="width:52px">Dòng</th><th>Sản phẩm</th><th style="width:130px">Khách</th><th style="width:60px">Sao</th><th>Nội dung</th><th style="width:96px">Ngày</th><th style="width:150px">Kết quả</th></tr></thead><tbody>';
			foreach ( array_slice( $kq['rows'], 0, 300 ) as $x ) {
				$cls = $x['loi'] ? ( ! empty( $x['trung'] ) ? 'mute' : 'bad' ) : 'ok';
				echo '<tr><td>' . (int) $x['dong'] . '</td>';
				echo '<td style="font-size:12px">' . ( $x['pid'] ? '#' . (int) $x['pid'] . ' ' . esc_html( $x['sp'] ) : '-' ) . '</td>';
				echo '<td>' . esc_html( $x['ten'] ) . ( $x['mua'] ? '<br><span class="smc-pill ac" style="margin-top:3px">đã mua</span>' : '' ) . '</td>';
				echo '<td>' . (int) $x['sao'] . '★</td>';
				echo '<td style="font-size:12px">' . esc_html( wp_trim_words( $x['noi'], 22 ) ) . ( $x['anh'] ? ' <span class="smc-pill mute">' . count( $x['anh'] ) . ' ảnh</span>' : '' ) . ( '' !== $x['tl'] ? ' <span class="smc-pill mute">có trả lời</span>' : '' ) . '</td>';
				echo '<td style="font-size:12px">' . ( $x['ts'] ? esc_html( wp_date( 'd/m/Y', (int) $x['ts'] ) ) : '-' ) . '</td>';
				echo '<td><span class="smc-pill ' . esc_attr( $cls ) . '">' . esc_html( $x['loi'] ? implode( ', ', $x['loi'] ) : 'Sẵn sàng' ) . '</span></td></tr>';
			}
			echo '</tbody></table></div>';
			if ( count( $kq['rows'] ) > 300 ) {
				echo '<p class="description">Chỉ hiện 300 dòng đầu để xem trước, khi nhập vẫn xử lý toàn bộ.</p>';
			}
			echo '<div style="display:flex;gap:10px;align-items:center;flex-wrap:wrap">';
			if ( $ok ) {
				echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '" style="margin:0">';
				echo '<input type="hidden" name="action" value="smlife_dgsp_csv_nhap" />';
				wp_nonce_field( 'smlife_dgsp_csv_nhap', 'smlife_dgsp_csv_nonce' );
				submit_button( 'Nhập ' . count( $ok ) . ' đánh giá hợp lệ', 'primary', 'submit', false );
				echo '</form>';
			}
			echo '<a class="button" href="' . esc_url( wp_nonce_url( admin_url( 'admin-post.php?action=smlife_dgsp_csv_huy' ), 'smlife_dgsp_csv_huy' ) ) . '">Huỷ, chọn file khác</a>';
			echo '</div>';
			echo '</div></div>';
			return;
		}
	}

	echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '" enctype="multipart/form-data" style="display:flex;gap:10px;align-items:center;flex-wrap:wrap;margin:0 0 14px">';
	echo '<input type="hidden" name="action" value="smlife_dgsp_csv" />';
	wp_nonce_field( 'smlife_dgsp_csv', 'smlife_dgsp_csv_nonce' );
	echo '<input type="file" name="csv" accept=".csv,text/csv" required /> ';
	submit_button( 'Tải lên và kiểm tra', 'primary', 'submit', false );
	echo '</form>';
	echo '<details><summary style="cursor:pointer;font-weight:600;color:var(--smc-tx)">Các cột trong file mẫu</summary><table class="widefat smdg-cot"><tbody>';
	foreach ( smlife_dgsp_csv_cot() as $k => $mo ) {
		echo '<tr><td><code>' . esc_html( $k ) . '</code></td><td>' . esc_html( $mo ) . '</td></tr>';
	}
	echo '</tbody></table></details>';
	echo '<p class="description" style="margin:12px 0 0">Nhập lại cùng một file không tạo trùng: dòng nào đã nhập (cùng sản phẩm, tên và nội dung) sẽ tự bỏ qua. Ảnh cần tải lên Thư viện trước rồi dán link vào cột anh.</p>';
	echo '</div></div>';
}

function smlife_dgsp_page() {
	if ( ! current_user_can( 'manage_options' ) ) { return; }
	$o  = smlife_dgsp_opt();
	$ks = smlife_dgsp_khao_sat();

	echo '<div class="wrap smlife-wrap">';
	echo '<div class="smc-hd"><h1>Đánh giá sản phẩm</h1>';
	echo '<p>Khối đánh giá đọc thẳng từ đánh giá chuẩn của WooCommerce. Đặt <code>[smlife_dg_sp]</code> vào Product Layout, thay cho <code>[WP_REVIEWS_MH]</code>.</p></div>';
	if ( isset( $_GET['updated'] ) ) { echo '<div class="notice notice-success is-dismissible"><p>Đã lưu.</p></div>'; }

	/* --- Cai dat --- */
	echo '<div class="smlife-card"><h2>Hiển thị</h2><div class="in">';
	echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '">';
	echo '<input type="hidden" name="action" value="smlife_dgsp_save" />';
	wp_nonce_field( 'smlife_dgsp_save', 'smlife_dgsp_nonce' );
	echo '<table class="form-table">';
	echo '<tr><th>Bật module</th><td><label><input type="checkbox" name="dg[enabled]" value="1" ' . checked( 1, $o['enabled'], false ) . ' /> Cho phép shortcode <code>[smlife_dg_sp]</code> hoạt động</label></td></tr>';
	echo '<tr><th>Hiện sẵn bao nhiêu đánh giá</th><td><input type="number" min="1" max="30" name="dg[so_dau]" value="' . esc_attr( $o['so_dau'] ) . '" class="small-text" /> <span class="description">Số còn lại ẩn đi, khách bấm Xem thêm mới mở.</span></td></tr>';
	echo '<tr><th>Tải tối đa mỗi trang</th><td><input type="number" min="5" max="200" name="dg[so_toi_da]" value="' . esc_attr( $o['so_toi_da'] ) . '" class="small-text" /> <span class="description"><b>Ảnh hưởng trực tiếp tới tốc độ trang.</b> Mỗi đánh giá là một khối HTML kèm ảnh. Để 20 là vừa; muốn 100 thì trang sản phẩm nặng thêm thấy rõ. Bộ lọc chỉ lọc trong số đã tải ra.</span></td></tr>';
	echo '<tr><th>Hàng lọc</th><td><label><input type="checkbox" name="dg[loc]" value="1" ' . checked( 1, $o['loc'], false ) . ' /> Hiện hàng lọc theo hình ảnh, đã mua hàng và mức sao</label></td></tr>';
	echo '<tr><th>Dải ảnh đánh giá</th><td><label><input type="checkbox" name="dg[anh_dai]" value="1" ' . checked( 1, $o['anh_dai'], false ) . ' /> Hiện dải ảnh thật ở ô tổng kết</label></td></tr>';
	echo '<tr><th>Trả lời của shop</th><td><label><input type="checkbox" name="dg[tra_loi]" value="1" ' . checked( 1, $o['tra_loi'], false ) . ' /> Hiện phần Chủ Shop đã trả lời dưới mỗi đánh giá</label></td></tr>';
	echo '<tr><th>Nhãn đã mua hàng</th><td><input type="text" name="dg[nhan_mua]" value="' . esc_attr( $o['nhan_mua'] ) . '" class="regular-text" /> <span class="description">Để trống là không hiện nhãn.</span></td></tr>';
	echo '<tr><th>Hiện số đã bán</th><td><label><input type="checkbox" name="dg[db_hien]" value="1" ' . checked( 1, $o['db_hien'], false ) . ' /> Hiện số đã bán trong <code>[smlife_dg_meta]</code></label></td></tr>';
	echo '<tr><th>Số đã bán trong thẻ sản phẩm</th><td><label><input type="checkbox" name="dg[db_the]" value="1" ' . checked( 1, $o['db_the'], false ) . ' /> Hiện ở trang danh mục, trong từng thẻ sản phẩm</label><br /><select name="dg[db_vitri]" style="margin-top:6px">';
	foreach ( array( 'ten' => 'Ngay dưới tên sản phẩm', 'gia_truoc' => 'Phía trên khối giá', 'gia_sau' => 'Phía dưới khối giá' ) as $k => $lb ) {
		echo '<option value="' . esc_attr( $k ) . '" ' . selected( $o['db_vitri'], $k, false ) . '>' . esc_html( $lb ) . '</option>';
	}
	echo '</select> <span class="description">Plugin cũ đặt ngay dưới tên sản phẩm. Lệch chỗ thì đổi sang lựa chọn khác.</span></td></tr>';
	echo '<tr><th>Nhãn sau số đã bán</th><td><input type="text" name="dg[db_nhan]" value="' . esc_attr( $o['db_nhan'] ) . '" class="regular-text" /></td></tr>';
	echo '<tr><th>Khoá số đã bán của plugin cũ</th><td><input type="text" name="dg[db_khoa]" value="' . esc_attr( $o['db_khoa'] ) . '" class="regular-text" /> <span class="description">Dùng tạm khi chưa chép sang. Xem mục Số lượng đã bán.</span></td></tr>';
	echo '<tr><th>Tên shop khi trả lời</th><td><input type="text" name="dg[ten_shop]" value="' . esc_attr( $o['ten_shop'] ) . '" class="regular-text" /></td></tr>';
	echo '</table>';
	submit_button( 'Lưu' );
	echo '</form></div></div>';

	smlife_dgsp_csv_khoi();

	/* --- So luong da ban --- */
	echo '<div class="smlife-card" id="db"><h2>Số lượng đã bán</h2><div class="in">';
	echo '<p style="margin-top:0">Con số này do plugin cũ rải ngẫu nhiên chứ không phải số bán thật. Đặt <code>[smlife_dg_meta]</code> ngay dưới tên sản phẩm là hiện ra dòng sao, số đánh giá và số đã bán. Bấm vào chỗ số đánh giá thì trang tự cuộn xuống khối đánh giá.</p>';

	$kq = get_transient( 'smlife_dgsp_do_kq' );
	if ( isset( $_GET['do'] ) && is_array( $kq ) ) {
		delete_transient( 'smlife_dgsp_do_kq' );
		if ( $kq['ds'] ) {
			echo '<div class="notice notice-success" style="margin:0 0 14px"><p>Tìm thấy khoá khớp với số <b>' . esc_html( $kq['so'] ) . '</b>: ';
			foreach ( $kq['ds'] as $k ) { echo '<code>' . esc_html( $k ) . '</code> '; }
			echo '<br />Chép khoá bắt đầu bằng dấu gạch dưới và nhìn giống của plugin đánh giá vào ô bên dưới, lưu lại, rồi bấm Chép sang.</p></div>';
		} else {
			echo '<div class="notice notice-warning" style="margin:0 0 14px"><p>Không có khoá nào của sản phẩm <b>' . esc_html( $kq['pid'] ) . '</b> mang giá trị <b>' . esc_html( $kq['so'] ) . '</b>. Kiểm tra lại mã sản phẩm và con số đang hiện ngoài trang.</p></div>';
		}
	}

	echo '<h4 style="margin:0 0 6px">Dò khoá của plugin cũ</h4>';
	echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '" style="display:flex;gap:8px;align-items:flex-end;flex-wrap:wrap;margin:0 0 20px">';
	echo '<input type="hidden" name="action" value="smlife_dgsp_do" />';
	wp_nonce_field( 'smlife_dgsp_do', 'smlife_dgsp_do_nonce' );
	echo '<label>Mã sản phẩm<br /><input type="number" name="pid" class="small-text" /></label>';
	echo '<label>Số đã bán đang hiện ngoài trang<br /><input type="number" name="so" class="small-text" /></label>';
	echo '<button type="submit" class="button">Dò</button>';
	echo '<span class="description" style="flex:1 1 100%;margin-top:4px">Mở một sản phẩm ngoài trang, nhìn số đã bán rồi điền vào đây. Công cụ đọc toàn bộ thuộc tính của đúng sản phẩm đó và chỉ ra khoá nào đang chứa con số ấy.</span>';
	echo '</form>';

	$nonce2 = wp_create_nonce( 'smlife_dgsp_db' );
	echo '<h4 style="margin:0 0 6px">Chép từ plugin cũ sang</h4>';
	echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '" style="margin:0 0 10px">';
	echo '<input type="hidden" name="action" value="smlife_dgsp_save" />';
	wp_nonce_field( 'smlife_dgsp_save', 'smlife_dgsp_nonce' );
	foreach ( array( 'enabled', 'so_dau', 'so_toi_da', 'loc', 'anh_dai', 'tra_loi' ) as $k ) {
		echo '<input type="hidden" name="dg[' . esc_attr( $k ) . ']" value="' . esc_attr( $o[ $k ] ) . '" />';
	}
	echo '<input type="hidden" name="dg[nhan_mua]" value="' . esc_attr( $o['nhan_mua'] ) . '" />';
	echo '<input type="hidden" name="dg[ten_shop]" value="' . esc_attr( $o['ten_shop'] ) . '" />';
	echo '<input type="hidden" name="dg[db_hien]" value="' . esc_attr( $o['db_hien'] ) . '" />';
	echo '<input type="hidden" name="dg[db_nhan]" value="' . esc_attr( $o['db_nhan'] ) . '" />';
	echo '<input type="hidden" name="dg[db_the]" value="' . esc_attr( $o['db_the'] ) . '" />';
	echo '<input type="hidden" name="dg[db_vitri]" value="' . esc_attr( $o['db_vitri'] ) . '" />';
	echo '<input type="text" name="dg[db_khoa]" value="' . esc_attr( $o['db_khoa'] ) . '" class="regular-text" placeholder="_khoa_cua_plugin_cu" /> ';
	echo '<button type="submit" class="button">Lưu khoá</button>';
	echo '</form>';
	echo '<p><button type="button" class="button" id="smdg-chep"' . ( '' === trim( (string) $o['db_khoa'] ) ? ' disabled' : '' ) . '>Chép sang</button> <span id="smdg-chep-tt" style="margin-left:8px;font-weight:600"></span></p>';

	echo '<h4 style="margin:18px 0 6px">Hoặc rải số ngẫu nhiên</h4>';
	echo '<div style="display:flex;gap:10px;align-items:flex-end;flex-wrap:wrap">';
	echo '<label>Thấp nhất<br /><input type="number" id="smdg-min" value="30" class="small-text" /></label>';
	echo '<label>Cao nhất<br /><input type="number" id="smdg-max" value="300" class="small-text" /></label>';
	echo '<label>Phạm vi<br />' . wp_dropdown_categories( array( 'taxonomy' => 'product_cat', 'name' => 'smdg-cat', 'id' => 'smdg-cat', 'show_option_none' => '— Toàn bộ sản phẩm —', 'option_none_value' => 0, 'hierarchical' => true, 'hide_empty' => false, 'orderby' => 'name', 'echo' => false ) ) . '</label>';
	echo '<label><input type="checkbox" id="smdg-de" /> Ghi đè cả sản phẩm đã có số</label>';
	echo '<button type="button" class="button button-primary" id="smdg-sinh">Rải số</button>';
	echo '<span id="smdg-sinh-tt" style="font-weight:600"></span>';
	echo '</div>';
	echo '<p class="description" style="margin-top:8px">Không tích ô ghi đè thì chỉ điền cho sản phẩm chưa có số, những sản phẩm đã chép từ plugin cũ hoặc đã đặt tay sẽ được giữ nguyên.</p>';

	$js2  = '(function(){function chay(viec,nut,tt,them){var b=document.getElementById(nut);if(!b)return;';
	$js2 .= 'b.addEventListener("click",function(){b.disabled=true;var off=0,S={doc:0,ghi:0};var t=document.getElementById(tt);';
	$js2 .= 'function lo(){var f=new FormData();f.append("action","smlife_dgsp_db");f.append("_n","' . $nonce2 . '");f.append("viec",viec);f.append("off",off);';
	$js2 .= 'if(them)them(f);';
	$js2 .= 'fetch(ajaxurl,{method:"POST",credentials:"same-origin",body:f}).then(function(r){return r.json();}).then(function(j){';
	$js2 .= 'if(!j||!j.success){t.textContent="Lỗi, dừng lại.";b.disabled=false;return;}';
	$js2 .= 'S.doc+=j.data.doc;S.ghi+=j.data.ghi;off+=300;t.textContent="Đã đọc "+S.doc+", ghi "+S.ghi;';
	$js2 .= 'if(j.data.doc>0){lo();}else{t.textContent="Xong. Đọc "+S.doc+" sản phẩm, ghi "+S.ghi+" số.";b.disabled=false;}';
	$js2 .= '}).catch(function(){t.textContent="Lỗi mạng.";b.disabled=false;});}lo();});}';
	$js2 .= 'chay("chep","smdg-chep","smdg-chep-tt",null);';
	$js2 .= 'chay("sinh","smdg-sinh","smdg-sinh-tt",function(f){';
	$js2 .= 'f.append("min",document.getElementById("smdg-min").value);';
	$js2 .= 'f.append("max",document.getElementById("smdg-max").value);';
	$js2 .= 'f.append("cat",document.getElementById("smdg-cat").value);';
	$js2 .= 'if(document.getElementById("smdg-de").checked)f.append("de","1");});})();';
	echo '<h4 style="margin:18px 0 6px">Tính lại điểm trung bình</h4>';
	echo '<p style="margin:0 0 8px">Chạy sau khi tắt plugin cũ. WooCommerce lưu sẵn điểm trung bình và số đánh giá vào từng sản phẩm; nút này bắt nó tính lại từ comment thật, phòng trường hợp plugin cũ từng tự tay ghi đè hai con số đó.</p>';
	echo '<p><button type="button" class="button" id="smdg-tinh">Tính lại toàn bộ</button> <span id="smdg-tinh-tt" style="margin-left:8px;font-weight:600"></span></p>';
	$js2 .= '(function(){var b=document.getElementById("smdg-tinh");if(!b)return;b.addEventListener("click",function(){b.disabled=true;var off=0,S={doc:0,ghi:0};var t=document.getElementById("smdg-tinh-tt");';
	$js2 .= 'function lo(){var f=new FormData();f.append("action","smlife_dgsp_tinh");f.append("_n","' . $nonce2 . '");f.append("off",off);';
	$js2 .= 'fetch(ajaxurl,{method:"POST",credentials:"same-origin",body:f}).then(function(r){return r.json();}).then(function(j){';
	$js2 .= 'if(!j||!j.success){t.textContent="Lỗi, dừng lại.";b.disabled=false;return;}';
	$js2 .= 'S.doc+=j.data.doc;S.ghi+=j.data.ghi;off+=100;t.textContent="Đã xét "+S.doc+", sửa "+S.ghi;';
	$js2 .= 'if(j.data.doc>0){lo();}else{t.textContent="Xong. Xét "+S.doc+" sản phẩm, sửa "+S.ghi+" điểm.";b.disabled=false;}';
	$js2 .= '}).catch(function(){t.textContent="Lỗi mạng.";b.disabled=false;});}lo();});})();';
	echo '<script>' . $js2 . '</script>'; // phpcs:ignore
	echo '</div></div>';

	echo '</div>';
}

add_action( 'wp_ajax_smlife_dgsp_lo', function () {
	if ( ! current_user_can( 'manage_options' ) ) { wp_send_json_error(); }
	check_ajax_referer( 'smlife_dgsp_lo', '_n' );
	$off = isset( $_POST['off'] ) ? max( 0, (int) $_POST['off'] ) : 0;
	wp_send_json_success( smlife_dgsp_lo( $off, 200 ) );
} );

add_action( 'admin_post_smlife_dgsp_save', function () {
	if ( ! current_user_can( 'manage_options' ) ) { wp_die( 'Không đủ quyền.' ); }
	check_admin_referer( 'smlife_dgsp_save', 'smlife_dgsp_nonce' );
	$in = isset( $_POST['dg'] ) && is_array( $_POST['dg'] ) ? wp_unslash( $_POST['dg'] ) : array();
	$d  = smlife_dgsp_defaults();
	update_option( 'smlife_dgsp', array(
		'enabled'   => empty( $in['enabled'] ) ? 0 : 1,
		'so_dau'    => max( 1, min( 30, isset( $in['so_dau'] ) ? (int) $in['so_dau'] : 5 ) ),
		'so_toi_da' => max( 5, min( 200, isset( $in['so_toi_da'] ) ? (int) $in['so_toi_da'] : 20 ) ),
		'loc'       => empty( $in['loc'] ) ? 0 : 1,
		'anh_dai'   => empty( $in['anh_dai'] ) ? 0 : 1,
		'tra_loi'   => empty( $in['tra_loi'] ) ? 0 : 1,
		'nhan_mua'  => isset( $in['nhan_mua'] ) ? sanitize_text_field( $in['nhan_mua'] ) : $d['nhan_mua'],
		'ten_shop'  => isset( $in['ten_shop'] ) ? sanitize_text_field( $in['ten_shop'] ) : $d['ten_shop'],
		'db_hien'   => empty( $in['db_hien'] ) ? 0 : 1,
		'db_nhan'   => isset( $in['db_nhan'] ) ? sanitize_text_field( $in['db_nhan'] ) : $d['db_nhan'],
		'db_khoa'   => isset( $in['db_khoa'] ) ? preg_replace( '/[^A-Za-z0-9_\-]/', '', (string) $in['db_khoa'] ) : '',
		'db_the'    => empty( $in['db_the'] ) ? 0 : 1,
		'db_vitri'  => ( isset( $in['db_vitri'] ) && in_array( $in['db_vitri'], array( 'ten', 'gia_truoc', 'gia_sau' ), true ) ) ? $in['db_vitri'] : 'ten',
	), true );
	if ( function_exists( 'rocket_clean_domain' ) ) { smlife_xoa_cache(); }
	wp_safe_redirect( admin_url( 'admin.php?page=smlife-dg-san-pham&updated=1' ) );
	exit;
} );
