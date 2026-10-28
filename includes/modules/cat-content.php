<?php
/**
 * SMLIFE Core - Module: Dynamic Shortcode
 * =====================================================================
 * Mot layout san pham dung chung cho moi danh muc. 5 vung noi dung thay
 * doi theo danh muc duoc dat trong layout bang shortcode dong.
 *
 * Admin : SMLIFE Core > Dynamic Shortcode
 * Option: smlife_catc
 *
 * 5 shortcode dat trong block "Product Layout CHUAN":
 *   [smlife_tinh_trang]    1. Dong tinh trang duoi ten san pham (chu, khong phai block)
 *   [smlife_banner_sp]     2. Khoi duoi anh san pham
 *   [smlife_chi_co_tai]    3. Khoi CHI CO TAI SMLIFE, cot phai
 *   [smlife_vat_lieu]      4. Khoi mau van go / vat lieu
 *   [smlife_noidung_them]  5. Khoi noi dung bo sung cuoi trang
 *
 * Slot 2 den 5 tro toi mot UX Block cua Flatsome, noi dung van soan bang
 * UX Builder nhu binh thuong. Module chi lo phan danh muc nao dung block nao.
 *
 * Quy tac: san pham nam o nhieu danh muc thi danh muc cap nho nhat thang.
 * Slot nao danh muc con de trong thi tu lay cua danh muc cha.
 * Khong co gi thi shortcode tra ve rong, KHONG in ma shortcode tho.
 * =====================================================================
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }
if ( defined( 'SMLIFE_CATC_LOADED' ) ) { return; }
define( 'SMLIFE_CATC_LOADED', true );

/* =====================================================================
 * 1. DINH NGHIA 5 SLOT
 * ===================================================================== */

/* Slot 1 Tinh trang la mot dong chu nen quan ly rieng.
 * 4 slot con lai deu tro toi UX Block. */
function smlife_catc_slots() {
	return array(
		'banner' => array(
			'no'   => '2',
			'sc'   => 'smlife_banner_sp',
			'name' => 'Khối dưới ảnh sản phẩm',
			'desc' => 'Ví dụ khung vàng "Tùy chỉnh theo yêu cầu".',
			'mb'   => 0,
		),
		'chi_co_tai' => array(
			'no'   => '3',
			'sc'   => 'smlife_chi_co_tai',
			'name' => 'Khối CHỈ CÓ TẠI SMLIFE',
			'desc' => 'Cột phải, cạnh khung giá.',
			'mb'   => 1,
		),
		'vat_lieu' => array(
			'no'   => '4',
			'sc'   => 'smlife_vat_lieu',
			'name' => 'Khối màu ván gỗ / vật liệu',
			'desc' => 'Ví dụ "Click để xem màu ván gỗ thực tế".',
			'mb'   => 0,
		),
		'extra' => array(
			'no'   => '5',
			'sc'   => 'smlife_noidung_them',
			'name' => 'Nội dung bổ sung cuối trang',
			'desc' => 'Ví dụ "Hướng dẫn mua nội thất tại SMLIFE".',
			'mb'   => 0,
		),
	);
}

/* Cac mau cau Tinh trang. Moi cau mang san mau chu hop ly nen khong phai
 * chon mau cho tung danh muc nua, chi cau tu nhap moi can chon mau. */
/* Gia tri danh dau "slot nay dung noi dung tu soan" thay vi slug cua UX Block.
 * Dat hai dau gach duoi hai ben cho khong bao gio trung slug that. */
if ( ! defined( 'SMLIFE_CATC_HTML' ) ) {
	define( 'SMLIFE_CATC_HTML', '__tu-soan__' );
}

function smlife_catc_presets() {
	return array(
		'moi'      => array( 'Sản xuất mới. Giao sớm 1~3 ngày!',       '#d97706' ),
		'grab'     => array( 'Hàng có sẵn, giao Grab 3h nhận liền',    '#16a34a' ),
		'nhanh'    => array( 'Hàng có sẵn, giao nhanh 1-3 ngày',       '#16a34a' ),
		'hoatoc'   => array( 'Hàng có sẵn (cần HỎA TỐC gọi Hotline)',  '#dc2626' ),
		'tratruoc' => array( 'Hàng sẵn, thanh toán trước giảm 5%',     '#16a34a' ),
	);
}

/* Ban 1.1.0 va 1.1.1 dung key 'san' cho cau tra truoc giam 5%. */
function smlife_catc_fix_mode( $mode ) {
	return ( 'san' === $mode ) ? 'tratruoc' : $mode;
}

function smlife_catc_blank() {
	$b = array(
		'status_mode'  => 'off',
		'status_text'  => '',
		'status_color' => '#16a34a',
	);
	foreach ( smlife_catc_slots() as $k => $s ) {
		$b[ $k ]           = '';
		$b[ $k . '_html' ] = '';
	}
	return $b;
}

/* =====================================================================
 * 2. DU LIEU
 * ===================================================================== */

/* Xoa bo nho tam sau khi luu. */
function smlife_catc_flush() {
	$GLOBALS['smlife_catc_cache'] = null;
}

function smlife_catc_store() {
	/* Moi trang san pham goi ham nay khoang 20 lan (5 slot x chuoi danh muc).
	   Nho lai ket qua de chi doc option dung mot lan cho ca lan tai trang. */
	if ( isset( $GLOBALS['smlife_catc_cache'] ) && is_array( $GLOBALS['smlife_catc_cache'] ) ) {
		return $GLOBALS['smlife_catc_cache'];
	}
	$o = get_option( 'smlife_catc', array() );
	if ( ! is_array( $o ) ) {
		$o = array();
	}
	if ( ! isset( $o['cats'] ) || ! is_array( $o['cats'] ) ) {
		/* Du lieu ban 1.1.0 luu phang theo term_id, nang len cau truc moi. */
		$old       = $o;
		$o         = array( 'cats' => array(), 'hide_mb' => array() );
		foreach ( $old as $k => $v ) {
			if ( is_numeric( $k ) && is_array( $v ) ) {
				$o['cats'][ (int) $k ] = $v;
			}
		}
	}
	if ( ! isset( $o['hide_mb'] ) || ! is_array( $o['hide_mb'] ) ) {
		$o['hide_mb'] = array();
		foreach ( smlife_catc_slots() as $k => $s ) {
			$o['hide_mb'][ $k ] = (int) $s['mb'];
		}
	}
	if ( ! isset( $o['kethua'] ) ) {
		$o['kethua'] = 0;   /* mac dinh KHONG ke thua tu danh muc cha */
	}
	if ( ! isset( $o['tt_chung'] ) ) {
		$o['tt_chung'] = 1;          /* ep moi Tinh trang ve cung mot mau */
	}
	if ( ! isset( $o['tt_mau'] ) ) {
		$o['tt_mau'] = '#15803d';    /* xanh la dam */
	}
	$GLOBALS['smlife_catc_cache'] = $o;
	return $o;
}

function smlife_catc_get( $term_id ) {
	$st = smlife_catc_store();
	$t  = (int) $term_id;
	return isset( $st['cats'][ $t ] ) && is_array( $st['cats'][ $t ] )
		? array_merge( smlife_catc_blank(), $st['cats'][ $t ] )
		: smlife_catc_blank();
}

/* Mau dung chung cho dong Tinh trang. Tra ve '' neu de tung danh muc tu chon. */
function smlife_catc_tt_mau() {
	$st = smlife_catc_store();
	if ( empty( $st['tt_chung'] ) ) {
		return '';
	}
	$m = sanitize_hex_color( isset( $st['tt_mau'] ) ? $st['tt_mau'] : '' );
	return $m ? $m : '#15803d';
}

/* Co lan len danh muc cha hay khong. */
function smlife_catc_kethua() {
	$st = smlife_catc_store();
	return ! empty( $st['kethua'] );
}

function smlife_catc_hide_mb( $slot ) {
	$st = smlife_catc_store();
	return ! empty( $st['hide_mb'][ $slot ] );
}

/* Cau Tinh trang cuoi cung cua mot danh muc, kem mau chu. */
function smlife_catc_status( $c ) {
	$mode = smlife_catc_fix_mode( $c['status_mode'] );
	$pre  = smlife_catc_presets();
	if ( isset( $pre[ $mode ] ) ) {
		return array( 'text' => $pre[ $mode ][0], 'color' => $pre[ $mode ][1] );
	}
	if ( 'custom' === $mode ) {
		$t = trim( (string) $c['status_text'] );
		if ( '' !== $t ) {
			$col = sanitize_hex_color( $c['status_color'] );
			return array( 'text' => $t, 'color' => $col ? $col : '#16a34a' );
		}
	}
	return array( 'text' => '', 'color' => '' );
}

function smlife_catc_status_text( $c ) {
	$s = smlife_catc_status( $c );
	return $s['text'];
}

/* =====================================================================
 * 3. TIM NOI DUNG CHO SAN PHAM DANG XEM
 * ===================================================================== */

/* Danh sach term_id xep theo thu tu uu tien: danh muc sau nhat cua san pham
 * truoc, roi lan nguoc len cha cua no, sau do den danh muc sau nhi.
 * Nho vay danh muc con de trong slot nao thi tu dong lay cua cha. */
function smlife_catc_chain( $product_id ) {
	static $cache = array();
	$pid = (int) $product_id;
	if ( isset( $cache[ $pid ] ) ) {
		return $cache[ $pid ];
	}
	$chain = array();
	$terms = wp_get_post_terms( $pid, 'product_cat', array( 'fields' => 'ids' ) );
	if ( is_wp_error( $terms ) || empty( $terms ) ) {
		$cache[ $pid ] = $chain;
		return $chain;
	}
	$depth = array();
	foreach ( $terms as $t ) {
		$anc         = get_ancestors( $t, 'product_cat', 'taxonomy' );
		$depth[ $t ] = is_array( $anc ) ? count( $anc ) : 0;
	}
	/* Sau nhat truoc. Hai danh muc cung cap thi term_id nho hon truoc,
	   de ket qua luon giong nhau chu khong phu thuoc thu tu WordPress tra ve. */
	$ids = array_keys( $depth );
	usort( $ids, function ( $x, $y ) use ( $depth ) {
		if ( $depth[ $x ] === $depth[ $y ] ) {
			return $x <=> $y;
		}
		return $depth[ $y ] <=> $depth[ $x ];
	} );
	$lan = smlife_catc_kethua();
	foreach ( $ids as $t ) {
		$chain[] = (int) $t;
		/* Tat ke thua thi dung lai o danh muc duoc gan truc tiep cho san pham,
		   khong lan nguoc len danh muc cha nua. Danh muc con khong dat gi thi
		   slot do khong hien, du cha co dat. */
		if ( ! $lan ) {
			continue;
		}
		$anc = get_ancestors( $t, 'product_cat', 'taxonomy' );
		if ( is_array( $anc ) ) {
			foreach ( $anc as $a ) {
				$chain[] = (int) $a;
			}
		}
	}
	$chain         = array_values( array_unique( $chain ) );
	$cache[ $pid ] = $chain;
	return $chain;
}

/* Lay gia tri mot slot, lan theo chuoi uu tien, gap cai dau tien co noi dung. */
function smlife_catc_field( $field, $product_id = 0 ) {
	if ( ! $product_id ) {
		$product_id = get_the_ID();
	}
	if ( ! $product_id ) {
		return 'status' === $field ? array( 'text' => '', 'color' => '' ) : '';
	}
	foreach ( smlife_catc_chain( $product_id ) as $t ) {
		$c = smlife_catc_get( $t );
		if ( 'status' === $field ) {
			$s = smlife_catc_status( $c );
			if ( '' !== $s['text'] ) {
				return $s;
			}
			continue;
		}
		list( $type, $val ) = smlife_catc_slot_of( $c, $field );
		if ( 'block' === $type ) {
			return $val;
		}
		if ( 'html' === $type ) {
			/* Slot nay dung noi dung tu soan, khong phai slug block.
			   Ai can noi dung that thi goi smlife_catc_resolve(). */
			return '';
		}
	}
	return 'status' === $field ? array( 'text' => '', 'color' => '' ) : '';
}

/* Mot slot cua mot danh muc dang dung kieu gi.
 * Tra ve array( loai, gia tri ): loai la 'block' | 'html' | '' (de trong). */
function smlife_catc_slot_of( $c, $slot ) {
	$v = isset( $c[ $slot ] ) ? trim( (string) $c[ $slot ] ) : '';
	if ( '' === $v ) {
		return array( '', '' );
	}
	if ( SMLIFE_CATC_HTML === $v ) {
		$h = isset( $c[ $slot . '_html' ] ) ? trim( (string) $c[ $slot . '_html' ] ) : '';
		/* Chon tu soan nhung bo trong thi coi nhu chua dat, de con lan len cha. */
		return ( '' === $h ) ? array( '', '' ) : array( 'html', $h );
	}
	return array( 'block', $v );
}

/* Lan theo chuoi uu tien cua san pham, lay slot dau tien co noi dung that. */
function smlife_catc_resolve( $slot, $product_id = 0 ) {
	if ( ! $product_id ) {
		$product_id = get_the_ID();
	}
	if ( ! $product_id ) {
		return array( '', '' );
	}
	foreach ( smlife_catc_chain( $product_id ) as $t ) {
		list( $type, $val ) = smlife_catc_slot_of( smlife_catc_get( $t ), $slot );
		if ( '' !== $type ) {
			return array( $type, $val );
		}
	}
	return array( '', '' );
}

/* Slot nay neu de trong thi se an theo danh muc cha nao.
 * Chi dung de hien goi y trong trang quan tri. */
function smlife_catc_inherited( $term_id, $slot ) {
	if ( ! smlife_catc_kethua() ) {
		return array( '', 0 );
	}
	$anc = get_ancestors( (int) $term_id, 'product_cat', 'taxonomy' );
	if ( ! is_array( $anc ) ) {
		return array( '', 0 );
	}
	foreach ( $anc as $a ) {
		$c = smlife_catc_get( $a );
		if ( 'status' === $slot ) {
			$s = smlife_catc_status( $c );
			if ( '' !== $s['text'] ) {
				return array( $s['text'], (int) $a );
			}
			continue;
		}
		list( $type, $val ) = smlife_catc_slot_of( $c, $slot );
		if ( 'html' === $type ) {
			return array( SMLIFE_CATC_HTML, (int) $a );
		}
		if ( 'block' === $type ) {
			return array( $val, (int) $a );
		}
	}
	return array( '', 0 );
}

/* =====================================================================
 * 4. SHORTCODE
 * ===================================================================== */

/* Slot 1: dong chu Tinh trang */
add_shortcode( 'smlife_tinh_trang', 'smlife_catc_sc_status' );
function smlife_catc_sc_status() {
	/* 1.24.0: san pham het hang thi khong hien cau mac dinh cua danh muc
	   (vd "Hang co san, giao Grab 3h") de khong noi nguoc voi nut Lien he dat hang */
	$sp = function_exists( 'wc_get_product' ) ? wc_get_product( get_the_ID() ) : null;
	if ( $sp && ! $sp->is_in_stock() ) {
		return '';
	}
	$s = smlife_catc_field( 'status' );
	if ( empty( $s['text'] ) ) {
		return '';
	}
	$chung = smlife_catc_tt_mau();
	if ( '' !== $chung ) {
		$col = $chung;
	} else {
		$col = sanitize_hex_color( $s['color'] ) ? $s['color'] : '#16a34a';
	}
	$h   = '<div class="smlife-catc-tt">';
	$h  .= '<span class="smlife-catc-tt-l">Tình trạng:</span> ';
	$h  .= '<span class="smlife-catc-tt-v" style="color:' . esc_attr( $col ) . '">' . esc_html( $s['text'] ) . '</span>';
	$h  .= '</div>';
	return $h;
}

/* Shortcode bo cuc cua Flatsome. Dan tu UX Builder ra thi se gap may cai nay. */
function smlife_catc_is_builder( $s ) {
	return (bool) preg_match( '#\[(row|col|col_inner|row_inner|section|ux_[a-z_]+|featured_box|gap|block)[\s\]]#i', (string) $s );
}

/* Sua lai noi dung shortcode bi trinh soan thao lam hong.
 *
 * Dan shortcode cua UX Builder vao tab Truc quan thi WordPress boc moi dong
 * vao mot the <p>. Trinh duyet khong cho the <p> chua the <div>, nen khi
 * Flatsome dung ra thi cau truc vo vun va khong hien gi ca. Ham nay go bo
 * nhung the <p> va <br> do, va them [row] bao ngoai neu nguoi dung chi copy
 * mot cot [col] roi vi [col] dung mot minh thi Flatsome khong dung duoc. */
function smlife_catc_fix_sc( $raw ) {
	$s = (string) $raw;
	if ( false === strpos( $s, '[' ) ) {
		return $s;
	}

	/* Doan van nao chi gom shortcode va khoang trang thi bo the p di,
	   doan co chu that thi giu nguyen. */
	$s = preg_replace_callback( '#<p>(.*?)</p>#is', function ( $m ) {
		$t = preg_replace( '#\[[^\]]*\]#', '', $m[1] );
		$t = str_replace( array( '&nbsp;', '<br />', '<br/>', '<br>' ), ' ', $t );
		return ( '' === trim( $t ) ) ? $m[1] . "\n" : $m[0];
	}, $s );

	/* The xuong dong dinh ngay canh shortcode cung phai bo. */
	$s = preg_replace( '#<br\s*/?>\s*(?=\[)#i', "\n", $s );
	$s = preg_replace( '#(\])\s*<br\s*/?>#i', "$1\n", $s );

	/* Chi copy mot cot thi them hang bao ngoai, khong thi Flatsome bo qua.
	   [col_inner] khong tinh vi no da nam trong [row_inner] roi. */
	if ( preg_match( '#^\s*\[col[\s\]]#i', $s ) && ! preg_match( '#\[row[\s\]]#i', $s ) ) {
		$s = "[row]\n" . trim( $s ) . "\n[/row]";
	}

	return $s;
}

/* Noi dung tu soan: cho chay shortcode, tu xuong dong neu la van ban thuan. */
function smlife_catc_html( $raw ) {
	$raw = (string) $raw;
	if ( '' === trim( $raw ) ) {
		return '';
	}
	$raw = smlife_catc_fix_sc( $raw );
	$out = do_shortcode( $raw );
	/* Da co the khoi roi thi khong dong them the p nua, tranh vo bo cuc. */
	if ( ! preg_match( '#<(p|div|section|table|ul|ol|figure|h[1-6])[\s>]#i', $out ) ) {
		$out = wpautop( $out );
	}
	return $out;
}

/* Slot 2 den 5: hoac UX Block cua Flatsome, hoac noi dung tu soan tai cho. */
function smlife_catc_render_slot( $slot ) {
	list( $type, $val ) = smlife_catc_resolve( $slot );
	if ( '' === $type ) {
		return '';
	}
	if ( 'html' === $type ) {
		$inner = smlife_catc_html( $val );
	} else {
		/* Flatsome tat hoac doi theme thi khong co shortcode block.
		   Tra ve rong chu tuyet doi khong in [block id=...] ra trang. */
		if ( ! shortcode_exists( 'block' ) ) {
			return '';
		}
		$inner = do_shortcode( '[block id="' . esc_attr( $val ) . '"]' );
	}
	if ( '' === trim( wp_strip_all_tags( $inner ) ) && false === strpos( $inner, '<img' ) ) {
		return '';
	}
	$cls = 'smlife-catc-slot smlife-catc-' . sanitize_html_class( $slot );
	if ( smlife_catc_hide_mb( $slot ) ) {
		$cls .= ' smlife-catc-pc';
	}
	return '<div class="' . esc_attr( $cls ) . '">' . $inner . '</div>';
}

add_shortcode( 'smlife_banner_sp', function () {
	return smlife_catc_render_slot( 'banner' );
} );
add_shortcode( 'smlife_chi_co_tai', function () {
	return smlife_catc_render_slot( 'chi_co_tai' );
} );
add_shortcode( 'smlife_vat_lieu', function () {
	return smlife_catc_render_slot( 'vat_lieu' );
} );
add_shortcode( 'smlife_noidung_them', function () {
	return smlife_catc_render_slot( 'extra' );
} );

/* =====================================================================
 * 5. CSS FRONTEND
 * ===================================================================== */
add_action( 'wp_head', function () {
	$c  = '.smlife-catc-tt{font-size:14px;line-height:1.6;margin:6px 0 10px}';
	$c .= '.smlife-catc-tt-l{color:#555}';
	$c .= '.smlife-catc-tt-v{font-weight:600}';
	$c .= '.smlife-catc-slot{margin:0 0 16px}';
	$c .= '.smlife-catc-slot img{max-width:100%;height:auto}';
	$c .= '@media (max-width:849px){.smlife-catc-pc{display:none !important}}';
	echo '<style id="smlife-catc-css">' . $c . '</style>';
}, 20 );

/* =====================================================================
 * 6. TRANG QUAN TRI - BANG TRAI TOAN BO DANH MUC
 * ===================================================================== */
/* Ban cu luu option o che do autoload, tuc la WordPress tai no o moi request
 * cua ca website du chi trang san pham moi dung. Doi mot lan cho nhe. */
add_action( 'admin_init', function () {
	if ( get_option( 'smlife_catc_al', 0 ) ) {
		return;
	}
	$v = get_option( 'smlife_catc', null );
	if ( null !== $v ) {
		if ( function_exists( 'wp_set_option_autoload' ) ) {
			wp_set_option_autoload( 'smlife_catc', false );
		} else {
			delete_option( 'smlife_catc' );
			add_option( 'smlife_catc', $v, '', 'no' );
		}
		smlife_catc_flush();
	}
	update_option( 'smlife_catc_al', 1, false );
} );

/* Trang nay co nut Chen anh nen can Thu vien anh cua WordPress. */
add_action( 'admin_enqueue_scripts', function ( $hook ) {
	if ( false === strpos( (string) $hook, 'smlife-noi-dung-danh-muc' ) ) {
		return;
	}
	if ( function_exists( 'wp_enqueue_media' ) ) {
		wp_enqueue_media();
	}
} );

add_action( 'admin_menu', 'smlife_catc_menu', 60 );
function smlife_catc_menu() {
	add_submenu_page(
		'smlife-core',
		'Dynamic Shortcode',
		'Dynamic Shortcode',
		'manage_options',
		'smlife-noi-dung-danh-muc',
		'smlife_catc_page'
	);
}

/* Danh sach danh muc san pham dang phang, thut dau dong theo cap. */
function smlife_catc_term_options() {
	$out   = array();
	$terms = get_terms( array(
		'taxonomy'   => 'product_cat',
		'hide_empty' => false,
		'orderby'    => 'name',
	) );
	if ( is_wp_error( $terms ) || empty( $terms ) ) {
		return $out;
	}
	$by_parent = array();
	foreach ( $terms as $t ) {
		$by_parent[ (int) $t->parent ][] = $t;
	}
	$walk = function ( $parent, $level ) use ( &$walk, &$out, $by_parent ) {
		if ( empty( $by_parent[ $parent ] ) ) {
			return;
		}
		foreach ( $by_parent[ $parent ] as $t ) {
			$out[ (int) $t->term_id ] = array( 'name' => $t->name, 'level' => $level );
			$walk( (int) $t->term_id, $level + 1 );
		}
	};
	$walk( 0, 0 );
	return $out;
}

/* Danh sach UX Block cua Flatsome. */
function smlife_catc_block_options() {
	$out  = array();
	$rows = get_posts( array(
		'post_type'      => 'blocks',
		'post_status'    => 'publish',
		'posts_per_page' => -1, /* 1.24.0: bo gioi han 300, qua 300 block thi block dang chon bi mat khi luu */
		'no_found_rows'  => true,
		'orderby'        => 'title',
		'order'          => 'ASC',
	) );
	foreach ( $rows as $r ) {
		$out[ $r->post_name ] = $r->post_title;
	}
	return $out;
}

function smlife_catc_page() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}
	$cats   = smlife_catc_term_options();
	$blocks = smlife_catc_block_options();
	$slots  = smlife_catc_slots();
	$store  = smlife_catc_store();
	$pre    = smlife_catc_presets();

	$done = 0;
	foreach ( $cats as $id => $t ) {
		if ( ! empty( $store['cats'][ $id ] ) ) {
			$done++;
		}
	}

	echo '<div class="wrap smlife-wrap smlife-catc-wrap">';
	echo '<div class="smc-hd"><h1>Dynamic Shortcode</h1>';
	echo '<p>Mọi sản phẩm chạy chung một block layout. 5 vùng nội dung thay đổi theo danh mục được đặt sẵn trong layout bằng shortcode. Mỗi vùng có hai cách điền: chọn một <b>UX Block</b> có sẵn khi nội dung cầu kỳ, hoặc bấm <b>Tự soạn tại đây</b> gõ thẳng vài dòng chữ hay chèn một tấm ảnh mà không cần ra tạo block.</p>';
	echo '<div class="smc-meta"><span><b>' . (int) $done . '</b> / ' . count( $cats ) . ' danh mục đã cấu hình</span>';
	echo '<span><b>' . count( $blocks ) . '</b> UX Block khả dụng</span></div></div>';

	if ( isset( $_GET['saved'] ) ) {
		echo '<div class="notice notice-success is-dismissible"><p>Đã lưu. Thay đổi sẽ lên website ở lần xoá cache kế tiếp.</p></div>';
	}
	if ( isset( $_GET['thieu'] ) ) {
		echo '<div class="notice notice-error"><p><b>Chưa lưu gì cả.</b> Máy chủ chỉ nhận được một phần dữ liệu của bảng (giới hạn <code>max_input_vars</code> = ' . (int) ini_get( 'max_input_vars' ) . '). Để tránh xoá nhầm cấu hình các danh mục còn lại, plugin đã dừng. Nhờ hosting tăng <code>max_input_vars</code> lên 5000 rồi lưu lại.</p></div>';
	}
	$miv = (int) ini_get( 'max_input_vars' );
	$can = count( $cats ) * ( 3 + 2 * count( $slots ) ) + 20;
	if ( $miv > 0 && $can > $miv * 0.8 ) {
		echo '<div class="notice notice-warning"><p>Bảng này gửi khoảng <b>' . (int) $can . '</b> ô dữ liệu mỗi lần lưu, máy chủ đang cho tối đa <b>' . (int) $miv . '</b> (<code>max_input_vars</code>). ' . ( $can > $miv ? 'Đã vượt giới hạn: plugin sẽ từ chối lưu để không xoá nhầm. ' : 'Sắp chạm giới hạn. ' ) . 'Nên nhờ hosting tăng lên 5000.</p></div>';
	}
	if ( empty( $cats ) ) {
		echo '<div class="notice notice-warning"><p>Chưa có danh mục sản phẩm nào.</p></div></div>';
		return;
	}
	if ( empty( $blocks ) ) {
		echo '<div class="notice notice-warning"><p>Chưa tìm thấy UX Block nào. Vào <b>Flatsome &gt; UX Blocks</b> tạo block trước, rồi quay lại đây chọn.</p></div>';
	}

	/* --- Nhac dat shortcode vao layout --- */
	echo '<div class="smlife-card smlife-catc-help"><h2>Đặt 5 shortcode này vào block layout sản phẩm</h2><div class="in">';
	echo '<p style="margin-top:0">Làm đúng một lần trong block <b>Product Layout CHUẨN</b>. Sau đó mọi danh mục đều dùng chung layout đó.</p>';
	echo '<ol class="smlife-catc-sclist">';
	echo '<li><code>[smlife_tinh_trang]</code><span>Ngay dưới tên sản phẩm, trên dòng giá</span></li>';
	foreach ( $slots as $k => $s ) {
		echo '<li><code>[' . esc_html( $s['sc'] ) . ']</code><span>' . esc_html( $s['name'] . ' - ' . $s['desc'] ) . '</span></li>';
	}
	echo '</ol>';
	echo '<p class="description" style="margin-bottom:0">Danh mục nào bỏ trống slot nào thì slot đó tự biến mất, trang không hiện dòng chữ shortcode thô.</p>';
	echo '<p class="description" style="margin-bottom:0">Khi nào dùng cách nào: nội dung chỉ là một tấm ảnh, một đoạn chữ hay một khung màu thì tự soạn tại đây cho nhanh. Nội dung nhiều cột, nhiều hiệu ứng, hoặc dùng lại ở nhiều chỗ thì tạo UX Block rồi chọn ở đây.</p>';
	echo '</div></div>';

	echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '">';
	echo '<input type="hidden" name="action" value="smlife_catc_save" />';
	wp_nonce_field( 'smlife_catc_save', 'smlife_catc_nonce' );

	/* --- Cai dat chung --- */
	echo '<div class="smlife-card"><h2>Kế thừa từ danh mục cha</h2><div class="in">';
	echo '<label><input type="checkbox" name="kethua" value="1" ' . checked( true, smlife_catc_kethua(), false ) . ' /> ';
	echo 'Danh mục con bỏ trống thì lấy nội dung của danh mục cha</label>';
	echo '<p class="description">Mặc định <b>tắt</b>: mỗi danh mục chỉ dùng đúng nội dung của chính nó. Danh mục con bỏ trống slot nào thì slot đó <b>không hiện</b>, dù danh mục cha có đặt.</p>';
	echo '<p class="description">Bật lên thì danh mục con bỏ trống sẽ ăn theo cha, và ô chọn trong bảng sẽ ghi sẵn đang theo cha nào.</p>';
	echo '</div></div>';

	$st_now = smlife_catc_store();
	echo '<div class="smlife-card"><h2>Màu dòng Tình trạng</h2><div class="in">';
	echo '<label><input type="checkbox" name="tt_chung" value="1" ' . checked( true, ! empty( $st_now['tt_chung'] ), false ) . ' /> ';
	echo 'Mọi danh mục dùng chung một màu cho phần chữ sau <b>Tình trạng:</b></label> ';
	echo '<input type="text" name="tt_mau" value="' . esc_attr( isset( $st_now['tt_mau'] ) ? $st_now['tt_mau'] : '#15803d' ) . '" class="regular-text" style="max-width:120px;margin-left:10px" placeholder="#15803d" />';
	echo '<p class="description">Bật lên thì màu đặt riêng ở từng danh mục bên dưới sẽ bị bỏ qua, tất cả hiện cùng một màu. Tắt đi là mỗi danh mục quay lại dùng màu của chính nó, không mất cài đặt cũ.</p>';
	echo '</div></div>';

	echo '<div class="smlife-card"><h2>Ẩn trên điện thoại</h2><div class="in">';
	echo '<p class="description" style="margin-top:0">Tick slot nào thì slot đó chỉ hiện trên máy tính. Áp dụng cho mọi danh mục.</p>';
	echo '<div class="smlife-catc-mb">';
	foreach ( $slots as $k => $s ) {
		$on = ! empty( $store['hide_mb'][ $k ] );
		echo '<label><input type="checkbox" name="hide_mb[' . esc_attr( $k ) . ']" value="1" ' . checked( true, $on, false ) . ' /> ';
		echo '<b>' . esc_html( $s['no'] ) . '.</b> ' . esc_html( $s['name'] ) . '</label>';
	}
	echo '</div></div></div>';

	/* --- Canh bao: mot danh muc dung cung mot block cho hai slot --- */
	$trung = array();
	foreach ( $cats as $id => $t ) {
		$cc = smlife_catc_get( $id );
		$kk = array();
		foreach ( $slots as $k => $s ) {
			$v = (string) $cc[ $k ];
			if ( '' === $v || SMLIFE_CATC_HTML === $v ) {
				continue;
			}
			$kk[ $v ][] = $s['no'];
		}
		foreach ( $kk as $blk => $nos ) {
			if ( count( $nos ) > 1 ) {
				$ten     = isset( $blocks[ $blk ] ) ? $blocks[ $blk ] : $blk;
				$trung[] = $t['name'] . ': slot ' . implode( ' và ', $nos ) . ' cùng dùng block "' . $ten . '"';
			}
		}
	}
	if ( $trung ) {
		echo '<div class="notice notice-warning"><p><b>Có danh mục đang dùng chung một block cho hai slot</b>, nên khối đó hiện hai lần trên trang sản phẩm. Vào bảng bên dưới sửa ô thừa thành <b>Không hiện</b>:</p><ul style="margin:0 0 6px 18px;list-style:disc">';
		foreach ( array_slice( $trung, 0, 20 ) as $line ) {
			echo '<li>' . esc_html( $line ) . '</li>';
		}
		if ( count( $trung ) > 20 ) {
			echo '<li>... và ' . (int) ( count( $trung ) - 20 ) . ' danh mục nữa.</li>';
		}
		echo '</ul></div>';
	}

	/* --- Bang chinh --- */
	echo '<div class="smlife-card"><h2>Chọn nội dung cho từng danh mục</h2><div class="in">';
	echo '<div class="smlife-catc-bar">';
	echo '<input type="search" id="smlife-catc-find" placeholder="Lọc nhanh theo tên danh mục..." />';
	echo '<label class="smlife-catc-only"><input type="checkbox" id="smlife-catc-onlyset" /> Chỉ hiện danh mục đã cấu hình</label>';
	echo '</div>';

	echo '<div class="smlife-catc-scroll"><table class="smlife-catc-tb"><thead><tr>';
	echo '<th class="c-name">Danh mục</th>';
	echo '<th>1. Tình trạng</th>';
	foreach ( $slots as $k => $s ) {
		echo '<th>' . esc_html( $s['no'] . '. ' . $s['name'] ) . '</th>';
	}
	echo '</tr></thead><tbody>';

	foreach ( $cats as $id => $t ) {
		$c    = smlife_catc_get( $id );
		$mode = smlife_catc_fix_mode( $c['status_mode'] );
		$set  = ! empty( $store['cats'][ $id ] );
		$lv   = min( 3, (int) $t['level'] );

		echo '<tr class="lv' . $lv . ( $set ? ' is-set' : '' ) . '" data-name="' . esc_attr( mb_strtolower( $t['name'] ) ) . '" data-set="' . ( $set ? 1 : 0 ) . '">';
		echo '<td class="c-name"><span class="nm">' . esc_html( $t['name'] ) . '</span></td>';

		/* Cot Tinh trang */
		list( $inh_tt, $inh_tt_id ) = smlife_catc_inherited( $id, 'status' );
		echo '<td class="c-tt">';
		echo '<select name="c[' . (int) $id . '][status_mode]" class="catc-mode' . ( 'off' !== $mode ? ' has' : '' ) . '">';
		$none = ( '' !== $inh_tt ) ? '↳ ' . smlife_catc_cut( $inh_tt, 34 ) : 'Không hiện';
		$tip  = ( '' !== $inh_tt ) ? ' title="Đang lấy của danh mục cha: ' . esc_attr( $inh_tt ) . '"' : '';
		echo '<option value="off"' . $tip . '>' . esc_html( $none ) . '</option>';
		foreach ( $pre as $mk => $mv ) {
			echo '<option value="' . esc_attr( $mk ) . '" ' . selected( $mode, $mk, false ) . '>' . esc_html( $mv[0] ) . '</option>';
		}
		echo '<option value="custom" ' . selected( $mode, 'custom', false ) . '>Tự nhập câu khác…</option>';
		echo '</select>';
		echo '<div class="catc-custom"' . ( 'custom' === $mode ? '' : ' style="display:none"' ) . '>';
		echo '<input type="text" name="c[' . (int) $id . '][status_text]" value="' . esc_attr( $c['status_text'] ) . '" placeholder="Nhập câu riêng" />';
		echo '<input type="color" name="c[' . (int) $id . '][status_color]" value="' . esc_attr( $c['status_color'] ) . '" title="Màu chữ" />';
		echo '</div>';
		echo '</td>';

		/* 4 cot noi dung: chon UX Block co san, hoac tu soan ngay tai day */
		foreach ( $slots as $k => $s ) {
			list( $inh, $inh_id ) = smlife_catc_inherited( $id, $k );
			$lbl = 'Không hiện';
			$tip = '';
			if ( '' !== $inh ) {
				if ( SMLIFE_CATC_HTML === $inh ) {
					$title = 'Nội dung tự soạn';
				} else {
					$title = isset( $blocks[ $inh ] ) ? $blocks[ $inh ] : $inh;
				}
				$lbl = '↳ ' . smlife_catc_cut( $title, 34 );
				$tip = ' title="Đang lấy của danh mục cha: ' . esc_attr( $title ) . '"';
			}
			$cur  = (string) $c[ $k ];
			$html = (string) $c[ $k . '_html' ];
			$self = ( SMLIFE_CATC_HTML === $cur );
			$has  = ( '' !== $cur );

			echo '<td class="c-slot">';
			echo '<select name="c[' . (int) $id . '][' . esc_attr( $k ) . ']" class="catc-blk' . ( $has ? ' has' : '' ) . '">';
			echo '<option value=""' . $tip . '>' . esc_html( $lbl ) . '</option>';
			echo '<option value="' . esc_attr( SMLIFE_CATC_HTML ) . '" ' . selected( $self, true, false ) . '>✎ Tự soạn tại đây</option>';
			/* 1.24.0: block dang chon nhung khong con trong danh sach (nhap, doi ten, xoa)
			   van phai in ra va chon san, neu khong bam Luu se thanh Khong hien */
			if ( $has && ! $self && ! isset( $blocks[ $cur ] ) ) {
				echo '<option value="' . esc_attr( $cur ) . '" selected="selected">⚠ ' . esc_html( $cur ) . ' (không tìm thấy block đang xuất bản)</option>';
			}
			if ( ! empty( $blocks ) ) {
				echo '<optgroup label="UX Block có sẵn">';
				foreach ( $blocks as $slug => $title ) {
					echo '<option value="' . esc_attr( $slug ) . '" ' . selected( $cur, $slug, false ) . '>' . esc_html( $title ) . '</option>';
				}
				echo '</optgroup>';
			}
			echo '</select>';

			/* Khung soan nhanh. Luon in ra de luu duoc, chi an di khi khong dung. */
			echo '<div class="catc-html"' . ( $self ? '' : ' style="display:none"' ) . '>';
			echo '<button type="button" class="button button-small catc-edit" data-title="' . esc_attr( $t['name'] . ' - ' . $s['no'] . '. ' . $s['name'] ) . '">';
			echo '✎ ' . ( '' !== trim( $html ) ? 'Sửa nội dung' : 'Soạn nội dung' ) . '</button>';
			echo '<span class="catc-sum">' . esc_html( smlife_catc_sum( $html ) ) . '</span>';
			echo '<textarea name="c[' . (int) $id . '][' . esc_attr( $k ) . '_html]" class="catc-src" rows="4">' . esc_textarea( $html ) . '</textarea>';
			echo '</div>';
			echo '</td>';
		}
		echo '</tr>';
	}
	echo '</tbody></table></div>';
	if ( smlife_catc_kethua() ) {
		echo '<p class="description" style="margin-bottom:0">Ô nào ghi <b>↳</b> nghĩa là danh mục đó đang bỏ trống và tự lấy nội dung của danh mục cha, rê chuột lên để xem lấy của cha nào. Nên đặt nội dung chung ở danh mục cấp 1, chỉ đặt đè ở danh mục con khi thật sự khác.</p>';
	} else {
		echo '<p class="description" style="margin-bottom:0">Đang <b>tắt kế thừa</b>: danh mục nào bỏ trống slot nào thì slot đó không hiện, dù danh mục cha có đặt. Muốn danh mục con ăn theo cha thì bật ô <b>Kế thừa từ danh mục cha</b> ở trên.</p>';
	}
	echo '</div></div>';

	/* Khung soan noi dung bat len giua man hinh. Mot cai dung chung cho ca bang.
	   Chi mot trinh soan thao duy nhat cho ca bang: 40 o ma moi o mot trinh soan
	   thao thi trang se nang khong mo noi. Bam vao o nao thi noi dung o do duoc
	   nap vao day, bam Xong thi tra nguoc lai o do. */
	echo '<div class="smlife-catc-modal is-off" id="smlife-catc-modal"><div class="box">';

	echo '<div class="hd">';
	echo '<b id="smlife-catc-mtitle">Soạn nội dung</b>';
	echo '<span class="tabs">';
	echo '<button type="button" class="tab is-on" data-tab="edit">Soạn thảo</button>';
	echo '<button type="button" class="tab" data-tab="prev">Xem thử</button>';
	echo '</span>';
	echo '<button type="button" class="catc-close" title="Đóng">✕</button>';
	echo '</div>';

	echo '<div class="bd pane pane-edit">';
	echo '<div class="catc-warn" id="smlife-catc-warn" style="display:none">';
	echo '<b>Đây là nội dung của UX Builder.</b> Tab <b>Trực quan</b> sẽ làm hỏng nó, nên tao đã tự chuyển sang tab <b>Mã</b> và khoá lại. ';
	echo 'Nội dung kiểu này nên để thành UX Block cho dễ sửa sau này: ';
	echo '<button type="button" class="button button-small" id="smlife-catc-mk">Tạo UX Block từ nội dung này</button> ';
	echo '<span class="msg"></span>';
	echo '</div>';
	wp_editor( '', 'smlife_catc_ed', array(
		'textarea_name' => 'smlife_catc_scratch',
		'textarea_rows' => 16,
		'media_buttons' => true,
		'teeny'         => false,
		'quicktags'     => true,
		'drag_drop_upload' => true,
	) );
	echo '<p class="description">Dán được cả HTML và shortcode, ví dụ <code>[block id="..."]</code>. Tab <b>Xem thử</b> hiển thị đúng giao diện web thật.</p>';
	echo '</div>';

	echo '<div class="bd pane pane-prev" style="display:none">';
	echo '<div class="prevbar">';
	echo '<button type="button" class="button button-small is-on" data-w="pc">Máy tính</button>';
	echo '<button type="button" class="button button-small" data-w="mb">Điện thoại</button>';
	echo '<button type="button" class="button button-small" data-w="re">Tải lại</button>';
	echo '<span class="description" id="smlife-catc-pmsg"></span>';
	echo '</div>';
	echo '<div class="frame"><iframe id="smlife-catc-frame" title="Xem thử nội dung"></iframe></div>';
	echo '</div>';

	echo '<div class="ft"><button type="button" class="button button-primary catc-close">Xong</button>';
	echo '<span class="description">Nội dung chỉ thật sự lưu khi bấm <b>Lưu toàn bộ</b> ở cuối trang.</span></div>';
	echo '</div></div>';

	echo '<div class="smlife-catc-save"><div class="in">';
	submit_button( 'Lưu toàn bộ', 'primary large', 'submit', false );
	echo '<span class="description">Lưu một lần cho tất cả danh mục trong bảng.</span>';
	/* 1.24.0: moc cuoi form. PHP cat bot o nhap khi vuot max_input_vars, cat tu cuoi len,
	   nen thieu moc nay nghia la du lieu den khong du -> tu choi luu de khong xoa nham. */
	echo '<input type="hidden" name="catc_so" value="' . count( $cats ) . '" />';
	echo '<input type="hidden" name="catc_het" value="1" />';
	echo '</div></div>';
	echo '</form></div>';

	smlife_catc_page_js();
}

/* Loc noi dung tu soan truoc khi luu.
 * Luon chan script, style va cac thuoc tinh onclick kieu nay, ke ca voi
 * tai khoan duoc phep dang HTML tu do, vi o nay chi de dat noi dung ban hang
 * chu khong phai cho de chay ma. Ai khong duoc phep thi loc tiep theo chuan
 * bai viet cua WordPress (bo luon iframe). */
function smlife_catc_clean_html( $raw ) {
	$h = (string) $raw;
	$h = preg_replace( '#<\s*(script|style)\b[^>]*>.*?<\s*/\s*\1\s*>#is', '', $h );
	$h = preg_replace( '#<\s*/?\s*(script|style)\b[^>]*>#i', '', $h );
	$h = preg_replace( '#\son[a-z]+\s*=\s*(?:"[^"]*"|\'[^\']*\'|[^\s>]+)#i', '', $h );
	$h = preg_replace( '#(href|src)\s*=\s*([\'"])\s*javascript:[^\'"]*\2#i', '$1="#"', $h );
	if ( ! current_user_can( 'unfiltered_html' ) ) {
		$h = wp_kses_post( $h );
	}
	return trim( $h );
}

/* Mot dong tom tat noi dung tu soan de nhin phat biet trong bang. */
function smlife_catc_sum( $html ) {
	$html = (string) $html;
	if ( '' === trim( $html ) ) {
		return 'chưa có nội dung';
	}
	$bit = array();
	$n   = preg_match_all( '#<img[\s>]#i', $html );
	if ( $n ) {
		$bit[] = $n . ' ảnh';
	}
	if ( preg_match( '#\[[a-z0-9_-]+#i', $html ) ) {
		$bit[] = 'shortcode';
	}
	$txt = trim( preg_replace( '/\s+/', ' ', wp_strip_all_tags( $html ) ) );
	if ( '' !== $txt ) {
		$bit[] = smlife_catc_cut( $txt, 30 );
	}
	return implode( ' · ', $bit );
}

/* Cat chuoi dai cho vua o chon. */
function smlife_catc_cut( $s, $len ) {
	$s = trim( (string) $s );
	if ( function_exists( 'mb_strlen' ) && mb_strlen( $s ) > $len ) {
		return mb_substr( $s, 0, $len - 1 ) . '…';
	}
	return $s;
}

/* JS nho: hien o tu nhap khi chon Tu nhap, va loc nhanh danh muc. */
function smlife_catc_page_js() {
	?>
	<script>
	(function () {
		var tb = document.querySelector('.smlife-catc-tb');
		if (!tb) { return; }
		var CFG = <?php echo wp_json_encode( array(
			'self'  => SMLIFE_CATC_HTML,
			'ajax'  => admin_url( 'admin-ajax.php' ),
			'nonce' => wp_create_nonce( 'smlife_catc_prev' ),
			'blk'   => post_type_exists( 'blocks' ),
		) ); ?>;
		var SELF = CFG.self;

		/* Chon "Tu nhap cau khac" thi moi hien o nhap chu va chon mau. */
		tb.addEventListener('change', function (e) {
			if (!e.target.classList.contains('catc-mode')) { return; }
			var box = e.target.parentNode.querySelector('.catc-custom');
			if (box) { box.style.display = (e.target.value === 'custom') ? '' : 'none'; }
			e.target.classList.toggle('has', e.target.value !== 'off');
			var tr = e.target.closest ? e.target.closest('tr') : null;
			if (tr) { mark(tr); }
		});

		/* O chon co noi dung thi to dam len cho de quet mat.
		   Chon "Tu soan tai day" thi mo luon khung soan cho do mat mot cu bam. */
		tb.addEventListener('change', function (e) {
			if (!e.target.classList.contains('catc-blk')) { return; }
			e.target.classList.toggle('has', e.target.value !== '');
			var box = e.target.parentNode.querySelector('.catc-html');
			var self = (e.target.value === SELF);
			if (box) {
				box.style.display = self ? '' : 'none';
				if (self) { openBox(box.querySelector('.catc-edit')); }
			}
			var tr = e.target.closest ? e.target.closest('tr') : null;
			if (tr) { mark(tr); }
		});

		/* Bam nut Soan / Sua thi bat khung soan len. */
		tb.addEventListener('click', function (e) {
			var b = e.target.closest ? e.target.closest('.catc-edit') : null;
			if (b) { e.preventDefault(); openBox(b); }
		});

		/* Hang nao co it nhat mot o da chon thi danh dau ngay, khong can tai lai trang. */
		function mark(tr) {
			var n = tr.querySelectorAll('.catc-blk.has, .catc-mode.has').length;
			tr.classList.toggle('is-set', n > 0);
			tr.setAttribute('data-set', n > 0 ? '1' : '0');
		}

		/* ----- Khung soan noi dung ----- */
		var ED = 'smlife_catc_ed';
		var modal = document.getElementById('smlife-catc-modal');
		var mbody = modal ? modal.querySelector('.pane-edit') : null;
		var mtitle = document.getElementById('smlife-catc-mtitle');
		var frame = document.getElementById('smlife-catc-frame');
		var pmsg = document.getElementById('smlife-catc-pmsg');
		var cell = null;   /* o dang mo */

		/* Doc / ghi noi dung cua trinh soan thao, dung duoc o ca tab Truc quan
		   lan tab Ma. Tab nao dang mo thi lay dung cho do. */
		function edGet() {
			var ta = document.getElementById(ED);
			var ed = window.tinymce ? tinymce.get(ED) : null;
			if (ed && !ed.isHidden()) { return ed.getContent(); }
			return ta ? ta.value : '';
		}
		function edSet(v) {
			var ta = document.getElementById(ED);
			var ed = window.tinymce ? tinymce.get(ED) : null;
			if (ta) { ta.value = v; }
			if (ed) { ed.setContent(v); ed.undoManager.clear(); }
		}

		function openBox(btn) {
			if (!btn || !modal) { return; }
			if (cell) { sync(); }
			cell = btn.parentNode;
			var ta = cell.querySelector('.catc-src');
			if (!ta) { cell = null; return; }
			mtitle.textContent = btn.getAttribute('data-title') || 'Soạn nội dung';
			edSet(ta.value);
			guard(ta.value);
			tab('edit');
			modal.classList.remove('is-off');
			document.body.classList.add('smlife-catc-lock');
			var ed = window.tinymce ? tinymce.get(ED) : null;
			if (ed && !ed.isHidden()) { ed.focus(); }
		}

		/* Tra noi dung ve dung o cua no. Goi ca khi dong lan khi doi o,
		   nen khong bao gio mat chu du bam dong bang cach nao. */
		function sync() {
			if (!cell) { return; }
			var ta = cell.querySelector('.catc-src');
			if (!ta) { return; }
			ta.value = edGet();
			var sum = cell.querySelector('.catc-sum');
			var btn = cell.querySelector('.catc-edit');
			if (sum) { sum.textContent = brief(ta.value); }
			if (btn) { btn.textContent = ta.value.trim() ? '✎ Sửa nội dung' : '✎ Soạn nội dung'; }
			var tr = cell.closest ? cell.closest('tr') : null;
			if (tr) { mark(tr); }
		}

		function closeBox() {
			if (!modal || modal.classList.contains('is-off')) { return; }
			sync();
			cell = null;
			modal.classList.add('is-off');
			document.body.classList.remove('smlife-catc-lock');
			if (frame) { frame.removeAttribute('src'); }
		}

		/* Noi dung cua UX Builder thi bat buoc dung tab Ma.
		   Tab Truc quan se boc moi dong vao the <p> va lam vo cau truc. */
		var BUILDER = /\[(row|col|col_inner|row_inner|section|ux_[a-z_]+|featured_box|gap|block)[\s\]]/i;
		function guard(v) {
			var warn = document.getElementById('smlife-catc-warn');
			var on = BUILDER.test(v || '');
			if (warn) {
				warn.style.display = on ? '' : 'none';
				var m = warn.querySelector('.msg');
				if (m) { m.innerHTML = ''; }
			}
			if (on && window.switchEditors) {
				try { switchEditors.go(ED, 'html'); } catch (err) {}
			}
		}

		/* Tao UX Block tu noi dung dang soan, roi gan luon vao o nay. */
		var mkbtn = document.getElementById('smlife-catc-mk');
		if (mkbtn) {
			mkbtn.addEventListener('click', function (e) {
				e.preventDefault();
				if (!cell || !CFG.blk) { return; }
				var warn = document.getElementById('smlife-catc-warn');
				var msg = warn ? warn.querySelector('.msg') : null;
				var name = (mtitle.textContent || 'Nội dung danh mục').trim();
				mkbtn.disabled = true;
				if (msg) { msg.textContent = 'Đang tạo...'; }
				var fd = new FormData();
				fd.append('action', 'smlife_catc_mkblock');
				fd.append('nonce', CFG.nonce);
				fd.append('name', name);
				fd.append('html', edGet());
				window.fetch(CFG.ajax, { method: 'POST', body: fd, credentials: 'same-origin' })
					.then(function (r) { return r.json(); })
					.then(function (j) {
						mkbtn.disabled = false;
						if (!j || !j.success || !j.data) {
							if (msg) { msg.textContent = (j && j.data && j.data.msg) ? j.data.msg : 'Chưa tạo được.'; }
							return;
						}
						addBlock(j.data.slug, j.data.title);
						var sel = cell.parentNode.querySelector('.catc-blk');
						var ta = cell.querySelector('.catc-src');
						if (ta) { ta.value = ''; }
						if (sel) {
							sel.value = j.data.slug;
							sel.classList.add('has');
							cell.style.display = 'none';
						}
						var tr = cell.closest ? cell.closest('tr') : null;
						cell = null;
						if (tr) { mark(tr); }
						if (msg) {
							msg.innerHTML = 'Xong. Đã gán block <b>' + esc(j.data.title) + '</b> vào ô này. ' +
								'<a href="' + j.data.edit + '" target="_blank" rel="noopener">Mở bằng UX Builder</a>. ' +
								'Nhớ bấm Lưu toàn bộ.';
						}
					})
					.catch(function () {
						mkbtn.disabled = false;
						if (msg) { msg.textContent = 'Chưa tạo được, thử lại giúp tao.'; }
					});
			});
		}
		function esc(t) { var d = document.createElement('span'); d.textContent = t; return d.innerHTML; }

		/* Block moi phai co mat o moi o chon, khong thi lan sau khong thay. */
		function addBlock(slug, title) {
			var sels = tb.querySelectorAll('select.catc-blk');
			for (var i = 0; i < sels.length; i++) {
				if (sels[i].querySelector('option[value="' + slug + '"]')) { continue; }
				var grp = sels[i].querySelector('optgroup');
				var op = document.createElement('option');
				op.value = slug;
				op.textContent = title;
				if (grp) { grp.appendChild(op); } else { sels[i].appendChild(op); }
			}
		}

		function brief(v) {
			v = (v || '').trim();
			if (!v) { return 'chưa có nội dung'; }
			var bit = [];
			var n = (v.match(/<img[\s>]/gi) || []).length;
			if (n) { bit.push(n + ' ảnh'); }
			if (/\[[a-z0-9_-]+/i.test(v)) { bit.push('shortcode'); }
			var t = v.replace(/<[^>]*>/g, ' ').replace(/\s+/g, ' ').trim();
			if (t) { bit.push(t.length > 30 ? t.slice(0, 29) + '…' : t); }
			return bit.join(' · ');
		}

		/* Doi tab Soan thao / Xem thu. */
		function tab(name) {
			if (!modal) { return; }
			var i, t = modal.querySelectorAll('.tabs .tab');
			for (i = 0; i < t.length; i++) { t[i].classList.toggle('is-on', t[i].getAttribute('data-tab') === name); }
			modal.querySelector('.pane-edit').style.display = (name === 'edit') ? '' : 'none';
			modal.querySelector('.pane-prev').style.display = (name === 'prev') ? '' : 'none';
			if (name === 'prev') { draw(); }
		}

		/* Nho server dung thu noi dung roi tra ve mot dia chi xem thu.
		   Xem thu chay tren chinh giao dien web that nen thay dung mau ma,
		   font chu, khoang cach nhu khach se thay. */
		var busy = false;
		function draw() {
			if (!frame || busy) { return; }
			busy = true;
			if (pmsg) { pmsg.textContent = 'Đang dựng bản xem thử...'; }
			var fd = new FormData();
			fd.append('action', 'smlife_catc_prev');
			fd.append('nonce', CFG.nonce);
			fd.append('html', edGet());
			window.fetch(CFG.ajax, { method: 'POST', body: fd, credentials: 'same-origin' })
				.then(function (r) { return r.json(); })
				.then(function (j) {
					busy = false;
					if (j && j.success && j.data && j.data.url) {
						frame.src = j.data.url;
						if (pmsg) { pmsg.textContent = ''; }
					} else if (pmsg) {
						pmsg.textContent = 'Chưa dựng được bản xem thử, thử lại giúp tao.';
					}
				})
				.catch(function () {
					busy = false;
					if (pmsg) { pmsg.textContent = 'Chưa dựng được bản xem thử, thử lại giúp tao.'; }
				});
		}

		if (modal) {
			modal.addEventListener('click', function (e) {
				var el = e.target;
				if (el === modal || (el.closest && el.closest('.catc-close'))) { e.preventDefault(); closeBox(); return; }
				var tb = el.closest ? el.closest('.tabs .tab') : null;
				if (tb) { e.preventDefault(); tab(tb.getAttribute('data-tab')); return; }
				var w = el.closest ? el.closest('[data-w]') : null;
				if (w) {
					e.preventDefault();
					var kind = w.getAttribute('data-w');
					if (kind === 're') { draw(); return; }
					var box = modal.querySelector('.pane-prev .frame');
					if (box) { box.classList.toggle('is-mb', kind === 'mb'); }
					var all = modal.querySelectorAll('.prevbar [data-w]');
					for (var i = 0; i < all.length; i++) {
						if (all[i].getAttribute('data-w') !== 're') { all[i].classList.toggle('is-on', all[i] === w); }
					}
				}
			});
			document.addEventListener('keydown', function (e) {
				if (e.key === 'Escape' && !modal.classList.contains('is-off')) { closeBox(); }
			});
		}

		/* Bam Luu toan bo khi khung soan dang mo thi van phai lay chu ve truoc. */
		var form = tb.closest ? tb.closest('form') : null;
		if (form) { form.addEventListener('submit', sync); }

		/* Loc nhanh theo ten danh muc + chi hien danh muc da cau hinh. */
		/* Loc nhanh theo ten danh muc + chi hien danh muc da cau hinh. */
		var find = document.getElementById('smlife-catc-find');
		var only = document.getElementById('smlife-catc-onlyset');
		function run() {
			var q = (find && find.value || '').toLowerCase().trim();
			var o = only && only.checked;
			var rows = tb.querySelectorAll('tbody tr');
			for (var i = 0; i < rows.length; i++) {
				var r = rows[i];
				var okName = !q || (r.getAttribute('data-name') || '').indexOf(q) !== -1;
				var okSet = !o || r.getAttribute('data-set') === '1';
				r.style.display = (okName && okSet) ? '' : 'none';
			}
		}
		if (find) { find.addEventListener('input', run); }
		if (only) { only.addEventListener('change', run); }
	})();
	</script>
	<?php
}

/* =====================================================================
 * 6b. XEM THU
 * Noi dung duoc dung thu tren chinh giao dien web that chu khong phai mo
 * phong trong trang quan tri, nen thay dung font chu, mau sac, khoang cach
 * nhu khach se thay. Cach lam: luu tam noi dung vao transient 5 phut roi
 * mo mot dia chi o giao dien truoc, o do CSS cua Flatsome moi duoc nap.
 * ===================================================================== */

add_action( 'wp_ajax_smlife_catc_prev', function () {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_send_json_error();
	}
	check_ajax_referer( 'smlife_catc_prev', 'nonce' );
	$html = isset( $_POST['html'] ) ? (string) wp_unslash( $_POST['html'] ) : '';
	$html = smlife_catc_clean_html( $html );
	$key  = wp_generate_password( 16, false, false );
	set_transient( 'smlife_catc_prev_' . $key, $html, 5 * MINUTE_IN_SECONDS );
	wp_send_json_success( array( 'url' => add_query_arg( 'smlife_catc_prev', $key, home_url( '/' ) ) ) );
}, 16 );

/* Chuyen noi dung dan tu UX Builder thanh mot UX Block that.
 * Day moi la cho dung cua noi dung kieu nay: sau nay mo bang UX Builder sua
 * duoc, va dung lai duoc o nhieu danh muc khac. */
add_action( 'wp_ajax_smlife_catc_mkblock', function () {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_send_json_error( array( 'msg' => 'Không đủ quyền.' ) );
	}
	check_ajax_referer( 'smlife_catc_prev', 'nonce' );

	if ( ! post_type_exists( 'blocks' ) ) {
		wp_send_json_error( array( 'msg' => 'Không tìm thấy UX Block của Flatsome.' ) );
	}
	$html = isset( $_POST['html'] ) ? (string) wp_unslash( $_POST['html'] ) : '';
	$html = smlife_catc_fix_sc( smlife_catc_clean_html( $html ) );
	if ( '' === trim( $html ) ) {
		wp_send_json_error( array( 'msg' => 'Nội dung đang trống.' ) );
	}
	$name = isset( $_POST['name'] ) ? sanitize_text_field( wp_unslash( $_POST['name'] ) ) : '';
	$name = ( '' !== trim( $name ) ) ? trim( $name ) : 'Nội dung danh mục';

	$id = wp_insert_post( array(
		'post_type'    => 'blocks',
		'post_status'  => 'publish',
		'post_title'   => $name,
		'post_content' => $html,
	), true );
	if ( is_wp_error( $id ) ) {
		wp_send_json_error( array( 'msg' => $id->get_error_message() ) );
	}
	$post = get_post( $id );
	wp_send_json_success( array(
		'slug'  => $post->post_name,
		'title' => $post->post_title,
		'edit'  => admin_url( 'post.php?post=' . (int) $id . '&action=edit' ),
	) );
} );

add_action( 'template_redirect', function () {
	if ( empty( $_GET['smlife_catc_prev'] ) ) {
		return;
	}
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}
	$key  = sanitize_key( wp_unslash( $_GET['smlife_catc_prev'] ) );
	$html = get_transient( 'smlife_catc_prev_' . $key );
	if ( false === $html ) {
		$html = '<p>Bản xem thử đã hết hạn. Bấm Tải lại giúp tao.</p>';
	}
	nocache_headers();
	echo '<!doctype html><html ' . get_language_attributes() . '><head><meta charset="' . esc_attr( get_bloginfo( 'charset' ) ) . '">';
	echo '<meta name="viewport" content="width=device-width, initial-scale=1">';
	echo '<meta name="robots" content="noindex, nofollow">';
	wp_head();
	echo '<style>body{background:#fff !important;padding:18px !important;margin:0 !important}';
	echo '.smlife-catc-prevwrap{max-width:100%}';
	echo '#wpadminbar,.header,.footer,#header,#footer,.off-canvas{display:none !important}</style>';
	echo '</head><body class="smlife-catc-prevbody"><div class="smlife-catc-prevwrap">';
	echo '<div class="smlife-catc-slot">' . smlife_catc_html( $html ) . '</div>';
	echo '</div>';
	wp_footer();
	echo '</body></html>';
	exit;
}, 1 );

/* =====================================================================
 * 7. LUU
 * ===================================================================== */
add_action( 'admin_post_smlife_catc_save', function () {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( 'Không đủ quyền.' );
	}
	check_admin_referer( 'smlife_catc_save', 'smlife_catc_nonce' );

	$slots = smlife_catc_slots();
	$in    = isset( $_POST['c'] ) && is_array( $_POST['c'] ) ? wp_unslash( $_POST['c'] ) : array();
	$hide  = isset( $_POST['hide_mb'] ) && is_array( $_POST['hide_mb'] ) ? wp_unslash( $_POST['hide_mb'] ) : array();

	/* 1.24.0: thieu moc cuoi form hoac so dong den it hon so dong da ve = du lieu bi cat */
	$so_ve = isset( $_POST['catc_so'] ) ? (int) $_POST['catc_so'] : -1;
	if ( empty( $_POST['catc_het'] ) || ( $so_ve >= 0 && count( $in ) < $so_ve ) ) {
		wp_safe_redirect( admin_url( 'admin.php?page=smlife-noi-dung-danh-muc&thieu=1' ) );
		exit;
	}

	$ok_modes = array_merge( array( 'off', 'custom' ), array_keys( smlife_catc_presets() ) );
	/* 1.24.0: gop vao du lieu cu thay vi dung lai tu dau. Danh muc khong co trong
	   lan gui nay (vd vua tao sau khi mo trang) giu nguyen cau hinh. */
	$cu       = smlife_catc_store();
	$cats     = ( isset( $cu['cats'] ) && is_array( $cu['cats'] ) ) ? $cu['cats'] : array();

	foreach ( $in as $tid => $row ) {
		$tid = (int) $tid;
		if ( ! $tid || ! is_array( $row ) ) {
			continue;
		}
		$mode = isset( $row['status_mode'] ) ? sanitize_key( $row['status_mode'] ) : 'off';
		if ( ! in_array( $mode, $ok_modes, true ) ) {
			$mode = 'off';
		}
		$col = isset( $row['status_color'] ) ? sanitize_hex_color( $row['status_color'] ) : '';
		$new = array(
			'status_mode'  => $mode,
			'status_text'  => isset( $row['status_text'] ) ? sanitize_text_field( $row['status_text'] ) : '',
			'status_color' => $col ? $col : '#16a34a',
		);
		$has = ( 'off' !== $mode && ! ( 'custom' === $mode && '' === trim( $new['status_text'] ) ) );
		foreach ( $slots as $k => $s ) {
			$raw = isset( $row[ $k ] ) ? (string) $row[ $k ] : '';
			$v   = ( SMLIFE_CATC_HTML === $raw ) ? SMLIFE_CATC_HTML : sanitize_title( $raw );

			/* Noi dung tu soan: giu HTML va shortcode, chi chan ma doc.
			   Ai duoc phep dang HTML tu do thi giu nguyen, con lai loc theo
			   chuan bai viet cua WordPress. */
			$h = isset( $row[ $k . '_html' ] ) ? (string) $row[ $k . '_html' ] : '';
			if ( '' !== trim( $h ) ) {
				$h = smlife_catc_clean_html( $h );
			}

			$new[ $k ]           = $v;
			$new[ $k . '_html' ] = trim( $h );

			/* Chon tu soan nhung bo trong thi coi nhu chua dat. */
			if ( SMLIFE_CATC_HTML === $v && '' === $new[ $k . '_html' ] ) {
				$new[ $k ] = '';
				continue;
			}
			if ( '' !== $new[ $k ] ) {
				$has = true;
			}
		}
		/* Danh muc trong hoan toan thi khong luu, cho du lieu gon. */
		if ( $has ) {
			$cats[ $tid ] = $new;
		} else {
			unset( $cats[ $tid ] );
		}
	}

	$hm = array();
	foreach ( $slots as $k => $s ) {
		$hm[ $k ] = empty( $hide[ $k ] ) ? 0 : 1;
	}

	/* autoload = false: option nay co the kha nang, chi trang san pham moi can.
	   De autoload thi WordPress phai tai no o moi request cua ca website. */
	update_option( 'smlife_catc', array(
		'cats'    => $cats,
		'hide_mb' => $hm,
		'kethua'  => empty( $_POST['kethua'] ) ? 0 : 1,
		'tt_chung' => empty( $_POST['tt_chung'] ) ? 0 : 1,
		'tt_mau'  => ( isset( $_POST['tt_mau'] ) && sanitize_hex_color( wp_unslash( $_POST['tt_mau'] ) ) )
			? sanitize_hex_color( wp_unslash( $_POST['tt_mau'] ) ) : '#15803d',
	), false );
	smlife_catc_flush();

	/* 1.24.0: khong xoa cache ngay, danh dau cho job dem (xem smlife_xoa_cache) */
	smlife_xoa_cache( 'Dynamic Shortcode' );

	wp_safe_redirect( admin_url( 'admin.php?page=smlife-noi-dung-danh-muc&saved=1' ) );
	exit;
} );
