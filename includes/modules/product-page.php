<?php
/**
 * SMLIFE Core - Module: Trang san pham
 *
 * Hai viec:
 *   1. Shortcode [smlife_tag_sp] - mot hang tag gon gang thay cho khoi
 *      "Khuyen mai chi trong hom nay" va nut "KHUYEN MAI, QUA TANG HOM NAY".
 *      Noi dung tag lay tu: tinh trang kho cua WooCommerce + 3 truong ACF
 *      button_qua_tang / button_tra_gop / button_extra (dung chung voi
 *      the san pham o trang danh muc, nen sua mot noi la doi ca hai).
 *   2. Chuan hoa co chu va khoang cach cho trang san pham: tieu de, gia,
 *      cac dong meta, khoi mo ta ngan, va phan noi dung ben duoi.
 *
 * Ghi chu ky thuat:
 *   - CSS gioi han trong body.single-product nen khong dung toi trang khac.
 *   - Tag dung flex-wrap: chat thi tu xuong dong, khong tran ra ngoai cot.
 *   - Khong dung !important tru cho hai cho Flatsome ghi size thang vao the.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/* =====================================================================
 * 1. CAI DAT
 * ===================================================================== */

function smlife_pp_defaults() {
	return array(
		'enabled'  => 1,
		/* Tag */
		'tag'      => 1,
		'kho'      => 1,
		't_kho'    => 'Còn hàng',
		't_het'    => 'Liên hệ đặt hàng',
		'acf'      => 1,
		'chinhsach' => "Bảo hành 12 tháng|lam\nĐổi trả trong 7 ngày|vang\nMiễn phí lắp đặt tại HCM|luc",
		/* Bo goc anh san pham */
		'bogoc'    => 1,
		'r_anh'    => 10,
		'r_thumb'  => 8,
		/* Co chu */
		'f_title'  => 26,
		'f_price'  => 30,
		'f_meta'   => 14.5,
		'gap_gia'  => 22,
		'f_desc'   => 14.5,
		/* Noi dung ben duoi */
		'bien_the' => 14,
		'nhan_rong' => 64,
		/* Bang thong so trong tab mo ta */
		'bang'     => 1,
		'bang_w'   => 38,
		'bang_mau' => '#e8658d',
		'bang_chu' => '#ffffff',
		'bang_nhat' => '#fff2f7',
		'bang_xam' => '#eceef0',
		'duoi'     => 1,
		'f_duoi'   => 15,
	);
}

function smlife_pp_opt() {
	$o = get_option( 'smlife_pp', array() );
	return is_array( $o ) ? array_merge( smlife_pp_defaults(), $o ) : smlife_pp_defaults();
}

/* =====================================================================
 * 2. SHORTCODE [smlife_tag_sp]
 * ===================================================================== */

/* Doc o textarea "tag chinh sach" thanh mang. Moi dong: Noi dung|mau */
function smlife_pp_colors() {
	return array( 'luc', 'hong', 'lam', 'vang', 'xam' );
}

function smlife_pp_policy_list( $o ) {
	$raw = isset( $o['chinhsach'] ) ? (string) $o['chinhsach'] : '';
	if ( '' === trim( $raw ) ) {
		return array();
	}
	$ok   = smlife_pp_colors();
	$list = array();
	foreach ( preg_split( '/\r\n|\r|\n/', $raw ) as $line ) {
		$line = trim( $line );
		if ( '' === $line ) {
			continue;
		}
		$parts = explode( '|', $line );
		$text  = trim( $parts[0] );
		if ( '' === $text ) {
			continue;
		}
		$mau = isset( $parts[1] ) ? strtolower( trim( $parts[1] ) ) : 'luc';
		if ( ! in_array( $mau, $ok, true ) ) {
			$mau = 'luc';
		}
		$list[] = array( 'text' => $text, 'mau' => $mau );
		if ( count( $list ) >= 12 ) {
			break;
		}
	}
	return $list;
}

function smlife_pp_tag_sp() {
	$o = smlife_pp_opt();
	if ( empty( $o['enabled'] ) || empty( $o['tag'] ) ) {
		return '';
	}

	global $product;
	$p = $product;
	if ( ! $p && function_exists( 'wc_get_product' ) ) {
		$p = wc_get_product( get_the_ID() );
	}
	if ( ! $p || ! is_a( $p, 'WC_Product' ) ) {
		return '';
	}

	$out = '';

	if ( ! empty( $o['kho'] ) ) {
		if ( $p->is_in_stock() ) {
			$out .= '<span class="t t-kho">' . esc_html( $o['t_kho'] ) . '</span>';
		} else {
			$out .= '<span class="t t-het">' . esc_html( $o['t_het'] ) . '</span>';
		}
	}

	/* Tag rieng cua tung san pham, lay tu ACF - dung chung voi the o trang danh muc */
	if ( ! empty( $o['acf'] ) && function_exists( 'get_field' ) ) {
		$map = array(
			't-1' => 'button_qua_tang',
			't-2' => 'button_tra_gop',
			't-3' => 'button_extra',
		);
		foreach ( $map as $cls => $field ) {
			$v = get_field( $field, $p->get_id() );
			if ( is_string( $v ) && '' !== trim( $v ) ) {
				$out .= '<span class="t ' . esc_attr( $cls ) . '">' . esc_html( trim( $v ) ) . '</span>';
			}
		}
	}

	/* Tag chinh sach - giong nhau o moi san pham, soan trong trang cai dat */
	foreach ( smlife_pp_policy_list( $o ) as $tag ) {
		$out .= '<span class="t t-' . esc_attr( $tag['mau'] ) . '">' . esc_html( $tag['text'] ) . '</span>';
	}

	if ( '' === $out ) {
		return '';
	}
	return '<div class="smlife-tagsp">' . $out . '</div>';
}
add_shortcode( 'smlife_tag_sp', 'smlife_pp_tag_sp' );

/* =====================================================================
 * 3. CSS TRANG SAN PHAM
 * ===================================================================== */

add_action( 'wp_head', 'smlife_pp_css', 99 );
function smlife_pp_css() {
	if ( ! function_exists( 'is_product' ) || ! is_product() ) {
		return;
	}
	$o = smlife_pp_opt();
	if ( empty( $o['enabled'] ) ) {
		return;
	}

	$ft = (float) $o['f_title'];
	$fp = (float) $o['f_price'];
	$fm = (float) $o['f_meta'];
	$fd = (float) $o['f_desc'];
	$fu = (float) $o['f_duoi'];

	$c  = '';

	/* --- Tag --- */
	$c .= '.smlife-tagsp{display:flex;flex-wrap:wrap;gap:8px;margin:14px 0 0}';
	$c .= '.smlife-tagsp .t{display:inline-flex;align-items:center;gap:7px;font-size:13.5px;font-weight:600;line-height:1;padding:8px 14px;border-radius:999px;border:1px solid transparent;white-space:nowrap}';
	$c .= '.smlife-tagsp .t-kho{background:#eaf7ef;border-color:#cfead9;color:#1b7a43}';
	$c .= '.smlife-tagsp .t-kho::before{content:"";display:block;width:7px;height:7px;border-radius:50%;background:#22a45d}';
	$c .= '.smlife-tagsp .t-het{background:#f5f5f5;border-color:#e4e4e4;color:#767676}';
	$c .= '.smlife-tagsp .t-het::before{content:"";display:block;width:7px;height:7px;border-radius:50%;background:#aaa}';
	$c .= '.smlife-tagsp .t-1{background:#fff0f5;border-color:#ffd4e3;color:#e01f5d}';
	$c .= '.smlife-tagsp .t-2{background:#eef6fd;border-color:#cfe4f7;color:#1667a8}';
	$c .= '.smlife-tagsp .t-3{background:#fff7e8;border-color:#ffe2ae;color:#a4680a}';
	/* Mau cho tag chinh sach */
	$c .= '.smlife-tagsp .t-luc{background:#eaf7ef;border-color:#cfead9;color:#1b7a43}';
	$c .= '.smlife-tagsp .t-hong{background:#fff0f5;border-color:#ffd4e3;color:#e01f5d}';
	$c .= '.smlife-tagsp .t-lam{background:#eef6fd;border-color:#cfe4f7;color:#1667a8}';
	$c .= '.smlife-tagsp .t-vang{background:#fff7e8;border-color:#ffe2ae;color:#a4680a}';
	$c .= '.smlife-tagsp .t-xam{background:#f5f5f5;border-color:#e4e4e4;color:#5f5f5f}';

	/* --- Tieu de + meta --- */
	$c .= '.single-product h1.product-title{font-size:' . $ft . 'px;line-height:1.3;font-weight:700;margin:0 0 12px}';
	$c .= '.single-product .product-info .star-rating,.single-product .product-info .wp-rv-rating{vertical-align:middle}';
	$c .= '.single-product .smlife-catc-tt{font-size:' . $fm . 'px;line-height:1.6;margin:0 0 12px}';

	/* --- Gia --- */
	$c .= '.single-product .product-page-price{font-size:' . $fp . 'px;line-height:1.2;font-weight:800;margin:0 0 6px}';
	$c .= '.single-product .product-page-price del{font-size:' . round( $fp * 0.58, 1 ) . 'px;font-weight:500;opacity:.55;margin-right:8px}';
	$c .= '.single-product .product-page-price .gia-tu-do-dam{font-size:' . round( $fp * 0.62, 1 ) . 'px;font-weight:600;color:#2b2b2b}';
	$c .= '.single-product .product-price-container{margin:0 0 ' . (float) $o['gap_gia'] . 'px}';

	/* --- Dong ghi chu: VAT, ma san pham --- */
	$c .= '.single-product .product-info p{font-size:' . $fm . 'px;line-height:1.6}';
	$c .= '.single-product .product-info .featured-box p{margin:0 0 6px}';
	$c .= '.single-product .smlife-pp-vat{color:#00a0e0;font-weight:600;margin:0 0 4px}';
	$c .= '.single-product .smlife-pp-sku{color:#5a5a5a;margin:0 0 2px}';
	$c .= '.single-product .smlife-pp-sku strong{color:#1f1f1f}';
	/* Nut copy ma SP do snippet chen vao sau the <p>, ep nam cung hang */
	$c .= '.single-product .smlife-pp-sku{display:inline-block;vertical-align:middle}';
	$c .= '.single-product #sm-copy-btn{vertical-align:middle;margin-top:-2px}';

	/* --- Mo ta ngan --- */
	/* Chi dat khoang cach va co chu. Khung xam bo goc la cua the <pre> trong
	   noi dung, mo ta ngan khong boc them khung nua keo thanh hai lop long nhau. */
	$c .= '.single-product .product-short-description{margin:16px 0 0}';
	$c .= '.single-product .product-short-description p{font-size:' . $fd . 'px;line-height:1.75;margin:0 0 4px}';
	$c .= '.single-product .product-short-description p:last-child{margin-bottom:0}';

	/* --- Khoi mua hang --- */
	$c .= '.single-product form.cart{margin:16px 0 0}';
	/* Ten bien the va o chon dang dinh sat nhau, tach ra cho de doc */
	$gb = (float) $o['bien_the'];
	$c .= '.single-product .variations_form .variations th.label{padding:7px ' . $gb . 'px 7px 0;vertical-align:middle}';
	$c .= '.single-product .variations_form .variations td.value{padding:7px 0}';
	$c .= '.single-product .variations label{font-size:13.5px;font-weight:600;color:#5a5a5a;margin:0}';
	/* Ten thuoc tinh dai (vd "Chọn khổ Nệm") lam cot trai phinh ra, day o chon
	   xuong dong. Theme Flatsome ep white-space:nowrap nen chu khong bao gio
	   xuong dong duoc - go bo rang buoc do, cho chu ngat dong va chi chiem be
	   ngang vua du. nhan_rong = 0 la hep nhat co the. */
	$nr = (int) $o['nhan_rong'];
	$c .= '.single-product table.variations th.label label{display:block;white-space:normal !important;width:min-content;';
	$c .= 'min-width:' . ( $nr > 0 ? $nr . 'px' : '3.2em' ) . ';line-height:1.25;margin:0}';
	/* Flatsome tha noi link Xoa len tren hang chon mau, keo xuong duoi */
	$c .= '.single-product .reset_variations{float:none;position:static;display:inline-block;margin:8px 0 0;font-size:12px;color:#9a9a9a;text-transform:none}';
	$c .= '.single-product .variations_form .reset_variations:hover{color:' . smlife_mau() . '}';

	/* --- Noi dung ben duoi --- */
	if ( ! empty( $o['duoi'] ) ) {
		$c .= '.single-product .woocommerce-Tabs-panel,.single-product .product-footer .entry-content{font-size:' . $fu . 'px;line-height:1.75}';
		$c .= '.single-product .woocommerce-Tabs-panel p,.single-product .product-footer .entry-content p{font-size:' . $fu . 'px;line-height:1.75}';
		$c .= '.single-product .woocommerce-Tabs-panel li,.single-product .product-footer .entry-content li{font-size:' . $fu . 'px;line-height:1.7}';
		$c .= '.single-product .woocommerce-Tabs-panel h2{font-size:' . round( $fu * 1.42, 1 ) . 'px;line-height:1.35}';
		$c .= '.single-product .woocommerce-Tabs-panel h3{font-size:' . round( $fu * 1.2, 1 ) . 'px;line-height:1.4}';
		$c .= '.single-product .product-tabs>li>a{font-size:' . $fu . 'px}';
	}

	/* --- Bang thong so ky thuat trong tab mo ta ---
	   Bang nay go tay trong noi dung tung san pham, moi o deu co style viet
	   thang vao the (border den, nen hong). Style inline thang moi selector,
	   nen cho nay buoc phai dung !important. */
	if ( ! empty( $o['bang'] ) ) {
		$bw   = (float) $o['bang_w'];
		$bm   = sanitize_hex_color( $o['bang_mau'] ) ? $o['bang_mau'] : '#e8658d';
		$bn   = sanitize_hex_color( $o['bang_nhat'] ) ? $o['bang_nhat'] : '#fff2f7';
		$bx   = sanitize_hex_color( $o['bang_xam'] ) ? $o['bang_xam'] : '#eceef0';
		$bc   = ( isset( $o['bang_chu'] ) && sanitize_hex_color( $o['bang_chu'] ) ) ? $o['bang_chu'] : '#ffffff';
		$sel  = '.single-product .woocommerce-Tabs-panel--description table';
		/* Khung ngoai */
		$c .= $sel . '{width:100% !important;background:#fff !important;border:1px solid ' . $bn . ' !important;border-collapse:separate !important;border-spacing:0 !important;border-radius:12px;margin:0 0 22px;overflow:hidden}';
		/* O: ngan nhau bang ke trang thay vi vien */
		$c .= $sel . ' td{border:0 !important;border-bottom:2px solid #fff !important;padding:12px 16px !important;vertical-align:top;line-height:1.6}';
		$c .= $sel . ' tr:last-child td{border-bottom:0 !important}';
		/* Cot trai: ten thong so, nen hong nhat */
		$c .= $sel . ' tr>td:first-child{width:' . $bw . '%;background:' . $bn . ' !important;color:#8a4a60;font-weight:600;border-right:2px solid #fff !important}';
		/* Cot phai: chi tiet, nen xam */
		$c .= $sel . ' tr>td:last-child{background:' . $bx . ' !important;color:#2b2b2b}';
		/* Hang tieu de nhom (td colspan) - dat SAU first-child de de len */
		$c .= $sel . ' tr>td[colspan]{width:auto !important;background:' . $bm . ' !important;color:' . $bc . ' !important;font-weight:700;text-transform:uppercase;letter-spacing:.5px;font-size:.86em;border-right:0 !important;border-bottom:0 !important;padding-top:10px !important;padding-bottom:10px !important}';
		/* Dien thoai */
		$c .= '@media (max-width:549px){' . $sel . ' td{padding:9px 11px !important;font-size:.94em}' . $sel . ' tr>td:first-child{width:42%}}';
	}

	/* --- Bo goc anh san pham --- */
	if ( ! empty( $o['bogoc'] ) ) {
		$ra = (float) $o['r_anh'];
		$rt = (float) $o['r_thumb'];
		$c .= '.single-product .woocommerce-product-gallery__image img,.single-product .product-gallery img.wp-post-image{border-radius:' . $ra . 'px}';
		/* Anh chon bien the dung chung class voi anh nho cua gallery nen phai
		   tru han ra bang :not(). KHONG duoc ep border-radius:0 cho chung: o
		   chon mau la hinh tron, chinh cai bo goc cua anh tao ra hinh tron do,
		   ep ve 0 la anh vuong nam trong vong tron. Tru ra thi theme tu lo. */
		$c .= '.single-product img.attachment-woocommerce_gallery_thumbnail:not(.ux-swatch__img){border-radius:' . $rt . 'px}';
		$c .= '.single-product .product-thumbnails img:not(.ux-swatch__img){border-radius:' . $rt . 'px}';
	}

	/* --- Dien thoai --- */
	$c .= '@media (max-width:549px){';
	$c .= '.single-product h1.product-title{font-size:' . round( $ft * 0.78, 1 ) . 'px}';
	$c .= '.single-product .product-page-price{font-size:' . round( $fp * 0.8, 1 ) . 'px}';
	$c .= '.smlife-tagsp{gap:6px;margin-top:12px}';
	$c .= '.smlife-tagsp .t{font-size:12.5px;padding:7px 11px}';
	$c .= '}';

	echo '<style id="smlife-pp">' . $c . '</style>'; // phpcs:ignore
}

/* =====================================================================
 * 5. KHOI PHUC SAO LUU LAYOUT (phan con lai cua cong cu layout da go bo)
 * Cong cu quet/reset layout da bo theo yeu cau - chu site tu ra soat tay.
 * Giu lai DUY NHAT nut khoi phuc cho 2 ban sao luu da tao truoc do; khoi
 * phuc xong thi xoa ban sao luu va nut tu bien mat.
 * ===================================================================== */

add_action( 'admin_post_smlife_pp_catlayout_restore', function () {
	if ( ! current_user_can( 'manage_options' ) ) { wp_die( 'Không đủ quyền.' ); }
	check_admin_referer( 'smlife_pp_layout', 'smlife_pp_layout_nonce' );
	$bak = get_option( 'smlife_pp_catlayout_backup', array() );
	$n   = 0;
	if ( isset( $bak['data'] ) && is_array( $bak['data'] ) ) {
		foreach ( $bak['data'] as $tid => $v ) {
			update_term_meta( (int) $tid, 'cat_product_block', $v );
			$n++;
		}
	}
	delete_option( 'smlife_pp_catlayout_backup' );
	/* Khoi phuc layout la thao tac cuu ho, can thay ngay */
	$GLOBALS['smlife_co_doi'] = true;
	smlife_xoa_cache( 'Trang sản phẩm', true );
	wp_safe_redirect( admin_url( 'admin.php?page=smlife-trang-san-pham&lt_cat_restored=' . $n ) );
	exit;
} );

add_action( 'admin_post_smlife_pp_layout_restore', function () {
	if ( ! current_user_can( 'manage_options' ) ) { wp_die( 'Không đủ quyền.' ); }
	check_admin_referer( 'smlife_pp_layout', 'smlife_pp_layout_nonce' );
	$bak = get_option( 'smlife_pp_layout_backup', array() );
	$n   = 0;
	if ( isset( $bak['data'], $bak['key'] ) && is_array( $bak['data'] ) && preg_match( '/^[A-Za-z0-9_\-]{1,64}$/', (string) $bak['key'] ) ) {
		foreach ( $bak['data'] as $pid => $v ) {
			update_post_meta( (int) $pid, $bak['key'], $v );
			$n++;
		}
	}
	delete_option( 'smlife_pp_layout_backup' );
	/* Khoi phuc layout la thao tac cuu ho, can thay ngay */
	$GLOBALS['smlife_co_doi'] = true;
	smlife_xoa_cache( 'Trang sản phẩm', true );
	wp_safe_redirect( admin_url( 'admin.php?page=smlife-trang-san-pham&lt_restored=' . $n ) );
	exit;
} );

function smlife_pp_layout_card() {
	if ( isset( $_GET['lt_cat_restored'] ) ) {
		echo '<div class="notice notice-success is-dismissible"><p>Đã khôi phục layout cho <b>' . (int) $_GET['lt_cat_restored'] . '</b> danh mục. Bản sao lưu đã dùng xong và được dọn.</p></div>';
	}
	if ( isset( $_GET['lt_restored'] ) ) {
		echo '<div class="notice notice-success is-dismissible"><p>Đã khôi phục dữ liệu cho <b>' . (int) $_GET['lt_restored'] . '</b> sản phẩm. Bản sao lưu đã dùng xong và được dọn.</p></div>';
	}
	$pbak = get_option( 'smlife_pp_layout_backup', array() );
	$cbak = get_option( 'smlife_pp_catlayout_backup', array() );
	$has_p = isset( $pbak['data'] ) && is_array( $pbak['data'] ) && $pbak['data'];
	$has_c = isset( $cbak['data'] ) && is_array( $cbak['data'] ) && $cbak['data'];
	if ( ! $has_p && ! $has_c ) {
		return;
	}
	echo '<div class="smlife-card" style="margin-top:18px"><h2>Khôi phục dữ liệu sao lưu</h2><div class="in">';
	echo '<p>Còn bản sao lưu từ công cụ layout trước đây. Bấm khôi phục để trả dữ liệu về như trước khi chạy công cụ; khôi phục xong nút sẽ tự biến mất.</p>';
	if ( $has_p ) {
		echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '" style="margin:0 0 8px">';
		echo '<input type="hidden" name="action" value="smlife_pp_layout_restore" />';
		wp_nonce_field( 'smlife_pp_layout', 'smlife_pp_layout_nonce' );
		submit_button( 'Khôi phục ' . count( $pbak['data'] ) . ' sản phẩm (khóa ' . esc_html( isset( $pbak['key'] ) ? $pbak['key'] : '' ) . ', lúc ' . esc_html( isset( $pbak['time'] ) ? $pbak['time'] : '' ) . ')', 'primary', 'submit', false );
		echo '</form>';
	}
	if ( $has_c ) {
		echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '" style="margin:0">';
		echo '<input type="hidden" name="action" value="smlife_pp_catlayout_restore" />';
		wp_nonce_field( 'smlife_pp_layout', 'smlife_pp_layout_nonce' );
		submit_button( 'Khôi phục ' . count( $cbak['data'] ) . ' danh mục (bản lưu ' . esc_html( isset( $cbak['time'] ) ? $cbak['time'] : '' ) . ')', 'primary', 'submit', false );
		echo '</form>';
	}
	echo '</div></div>';
}

/* =====================================================================
 * 4. TRANG CAI DAT
 * ===================================================================== */

add_action( 'admin_menu', function () {
	add_submenu_page(
		'smlife-core',
		'Trang sản phẩm',
		'Trang sản phẩm',
		'manage_options',
		'smlife-trang-san-pham',
		'smlife_pp_page'
	);
}, 10 );

function smlife_pp_page() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}
	$o = smlife_pp_opt();
	echo '<div class="wrap smlife-wrap">';
	echo '<div class="smc-hd"><h1>Trang sản phẩm</h1>';
	echo '<p>Hàng tag dưới khối mua hàng và cỡ chữ chung của trang sản phẩm.</p></div>';
	if ( isset( $_GET['updated'] ) ) {
		echo '<div class="notice notice-success is-dismissible"><p>Đã lưu.</p></div>';
	}
	echo '<div class="smlife-card">';
	echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '">';
	echo '<input type="hidden" name="action" value="smlife_pp_save" />';
	wp_nonce_field( 'smlife_pp_save', 'smlife_pp_nonce' );

	echo '<table class="form-table" role="presentation">';
	echo '<tr><th scope="row">Bật module</th><td><label><input type="checkbox" name="pp[enabled]" value="1" ' . checked( 1, $o['enabled'], false ) . ' /> Dùng cỡ chữ chuẩn và hàng tag cho trang sản phẩm</label></td></tr>';

	echo '<tr><th scope="row" colspan="2"><h2 style="margin:18px 0 0">Hàng tag</h2></th></tr>';
	echo '<tr><th scope="row">Hiện hàng tag</th><td><label><input type="checkbox" name="pp[tag]" value="1" ' . checked( 1, $o['tag'], false ) . ' /> Bật shortcode <code>[smlife_tag_sp]</code></label><p class="description">Dán <code>[smlife_tag_sp]</code> vào Product Layout, chỗ muốn hiện tag. Nội dung lấy từ 3 trường ACF <code>button_qua_tang</code>, <code>button_tra_gop</code>, <code>button_extra</code> - dùng chung với thẻ sản phẩm ở trang danh mục.</p></td></tr>';
	echo '<tr><th scope="row">Hiện tình trạng kho</th><td><label><input type="checkbox" name="pp[kho]" value="1" ' . checked( 1, $o['kho'], false ) . ' /> Thêm tag tình trạng kho ở đầu hàng</label></td></tr>';
	echo '<tr><th scope="row">Chữ khi còn hàng</th><td><input type="text" name="pp[t_kho]" value="' . esc_attr( $o['t_kho'] ) . '" class="regular-text" /></td></tr>';
	echo '<tr><th scope="row">Chữ khi hết hàng</th><td><input type="text" name="pp[t_het]" value="' . esc_attr( $o['t_het'] ) . '" class="regular-text" /></td></tr>';

	echo '<tr><th scope="row">Tag riêng của sản phẩm</th><td><label><input type="checkbox" name="pp[acf]" value="1" ' . checked( 1, $o['acf'], false ) . ' /> Lấy 3 trường ACF của từng sản phẩm</label></td></tr>';
	echo '<tr><th scope="row">Tag chính sách</th><td>';
	echo '<textarea name="pp[chinhsach]" rows="6" class="large-text code">' . esc_textarea( $o['chinhsach'] ) . '</textarea>';
	echo '<p class="description">Các tag này hiện ở <b>mọi sản phẩm</b>, dùng để khách nắm nhanh chính sách SMLIFE. Mỗi dòng một tag, viết theo mẫu <code>Nội dung|màu</code>. Màu chọn trong: <code>luc</code> (xanh lá), <code>hong</code>, <code>lam</code> (xanh dương), <code>vang</code>, <code>xam</code>. Không ghi màu thì mặc định là <code>luc</code>. Tối đa 12 tag.</p>';
	echo '<p class="description">Ví dụ: <code>Miễn phí lắp đặt tại HCM|luc</code></p>';
	echo '</td></tr>';

	echo '<tr><th scope="row">Bề ngang tên biến thể</th><td><input type="number" step="1" min="0" max="200" name="pp[nhan_rong]" value="' . esc_attr( $o['nhan_rong'] ) . '" class="small-text" /> px <span class="description">Tên thuộc tính dài sẽ tự ngắt dòng trong bề ngang này, để ô chọn không bị đẩy xuống. 64px thường cho 2 dòng gọn. Điền <code>0</code> là hẹp nhất có thể (mỗi chữ một dòng).</span></td></tr>';
	echo '<tr><th scope="row">Cách tên biến thể</th><td><input type="number" step="1" min="0" max="40" name="pp[bien_the]" value="' . esc_attr( $o['bien_the'] ) . '" class="small-text" /> px <span class="description">Khoảng hở giữa tên thuộc tính (Màu gỗ...) và các ô chọn.</span></td></tr>';

	echo '<tr><th scope="row" colspan="2"><h2 style="margin:18px 0 0">Bảng thông số kỹ thuật</h2></th></tr>';
	echo '<tr><th scope="row">Chuẩn hoá bảng</th><td><label><input type="checkbox" name="pp[bang]" value="1" ' . checked( 1, $o['bang'], false ) . ' /> Trình bày lại mọi bảng trong tab mô tả theo một kiểu chung</label><p class="description">Bỏ viền đen và nền hồng gõ sẵn trong nội dung, thay bằng cột trái nền xám, dòng kẻ mảnh, bo góc. Hàng tiêu đề nhóm (gộp 2 cột) được tô riêng. Không sửa nội dung, chỉ đổi cách hiển thị.</p></td></tr>';
	echo '<tr><th scope="row">Bề ngang cột trái</th><td><input type="number" step="1" min="20" max="60" name="pp[bang_w]" value="' . esc_attr( $o['bang_w'] ) . '" class="small-text" /> %</td></tr>';
	echo '<tr><th scope="row">Màu hàng tiêu đề nhóm</th><td><input type="text" name="pp[bang_mau]" value="' . esc_attr( $o['bang_mau'] ) . '" class="regular-text" style="max-width:120px" placeholder="#e8658d" /> <span class="description">Nền của hàng gộp 2 cột, ví dụ hàng "Thời gian giao hàng và bảo hành".</span></td></tr>';
	echo '<tr><th scope="row">Màu chữ hàng tiêu đề nhóm</th><td><input type="text" name="pp[bang_chu]" value="' . esc_attr( isset( $o['bang_chu'] ) ? $o['bang_chu'] : '#ffffff' ) . '" class="regular-text" style="max-width:120px" placeholder="#ffffff" /> <span class="description">Để <code>#ffffff</code> cho dải hồng đậm. Muốn kiểu nhẹ nhàng hơn thì đặt nền <code>#fde8ef</code> và chữ <code>#c2185b</code>.</span></td></tr>';
	echo '<tr><th scope="row">Nền cột tên thông số</th><td><input type="text" name="pp[bang_nhat]" value="' . esc_attr( $o['bang_nhat'] ) . '" class="regular-text" style="max-width:120px" placeholder="#fff2f7" /> <span class="description">Hồng nhạt. Cũng là màu viền ngoài của bảng.</span></td></tr>';
	echo '<tr><th scope="row">Nền cột chi tiết</th><td><input type="text" name="pp[bang_xam]" value="' . esc_attr( $o['bang_xam'] ) . '" class="regular-text" style="max-width:120px" placeholder="#eceef0" /> <span class="description">Xám nhẹ. Để <code>#ffffff</code> nếu muốn cột phải trắng.</span></td></tr>';

	echo '<tr><th scope="row" colspan="2"><h2 style="margin:18px 0 0">Ảnh sản phẩm</h2></th></tr>';
	echo '<tr><th scope="row">Bo góc ảnh</th><td><label><input type="checkbox" name="pp[bogoc]" value="1" ' . checked( 1, $o['bogoc'], false ) . ' /> Bo góc nhẹ cho ảnh trên trang sản phẩm</label></td></tr>';
	echo '<tr><th scope="row">Độ bo ảnh lớn</th><td><input type="number" step="1" min="0" max="30" name="pp[r_anh]" value="' . esc_attr( $o['r_anh'] ) . '" class="small-text" /> px</td></tr>';
	echo '<tr><th scope="row">Độ bo ảnh nhỏ</th><td><input type="number" step="1" min="0" max="30" name="pp[r_thumb]" value="' . esc_attr( $o['r_thumb'] ) . '" class="small-text" /> px <span class="description">Dải ảnh nhỏ bên cạnh.</span></td></tr>';

	echo '<tr><th scope="row" colspan="2"><h2 style="margin:18px 0 0">Cỡ chữ (px)</h2></th></tr>';
	echo '<tr><th scope="row">Tên sản phẩm</th><td><input type="number" step="0.5" min="16" max="42" name="pp[f_title]" value="' . esc_attr( $o['f_title'] ) . '" class="small-text" /></td></tr>';
	echo '<tr><th scope="row">Giá</th><td><input type="number" step="0.5" min="18" max="52" name="pp[f_price]" value="' . esc_attr( $o['f_price'] ) . '" class="small-text" /> <span class="description">Giá gạch và chữ "Giá chỉ" tự lấy theo tỉ lệ.</span></td></tr>';
	echo '<tr><th scope="row">Khoảng cách dưới giá</th><td><input type="number" step="1" min="0" max="60" name="pp[gap_gia]" value="' . esc_attr( $o['gap_gia'] ) . '" class="small-text" /> px <span class="description">Tăng lên nếu dòng VAT bị dính vào giá, giảm xuống nếu hở quá.</span></td></tr>';
	echo '<tr><th scope="row">Dòng ghi chú</th><td><input type="number" step="0.5" min="11" max="20" name="pp[f_meta]" value="' . esc_attr( $o['f_meta'] ) . '" class="small-text" /> <span class="description">Tình trạng, VAT, mã sản phẩm.</span></td></tr>';
	echo '<tr><th scope="row">Mô tả ngắn</th><td><input type="number" step="0.5" min="11" max="20" name="pp[f_desc]" value="' . esc_attr( $o['f_desc'] ) . '" class="small-text" /> <span class="description">Khối xám kích thước, chất liệu.</span></td></tr>';

	echo '<tr><th scope="row" colspan="2"><h2 style="margin:18px 0 0">Nội dung bên dưới</h2></th></tr>';
	echo '<tr><th scope="row">Chuẩn hoá</th><td><label><input type="checkbox" name="pp[duoi]" value="1" ' . checked( 1, $o['duoi'], false ) . ' /> Đồng bộ cỡ chữ phần mô tả chi tiết và các tab bên dưới</label></td></tr>';
	echo '<tr><th scope="row">Cỡ chữ bên dưới</th><td><input type="number" step="0.5" min="12" max="22" name="pp[f_duoi]" value="' . esc_attr( $o['f_duoi'] ) . '" class="small-text" /> <span class="description">Tiêu đề H2, H3 tự lấy theo tỉ lệ.</span></td></tr>';
	echo '</table>';

	submit_button( 'Lưu' );
	echo '</form></div>';
	smlife_pp_layout_card();
	echo '</div>';
}

add_action( 'admin_post_smlife_pp_save', function () {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( 'Không đủ quyền.' );
	}
	check_admin_referer( 'smlife_pp_save', 'smlife_pp_nonce' );
	$in  = isset( $_POST['pp'] ) && is_array( $_POST['pp'] ) ? wp_unslash( $_POST['pp'] ) : array();
	$d   = smlife_pp_defaults();
	$num = function ( $v, $min, $max, $def ) {
		$v = is_numeric( $v ) ? (float) $v : $def;
		return max( $min, min( $max, $v ) );
	};
	$new = array(
		'enabled' => empty( $in['enabled'] ) ? 0 : 1,
		'tag'     => empty( $in['tag'] ) ? 0 : 1,
		'kho'     => empty( $in['kho'] ) ? 0 : 1,
		't_kho'   => isset( $in['t_kho'] ) ? sanitize_text_field( $in['t_kho'] ) : $d['t_kho'],
		't_het'   => isset( $in['t_het'] ) ? sanitize_text_field( $in['t_het'] ) : $d['t_het'],
		'acf'     => empty( $in['acf'] ) ? 0 : 1,
		'chinhsach' => isset( $in['chinhsach'] ) ? sanitize_textarea_field( $in['chinhsach'] ) : $d['chinhsach'],
		'bien_the' => $num( isset( $in['bien_the'] ) ? $in['bien_the'] : null, 0, 40, $d['bien_the'] ),
		'nhan_rong' => $num( isset( $in['nhan_rong'] ) ? $in['nhan_rong'] : null, 0, 200, $d['nhan_rong'] ),
		'bang'    => empty( $in['bang'] ) ? 0 : 1,
		'bang_w'  => $num( isset( $in['bang_w'] ) ? $in['bang_w'] : null, 20, 60, $d['bang_w'] ),
		'bang_mau' => ( isset( $in['bang_mau'] ) && sanitize_hex_color( $in['bang_mau'] ) ) ? sanitize_hex_color( $in['bang_mau'] ) : $d['bang_mau'],
		'bang_chu' => ( isset( $in['bang_chu'] ) && sanitize_hex_color( $in['bang_chu'] ) ) ? sanitize_hex_color( $in['bang_chu'] ) : '#ffffff',
		'bang_nhat' => ( isset( $in['bang_nhat'] ) && sanitize_hex_color( $in['bang_nhat'] ) ) ? sanitize_hex_color( $in['bang_nhat'] ) : $d['bang_nhat'],
		'bang_xam' => ( isset( $in['bang_xam'] ) && sanitize_hex_color( $in['bang_xam'] ) ) ? sanitize_hex_color( $in['bang_xam'] ) : $d['bang_xam'],
		'bogoc'   => empty( $in['bogoc'] ) ? 0 : 1,
		'r_anh'   => $num( isset( $in['r_anh'] ) ? $in['r_anh'] : null, 0, 30, $d['r_anh'] ),
		'r_thumb' => $num( isset( $in['r_thumb'] ) ? $in['r_thumb'] : null, 0, 30, $d['r_thumb'] ),
		'f_title' => $num( isset( $in['f_title'] ) ? $in['f_title'] : null, 16, 42, $d['f_title'] ),
		'f_price' => $num( isset( $in['f_price'] ) ? $in['f_price'] : null, 18, 52, $d['f_price'] ),
		'f_meta'  => $num( isset( $in['f_meta'] ) ? $in['f_meta'] : null, 11, 20, $d['f_meta'] ),
		'gap_gia' => $num( isset( $in['gap_gia'] ) ? $in['gap_gia'] : null, 0, 60, $d['gap_gia'] ),
		'f_desc'  => $num( isset( $in['f_desc'] ) ? $in['f_desc'] : null, 11, 20, $d['f_desc'] ),
		'duoi'    => empty( $in['duoi'] ) ? 0 : 1,
		'f_duoi'  => $num( isset( $in['f_duoi'] ) ? $in['f_duoi'] : null, 12, 22, $d['f_duoi'] ),
	);
	update_option( 'smlife_pp', $new, false );
	wp_safe_redirect( admin_url( 'admin.php?page=smlife-trang-san-pham&updated=1' ) );
	exit;
} );

/* ===== Ha tong mau hong cua hang tieu de nhom (ban 1.9.0) =====
 * Mau cu #ee2468 la mau thuong hieu nguyen ban, do day mau nen nhin gat.
 * Doi mot lan sang #e8658d diu hon cho nhung site con dang de mau cu.
 * Chi chay dung mot lan, sau do ai doi lai gi thi giu nguyen cua ho.
 */
add_action( 'admin_init', function () {
	if ( get_option( 'smlife_pp_hamau' ) ) {
		return;
	}
	update_option( 'smlife_pp_hamau', 1, false );
	$o = get_option( 'smlife_pp', array() );
	if ( is_array( $o ) && isset( $o['bang_mau'] ) && '#ee2468' === strtolower( $o['bang_mau'] ) ) {
		$o['bang_mau'] = '#e8658d';
		update_option( 'smlife_pp', $o );
	}
} );
