<?php
/**
 * SMLIFE Core - Module: Nut Mua ngay
 * Them nut "Mua ngay" canh nut Them vao gio, mo popup dat hang nhanh.
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

/* ============================================================
 * PHẦN A - NÚT MUA NGAY (tạo nút + popup form)
 * ============================================================ */

/* ===== Cấu hình nút (SMLIFE Core > Nút Mua ngay) ===== */
function smlife_qb_opt() {
	$def = array(
		'label'     => 'MUA NGAY',
		'bg'        => '#2b95ec',
		'color'     => '#ffffff',
		'radius'    => 99,
		'fontsize'  => 0, // 0 = theo theme
		'fullwidth' => 0,
		'hide'      => 0,
		'h_pc'      => 34,
		'h_mb'      => 40,
		'gap'       => 8,
		'cart_label'=> '',
		'cart_bg'   => '',
		'cart_color'=> '#ffffff',
	);
	$s = get_option( 'smlife_qb', array() );
	return is_array( $s ) ? array_merge( $def, $s ) : $def;
}

/* 1. CHÈN NÚT "MUA NGAY" VÀO TRANG SẢN PHẨM (Simple & Variable) */
add_action( 'woocommerce_after_add_to_cart_button', 'smlife_add_quick_buy_button' );
function smlife_add_quick_buy_button() {
	global $product;

	if ( ! $product || ! $product->is_in_stock() ) {
		return;
	}

	$o = smlife_qb_opt();
	if ( ! empty( $o['hide'] ) ) {
		return;
	}

	$radius = (int) $o['radius'];
	$fs     = (int) $o['fontsize'] > 0 ? 'font-size:' . (int) $o['fontsize'] . 'px !important;' : '';
	$hpc    = max( 26, min( 70, (int) $o['h_pc'] ) );
	$hmb    = max( 26, min( 70, (int) $o['h_mb'] ) );
	$gap    = max( 0, min( 20, (int) $o['gap'] ) );
	$full   = ! empty( $o['fullwidth'] );

	echo '<button type="button" class="button alt purchase-form">' . esc_html( $o['label'] ) . '</button>';

	$c  = '';
	/* Hang nut: o so luong + 2 nut, khong bao gio rot xuong dong */
	$c .= '.woocommerce div.product form.cart .woocommerce-variation-add-to-cart,';
	$c .= '.woocommerce div.product form.cart:not(.variations_form){display:flex !important;flex-wrap:' . ( $full ? 'wrap' : 'nowrap' ) . ';align-items:center;gap:' . $gap . 'px;}';

	/* O so luong: ca 3 thanh phan cung chieu cao */
	$c .= '.woocommerce div.product form.cart .quantity,';
	$c .= '.woocommerce div.product form.cart .quantity.ux-quantity{flex:0 0 auto;margin:0 !important;height:' . $hpc . 'px;display:flex;align-items:center;overflow:hidden;}';
	$c .= '.woocommerce div.product form.cart .quantity .ux-quantity__button{flex:0 0 26px;width:26px !important;height:' . $hpc . 'px !important;min-height:' . $hpc . 'px !important;line-height:' . $hpc . 'px !important;padding:0 !important;font-size:15px;}';
	$c .= '.woocommerce div.product form.cart .quantity input.qty{flex:0 0 36px;width:36px !important;height:' . $hpc . 'px !important;min-height:' . $hpc . 'px !important;line-height:' . $hpc . 'px !important;margin:0 !important;padding:0 2px !important;text-align:center;font-size:14px;box-shadow:none;}';

	/* 2 nut chia deu phan con lai */
	$c .= '.woocommerce div.product form.cart .single_add_to_cart_button.button,';
	$c .= '.woocommerce div.product form.cart button.purchase-form{flex:1 1 0;min-width:0;margin:0 !important;';
	$c .= 'height:' . $hpc . 'px !important;min-height:' . $hpc . 'px !important;line-height:1.1 !important;';
	$c .= 'padding:0 10px !important;font-size:13px;letter-spacing:.3px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;';
	$c .= 'display:inline-flex;align-items:center;justify-content:center;border-radius:' . $radius . 'px !important;' . $fs . '}';

	/* Mau nut Mua ngay */
	$c .= '.woocommerce div.product form.cart button.purchase-form,';
	$c .= '.woocommerce div.product form.cart button.purchase-form:hover,';
	$c .= '.woocommerce div.product form.cart button.purchase-form:focus,';
	$c .= '.woocommerce div.product form.cart button.purchase-form:active{background-color:' . esc_attr( $o['bg'] ) . ' !important;color:' . esc_attr( $o['color'] ) . ' !important;border:0 !important;}';

	/* Mau nut Them vao gio (de trong = giu theo giao dien) */
	if ( '' !== trim( (string) $o['cart_bg'] ) ) {
		$c .= '.woocommerce div.product form.cart .single_add_to_cart_button.button,';
		$c .= '.woocommerce div.product form.cart .single_add_to_cart_button.button:hover,';
		$c .= '.woocommerce div.product form.cart .single_add_to_cart_button.button:focus,';
		$c .= '.woocommerce div.product form.cart .single_add_to_cart_button.button:active{background-color:' . esc_attr( $o['cart_bg'] ) . ' !important;color:' . esc_attr( $o['cart_color'] ) . ' !important;border:0 !important;}';
	}

	/* Nut full chieu rong: xuong dong, chiem tron hang */
	if ( $full ) {
		$c .= '.woocommerce div.product form.cart button.purchase-form{flex:1 1 100% !important;width:100% !important;}';
	}

	/* Dien thoai: nut cao hon cho de bam, van giu dung 1 hang */
	$c .= '@media (max-width:849px){';
	$c .= '.woocommerce div.product form.cart .quantity,';
	$c .= '.woocommerce div.product form.cart .quantity.ux-quantity{height:' . $hmb . 'px;}';
	$c .= '.woocommerce div.product form.cart .quantity .ux-quantity__button{flex:0 0 24px;width:24px !important;height:' . $hmb . 'px !important;min-height:' . $hmb . 'px !important;line-height:' . $hmb . 'px !important;}';
	$c .= '.woocommerce div.product form.cart .quantity input.qty{flex:0 0 32px;width:32px !important;height:' . $hmb . 'px !important;min-height:' . $hmb . 'px !important;line-height:' . $hmb . 'px !important;}';
	$c .= '.woocommerce div.product form.cart .single_add_to_cart_button.button,';
	$c .= '.woocommerce div.product form.cart button.purchase-form{height:' . $hmb . 'px !important;min-height:' . $hmb . 'px !important;padding:0 8px !important;font-size:12.5px;}';
	$c .= '}';
	$c .= '@media (max-width:380px){';
	$c .= '.woocommerce div.product form.cart .woocommerce-variation-add-to-cart,';
	$c .= '.woocommerce div.product form.cart:not(.variations_form){gap:5px;}';
	$c .= '.woocommerce div.product form.cart .single_add_to_cart_button.button,';
	$c .= '.woocommerce div.product form.cart button.purchase-form{padding:0 5px !important;font-size:11.5px;letter-spacing:0;}';
	$c .= '}';

	echo '<style>' . $c . '</style>';
}

/* Doi chu nut Them vao gio o trang san pham */
add_filter( 'woocommerce_product_single_add_to_cart_text', 'smlife_qb_cart_label', 20 );
function smlife_qb_cart_label( $text ) {
	$o = smlife_qb_opt();
	$l = trim( (string) $o['cart_label'] );
	return '' !== $l ? $l : $text;
}

/* 2. CHÈN POPUP FORM (LIGHTBOX) VÀO FOOTER */
add_action( 'wp_footer', 'smlife_quick_buy_popup_content' );
function smlife_quick_buy_popup_content() {
	if ( ! function_exists( 'is_product' ) || ! is_product() ) {
		return;
	}
	$o = smlife_qb_opt();
	if ( ! empty( $o['hide'] ) ) {
		return;
	}

	// CSS cho popup.
	$css  = '[href="#test-popup"]{display:none;}';
	$css .= '.product-info-popup{display:flex;align-items:center;margin-bottom:14px;}';
	$css .= '.product-info-popup img{width:64px;height:64px;object-fit:cover;margin-right:12px;border-radius:6px;}';
	$css .= '.product-info-popup h5{margin:0;font-size:15px;font-weight:bold;line-height:1.35;}';
	$css .= '#test-popup{background:#fff;padding:26px 28px;border-radius:10px;max-width:480px;margin:0 auto;position:relative;}';
	/* May tinh: chua khung xuong mot chut, co bien deu hai ben va phia tren */
	$css .= '@media (min-width:850px){';
	$css .= '.mfp-wrap .mfp-content{margin-top:42px;margin-bottom:42px;}';
	$css .= '.mfp-content #test-popup,.mfp-content .lightbox-content{padding:18px !important;}';
	$css .= '#test-popup{padding:26px 28px;}';
	$css .= '}';
	$css .= '#test-popup input[type="submit"]{width:100%;background:#EE2468;border-radius:5px;margin-top:10px;}';
	/* Dien thoai: gon lai de nut Gui dat hang khong bi day xuong qua man hinh */
	/* Khi popup mo thi an Promo Bar di, neu khong no che mat dau X dong popup */
	$css .= 'body.smlife-popup-open #sml-promo-bar,body.smlife-popup-open .sml-promo-bar{display:none !important;}';
	$css .= 'body.smlife-popup-open #wrapper{padding-top:0 !important;}';
	$css .= '.mfp-close,.mfp-close:active{z-index:1100 !important;opacity:1 !important;}';
	$css .= '@media (max-width:849px){';
	$css .= '#test-popup{padding:12px 12px 14px;}';
	$css .= '.mfp-content #test-popup,.mfp-content .lightbox-content{padding:12px !important;}';
	$css .= '#test-popup > h3{font-size:15px !important;margin:0 0 8px !important;}';
	$css .= '.product-info-popup{margin-bottom:8px;}';
	$css .= '.product-info-popup img{width:46px;height:46px;margin-right:9px;}';
	$css .= '.product-info-popup h5{font-size:13px;}';
	/* Dau X nam goc tren ben phai cua khung, khong bi thanh nao de len */
	$css .= '.mfp-wrap .mfp-close{top:2px !important;right:2px !important;width:40px !important;height:40px !important;line-height:40px !important;font-size:26px !important;}';
	$css .= '}';
	echo '<style>' . $css . '</style>';

	// Nội dung bên trong lightbox (thuần chuỗi, không dùng ob_start).
	$popup_content  = '<h3 style="text-align:center; color:#EE2468; font-weight:bold;">ĐẶT HÀNG NHANH</h3>';
	$popup_content .= '<div class="product-info-popup">';
	$popup_content .= '<img src="" class="popup-prod-img">';
	$popup_content .= '<div class="popup-prod-meta"><h5 class="popup-prod-title">Đang tải...</h5></div>';
	$popup_content .= '</div>';
	$popup_content .= do_shortcode( '[smlife_quick_order]' );

	// Lightbox của Flatsome bọc nội dung trên.
	echo do_shortcode( '[lightbox id="test-popup" width="480px" padding="18px"]' . $popup_content . '[/lightbox]' );

	// Nút ẩn để kích hoạt lightbox (JS sẽ click vào đây).
	echo '<a href="#test-popup" class="trigger-quick-buy-popup">Open Popup</a>';

	// Script điều khiển popup - dùng NOWDOC để không bị nội suy biến PHP ($).
	echo <<<'JS'
<script>
function smlifeGrabPrice(){
	var boxes = [
		'.woocommerce-variation-price',
		'.product-main .price',
		'.summary .price',
		'.price'
	];
	for (var i = 0; i < boxes.length; i++) {
		var box = document.querySelector(boxes[i]);
		if (!box) { continue; }
		var el = box.querySelector('ins .woocommerce-Price-amount') || box.querySelector('.woocommerce-Price-amount');
		if (!el) { continue; }
		var num = (el.textContent || '').replace(/[^0-9]/g, '');
		if (num) { return num; }
	}
	return '';
}
jQuery(document).ready(function($){
	$('body').on('click', '.purchase-form', function(e){
		e.preventDefault();
		var prodTitle = $('.product_title').eq(0).text().trim();
		var prodImg = '';
		if($('.woocommerce-product-gallery__image img').length > 0){
			prodImg = $('.woocommerce-product-gallery__image img').eq(0).attr('src');
		} else {
			prodImg = $('.slide.first img').attr('src');
		}
		$('.popup-prod-title').text(prodTitle);
		$('.popup-prod-img').attr('src', prodImg);
		$('input[name="qo_product"]').val(prodTitle);
		$('input[name="qo_link"]').val(window.location.href);
		$('input[name="qo_price"]').val(smlifeGrabPrice());
		$('body').addClass('smlife-popup-open');
		$('.trigger-quick-buy-popup').trigger('click');
	});
	$(document).on('mfpClose', function(){ $('body').removeClass('smlife-popup-open'); });
	$('body').on('click', '.mfp-close, .mfp-bg', function(){ $('body').removeClass('smlife-popup-open'); });
	$(document).on('keyup', function(e){ if(e.key === 'Escape'){ $('body').removeClass('smlife-popup-open'); } });
});
</script>
JS;
}

/* 3. Mục quản trị: SMLIFE Core > Nút Mua ngay */
add_action( 'admin_menu', function () {
	add_submenu_page(
		'smlife-core',
		'Nút Mua ngay',
		'Nút Mua ngay',
		'manage_options',
		'smlife-mua-ngay',
		'smlife_qb_page'
	);
}, 22 );

function smlife_qb_page() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}
	$o = smlife_qb_opt();
	echo '<div class="wrap smlife-wrap">';
	echo '<div class="smc-hd"><h1>Nút Mua ngay</h1>';
	echo '<p>Nút đặt hàng nhanh đứng cạnh nút Thêm vào giỏ, bấm vào mở popup Đặt hàng nhanh.</p></div>';
	if ( isset( $_GET['updated'] ) ) {
		echo '<div class="notice notice-success is-dismissible"><p>Đã lưu.</p></div>';
	}
	echo '<div class="smlife-card">';
	echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '">';
	echo '<input type="hidden" name="action" value="smlife_qb_save" />';
	wp_nonce_field( 'smlife_qb_save', 'smlife_qb_nonce' );
	echo '<table class="form-table" role="presentation">';
	echo '<tr><th scope="row">Ẩn nút Mua ngay</th><td><label><input type="checkbox" name="qb[hide]" value="1" ' . checked( 1, $o['hide'], false ) . ' /> Tắt hẳn nút trên mọi trang sản phẩm</label></td></tr>';
	echo '<tr><th scope="row">Chữ trên nút</th><td><input type="text" name="qb[label]" value="' . esc_attr( $o['label'] ) . '" class="regular-text" /></td></tr>';
	echo '<tr><th scope="row">Màu nền</th><td><input type="color" name="qb[bg]" value="' . esc_attr( $o['bg'] ) . '" /></td></tr>';
	echo '<tr><th scope="row">Màu chữ</th><td><input type="color" name="qb[color]" value="' . esc_attr( $o['color'] ) . '" /></td></tr>';
	echo '<tr><th scope="row">Bo góc (px)</th><td><input type="number" min="0" max="99" name="qb[radius]" value="' . esc_attr( $o['radius'] ) . '" class="small-text" /></td></tr>';
	echo '<tr><th scope="row">Cỡ chữ (px)</th><td><input type="number" min="0" max="24" name="qb[fontsize]" value="' . esc_attr( $o['fontsize'] ) . '" class="small-text" /> <span class="description">0 = theo giao diện</span></td></tr>';
	echo '<tr><th scope="row">Chiều cao nút - máy tính (px)</th><td><input type="number" min="26" max="70" name="qb[h_pc]" value="' . esc_attr( $o['h_pc'] ) . '" class="small-text" /> <span class="description">Áp cho cả ô số lượng và 2 nút. Mặc định 34</span></td></tr>';
	echo '<tr><th scope="row">Chiều cao nút - điện thoại (px)</th><td><input type="number" min="26" max="70" name="qb[h_mb]" value="' . esc_attr( $o['h_mb'] ) . '" class="small-text" /> <span class="description">Mặc định 40 cho dễ bấm bằng ngón tay</span></td></tr>';
	echo '<tr><th scope="row">Khoảng cách giữa các nút (px)</th><td><input type="number" min="0" max="20" name="qb[gap]" value="' . esc_attr( $o['gap'] ) . '" class="small-text" /></td></tr>';
	echo '<tr><th scope="row">Chữ nút Thêm vào giỏ</th><td><input type="text" name="qb[cart_label]" value="' . esc_attr( $o['cart_label'] ) . '" class="regular-text" placeholder="Để trống = giữ mặc định (Thêm vào giỏ hàng)" /></td></tr>';
	echo '<tr><th scope="row">Màu nền nút Thêm vào giỏ</th><td><input type="color" name="qb[cart_bg]" value="' . esc_attr( $o['cart_bg'] ? $o['cart_bg'] : '#8a9096' ) . '" /> <label style="margin-left:10px"><input type="checkbox" name="qb[cart_bg_on]" value="1" ' . checked( 1, $o['cart_bg'] ? 1 : 0, false ) . ' /> Dùng màu này</label> <span class="description">Bỏ chọn = giữ màu của giao diện</span></td></tr>';
	echo '<tr><th scope="row">Màu chữ nút Thêm vào giỏ</th><td><input type="color" name="qb[cart_color]" value="' . esc_attr( $o['cart_color'] ) . '" /></td></tr>';
	echo '<tr><th scope="row">Full chiều rộng</th><td><label><input type="checkbox" name="qb[fullwidth]" value="1" ' . checked( 1, $o['fullwidth'], false ) . ' /> Nút xuống dòng, chiếm trọn chiều ngang (mặc định: đứng cạnh nút Thêm vào giỏ, chia 50/50)</label></td></tr>';
	echo '</table>';
	submit_button( 'Lưu' );
	echo '</form></div></div>';
}

add_action( 'admin_post_smlife_qb_save', function () {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( 'Không đủ quyền.' );
	}
	check_admin_referer( 'smlife_qb_save', 'smlife_qb_nonce' );
	$in  = isset( $_POST['qb'] ) && is_array( $_POST['qb'] ) ? wp_unslash( $_POST['qb'] ) : array();
	$hex = function ( $v, $d ) {
		$v = sanitize_hex_color( $v );
		return $v ? $v : $d;
	};
	$label = isset( $in['label'] ) ? sanitize_text_field( $in['label'] ) : 'MUA NGAY';
	$new   = array(
		'label'     => '' === $label ? 'MUA NGAY' : $label,
		'bg'        => $hex( isset( $in['bg'] ) ? $in['bg'] : '', '#2b95ec' ),
		'color'     => $hex( isset( $in['color'] ) ? $in['color'] : '', '#ffffff' ),
		'radius'    => isset( $in['radius'] ) ? max( 0, min( 99, absint( $in['radius'] ) ) ) : 99,
		'fontsize'  => isset( $in['fontsize'] ) ? max( 0, min( 24, absint( $in['fontsize'] ) ) ) : 0,
		'fullwidth' => empty( $in['fullwidth'] ) ? 0 : 1,
		'hide'      => empty( $in['hide'] ) ? 0 : 1,
		'h_pc'      => isset( $in['h_pc'] ) ? max( 26, min( 70, absint( $in['h_pc'] ) ) ) : 34,
		'h_mb'      => isset( $in['h_mb'] ) ? max( 26, min( 70, absint( $in['h_mb'] ) ) ) : 40,
		'gap'       => isset( $in['gap'] ) ? max( 0, min( 20, absint( $in['gap'] ) ) ) : 8,
		'cart_label'=> isset( $in['cart_label'] ) ? sanitize_text_field( $in['cart_label'] ) : '',
		'cart_bg'   => empty( $in['cart_bg_on'] ) ? '' : $hex( isset( $in['cart_bg'] ) ? $in['cart_bg'] : '', '#8a9096' ),
		'cart_color'=> $hex( isset( $in['cart_color'] ) ? $in['cart_color'] : '', '#ffffff' ),
	);
	update_option( 'smlife_qb', $new );
	wp_safe_redirect( admin_url( 'admin.php?page=smlife-mua-ngay&updated=1' ) );
	exit;
} );
