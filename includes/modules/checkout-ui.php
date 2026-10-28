<?php
/**
 * SMLIFE Core - Module: Giao dien Checkout
 * Doi chu nut dat hang va tinh chinh giao dien trang Thanh toan / Hoan tat.
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

/* ============================================================
 * PHẦN B - GIAO DIỆN CHECKOUT
 * ============================================================ */

/* ===== Cấu hình ===== */
function smlife_ui() {
	$def = array(
		'co_accent'      => '#e8641e',
		'co_btn_color'   => '#e8641e',
		'co_btn_label'   => '',
		'co_maxw'        => 720,
		'co_radius'      => 8,
		'co_show_coupon' => 1,
		'co_show_vat'    => 1,
		'co_sec_bg'      => '#f2f3f5',
		'co_sec_ink'     => '#3c4043',
		'co_sec_bar'     => '#c3c7cc',
		'co_step2'       => 'Thông tin đơn hàng',
	);
	$s = get_option( 'smlife_ui', array() );
	return is_array( $s ) ? array_merge( $def, $s ) : $def;
}

function smlife_ui_hex2rgba( $hex, $a ) {
	$hex = ltrim( (string) $hex, '#' );
	if ( 3 === strlen( $hex ) ) {
		$hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
	}
	if ( 6 !== strlen( $hex ) || ! ctype_xdigit( $hex ) ) {
		$hex = 'e8641e';
	}
	return 'rgba(' . hexdec( substr( $hex, 0, 2 ) ) . ',' . hexdec( substr( $hex, 2, 2 ) ) . ',' . hexdec( substr( $hex, 4, 2 ) ) . ',' . $a . ')';
}

/* ===== Submenu ===== */
add_action( 'admin_menu', function () {
	add_submenu_page(
		'smlife-core',
		'Giao diện Checkout',
		'Giao diện Checkout',
		'manage_options',
		'smlife-giao-dien',
		'smlife_ui_page'
	);
}, 32 );

function smlife_ui_field( $k, $v ) {
	return 'name="ui[' . esc_attr( $k ) . ']" value="' . esc_attr( $v ) . '"';
}

function smlife_ui_page() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}
	$o = smlife_ui();
	echo '<div class="wrap smlife-wrap">';
	echo '<div class="smc-hd"><h1>Giao diện Checkout</h1>';
	echo '<p>Màu sắc, độ rộng và các khối hiện ẩn của trang Thanh toán và trang Đơn hàng hoàn tất.</p></div>';
	if ( isset( $_GET['updated'] ) ) {
		echo '<div class="notice notice-success is-dismissible"><p>Đã lưu.</p></div>';
	}

	echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '">';
	echo '<input type="hidden" name="action" value="smlife_ui_save" />';
	wp_nonce_field( 'smlife_ui_save', 'smlife_ui_nonce' );

	echo '<div class="smlife-card"><h2>Trang Checkout / Hoàn tất</h2><table class="form-table" role="presentation">';
	echo '<tr><th scope="row">Màu nhấn</th><td><input type="color" ' . smlife_ui_field( 'co_accent', $o['co_accent'] ) . ' /> <span class="description">Màu cam của tiêu đề, tổng tiền, nút...</span></td></tr>';
	echo '<tr><th scope="row">Màu nút Đặt hàng</th><td><input type="color" ' . smlife_ui_field( 'co_btn_color', $o['co_btn_color'] ) . ' /></td></tr>';
	echo '<tr><th scope="row">Chữ trên nút Đặt hàng</th><td><input type="text" ' . smlife_ui_field( 'co_btn_label', $o['co_btn_label'] ) . ' class="regular-text" placeholder="Để trống = giữ mặc định (ĐẶT HÀNG)" /></td></tr>';
	echo '<tr><th scope="row">Độ rộng tối đa (px)</th><td><input type="number" min="400" max="1200" ' . smlife_ui_field( 'co_maxw', $o['co_maxw'] ) . ' class="small-text" /></td></tr>';
	echo '<tr><th scope="row">Bo góc (px)</th><td><input type="number" min="0" max="30" ' . smlife_ui_field( 'co_radius', $o['co_radius'] ) . ' class="small-text" /></td></tr>';
	echo '<tr><th scope="row">Màu nền tiêu đề khu vực</th><td><input type="color" ' . smlife_ui_field( 'co_sec_bg', $o['co_sec_bg'] ) . ' /> <span class="description">Nền của các dải GIỎ HÀNG, THÔNG TIN THANH TOÁN, ĐƠN HÀNG CỦA BẠN, PHƯƠNG THỨC THANH TOÁN</span></td></tr>';
	echo '<tr><th scope="row">Màu chữ tiêu đề khu vực</th><td><input type="color" ' . smlife_ui_field( 'co_sec_ink', $o['co_sec_ink'] ) . ' /></td></tr>';
	echo '<tr><th scope="row">Màu vạch bên trái</th><td><input type="color" ' . smlife_ui_field( 'co_sec_bar', $o['co_sec_bar'] ) . ' /> <span class="description">Vạch dọc 3px ở đầu mỗi dải tiêu đề</span></td></tr>';
	echo '<tr><th scope="row">Chữ bước 2 trên thanh tiến trình</th><td><input type="text" ' . smlife_ui_field( 'co_step2', $o['co_step2'] ) . ' class="regular-text" placeholder="Thông tin đơn hàng" /> <span class="description">Thay cho chữ mặc định &quot;Chi tiết thanh toán&quot;. Để trống = giữ mặc định.</span></td></tr>';
	echo '<tr><th scope="row">Hiện / ẩn khối</th><td>';
	echo '<label style="display:block;margin:3px 0"><input type="checkbox" name="ui[co_show_coupon]" value="1" ' . checked( 1, $o['co_show_coupon'], false ) . ' /> Ô nhập mã ưu đãi</label>';
	echo '<label style="display:block;margin:3px 0"><input type="checkbox" name="ui[co_show_vat]" value="1" ' . checked( 1, $o['co_show_vat'], false ) . ' /> Ô xuất hóa đơn VAT</label>';
	echo '</td></tr>';
	echo '</table></div>';

	submit_button( 'Lưu giao diện' );
	echo '</form></div>';
}

/* ===== Lưu ===== */
add_action( 'admin_post_smlife_ui_save', function () {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( 'Không đủ quyền.' );
	}
	check_admin_referer( 'smlife_ui_save', 'smlife_ui_nonce' );
	$in = isset( $_POST['ui'] ) && is_array( $_POST['ui'] ) ? wp_unslash( $_POST['ui'] ) : array();

	$hex = function ( $v, $d ) {
		$v = sanitize_hex_color( $v );
		return $v ? $v : $d;
	};
	$cur = get_option( 'smlife_ui', array() );
	if ( ! is_array( $cur ) ) {
		$cur = array();
	}
	$new = array_merge( $cur, array(
		'co_accent'      => $hex( isset( $in['co_accent'] ) ? $in['co_accent'] : '', '#e8641e' ),
		'co_btn_color'   => $hex( isset( $in['co_btn_color'] ) ? $in['co_btn_color'] : '', '#e8641e' ),
		'co_btn_label'   => isset( $in['co_btn_label'] ) ? sanitize_text_field( $in['co_btn_label'] ) : '',
		'co_maxw'        => isset( $in['co_maxw'] ) ? max( 400, min( 1200, absint( $in['co_maxw'] ) ) ) : 720,
		'co_radius'      => isset( $in['co_radius'] ) ? max( 0, min( 30, absint( $in['co_radius'] ) ) ) : 8,
		'co_show_coupon' => empty( $in['co_show_coupon'] ) ? 0 : 1,
		'co_show_vat'    => empty( $in['co_show_vat'] ) ? 0 : 1,
		'co_sec_bg'      => $hex( isset( $in['co_sec_bg'] ) ? $in['co_sec_bg'] : '', '#f2f3f5' ),
		'co_sec_ink'     => $hex( isset( $in['co_sec_ink'] ) ? $in['co_sec_ink'] : '', '#3c4043' ),
		'co_sec_bar'     => $hex( isset( $in['co_sec_bar'] ) ? $in['co_sec_bar'] : '', '#c3c7cc' ),
		'co_step2'       => isset( $in['co_step2'] ) ? sanitize_text_field( $in['co_step2'] ) : 'Thông tin đơn hàng',
	) );
	update_option( 'smlife_ui', $new );
	wp_safe_redirect( admin_url( 'admin.php?page=smlife-giao-dien&updated=1' ) );
	exit;
} );

/* ===== Đổi chữ nút Đặt hàng ở Checkout ===== */
add_filter( 'woocommerce_order_button_text', function ( $text ) {
	$o = smlife_ui();
	return '' !== $o['co_btn_label'] ? $o['co_btn_label'] : $text;
}, 99 );

/* ===== CSS ghi đè Checkout + Hoàn tất ===== */
add_action( 'wp_head', function () {
	if ( ! function_exists( 'is_checkout' ) || ! is_checkout() ) {
		return;
	}
	$is_received = function_exists( 'is_wc_endpoint_url' ) && is_wc_endpoint_url( 'order-received' );
	$o           = smlife_ui();
	$accent      = $o['co_accent'];
	$weak        = smlife_ui_hex2rgba( $accent, '.09' );
	$css         = ':root{--sm-accent:' . $accent . ';--sm-accent-weak:' . $weak . ';}';

	if ( ! $is_received ) {
		$css .= ':root{--sm-maxw:' . (int) $o['co_maxw'] . 'px;--sm-radius:' . (int) $o['co_radius'] . 'px;';
		$css .= '--sm-sec-bg:' . $o['co_sec_bg'] . ';--sm-sec-ink:' . $o['co_sec_ink'] . ';--sm-sec-bar:' . $o['co_sec_bar'] . ';}';
		$css .= '.woocommerce-checkout #place_order{background:' . $o['co_btn_color'] . '!important;}';
		if ( empty( $o['co_show_coupon'] ) ) {
			$css .= '.woocommerce-checkout .woocommerce-form-coupon-toggle,.woocommerce-checkout .checkout_coupon{display:none!important;}';
		}
		if ( empty( $o['co_show_vat'] ) ) {
			$css .= '.woocommerce-checkout .smlife-vat-wrap{display:none!important;}';
		}
	}
	echo '<style id="smlife-ui-checkout">' . $css . '</style>';
}, 99 );

/* ===== Đổi chữ bước 2 trên thanh tiến trình Checkout ===== */
add_filter( 'gettext', 'smlife_ui_step2_label', 20, 3 );
function smlife_ui_step2_label( $translated, $text, $domain ) {
	/* 1.24.0: so chu truoc (re), chi chu dung moi goi is_checkout (dat: has_shortcode
	   dung lai regex moi lan, ma gettext chay hang nghin lan moi trang) */
	if ( 'Checkout details' !== $text && 'Chi tiết thanh toán' !== $translated ) {
		return $translated;
	}
	if ( ! function_exists( 'is_checkout' ) || ! is_checkout() ) {
		return $translated;
	}
	$o = smlife_ui();
	$new = trim( (string) $o['co_step2'] );
	if ( '' === $new ) {
		return $translated;
	}
	if ( 'Checkout details' === $text || 'Chi tiết thanh toán' === $translated ) {
		return $new;
	}
	return $translated;
}

/* ============================================================
 * PHẦN C - GIỎ HÀNG TRONG TRANG THANH TOÁN TỰ CẬP NHẬT
 * ============================================================
 * Trang Thanh toán của site có nhúng bảng giỏ hàng (form.woocommerce-cart-form)
 * để khách sửa số lượng ngay tại chỗ. Nhưng WooCommerce chỉ nạp script giỏ hàng
 * (wc-cart) ở trang Giỏ hàng, không nạp ở trang Thanh toán. Hệ quả: bấm tăng
 * giảm số lượng thì con số đổi nhưng không có gì gửi lên máy chủ, nút "Cập nhật
 * giỏ hàng" lại đang bị ẩn, nên thành tiền và tổng đơn đứng yên.
 *
 * Đoạn dưới bù đúng phần còn thiếu: đổi số lượng xong chờ 0,65 giây rồi gửi
 * chính form đó lên máy chủ bằng AJAX (dùng luôn nonce có sẵn trong form),
 * lấy kết quả về cập nhật lại các ô thành tiền, rồi báo WooCommerce tính lại
 * khối tổng đơn. Ô nhập số lượng và nút cộng trừ giữ nguyên, không dựng lại,
 * nên không mất trình xử lý của giao diện.
 */
add_action( 'wp_footer', function () {
	if ( ! function_exists( 'is_checkout' ) || ! is_checkout() ) { return; }
	if ( function_exists( 'is_order_received_page' ) && is_order_received_page() ) { return; }

	echo '<style id="smlife-ckqty-css">form.woocommerce-cart-form.smlife-ck-dang-tinh{opacity:.55;transition:opacity .15s}</style>'; // phpcs:ignore

	$js  = '(function(){';
	$js .= 'var f=document.querySelector("form.woocommerce-cart-form");if(!f)return;';
	$js .= 'var hen=null,dang=false,cho=false;';
	/* Cap nhat tung o trong mot dong, bo qua o chua chinh input so luong */
	$js .= 'function chep(cu,moi){var a=cu.children,b=moi.children;if(a.length!==b.length)return false;';
	$js .= 'for(var i=0;i<a.length;i++){if(a[i].querySelector&&a[i].querySelector("input.qty"))continue;';
	$js .= 'if(a[i].innerHTML!==b[i].innerHTML){a[i].innerHTML=b[i].innerHTML;}}return true;}';
	$js .= 'function xong(){dang=false;f.classList.remove("smlife-ck-dang-tinh");if(cho){cho=false;dat();}}';
	$js .= 'function gui(){';
	$js .= 'if(dang){cho=true;return;}dang=true;f.classList.add("smlife-ck-dang-tinh");';
	$js .= 'var fd=new FormData(f);fd.append("update_cart","1");';
	$js .= 'fetch(f.action||window.location.href,{method:"POST",body:fd,credentials:"same-origin"})';
	$js .= '.then(function(r){return r.text();}).then(function(h){';
	$js .= 'var d=new DOMParser().parseFromString(h,"text/html");';
	$js .= 'var nf=d.querySelector("form.woocommerce-cart-form");';
	$js .= 'var cu=f.querySelectorAll(".cart_item"),moi=nf?nf.querySelectorAll(".cart_item"):[];';
	$js .= 'var lai=(!nf||cu.length!==moi.length||!cu.length);';
	$js .= 'if(!lai){for(var i=0;i<cu.length;i++){if(!chep(cu[i],moi[i])){lai=true;break;}';
	$js .= 'var qa=cu[i].querySelector("input.qty"),qb=moi[i].querySelector("input.qty");';
	$js .= 'if(qa&&qb&&qa.value!==qb.value&&document.activeElement!==qa){qa.value=qb.value;}}}';
	$js .= 'if(nf){var n1=f.querySelector(\'[name="woocommerce-cart-nonce"]\'),n2=nf.querySelector(\'[name="woocommerce-cart-nonce"]\');';
	$js .= 'if(n1&&n2){n1.value=n2.value;}}';
	$js .= 'xong();if(lai){window.location.reload();return;}';
	$js .= 'if(window.jQuery){try{jQuery(document.body).trigger("update_checkout");}catch(e){}';
	$js .= 'try{jQuery(document.body).trigger("wc_fragment_refresh");}catch(e){}}';
	$js .= '}).catch(function(){xong();});}';
	$js .= 'function dat(){clearTimeout(hen);hen=setTimeout(gui,650);}';
	$js .= 'function la(t){return t&&t.classList&&t.classList.contains("qty");}';
	$js .= 'f.addEventListener("change",function(e){if(la(e.target))dat();});';
	$js .= 'f.addEventListener("input",function(e){if(la(e.target))dat();});';
	$js .= 'f.addEventListener("click",function(e){if(e.target.closest&&e.target.closest(".quantity"))dat();});';
	$js .= 'f.addEventListener("keyup",function(e){if(la(e.target)&&e.key==="Enter"){e.preventDefault();dat();}});';
	$js .= '})();';
	echo '<script id="smlife-ckqty-js">' . $js . '</script>'; // phpcs:ignore
}, 97 );
