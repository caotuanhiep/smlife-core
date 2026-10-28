<?php
/**
 * SMLIFE Core - Module: Email Setting
 * Quan ly tap trung toan bo email cua website.
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

/**
 * SMLIFE - Snippet 07: Email Center (quản lý tập trung toàn bộ email)
 * ------------------------------------------------------------------
 * Thay plugin: Email Customizer + email thông báo của Contact Form 7 (Mua ngay)
 * Gắn vào menu "SMLIFE Core" (cần snippet 00). Nếu chưa có 00 vẫn chạy engine
 * bằng giá trị mặc định, nhưng không hiện trang quản trị.
 *
 * Code Snippets: chọn "Run everywhere".
 * Khi dán vào Code Snippets: KHÔNG copy dòng <?php ở trên cùng.
 *
 * Gồm:
 *   - Engine: render email theo mẫu (Header + Thân + Bảng đơn hàng + Footer).
 *   - Ghép vào WooCommerce: đơn mới (nội bộ), xác nhận, chờ thanh toán,
 *     thanh toán chưa thành công, thanh toán thành công.
 *   - Email "Đặt hàng nhanh" cho form Mua ngay (Contact Form 7).
 *   - Trang quản trị WYSIWYG: danh sách email, soạn nội dung, chèn token,
 *     chọn Header/Footer, xem trước, gửi thử.
 */

/* =====================================================================
 * PHẦN A - DỮ LIỆU & CẤU HÌNH
 * ===================================================================== */

/* Màu nhấn mặc định của email (tông cam) */
if ( ! defined( 'SMLIFE_EC_ACCENT' ) ) {
	define( 'SMLIFE_EC_ACCENT', '#e8641e' );
}

/* Cấu hình mặc định (dùng khi chưa lưu gì trong admin) */
function smlife_ec_defaults() {
	/* 1.25.0: ten thuong hieu lay theo tung website (truoc ghi cung ' . $br . ') */
	$br = smlife_ten_thuong_hieu();
	$accent   = SMLIFE_EC_ACCENT;
	$bg_email = 'https://shopdepot.vn/wp-content/uploads/2026/10/Email-background.png';
	$hotline  = '0914 913 179';
	$showroom = 'Showroom: Số 14, đường 73, Tân Hưng, HCM (Gần Lotte Mart Q.7)';
	$website  = wp_parse_url( home_url(), PHP_URL_HOST );
	$website  = $website ? preg_replace( '/^www\./', '', $website ) : 'website';
	$logo     = ''; // Dán URL logo trong tab Header & Footer.

	$headers = array(
		1 => array(
			'name'        => 'Header chung',
			'bg_image'    => $bg_email,
			'logo'        => $logo,
			'logo_w'      => 170,
			'title_color' => $accent,
		),
	);

	$footers = array(
		1 => array(
			'name'       => 'Footer chung',
			'bg'         => '#262626',
			'logo'       => $logo,
			'logo_w'     => 150,
			'hotline'    => $hotline,
			'showroom'   => $showroom,
			'website'    => $website,
			'text_color' => '#e9e9e9',
		),
	);

	$emails = array();

	$emails['quick_order'] = array(
		'label'        => 'Đặt hàng nhanh (Mua ngay)',
		'purpose'      => 'Gửi nội bộ cho shop khi khách đặt qua nút Mua ngay trên trang sản phẩm. Không phải đơn WooCommerce.',
		'enabled'      => 1,
		'wc'           => '',
		'subject'      => 'Đặt hàng nhanh từ {ten} - {sdt}',
		'heading'      => 'Đơn hàng mới',
		'heading_icon' => '🔔',
		'title'        => 'Đặt hàng nhanh',
		'body'         => '<p>Có một đơn <strong>Mua ngay</strong> vừa được gửi từ website. Thông tin khách hàng và sản phẩm bên dưới.</p>',
		'block'        => 'quick',
		'info_box'     => 1,
		'order_table'  => 0,
		'support'      => 0,
		'btn_label'    => '',
		'btn_url'      => '',
		'header_ver'   => 1,
		'footer_ver'   => 1,
		'to'           => 'admin',
	);

	$emails['lien_he'] = array(
		'label'        => 'Liên hệ từ website',
		'purpose'      => 'Gửi nội bộ khi khách gửi form liên hệ [smlife_lien_he] trên website. Bấm Trả lời trong hộp thư là trả thẳng cho khách.',
		'enabled'      => 1,
		'wc'           => '',
		'subject'      => 'Liên hệ mới từ {ten} - {sdt}',
		'heading'      => 'Liên hệ mới từ website',
		'heading_icon' => '✉️',
		'title'        => 'Liên hệ từ website',
		'body'         => '<p>Có một khách vừa gửi liên hệ từ website. Thông tin bên dưới.</p><p><strong>Chủ đề:</strong> {chu_de}</p><p><strong>Nội dung khách viết:</strong><br />{noi_dung}</p><p style="color:#8a8a8a;font-size:13px;margin-top:16px;">Gửi từ trang: {trang}</p>',
		'block'        => '',
		'info_box'     => 1,
		'order_table'  => 0,
		'support'      => 0,
		'btn_label'    => '',
		'btn_url'      => '',
		'header_ver'   => 1,
		'footer_ver'   => 1,
		'to'           => 'admin',
	);

	$emails['quick_customer'] = array(
		'label'        => 'Xác nhận cho khách (Mua ngay)',
		'purpose'      => 'Gửi cho KHÁCH sau khi đặt Mua ngay, CHỈ KHI khách có nhập email. Cảm ơn + báo nhân viên sẽ gọi xác nhận.',
		'enabled'      => 1,
		'wc'           => '',
		'subject'      => '' . $br . ' đã nhận đơn đặt hàng của bạn',
		'heading'      => 'Đã nhận đơn đặt hàng của bạn',
		'heading_icon' => '✅',
		'title'        => 'Xác nhận đặt hàng',
		'body'         => '<p>Xin chào <strong>{ten}</strong></p><p>Cảm ơn bạn đã đặt hàng tại ' . $br . '!</p><p>Chúng tôi đã nhận được yêu cầu của bạn. Nhân viên kinh doanh sẽ gọi điện xác nhận đơn hàng trong thời gian sớm nhất. Nếu cần gấp, vui lòng gọi hotline {hotline}.</p>',
		'block'        => 'quick',
		'info_box'     => 1,
		'order_table'  => 0,
		'support'      => 0,
		'btn_label'    => '',
		'btn_url'      => '',
		'header_ver'   => 1,
		'footer_ver'   => 1,
		'to'           => 'customer',
	);

	$emails['admin_new_order'] = array(
		'label'        => 'Thông báo nội bộ - Đơn hàng mới',
		'purpose'      => 'Gửi cho shop (admin) khi có đơn WooCommerce mới. Tương ứng email "New order" của WooCommerce.',
		'enabled'      => 1,
		'wc'           => 'new_order',
		'subject'      => 'Đơn hàng mới #{ma_don} - {ten}',
		'heading'      => 'Đơn hàng mới #{ma_don}',
		'heading_icon' => '🔔',
		'title'        => 'Thông báo nội bộ',
		'body'         => '<p><strong>Phương thức:</strong> {thanh_toan}</p>',
		'block'        => '',
		'info_box'     => 1,
		'order_table'  => 1,
		'support'      => 0,
		'btn_label'    => 'Xử lý đơn hàng',
		'btn_url'      => '{link_admin}',
		'header_ver'   => 1,
		'footer_ver'   => 1,
		'to'           => 'admin',
	);

	$emails['customer_processing'] = array(
		'label'        => 'Xác nhận đặt hàng thành công',
		'purpose'      => 'Gửi cho khách khi đơn vào trạng thái Đang xử lý. Tương ứng email "Processing order".',
		'enabled'      => 1,
		'wc'           => 'customer_processing_order',
		'subject'      => 'Xác nhận đơn hàng #{ma_don} tại ' . $br . '',
		'heading'      => 'Xác nhận đặt hàng thành công #{ma_don}',
		'heading_icon' => '✅',
		'title'        => 'Đơn hàng mới',
		'body'         => '<p>Xin chào <strong>{ten}</strong></p><p>Cảm ơn bạn đã lựa chọn ' . $br . '!</p><p>Đơn đặt hàng của bạn đã được tiếp nhận và xử lý trong thời gian sớm nhất.</p>',
		'block'        => '',
		'info_box'     => 1,
		'order_table'  => 1,
		'support'      => 0,
		'btn_label'    => 'Xem đơn hàng',
		'btn_url'      => '{link_donhang}',
		'header_ver'   => 1,
		'footer_ver'   => 1,
		'to'           => 'customer',
	);

	$emails['customer_on_hold'] = array(
		'label'        => 'Chờ thanh toán',
		'purpose'      => 'Gửi cho khách khi đơn ở trạng thái Tạm giữ/Chờ chuyển khoản. Tương ứng email "Order on-hold".',
		'enabled'      => 1,
		'wc'           => 'customer_on_hold_order',
		'subject'      => 'Đơn hàng #{ma_don} đang chờ thanh toán',
		'heading'      => 'Đơn hàng của bạn đang chờ thanh toán!',
		'heading_icon' => '✅',
		'title'        => 'Chờ thanh toán',
		'body'         => '<p>Xin chào <strong>{ten}</strong></p><p>Cảm ơn bạn đã lựa chọn ' . $br . '!</p><p>Chúng tôi sẽ tiến hành sản xuất, đóng và giao hàng cho bạn ngay sau khi nhận được chuyển khoản của bạn.</p>',
		'block'        => '',
		'info_box'     => 1,
		'order_table'  => 1,
		'support'      => 0,
		'btn_label'    => 'Xem hướng dẫn thanh toán',
		'btn_url'      => '{link_donhang}',
		'header_ver'   => 1,
		'footer_ver'   => 1,
		'to'           => 'customer',
	);

	$emails['customer_failed'] = array(
		'label'        => 'Thanh toán chưa thành công',
		'purpose'      => 'Gửi khi thanh toán thất bại. Tương ứng email "Failed order" (mặc định gửi admin).',
		'enabled'      => 1,
		'wc'           => 'failed_order',
		'subject'      => 'Thanh toán đơn hàng #{ma_don} chưa thành công',
		'heading'      => 'Đơn hàng không thành công #{ma_don}',
		'heading_icon' => '⚠️',
		'title'        => 'Thanh toán chưa thành công',
		'body'         => '<p>Xin chào <strong>{ten}</strong></p><p>Cảm ơn bạn đã lựa chọn ' . $br . '!</p><p>Thanh toán cho đơn hàng chưa thành công. ' . $br . ' rất xin lỗi vì trải nghiệm chưa tốt này. Nguyên nhân có thể do thẻ thanh toán của bạn chưa được tích hợp vào cổng thanh toán hoặc tài khoản của bạn không đủ tiền. Vui lòng thực hiện lại hoặc liên hệ Hotline để được hỗ trợ ngay: {hotline}</p>',
		'block'        => '',
		'info_box'     => 1,
		'order_table'  => 1,
		'support'      => 0,
		'btn_label'    => 'Thử thanh toán lại',
		'btn_url'      => '{link_donhang}',
		'header_ver'   => 1,
		'footer_ver'   => 1,
		'to'           => 'customer',
	);

	$emails['order_held'] = array(
		'label'        => 'Đơn hàng tạm giữ',
		'purpose'      => 'Mẫu dùng cho đơn tạm giữ (gửi thủ công hoặc gắn trạng thái tùy chỉnh).',
		'enabled'      => 0,
		'wc'           => '',
		'subject'      => 'Đơn hàng #{ma_don} đang được tạm giữ',
		'heading'      => 'Đơn hàng tạm giữ #{ma_don}',
		'heading_icon' => '⏳',
		'title'        => 'Đơn hàng tạm giữ',
		'body'         => '<p>Xin chào <strong>{ten}</strong></p><p>Cảm ơn bạn đã lựa chọn ' . $br . '!</p><p>Chúng tôi sẽ tiến hành sản xuất, đóng và giao hàng cho bạn ngay sau khi nhận được chuyển khoản của bạn.</p>',
		'block'        => '',
		'info_box'     => 1,
		'order_table'  => 1,
		'support'      => 0,
		'btn_label'    => '',
		'btn_url'      => '',
		'header_ver'   => 1,
		'footer_ver'   => 1,
		'to'           => 'customer',
	);

	$emails['customer_completed'] = array(
		'label'        => 'Thanh toán thành công',
		'purpose'      => 'Gửi cho khách khi đơn Hoàn thành. Tương ứng email "Completed order".',
		'enabled'      => 1,
		'wc'           => 'customer_completed_order',
		'subject'      => 'Thanh toán đơn hàng #{ma_don} thành công',
		'heading'      => 'Thanh toán thành công #{ma_don}',
		'heading_icon' => '📲',
		'title'        => 'Thanh toán thành công',
		'body'         => '<p>Xin chào <strong>{ten}</strong></p><p>Cảm ơn bạn đã lựa chọn ' . $br . '!</p><p>' . $br . ' đã nhận được thanh toán cho đơn hàng của bạn. Bộ phận giao hàng sẽ gọi điện thông báo cho quý khách trước khi giao hàng.</p>',
		'block'        => '',
		'info_box'     => 1,
		'order_table'  => 1,
		'support'      => 0,
		'btn_label'    => 'Xem đơn hàng',
		'btn_url'      => '{link_donhang}',
		'header_ver'   => 1,
		'footer_ver'   => 1,
		'to'           => 'customer',
	);

	return array(
		'enabled' => 1,
		'headers' => $headers,
		'footers' => $footers,
		'emails'  => $emails,
		'cf7'     => array(
			'form_id'   => 6745,
			'notify_to' => 'sales@smlife.vn',
			'f_name'    => 'your-name',
			'f_email'   => 'your-email',
			'f_phone'   => 'tel-688',
			'f_address' => 'your-address',
			'f_note'    => 'your-product',
			'f_special' => 'your-note',
			'f_link'    => '',
		),
	);
}

/* Lấy toàn bộ cấu hình đã trộn mặc định */
function smlife_ec_all() {
	$def   = smlife_ec_defaults();
	$saved = get_option( 'smlife_email_center', array() );
	if ( ! is_array( $saved ) ) {
		$saved = array();
	}
	$out = $def;
	if ( isset( $saved['enabled'] ) ) {
		$out['enabled'] = (int) $saved['enabled'];
	}
	foreach ( array( 'headers', 'footers' ) as $grp ) {
		if ( ! empty( $saved[ $grp ] ) && is_array( $saved[ $grp ] ) ) {
			foreach ( $saved[ $grp ] as $ver => $data ) {
				if ( isset( $out[ $grp ][ $ver ] ) && is_array( $data ) ) {
					$out[ $grp ][ $ver ] = array_merge( $out[ $grp ][ $ver ], $data );
				}
			}
		}
	}
	if ( ! empty( $saved['emails'] ) && is_array( $saved['emails'] ) ) {
		foreach ( $saved['emails'] as $key => $data ) {
			if ( isset( $out['emails'][ $key ] ) && is_array( $data ) ) {
				$out['emails'][ $key ] = array_merge( $out['emails'][ $key ], $data );
			}
		}
	}
	if ( ! empty( $saved['cf7'] ) && is_array( $saved['cf7'] ) ) {
		$out['cf7'] = array_merge( $out['cf7'], $saved['cf7'] );
	}
	return $out;
}

function smlife_ec_email_cfg( $key ) {
	$all = smlife_ec_all();
	return isset( $all['emails'][ $key ] ) ? $all['emails'][ $key ] : null;
}

/* =====================================================================
 * PHẦN B - ENGINE RENDER
 * ===================================================================== */

/* Tach dia chi giao hang thanh tung dong rieng.
 * WooCommerce tra ve chuoi HTML ngan cach bang <br/>. Truoc day code dung
 * wp_strip_all_tags() nen <br/> bi xoa thang, khong thay bang dau cach,
 * lam dia chi dinh lien thanh mot cuc: "...183 Le Thanh TonPhuong Ben Thanh...".
 * Ham nay doi <br/> thanh xuong dong that roi tach ra, dong thoi bo dong dau
 * neu dong do chinh la ten khach (WooCommerce chen ten vao dia chi). */
function smlife_ec_address_lines( $order ) {
	if ( ! ( $order instanceof WC_Order ) ) {
		return array();
	}
	$raw = (string) $order->get_formatted_billing_address();
	if ( '' === trim( $raw ) ) {
		return array();
	}
	$raw   = preg_replace( '#<br\s*/?>#i', "\n", $raw );
	$raw   = wp_strip_all_tags( $raw );
	$lines = array_values( array_filter( array_map( 'trim', explode( "\n", $raw ) ), 'strlen' ) );
	$name  = trim( (string) $order->get_formatted_billing_full_name() );
	if ( ! empty( $lines ) && '' !== $name && 0 === strcasecmp( $lines[0], $name ) ) {
		array_shift( $lines );
	}
	return $lines;
}

/* Danh sách token + mô tả (cho admin) */
function smlife_ec_token_list() {
	return array(
		'{ten}'          => 'Tên khách hàng',
		'{email}'        => 'Email khách',
		'{sdt}'          => 'Số điện thoại',
		'{diachi}'       => 'Địa chỉ nhận hàng (một dòng, ngăn bằng dấu phẩy)',
		'{diachi_dong}'  => 'Địa chỉ nhận hàng (mỗi phần một dòng)',
		'{ma_don}'       => 'Mã đơn hàng',
		'{ngay}'         => 'Ngày đặt',
		'{tong}'         => 'Tổng cộng',
		'{thanh_toan}'   => 'Phương thức thanh toán',
		'{hotline}'      => 'Hotline',
		'{showroom}'     => 'Showroom',
		'{website}'      => 'Website',
		'{link_donhang}' => 'Link xem đơn hàng',
		'{link_admin}'   => 'Link xử lý đơn (admin)',
		'{ghichu}'       => 'Ghi chú đơn hàng (Mua ngay)',
		'{luuy}'         => 'Lưu ý đặc biệt (Mua ngay)',
		'{link_sp}'      => 'Link sản phẩm (Mua ngay)',
	);
}

/* Dọn tiền tệ: bỏ thẻ HTML + giải mã ký hiệu ₫ */
function smlife_ec_money( $html ) {
	return trim( html_entity_decode( wp_strip_all_tags( $html ), ENT_QUOTES, 'UTF-8' ) );
}

/* Chuẩn hóa dữ liệu đơn hàng từ WC_Order hoặc mẫu */
function smlife_ec_order_data( $order = null, $extra = array() ) {
	$d = array(
		'ten'        => '',
		'email'      => '',
		'sdt'        => '',
		'diachi'     => '',
		'ma_don'     => '',
		'ngay'       => date_i18n( 'd/m/Y' ),
		'tong'       => '',
		'thanh_toan' => '',
		'link_donhang' => '',
		'link_admin'   => '',
		'ghichu'     => isset( $extra['ghichu'] ) ? $extra['ghichu'] : '',
		'diachi_dong' => '',
		'luuy'       => isset( $extra['luuy'] ) ? $extra['luuy'] : '',
		'noi_dung'   => isset( $extra['noi_dung'] ) ? $extra['noi_dung'] : '',
		'chu_de'     => isset( $extra['chu_de'] ) ? $extra['chu_de'] : '',
		'trang'      => isset( $extra['trang'] ) ? $extra['trang'] : '',
		'link_sp'    => isset( $extra['link_sp'] ) ? $extra['link_sp'] : '',
		'items'      => array(),
		'totals'     => array(),
		'note'       => '',
		'vat'        => array(),
		'nhanluc'    => '',
	);

	if ( $order instanceof WC_Order ) {
		$d['ten']    = trim( $order->get_formatted_billing_full_name() );
		$d['email']  = $order->get_billing_email();
		$d['sdt']    = $order->get_billing_phone();
		$lines            = smlife_ec_address_lines( $order );
		$d['diachi']      = implode( ', ', $lines );
		$d['diachi_dong'] = implode( '<br>', array_map( 'esc_html', $lines ) );
		$d['ma_don'] = $order->get_order_number();
		$d['ngay']   = $order->get_date_created() ? wc_format_datetime( $order->get_date_created(), 'd/m/Y' ) : $d['ngay'];
		$d['tong']   = smlife_ec_money( wc_price( $order->get_total(), array( 'currency' => $order->get_currency() ) ) );
		$d['thanh_toan']   = $order->get_payment_method_title();
		$d['link_donhang'] = $order->get_view_order_url();
		$d['link_admin']   = admin_url( 'post.php?post=' . $order->get_id() . '&action=edit' );
		$d['note']   = $order->get_customer_note();
		if ( $order->get_meta( '_smlife_vat' ) ) {
			$d['vat'] = array(
				'company' => $order->get_meta( '_smlife_vat_company' ),
				'taxcode' => $order->get_meta( '_smlife_vat_taxcode' ),
				'email'   => $order->get_meta( '_smlife_vat_email' ),
				'address' => $order->get_meta( '_smlife_vat_address' ),
			);
		}

		foreach ( $order->get_items() as $item ) {
			$product = $item->get_product();
			$img     = '';
			$sku     = '';
			if ( $product ) {
				$sku = $product->get_sku();
				$iid = $product->get_image_id();
				if ( $iid ) {
					$img = wp_get_attachment_image_url( $iid, 'thumbnail' );
				}
			}
			$d['items'][] = array(
				'name'  => $item->get_name(),
				'sku'   => $sku,
				'qty'   => $item->get_quantity(),
				'img'   => $img,
				/* 1.24.0: gia truoc giam (subtotal). Truoc dung get_total (da tru giam) ma dong Giam gia ben duoi lai tru nua, nhin nhu tru 2 lan */
				'total' => smlife_ec_money( wc_price( $item->get_subtotal(), array( 'currency' => $order->get_currency() ) ) ),
			);
		}
		if ( method_exists( $order, 'get_order_item_totals' ) ) {
			foreach ( $order->get_order_item_totals() as $k => $row ) {
				$d['totals'][] = array(
					'key'   => $k,
					'label' => isset( $row['label'] ) ? wp_strip_all_tags( $row['label'] ) : '',
					'value' => isset( $row['value'] ) ? smlife_ec_money( $row['value'] ) : '',
				);
			}
		}
	} else {
		// Dữ liệu mẫu để xem trước.
		$d['ten']    = isset( $extra['ten'] ) && '' !== $extra['ten'] ? $extra['ten'] : 'Nguyễn Văn A';
		$d['email']  = isset( $extra['email'] ) && '' !== $extra['email'] ? $extra['email'] : 'khachhang@example.com';
		$d['sdt']    = isset( $extra['sdt'] ) && '' !== $extra['sdt'] ? $extra['sdt'] : '0900000000';
		$d['diachi'] = isset( $extra['diachi'] ) && '' !== $extra['diachi'] ? $extra['diachi'] : '123 Đường Mẫu, P. Tân Hưng, TP. HCM';
		$d['ma_don'] = '33844';
		$d['tong']   = '4.332.000₫';
		$d['thanh_toan']   = 'COD Thanh toán khi giao hàng';
		$d['link_donhang'] = home_url( '/' );
		$d['link_admin']   = admin_url();
		$d['note']   = 'Ghi chú chi tiết đơn hàng, thông tin xuất VAT';
		$d['items']  = array(
			array( 'name' => 'Kệ sách gỗ trang trí hiện đại Bovina 4 ô', 'sku' => 'BS-Bovina.4', 'qty' => 1, 'img' => '', 'total' => '2.280.000₫' ),
			array( 'name' => 'Kệ sách gỗ trang trí hiện đại Bovina 4 ô', 'sku' => 'BS-Bovina.4', 'qty' => 1, 'img' => '', 'total' => '2.280.000₫' ),
		);
		$d['totals'] = array(
			array( 'key' => 'cart_subtotal',  'label' => 'Tổng số phụ:',  'value' => '4.560.000₫' ),
			array( 'key' => 'discount',       'label' => 'Giảm giá:',     'value' => '-228.000₫' ),
			array( 'key' => 'shipping',       'label' => 'Vận chuyển:',   'value' => '60.000₫ qua SMLIFE NOW' ),
			array( 'key' => 'payment_method', 'label' => 'Thanh toán:',   'value' => 'COD Thanh toán khi giao hàng' ),
			array( 'key' => 'order_total',    'label' => 'Tổng cộng:',    'value' => '4.332.000₫' ),
		);
		$d['vat'] = array(
			'company' => 'Công ty TNHH Nội thất ABC',
			'taxcode' => '0312345678',
			'email'   => 'ketoan@abc.com.vn',
			'address' => '123 Nguyễn Văn Linh, P. Tân Phong, Q.7, TP. HCM',
		);
	}

	return $d;
}

/* Thay token trong chuỗi */
function smlife_ec_apply_tokens( $html, $d ) {
	$map = array(
		'{ten}'          => $d['ten'],
		'{email}'        => $d['email'],
		'{sdt}'          => $d['sdt'],
		'{diachi}'       => $d['diachi'],
		'{ma_don}'       => $d['ma_don'],
		'{ngay}'         => $d['ngay'],
		'{tong}'         => $d['tong'],
		'{thanh_toan}'   => $d['thanh_toan'],
		'{link_donhang}' => $d['link_donhang'],
		'{link_admin}'   => $d['link_admin'],
		'{ghichu}'       => $d['ghichu'],
		'{luuy}'         => $d['luuy'],
		'{link_sp}'      => $d['link_sp'],
		'{noi_dung}'     => isset( $d['noi_dung'] ) ? $d['noi_dung'] : '',
		'{chu_de}'       => isset( $d['chu_de'] ) ? $d['chu_de'] : '',
		'{trang}'        => isset( $d['trang'] ) ? $d['trang'] : '',
		'{hotline}'      => smlife_ec_footer_val( 'hotline' ),
		'{showroom}'     => smlife_ec_footer_val( 'showroom' ),
		'{website}'      => smlife_ec_footer_val( 'website' ),
	);
	return str_replace( array_keys( $map ), array_values( $map ), $html );
}

function smlife_ec_footer_val( $k ) {
	$all = smlife_ec_all();
	return isset( $all['footers'][1][ $k ] ) ? $all['footers'][1][ $k ] : '';
}

/* Render HEADER email */
function smlife_ec_render_header( $hv, $title ) {
	$all = smlife_ec_all();
	$h   = isset( $all['headers'][ $hv ] ) ? $all['headers'][ $hv ] : $all['headers'][1];
	$bg  = $h['bg_image'];
	$logo = $h['logo'];
	$lw  = (int) $h['logo_w'];
	$tc  = $h['title_color'];

	$logo_html = $logo
		? '<img class="sm-logo" src="' . esc_url( $logo ) . '" width="' . $lw . '" alt="' . esc_attr( smlife_ten_thuong_hieu() ) . '" style="display:block;border:0;max-width:' . $lw . 'px;height:auto;" />'
		: '<span style="font-size:22px;font-weight:800;color:' . esc_attr( $tc ) . ';">SHOP<span style="color:#1a1a1a;">DEPOT</span></span>';

	$bg_attr = $bg ? ' background="' . esc_url( $bg ) . '" style="background:#eef4fb url(\'' . esc_url( $bg ) . '\') no-repeat center top;background-size:cover;"' : ' style="background:#eef4fb;"';

	$out  = '<tr><td align="center"' . $bg_attr . '>';
	$out .= '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="width:100%;"><tr>';
	$out .= '<td class="sm-head-logo" align="left" valign="middle" style="padding:24px 28px;">' . $logo_html . '</td>';
	$out .= '<td class="sm-head-title" align="right" valign="middle" style="padding:24px 28px;font-size:20px;font-weight:800;color:' . esc_attr( $tc ) . ';">' . esc_html( $title ) . '</td>';
	$out .= '</tr></table></td></tr>';
	return $out;
}

/* Render FOOTER email */
function smlife_ec_render_footer( $fv ) {
	$all = smlife_ec_all();
	$f   = isset( $all['footers'][ $fv ] ) ? $all['footers'][ $fv ] : $all['footers'][1];
	$logo = $f['logo'];
	$lw  = (int) $f['logo_w'];
	$accent = SMLIFE_EC_ACCENT;

	$logo_html = $logo
		? '<img class="sm-logo" src="' . esc_url( $logo ) . '" width="' . $lw . '" alt="' . esc_attr( smlife_ten_thuong_hieu() ) . '" style="display:block;border:0;max-width:' . $lw . 'px;height:auto;" />'
		: '<span style="font-size:20px;font-weight:800;color:#ffffff;">SHOP<span style="color:' . $accent . ';">DEPOT</span></span>';

	$out  = '<tr><td style="background:' . esc_attr( $f['bg'] ) . ';padding:22px 28px;">';
	$out .= '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="width:100%;"><tr>';
	$out .= '<td class="sm-foot-l" align="left" valign="middle" width="42%">' . $logo_html . '</td>';
	$out .= '<td class="sm-foot-r" align="right" valign="middle" style="color:' . esc_attr( $f['text_color'] ) . ';font-size:13px;line-height:1.7;">';
	$out .= '<div style="font-size:19px;font-weight:800;color:' . $accent . ';">Hotline: ' . esc_html( $f['hotline'] ) . '</div>';
	$out .= '<div>' . esc_html( $f['showroom'] ) . '</div>';
	if ( ! empty( $f['website'] ) ) {
		$out .= '<div style="color:' . $accent . ';font-weight:600;">' . esc_html( $f['website'] ) . '</div>';
	}
	$out .= '</td></tr></table></td></tr>';
	return $out;
}

/* Render khối thông tin khách */
function smlife_ec_render_infobox( $d ) {
	$out  = '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#f3f3f3;border-radius:10px;margin:18px 0;"><tr><td style="padding:18px 22px;font-size:14px;color:#333;line-height:1.9;">';
	$out .= '<div><strong>Khách hàng:</strong> ' . esc_html( $d['ten'] ) . '</div>';
	if ( $d['email'] ) {
		$out .= '<div><strong>Email:</strong> ' . esc_html( $d['email'] ) . '</div>';
	}
	if ( $d['sdt'] ) {
		$out .= '<div><strong>SĐT:</strong> ' . esc_html( $d['sdt'] ) . '</div>';
	}
	/* 1.24.1: gom dia chi thanh mot dong nhu email Mua ngay (truoc in moi phan mot dong) */
	if ( $d['diachi'] ) {
		$out .= '<div><strong>Địa chỉ:</strong> ' . esc_html( $d['diachi'] ) . '</div>';
	}
	$out .= '</td></tr></table>';
	return $out;
}

/* Render bảng tóm tắt đơn hàng */
function smlife_ec_render_table( $d ) {
	$accent = SMLIFE_EC_ACCENT;
	$out  = '<h3 style="font-size:17px;color:#23262b;margin:22px 0 4px;">Tóm tắt đơn hàng</h3>';
	if ( $d['ma_don'] ) {
		$out .= '<div style="font-size:14px;color:#555;margin:0 0 10px;"><strong style="color:' . $accent . ';">Đơn hàng #' . esc_html( $d['ma_don'] ) . '</strong> | Ngày ' . esc_html( $d['ngay'] ) . '</div>';
	}
	$out .= '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" class="sm-tbl" style="width:100%;font-size:14px;color:#333;border-collapse:collapse;">';
	/* 1.24.1: chot do rong cot SL (48px) va Thanh tien (110px), cot San pham an phan con lai.
	   Cac dong tong (Van chuyen, Thanh toan... chu dai) tach ra bang rieng ben duoi, truoc
	   day nam chung bang nen cot Thanh tien bi keo rong, ten san pham bi ep nho. */
	$out .= '<tr style="border-bottom:1px solid #e5e5e5;"><th align="left" style="padding:0 0 9px;font-size:11.5px;letter-spacing:.04em;color:#8a8f97;text-transform:uppercase;">SẢN PHẨM</th><th align="center" width="48" style="width:48px;padding:0 0 9px;font-size:11.5px;color:#8a8f97;text-transform:uppercase;">SL</th><th align="right" width="110" style="width:110px;padding:0 0 9px;font-size:11.5px;color:#8a8f97;text-transform:uppercase;white-space:nowrap;">THÀNH TIỀN</th></tr>';

	foreach ( $d['items'] as $it ) {
		$thumb = $it['img']
			? '<img class="sm-pimg" src="' . esc_url( $it['img'] ) . '" width="56" height="56" style="display:block;border-radius:8px;border:0;object-fit:cover;" alt="" />'
			: '<div style="width:56px;height:56px;border-radius:8px;background:#eee;"></div>';
		$out .= '<tr style="border-bottom:1px solid #eee;"><td style="padding:12px 0;"><table role="presentation" cellpadding="0" cellspacing="0"><tr><td valign="top" style="padding-right:12px;">' . $thumb . '</td><td valign="top"><div style="font-weight:700;color:#23262b;">' . esc_html( $it['name'] ) . '</div>';
		if ( $it['sku'] ) {
			$out .= '<div style="color:#8a8f97;font-size:12.5px;">Mã sp: ' . esc_html( $it['sku'] ) . '</div>';
		}
		$out .= '</td></tr></table></td><td align="center" valign="top" width="48" style="width:48px;padding:12px 0;">' . esc_html( str_pad( (string) $it['qty'], 2, '0', STR_PAD_LEFT ) ) . '</td><td align="right" valign="top" width="110" style="width:110px;padding:12px 0;font-weight:600;white-space:nowrap;">' . esc_html( $it['total'] ) . '</td></tr>';
	}

	$out .= '</table>';
	/* Bang tong: nhan ben trai, gia tri ben phai duoc rong toi 62% de chu dai khong bi ep */
	$out .= '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" class="sm-tbl" style="width:100%;font-size:14px;color:#333;border-collapse:collapse;">';
	$dau = true;
	foreach ( $d['totals'] as $row ) {
		$is_total = ( 'order_total' === $row['key'] );
		$is_sub   = ( 'cart_subtotal' === $row['key'] );
		$lbl_style = 'padding:11px 0;color:#444;';
		$val_style = 'padding:11px 0;text-align:right;color:#333;font-weight:600;';
		if ( $is_total ) {
			$lbl_style = 'padding:13px 0;color:' . $accent . ';font-weight:800;font-size:15px;';
			$val_style = 'padding:13px 0;text-align:right;color:' . $accent . ';font-weight:800;font-size:18px;';
		} elseif ( $is_sub ) {
			$val_style = 'padding:11px 0;text-align:right;color:#1a5fb4;font-weight:700;';
		}
		/* dong dau khong ke vien tren: dong san pham cuoi da co vien duoi, tranh vien doi */
		$out .= '<tr style="' . ( $dau ? '' : 'border-top:1px solid #eee;' ) . '"><td valign="middle" style="' . $lbl_style . 'padding-right:12px;">' . esc_html( $row['label'] ) . '</td><td valign="middle" width="62%" style="' . $val_style . 'width:62%;">' . esc_html( $row['value'] ) . '</td></tr>';
		$dau = false;
	}

	if ( $d['note'] ) {
		$out .= '<tr style="border-top:1px solid #eee;"><td valign="top" style="padding:11px 12px 11px 0;color:#444;">Ghi chú đơn hàng:</td><td valign="top" width="62%" style="padding:11px 0;text-align:right;color:#333;width:62%;">' . esc_html( $d['note'] ) . '</td></tr>';
	}

	$out .= '</table>';
	return $out;
}

/* Render khối thông tin xuất hóa đơn VAT */
function smlife_ec_render_vat( $d ) {
	if ( empty( $d['vat'] ) || ( empty( $d['vat']['company'] ) && empty( $d['vat']['taxcode'] ) ) ) {
		return '';
	}
	$v = $d['vat'];
	$out  = '<h3 class="sm-h3" style="font-size:15px;color:#23262b;margin:20px 0 8px;">Thông tin xuất hóa đơn VAT</h3>';
	$out .= '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="width:100%;font-size:14px;color:#333;border-collapse:collapse;border:1px solid #eee;border-radius:8px;">';
	$rows = array(
		'Tên doanh nghiệp' => isset( $v['company'] ) ? $v['company'] : '',
		'Mã số thuế'       => isset( $v['taxcode'] ) ? $v['taxcode'] : '',
		'Email nhận HĐ'    => isset( $v['email'] ) ? $v['email'] : '',
		'Địa chỉ'          => isset( $v['address'] ) ? $v['address'] : '',
	);
	foreach ( $rows as $lbl => $val ) {
		if ( '' === $val ) {
			continue;
		}
		$out .= '<tr style="border-bottom:1px solid #f0f0f0;"><td style="padding:10px 14px;color:#777;width:40%;">' . esc_html( $lbl ) . '</td><td style="padding:10px 14px;font-weight:600;color:#23262b;">' . esc_html( $val ) . '</td></tr>';
	}
	$out .= '</table>';
	return $out;
}

/* Render khối đặt hàng nhanh (CF7) */
function smlife_ec_render_quick( $d ) {
	$stamp = $d['nhanluc'] ? $d['nhanluc'] : date_i18n( 'd/m/Y H:i' );
	$out   = '<div style="font-size:13.5px;color:#8a8f97;margin:0 0 14px;">Nhận lúc: ' . esc_html( $stamp ) . '</div>';
	$out  .= smlife_ec_render_infobox( $d );
	$out .= '<h3 style="font-size:17px;color:#23262b;margin:20px 0 10px;">Thông tin sản phẩm</h3>';
	$out .= '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="font-size:14px;color:#333;border-collapse:collapse;">';
	if ( $d['ghichu'] ) {
		$out .= '<tr style="border-bottom:1px solid #eee;"><td style="padding:12px 0;color:#555;width:38%;">Ghi chú đơn hàng:</td><td style="padding:12px 0;font-weight:700;color:#23262b;">' . esc_html( $d['ghichu'] ) . '</td></tr>';
	}
	if ( $d['luuy'] ) {
		$out .= '<tr style="border-bottom:1px solid #eee;"><td style="padding:12px 0;color:#555;vertical-align:top;">Lưu ý đặc biệt:</td><td style="padding:12px 0;">' . esc_html( $d['luuy'] ) . '</td></tr>';
	}
	if ( $d['link_sp'] ) {
		$out .= '<tr style="border-bottom:1px solid #eee;"><td style="padding:12px 0;color:#555;vertical-align:top;">Link sản phẩm:</td><td style="padding:12px 0;word-break:break-all;"><a href="' . esc_url( $d['link_sp'] ) . '" style="color:' . SMLIFE_EC_ACCENT . ';">' . esc_html( $d['link_sp'] ) . '</a></td></tr>';
	}
	$out .= '</table>';
	return $out;
}

/* Render khối hỗ trợ (hotline) */
function smlife_ec_render_support() {
	$accent = SMLIFE_EC_ACCENT;
	$hotline = smlife_ec_footer_val( 'hotline' );
	$showroom = smlife_ec_footer_val( 'showroom' );
	$tel = preg_replace( '/[^0-9+]/', '', $hotline );
	$out  = '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:rgba(232,100,30,.09);border-radius:12px;margin:18px 0 0;"><tr><td style="padding:18px 22px;">';
	$out .= '<div style="font-weight:700;color:#23262b;font-size:14px;margin:0 0 8px;">Hỗ trợ &amp; lưu ý</div>';
	$out .= '<div style="font-size:13px;color:#444;line-height:1.6;margin:0 0 12px;">Nếu bạn chưa thấy nhân viên Sales liên hệ xác nhận đơn hàng, vui lòng gọi hotline để được hỗ trợ ngay.</div>';
	$out .= '<a href="tel:' . esc_attr( $tel ) . '" style="display:inline-block;background:' . $accent . ';color:#fff;font-weight:700;font-size:15px;border-radius:999px;padding:9px 20px;text-decoration:none;">Hotline: ' . esc_html( $hotline ) . '</a>';
	$out .= '<div style="font-size:13px;color:#444;margin:12px 0 0;">' . esc_html( $showroom ) . '</div>';
	$out .= '</td></tr></table>';
	return $out;
}

/* Render toàn bộ 1 email -> HTML hoàn chỉnh */
function smlife_ec_render( $key, $order = null, $extra = array() ) {
	$cfg = smlife_ec_email_cfg( $key );
	if ( ! $cfg ) {
		return '';
	}
	$d    = smlife_ec_order_data( $order, $extra );
	$accent = SMLIFE_EC_ACCENT;

	$title   = smlife_ec_apply_tokens( $cfg['title'], $d );
	$heading = smlife_ec_apply_tokens( $cfg['heading'], $d );
	$body    = smlife_ec_apply_tokens( $cfg['body'], $d );

	$inner  = '';
	// Tiêu đề H2 + icon
	if ( '' !== trim( $heading ) ) {
		$icon = ! empty( $cfg['heading_icon'] ) ? '<span style="margin-right:8px;">' . esc_html( $cfg['heading_icon'] ) . '</span>' : '';
		$inner .= '<h2 class="sm-h2" style="font-size:20px;color:' . $accent . ';margin:0 0 14px;font-weight:800;line-height:1.3;">' . $icon . esc_html( $heading ) . '</h2>';
	}
	// Thân (WYSIWYG)
	$inner .= '<div style="font-size:15px;color:#3c3c3c;line-height:1.75;">' . wp_kses_post( $body ) . '</div>';

	// Các khối
	if ( 'quick' === $cfg['block'] ) {
		$inner .= smlife_ec_render_quick( $d );
	} else {
		if ( ! empty( $cfg['info_box'] ) ) {
			$inner .= smlife_ec_render_infobox( $d );
		}
		if ( ! empty( $cfg['order_table'] ) ) {
			$inner .= smlife_ec_render_table( $d );
			$inner .= smlife_ec_render_vat( $d );
		}
	}

	// Nút
	if ( ! empty( $cfg['btn_label'] ) ) {
		$url = smlife_ec_apply_tokens( $cfg['btn_url'], $d );
		$url = $url ? $url : '#';
		$inner .= '<table role="presentation" cellpadding="0" cellspacing="0" style="margin:22px 0 0;"><tr><td style="background:' . $accent . ';border-radius:8px;"><a href="' . esc_url( $url ) . '" style="display:inline-block;padding:13px 26px;color:#fff;font-weight:700;text-decoration:none;font-size:15px;">' . esc_html( $cfg['btn_label'] ) . '</a></td></tr></table>';
	}

	// Hỗ trợ
	if ( ! empty( $cfg['support'] ) ) {
		$inner .= smlife_ec_render_support();
	}

	// Khung ngoài
	$style = '<style>'
		. 'body{margin:0;padding:0;}'
		. 'img{border:0;outline:none;text-decoration:none;}'
		. 'table{border-collapse:collapse;}'
		. '@media only screen and (max-width:600px){'
		. '.sm-outer{padding:10px 0!important;}'
		. '.sm-card{border-radius:0!important;}'
		. '.sm-body{padding:22px 16px!important;}'
		. '.sm-head-logo,.sm-head-title{padding:16px 16px!important;}'
		. '.sm-head-title{font-size:15px!important;}'
		. '.sm-logo{max-width:135px!important;}'
		. '.sm-foot-l,.sm-foot-r{display:block!important;width:100%!important;text-align:left!important;}'
		. '.sm-foot-r{padding-top:12px!important;}'
		. '.sm-h2{font-size:18px!important;}'
		. '.sm-tbl th,.sm-tbl td{font-size:13px!important;}'
		. 'img.sm-pimg{width:50px!important;height:50px!important;}'
		. '}'
		. '</style>';
	$html  = '<!DOCTYPE html><html><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><meta http-equiv="X-UA-Compatible" content="IE=edge">' . $style . '</head>';
	$html .= '<body style="margin:0;padding:0;background:#f1f1f1;-webkit-text-size-adjust:100%;">';
	$html .= '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" class="sm-outer" style="background:#f1f1f1;padding:24px 12px;"><tr><td align="center">';
	$html .= '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" class="sm-card" style="width:100%;max-width:640px;background:#ffffff;border-radius:14px;overflow:hidden;box-shadow:0 10px 30px rgba(0,0,0,.06);">';
	$html .= smlife_ec_render_header( 1, $title );
	$html .= '<tr><td class="sm-body" style="padding:28px 30px;">' . $inner . '</td></tr>';
	$html .= smlife_ec_render_footer( 1 );
	$html .= '</table></td></tr></table></body></html>';
	return $html;
}

function smlife_ec_subject( $key, $order = null, $extra = array() ) {
	$cfg = smlife_ec_email_cfg( $key );
	if ( ! $cfg ) {
		return '';
	}
	$d = smlife_ec_order_data( $order, $extra );
	return smlife_ec_apply_tokens( $cfg['subject'], $d );
}

/* =====================================================================
 * PHẦN C - GHÉP VÀO WOOCOMMERCE
 * ===================================================================== */

function smlife_ec_wc_map() {
	$all = smlife_ec_all();
	$map = array();
	foreach ( $all['emails'] as $key => $cfg ) {
		if ( ! empty( $cfg['wc'] ) ) {
			$map[ $cfg['wc'] ] = $key;
		}
	}
	return $map;
}

/* Ghi lại email WC đang chạy để biết đơn hàng + loại */
add_action( 'woocommerce_email_header', function ( $heading, $email ) {
	$GLOBALS['smlife_ec_current'] = $email;
}, 1, 2 );

/* Thay toàn bộ nội dung HTML của email WC bằng mẫu của mình */
add_filter( 'woocommerce_mail_content', function ( $content ) {
	$all = smlife_ec_all();
	if ( empty( $all['enabled'] ) ) {
		return $content;
	}
	$email = isset( $GLOBALS['smlife_ec_current'] ) ? $GLOBALS['smlife_ec_current'] : null;
	if ( ! $email || empty( $email->id ) ) {
		return $content;
	}
	$map = smlife_ec_wc_map();
	if ( ! isset( $map[ $email->id ] ) ) {
		return $content;
	}
	$key = $map[ $email->id ];
	$cfg = smlife_ec_email_cfg( $key );
	if ( empty( $cfg['enabled'] ) ) {
		return $content;
	}
	$order = ( isset( $email->object ) && $email->object instanceof WC_Order ) ? $email->object : null;
	if ( ! $order ) {
		return $content;
	}
	$html = smlife_ec_render( $key, $order );
	return $html ? $html : $content;
}, 99 );

/* Tùy chỉnh tiêu đề email WC theo cấu hình */
add_action( 'init', function () {
	$all = smlife_ec_all();
	if ( empty( $all['enabled'] ) ) {
		return;
	}
	foreach ( $all['emails'] as $key => $cfg ) {
		if ( empty( $cfg['wc'] ) || empty( $cfg['enabled'] ) ) {
			continue;
		}
		$wc_id = $cfg['wc'];
		add_filter( 'woocommerce_email_subject_' . $wc_id, function ( $subject, $order ) use ( $key ) {
			$s = smlife_ec_subject( $key, $order instanceof WC_Order ? $order : null );
			return $s ? $s : $subject;
		}, 20, 2 );
	}
} );

/* =====================================================================
 * PHẦN D - EMAIL MUA NGAY (CONTACT FORM 7)
 * ===================================================================== */

/* Form này có phải form đặt hàng nhanh mà Email Center quản lý không? */
function smlife_ec_cf7_is_target( $cf7 ) {
	$all = smlife_ec_all();
	if ( empty( $all['enabled'] ) ) {
		return false;
	}
	$conf = $all['cf7'];
	if ( ! empty( $conf['form_id'] ) ) {
		return (int) $conf['form_id'] === (int) $cf7->id();
	}
	// form_id = 0: coi là form đặt hàng nếu có dữ liệu ở field sản phẩm.
	if ( class_exists( 'WPCF7_Submission' ) ) {
		$sub = WPCF7_Submission::get_instance();
		if ( $sub ) {
			$data = $sub->get_posted_data();
			$pf   = ! empty( $conf['f_note'] ) ? $conf['f_note'] : 'your-product';
			if ( ! empty( $data[ $pf ] ) ) {
				return true;
			}
		}
	}
	return false;
}

/* Gửi email theo mẫu Email Center khi khách đặt Mua ngay */
add_action( 'wpcf7_before_send_mail', function ( $cf7 ) {
	if ( ! smlife_ec_cf7_is_target( $cf7 ) ) {
		return;
	}
	if ( ! class_exists( 'WPCF7_Submission' ) ) {
		return;
	}
	$sub = WPCF7_Submission::get_instance();
	if ( ! $sub ) {
		return;
	}
	$data = $sub->get_posted_data();
	$all  = smlife_ec_all();
	$conf = $all['cf7'];
	$get  = function ( $field ) use ( $data ) {
		if ( '' === $field || ! isset( $data[ $field ] ) ) {
			return '';
		}
		$v = $data[ $field ];
		return is_array( $v ) ? implode( ', ', $v ) : (string) $v;
	};

	// Link sản phẩm: ưu tiên field cấu hình; nếu trống thì lấy URL trang khách submit
	// (chính là trang sản phẩm khi bấm Mua ngay).
	$link = $get( $conf['f_link'] );
	if ( '' === $link ) {
		$u = method_exists( $sub, 'get_meta' ) ? $sub->get_meta( 'url' ) : '';
		if ( ! $u && ! empty( $_SERVER['HTTP_REFERER'] ) ) {
			$u = esc_url_raw( wp_unslash( $_SERVER['HTTP_REFERER'] ) );
		}
		$link = $u ? $u : '';
	}

	$extra = array(
		'ten'     => $get( $conf['f_name'] ),
		'email'   => $get( $conf['f_email'] ),
		'sdt'     => $get( $conf['f_phone'] ),
		'diachi'  => $get( $conf['f_address'] ),
		'ghichu'  => $get( $conf['f_note'] ),
		'luuy'    => $get( $conf['f_special'] ),
		'link_sp' => $link,
	);
	$headers = array( 'Content-Type: text/html; charset=UTF-8' );

	// 1) Email nội bộ cho shop (mẫu Đặt hàng nhanh).
	$qa = smlife_ec_email_cfg( 'quick_order' );
	if ( ! empty( $qa['enabled'] ) ) {
		$to = is_email( $conf['notify_to'] ) ? $conf['notify_to'] : get_option( 'admin_email' );
		$h  = $headers;
		if ( is_email( $extra['email'] ) ) {
			$h[] = 'Reply-To: ' . $extra['email'];
		}
		wp_mail( $to, smlife_ec_subject( 'quick_order', null, $extra ), smlife_ec_render( 'quick_order', null, $extra ), $h );
	}

	// 2) Email xác nhận cho khách - CHỈ KHI khách có nhập email hợp lệ.
	$qc = smlife_ec_email_cfg( 'quick_customer' );
	if ( ! empty( $qc['enabled'] ) && is_email( $extra['email'] ) ) {
		wp_mail( $extra['email'], smlife_ec_subject( 'quick_customer', null, $extra ), smlife_ec_render( 'quick_customer', null, $extra ), $headers );
	}
}, 20 );

/* Bỏ email mẫu mặc định của CF7 cho form đặt hàng nhanh (tránh gửi trùng) */
add_filter( 'wpcf7_skip_mail', function ( $skip, $cf7 ) {
	if ( smlife_ec_cf7_is_target( $cf7 ) ) {
		return true;
	}
	return $skip;
}, 10, 2 );

/* =====================================================================
 * PHẦN E - TRANG QUẢN TRỊ (WYSIWYG)
 * ===================================================================== */
add_action( 'admin_menu', function () {
	add_submenu_page(
		'smlife-core',
		'Email Setting',
		'Email Setting',
		'manage_options',
		'smlife-email-center',
		'smlife_ec_admin_page'
	);
}, 90 );

/* Tải media uploader cho nút chọn ảnh logo */
add_action( 'admin_enqueue_scripts', function ( $hook ) {
	if ( false !== strpos( (string) $hook, 'smlife-email-center' ) ) {
		wp_enqueue_media();
	}
} );

function smlife_ec_admin_name( $path ) {
	return 'smlife_ec[' . $path . ']';
}

function smlife_ec_admin_page() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}
	$all   = smlife_ec_all();
	$view  = isset( $_GET['view'] ) ? sanitize_key( wp_unslash( $_GET['view'] ) ) : 'emails';
	$keys  = array_keys( $all['emails'] );
	$email = isset( $_GET['email'] ) ? sanitize_key( wp_unslash( $_GET['email'] ) ) : $keys[0];
	if ( ! isset( $all['emails'][ $email ] ) ) {
		$email = $keys[0];
	}

	echo '<div class="wrap">';

	echo '<h1>SMLIFE Email Setting</h1>';

	if ( isset( $_GET['updated'] ) ) {
		echo '<div class="notice notice-success is-dismissible"><p>Đã lưu.</p></div>';
	}
	if ( isset( $_GET['test_sent'] ) ) {
		$ok = '1' === $_GET['test_sent'];
		echo '<div class="notice ' . ( $ok ? 'notice-success' : 'notice-error' ) . ' is-dismissible"><p>' . ( $ok ? 'Đã gửi email thử. Kiểm tra hộp thư.' : 'Gửi thử thất bại. Kiểm tra cấu hình gửi mail (SMTP).' ) . '</p></div>';
	}

	// Thanh điều hướng view
	$u_emails = admin_url( 'admin.php?page=smlife-email-center&view=emails' );
	$u_header = admin_url( 'admin.php?page=smlife-email-center&view=header' );
	echo '<h2 class="nav-tab-wrapper">';
	echo '<a href="' . esc_url( $u_emails ) . '" class="nav-tab ' . ( 'header' !== $view ? 'nav-tab-active' : '' ) . '">Danh sách Email</a>';
	echo '<a href="' . esc_url( $u_header ) . '" class="nav-tab ' . ( 'header' === $view ? 'nav-tab-active' : '' ) . '">Header &amp; Footer chung</a>';
	echo '</h2>';

	if ( 'header' === $view ) {
		smlife_ec_admin_header_screen( $all );
		echo '</div>';
		return;
	}

	echo '<div class="smlec-wrap">';

	// Cột trái: danh sách
	echo '<div class="smlec-list">';
	foreach ( $all['emails'] as $k => $cfg ) {
		$cls = ( $k === $email ) ? 'active' : '';
		$dot = ! empty( $cfg['enabled'] ) ? 'smlec-on' : 'smlec-off';
		$url = admin_url( 'admin.php?page=smlife-email-center&view=emails&email=' . $k );
		echo '<a href="' . esc_url( $url ) . '" class="' . esc_attr( $cls ) . '"><span class="smlec-dot ' . $dot . '"></span>' . esc_html( $cfg['label'] ) . '</a>';
	}
	echo '</div>';

	// Cột giữa: editor
	smlife_ec_admin_email_editor( $email, $all['emails'][ $email ] );

	// Cột phải: preview + gửi thử
	smlife_ec_admin_preview( $email );

	echo '</div>';
	echo '</div>';
}

function smlife_ec_admin_email_editor( $key, $cfg ) {
	echo '<div class="smlec-main">';
	echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '">';
	echo '<input type="hidden" name="action" value="smlife_ec_save_email" />';
	echo '<input type="hidden" name="email_key" value="' . esc_attr( $key ) . '" />';
	wp_nonce_field( 'smlife_ec_save_' . $key, 'smlife_ec_nonce' );

	echo '<h2 style="margin-top:4px;">' . esc_html( $cfg['label'] ) . '</h2>';
	echo '<div class="smlec-purpose"><strong>Mục đích:</strong> ' . esc_html( $cfg['purpose'] ) . '</div>';

	echo '<div class="smlec-field"><label class="t"><input type="checkbox" name="' . esc_attr( smlife_ec_admin_name( 'enabled' ) ) . '" value="1" ' . checked( 1, $cfg['enabled'], false ) . ' /> Bật gửi email này</label></div>';

	echo '<div class="smlec-field"><label class="t">Tiêu đề email</label><input type="text" name="' . esc_attr( smlife_ec_admin_name( 'subject' ) ) . '" value="' . esc_attr( $cfg['subject'] ) . '" /></div>';

	echo '<div class="smlec-row">';
	echo '<div class="smlec-field" style="flex:1 1 200px"><label class="t">Tiêu đề lớn trong email (H2)</label><input type="text" name="' . esc_attr( smlife_ec_admin_name( 'heading' ) ) . '" value="' . esc_attr( $cfg['heading'] ) . '" /></div>';
	echo '<div class="smlec-field" style="flex:0 0 110px"><label class="t">Icon</label><input type="text" name="' . esc_attr( smlife_ec_admin_name( 'heading_icon' ) ) . '" value="' . esc_attr( $cfg['heading_icon'] ) . '" /></div>';
	echo '</div>';

	echo '<div class="smlec-field" style="max-width:260px"><label class="t">Tiêu đề góc phải header</label><input type="text" name="' . esc_attr( smlife_ec_admin_name( 'title' ) ) . '" value="' . esc_attr( $cfg['title'] ) . '" /></div>';

	// Token palette
	echo '<div class="smlec-field"><label class="t">Biến động (bấm để chèn vào nội dung)</label><div class="smlec-tokens">';
	foreach ( smlife_ec_token_list() as $tok => $desc ) {
		echo '<button type="button" class="smlec-tok" data-tok="' . esc_attr( $tok ) . '" title="' . esc_attr( $desc ) . '">' . esc_html( $tok ) . '</button>';
	}
	echo '</div></div>';

	// WYSIWYG body
	echo '<div class="smlec-field"><label class="t">Nội dung</label>';
	wp_editor(
		$cfg['body'],
		'smlec_body',
		array(
			'textarea_name' => 'smlife_ec[body]',
			'textarea_rows' => 8,
			'media_buttons' => false,
			'teeny'         => true,
		)
	);
	echo '</div>';

	// Các khối hiển thị
	echo '<div class="smlec-field"><label class="t">Khối hiển thị kèm theo</label>';
	echo '<label style="display:block;margin:3px 0"><input type="checkbox" name="' . esc_attr( smlife_ec_admin_name( 'info_box' ) ) . '" value="1" ' . checked( 1, $cfg['info_box'], false ) . ' /> Khối thông tin khách hàng</label>';
	echo '<label style="display:block;margin:3px 0"><input type="checkbox" name="' . esc_attr( smlife_ec_admin_name( 'order_table' ) ) . '" value="1" ' . checked( 1, $cfg['order_table'], false ) . ' /> Bảng tóm tắt đơn hàng</label>';
	echo '<label style="display:block;margin:3px 0"><input type="checkbox" name="' . esc_attr( smlife_ec_admin_name( 'support' ) ) . '" value="1" ' . checked( 1, $cfg['support'], false ) . ' /> Khối hỗ trợ (hotline)</label>';
	echo '</div>';

	// Nút CTA
	echo '<div class="smlec-row">';
	echo '<div class="smlec-field" style="flex:1 1 200px"><label class="t">Nhãn nút (để trống nếu không có)</label><input type="text" name="' . esc_attr( smlife_ec_admin_name( 'btn_label' ) ) . '" value="' . esc_attr( $cfg['btn_label'] ) . '" /></div>';
	echo '<div class="smlec-field" style="flex:1 1 200px"><label class="t">Link nút</label><input type="text" name="' . esc_attr( smlife_ec_admin_name( 'btn_url' ) ) . '" value="' . esc_attr( $cfg['btn_url'] ) . '" /></div>';
	echo '</div>';

	submit_button( 'Lưu email này' );
	echo '</form>';

	// JS chèn token
	echo '<script>(function(){'
		. 'document.querySelectorAll(".smlec-tok").forEach(function(b){b.addEventListener("click",function(){'
		. 'var t=this.getAttribute("data-tok");'
		. 'if(window.tinymce&&tinymce.get("smlec_body")&&!tinymce.get("smlec_body").isHidden()){tinymce.get("smlec_body").execCommand("mceInsertContent",false,t);}'
		. 'else{var ta=document.getElementById("smlec_body");if(ta){var s=ta.selectionStart||0,e=ta.selectionEnd||0;ta.value=ta.value.substring(0,s)+t+ta.value.substring(e);ta.focus();}}'
		. '});});'
		. '})();</script>';

	echo '</div>';
}

function smlife_ec_admin_preview( $key ) {
	$html = smlife_ec_render( $key );
	echo '<div class="smlec-prev">';
	echo '<h3 style="margin-top:4px;">Xem trước</h3>';
	echo '<iframe style="width:100%;height:620px;border:1px solid #e5e5e5;border-radius:8px;background:#f1f1f1" srcdoc="' . esc_attr( $html ) . '"></iframe>';
	echo '<p class="description">Lưu email rồi tải lại trang để xem bản mới nhất.</p>';

	echo '<h3>Gửi thử</h3>';
	echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '" class="smlec-test">';
	echo '<input type="hidden" name="action" value="smlife_ec_send_test" />';
	echo '<input type="hidden" name="email_key" value="' . esc_attr( $key ) . '" />';
	wp_nonce_field( 'smlife_ec_test', 'smlife_ec_nonce' );
	echo '<input type="email" name="test_to" value="' . esc_attr( get_option( 'admin_email' ) ) . '" class="regular-text" style="width:100%;margin-bottom:8px" /> ';
	submit_button( 'Gửi thử', 'secondary', 'submit', false );
	echo '</form>';
	echo '</div>';
}

function smlife_ec_admin_header_screen( $all ) {
	echo '<div style="background:#fff;border:1px solid #dcdcde;border-radius:8px;padding:18px 22px;margin-top:14px;max-width:1000px">';
	echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '">';
	echo '<input type="hidden" name="action" value="smlife_ec_save_headers" />';
	wp_nonce_field( 'smlife_ec_headers', 'smlife_ec_nonce' );

	echo '<div class="smlec-field"><label class="t"><input type="checkbox" name="smlife_ec[enabled]" value="1" ' . checked( 1, $all['enabled'], false ) . ' /> Bật Email Center (ghép mẫu vào WooCommerce + CF7)</label></div>';

	echo '<div class="smlec-row">';
	foreach ( array( 1 ) as $v ) {
		$h = $all['headers'][ $v ];
		echo '<div style="flex:1 1 440px;border:1px solid #eee;border-radius:8px;padding:14px">';
		echo '<h3 style="margin-top:0">Header chung</h3>';
		echo '<div class="smlec-field"><label class="t">Ảnh nền header (URL)</label><input type="url" name="smlife_ec[headers][' . $v . '][bg_image]" value="' . esc_attr( $h['bg_image'] ) . '" style="width:100%" /></div>';
		echo '<div class="smlec-field"><label class="t">Logo (URL)</label><input type="url" class="smlec-img-field" name="smlife_ec[headers][' . $v . '][logo]" value="' . esc_attr( $h['logo'] ) . '" style="width:72%" /> <button type="button" class="button smlec-pick">Chọn ảnh</button></div>';
		echo '<div class="smlec-row"><div class="smlec-field" style="flex:0 0 130px"><label class="t">Rộng logo (px)</label><input type="number" name="smlife_ec[headers][' . $v . '][logo_w]" value="' . esc_attr( $h['logo_w'] ) . '" /></div>';
		echo '<div class="smlec-field" style="flex:0 0 160px"><label class="t">Màu tiêu đề</label><input type="text" name="smlife_ec[headers][' . $v . '][title_color]" value="' . esc_attr( $h['title_color'] ) . '" /></div></div>';
		echo '</div>';
	}
	echo '</div>';

	echo '<div class="smlec-row">';
	foreach ( array( 1 ) as $v ) {
		$f = $all['footers'][ $v ];
		echo '<div style="flex:1 1 440px;border:1px solid #eee;border-radius:8px;padding:14px">';
		echo '<h3 style="margin-top:0">Footer chung</h3>';
		echo '<div class="smlec-field"><label class="t">Màu nền</label><input type="text" name="smlife_ec[footers][' . $v . '][bg]" value="' . esc_attr( $f['bg'] ) . '" /></div>';
		echo '<div class="smlec-field"><label class="t">Logo (URL)</label><input type="url" class="smlec-img-field" name="smlife_ec[footers][' . $v . '][logo]" value="' . esc_attr( $f['logo'] ) . '" style="width:72%" /> <button type="button" class="button smlec-pick">Chọn ảnh</button></div>';
		echo '<div class="smlec-field" style="flex:0 0 130px"><label class="t">Rộng logo (px)</label><input type="number" name="smlife_ec[footers][' . $v . '][logo_w]" value="' . esc_attr( $f['logo_w'] ) . '" /></div>';
		echo '<div class="smlec-field"><label class="t">Hotline</label><input type="text" name="smlife_ec[footers][' . $v . '][hotline]" value="' . esc_attr( $f['hotline'] ) . '" style="width:100%" /></div>';
		echo '<div class="smlec-field"><label class="t">Showroom</label><input type="text" name="smlife_ec[footers][' . $v . '][showroom]" value="' . esc_attr( $f['showroom'] ) . '" style="width:100%" /></div>';
		echo '<div class="smlec-field"><label class="t">Website</label><input type="text" name="smlife_ec[footers][' . $v . '][website]" value="' . esc_attr( $f['website'] ) . '" style="width:100%" /></div>';
		echo '</div>';
	}
	echo '</div>';

	submit_button( 'Lưu Header & Footer' );
	echo '</form>';

	echo '<script>(function(){document.querySelectorAll(".smlec-pick").forEach(function(btn){btn.addEventListener("click",function(e){e.preventDefault();var field=this.previousElementSibling;while(field&&!field.classList.contains("smlec-img-field")){field=field.previousElementSibling;}if(!window.wp||!wp.media){return;}var frame=wp.media({title:"Chọn ảnh",multiple:false});frame.on("select",function(){var a=frame.state().get("selection").first().toJSON();if(field){field.value=a.url;}});frame.open();});});})();</script>';
	echo '</div>';
}

/* =====================================================================
 * PHẦN F - LƯU & GỬI THỬ
 * ===================================================================== */
add_action( 'admin_post_smlife_ec_save_email', function () {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( 'Không đủ quyền.' );
	}
	$key = isset( $_POST['email_key'] ) ? sanitize_key( wp_unslash( $_POST['email_key'] ) ) : '';
	check_admin_referer( 'smlife_ec_save_' . $key, 'smlife_ec_nonce' );

	$all = smlife_ec_all();
	if ( ! isset( $all['emails'][ $key ] ) ) {
		wp_die( 'Email không hợp lệ.' );
	}
	$in = isset( $_POST['smlife_ec'] ) && is_array( $_POST['smlife_ec'] ) ? wp_unslash( $_POST['smlife_ec'] ) : array();

	$saved = get_option( 'smlife_email_center', array() );
	if ( ! is_array( $saved ) ) {
		$saved = array();
	}
	if ( empty( $saved['emails'] ) || ! is_array( $saved['emails'] ) ) {
		$saved['emails'] = array();
	}

	$saved['emails'][ $key ] = array(
		'enabled'      => empty( $in['enabled'] ) ? 0 : 1,
		'subject'      => isset( $in['subject'] ) ? sanitize_text_field( $in['subject'] ) : '',
		'heading'      => isset( $in['heading'] ) ? sanitize_text_field( $in['heading'] ) : '',
		'heading_icon' => isset( $in['heading_icon'] ) ? sanitize_text_field( $in['heading_icon'] ) : '',
		'title'        => isset( $in['title'] ) ? sanitize_text_field( $in['title'] ) : '',
		'body'         => isset( $in['body'] ) ? wp_kses_post( $in['body'] ) : '',
		'info_box'     => empty( $in['info_box'] ) ? 0 : 1,
		'order_table'  => empty( $in['order_table'] ) ? 0 : 1,
		'support'      => empty( $in['support'] ) ? 0 : 1,
		'btn_label'    => isset( $in['btn_label'] ) ? sanitize_text_field( $in['btn_label'] ) : '',
		'btn_url'      => isset( $in['btn_url'] ) ? sanitize_text_field( $in['btn_url'] ) : '',
		'header_ver'   => isset( $in['header_ver'] ) ? max( 1, min( 2, (int) $in['header_ver'] ) ) : 1,
		'footer_ver'   => isset( $in['footer_ver'] ) ? max( 1, min( 2, (int) $in['footer_ver'] ) ) : 1,
	);

	update_option( 'smlife_email_center', $saved );
	wp_safe_redirect( admin_url( 'admin.php?page=smlife-email-center&view=emails&email=' . $key . '&updated=1' ) );
	exit;
} );

add_action( 'admin_post_smlife_ec_save_headers', function () {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( 'Không đủ quyền.' );
	}
	check_admin_referer( 'smlife_ec_headers', 'smlife_ec_nonce' );
	$in = isset( $_POST['smlife_ec'] ) && is_array( $_POST['smlife_ec'] ) ? wp_unslash( $_POST['smlife_ec'] ) : array();

	$saved = get_option( 'smlife_email_center', array() );
	if ( ! is_array( $saved ) ) {
		$saved = array();
	}
	$saved['enabled'] = empty( $in['enabled'] ) ? 0 : 1;

	$saved['headers'] = array();
	foreach ( array( 1 ) as $v ) {
		$h = isset( $in['headers'][ $v ] ) ? $in['headers'][ $v ] : array();
		$saved['headers'][ $v ] = array(
			'bg_image'    => isset( $h['bg_image'] ) ? esc_url_raw( $h['bg_image'] ) : '',
			'logo'        => isset( $h['logo'] ) ? esc_url_raw( $h['logo'] ) : '',
			'logo_w'      => isset( $h['logo_w'] ) ? absint( $h['logo_w'] ) : 160,
			'title_color' => isset( $h['title_color'] ) ? sanitize_text_field( $h['title_color'] ) : '#e8641e',
		);
	}

	$saved['footers'] = array();
	foreach ( array( 1 ) as $v ) {
		$f = isset( $in['footers'][ $v ] ) ? $in['footers'][ $v ] : array();
		$saved['footers'][ $v ] = array(
			'bg'         => isset( $f['bg'] ) ? sanitize_text_field( $f['bg'] ) : '#262626',
			'logo'       => isset( $f['logo'] ) ? esc_url_raw( $f['logo'] ) : '',
			'logo_w'     => isset( $f['logo_w'] ) ? absint( $f['logo_w'] ) : 150,
			'hotline'    => isset( $f['hotline'] ) ? sanitize_text_field( $f['hotline'] ) : '',
			'showroom'   => isset( $f['showroom'] ) ? sanitize_text_field( $f['showroom'] ) : '',
			'website'    => isset( $f['website'] ) ? sanitize_text_field( $f['website'] ) : '',
			'text_color' => '#e9e9e9',
		);
	}

	// Da bo o nhap CF7 (form Mua ngay dung snippet 06). Giu nguyen cau hinh CF7 da luu truoc do.
	$cur_all      = smlife_ec_all();
	$saved['cf7'] = ( isset( $cur_all['cf7'] ) && is_array( $cur_all['cf7'] ) ) ? $cur_all['cf7'] : array();

	update_option( 'smlife_email_center', $saved );
	wp_safe_redirect( admin_url( 'admin.php?page=smlife-email-center&view=header&updated=1' ) );
	exit;
} );

add_action( 'admin_post_smlife_ec_send_test', function () {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( 'Không đủ quyền.' );
	}
	check_admin_referer( 'smlife_ec_test', 'smlife_ec_nonce' );
	$key = isset( $_POST['email_key'] ) ? sanitize_key( wp_unslash( $_POST['email_key'] ) ) : '';
	$to  = isset( $_POST['test_to'] ) ? sanitize_email( wp_unslash( $_POST['test_to'] ) ) : '';
	$ok  = false;
	if ( is_email( $to ) && smlife_ec_email_cfg( $key ) ) {
		$subject = smlife_ec_subject( $key );
		$html    = smlife_ec_render( $key );
		$headers = array( 'Content-Type: text/html; charset=UTF-8' );
		$ok      = wp_mail( $to, '[Thử] ' . $subject, $html, $headers );
	}
	wp_safe_redirect( admin_url( 'admin.php?page=smlife-email-center&view=emails&email=' . $key . '&test_sent=' . ( $ok ? '1' : '0' ) ) );
	exit;
} );
