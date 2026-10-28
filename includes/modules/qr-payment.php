<?php
/**
 * SMLIFE Core - Module: Thanh toan QR chuyen khoan
 *
 * Thay cho plugin "iSures QrCode Payment for Vietnam". Ba khac biet chinh:
 *
 *   1. Ma QR duoc dung ngay tren website theo chuan VietQR / EMVCo, khong goi
 *      ra dich vu ben thu ba. Plugin cu goi sang qrcode.io.vn moi lan khach
 *      dat hang, cham thi hong anh, va so tien lan ma don di qua server la.
 *   2. Khong co trinh kiem tra ban cap nhat - day la thu dang do day error log
 *      cua hosting bang dong "Trying to access array offset on null".
 *   3. Dung LAI dung ma cong thanh toan 'vicweb_bacs' cua plugin cu, nen moi
 *      rang buoc Smart Coupons theo phuong thuc thanh toan va moi don hang cu
 *      van chay nguyen, khong phai sua lai gi.
 *
 * Vi dung lai ma do, HAI THU KHONG DUOC BAT CUNG LUC. Module tu dung im neu
 * phat hien plugin iSures con hoat dong.
 *
 * Ma QR chi xuat hien o trang cam on va trang xem don cua khach, khong gui
 * trong email (email khong chay duoc JavaScript).
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/* Dung lai dung ma cong thanh toan cua plugin cu */
function smlife_qr_id() { return 'vicweb_bacs'; }
function smlife_qr_isures() { return 'isures-qrcode-payment-for-vietnam/isures-qrcode-payment-for-vietnam.php'; }

/* ===== 1. Danh sach ngan hang ===== */

/* Ma BIN do NAPAS cap. Ngan hang nao khong co trong day thi chon "Tu nhap". */
function smlife_qr_banks() {
	return array(
		'970436' => 'Vietcombank (VCB)',
		'970415' => 'VietinBank',
		'970418' => 'BIDV',
		'970405' => 'Agribank',
		'970407' => 'Techcombank',
		'970422' => 'MB Bank',
		'970416' => 'ACB',
		'970432' => 'VPBank',
		'970423' => 'TPBank',
		'970403' => 'Sacombank',
		'970437' => 'HDBank',
		'970441' => 'VIB',
		'970443' => 'SHB',
		'970431' => 'Eximbank',
		'970426' => 'MSB',
		'970448' => 'OCB',
		'970440' => 'SeABank',
		'970429' => 'SCB',
		'970449' => 'LPBank',
		'970428' => 'Nam A Bank',
		'970412' => 'PVcomBank',
		'970409' => 'BacA Bank',
		'970425' => 'ABBANK',
		'970427' => 'VietABank',
		'970419' => 'NCB',
		'970433' => 'VietBank',
		'970438' => 'BaoViet Bank',
		'970400' => 'SaigonBank',
		'970430' => 'PGBank',
		'970452' => 'KienlongBank',
		'970454' => 'BVBank',
		'970406' => 'DongA Bank',
		'970408' => 'GPBank',
		'970414' => 'Oceanbank',
		'970444' => 'CBBank',
		'970446' => 'Co-opBank',
		'970421' => 'VRB',
		'970434' => 'Indovina Bank',
		'970424' => 'Shinhan Bank',
		'970457' => 'Woori Bank',
		'970458' => 'UOB',
		'970439' => 'Public Bank',
		'970442' => 'Hong Leong Bank',
		'970410' => 'Standard Chartered',
		'546034' => 'CAKE by VPBank',
		'546035' => 'Ubank by VPBank',
		'963388' => 'Timo',
		'971005' => 'Viettel Money',
		'971011' => 'VNPT Money',
		'tay'    => '— Ngân hàng khác, tự nhập mã BIN —',
	);
}

/* ===== 2. Dung chuoi VietQR ===== */

/* Mot o du lieu EMVCo: ma 2 ky tu + do dai 2 chu so + noi dung */
function smlife_qr_tlv( $id, $val ) {
	return $id . sprintf( '%02d', strlen( $val ) ) . $val;
}

/* CRC-16/CCITT-FALSE. Kiem chung: chuoi "123456789" cho ra 29B1. */
function smlife_qr_crc16( $s ) {
	$crc = 0xFFFF;
	$n   = strlen( $s );
	for ( $i = 0; $i < $n; $i++ ) {
		$crc ^= ord( $s[ $i ] ) << 8;
		for ( $j = 0; $j < 8; $j++ ) {
			$crc = ( $crc & 0x8000 ) ? ( ( $crc << 1 ) ^ 0x1021 ) : ( $crc << 1 );
			$crc &= 0xFFFF;
		}
	}
	return strtoupper( sprintf( '%04X', $crc ) );
}

/* Bo dau tieng Viet va ky tu la. Noi dung chuyen khoan chi nhan chu khong dau. */
function smlife_qr_ascii( $s ) {
	$co = array(
		'à','á','ạ','ả','ã','â','ầ','ấ','ậ','ẩ','ẫ','ă','ằ','ắ','ặ','ẳ','ẵ',
		'è','é','ẹ','ẻ','ẽ','ê','ề','ế','ệ','ể','ễ',
		'ì','í','ị','ỉ','ĩ',
		'ò','ó','ọ','ỏ','õ','ô','ồ','ố','ộ','ổ','ỗ','ơ','ờ','ớ','ợ','ở','ỡ',
		'ù','ú','ụ','ủ','ũ','ư','ừ','ứ','ự','ử','ữ',
		'ỳ','ý','ỵ','ỷ','ỹ','đ',
	);
	$khong = array(
		'a','a','a','a','a','a','a','a','a','a','a','a','a','a','a','a','a',
		'e','e','e','e','e','e','e','e','e','e','e',
		'i','i','i','i','i',
		'o','o','o','o','o','o','o','o','o','o','o','o','o','o','o','o','o',
		'u','u','u','u','u','u','u','u','u','u','u',
		'y','y','y','y','y','d',
	);
	$s = str_replace( $co, $khong, (string) $s );
	$s = str_replace( array_map( 'smlife_qr_hoa', $co ), array_map( 'strtoupper', $khong ), $s );
	$s = preg_replace( '/[^A-Za-z0-9 ]/', '', $s );
	$s = preg_replace( '/\s+/', ' ', (string) $s );
	return trim( (string) $s );
}

function smlife_qr_hoa( $s ) {
	return function_exists( 'mb_strtoupper' ) ? mb_strtoupper( $s, 'UTF-8' ) : strtoupper( $s );
}

/* Chuoi VietQR hoan chinh. amount = 0 nghia la QR khong gan san so tien. */
function smlife_qr_payload( $bin, $acc, $amount = 0, $info = '' ) {
	$bin = preg_replace( '/[^0-9]/', '', (string) $bin );
	$acc = preg_replace( '/[^A-Za-z0-9]/', '', (string) $acc );
	if ( '' === $bin || '' === $acc ) {
		return '';
	}
	$amount = (int) round( (float) $amount );
	$info   = substr( smlife_qr_ascii( $info ), 0, 25 );

	$tk  = smlife_qr_tlv( '00', $bin ) . smlife_qr_tlv( '01', $acc );
	$nap = smlife_qr_tlv( '00', 'A000000727' ) . smlife_qr_tlv( '01', $tk ) . smlife_qr_tlv( '02', 'QRIBFTTA' );

	$p  = smlife_qr_tlv( '00', '01' );
	$p .= smlife_qr_tlv( '01', $amount > 0 ? '12' : '11' );
	$p .= smlife_qr_tlv( '38', $nap );
	$p .= smlife_qr_tlv( '53', '704' );
	if ( $amount > 0 ) {
		$p .= smlife_qr_tlv( '54', (string) $amount );
	}
	$p .= smlife_qr_tlv( '58', 'VN' );
	if ( '' !== $info ) {
		$p .= smlife_qr_tlv( '62', smlife_qr_tlv( '08', $info ) );
	}
	$p .= '6304';
	return $p . smlife_qr_crc16( $p );
}

/* ===== 3. Chong xung dot voi plugin cu ===== */

function smlife_qr_xung_dot() {
	$ds = (array) get_option( 'active_plugins', array() );
	if ( in_array( smlife_qr_isures(), $ds, true ) ) {
		return true;
	}
	if ( is_multisite() ) {
		$n = (array) get_site_option( 'active_sitewide_plugins', array() );
		if ( isset( $n[ smlife_qr_isures() ] ) ) {
			return true;
		}
	}
	return false;
}

add_action( 'admin_notices', function () {
	if ( ! current_user_can( 'manage_options' ) || ! smlife_qr_xung_dot() ) {
		return;
	}
	echo '<div class="notice notice-error"><p><b>SMLIFE Core - Thanh toán QR đang tạm dừng.</b> ';
	echo 'Plugin <i>iSures QrCode Payment for Vietnam</i> vẫn đang bật và dùng chung mã cổng thanh toán <code>' . esc_html( smlife_qr_id() ) . '</code>. ';
	echo 'Vào <a href="' . esc_url( admin_url( 'plugins.php' ) ) . '">Plugin</a> tắt nó đi thì module này mới chạy. ';
	echo 'Cấu hình tiêu đề, ghi chú, tiền tố và trạng thái đơn sẽ tự được giữ lại.</p></div>';
} );

/* ===== 4. Cong thanh toan ===== */

add_action( 'plugins_loaded', function () {
	if ( ! class_exists( 'WC_Payment_Gateway' ) || smlife_qr_xung_dot() ) {
		return;
	}

	class SMLIFE_QR_Gateway extends WC_Payment_Gateway {

		/* Khai bao truoc, PHP 8.2 tro len bao loi khi tao thuoc tinh dong */
		public $instructions = '';

		public function __construct() {
			$this->id                 = smlife_qr_id();
			$this->has_fields         = false;
			$this->method_title       = 'Chuyển khoản QR (SMLIFE Core)';
			$this->method_description = 'Hiện mã VietQR ở trang cảm ơn để khách quét bằng app ngân hàng. Mã được dựng ngay trên website, không gọi ra dịch vụ bên ngoài.';

			$this->init_form_fields();
			$this->init_settings();

			$this->title        = $this->get_option( 'title' );
			$this->description  = $this->get_option( 'description' );
			$this->instructions = $this->get_option( 'instructions' );

			add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );
			add_action( 'woocommerce_thankyou_' . $this->id, array( $this, 'trang_cam_on' ) );
			add_action( 'woocommerce_view_order', array( $this, 'trang_xem_don' ), 15 );
		}

		public function init_form_fields() {
			$tt = array();
			if ( function_exists( 'wc_get_order_statuses' ) ) {
				$tt = wc_get_order_statuses();
			}
			$this->form_fields = array(
				'enabled'      => array(
					'title'   => 'Bật/Tắt',
					'type'    => 'checkbox',
					'label'   => 'Bật phương thức chuyển khoản quét mã QR',
					'default' => 'no',
				),
				'title'        => array(
					'title'       => 'Tiêu đề',
					'type'        => 'text',
					'description' => 'Dòng chữ khách thấy ở bước chọn thanh toán.',
					'default'     => 'Quét QR Chuyển Khoản 24/7 (Hỗ trợ cả Momo/ZaloPay)',
					'desc_tip'    => true,
				),
				'description'  => array(
					'title'       => 'Mô tả',
					'type'        => 'textarea',
					'description' => 'Hiện ngay dưới tiêu đề khi khách chọn phương thức này.',
					'default'     => '',
					'desc_tip'    => true,
				),
				'bank'         => array(
					'title'       => 'Ngân hàng',
					'type'        => 'select',
					'options'     => smlife_qr_banks(),
					'default'     => '970423',
					'description' => 'Ngân hàng nhận tiền. Chọn xong nhớ quét thử mã ở trang SMLIFE Core > Thanh toán QR trước khi bật.',
					'desc_tip'    => true,
				),
				'bin_tay'      => array(
					'title'       => 'Mã BIN tự nhập',
					'type'        => 'text',
					'description' => 'Chỉ điền khi chọn "Ngân hàng khác" ở trên. Mã BIN gồm 6 chữ số do NAPAS cấp.',
					'default'     => '',
					'desc_tip'    => true,
				),
				'so_tk'        => array(
					'title'       => 'Số tài khoản',
					'type'        => 'text',
					'default'     => '',
				),
				'chu_tk'       => array(
					'title'       => 'Tên chủ tài khoản',
					'type'        => 'text',
					'default'     => '',
				),
				'prefix'       => array(
					'title'       => 'Tiền tố nội dung',
					'type'        => 'text',
					'description' => 'Nội dung chuyển khoản là tiền tố cộng số đơn, ví dụ SML12345. Để trống thì chỉ có số đơn.',
					'default'     => 'SML',
					'desc_tip'    => true,
				),
				'instructions' => array(
					'title'       => 'Ghi chú',
					'type'        => 'textarea',
					'description' => 'Hiện dưới mã QR ở trang cảm ơn. Gõ #noidungck ở chỗ muốn chèn nội dung chuyển khoản.',
					'default'     => 'Vui lòng chuyển khoản đúng nội dung #noidungck, để SMLIFE kích hoạt đơn hàng.',
					'desc_tip'    => true,
				),
				'order_status' => array(
					'title'       => 'Trạng thái đơn sau khi đặt',
					'type'        => 'select',
					'options'     => $tt,
					'default'     => 'wc-on-hold',
					'description' => 'Bản miễn phí không tự đối soát ngân hàng, nên đơn cần một trạng thái chờ để nhân viên xác nhận bằng tay.',
					'desc_tip'    => true,
				),
			);
		}

		/* Ma BIN dang dung, tinh ca truong hop tu nhap */
		public function lay_bin() {
			$b = (string) $this->get_option( 'bank' );
			if ( 'tay' === $b ) {
				$b = (string) $this->get_option( 'bin_tay' );
			}
			return preg_replace( '/[^0-9]/', '', $b );
		}

		public function ten_bank() {
			$ds = smlife_qr_banks();
			$b  = (string) $this->get_option( 'bank' );
			if ( 'tay' === $b ) {
				return 'Mã BIN ' . $this->lay_bin();
			}
			return isset( $ds[ $b ] ) ? $ds[ $b ] : $b;
		}

		/* Noi dung chuyen khoan cua mot don */
		public function noi_dung( $order ) {
			$p = smlife_qr_ascii( (string) $this->get_option( 'prefix' ) );
			$p = str_replace( ' ', '', $p );
			return $p . $order->get_order_number();
		}

		public function is_available() {
			if ( ! parent::is_available() ) {
				return false;
			}
			return ( '' !== $this->lay_bin() && '' !== trim( (string) $this->get_option( 'so_tk' ) ) );
		}

		public function process_payment( $order_id ) {
			$order = wc_get_order( $order_id );
			if ( ! $order ) {
				return array( 'result' => 'failure' );
			}
			$tt = (string) $this->get_option( 'order_status', 'wc-on-hold' );
			$tt = str_replace( 'wc-', '', $tt );
			if ( '' === $tt ) {
				$tt = 'on-hold';
			}
			$order->update_status( $tt, 'Chờ khách chuyển khoản QR. ' );

			if ( function_exists( 'wc_reduce_stock_levels' ) ) {
				wc_reduce_stock_levels( $order_id );
			}
			if ( WC()->cart ) {
				WC()->cart->empty_cart();
			}
			return array(
				'result'   => 'success',
				'redirect' => $this->get_return_url( $order ),
			);
		}

		public function trang_cam_on( $order_id ) {
			echo $this->khoi_qr( $order_id ); // phpcs:ignore
		}

		public function trang_xem_don( $order_id ) {
			$order = wc_get_order( $order_id );
			if ( ! $order || $this->id !== $order->get_payment_method() ) {
				return;
			}
			if ( ! $order->has_status( array( 'on-hold', 'pending' ) ) ) {
				return;
			}
			echo $this->khoi_qr( $order_id ); // phpcs:ignore
		}

		/* Khoi hien thi QR + thong tin chuyen khoan */
		public function khoi_qr( $order_id ) {
			$order = wc_get_order( $order_id );
			if ( ! $order || $this->id !== $order->get_payment_method() ) {
				return '';
			}
			$bin = $this->lay_bin();
			$acc = trim( (string) $this->get_option( 'so_tk' ) );
			if ( '' === $bin || '' === $acc ) {
				return '';
			}
			$tien = (float) $order->get_total();
			$nd   = $this->noi_dung( $order );
			$ma   = smlife_qr_payload( $bin, $acc, $tien, $nd );
			if ( '' === $ma ) {
				return '';
			}

			smlife_qr_nap_js();

			$dong = array(
				array( 'Ngân hàng', $this->ten_bank(), '' ),
				array( 'Số tài khoản', $acc, $acc ),
				array( 'Chủ tài khoản', (string) $this->get_option( 'chu_tk' ), '' ),
				array( 'Số tiền', html_entity_decode( wp_strip_all_tags( wc_price( $tien ) ), ENT_QUOTES, 'UTF-8' ), (string) (int) round( $tien ) ),
				array( 'Nội dung', $nd, $nd ),
			);

			$h  = '<div class="smlife-qr">';
			$h .= '<div class="smlife-qr-hd">Quét mã để chuyển khoản</div>';
			$h .= '<div class="smlife-qr-body">';
			$h .= '<div class="smlife-qr-anh"><div class="smlife-qr-ve" data-qr="' . esc_attr( $ma ) . '"></div></div>';
			$h .= '<div class="smlife-qr-tt"><table>';
			foreach ( $dong as $d ) {
				if ( '' === trim( (string) $d[1] ) ) {
					continue;
				}
				$h .= '<tr><th>' . esc_html( $d[0] ) . '</th><td><span>' . esc_html( $d[1] ) . '</span>';
				if ( '' !== $d[2] ) {
					$h .= '<button type="button" class="smlife-qr-cp" data-cp="' . esc_attr( $d[2] ) . '">Chép</button>';
				}
				$h .= '</td></tr>';
			}
			$h .= '</table></div></div>';

			$gc = trim( (string) $this->instructions );
			if ( '' !== $gc ) {
				$gc = str_replace( '#noidungck', $nd, $gc );
				$h .= '<p class="smlife-qr-gc">' . nl2br( esc_html( $gc ) ) . '</p>';
			}
			$h .= '</div>';
			return $h;
		}
	}
}, 11 );

add_filter( 'woocommerce_payment_gateways', function ( $ds ) {
	if ( class_exists( 'SMLIFE_QR_Gateway' ) ) {
		$ds[] = 'SMLIFE_QR_Gateway';
	}
	return $ds;
} );

/* ===== 5. CSS + JS ve ma QR ===== */

function smlife_qr_nap_js() {
	static $xong = false;
	if ( $xong ) {
		return;
	}
	$xong = true;
	wp_enqueue_script( 'smlife-qrcode', SMLIFE_CORE_URL . 'assets/qrcode.js', array(), '2.0.4', true );
	add_action( 'wp_footer', 'smlife_qr_js_ve', 99 );
	add_action( 'wp_footer', 'smlife_qr_css', 5 );
}

function smlife_qr_css() {
	$mau  = function_exists( 'smlife_mau' ) ? smlife_mau() : '#ee2468';
	$nhat = function_exists( 'smlife_mau_nhat' ) ? smlife_mau_nhat( 0.96 ) : '#fef2f6';
	$c  = '.smlife-qr{border:1px solid #ececec;border-radius:12px;background:#fff;margin:20px 0;overflow:hidden}';
	$c .= '.smlife-qr-hd{background:' . $nhat . ';color:' . $mau . ';font-size:15px;font-weight:700;padding:12px 16px;border-bottom:1px solid #f2f2f2}';
	$c .= '.smlife-qr-body{display:flex;gap:18px;align-items:flex-start;padding:16px;flex-wrap:wrap}';
	$c .= '.smlife-qr-anh{flex:0 0 196px}';
	$c .= '.smlife-qr-ve{width:196px;height:196px;border:1px solid #f0f0f0;border-radius:10px;padding:8px;background:#fff;display:flex;align-items:center;justify-content:center}';
	$c .= '.smlife-qr-ve svg{display:block;width:100%;height:auto}';
	$c .= '.smlife-qr-tt{flex:1 1 260px;min-width:0}';
	$c .= '.smlife-qr-tt table{width:100%;border-collapse:collapse;margin:0;border:0}';
	$c .= '.smlife-qr-tt tr{border-bottom:1px solid #f4f4f4}';
	$c .= '.smlife-qr-tt th{text-align:left;font-size:13px;font-weight:500;color:#8d8d8d;padding:8px 10px 8px 0;white-space:nowrap;width:1%;border:0}';
	$c .= '.smlife-qr-tt td{font-size:14px;font-weight:600;color:#3a3a3a;padding:8px 0;border:0;word-break:break-word}';
	$c .= '.smlife-qr-cp{background:' . $nhat . ';color:' . $mau . ';border:0;border-radius:6px;font-size:11.5px;font-weight:600;line-height:1;padding:5px 9px;margin:0 0 0 8px;cursor:pointer;min-height:0;vertical-align:middle}';
	$c .= '.smlife-qr-gc{font-size:13px;line-height:1.6;color:#8d8d8d;padding:0 16px 14px;margin:0}';
	$c .= '@media (max-width:549px){.smlife-qr-body{gap:14px;padding:13px}.smlife-qr-anh{flex:0 0 100%}.smlife-qr-ve{width:100%;max-width:240px;height:auto;aspect-ratio:1/1;margin:0 auto}}';
	echo '<style id="smlife-qr-css">' . $c . '</style>'; // phpcs:ignore
}

function smlife_qr_js_ve() {
	$j  = '(function(){if(typeof qrcode!=="function")return;';
	$j .= 'var l=document.querySelectorAll(".smlife-qr-ve[data-qr]");';
	$j .= 'for(var i=0;i<l.length;i++){var d=l[i].getAttribute("data-qr")||"";if(!d)continue;';
	$j .= 'try{var q=qrcode(0,"M");q.addData(d,"Byte");q.make();l[i].innerHTML=q.createSvgTag(4,0);}catch(e){}}';
	$j .= 'document.addEventListener("click",function(e){var b=e.target.closest?e.target.closest(".smlife-qr-cp"):null;if(!b)return;';
	$j .= 'var v=b.getAttribute("data-cp")||"";if(navigator.clipboard)navigator.clipboard.writeText(v);';
	$j .= 'var t=b.textContent;b.textContent="Đã chép";setTimeout(function(){b.textContent=t;},1300);});})();';
	echo '<script>' . $j . '</script>'; // phpcs:ignore
}

/* ===== 6. Trang trong SMLIFE Core ===== */

add_action( 'admin_menu', function () {
	add_submenu_page( 'smlife-core', 'Thanh toán QR', 'Thanh toán QR', 'manage_options', 'smlife-thanh-toan-qr', 'smlife_qr_page' );
}, 13 );

function smlife_qr_page() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}
	$s   = get_option( 'woocommerce_' . smlife_qr_id() . '_settings', array() );
	$s   = is_array( $s ) ? $s : array();
	$get = function ( $k, $d = '' ) use ( $s ) {
		return isset( $s[ $k ] ) ? $s[ $k ] : $d;
	};
	$bin = (string) $get( 'bank', '' );
	if ( 'tay' === $bin ) {
		$bin = (string) $get( 'bin_tay', '' );
	}
	$bin = preg_replace( '/[^0-9]/', '', $bin );
	$acc = trim( (string) $get( 'so_tk', '' ) );
	$ds  = smlife_qr_banks();
	$url = admin_url( 'admin.php?page=wc-settings&tab=checkout&section=' . smlife_qr_id() );

	echo '<div class="wrap smlife-wrap">';
	echo '<div class="smc-hd"><h1>Thanh toán QR chuyển khoản</h1>';
	echo '<p>Mã VietQR được dựng ngay trên website theo chuẩn EMVCo, không gọi ra dịch vụ bên ngoài. Mã hiện ở trang cảm ơn và trang xem đơn của khách.</p></div>';

	if ( smlife_qr_xung_dot() ) {
		echo '<div class="notice notice-error" style="margin:0 0 16px"><p><b>Đang tạm dừng.</b> Plugin iSures QrCode Payment for Vietnam vẫn bật và dùng chung mã cổng <code>' . esc_html( smlife_qr_id() ) . '</code>. Tắt plugin đó đi thì module này mới chạy.</p></div>';
	}

	echo '<div class="smlife-card"><h2>Cấu hình hiện tại</h2><div class="in">';
	echo '<table class="widefat striped" style="max-width:760px"><tbody>';
	echo '<tr><th style="width:190px">Trạng thái</th><td>' . ( 'yes' === $get( 'enabled' ) ? '<b style="color:#1a7f37">Đang bật</b>' : '<b style="color:#b32d2e">Đang tắt</b>' ) . '</td></tr>';
	echo '<tr><th>Tiêu đề</th><td>' . esc_html( (string) $get( 'title' ) ) . '</td></tr>';
	echo '<tr><th>Ngân hàng</th><td>' . esc_html( isset( $ds[ (string) $get( 'bank' ) ] ) ? $ds[ (string) $get( 'bank' ) ] : '(chưa chọn)' ) . ( $bin ? ' — BIN ' . esc_html( $bin ) : '' ) . '</td></tr>';
	echo '<tr><th>Số tài khoản</th><td>' . ( $acc ? esc_html( $acc ) : '<i style="color:#b32d2e">chưa điền</i>' ) . '</td></tr>';
	echo '<tr><th>Chủ tài khoản</th><td>' . esc_html( (string) $get( 'chu_tk' ) ) . '</td></tr>';
	echo '<tr><th>Nội dung chuyển khoản</th><td><code>' . esc_html( smlife_qr_ascii( (string) $get( 'prefix', 'SML' ) ) ) . '12345</code> — tiền tố cộng số đơn</td></tr>';
	echo '<tr><th>Trạng thái đơn</th><td>' . esc_html( (string) $get( 'order_status', 'wc-on-hold' ) ) . '</td></tr>';
	echo '</tbody></table>';
	echo '<p style="margin-bottom:0"><a class="button button-primary" href="' . esc_url( $url ) . '">Mở trang cài đặt</a></p>';
	echo '</div></div>';

	/* Quet thu */
	echo '<div class="smlife-card"><h2>Quét thử trước khi bật</h2><div class="in">';
	if ( '' === $bin || '' === $acc ) {
		echo '<p style="margin:0">Điền ngân hàng và số tài khoản trước đã, rồi quay lại đây để xem mã thử.</p>';
	} else {
		$ma = smlife_qr_payload( $bin, $acc, 10000, smlife_qr_ascii( (string) $get( 'prefix', 'SML' ) ) . 'TEST' );
		wp_enqueue_script( 'smlife-qrcode', SMLIFE_CORE_URL . 'assets/qrcode.js', array(), '2.0.4', true );
		echo '<p style="margin-top:0"><b style="color:#b32d2e">Bắt buộc làm một lần.</b> Mở app ngân hàng, quét mã dưới đây. App phải hiện đúng tên <b>' . esc_html( (string) $get( 'chu_tk' ) ) . '</b>, đúng số tài khoản, số tiền <b>10.000đ</b> và nội dung <b>' . esc_html( smlife_qr_ascii( (string) $get( 'prefix', 'SML' ) ) . 'TEST' ) . '</b>. Đúng hết thì mới bật phương thức này cho khách. <b>Đừng bấm chuyển tiền.</b></p>';
		echo '<div id="smlife-qr-thu" data-qr="' . esc_attr( $ma ) . '" style="width:220px;height:220px;border:1px solid #e5e5e5;border-radius:10px;padding:10px;background:#fff"></div>';
		echo '<p style="margin-bottom:0"><span class="description">Chuỗi VietQR: <code style="word-break:break-all">' . esc_html( $ma ) . '</code></span></p>';
		echo '<script>window.addEventListener("load",function(){var e=document.getElementById("smlife-qr-thu");if(!e||typeof qrcode!=="function")return;var q=qrcode(0,"M");q.addData(e.getAttribute("data-qr"),"Byte");q.make();e.innerHTML=q.createSvgTag(4,0);e.querySelector("svg").style.width="100%";e.querySelector("svg").style.height="auto";});</script>';
	}
	echo '</div></div>';

	echo '<div class="smlife-card"><h2>Vài điều cần biết</h2><div class="in"><ul style="margin:0">';
	echo '<li>Module dùng lại đúng mã cổng <code>' . esc_html( smlife_qr_id() ) . '</code> của plugin cũ, nên mọi ràng buộc Smart Coupons theo phương thức thanh toán và mọi đơn hàng cũ vẫn chạy nguyên.</li>';
	echo '<li>Bản này không đối soát tự động với ngân hàng. Đơn về trạng thái chờ, nhân viên kiểm tra sao kê rồi chuyển trạng thái bằng tay.</li>';
	echo '<li>Mã QR không gửi trong email vì email không chạy được JavaScript. Khách xem lại mã ở trang Đơn hàng trong tài khoản.</li>';
	echo '<li>Nội dung chuyển khoản tự bỏ dấu tiếng Việt, vì chuẩn VietQR chỉ nhận chữ không dấu.</li>';
	echo '</ul></div></div></div>';
}
