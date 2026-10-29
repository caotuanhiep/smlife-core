<?php
/**
 * SMLIFE Core - Module: Danh gia cua hang Google (Google Customer Reviews)
 *
 * Hien hop thoai cua Google tren trang Dat hang thanh cong, hoi khach co dong y
 * nhan email khao sat sau khi nhan hang khong. Khach dong y thi Google tu gui
 * email hoi cham diem cua hang. Diem nay hien thanh "Diem xep hang cua cua hang"
 * trong Merchant Center va ngoi sao canh ten cua hang tren Google Shopping.
 *
 * Can bat chuong trinh "Danh gia cua khach hang" trong Merchant Center truoc,
 * roi nhap Merchant ID vao trang cai dat cua module.
 * Don khong co email hop le thi bo qua, khong hien hop thoai.
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

function smlife_gcr_opt() {
	$def = array(
		'enabled'     => 1,
		'merchant_id' => '',
		'giao_ngay'   => 3,             /* So ngay du kien giao hang */
		'vi_tri'      => 'CENTER_DIALOG',
		'gui_gtin'    => 0,
	);
	$s = get_option( 'smlife_gcr', array() );
	return is_array( $s ) ? array_merge( $def, $s ) : $def;
}

/* Ngay du kien giao: bo qua Chu nhat de khong bao som hon thuc te */
function smlife_gcr_ngay_giao( $so_ngay ) {
	$t = current_time( 'timestamp' );
	$n = 0;
	while ( $n < (int) $so_ngay ) {
		$t += DAY_IN_SECONDS;
		if ( '7' !== gmdate( 'N', $t ) ) {
			$n++;
		}
	}
	return gmdate( 'Y-m-d', $t );
}

/* In doan ma tren trang Dat hang thanh cong */
add_action( 'woocommerce_thankyou', function ( $order_id ) {
	$o = smlife_gcr_opt();
	if ( empty( $o['enabled'] ) || empty( $o['merchant_id'] ) || ! $order_id ) {
		return;
	}
	$order = wc_get_order( $order_id );
	if ( ! $order ) {
		return;
	}
	/* Moi don chi hien mot lan, khach tai lai trang khong bi hoi lai */
	if ( $order->get_meta( '_smlife_gcr_da_hien' ) ) {
		return;
	}
	$email = sanitize_email( $order->get_billing_email() );
	if ( ! is_email( $email ) ) {
		return;
	}
	$quoc_gia = $order->get_shipping_country() ? $order->get_shipping_country() : ( $order->get_billing_country() ? $order->get_billing_country() : 'VN' );

	$data = array(
		'merchant_id'             => (int) $o['merchant_id'],
		'order_id'                => (string) $order->get_order_number(),
		'email'                   => $email,
		'delivery_country'        => strtoupper( $quoc_gia ),
		'estimated_delivery_date' => smlife_gcr_ngay_giao( max( 1, (int) $o['giao_ngay'] ) ),
		'opt_in_style'            => in_array( $o['vi_tri'], array( 'CENTER_DIALOG', 'BOTTOM_RIGHT_DIALOG', 'BOTTOM_LEFT_DIALOG', 'TOP_RIGHT_DIALOG', 'TOP_LEFT_DIALOG', 'BOTTOM_TRAY' ), true ) ? $o['vi_tri'] : 'CENTER_DIALOG',
	);

	if ( ! empty( $o['gui_gtin'] ) ) {
		$gtins = array();
		foreach ( $order->get_items() as $item ) {
			$p = $item->get_product();
			if ( ! $p ) { continue; }
			$g = method_exists( $p, 'get_global_unique_id' ) ? (string) $p->get_global_unique_id() : '';
			if ( preg_match( '/^\d{8,14}$/', $g ) ) {
				$gtins[] = array( 'gtin' => $g );
			}
		}
		if ( $gtins ) {
			$data['products'] = $gtins;
		}
	}

	$order->update_meta_data( '_smlife_gcr_da_hien', time() );
	$order->save();
	?>
<script>window.___gcfg = { lang: 'vi' };</script>
<script src="https://apis.google.com/js/platform.js?onload=smlifeGcrOptIn" async defer></script>
<script>
window.smlifeGcrOptIn = function () {
	window.gapi.load('surveyoptin', function () {
		window.gapi.surveyoptin.render(<?php echo wp_json_encode( $data ); ?>);
	});
};
</script>
	<?php
}, 30 );

/* ===== Submenu: SMLIFE Core > Đánh giá cửa hàng Google ===== */
add_action( 'admin_menu', function () {
	add_submenu_page(
		'smlife-core',
		'Đánh giá cửa hàng Google',
		'Đánh giá cửa hàng Google',
		'manage_options',
		'smlife-danh-gia-google',
		'smlife_gcr_page'
	);
}, 12 );

function smlife_gcr_page() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}
	$o = smlife_gcr_opt();
	echo '<div class="wrap smlife-wrap">';
	echo '<div class="smc-hd"><h1>Đánh giá cửa hàng Google</h1>';
	echo '<p>Sau khi khách đặt hàng xong, Google hiện một hộp nhỏ hỏi khách có muốn nhận khảo sát không. Khách đồng ý thì vài ngày sau khi nhận hàng, Google gửi email nhờ chấm điểm cửa hàng. Đủ số lượng đánh giá thì ngôi sao hiện cạnh tên cửa hàng trên Google Shopping và quảng cáo.</p></div>';
	if ( isset( $_GET['updated'] ) ) {
		echo '<div class="notice notice-success is-dismissible"><p>Đã lưu.</p></div>';
	}
	if ( empty( $o['merchant_id'] ) ) {
		echo '<div class="notice notice-warning"><p>Chưa có Merchant ID nên module chưa chạy. Nhập Merchant ID bên dưới rồi bấm Lưu.</p></div>';
	}
	echo '<div class="smlife-card">';
	echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '">';
	echo '<input type="hidden" name="action" value="smlife_gcr_save" />';
	wp_nonce_field( 'smlife_gcr_save', 'smlife_gcr_nonce' );
	echo '<table class="form-table" role="presentation">';
	echo '<tr><th scope="row">Bật tính năng</th><td><label><input type="checkbox" name="gc[enabled]" value="1" ' . checked( 1, $o['enabled'], false ) . ' /> Hiện hộp đồng ý khảo sát trên trang Đặt hàng thành công</label></td></tr>';
	echo '<tr><th scope="row">Merchant ID</th><td><input type="text" name="gc[merchant_id]" value="' . esc_attr( $o['merchant_id'] ) . '" class="regular-text" style="max-width:200px" inputmode="numeric" /> <span class="description">Dãy số góc trên bên phải Merchant Center, dưới tên cửa hàng. smlife.vn là 132826251</span></td></tr>';
	echo '<tr><th scope="row">Số ngày giao dự kiến</th><td><input type="number" name="gc[giao_ngay]" value="' . esc_attr( $o['giao_ngay'] ) . '" min="1" max="30" class="small-text" /> ngày <span class="description">Google gửi email khảo sát sau ngày này. Không tính Chủ nhật.</span></td></tr>';
	echo '<tr><th scope="row">Vị trí hộp</th><td><select name="gc[vi_tri]">';
	foreach ( array( 'CENTER_DIALOG' => 'Giữa màn hình', 'BOTTOM_RIGHT_DIALOG' => 'Góc dưới bên phải', 'BOTTOM_LEFT_DIALOG' => 'Góc dưới bên trái', 'BOTTOM_TRAY' => 'Thanh dưới cùng' ) as $k => $v ) {
		echo '<option value="' . esc_attr( $k ) . '" ' . selected( $k, $o['vi_tri'], false ) . '>' . esc_html( $v ) . '</option>';
	}
	echo '</select></td></tr>';
	echo '<tr><th scope="row">Gửi mã GTIN</th><td><label><input type="checkbox" name="gc[gui_gtin]" value="1" ' . checked( 1, $o['gui_gtin'], false ) . ' /> Gửi kèm mã vạch sản phẩm</label><br><span class="description">Chỉ bật nếu sản phẩm có mã vạch GTIN thật. Hàng tự sản xuất thì để tắt.</span></td></tr>';
	echo '</table>';
	submit_button( 'Lưu' );
	echo '</form></div>';
	echo '<div class="smlife-card"><h2>Lưu ý</h2><ul style="list-style:disc;padding-left:20px">';
	echo '<li>Chỉ hiện với đơn có email hợp lệ. Đơn đặt qua điện thoại, Zalo hoặc form không nhập email sẽ không được hỏi.</li>';
	echo '<li>Mỗi đơn chỉ hiện một lần, khách tải lại trang không bị hỏi lại.</li>';
	echo '<li>Google cần đủ số đánh giá trong 12 tháng mới hiện điểm. Nên nhắc khách nhập email khi đặt hàng.</li>';
	echo '</ul></div></div>';
}

add_action( 'admin_post_smlife_gcr_save', function () {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( 'Không đủ quyền.' );
	}
	check_admin_referer( 'smlife_gcr_save', 'smlife_gcr_nonce' );
	$in = isset( $_POST['gc'] ) && is_array( $_POST['gc'] ) ? wp_unslash( $_POST['gc'] ) : array();
	$id = isset( $in['merchant_id'] ) ? preg_replace( '/\D/', '', (string) $in['merchant_id'] ) : '';
	$vt = isset( $in['vi_tri'] ) ? sanitize_text_field( $in['vi_tri'] ) : 'CENTER_DIALOG';
	update_option( 'smlife_gcr', array(
		'enabled'     => empty( $in['enabled'] ) ? 0 : 1,
		'merchant_id' => $id,
		'giao_ngay'   => isset( $in['giao_ngay'] ) ? max( 1, min( 30, (int) $in['giao_ngay'] ) ) : 3,
		'vi_tri'      => in_array( $vt, array( 'CENTER_DIALOG', 'BOTTOM_RIGHT_DIALOG', 'BOTTOM_LEFT_DIALOG', 'BOTTOM_TRAY' ), true ) ? $vt : 'CENTER_DIALOG',
		'gui_gtin'    => empty( $in['gui_gtin'] ) ? 0 : 1,
	) );
	wp_safe_redirect( admin_url( 'admin.php?page=smlife-danh-gia-google&updated=1' ) );
	exit;
} );
