<?php
/**
 * SMLIFE Core - Module: Cau truc gia
 * Trinh bay lai khoi gia tren trang san pham.
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

/**
 * SMLIFE CORE - 05. Cấu trúc giá trang sản phẩm
 * ---------------------------------------------
 * Gộp từ snippet "PHP - Cấu trúc giá trang sản phẩm" vào SMLIFE Core.
 * - Đổi cách hiển thị giá: giá 0 -> "Liên hệ"; biến thể -> "Giá chỉ <giá thấp nhất>".
 * - Khi chọn biến thể -> đổi chữ thành "Giá mẫu này".
 * - Tự động chọn biến thể mặc định (biến thể đầu tiên) khi mở trang sản phẩm.
 * Có bật/tắt và sửa chữ trong SMLIFE Core > Cấu trúc giá.
 *
 * WPCode: Run everywhere. Khi dán: KHÔNG copy dòng mở PHP ở trên cùng.
 */

/* ===== Cấu hình ===== */
function smlife_price_opt() {
	$def = array(
		'enabled'    => 1,
		't_from'     => 'Giá chỉ 🧐',
		't_contact'  => 'Liên hệ',
		't_variation'=> '✔ Giá mẫu này: ',
		'autoselect' => 1,
	);
	$s = get_option( 'smlife_price', array() );
	return is_array( $s ) ? array_merge( $def, $s ) : $def;
}

/* Span "giá từ" dựng theo cấu hình (dùng chung cho filter 1 và 2). */
function smlife_price_from_span() {
	$o = smlife_price_opt();
	return '<span class="gia-tu-do-dam gia-tong">' . $o['t_from'] . '</span>';
}

/* ===== 1. Đổi hiển thị giá tổng ===== */
add_filter( 'woocommerce_get_price_html', function ( $price, $product ) {
	$o = smlife_price_opt();
	if ( empty( $o['enabled'] ) ) {
		return $price;
	}
	$contact_text = '<span style="color:#000; font-weight:normal;">' . $o['t_contact'] . '</span>';
	$gia_tu_text  = smlife_price_from_span();

	// Không phải biến thể và giá = 0 -> Liên hệ.
	/* 1.24.0: ep kieu so. PHP 8 coi '' == 0 la sai, san pham bo trong gia bi hien "Gia chi" khong so */
	if ( ! $product->is_type( 'variable' ) && (float) $product->get_price() <= 0 ) {
		return $gia_tu_text . ' ' . $contact_text;
	}

	// Sản phẩm biến thể.
	if ( $product->is_type( 'variable' ) ) {
		/* 1.24.0: lay gia thap nhat KHAC 0 (mot bien the de 0 thi truoc day hien "Gia chi 0d") */
		$ds = $product->get_variation_prices( true );
		$ds = isset( $ds['price'] ) ? array_filter( array_map( 'floatval', (array) $ds['price'] ), function ( $g ) {
			return $g > 0;
		} ) : array();
		if ( empty( $ds ) ) {
			return $gia_tu_text . ' ' . $contact_text;
		}
		return $gia_tu_text . ' ' . wc_price( min( $ds ) );
	}

	return $gia_tu_text . ' ' . $price;
}, 10, 2 );

/* ===== 2. Đổi chữ khi chọn biến thể ===== */
add_filter( 'woocommerce_available_variation', function ( $variation ) {
	$o = smlife_price_opt();
	if ( empty( $o['enabled'] ) ) {
		return $variation;
	}
	if ( isset( $variation['price_html'] ) && ! empty( $variation['price_html'] ) ) {
		$variation['price_html'] = str_replace(
			smlife_price_from_span(),
			'<span class="gia-tu-do-dam gia-mau">' . $o['t_variation'] . '</span>',
			$variation['price_html']
		);
	}
	return $variation;
}, 10, 1 );

/* ===== 3. Tự động chọn biến thể mặc định ===== */
add_action( 'wp_footer', function () {
	$o = smlife_price_opt();
	if ( empty( $o['enabled'] ) || empty( $o['autoselect'] ) ) {
		return;
	}
	if ( ! function_exists( 'is_product' ) || ! is_product() ) {
		return;
	}
	global $product;
	if ( ! $product || ! $product->is_type( 'variable' ) ) {
		return;
	}
	/* 1.24.0: admin da dat bien the mac dinh thi WooCommerce tu chon, khong de len.
	   Con lai chon bien the DAU TIEN CON HANG, duyet tung cai va dung ngay khi gap,
	   thay vi get_available_variations() dung du lieu cho moi bien the (rat nang
	   voi san pham nhieu mau x kich thuoc, WooCommerce tu bo qua khi > 30). */
	if ( $product->get_default_attributes() ) {
		return;
	}
	$attrs = null;
	foreach ( $product->get_children() as $cid ) {
		$v = wc_get_product( $cid );
		if ( $v && $v->is_purchasable() && $v->is_in_stock() && $v->variation_is_visible() ) {
			$attrs = $v->get_variation_attributes();
			break;
		}
	}
	if ( empty( $attrs ) ) {
		return;
	}
	$json = wp_json_encode( $attrs );

	$js  = '<script>jQuery(document).ready(function($){';
	$js .= 'var defaultAttributes = ' . $json . ';';
	$js .= '$(".variations_form").find(".variations select").each(function(){';
	$js .= 'var $select=$(this);var attribute=$select.attr("name");var value=defaultAttributes[attribute];';
	$js .= 'if(value){$select.val(value).trigger("change");}';
	$js .= '});});</script>';
	echo $js; // phpcs:ignore
} );

/* ===== Submenu: SMLIFE Core > Cấu trúc giá ===== */
add_action( 'admin_menu', function () {
	add_submenu_page(
		'smlife-core',
		'Cấu trúc giá',
		'Cấu trúc giá',
		'manage_options',
		'smlife-cau-truc-gia',
		'smlife_price_page'
	);
}, 12 );

function smlife_price_page() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}
	$o = smlife_price_opt();
	echo '<div class="wrap smlife-wrap">';
	echo '<div class="smc-hd"><h1>Cấu trúc giá trang sản phẩm</h1>';
	echo '<p>Cách hiển thị giá gốc, giá khuyến mãi và dòng giá theo mẫu trên trang sản phẩm.</p></div>';
	if ( isset( $_GET['updated'] ) ) {
		echo '<div class="notice notice-success is-dismissible"><p>Đã lưu.</p></div>';
	}
	echo '<div class="smlife-card">';
	echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '">';
	echo '<input type="hidden" name="action" value="smlife_price_save" />';
	wp_nonce_field( 'smlife_price_save', 'smlife_price_nonce' );
	echo '<table class="form-table" role="presentation">';
	echo '<tr><th scope="row">Bật tính năng</th><td><label><input type="checkbox" name="pr[enabled]" value="1" ' . checked( 1, $o['enabled'], false ) . ' /> Đổi cách hiển thị giá trên trang sản phẩm</label></td></tr>';
	echo '<tr><th scope="row">Chữ "giá từ"</th><td><input type="text" name="pr[t_from]" value="' . esc_attr( $o['t_from'] ) . '" class="regular-text" /> <span class="description">Đứng trước giá, vd: Giá chỉ 🧐</span></td></tr>';
	echo '<tr><th scope="row">Chữ khi giá = 0</th><td><input type="text" name="pr[t_contact]" value="' . esc_attr( $o['t_contact'] ) . '" class="regular-text" /> <span class="description">vd: Liên hệ</span></td></tr>';
	echo '<tr><th scope="row">Chữ khi chọn biến thể</th><td><input type="text" name="pr[t_variation]" value="' . esc_attr( $o['t_variation'] ) . '" class="regular-text" /> <span class="description">vd: ✔ Giá mẫu này: </span></td></tr>';
	echo '<tr><th scope="row">Tự chọn biến thể đầu</th><td><label><input type="checkbox" name="pr[autoselect]" value="1" ' . checked( 1, $o['autoselect'], false ) . ' /> Tự động chọn biến thể đầu tiên khi mở trang (hiện giá ngay)</label></td></tr>';
	echo '</table>';
	submit_button( 'Lưu' );
	echo '</form></div></div>';
}

add_action( 'admin_post_smlife_price_save', function () {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( 'Không đủ quyền.' );
	}
	check_admin_referer( 'smlife_price_save', 'smlife_price_nonce' );
	$in  = isset( $_POST['pr'] ) && is_array( $_POST['pr'] ) ? wp_unslash( $_POST['pr'] ) : array();
	$new = array(
		'enabled'    => empty( $in['enabled'] ) ? 0 : 1,
		't_from'     => isset( $in['t_from'] ) ? sanitize_text_field( $in['t_from'] ) : 'Giá chỉ 🧐',
		't_contact'  => isset( $in['t_contact'] ) ? sanitize_text_field( $in['t_contact'] ) : 'Liên hệ',
		't_variation'=> isset( $in['t_variation'] ) ? sanitize_text_field( $in['t_variation'] ) : '✔ Giá mẫu này: ',
		'autoselect' => empty( $in['autoselect'] ) ? 0 : 1,
	);
	update_option( 'smlife_price', $new );
	wp_safe_redirect( admin_url( 'admin.php?page=smlife-cau-truc-gia&updated=1' ) );
	exit;
} );
