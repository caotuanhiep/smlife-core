<?php
/**
 * SMLIFE Core - Module: San pham lien quan ngau nhien
 *
 * Thay cho khoi Related Products mac dinh cua WooCommerce. Khac biet chinh:
 *
 *   - Chon duoc so luong san pham (Related goc cua Flatsome khong cho sua).
 *   - Moc lay tu danh muc, tu the tag, hoac ca hai.
 *   - Danh muc con it hang thi tu noi rong len danh muc cha cho du so luong.
 *   - Loc bo hang het, tranh dan khach toi san pham khong mua duoc.
 *   - Moi lan trang duoc dung lai la boc mot bo moi.
 *
 * VE TOC DO VA BO NHO DEM:
 * Cau lenh dung ORDER BY RAND nen moi lan chay la mot ket qua khac. Nhung
 * website dang bat WP Rocket, trang san pham duoc luu thanh file HTML, nen
 * cau lenh nay chi chay dung mot lan luc tao cache roi thoi. Dat han cache
 * 24 gio la moi ngay khach thay mot bo khac, khong ton them gi.
 * Muon doi bo ngay lap tuc thi xoa cache.
 *
 * Dat ra trang bang shortcode [smlife_sp_lien_quan], thay cho phan tu
 * Product Related trong Product Layout.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/* ===== 1. Cai dat ===== */

function smlife_lq_defaults() {
	return array(
		'enabled'  => 1,
		'tieu_de'  => 'Có thể bạn thích...',
		'so_sp'    => 12,
		'cot'      => 4,
		'nguon'    => 'cat',
		'mo_rong'  => 1,
		'con_hang' => 1,
		'nho_gio'  => 12,
		'slider'   => 1,
		'cot_mb'   => 2,
		'nut_tx'   => 'Xem thêm',
		'dm_hien'  => 1,
		'dm_tx'    => 'Xem tất cả {ten}',
	);
}

function smlife_lq_opt() {
	$o = get_option( 'smlife_lq', array() );
	return is_array( $o ) ? array_merge( smlife_lq_defaults(), $o ) : smlife_lq_defaults();
}

/* Danh muc cua san pham, bo danh muc "Uncategorized" cho do loang */
function smlife_lq_terms( $pid, $tax ) {
	$ids = wp_get_post_terms( $pid, $tax, array( 'fields' => 'ids' ) );
	return is_wp_error( $ids ) ? array() : array_map( 'intval', $ids );
}

/* Danh muc cha cua mot nhom danh muc */
function smlife_lq_cha( $term_ids, $tax = 'product_cat' ) {
	$out = array();
	foreach ( $term_ids as $id ) {
		$t = get_term( $id, $tax );
		if ( $t && ! is_wp_error( $t ) && $t->parent ) {
			$out[] = (int) $t->parent;
		}
	}
	return array_values( array_unique( $out ) );
}

/* Do sau cua mot danh muc: dem xem phai leo bao nhieu bac moi toi goc */
function smlife_lq_sau( $term_id, $tax = 'product_cat' ) {
	$n = 0;
	$t = get_term( $term_id, $tax );
	while ( $t && ! is_wp_error( $t ) && $t->parent && $n < 10 ) {
		$n++;
		$t = get_term( $t->parent, $tax );
	}
	return $n;
}

/* Thang bac de leo dan khi thieu hang.
 *
 * Bac 0: dung cac danh muc SAU NHAT ma san pham duoc gan, va chi lay san pham
 *        gan truc tiep vao do. Dang xem mot cai giuong ngu bet thi truoc het
 *        chi lay giuong ngu bet khac, khong vo dua ca nam giuong ngu.
 * Bac 1: van danh muc do nhung tinh ca danh muc con, phong truong hop danh muc
 *        sau nhat van con cap con ben duoi.
 * Bac 2 tro di: leo len danh muc cha, roi cha cua cha, moi bac deu tinh ca con.
 *
 * Chi leo len khi bac truoc chua gom du so luong can.
 */
function smlife_lq_thang( $pid ) {
	$ids = smlife_lq_terms( $pid, 'product_cat' );
	if ( ! $ids ) {
		return array();
	}
	$sau = array();
	$max = -1;
	foreach ( $ids as $id ) {
		$d = smlife_lq_sau( $id );
		if ( $d > $max ) {
			$max = $d;
			$sau = array( $id );
		} elseif ( $d === $max ) {
			$sau[] = $id;
		}
	}
	$thang = array(
		array( 'terms' => $sau, 'con' => false ),
		array( 'terms' => $sau, 'con' => true ),
	);
	$hien = $sau;
	for ( $i = 0; $i < 4; $i++ ) {
		$cha = smlife_lq_cha( $hien );
		if ( ! $cha ) {
			break;
		}
		$thang[] = array( 'terms' => $cha, 'con' => true );
		$hien    = $cha;
	}
	return $thang;
}

/* Mot luot truy van, tra ve mang ID */
function smlife_lq_query( $args ) {
	$ids = get_posts( $args );
	return is_array( $ids ) ? array_map( 'intval', $ids ) : array();
}

/* Tim san pham lien quan theo thang bac, boc ngau nhien trong tung bac. */
function smlife_lq_tim( $pid, $so, $o ) {
	$goc = array(
		'post_type'           => 'product',
		'post_status'         => 'publish',
		'orderby'             => 'rand',
		'fields'              => 'ids',
		'ignore_sticky_posts' => true,
		'no_found_rows'       => true,
	);
	if ( ! empty( $o['con_hang'] ) ) {
		$goc['meta_query'] = array( // phpcs:ignore
			array( 'key' => '_stock_status', 'value' => 'instock' ),
		);
	}

	$ids = array();

	/* Phan theo danh muc, leo tu bac sau nhat */
	if ( 'tag' !== $o['nguon'] ) {
		foreach ( smlife_lq_thang( $pid ) as $i => $bac ) {
			if ( count( $ids ) >= $so ) {
				break;
			}
			/* Tat noi rong thi chi dung dung bac dau tien */
			if ( $i > 0 && empty( $o['mo_rong'] ) ) {
				break;
			}
			$args = $goc;
			$args['posts_per_page'] = $so - count( $ids );
			$args['post__not_in']   = array_merge( array( (int) $pid ), $ids );
			$args['tax_query']      = array( // phpcs:ignore
				array(
					'taxonomy'         => 'product_cat',
					'field'            => 'term_id',
					'terms'            => $bac['terms'],
					'include_children' => (bool) $bac['con'],
				),
			);
			$ids = array_merge( $ids, smlife_lq_query( $args ) );
		}
	}

	/* Van thieu thi vet them theo tag */
	if ( 'cat' !== $o['nguon'] && count( $ids ) < $so ) {
		$tag = smlife_lq_terms( $pid, 'product_tag' );
		if ( $tag ) {
			$args = $goc;
			$args['posts_per_page'] = $so - count( $ids );
			$args['post__not_in']   = array_merge( array( (int) $pid ), $ids );
			$args['tax_query']      = array( // phpcs:ignore
				array( 'taxonomy' => 'product_tag', 'field' => 'term_id', 'terms' => $tag ),
			);
			$ids = array_merge( $ids, smlife_lq_query( $args ) );
		}
	}

	return array_values( array_unique( $ids ) );
}

/* Danh muc sau nhat ma san pham duoc gan - dung cho dong "Xem tat ca" cuoi khoi */
function smlife_lq_dm_term( $pid ) {
	$ids = smlife_lq_terms( $pid, 'product_cat' );
	if ( ! $ids ) {
		return null;
	}
	$best = 0;
	$max  = -1;
	foreach ( $ids as $id ) {
		$d = smlife_lq_sau( $id );
		if ( $d > $max ) {
			$max  = $d;
			$best = $id;
		}
	}
	$t = get_term( $best, 'product_cat' );
	return ( $t && ! is_wp_error( $t ) ) ? $t : null;
}

/* Dem so san pham trong danh muc, tinh ca danh muc con. Mot cau COUNT duy nhat. */
function smlife_lq_dem( $term_id ) {
	$a = array(
		'post_type'           => 'product',
		'post_status'         => 'publish',
		'posts_per_page'      => 1,
		'fields'              => 'ids',
		'no_found_rows'       => false,
		'ignore_sticky_posts' => true,
		'tax_query'           => array( // phpcs:ignore
			array(
				'taxonomy'         => 'product_cat',
				'field'            => 'term_id',
				'terms'            => array( (int) $term_id ),
				'include_children' => true,
			),
		),
	);
	/* Neu cua hang dang an hang het thi dem cho khop voi trang danh muc */
	if ( 'yes' === get_option( 'woocommerce_hide_out_of_stock_items' ) ) {
		$a['meta_query'] = array( // phpcs:ignore
			array( 'key' => '_stock_status', 'value' => 'instock' ),
		);
	}
	$q = new WP_Query( $a );
	return (int) $q->found_posts;
}

/* Bo nho tam cho bo san pham da boc.
 *
 * Cau lenh ORDER BY RAND khong dung duoc chi muc, moi lan chay la mot lan
 * sap xep lai toan bo hang khop. Trang da vao cache cua WP Rocket thi khach
 * khong chiu phi nay, NHUNG nguoi dang dang nhap thi khong bao gio duoc doc
 * cache, nen moi lan mo mot trang san pham la mot lan chay lai tu dau. Do la
 * ly do chinh khien chu website thay web cham hon han khach vang lai.
 *
 * Giai phap: nho ket qua trong mot khoang thoi gian. Van doi bo deu dan,
 * nhung khong phai boc lai o moi luot xem. Dat 0 gio la tat, boc lai moi lan.
 */
function smlife_lq_tim_nho( $pid, $so, $o ) {
	$gio = isset( $o['nho_gio'] ) ? (int) $o['nho_gio'] : 12;
	if ( $gio < 1 ) {
		return smlife_lq_tim( $pid, $so, $o );
	}
	/* 1.19.1: them so the he vao khoa. Luu cai dat thi tang so nay, khoa cu tu bo
	   va het han dan. Chay dung ca khi transient nam trong Redis. */
	$the  = (int) get_option( 'smlife_lq_the', 1 );
	$khoa = 'smlife_lq_' . $the . '_' . (int) $pid . '_' . (int) $so . '_' . substr( md5( (string) $o['nguon'] . (int) $o['mo_rong'] . (int) $o['con_hang'] ), 0, 8 );
	$cu   = get_transient( $khoa );
	if ( is_array( $cu ) && $cu ) {
		return $cu;
	}
	$ids = smlife_lq_tim( $pid, $so, $o );
	if ( $ids ) {
		set_transient( $khoa, $ids, $gio * HOUR_IN_SECONDS );
	}
	return $ids;
}

/* ===== 2. Giao dien: CSS va JS cho thanh truot ===== */

/* Mot bo CSS dung chung, in dung mot lan cho moi trang. */
function smlife_lq_css( $cot, $cot_mb ) {
	$pk  = number_format( $cot + 0.3, 2, '.', '' );
	$pkm = number_format( $cot_mb + 0.25, 2, '.', '' );
	$mau  = function_exists( 'smlife_mau' ) ? smlife_mau() : '#ee2468';
	$nhat = function_exists( 'smlife_mau_nhat' ) ? smlife_mau_nhat( 0.96 ) : '#fff5f8';
	$vien = function_exists( 'smlife_mau_rgba' ) ? smlife_mau_rgba( 0.35 ) : 'rgba(238,36,104,.35)';
	$dam  = function_exists( 'smlife_mau_dam' ) ? smlife_mau_dam() : '#c81e57';
	$bong = function_exists( 'smlife_mau_rgba' ) ? smlife_mau_rgba( 0.26 ) : 'rgba(238,36,104,.26)';
	$bon2 = function_exists( 'smlife_mau_rgba' ) ? smlife_mau_rgba( 0.34 ) : 'rgba(238,36,104,.34)';

	$c  = '.smlife-lq-w{position:relative}';
	/* Dang thanh truot: hang san pham khong xuong dong, keo ngang duoc */
	$c .= '.smlife-lq .products.row{flex-wrap:nowrap !important;overflow-x:auto;overflow-y:hidden;scroll-snap-type:x proximity;scroll-behavior:smooth;-webkit-overflow-scrolling:touch;scrollbar-width:none;padding-bottom:6px}';
	$c .= '.smlife-lq .products.row::-webkit-scrollbar{display:none}';
	$c .= '.smlife-lq .products.row>.col{flex:0 0 calc(100%/' . $pk . ') !important;max-width:calc(100%/' . $pk . ') !important;scroll-snap-align:start}';
	/* Bam Xem them: tra ve dang luoi day du */
	$c .= '.smlife-lq.mo .products.row{flex-wrap:wrap !important;overflow:visible;scroll-snap-type:none;padding-bottom:0}';
	$c .= '.smlife-lq.mo .products.row>.col{flex:0 0 ' . number_format( 100 / $cot, 4, '.', '' ) . '% !important;max-width:' . number_format( 100 / $cot, 4, '.', '' ) . '% !important}';
	$c .= '.smlife-lq.mo .smlife-lq-ar{display:none}';
	/* Hai mui nhon hai ben, chi hien tren may tinh */
	$c .= '.smlife-lq-ar{position:absolute;top:34%;transform:translateY(-50%);z-index:6;width:38px;height:38px;border-radius:50%;border:0;background:#fff;box-shadow:0 2px 10px rgba(0,0,0,.16);cursor:pointer;padding:0;display:flex;align-items:center;justify-content:center;transition:opacity .2s}';
	$c .= '.smlife-lq-pr{left:-7px}.smlife-lq-ne{right:-7px}';
	$c .= '.smlife-lq-ar i{display:block;width:8px;height:8px;border-right:2px solid #333;border-bottom:2px solid #333}';
	$c .= '.smlife-lq-pr i{transform:rotate(135deg);margin-left:-3px}.smlife-lq-ne i{transform:rotate(-45deg);margin-right:-3px}';
	$c .= '.smlife-lq-ar.tat{opacity:0;pointer-events:none}';
	/* Nut Xem them */
	$c .= '.smlife-lq-b{text-align:center;margin:4px 0 0}';
	$c .= '.smlife-lq-xem{display:inline-flex;align-items:center;gap:8px;border:1px solid ' . $vien . ';background:' . $nhat . ';color:' . $mau . ';font-weight:600;font-size:14px;line-height:1;padding:12px 28px;border-radius:999px;cursor:pointer;transition:background .18s,color .18s}';
	$c .= '.smlife-lq-xem:hover{background:' . $mau . ';color:#fff;border-color:' . $mau . '}';
	$c .= '.smlife-lq-xem i{display:block;width:7px;height:7px;border-right:2px solid currentColor;border-bottom:2px solid currentColor;transform:rotate(45deg);margin-top:-3px}';
	/* Dong "Xem tat ca danh muc" o day khoi, chi hien khi da xo ra luoi */
	$c .= '.smlife-lq-dm{margin:18px 0 2px;text-align:center}';
	$c .= '.smlife-lq.sl .smlife-lq-dm{display:none}.smlife-lq.sl.mo .smlife-lq-dm{display:block;animation:smlqhien .28s ease}';
	$c .= '@keyframes smlqhien{from{opacity:0;transform:translateY(-7px)}to{opacity:1;transform:none}}';
	$c .= '.smlife-lq-dm a{display:inline-flex;align-items:center;justify-content:center;gap:11px;flex-wrap:wrap;max-width:100%;padding:14px 28px;border-radius:999px;background:' . $mau . ';border:0;color:#fff !important;font-size:15px;font-weight:600;line-height:1.35;text-decoration:none !important;box-shadow:0 6px 18px ' . $bong . ';transition:background .18s,box-shadow .18s,transform .18s}';
	$c .= '.smlife-lq-dm a:hover{background:' . $dam . ';box-shadow:0 9px 24px ' . $bon2 . ';transform:translateY(-1px)}';
	$c .= '.smlife-lq-dm b{font-weight:700}';
	$c .= '.smlife-lq-dm span.dem{font-size:12.5px;font-weight:600;background:rgba(255,255,255,.22);padding:5px 11px;border-radius:999px;white-space:nowrap}';
	$c .= '.smlife-lq-dm i{display:inline-block;width:7px;height:7px;border-right:2px solid #fff;border-bottom:2px solid #fff;transform:rotate(-45deg);margin-left:-3px;transition:margin .18s}';
	$c .= '.smlife-lq-dm a:hover i{margin-left:2px}';
	/* May tinh bang: 3 cot, bam theo medium-columns-3 cua Flatsome */
	$c .= '@media (max-width:849px){.smlife-lq-ar{display:none}';
	$c .= '.smlife-lq .products.row>.col{flex:0 0 calc(100%/3.3) !important;max-width:calc(100%/3.3) !important}';
	$c .= '.smlife-lq.mo .products.row>.col{flex:0 0 33.3333% !important;max-width:33.3333% !important}}';
	/* Dien thoai: so cot dat trong cai dat, mac dinh 2, quet tay la du */
	$c .= '@media (max-width:549px){';
	$c .= '.smlife-lq .products.row>.col{flex:0 0 calc(100%/' . $pkm . ') !important;max-width:calc(100%/' . $pkm . ') !important}';
	$c .= '.smlife-lq.mo .products.row>.col{flex:0 0 ' . number_format( 100 / $cot_mb, 4, '.', '' ) . '% !important;max-width:' . number_format( 100 / $cot_mb, 4, '.', '' ) . '% !important}';
	$c .= '.smlife-lq-xem{font-size:13.5px;padding:11px 24px}';
	$c .= '.smlife-lq-dm a{font-size:14px;padding:12px 20px;gap:8px}.smlife-lq-dm span.dem{font-size:11.5px;padding:4px 9px}}';
	return $c;
}

function smlife_lq_js() {
	$j  = '(function(){';
	$j .= 'function up(w){var r=w.querySelector(".products");if(!r)return;';
	$j .= 'var p=w.querySelector(".smlife-lq-pr"),n=w.querySelector(".smlife-lq-ne");if(!p||!n)return;';
	$j .= 'p.classList.toggle("tat",r.scrollLeft<=4);';
	$j .= 'n.classList.toggle("tat",r.scrollLeft+r.clientWidth>=r.scrollWidth-4);}';
	$j .= 'function all(){var l=document.querySelectorAll(".smlife-lq-w");for(var i=0;i<l.length;i++)up(l[i]);}';
	$j .= 'document.addEventListener("click",function(e){var t=e.target;if(!t||!t.closest)return;';
	$j .= 'var x=t.closest(".smlife-lq-xem");';
	$j .= 'if(x){var k=x.closest(".smlife-lq");if(k)k.classList.add("mo");var b=x.closest(".smlife-lq-b");if(b)b.style.display="none";';
	$j .= 'if(window.jQuery)jQuery(window).trigger("resize");return;}';
	$j .= 'var a=t.closest(".smlife-lq-ar");if(!a)return;e.preventDefault();';
	$j .= 'var w=a.closest(".smlife-lq-w");var r=w?w.querySelector(".products"):null;if(!r)return;';
	$j .= 'var c=r.querySelector(".col");var s=c?c.getBoundingClientRect().width:240;';
	$j .= 'var b=Math.max(1,Math.round(r.clientWidth/s)-1);';
	$j .= 'r.scrollBy({left:(a.classList.contains("smlife-lq-pr")?-1:1)*s*b,behavior:"smooth"});},false);';
	$j .= 'document.addEventListener("scroll",function(e){var r=e.target;';
	$j .= 'if(!r||!r.classList||!r.classList.contains("products"))return;';
	$j .= 'var w=r.closest?r.closest(".smlife-lq-w"):null;if(w)up(w);},true);';
	$j .= 'if(document.readyState!=="loading")all();';
	$j .= 'document.addEventListener("DOMContentLoaded",all);window.addEventListener("load",all);})();';
	return $j;
}

/* ===== 3. Shortcode ===== */

add_shortcode( 'smlife_sp_lien_quan', 'smlife_lq_sc' );
function smlife_lq_sc( $atts ) {
	if ( is_admin() && ! ( defined( 'DOING_AJAX' ) && DOING_AJAX ) ) { return ''; }
	$o = smlife_lq_opt();
	if ( empty( $o['enabled'] ) ) { return ''; }
	if ( ! function_exists( 'wc_get_product' ) ) { return ''; }

	$a = shortcode_atts( array( 'tieu_de' => '', 'so_sp' => 0, 'cot' => 0, 'slider' => '' ), $atts, 'smlife_sp_lien_quan' );

	$pid = get_the_ID();
	if ( ! $pid ) { return ''; }

	$so  = (int) $a['so_sp'] > 0 ? (int) $a['so_sp'] : (int) $o['so_sp'];
	$so  = max( 2, min( 40, $so ) );
	$cot = (int) $a['cot'] > 0 ? (int) $a['cot'] : (int) $o['cot'];
	$cot = max( 2, min( 6, $cot ) );
	$cmb = max( 1, min( 3, (int) $o['cot_mb'] ) );
	$sl  = '' !== $a['slider'] ? ( '1' === (string) $a['slider'] || 'yes' === $a['slider'] ) : ! empty( $o['slider'] );

	$ids = smlife_lq_tim_nho( $pid, $so, $o );
	if ( ! $ids ) { return ''; }

	$tieu_de = '' !== trim( (string) $a['tieu_de'] ) ? $a['tieu_de'] : $o['tieu_de'];

	/* Dung shortcode [products] chuan cua Woo de the san pham giong het cac
	   khoi khac tren web, khong phai dung lai giao dien rieng. */
	$sp = do_shortcode( '[products ids="' . implode( ',', $ids ) . '" columns="' . $cot . '" limit="' . count( $ids ) . '" orderby="post__in" cache="false"]' ); /* 1.24.0: cache=false - moi bo ngau nhien khong tao them transient wc_product_loop_* trong Redis */

	static $in_rui = false;

	$h = '';
	if ( $sl && ! $in_rui ) {
		$in_rui = true;
		$h     .= '<style>' . smlife_lq_css( $cot, $cmb ) . '</style>';
		$h     .= '<script>' . smlife_lq_js() . '</script>';
	}

	$h .= '<div class="smlife-lq' . ( $sl ? ' sl' : '' ) . '">';
	if ( '' !== trim( (string) $tieu_de ) ) {
		$h .= '<h2 class="smlife-lq-t">' . esc_html( $tieu_de ) . '</h2>';
	}

	if ( ! $sl ) {
		$h .= $sp . '</div>';
		return $h;
	}

	$h .= '<div class="smlife-lq-w">' . $sp;
	$h .= '<button type="button" class="smlife-lq-ar smlife-lq-pr tat" aria-label="Lùi lại"><i></i></button>';
	$h .= '<button type="button" class="smlife-lq-ar smlife-lq-ne" aria-label="Xem tiếp"><i></i></button>';
	$h .= '</div>';

	/* Chi can nut Xem them khi so san pham nhieu hon mot man hinh */
	$nut = trim( (string) $o['nut_tx'] );
	if ( '' !== $nut && count( $ids ) > $cot ) {
		$h .= '<div class="smlife-lq-b"><button type="button" class="smlife-lq-xem">' . esc_html( $nut ) . '<i></i></button></div>';
	}

	$h .= smlife_lq_dong_dm( $pid, $o );
	$h .= '</div>';
	return $h;
}

/* Dong cuoi khoi: Xem tat ca <ten danh muc> - <so> san pham */
function smlife_lq_dong_dm( $pid, $o ) {
	if ( empty( $o['dm_hien'] ) ) { return ''; }
	$t = smlife_lq_dm_term( $pid );
	if ( ! $t ) { return ''; }
	$url = get_term_link( $t );
	if ( is_wp_error( $url ) ) { return ''; }
	$so = smlife_lq_dem( $t->term_id );
	if ( $so < 2 ) { return ''; }

	$tx = trim( (string) $o['dm_tx'] );
	if ( '' === $tx ) { $tx = 'Xem tất cả {ten}'; }
	$co_so = ( false !== strpos( $tx, '{so}' ) );
	/* esc_html chay truoc, the {ten} {so} khong co ky tu dac biet nen con nguyen */
	$tx = str_replace(
		array( '{ten}', '{so}' ),
		array( '<b>' . esc_html( $t->name ) . '</b>', '<b>' . esc_html( number_format_i18n( $so ) ) . '</b>' ),
		esc_html( $tx )
	);

	$h  = '<div class="smlife-lq-dm"><a href="' . esc_url( $url ) . '">';
	$h .= '<span>' . $tx . '</span>';
	/* Chua tu dat {so} trong cau chu thi gan them the dem o ve sau */
	if ( ! $co_so ) {
		$h .= '<span class="dem">' . esc_html( number_format_i18n( $so ) ) . ' sản phẩm</span>';
	}
	$h .= '<i></i></a></div>';
	return $h;
}

/* ===== 4. Trang cai dat ===== */

add_action( 'admin_menu', function () {
	add_submenu_page( 'smlife-core', 'Sản phẩm liên quan', 'Sản phẩm liên quan', 'manage_options', 'smlife-lien-quan', 'smlife_lq_page' );
}, 21 );

function smlife_lq_page() {
	if ( ! current_user_can( 'manage_options' ) ) { return; }
	$o = smlife_lq_opt();
	echo '<div class="wrap smlife-wrap">';
	echo '<div class="smc-hd"><h1>Sản phẩm liên quan ngẫu nhiên</h1>';
	echo '<p>Thay cho khối Related Products mặc định. Mở Product Layout, bỏ phần tử <b>Product Related</b> đi và đặt <code>[smlife_sp_lien_quan]</code> vào đúng chỗ đó.</p></div>';
	if ( isset( $_GET['updated'] ) ) { echo '<div class="notice notice-success is-dismissible"><p>Đã lưu.</p></div>'; }

	echo '<div class="smlife-card"><form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '">';
	echo '<input type="hidden" name="action" value="smlife_lq_save" />';
	wp_nonce_field( 'smlife_lq_save', 'smlife_lq_nonce' );
	echo '<table class="form-table">';
	echo '<tr><th>Bật module</th><td><label><input type="checkbox" name="lq[enabled]" value="1" ' . checked( 1, $o['enabled'], false ) . ' /> Hiện khối sản phẩm liên quan ở nơi có đặt shortcode</label></td></tr>';
	echo '<tr><th>Tiêu đề</th><td><input type="text" name="lq[tieu_de]" value="' . esc_attr( $o['tieu_de'] ) . '" class="regular-text" /> <span class="description">Để trống là bỏ hẳn dòng tiêu đề.</span></td></tr>';
	echo '<tr><th>Số sản phẩm</th><td><input type="number" min="2" max="40" name="lq[so_sp]" value="' . esc_attr( $o['so_sp'] ) . '" class="small-text" /> <span class="description">Related gốc của Flatsome khoá cứng ở 4. Ở đây muốn 20 cũng được.</span></td></tr>';
	echo '<tr><th>Số cột</th><td><input type="number" min="2" max="6" name="lq[cot]" value="' . esc_attr( $o['cot'] ) . '" class="small-text" /></td></tr>';
	echo '<tr><th>Nguồn lấy</th><td><select name="lq[nguon]">';
	foreach ( array( 'cat' => 'Cùng danh mục, lấy từ danh mục nhỏ nhất trước', 'both' => 'Cùng danh mục trước, thiếu thì vét thêm theo tag', 'tag' => 'Chỉ cùng tag' ) as $k => $lb ) {
		echo '<option value="' . esc_attr( $k ) . '" ' . selected( $o['nguon'], $k, false ) . '>' . esc_html( $lb ) . '</option>';
	}
	echo '</select> <span class="description">Luôn ưu tiên danh mục nhỏ nhất mà sản phẩm được gắn. Đang xem giường ngủ bệt thì danh sách là giường ngủ bệt khác, chỉ khi không đủ hàng mới nới dần lên danh mục lớn hơn.</span></td></tr>';
	echo '<tr><th>Nới rộng khi thiếu</th><td><label><input type="checkbox" name="lq[mo_rong]" value="1" ' . checked( 1, $o['mo_rong'], false ) . ' /> Danh mục nhỏ không đủ hàng thì leo dần lên danh mục lớn hơn cho đủ số lượng</label></td></tr>';
	echo '<tr><th>Nhớ bộ sản phẩm trong</th><td><input type="number" min="0" max="168" name="lq[nho_gio]" value="' . esc_attr( $o['nho_gio'] ) . '" class="small-text" /> giờ <span class="description"><b>Ảnh hưởng lớn tới tốc độ.</b> Câu lệnh bốc ngẫu nhiên không dùng được chỉ mục nên khá nặng. Nhớ lại kết quả trong vài giờ thì vẫn đổi bộ đều đặn mà không phải bốc lại ở mỗi lượt xem. Quan trọng nhất là lúc chính mình đang đăng nhập, vì khi đó WP Rocket không trả cache. Để 0 là tắt, bốc lại mọi lần.</span></td></tr>';
	echo '<tr><th>Chỉ lấy hàng còn</th><td><label><input type="checkbox" name="lq[con_hang]" value="1" ' . checked( 1, $o['con_hang'], false ) . ' /> Bỏ qua sản phẩm hết hàng</label></td></tr>';
	echo '<tr><th>Dạng thanh trượt</th><td><label><input type="checkbox" name="lq[slider]" value="1" ' . checked( 1, $o['slider'], false ) . ' /> Xếp một hàng kéo ngang, có hai mũi nhọn hai bên. Bỏ chọn là xếp lưới nhiều dòng như cũ.</label></td></tr>';
	echo '<tr><th>Số cột trên điện thoại</th><td><input type="number" min="1" max="3" name="lq[cot_mb]" value="' . esc_attr( $o['cot_mb'] ) . '" class="small-text" /> <span class="description">Thường để 2.</span></td></tr>';
	echo '<tr><th>Dòng xem danh mục</th><td><label><input type="checkbox" name="lq[dm_hien]" value="1" ' . checked( 1, $o['dm_hien'], false ) . ' /> Cuối khối thêm một dòng dẫn sang danh mục nhỏ nhất của sản phẩm đang xem, có kèm số sản phẩm. Ở dạng thanh trượt thì dòng này chỉ hiện sau khi bấm nút mở rộng.</label></td></tr>';
	echo '<tr><th>Chữ dòng danh mục</th><td><input type="text" name="lq[dm_tx]" value="' . esc_attr( $o['dm_tx'] ) . '" class="regular-text" /> <span class="description">Dùng <code>{ten}</code> cho tên danh mục, <code>{so}</code> cho số sản phẩm. Mặc định: Xem tất cả {ten}</span></td></tr>';
	echo '<tr><th>Chữ trên nút mở rộng</th><td><input type="text" name="lq[nut_tx]" value="' . esc_attr( $o['nut_tx'] ) . '" class="regular-text" /> <span class="description">Bấm vào là thanh trượt xổ xuống thành lưới đầy đủ. Để trống là bỏ hẳn nút này.</span></td></tr>';
	echo '</table>';
	submit_button( 'Lưu' );
	echo '</form></div>';

	echo '<div class="smlife-card"><h2>Về tốc độ và bộ nhớ đệm</h2><div class="in">';
	echo '<p style="margin-top:0">Danh sách được bốc ngẫu nhiên mỗi lần trang được dựng lại. Website đang bật WP Rocket nên trang sản phẩm được lưu thành file HTML, câu lệnh ngẫu nhiên chỉ chạy đúng một lần lúc tạo cache rồi thôi, khách vào sau đọc file có sẵn. Vì vậy tính năng này <b>không làm chậm trang</b>.</p>';
	echo '<p style="margin-bottom:0">Muốn mỗi ngày một bộ khác thì vào WP Rocket đặt thời hạn cache 24 giờ. Muốn đổi bộ ngay lập tức thì xoá cache.</p>';
	echo '</div></div></div>';
}

/* Doi cai dat thi bo nho tam cu khong con dung nua.
 * 1.19.1: co Redis thi transient khong nam trong bang options, cau DELETE cu
 * khop 0 dong. Nay tang so the he de moi khoa cu bi bo qua ngay lap tuc,
 * va van don bang options cho website khong dung Redis. */
function smlife_lq_xoa_nho() {
	update_option( 'smlife_lq_the', (int) get_option( 'smlife_lq_the', 1 ) + 1, true );
	if ( ! wp_using_ext_object_cache() ) {
		global $wpdb;
		$wpdb->query( "DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_smlife_lq\\_%' OR option_name LIKE '_transient_timeout_smlife_lq\\_%'" ); // phpcs:ignore
	}
}

add_action( 'admin_post_smlife_lq_save', function () {
	if ( ! current_user_can( 'manage_options' ) ) { wp_die( 'Không đủ quyền.' ); }
	check_admin_referer( 'smlife_lq_save', 'smlife_lq_nonce' );
	$in = isset( $_POST['lq'] ) && is_array( $_POST['lq'] ) ? wp_unslash( $_POST['lq'] ) : array();
	$d  = smlife_lq_defaults();
	update_option( 'smlife_lq', array(
		'enabled'  => empty( $in['enabled'] ) ? 0 : 1,
		'tieu_de'  => isset( $in['tieu_de'] ) ? sanitize_text_field( $in['tieu_de'] ) : $d['tieu_de'],
		'so_sp'    => max( 2, min( 40, isset( $in['so_sp'] ) ? (int) $in['so_sp'] : 12 ) ),
		'cot'      => max( 2, min( 6, isset( $in['cot'] ) ? (int) $in['cot'] : 4 ) ),
		'nguon'    => ( isset( $in['nguon'] ) && in_array( $in['nguon'], array( 'cat', 'tag', 'both' ), true ) ) ? $in['nguon'] : 'cat',
		'mo_rong'  => empty( $in['mo_rong'] ) ? 0 : 1,
		'con_hang' => empty( $in['con_hang'] ) ? 0 : 1,
		'nho_gio'  => max( 0, min( 168, isset( $in['nho_gio'] ) ? (int) $in['nho_gio'] : 12 ) ),
		'slider'   => empty( $in['slider'] ) ? 0 : 1,
		'cot_mb'   => max( 1, min( 3, isset( $in['cot_mb'] ) ? (int) $in['cot_mb'] : 2 ) ),
		'nut_tx'   => isset( $in['nut_tx'] ) ? sanitize_text_field( $in['nut_tx'] ) : $d['nut_tx'],
		'dm_hien'  => empty( $in['dm_hien'] ) ? 0 : 1,
		'dm_tx'    => isset( $in['dm_tx'] ) ? sanitize_text_field( $in['dm_tx'] ) : $d['dm_tx'],
	), false );
	if ( ! empty( $GLOBALS['smlife_co_doi'] ) ) { smlife_lq_xoa_nho(); }
	if ( function_exists( 'rocket_clean_domain' ) ) { smlife_xoa_cache(); }
	wp_safe_redirect( admin_url( 'admin.php?page=smlife-lien-quan&updated=1' ) );
	exit;
} );
