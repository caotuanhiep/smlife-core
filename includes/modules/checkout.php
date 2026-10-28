<?php
/**
 * SMLIFE Core - Module: Gop Cart + Checkout
 * Gop gio hang va thanh toan thanh mot trang.
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

/**
 * SMLIFE - Snippet 1: Checkout Layout (gộp Cart + Checkout + giao diện dọc)
 * Thay plugin: DEVVN WooCommerce One Page Checkout
 * Code Snippets: chọn "Run everywhere"
 */

/* ===== 1. Hiển thị giỏ hàng trên trang Thanh toán ===== */
add_action( 'woocommerce_before_checkout_form', function () {
	$combine = function_exists( 'smlife_cfg' ) ? smlife_cfg( 'checkout_combine_enabled', 1 ) : 1;
	if ( ! $combine ) {
		return;
	}
	if ( ! function_exists( 'WC' ) || ! WC()->cart || WC()->cart->is_empty() ) {
		return;
	}
	if ( function_exists( 'is_wc_endpoint_url' ) && ( is_wc_endpoint_url( 'order-pay' ) || is_wc_endpoint_url( 'order-received' ) ) ) {
		return;
	}
	echo '<div class="smlife-onepage-cart">';
	echo do_shortcode( '[woocommerce_cart]' );
	echo '</div>';
}, 5 );

/* ===== 2. Chuyển hướng trang Giỏ hàng sang Thanh toán ===== */
add_action( 'template_redirect', function () {
	$combine = function_exists( 'smlife_cfg' ) ? smlife_cfg( 'checkout_combine_enabled', 1 ) : 1;
	if ( ! $combine ) {
		return;
	}
	if ( ! function_exists( 'is_cart' ) || ! is_cart() ) {
		return;
	}
	if ( function_exists( 'is_checkout' ) && is_checkout() ) {
		return;
	}
	if ( function_exists( 'WC' ) && WC()->cart && ! WC()->cart->is_empty() && function_exists( 'wc_get_checkout_url' ) ) {
		wp_safe_redirect( wc_get_checkout_url() );
		exit;
	}
} );

/* ===== 3. (Tùy chọn) Thêm vào giỏ ra thẳng Thanh toán ===== */
add_filter( 'woocommerce_add_to_cart_redirect', function ( $url ) {
	$on = function_exists( 'smlife_cfg' ) ? smlife_cfg( 'checkout_addtocart_redirect', 0 ) : 0;
	if ( $on && function_exists( 'wc_get_checkout_url' ) ) {
		return wc_get_checkout_url();
	}
	return $url;
}, 20 );

/* ===== 3b. Việt hóa vài nhãn tiếng Anh còn sót ===== */
add_filter( 'gettext', function ( $translated, $text, $domain ) {
	/* 1.24.0: chi doi chu ngoai trang khach, khong dung vao wp-admin
	   (truoc day "Shipping" o moi trang quan tri cung bi doi) */
	if ( is_admin() && ! wp_doing_ajax() ) {
		return $translated;
	}
	static $map = array(
		'Shipment'                                 => 'Vận chuyển',
		'Shipping'                                 => 'Vận chuyển',
		'Free shipping'                            => 'Miễn phí vận chuyển',
		'Billing address'                          => 'Địa chỉ nhận hàng',
		'Thank you. Your order has been received.' => 'Cảm ơn bạn! Đơn hàng của bạn đã được đặt thành công.',
	);
	return isset( $map[ $text ] ) ? $map[ $text ] : $translated;
}, 20, 3 );

/* Việt hóa tên gói vận chuyển trong bảng đơn hàng (hay hiện "Shipment") */
add_filter( 'woocommerce_shipping_package_name', function () {
	return 'Vận chuyển';
}, 20 );

/* ===== 3c. Khối thông tin + thẻ hỗ trợ ở trang Đơn hàng hoàn tất ===== */
add_action( 'woocommerce_thankyou', function ( $order_id ) {
	$order = $order_id ? wc_get_order( $order_id ) : false;
	if ( ! $order ) {
		return;
	}
	$date = $order->get_date_created() ? wc_format_datetime( $order->get_date_created(), 'd/m/Y' ) : '';
	$addr = $order->get_formatted_billing_address();

	echo '<div class="smlife-order-info">';
	echo '<div class="soi-title">Thông tin đơn hàng</div>';
	echo '<div class="soi-row"><span class="soi-l">Mã đơn hàng</span><span class="soi-v">#' . esc_html( $order->get_order_number() ) . '</span></div>';
	echo '<div class="soi-row"><span class="soi-l">Ngày đặt</span><span class="soi-v">' . esc_html( $date ) . '</span></div>';
	if ( $order->get_billing_email() ) {
		echo '<div class="soi-row"><span class="soi-l">Email</span><span class="soi-v">' . esc_html( $order->get_billing_email() ) . '</span></div>';
	}
	if ( $order->get_billing_phone() ) {
		echo '<div class="soi-row"><span class="soi-l">Số điện thoại</span><span class="soi-v">' . esc_html( $order->get_billing_phone() ) . '</span></div>';
	}
	echo '</div>';
}, 20 );

/* ===== 4. Giao diện trang Thanh toán ===== */
add_action( 'wp_head', function () {
	if ( ! function_exists( 'is_checkout' ) || ! is_checkout() ) {
		return;
	}
	if ( function_exists( 'is_wc_endpoint_url' ) && ( is_wc_endpoint_url( 'order-pay' ) || is_wc_endpoint_url( 'order-received' ) ) ) {
		return;
	}
	$combine = function_exists( 'smlife_cfg' ) ? smlife_cfg( 'checkout_combine_enabled', 1 ) : 1;
	if ( ! $combine ) {
		return;
	}
	echo '
<style id="smlife-checkout-layout">
:root{
  --sm-accent:#e8641e; --sm-accent-weak:rgba(232,100,30,.09);
  --sm-ink:#23262b; --sm-line:#eeeae4; --sm-line-2:#dcd6cd; --sm-muted:#8a8f97;
  --sm-radius:8px; --sm-maxw:720px;
  --sm-sec-bg:#f2f3f5; --sm-sec-ink:#3c4043; --sm-sec-bar:#c3c7cc;
  --sm-fs:14.5px;
}
.woocommerce-checkout .woocommerce{max-width:var(--sm-maxw)!important;margin-left:auto!important;margin-right:auto!important;float:none!important;}
.woocommerce-checkout .smlife-onepage-cart,
.woocommerce-checkout form.checkout{max-width:100%!important;width:100%!important;margin:0!important;float:none!important;}
.woocommerce-checkout #customer_details,
.woocommerce-checkout #order_review,
.woocommerce-checkout .col2-set{position:static!important;float:none!important;clear:both!important;width:100%!important;max-width:100%!important;}
.woocommerce-checkout form.checkout .row,
.smlife-onepage-cart .row{display:block!important;max-width:100%!important;margin:0!important;}
.woocommerce-checkout form.checkout .row > .col,
.woocommerce-checkout form.checkout .row > [class*="large-"],
.smlife-onepage-cart .row > .col,
.smlife-onepage-cart .row > [class*="large-"]{flex:0 0 100%!important;max-width:100%!important;width:100%!important;float:none!important;padding-left:0!important;padding-right:0!important;}
.woocommerce-checkout #customer_details .col-inner{padding:0!important;}

/* Tiêu đề khu vực: pill, màu đổi được trong SMLIFE Core > Giao diện Checkout */
.smlife-onepage-cart::before,
.woocommerce-checkout .woocommerce-billing-fields > h3,
.woocommerce-checkout #order_review_heading,
.woocommerce-checkout #payment::before{
  display:block;font-size:13px;line-height:1.2;text-transform:uppercase;letter-spacing:.05em;
  color:var(--sm-sec-ink)!important;font-weight:700!important;
  background-color:var(--sm-sec-bg)!important;
  border:0!important;border-left:3px solid var(--sm-sec-bar)!important;border-radius:var(--sm-radius)!important;
  padding:11px 16px!important;margin:0 0 14px!important;max-width:100%!important;
}
.smlife-onepage-cart::before{content:"Giỏ hàng";}
.woocommerce-checkout #payment::before{content:"Phương thức thanh toán";}
.woocommerce-checkout #order_review_heading{margin-top:22px!important;}
/* Gộp "Thông tin bổ sung" vào phần Thông tin thanh toán (bỏ tiêu đề riêng) */
.woocommerce-checkout .woocommerce-additional-fields > h3{display:none!important;}

/* Giỏ hàng */
.smlife-onepage-cart{border:0!important;border-radius:0!important;padding:0!important;margin:0 0 8px!important;background:transparent!important;box-shadow:none!important;}
.smlife-onepage-cart .wc-proceed-to-checkout,
.smlife-onepage-cart .cart_totals,
.smlife-onepage-cart .cart-collaterals{display:none!important;}
.smlife-onepage-cart table.cart{width:100%!important;border:0!important;margin:0!important;background:transparent!important;}
.smlife-onepage-cart table.cart thead{display:none!important;}
.smlife-onepage-cart table.cart td{border:0!important;border-top:1px solid var(--sm-line)!important;background:transparent!important;padding:11px 8px;vertical-align:middle;}
.smlife-onepage-cart table.cart tbody tr:first-child td{border-top:0!important;}
/* Chữ trong giỏ hàng cùng cỡ với phần vận chuyển / thanh toán */
.smlife-onepage-cart table.cart td,
.smlife-onepage-cart table.cart td a,
.smlife-onepage-cart .product-name,
.smlife-onepage-cart .product-name a{font-size:var(--sm-fs)!important;line-height:1.45;}

/* Nút "Tiếp tục xem sản phẩm" -> chữ xanh, không viền */
.smlife-onepage-cart a.button-continue-shopping,
.smlife-onepage-cart a.button-continue-shopping.is-outline,
.smlife-onepage-cart a.button-continue-shopping.button{background:transparent!important;border:0!important;color:#1a5fb4!important;padding:4px 0!important;margin:4px 0 0!important;font-weight:600!important;text-transform:none!important;letter-spacing:0!important;box-shadow:none!important;min-height:0!important;line-height:1.4!important;}
.smlife-onepage-cart a.button-continue-shopping:hover{color:#124a8f!important;background:transparent!important;text-decoration:underline!important;}

/* Hộp thông báo + coupon viền nét đứt */
.woocommerce-checkout .woocommerce-form-coupon-toggle{margin:0 0 14px!important;border:1px dashed #d3b48f!important;border-radius:var(--sm-radius)!important;padding:11px 14px!important;background:rgba(232,100,30,.03)!important;}
.woocommerce-checkout .woocommerce-info,
.woocommerce-checkout .woocommerce-form-login-toggle .woocommerce-info,
.woocommerce-checkout .woocommerce-form-coupon-toggle .woocommerce-info{border:0!important;background:transparent!important;box-shadow:none!important;padding:0!important;margin:0!important;color:var(--sm-muted)!important;font-size:var(--sm-fs);}
.woocommerce-checkout .woocommerce-info::before{display:none!important;}
.woocommerce-checkout .woocommerce-info a,
.woocommerce-checkout .showcoupon{color:var(--sm-accent)!important;}
.woocommerce-checkout .checkout_coupon{border:1px solid var(--sm-line-2)!important;border-radius:var(--sm-radius)!important;padding:14px!important;margin:0 0 14px!important;background:transparent!important;}

/* Khối thông tin + đơn hàng */
.woocommerce-checkout #customer_details{padding:0!important;border:0!important;margin-top:18px!important;background:transparent!important;box-shadow:none!important;}
.woocommerce-checkout #order_review{padding:0!important;border:0!important;margin-top:0!important;background:transparent!important;box-shadow:none!important;}
.woocommerce-checkout .woocommerce-shipping-fields{display:none!important;}

/* 2 cột bằng grid, khoảng cách gọn */
.woocommerce-checkout .woocommerce-billing-fields__field-wrapper,
.woocommerce-checkout .woocommerce-additional-fields__field-wrapper{display:grid!important;grid-template-columns:minmax(0,1fr) minmax(0,1fr);column-gap:12px;align-items:start;}
.woocommerce-checkout .woocommerce-billing-fields__field-wrapper .form-row,
.woocommerce-checkout .woocommerce-additional-fields__field-wrapper .form-row{grid-column:1 / -1;width:auto!important;min-width:0!important;float:none!important;clear:none!important;margin:0 0 10px!important;padding:0!important;}
.woocommerce-checkout .woocommerce-billing-fields__field-wrapper .form-row-first{grid-column:1 / 2!important;}
.woocommerce-checkout .woocommerce-billing-fields__field-wrapper .form-row-last{grid-column:2 / 3!important;}

/* Nhãn + ô nhập */
.woocommerce-checkout .form-row{margin:0 0 10px!important;padding:0!important;}
.woocommerce-checkout .form-row label{font-weight:600;font-size:13px;margin:0 0 4px;display:block;color:var(--sm-ink);line-height:1.3;}
.woocommerce-checkout .form-row input.input-text,
.woocommerce-checkout .form-row textarea,
.woocommerce-checkout .form-row select{width:100%!important;border:1px solid var(--sm-line-2)!important;border-radius:var(--sm-radius)!important;padding:9px 12px!important;box-shadow:none!important;min-height:42px;line-height:1.4;font-size:var(--sm-fs);background:#fff;color:var(--sm-ink);margin:0!important;}
.woocommerce-checkout .form-row textarea{min-height:80px;}
.woocommerce-checkout .form-row input.input-text:focus,
.woocommerce-checkout .form-row textarea:focus,
.woocommerce-checkout .form-row select:focus{outline:none!important;border-color:var(--sm-accent)!important;box-shadow:0 0 0 3px var(--sm-accent-weak)!important;}
.woocommerce-checkout .select2-container{width:100%!important;}
.woocommerce-checkout .select2-container--default .select2-selection--single{height:42px!important;border:1px solid var(--sm-line-2)!important;border-radius:var(--sm-radius)!important;display:flex!important;align-items:center;padding:0 12px;background:#fff;}
.woocommerce-checkout .select2-container--default .select2-selection--single .select2-selection__rendered{line-height:40px!important;padding:0!important;font-size:var(--sm-fs);color:var(--sm-ink);}
.woocommerce-checkout .select2-container--default .select2-selection--single .select2-selection__arrow{height:40px!important;right:10px;}

/* Bảng đơn hàng - bỏ bớt đường kẻ, chỉ giữ kẻ dưới mỗi sản phẩm */
.woocommerce-checkout #order_review table.shop_table{border:0!important;background:transparent!important;margin:0!important;font-size:var(--sm-fs)!important;}
.woocommerce-checkout #order_review table.shop_table thead{display:none!important;}
.woocommerce-checkout #order_review table.shop_table tbody td,
.woocommerce-checkout #order_review table.shop_table tbody th{border:0!important;border-bottom:1px solid var(--sm-line)!important;padding:10px 0!important;background:transparent!important;vertical-align:top;font-size:var(--sm-fs)!important;}
.woocommerce-checkout #order_review table.shop_table tfoot td,
.woocommerce-checkout #order_review table.shop_table tfoot th{border:0!important;padding:7px 0!important;background:transparent!important;vertical-align:top;font-size:var(--sm-fs)!important;}
.woocommerce-checkout #order_review table.shop_table tfoot tr:first-child td,
.woocommerce-checkout #order_review table.shop_table tfoot tr:first-child th{padding-top:13px!important;}
.woocommerce-checkout #order_review .order-total th,
.woocommerce-checkout #order_review .order-total td{border-top:1px solid var(--sm-line)!important;padding-top:13px!important;}
.woocommerce-checkout #order_review .order-total th{font-weight:700;color:var(--sm-ink);}
.woocommerce-checkout #order_review .order-total .amount{color:var(--sm-accent)!important;font-size:20px!important;font-weight:700;}
.woocommerce-checkout #order_review #shipping_method{margin:0!important;padding:0!important;list-style:none!important;}
.woocommerce-checkout #order_review #shipping_method li{margin:0!important;padding:2px 0!important;list-style:none!important;}
.woocommerce-checkout #order_review #shipping_method li label,
.woocommerce-checkout #order_review .woocommerce-shipping-totals td,
.woocommerce-checkout #order_review .woocommerce-shipping-totals th{font-size:var(--sm-fs)!important;}

/* Phương thức thanh toán - bỏ đường kẻ giữa các lựa chọn */
.woocommerce-checkout #payment{background:transparent!important;border:0!important;padding:0!important;border-radius:0!important;margin-top:20px!important;}
.woocommerce-checkout #payment ul.payment_methods{border:0!important;padding:0!important;margin:0 0 16px!important;}
.woocommerce-checkout #payment ul.payment_methods li{border:0!important;padding:8px 2px!important;margin:0!important;list-style:none!important;background:transparent!important;}
.woocommerce-checkout #payment ul.payment_methods li label{font-size:var(--sm-fs)!important;font-weight:600;color:var(--sm-ink);line-height:1.4;}
.woocommerce-checkout #payment .payment_box{background:transparent!important;margin:6px 0 0!important;padding:0 0 0 26px!important;font-size:12.5px;color:var(--sm-muted);}
.woocommerce-checkout #payment .payment_box p{margin:0!important;}
.woocommerce-checkout #payment .payment_box::before{display:none!important;}

/* Nút Đặt hàng */
.woocommerce-checkout #place_order{width:100%!important;background:var(--sm-accent)!important;border:0!important;color:#fff!important;border-radius:var(--sm-radius)!important;padding:16px 20px!important;font-weight:700!important;float:none!important;text-transform:uppercase;letter-spacing:.03em;font-size:16px!important;margin-top:6px!important;box-shadow:0 12px 24px -12px var(--sm-accent)!important;transition:filter .15s,transform .05s;}
.woocommerce-checkout #place_order:hover{filter:brightness(.96)!important;}
.woocommerce-checkout #place_order:active{transform:translateY(1px);}

/* ===== Điện thoại ===== */
@media(max-width:849px){
  /* Thu hẹp biên trang cho nội dung rộng nhất */
  .woocommerce-checkout .page-wrapper{padding-left:10px!important;padding-right:10px!important;}
  .woocommerce-checkout .page-wrapper .row{margin-left:-5px!important;margin-right:-5px!important;}
  .woocommerce-checkout .page-wrapper .row > .col{padding-left:5px!important;padding-right:5px!important;}
  .woocommerce-checkout .page-wrapper .row.row-small{margin-left:-3px!important;margin-right:-3px!important;}
  .woocommerce-checkout .page-wrapper .row.row-small > .col{padding-left:3px!important;padding-right:3px!important;}

  /* Mọi ô nhập về 1 cột - phải dùng đúng mức ưu tiên như lớp gốc ở trên */
  .smlife-onepage-cart table.cart thead{display:none!important;}
  .woocommerce-checkout .woocommerce-billing-fields__field-wrapper,
  .woocommerce-checkout .woocommerce-additional-fields__field-wrapper{grid-template-columns:minmax(0,1fr)!important;}
  .woocommerce-checkout .woocommerce-billing-fields__field-wrapper .form-row,
  .woocommerce-checkout .woocommerce-billing-fields__field-wrapper .form-row-first,
  .woocommerce-checkout .woocommerce-billing-fields__field-wrapper .form-row-last,
  .woocommerce-checkout .woocommerce-additional-fields__field-wrapper .form-row{grid-column:1 / -1!important;max-width:100%!important;}

  /* Khoảng cách gọn hơn nữa trên màn hẹp */
  .woocommerce-checkout .form-row{margin:0 0 8px!important;}
  .woocommerce-checkout .woocommerce-billing-fields__field-wrapper .form-row,
  .woocommerce-checkout .woocommerce-additional-fields__field-wrapper .form-row{margin:0 0 8px!important;}
  .woocommerce-checkout .form-row label{font-size:12.5px;margin:0 0 3px;}
  .woocommerce-checkout .form-row input.input-text,
  .woocommerce-checkout .form-row select{min-height:40px;padding:8px 11px!important;}
  /* O Ghi chu don hang: cao hon de go thoai mai, khong bi che */
  .woocommerce-checkout .form-row textarea,
  .woocommerce-checkout #order_comments{min-height:104px!important;height:104px;padding:9px 11px!important;line-height:1.5;}
  .woocommerce-checkout .woocommerce-additional-fields{margin-bottom:6px;}
  .woocommerce-checkout .select2-container--default .select2-selection--single{height:40px!important;}
  .woocommerce-checkout .select2-container--default .select2-selection--single .select2-selection__rendered{line-height:38px!important;}
  .smlife-onepage-cart::before,
  .woocommerce-checkout .woocommerce-billing-fields > h3,
  .woocommerce-checkout #order_review_heading,
  .woocommerce-checkout #payment::before{padding:9px 13px!important;margin:0 0 11px!important;}
  .woocommerce-checkout #customer_details{margin-top:14px!important;}
  .woocommerce-checkout #order_review_heading{margin-top:18px!important;}
  .woocommerce-checkout #payment{margin-top:16px!important;}
  .woocommerce-checkout #place_order{padding:15px 18px!important;font-size:15px!important;}
}
</style>';
} );

/* ===== 5. Giao diện trang Đơn hàng hoàn tất (thank you) ===== */
add_action( 'wp_head', function () {
	if ( ! function_exists( 'is_wc_endpoint_url' ) || ! is_wc_endpoint_url( 'order-received' ) ) {
		return;
	}
	echo '
<style id="smlife-thankyou-layout">
:root{
  --sm-accent:#e8641e; --sm-accent-weak:rgba(232,100,30,.09);
  --sm-ink:#23262b; --sm-line:#eeeae4; --sm-line-2:#dcd6cd; --sm-muted:#8a8f97;
  --sm-radius:8px; --sm-maxw:680px;
}
/* Một cột, căn giữa, sắp lại thứ tự - gỡ bọc 2 cột của theme bằng display:contents */
.woocommerce-order.row,.woocommerce-order{display:flex!important;flex-direction:column!important;max-width:var(--sm-maxw)!important;margin-left:auto!important;margin-right:auto!important;}
.woocommerce-order.row > .col,.woocommerce-order .col-inner{display:contents!important;}
.woocommerce-order .woocommerce-notice--success,
.woocommerce-order p.woocommerce-thankyou-order-received,
.woocommerce-order .smlife-order-info,
.woocommerce-order .woocommerce-order-details{width:100%!important;float:none!important;margin:0 0 18px!important;min-width:0;}
.woocommerce-order ul.woocommerce-order-overview{display:none!important;}
.woocommerce-order .woocommerce-customer-details{display:none!important;}
.woocommerce-order .smlife-ty-support{display:none!important;}
.woocommerce-order .clear{display:none!important;}
.woocommerce-order .col > p:not([class]),
.woocommerce-order .col-inner > p:not([class]){display:none!important;}
.woocommerce-order .woocommerce-notice--success,
.woocommerce-order p.woocommerce-thankyou-order-received{order:1;}
.woocommerce-order .smlife-order-info{order:2;}
.woocommerce-order .woocommerce-order-details{order:3;}

/* Banner cảm ơn */
.woocommerce-order .woocommerce-notice--success,
.woocommerce-order p.woocommerce-thankyou-order-received{background:var(--sm-accent-weak)!important;border:0!important;border-radius:14px!important;padding:28px 24px!important;text-align:center;font-size:18px!important;font-weight:700!important;color:var(--sm-ink)!important;}
.woocommerce-order p.woocommerce-thankyou-order-received::before{content:"\2713";display:flex;width:52px;height:52px;border-radius:50%;background:var(--sm-accent);color:#fff;align-items:center;justify-content:center;font-size:28px;line-height:1;margin:0 auto 14px;font-weight:700;}

/* Tiêu đề khu vực: box cam nhạt + vạch nhấn + icon */
.woocommerce-order .smlife-order-info .soi-title,
.woocommerce-order .woocommerce-order-details__title{font-size:13px!important;text-transform:uppercase;letter-spacing:.05em;color:var(--sm-accent)!important;font-weight:700!important;background-color:var(--sm-accent-weak)!important;background-repeat:no-repeat;background-position:14px center;border:0!important;border-left:3px solid var(--sm-accent)!important;border-radius:var(--sm-radius)!important;padding:11px 14px 11px 42px!important;margin:0 0 6px!important;}
.woocommerce-order .smlife-order-info .soi-title{background-image:url("data:image/svg+xml,%3Csvg%20xmlns=%22http://www.w3.org/2000/svg%22%20width=%2218%22%20height=%2218%22%20viewBox=%220%200%2024%2024%22%20fill=%22none%22%20stroke=%22%23e8641e%22%20stroke-width=%222%22%20stroke-linecap=%22round%22%20stroke-linejoin=%22round%22%3E%3Cpath%20d=%22M6%202h12v20l-3-2-3%202-3-2-3%202z%22/%3E%3Cpath%20d=%22M9%207h6M9%2011h6M9%2015h4%22/%3E%3C/svg%3E");}
.woocommerce-order .woocommerce-order-details__title{margin-top:0!important;background-image:url("data:image/svg+xml,%3Csvg%20xmlns=%22http://www.w3.org/2000/svg%22%20width=%2218%22%20height=%2218%22%20viewBox=%220%200%2024%2024%22%20fill=%22none%22%20stroke=%22%23e8641e%22%20stroke-width=%222%22%20stroke-linecap=%22round%22%20stroke-linejoin=%22round%22%3E%3Cpath%20d=%22M3%207l9-4%209%204-9%204-9-4z%22/%3E%3Cpath%20d=%22M3%207v10l9%204%209-4V7%22/%3E%3Cpath%20d=%22M12%2011v10%22/%3E%3C/svg%3E");}

/* Khối thông tin đơn hàng */
.woocommerce-order .smlife-order-info .soi-row{display:flex;justify-content:space-between;gap:18px;padding:12px 2px;border-bottom:1px solid var(--sm-line);font-size:14px;}
.woocommerce-order .smlife-order-info .soi-row:last-child{border-bottom:0;}
.woocommerce-order .smlife-order-info .soi-l{color:var(--sm-muted);flex:none;}
.woocommerce-order .smlife-order-info .soi-v{color:var(--sm-ink);font-weight:600;text-align:right;overflow-wrap:anywhere;word-break:break-word;}
.woocommerce-order .smlife-order-info .soi-addr{flex-direction:column;align-items:flex-start;gap:6px;}
.woocommerce-order .smlife-order-info .soi-addr .soi-v{text-align:left;font-weight:500;line-height:1.7;}

/* Bảng chi tiết đơn hàng phẳng */
.woocommerce-order .woocommerce-order-details table.shop_table{border:0!important;background:transparent!important;width:100%!important;margin:0!important;}
.woocommerce-order .woocommerce-order-details table.shop_table thead th{border:0!important;border-bottom:1px solid var(--sm-line)!important;font-size:11.5px;letter-spacing:.05em;text-transform:uppercase;color:var(--sm-muted)!important;padding:0 0 10px!important;background:transparent!important;}
.woocommerce-order .woocommerce-order-details table.shop_table tbody td,
.woocommerce-order .woocommerce-order-details table.shop_table tbody th,
.woocommerce-order .woocommerce-order-details table.shop_table tfoot td,
.woocommerce-order .woocommerce-order-details table.shop_table tfoot th{border:0!important;border-bottom:1px solid var(--sm-line)!important;padding:12px 0!important;background:transparent!important;vertical-align:top;}
.woocommerce-order .woocommerce-order-details table.shop_table tfoot tr:last-child td,
.woocommerce-order .woocommerce-order-details table.shop_table tfoot tr:last-child th{border-bottom:0!important;padding-top:15px!important;}
.woocommerce-order .woocommerce-order-details .order-total .amount{color:var(--sm-accent)!important;font-size:20px!important;font-weight:700;}

/* Thẻ hỗ trợ (callout nền cam nhạt) */
.woocommerce-order .smlife-ty-support{background:var(--sm-accent-weak);border-radius:14px;padding:20px 22px;}
.woocommerce-order .smlife-ty-support .sts-title{font-weight:700;color:var(--sm-ink);font-size:14px;margin:0 0 10px;}
.woocommerce-order .smlife-ty-support .sts-note{font-size:13px;color:var(--sm-ink);opacity:.82;line-height:1.65;margin:0 0 16px;}
.woocommerce-order .smlife-ty-support .sts-hotline{display:inline-flex;align-items:center;gap:8px;background:var(--sm-accent);color:#fff!important;font-weight:700;font-size:16px;border-radius:999px;padding:10px 20px;text-decoration:none;margin:0 0 14px;}
.woocommerce-order .smlife-ty-support .sts-showroom{display:flex;gap:8px;font-size:13px;color:var(--sm-ink);line-height:1.5;}
.woocommerce-order .smlife-ty-support .sts-ic{flex:none;width:18px;height:18px;background-repeat:no-repeat;background-position:center;background-size:contain;margin-top:1px;}
.woocommerce-order .smlife-ty-support .sts-phone{background-image:url("data:image/svg+xml,%3Csvg%20xmlns=%22http://www.w3.org/2000/svg%22%20width=%2216%22%20height=%2216%22%20viewBox=%220%200%2024%2024%22%20fill=%22none%22%20stroke=%22%23ffffff%22%20stroke-width=%222%22%20stroke-linecap=%22round%22%20stroke-linejoin=%22round%22%3E%3Cpath%20d=%22M5%204h4l2%205-3%202a12%2012%200%200%200%205%205l2-3%205%202v4a2%202%200%200%201-2%202A16%2016%200%200%201%203%206a2%202%200%200%201%202-2z%22/%3E%3C/svg%3E");}
.woocommerce-order .smlife-ty-support .sts-pin{background-image:url("data:image/svg+xml,%3Csvg%20xmlns=%22http://www.w3.org/2000/svg%22%20width=%2216%22%20height=%2216%22%20viewBox=%220%200%2024%2024%22%20fill=%22none%22%20stroke=%22%23e8641e%22%20stroke-width=%222%22%20stroke-linecap=%22round%22%20stroke-linejoin=%22round%22%3E%3Cpath%20d=%22M12%2021s7-6%207-11a7%207%200%200%200-14%200c0%205%207%2011%207%2011z%22/%3E%3Ccircle%20cx=%2212%22%20cy=%2210%22%20r=%222.5%22/%3E%3C/svg%3E");}
</style>';
} );

/* ===== 6. Ghi chú: theme đã có sẵn logo + breadcrumb cho cả trang Thanh toán
 * và trang Hoàn tất nên snippet không thêm nữa (tránh trùng). ===== */

/* ===== 7. Thông tin xuất hóa đơn VAT ===== */
add_action( 'woocommerce_after_order_notes', function ( $checkout ) {
	$v = function ( $k ) use ( $checkout ) {
		return is_object( $checkout ) && method_exists( $checkout, 'get_value' ) ? $checkout->get_value( $k ) : '';
	};
	echo '<div class="smlife-vat-wrap">';
	echo '<label class="smlife-vat-toggle"><input type="checkbox" id="smlife_vat" name="smlife_vat" value="1" ' . checked( 1, $v( 'smlife_vat' ), false ) . ' /> <span>Yêu cầu xuất hóa đơn VAT</span></label>';
	echo '<div class="smlife-vat-fields">';
	echo '<input type="text" name="smlife_vat_company" placeholder="Tên doanh nghiệp *" value="' . esc_attr( $v( 'smlife_vat_company' ) ) . '" />';
	echo '<div class="smlife-vat-2">';
	echo '<input type="text" name="smlife_vat_taxcode" placeholder="Mã số thuế *" value="' . esc_attr( $v( 'smlife_vat_taxcode' ) ) . '" />';
	echo '<input type="email" name="smlife_vat_email" placeholder="Email nhận hóa đơn *" value="' . esc_attr( $v( 'smlife_vat_email' ) ) . '" />';
	echo '</div>';
	echo '<input type="text" name="smlife_vat_address" placeholder="Địa chỉ" value="' . esc_attr( $v( 'smlife_vat_address' ) ) . '" />';
	echo '<p class="smlife-vat-note">Quý khách cần đảm bảo tính chính xác trong các thông tin cung cấp để xuất hóa đơn.</p>';
	echo '</div></div>';
}, 20 );

/* CSS + JS khối VAT */
add_action( 'wp_head', function () {
	if ( ! function_exists( 'is_checkout' ) || ! is_checkout() ) {
		return;
	}
	if ( function_exists( 'is_wc_endpoint_url' ) && ( is_wc_endpoint_url( 'order-pay' ) || is_wc_endpoint_url( 'order-received' ) ) ) {
		return;
	}
	echo '<style id="smlife-vat-css">'
		. '.woocommerce-checkout .smlife-vat-wrap{border:1px dashed #d3b48f;border-radius:10px;padding:14px 16px;margin:4px 0 8px;background:rgba(232,100,30,.03);}'
		. '.woocommerce-checkout .smlife-vat-toggle{display:flex;align-items:center;gap:9px;font-weight:600;color:#23262b;cursor:pointer;margin:0;font-size:14px;}'
		. '.woocommerce-checkout .smlife-vat-toggle input{width:17px;height:17px;}'
		. '.woocommerce-checkout .smlife-vat-fields{display:none;margin-top:14px;}'
		. '.woocommerce-checkout .smlife-vat-wrap.is-open .smlife-vat-fields{display:block;}'
		. '.woocommerce-checkout .smlife-vat-fields input{width:100%;border:1px solid #dcd6cd;border-radius:8px;padding:11px 13px;min-height:46px;margin:0 0 10px;box-shadow:none;background:#fff;}'
		. '.woocommerce-checkout .smlife-vat-fields input:focus{outline:none;border-color:#e8641e;box-shadow:0 0 0 3px rgba(232,100,30,.12);}'
		. '.woocommerce-checkout .smlife-vat-2{display:grid;grid-template-columns:1fr 1fr;gap:12px;}'
		. '.woocommerce-checkout .smlife-vat-note{font-size:12.5px;color:#1a5fb4;margin:2px 0 0;font-style:italic;}'
		. '@media(max-width:600px){.woocommerce-checkout .smlife-vat-2{grid-template-columns:1fr;}}'
		. '</style>';
	echo '<script>(function(){function init(){var w=document.querySelector(".smlife-vat-wrap");var c=document.getElementById("smlife_vat");if(!w||!c){return;}function s(){w.classList.toggle("is-open",c.checked);}c.addEventListener("change",s);s();}if(document.readyState!="loading"){init();}else{document.addEventListener("DOMContentLoaded",init);}document.body&&document.body.addEventListener("updated_checkout",init);})();</script>';
} );

/* Lưu thông tin VAT vào đơn (an toàn với HPOS) */
add_action( 'woocommerce_checkout_create_order', function ( $order, $data ) {
	if ( empty( $_POST['smlife_vat'] ) ) {
		return;
	}
	$order->update_meta_data( '_smlife_vat', 1 );
	$order->update_meta_data( '_smlife_vat_company', sanitize_text_field( wp_unslash( isset( $_POST['smlife_vat_company'] ) ? $_POST['smlife_vat_company'] : '' ) ) );
	$order->update_meta_data( '_smlife_vat_taxcode', sanitize_text_field( wp_unslash( isset( $_POST['smlife_vat_taxcode'] ) ? $_POST['smlife_vat_taxcode'] : '' ) ) );
	$order->update_meta_data( '_smlife_vat_email', sanitize_email( wp_unslash( isset( $_POST['smlife_vat_email'] ) ? $_POST['smlife_vat_email'] : '' ) ) );
	$order->update_meta_data( '_smlife_vat_address', sanitize_text_field( wp_unslash( isset( $_POST['smlife_vat_address'] ) ? $_POST['smlife_vat_address'] : '' ) ) );
}, 20, 2 );

/* Bắt buộc các trường VAT khi khách tick */
add_action( 'woocommerce_checkout_process', function () {
	if ( empty( $_POST['smlife_vat'] ) ) {
		return;
	}
	if ( empty( $_POST['smlife_vat_company'] ) ) {
		wc_add_notice( 'Vui lòng nhập Tên doanh nghiệp để xuất hóa đơn VAT.', 'error' );
	}
	if ( empty( $_POST['smlife_vat_taxcode'] ) ) {
		wc_add_notice( 'Vui lòng nhập Mã số thuế để xuất hóa đơn VAT.', 'error' );
	}
	if ( empty( $_POST['smlife_vat_email'] ) || ! is_email( wp_unslash( $_POST['smlife_vat_email'] ) ) ) {
		wc_add_notice( 'Vui lòng nhập Email nhận hóa đơn hợp lệ.', 'error' );
	}
} );

/* Hiện thông tin VAT trong trang quản trị đơn */
add_action( 'woocommerce_admin_order_data_after_billing_address', function ( $order ) {
	if ( ! $order->get_meta( '_smlife_vat' ) ) {
		return;
	}
	echo '<div style="margin-top:10px;padding:10px 12px;background:#fff6ef;border:1px solid #f3d6c0;border-radius:6px;">';
	echo '<strong>Xuất hóa đơn VAT</strong><br>';
	echo 'Tên DN: ' . esc_html( $order->get_meta( '_smlife_vat_company' ) ) . '<br>';
	echo 'MST: ' . esc_html( $order->get_meta( '_smlife_vat_taxcode' ) ) . '<br>';
	echo 'Email HĐ: ' . esc_html( $order->get_meta( '_smlife_vat_email' ) ) . '<br>';
	echo 'Địa chỉ: ' . esc_html( $order->get_meta( '_smlife_vat_address' ) );
	echo '</div>';
} );


/* ===== Submenu: SMLIFE Core > Gộp Cart + Checkout ===== */
add_action( 'admin_menu', function () {
	add_submenu_page(
		'smlife-core',
		'Gộp Cart + Checkout',
		'Gộp Cart + Checkout',
		'manage_options',
		'smlife-checkout',
		'smlife_checkout_page'
	);
}, 30 );

/* ===== Trang cài đặt (vào thẳng, không tab) ===== */
function smlife_checkout_page() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}
	$on1 = (int) smlife_cfg( 'checkout_combine_enabled', 1 );
	$on2 = (int) smlife_cfg( 'checkout_addtocart_redirect', 0 );

	echo '<div class="wrap smlife-wrap">';
	

	echo '<div class="smlife-hd"><h1>Gộp Cart + Checkout</h1>';
	echo '<p>Gộp giỏ hàng và thanh toán thành một trang: khách sửa số lượng, xóa sản phẩm ngay trên trang Thanh toán, và trang Giỏ hàng tự chuyển sang Thanh toán. Cần bật snippet 01.</p></div>';

	if ( isset( $_GET['updated'] ) ) {
		echo '<div class="notice notice-success is-dismissible"><p>Đã lưu cài đặt.</p></div>';
	}

	echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '">';
	echo '<input type="hidden" name="action" value="smlife_save_settings" />';
	wp_nonce_field( 'smlife_save_checkout', 'smlife_nonce' );

	echo '<div class="smlife-card"><h2>Tùy chọn</h2>';

	echo '<div class="smlife-opt">';
	echo '<label class="smlife-sw"><input type="checkbox" name="smlife_core_settings[checkout_combine_enabled]" value="1" ' . checked( 1, $on1, false ) . ' /><i></i></label>';
	echo '<span class="tx"><strong>Bật gộp Cart + Checkout</strong><em>Hiện giỏ hàng ngay trên trang Thanh toán và tự chuyển trang Giỏ hàng sang Thanh toán.</em></span>';
	echo '</div>';

	echo '<div class="smlife-opt">';
	echo '<label class="smlife-sw"><input type="checkbox" name="smlife_core_settings[checkout_addtocart_redirect]" value="1" ' . checked( 1, $on2, false ) . ' /><i></i></label>';
	echo '<span class="tx"><strong>Thêm vào giỏ ra thẳng Thanh toán</strong><em>Khi khách bấm Thêm vào giỏ thì đi thẳng tới trang Thanh toán. Đây là tùy chọn, có thể tắt.</em></span>';
	echo '</div>';

	echo '</div>';

	echo '<div class="smlife-note"><b>Lưu ý khi dùng</b><ul>';
	echo '<li>Hoạt động với giao diện thanh toán cổ điển (Flatsome dùng loại này).</li>';
	echo '<li>Với Flatsome: bật thêm <b>Auto update on quantity change</b> trong Theme Options &gt; WooCommerce &gt; Cart để cập nhật số lượng tức thì.</li>';
	echo '<li>Nếu bật <b>Thêm vào giỏ ra thẳng Thanh toán</b>: tắt <b>Enable AJAX add to cart buttons on archives</b> trong WooCommerce &gt; Settings &gt; Products để chạy được trên trang danh mục.</li>';
	echo '</ul></div>';

	submit_button( 'Lưu cài đặt' );
	echo '</form></div>';
}

/* ===== Lưu cài đặt ===== */
add_action( 'admin_post_smlife_save_settings', function () {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( 'Không đủ quyền.' );
	}
	check_admin_referer( 'smlife_save_checkout', 'smlife_nonce' );

	$in  = isset( $_POST['smlife_core_settings'] ) && is_array( $_POST['smlife_core_settings'] ) ? wp_unslash( $_POST['smlife_core_settings'] ) : array();
	$cur = get_option( 'smlife_core_settings', array() );
	if ( ! is_array( $cur ) ) {
		$cur = array();
	}
	$cur['checkout_combine_enabled']    = empty( $in['checkout_combine_enabled'] ) ? 0 : 1;
	$cur['checkout_addtocart_redirect'] = empty( $in['checkout_addtocart_redirect'] ) ? 0 : 1;

	update_option( 'smlife_core_settings', $cur );
	wp_safe_redirect( admin_url( 'admin.php?page=smlife-checkout&updated=1' ) );
	exit;
} );
