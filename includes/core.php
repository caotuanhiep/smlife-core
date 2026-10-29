<?php
/**
 * SMLIFE Core - Bo khung: danh muc module, bat tat, menu, bang dieu khien.
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

/* ===== Helper dung chung cho nhieu module ===== */
if ( ! function_exists( 'smlife_cfg' ) ) {
	function smlife_cfg( $key, $default = null ) {
		$o = get_option( 'smlife_core_settings', array() );
		if ( is_array( $o ) && array_key_exists( $key, $o ) ) {
			return $o[ $key ];
		}
		return $default;
	}
}

/* ===== Mau thuong hieu dung chung =====
 * Moi website dung plugin nay dat mot ma mau o Bang dieu khien, tat ca cac
 * khoi do plugin ve ra deu an theo: nut gui form, badge ma giam gia, bac gia
 * dang ap, gia san pham mua kem... Nho vay mang mot ban plugin sang website
 * ve tinh chi phai doi dung mot o, khong phai sua CSS tung module.
 */
function smlife_mau() {
	$m = smlife_cfg( 'mau', '#ee2468' );
	return sanitize_hex_color( $m ) ? $m : '#ee2468';
}

/* Tach ma mau ra 3 so R, G, B */
function smlife_mau_rgb( $hex = null ) {
	$hex = ltrim( (string) ( null === $hex ? smlife_mau() : $hex ), '#' );
	if ( 3 === strlen( $hex ) ) {
		$hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
	}
	if ( 6 !== strlen( $hex ) ) {
		return array( 238, 36, 104 );
	}
	return array( hexdec( substr( $hex, 0, 2 ) ), hexdec( substr( $hex, 2, 2 ) ), hexdec( substr( $hex, 4, 2 ) ) );
}

/* Mau thuong hieu dang rgba, dung cho vien sang khi bam vao o nhap */
function smlife_mau_rgba( $alpha = 0.1 ) {
	list( $r, $g, $b ) = smlife_mau_rgb();
	return 'rgba(' . (int) $r . ',' . (int) $g . ',' . (int) $b . ',' . (float) $alpha . ')';
}

/* Ban dam hon, dung cho trang thai di chuot len nut */
function smlife_mau_dam( $f = 0.84 ) {
	list( $r, $g, $b ) = smlife_mau_rgb();
	return sprintf( '#%02x%02x%02x', (int) round( $r * $f ), (int) round( $g * $f ), (int) round( $b * $f ) );
}

/* Ban rat nhat, dung lam nen cho o dang duoc chon */
function smlife_mau_nhat( $f = 0.94 ) {
	list( $r, $g, $b ) = smlife_mau_rgb();
	$m = function ( $c ) use ( $f ) {
		return (int) round( $c + ( 255 - $c ) * $f );
	};
	return sprintf( '#%02x%02x%02x', $m( $r ), $m( $g ), $m( $b ) );
}

/* ===== Danh muc module =====
 * group: ban-hang | lien-he | he-thong
 * file : ten file trong includes/modules/
 * page : slug trang cai dat (de trong neu module khong co trang rieng)
 * probe: ten mot ham dac trung, dung de phat hien snippet WPCode cu con bat
 */
function smlife_core_modules() {
	return array(
		'checkout' => array(
			'icon'  => 'dashicons-cart',
			'group' => 'ban-hang',
			'name'  => 'Gộp Cart + Checkout',
			'desc'  => 'Gộp giỏ hàng và thanh toán thành một trang, khách sửa số lượng ngay khi thanh toán.',
			'file'  => 'checkout.php',
			'page'  => 'smlife-checkout',
			'probe' => 'smlife_checkout_page',
		),
		'checkout-ui' => array(
			'icon'  => 'dashicons-admin-appearance',
			'group' => 'ban-hang',
			'name'  => 'Giao diện Checkout',
			'desc'  => 'Đổi chữ nút đặt hàng và tinh chỉnh trang Thanh toán, trang Hoàn tất.',
			'file'  => 'checkout-ui.php',
			'page'  => 'smlife-giao-dien',
			'probe' => 'smlife_ui_page',
		),
		/* 1.21.0: mac dinh TAT. Bat len van chua anh huong website cho toi khi
		   gan SMLIFE Ship vao vung giao hang / tat Smart Coupons. */
		'ship-rate' => array(
			'icon'  => 'dashicons-car',
			'group'    => 'ban-hang',
			'name'     => 'Phí giao hàng',
			'desc'     => 'Bảng phí giao hàng theo vùng, cân nặng và hạng vận chuyển, thay cho Table Rate Shipping. Có công cụ so sánh phí với Table Rate.',
			'file'     => 'shipping.php',
			'page'     => 'smlife-shipping',
			'probe'    => 'smlife_ship_page',
			'mac_dinh' => 0,
		),
		'coupon-plus' => array(
			'icon'  => 'dashicons-tickets-alt',
			'group'    => 'ban-hang',
			'name'     => 'Coupon nâng cao',
			'desc'     => 'Tự động áp mã, giới hạn theo phương thức thanh toán và thẻ sản phẩm, thay cho Smart Coupons. Tự chờ khi Smart Coupons còn bật.',
			'file'     => 'coupon.php',
			'page'     => 'smlife-coupon',
			'probe'    => 'smlife_cp_page',
			'mac_dinh' => 0,
		),
		'quick-buy' => array(
			'icon'  => 'dashicons-money-alt',
			'group' => 'ban-hang',
			'name'  => 'Nút Mua ngay',
			'desc'  => 'Thêm nút Mua ngay cạnh nút Thêm vào giỏ, bấm vào mở popup đặt hàng.',
			'file'  => 'quick-buy.php',
			'page'  => 'smlife-mua-ngay',
			'probe' => 'smlife_qb_page',
		),
		'quick-order' => array(
			'icon'  => 'dashicons-feedback',
			'group' => 'ban-hang',
			'name'  => 'Form Mua ngay',
			'desc'  => 'Form đặt hàng nhanh có honeypot và câu hỏi toán chống spam, không cần Contact Form 7.',
			'file'  => 'quick-order.php',
			'page'  => 'smlife-form-mua-ngay',
			'probe' => 'smlife_qo_page',
		),
		'cat-content' => array(
			'icon'  => 'dashicons-shortcode',
			'group' => 'ban-hang',
			'name'  => 'Dynamic Shortcode',
			'desc'  => 'Các shortcode động cho trang sản phẩm. Nội dung khác nhau giữa các danh mục soạn tập trung một chỗ.',
			'file'  => 'cat-content.php',
			'page'  => 'smlife-noi-dung-danh-muc',
			'probe' => 'smlife_catc_page',
		),
		'product-card' => array(
			'icon'  => 'dashicons-grid-view',
			'group' => 'ban-hang',
			'name'  => 'Thẻ sản phẩm',
			'desc'  => 'Trình bày lại thẻ sản phẩm ở trang danh mục: bo góc, giá, tag, số khách đã mua.',
			'file'  => 'product-card.php',
			'page'  => 'smlife-the-san-pham',
			'probe' => 'smlife_pc_page',
		),
		'product-page' => array(
			'icon'  => 'dashicons-products',
			'group' => 'ban-hang',
			'name'  => 'Trang sản phẩm',
			'desc'  => 'Hàng tag dưới khối mua hàng và cỡ chữ chuẩn cho trang sản phẩm.',
			'file'  => 'product-page.php',
			'page'  => 'smlife-trang-san-pham',
			'probe' => 'smlife_pp_page',
		),
		'lien-quan' => array(
			'icon'  => 'dashicons-randomize',
			'group' => 'ban-hang',
			'name'  => 'Sản phẩm liên quan',
			'desc'  => 'Thay khối Related mặc định: chọn được số lượng, bốc ngẫu nhiên theo danh mục hoặc tag, nới rộng khi danh mục ít hàng.',
			'file'  => 'related-random.php',
			'page'  => 'smlife-lien-quan',
			'probe' => 'smlife_lq_page',
		),
		'mua-kem' => array(
			'icon'  => 'dashicons-plus-alt',
			'group' => 'ban-hang',
			'name'  => 'Mua kèm sản phẩm',
			'desc'  => 'Khối "Sản phẩm thường mua cùng" trên trang sản phẩm, cấu hình theo nhóm nhiều danh mục, kèm mã giảm giá.',
			'file'  => 'mua-kem.php',
			'page'  => 'smlife-mua-kem',
			'probe' => 'smlife_mk_page',
		),
		'gia-so-luong' => array(
			'icon'  => 'dashicons-chart-bar',
			'group' => 'ban-hang',
			'name'  => 'Giá theo số lượng',
			'desc'  => 'Bậc giá theo số lượng dạng nút chọn, giá trên trang tự nhảy theo bậc và áp thật trong giỏ hàng.',
			'file'  => 'gia-so-luong.php',
			'page'  => 'smlife-gia-so-luong',
			'probe' => 'smlife_gsl_page',
		),
		'danh-gia' => array(
			'icon'  => 'dashicons-format-quote',
			'group' => 'ban-hang',
			'name'  => 'Đánh giá khách hàng',
			'desc'  => 'Khối đánh giá khách hàng đặt bằng shortcode: ảnh khách gửi, số sao, nội dung và tên khách.',
			'file'  => 'reviews.php',
			'page'  => 'smlife-danh-gia',
			'probe' => 'smlife_dg_page',
		),
		'price-layout' => array(
			'icon'  => 'dashicons-tag',
			'group' => 'ban-hang',
			'name'  => 'Cấu trúc giá',
			'desc'  => 'Trình bày lại khối giá trên trang sản phẩm cho dễ đọc.',
			'file'  => 'price-layout.php',
			'page'  => 'smlife-cau-truc-gia',
			'probe' => 'smlife_price_page',
		),
		'hotline-header' => array(
			'icon'  => 'dashicons-phone',
			'group' => 'lien-he',
			'name'  => 'Hotline Header',
			'desc'  => 'Khối số hotline trên đầu trang, quản lý tên, số, màu và đường kẻ.',
			'file'  => 'hotline-header.php',
			'page'  => 'smlife-hotline-header',
			'probe' => 'smlife_hh_page',
		),
		'mobile-menu' => array(
			'icon'  => 'dashicons-smartphone',
			'group' => 'lien-he',
			'name'  => 'Menu Mobile',
			'desc'  => 'Thanh menu cố định dưới màn hình điện thoại, có panel chat và gọi.',
			'file'  => 'mobile-menu.php',
			'page'  => 'smlife-menu-mobile',
			'probe' => 'smlife_mm_page',
		),
		'desktop-chat' => array(
			'icon'  => 'dashicons-format-chat',
			'group' => 'lien-he',
			'name'  => 'Desktop Chat Button',
			'desc'  => 'Cụm nút liên hệ dọc bám cạnh phải, chỉ hiện trên máy tính. Nạp nhanh dữ liệu từ Menu Mobile.',
			'file'  => 'desktop-chat.php',
			'page'  => 'smlife-desktop-chat',
			'probe' => 'smlife_dcb_page',
		),
		'duty-roster' => array(
			'icon'  => 'dashicons-groups',
			'group' => 'lien-he',
			'name'  => 'Lịch Trực Sales',
			'desc'  => 'Ai nghỉ, ai trực Chủ nhật. Tự mờ hotline và Zalo người ngoài ca, hotline tổng luôn sáng.',
			'file'  => 'duty-roster.php',
			'page'  => 'smlife-lich-truc',
			'probe' => 'smlife_duty_page',
		),
		'lien-he-form' => array(
			'icon'  => 'dashicons-email-alt',
			'group' => 'lien-he',
			'name'  => 'Form liên hệ',
			'desc'  => 'Form gửi yêu cầu tư vấn đặt bằng shortcode, chống spam, có mail báo và lưu lại trong admin.',
			'file'  => 'contact-form.php',
			'page'  => 'smlife-form-lien-he',
			'probe' => 'smlife_lh_page',
		),
		'chan-so-dt' => array(
			'icon'  => 'dashicons-shield',
			'group' => 'lien-he',
			'name'  => 'Chặn bấm số ĐT',
			'desc'  => 'Gỡ liên kết quay số khỏi mọi số điện thoại để khách lỡ tay hoặc bot không tạo chuyển đổi gọi điện ảo.',
			'file'  => 'phone-guard.php',
			'page'  => 'smlife-chan-so',
			'probe' => 'smlife_pg_page',
		),
		'promo-bar' => array(
			'icon'  => 'dashicons-megaphone',
			'group' => 'lien-he',
			'name'  => 'Promo Bar',
			'desc'  => 'Thanh chạy chữ cam kết bán hàng trên cùng website.',
			'file'  => 'promo-bar.php',
			'page'  => 'smlife-promo-bar',
			'probe' => 'smlife_pb_page',
		),
		'kieu-hien-thi' => array(
			'icon'  => 'dashicons-art',
			'group' => 'he-thong',
			'name'  => 'Kiểu hiển thị',
			'desc'  => 'Video YouTube đúng tỉ lệ, tab kiểu viên thuốc và khối bài viết kiểu tạp chí.',
			'file'  => 'theme-style.php',
			'page'  => 'smlife-kieu-hien-thi',
			'probe' => 'smlife_ts_page',
		),
		'email-center' => array(
			'icon'  => 'dashicons-email',
			'group' => 'he-thong',
			'name'  => 'Email Setting',
			'desc'  => 'Quản lý tập trung toàn bộ email của website, có xem trước và gửi thử.',
			'file'  => 'email-center.php',
			'page'  => 'smlife-email-center',
			'probe' => 'smlife_ec_admin_page',
		),
		'smtp' => array(
			'icon'  => 'dashicons-email-alt2',
			'group' => 'he-thong',
			'name'  => 'SMTP',
			'desc'  => 'Gửi email qua SMTP để thư không rơi vào hộp spam.',
			'file'  => 'smtp.php',
			'page'  => 'smlife-smtp',
			'probe' => 'smlife_smtp_page',
		),
		'dg-san-pham' => array(
			'icon'  => 'dashicons-star-filled',
			'group'    => 'ban-hang',
			'name'     => 'Đánh giá sản phẩm',
			'desc'     => 'Khối đánh giá kiểu sàn TMĐT, đọc thẳng từ đánh giá chuẩn của WooCommerce. Kèm công cụ di chuyển dữ liệu từ plugin cũ.',
			'file'     => 'product-reviews.php',
			'page'     => 'smlife-dg-san-pham',
			'probe'    => 'smlife_dgsp_page',
			'mac_dinh' => 0,
		),
		'loc-het-hang' => array(
			'icon'  => 'dashicons-hidden',
			'group' => 'ban-hang',
			'name'  => 'Ẩn hàng hết ở khối gợi ý',
			'desc'  => 'Bỏ sản phẩm hết hàng khỏi upsell, cross-sell và sản phẩm liên quan. Thay cho đoạn 6.3 của snippet WPCode, nhẹ hơn nhiều.',
			'file'  => 'loc-het-hang.php',
			'page'  => '',
			'probe' => 'smlife_lhh_loc',
		),
		'qr-payment' => array(
			'icon'  => 'dashicons-bank',
			'group'    => 'ban-hang',
			'name'     => 'Thanh toán QR',
			'desc'     => 'Cổng chuyển khoản quét mã VietQR, mã dựng ngay trên website, không gọi dịch vụ ngoài. Thay cho plugin iSures.',
			'file'     => 'qr-payment.php',
			'page'     => 'smlife-thanh-toan-qr',
			'probe'    => 'smlife_qr_page',
			'mac_dinh' => 0,
		),
		'schema-sp' => array(
			'icon'  => 'dashicons-google',
			'group'    => 'ban-hang',
			'name'     => 'Schema sản phẩm',
			'desc'     => 'Khai báo phí giao hàng, thời gian giao và chính sách đổi trả cho Google Merchant Listings. Dọn đánh giá rỗng và SKU sai định dạng.',
			'file'     => 'product-schema.php',
			'page'     => 'smlife-schema-san-pham',
			'probe'    => 'smlife_sch_page',
			'mac_dinh' => 0,
		),
		'danh-gia-google' => array(
			'icon'  => 'dashicons-star-half',
			'group'    => 'ban-hang',
			'name'     => 'Đánh giá cửa hàng Google',
			'desc'     => 'Hộp đồng ý khảo sát Google Customer Reviews trên trang Đặt hàng thành công. Tích điểm xếp hạng cửa hàng và ngôi sao trên Google Shopping.',
			'file'     => 'google-reviews.php',
			'page'     => 'smlife-danh-gia-google',
			'probe'    => 'smlife_gcr_page',
			'mac_dinh' => 0,
		),
		'tat-feed' => array(
			'icon'  => 'dashicons-rss',
			'group'    => 'he-thong',
			'name'     => 'Tắt feed sản phẩm',
			'desc'     => 'Chuyển hướng 301 feed RSS của sản phẩm và danh mục về trang chủ. Dọn nhóm lỗi 500 trong Search Console.',
			'file'     => 'seo-feed.php',
			'page'     => 'smlife-tat-feed',
			'probe'    => 'smlife_feed_page',
			'mac_dinh' => 0,
		),
		'tweaks' => array(
			'icon'  => 'dashicons-admin-tools',
			'group' => 'he-thong',
			'name'  => 'Tinh chỉnh hệ thống',
			'desc'  => 'Tắt emoji ảnh tải từ CDN ngoài, giúp icon hiển thị đúng và trang nhẹ hơn.',
			'file'  => 'tweaks.php',
			'page'  => '',
			'probe' => 'smlife_disable_emoji',
		),
		'lich-xoa-cache' => array(
			'icon'  => 'dashicons-clock',
			'group' => 'he-thong',
			'name'  => 'Lịch xoá cache',
			'desc'  => 'Xoá toàn bộ cache WP Rocket vào giờ cố định mỗi đêm (mặc định 00:05), có nhật ký từng lần chạy.',
			'file'  => 'cache-schedule.php',
			'page'  => 'smlife-lich-xoa-cache',
			'probe' => 'smlife_xc_page',
		),
	);
}

function smlife_core_groups() {
	return array(
		'ban-hang' => array( 'Bán hàng và Thanh toán', 'Mọi thứ khách chạm vào khi mua hàng.', 'dashicons-store' ),
		'lien-he'  => array( 'Liên hệ và Hỗ trợ', 'Cách khách tìm thấy và liên lạc với đội ngũ.', 'dashicons-phone' ),
		'he-thong' => array( 'Hệ thống', 'Email, cache và các tinh chỉnh nền.', 'dashicons-admin-generic' ),
	);
}

/* ===== Nhan dien website vua cai plugin lan dau =====
 * Mang plugin sang mot website ve tinh ma de mac dinh bat het thi vua kich
 * hoat xong la hang loat khoi nhay ra giua trang, con mang theo so dien thoai
 * va dia chi cua SMLIFE trong phan cai dat mau. Nen voi website chua he co
 * bat ky cai dat nao cua plugin, mac dinh la TAT HET, chu web tu bat cai can.
 *
 * Website dang chay san (da co it nhat mot cai dat cu) khong bi anh huong,
 * van giu nguyen cach cu la bat het khi chua tung bam Luu.
 */
function smlife_core_website_moi() {
	static $moi = null;
	if ( null !== $moi ) {
		return $moi;
	}
	$dau_vet = array(
		'smlife_core_settings', 'smlife_pp', 'smlife_mm', 'smlife_duty', 'smlife_ui',
		'smlife_price', 'smlife_catc', 'smlife_pc', 'smlife_email_center', 'smlife_hh',
		'smlife_pb', 'smlife_mk', 'smlife_gsl', 'smlife_qo', 'smlife_dc',
	);
	foreach ( $dau_vet as $k ) {
		if ( false !== get_option( $k, false ) ) {
			$moi = false;
			return $moi;
		}
	}
	$moi = true;
	return $moi;
}

/* ===== Trang thai bat tat ===== */
function smlife_core_state() {
	static $st = null;
	if ( null !== $st ) {
		return $st;
	}
	$saved = get_option( 'smlife_core_modules', null );
	$moi   = ( null === $saved || ! is_array( $saved ) ) && smlife_core_website_moi();
	$st    = array();
	foreach ( smlife_core_modules() as $k => $m ) {
		if ( is_array( $saved ) && isset( $saved[ $k ] ) ) {
			$st[ $k ] = (int) $saved[ $k ];
			continue;
		}
		/* Website moi cai lan dau: tat het cho an toan. Website cu dang chay
		   ma chua tung bam Luu thi giu nguyen nhu truoc, bat het. */
		$st[ $k ] = $moi ? 0 : ( isset( $m['mac_dinh'] ) ? (int) $m['mac_dinh'] : 1 );
	}
	return $st;
}

function smlife_core_is_on( $key ) {
	$st = smlife_core_state();
	return ! empty( $st[ $key ] );
}

/* ===== Nap module =====
 * Neu snippet WPCode cu van con bat, ham dac trung da ton tai. Khi do bo qua module
 * do de khong bi loi trung ten ham, va bao cho nguoi dung biet o Bang dieu khien.
 */
function smlife_core_conflicts() {
	static $c = null;
	if ( null !== $c ) {
		return $c;
	}
	$c = array();
	foreach ( smlife_core_modules() as $k => $m ) {
		if ( ! empty( $m['probe'] ) && function_exists( $m['probe'] ) ) {
			$c[] = $k;
		}
	}
	return $c;
}

/* ===== Cong cu dung chung: IP that cua khach =====
 *
 * Website chay sau Cloudflare thi REMOTE_ADDR la IP cua may chu Cloudflare,
 * tat ca khach dung chung vai IP. Gioi han "5 lan moi gio moi IP" khi do se
 * chan nham ca khach that. Uu tien IP Cloudflare chuyen kem, kiem tra dung
 * dinh dang roi moi dung.
 */
/* 1.24.0: chi tin header CF-Connecting-IP khi request that su di qua Cloudflare
 * (REMOTE_ADDR thuoc dai IP Cloudflare) hoac qua proxy noi bo cua hosting (IP
 * rieng). Truoc day tin moi request: bot goi thang may chu, bo qua Cloudflare,
 * tu dat header IP gia la qua mat gioi han so lan gui. */
function smlife_ip() {
	$ra = isset( $_SERVER['REMOTE_ADDR'] ) ? trim( (string) wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : ''; // phpcs:ignore
	$cf = isset( $_SERVER['HTTP_CF_CONNECTING_IP'] ) ? trim( (string) wp_unslash( $_SERVER['HTTP_CF_CONNECTING_IP'] ) ) : ''; // phpcs:ignore
	if ( filter_var( $cf, FILTER_VALIDATE_IP ) && ( ! filter_var( $ra, FILTER_VALIDATE_IP ) || smlife_ip_tin_cay( $ra ) ) ) {
		return $cf;
	}
	return filter_var( $ra, FILTER_VALIDATE_IP ) ? $ra : '0.0.0.0';
}

/* REMOTE_ADDR la Cloudflare hoac IP noi bo (proxy cua hosting) */
function smlife_ip_tin_cay( $ip ) {
	if ( ! filter_var( $ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE ) ) {
		return true; /* IP rieng / dac biet: proxy noi bo */
	}
	$ds = array(
		'173.245.48.0/20', '103.21.244.0/22', '103.22.200.0/22', '103.31.4.0/22', '141.101.64.0/18',
		'108.162.192.0/18', '190.93.240.0/20', '188.114.96.0/20', '197.234.240.0/22', '198.41.128.0/17',
		'162.158.0.0/15', '104.16.0.0/13', '104.24.0.0/14', '172.64.0.0/13', '131.0.72.0/22',
		'2400:cb00::/32', '2606:4700::/32', '2803:f800::/32', '2405:b500::/32', '2405:8100::/32',
		'2a06:98c0::/29', '2c0f:f248::/32',
	);
	foreach ( (array) apply_filters( 'smlife_ip_proxy', $ds ) as $dai ) {
		if ( smlife_ip_trong( $ip, $dai ) ) {
			return true;
		}
	}
	return false;
}

function smlife_ip_trong( $ip, $dai ) {
	$p = explode( '/', (string) $dai );
	$a = @inet_pton( $ip ); // phpcs:ignore
	$b = @inet_pton( $p[0] ); // phpcs:ignore
	if ( false === $a || false === $b || strlen( $a ) !== strlen( $b ) ) {
		return false;
	}
	$bit = isset( $p[1] ) ? (int) $p[1] : strlen( $a ) * 8;
	$day = intdiv( $bit, 8 );
	$le  = $bit % 8;
	if ( substr( $a, 0, $day ) !== substr( $b, 0, $day ) ) {
		return false;
	}
	if ( 0 === $le ) {
		return true;
	}
	$m = ( 0xFF << ( 8 - $le ) ) & 0xFF;
	return ( ord( $a[ $day ] ) & $m ) === ( ord( $b[ $day ] ) & $m );
}

/* ===== 1.24.0: KHOA VA CO "DA LAM" LUU THANG DB =====
 * Transient/option di qua Redis. Khi Redis day (noeviction + graceful) ghi bi
 * nuot mat, doc ra gia tri cu: khoa chong chay trung khong bao gio "co", co
 * "da lam" khong bao gio duoc ghi -> cung mot viec lap lai o moi request
 * (vd xoa cache lien tuc). Ham duoi day ghi/doc thang bang wp_options bang
 * cau lenh nguyen tu, khong phu thuoc Redis.
 * Tra ve true neu lay duoc khoa (chua ai giu hoac khoa cu da het han). */
function smlife_khoa_db( $ten, $giay ) {
	global $wpdb;
	$k   = 'smlife_khoa_' . preg_replace( '/[^a-z0-9_]/', '', strtolower( (string) $ten ) );
	$now = time();
	$het = (string) ( $now + max( 1, (int) $giay ) );
	$n   = $wpdb->query( $wpdb->prepare( "INSERT IGNORE INTO {$wpdb->options} (option_name, option_value, autoload) VALUES (%s, %s, 'off')", $k, $het ) ); // phpcs:ignore
	if ( 1 === (int) $n ) {
		return true;
	}
	$cu = $wpdb->get_var( $wpdb->prepare( "SELECT option_value FROM {$wpdb->options} WHERE option_name = %s", $k ) ); // phpcs:ignore
	if ( null !== $cu && (int) $cu < $now ) {
		/* Khoa het han: doi gia tri co dieu kien, hai tien trinh cung luc chi mot cai thang */
		$n = $wpdb->query( $wpdb->prepare( "UPDATE {$wpdb->options} SET option_value = %s WHERE option_name = %s AND option_value = %s", $het, $k, $cu ) ); // phpcs:ignore
		return 1 === (int) $n;
	}
	return false;
}

function smlife_mo_khoa_db( $ten ) {
	global $wpdb;
	$k = 'smlife_khoa_' . preg_replace( '/[^a-z0-9_]/', '', strtolower( (string) $ten ) );
	$wpdb->delete( $wpdb->options, array( 'option_name' => $k ) ); // phpcs:ignore
}

/* Doc option thang tu DB, bo qua object cache (dung khi nghi cache dang cu) */
function smlife_opt_db( $ten, $mac_dinh = false ) {
	global $wpdb;
	$v = $wpdb->get_var( $wpdb->prepare( "SELECT option_value FROM {$wpdb->options} WHERE option_name = %s LIMIT 1", $ten ) ); // phpcs:ignore
	return null === $v ? $mac_dinh : maybe_unserialize( $v );
}

/* Gioi han so lan mot hanh dong trong mot khoang thoi gian.
 * Tra ve true neu VAN CON duoc phep, va tu dem them mot lan. */
function smlife_gioi_han( $nhom, $khoa, $toi_da, $giay ) {
	$k = 'smlife_gh_' . $nhom . '_' . md5( (string) $khoa );
	$n = (int) get_transient( $k );
	if ( $n >= (int) $toi_da ) {
		return false;
	}
	set_transient( $k, $n + 1, (int) $giay );
	return true;
}

/* ===== Cong cu dung chung: chi xoa cache khi THAT SU co thay doi =====
 *
 * Truoc day bam Luu o bat ky trang cai dat nao cung xoa sach cache WP Rocket,
 * ke ca khi khong doi gi. Website hon 5.000 san pham thi moi lan nhu vay la
 * hang nghin trang phai dung lai tu dau.
 *
 * WordPress chi phat tin hieu updated_option / updated_*_meta khi gia tri moi
 * khac gia tri cu. Ta lang nghe cac tin hieu do trong luot luu; khong co tin
 * hieu nao nghia la khong co gi thay doi, giu nguyen cache.
 */
$GLOBALS['smlife_co_doi'] = false;
function smlife_danh_dau_opt( $ten ) {
	if ( 0 === strpos( (string) $ten, 'smlife' ) ) {
		$GLOBALS['smlife_co_doi'] = true;
	}
}
function smlife_danh_dau_meta() {
	$GLOBALS['smlife_co_doi'] = true;
}
add_action( 'added_option', 'smlife_danh_dau_opt' );
add_action( 'updated_option', 'smlife_danh_dau_opt' );
add_action( 'deleted_option', 'smlife_danh_dau_opt' );
if ( is_admin() ) {
	foreach ( array( 'post', 'term' ) as $smlife_loai ) {
		add_action( 'added_' . $smlife_loai . '_meta', 'smlife_danh_dau_meta' );
		add_action( 'updated_' . $smlife_loai . '_meta', 'smlife_danh_dau_meta' );
		add_action( 'deleted_' . $smlife_loai . '_meta', 'smlife_danh_dau_meta' );
	}
	unset( $smlife_loai );
}

/* Goi o cuoi moi ham luu cai dat. Tra ve true neu da xoa cache. */
/* 1.24.0: mac dinh KHONG xoa cache ngay. Ghi lai "thay doi cho len web", job dem
 * (Lich xoa cache, 00:15) xoa mot lan cho tat ca. Ly do: moi lan bam Luu xoa
 * sach 5.000+ trang giua ngay lam web cham va Redis/preload chay lien tuc.
 * Can gap thi bam "Đưa lên web ngay" o thanh thong bao.
 * $ngay = true: xoa ngay (bat/tat module, khoi phuc layout - can thay lien).
 * Khong co job dem (tat module Lich xoa cache hoac tat lich) thi xoa ngay nhu cu.
 * Tra ve true neu co thay doi (da xoa hoac da xep lich). */
function smlife_xoa_cache( $nguon = '', $ngay = false ) {
	if ( empty( $GLOBALS['smlife_co_doi'] ) ) {
		return false;
	}
	if ( ! $ngay && function_exists( 'smlife_xc_dem_bat' ) && smlife_xc_dem_bat() ) {
		$c = get_option( 'smlife_cho_xoa', array() );
		$c = is_array( $c ) ? $c : array();
		$n = '' !== (string) $nguon ? (string) $nguon : smlife_core_trang_ten();
		if ( empty( $c['tu'] ) ) {
			$c['tu'] = time();
		}
		$c['ds'][ $n ] = time();
		update_option( 'smlife_cho_xoa', $c, false );
		return true;
	}
	if ( function_exists( 'rocket_clean_domain' ) ) {
		rocket_clean_domain();
	}
	do_action( 'litespeed_purge_all' );
	delete_option( 'smlife_cho_xoa' );
	return true;
}

/* 1.25.0: ten thuong hieu ngan cua website dang chay (dung cho mac dinh email,
   menu...). Lay phan truoc dau " - " cua Ten website: "SMLIFE - Noi that..." -> "SMLIFE".
   Doi bang define( 'SMLIFE_CORE_BRAND', '...' ) trong wp-config neu can. */
function smlife_ten_thuong_hieu() {
	if ( defined( 'SMLIFE_CORE_BRAND' ) && '' !== trim( (string) SMLIFE_CORE_BRAND ) ) {
		return trim( (string) SMLIFE_CORE_BRAND );
	}
	$n = wp_specialchars_decode( (string) get_bloginfo( 'name' ), ENT_QUOTES );
	$n = trim( preg_split( '/\s+[-–|]\s+/u', $n )[0] );
	return '' !== $n ? $n : (string) wp_parse_url( home_url(), PHP_URL_HOST );
}

/* Ten trang cai dat dang luu, de ghi vao danh sach cho */
function smlife_core_trang_ten() {
	$ref = isset( $_POST['_wp_http_referer'] ) ? (string) wp_unslash( $_POST['_wp_http_referer'] ) : ( isset( $_SERVER['HTTP_REFERER'] ) ? (string) wp_unslash( $_SERVER['HTTP_REFERER'] ) : '' ); // phpcs:ignore
	if ( preg_match( '/[?&]page=([a-z0-9_-]+)/', $ref, $m ) ) {
		foreach ( smlife_core_modules() as $md ) {
			if ( ! empty( $md['page'] ) && $md['page'] === $m[1] ) {
				return (string) $md['name'];
			}
		}
		return $m[1];
	}
	return 'Cài đặt SMLIFE';
}

/* Thanh thong bao "thay doi cho len web" tren moi trang wp-admin cho admin */
add_action( 'admin_notices', function () {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}
	$c = get_option( 'smlife_cho_xoa', array() );
	if ( empty( $c['ds'] ) || ! is_array( $c['ds'] ) ) {
		return;
	}
	$ten = array();
	foreach ( $c['ds'] as $n => $t ) {
		$ten[] = '<b>' . esc_html( $n ) . '</b> (' . esc_html( wp_date( 'H:i d/m', (int) $t ) ) . ')';
	}
	$gio = function_exists( 'smlife_xc_gio_chay' ) ? smlife_xc_gio_chay() : '00:15';
	$url = wp_nonce_url( admin_url( 'admin-post.php?action=smlife_dua_len' ), 'smlife_dua_len' );
	echo '<div class="notice notice-info smlife-cho-xoa"><p>⏳ Thay đổi chờ lên website lúc <b>' . esc_html( $gio ) . '</b>: ' . implode( ', ', $ten ) . '. Khách vẫn đang thấy bản cũ. <a class="button button-small" href="' . esc_url( $url ) . '" onclick="return confirm(\'Xoá cache toàn website ngay bây giờ? Web sẽ chậm vài phút trong lúc tải lại.\')">Đưa lên web ngay</a></p></div>';
} );

add_action( 'admin_post_smlife_dua_len', function () {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( 'Không đủ quyền.' );
	}
	check_admin_referer( 'smlife_dua_len' );
	$GLOBALS['smlife_co_doi'] = true;
	smlife_xoa_cache( '', true );
	if ( function_exists( 'smlife_xc_ghi' ) ) {
		smlife_xc_ghi( 'bam-tay', true, 0, 'Bấm "Đưa lên web ngay": đã xoá cache toàn website' );
	}
	$ve = wp_get_referer();
	wp_safe_redirect( $ve ? $ve : admin_url( 'admin.php?page=smlife-core' ) );
	exit;
} );

/* ===== Nang cap phien ban =====
 *
 * Moi ban cap nhat deu doi CSS, JS hoac cau truc HTML. Trang cu con nam trong
 * cache WP Rocket se chay JS cu voi may chu moi, de sinh loi kho hieu (form
 * bao het phien, nut bam khong an). Phat hien doi phien ban la tu xoa cache
 * mot lan, khong phai nho bam tay.
 */
add_action( 'admin_init', function () {
	$cu = (string) get_option( 'smlife_core_ver', '' );
	if ( SMLIFE_CORE_VER === $cu ) {
		return;
	}
	update_option( 'smlife_core_ver', SMLIFE_CORE_VER, true );

	/* 1.20.0: Lich Truc bo dau van tay, dung ban chot luc 00:05 */
	if ( '' !== $cu && version_compare( $cu, '1.20.0', '<' ) ) {
		delete_option( 'smlife_duty_last_sig' );
	}

	/* 1.18.0: ha so san pham lien quan xuong 12 theo yeu cau, chi mot lan */
	/* Ban truoc 1.18 chua ghi so phien ban, nen rong cung tinh la ban cu */
	if ( '' === $cu || version_compare( $cu, '1.18.0', '<' ) ) {
		$lq = get_option( 'smlife_lq', array() );
		if ( is_array( $lq ) && isset( $lq['so_sp'] ) && (int) $lq['so_sp'] > 12 ) {
			$lq['so_sp'] = 12;
			update_option( 'smlife_lq', $lq, false );
		}
	}

	/* Tuy chon duoc doc o MOI trang thi phai nap san cung luot dau, neu khong
	   moi trang ton them mot cau truy van rieng cho no. */
	if ( function_exists( 'wp_set_option_autoload_values' ) ) {
		wp_set_option_autoload_values( array( 'smlife_ts' => true, 'smlife_pc' => true, 'smlife_pg' => true, 'smlife_dgsp' => true, 'smlife_smtp' => false ) );
	}

	if ( function_exists( 'rocket_clean_domain' ) ) {
		rocket_clean_domain();
	}
} );

/* Chup danh sach xung dot TRUOC khi nap module.
 * Neu chup sau, plugin se gap chinh cac ham minh vua nap va tuong la snippet cu. */
smlife_core_conflicts();

function smlife_core_load() {
	$dir = SMLIFE_CORE_DIR . 'includes/modules/';
	foreach ( smlife_core_modules() as $k => $m ) {
		if ( ! smlife_core_is_on( $k ) ) {
			continue;
		}
		if ( ! empty( $m['probe'] ) && function_exists( $m['probe'] ) ) {
			continue;
		}
		$f = $dir . $m['file'];
		if ( file_exists( $f ) ) {
			require_once $f;
		}
	}
}
smlife_core_load();

/* Module Lich xoa cache bi tat thi go lich da hen, khong de lai su kien mo coi */
add_action( 'init', function () {
	if ( ! is_admin() && ! wp_doing_cron() ) {
		return;
	}
	if ( ! smlife_core_is_on( 'lich-xoa-cache' ) && wp_next_scheduled( 'smlife_xc_chay' ) ) {
		wp_clear_scheduled_hook( 'smlife_xc_chay' );
	}
}, 30 );

/* ===== Shortcode du phong =====
 * Layout san pham co dat san 3 shortcode cua module Noi dung Danh muc.
 * Neu module bi tat hoac chua cai, WordPress se in nguyen chuoi
 * [smlife_tinh_trang] ra giua trang san pham. Dang ky ban rong o day de
 * truong hop do chi don gian la khong hien gi. Module bat thi no da dang ky
 * truoc roi, add_shortcode cua module thang. */
add_action( 'init', function () {
	foreach ( array( 'smlife_dg_sp', 'smlife_dg_meta', 'smlife_dg_daban', 'smlife_tinh_trang', 'smlife_banner_sp', 'smlife_chi_co_tai', 'smlife_vat_lieu', 'smlife_noidung_them', 'smlife_tag_sp', 'smlife_quick_order', 'smlife_mua_kem', 'smlife_gia_sl', 'smlife_lien_he', 'smlife_danh_gia', 'smlife_sp_lien_quan' ) as $sc ) {
		if ( ! shortcode_exists( $sc ) ) {
			add_shortcode( $sc, '__return_empty_string' );
		}
	}
}, 99 );

/* ===== Menu ===== */
add_action( 'admin_menu', function () {
	add_menu_page(
		'SMLIFE Core',
		'SMLIFE Core',
		'manage_options',
		'smlife-core',
		'smlife_core_dashboard',
		'dashicons-store',
		'1.07'
	);
	add_submenu_page(
		'smlife-core',
		'Bảng điều khiển',
		'Bảng điều khiển',
		'manage_options',
		'smlife-core',
		'smlife_core_dashboard'
	);
}, 1 );

/* Dua menu SMLIFE Core xuong ngay sau Flatsome neu co */
add_filter( 'custom_menu_order', '__return_true' );
add_filter( 'menu_order', function ( $order ) {
	if ( ! is_array( $order ) ) {
		return $order;
	}
	$me    = 'smlife-core';
	$after = 'flatsome-panel';
	$order = array_values( array_diff( $order, array( $me ) ) );
	$out   = array();
	$done  = false;
	foreach ( $order as $slug ) {
		$out[] = $slug;
		if ( $slug === $after ) {
			$out[] = $me;
			$done  = true;
		}
	}
	if ( ! $done ) {
		$out[] = $me;
	}
	return $out;
} );

/* ===== CSS dung chung cho moi trang cua plugin ===== */
add_action( 'admin_enqueue_scripts', function ( $hook ) {
	$on = ( false !== strpos( (string) $hook, 'smlife-' ) );
	if ( ! $on && isset( $_GET['page'] ) ) {
		$on = ( 0 === strpos( sanitize_text_field( wp_unslash( $_GET['page'] ) ), 'smlife-' ) );
	}
	if ( ! $on ) {
		return;
	}
	wp_enqueue_style( 'smlife-core-admin', SMLIFE_CORE_URL . 'assets/admin.css', array(), SMLIFE_CORE_VER );
	/* Mau nhan cua giao dien quan tri an theo mau thuong hieu */
	wp_add_inline_style( 'smlife-core-admin', ':root{--smc-ac:' . smlife_mau() . ';--smc-ac-d:' . smlife_mau_dam( 0.82 ) . ';--smc-ac-l:' . smlife_mau_nhat( 0.93 ) . ';--smc-ac-r:' . smlife_mau_rgba( 0.16 ) . ';}' );
}, 5 );

/* ===== 1.22.0: Khung chung cho moi trang cua plugin =====
 * - Lop body "smlife-admin" de admin.css chi ap tren trang cua plugin.
 * - Thanh dieu huong tren dau: icon module, nhom, link ve Bang dieu khien.
 * - Icon module truoc tieu de h1.
 */
function smlife_core_trang_hien_tai() {
	static $kq = false;
	if ( false !== $kq ) {
		return $kq;
	}
	$kq   = null;
	$page = isset( $_GET['page'] ) ? sanitize_key( wp_unslash( $_GET['page'] ) ) : ''; // phpcs:ignore
	if ( '' === $page || 0 !== strpos( $page, 'smlife-' ) ) {
		return $kq;
	}
	if ( 'smlife-core' === $page ) {
		$kq = array( 'key' => '', 'dash' => true );
		return $kq;
	}
	foreach ( smlife_core_modules() as $k => $m ) {
		if ( ! empty( $m['page'] ) && $m['page'] === $page ) {
			$kq = array( 'key' => $k, 'dash' => false, 'm' => $m );
			return $kq;
		}
	}
	$kq = array( 'key' => '', 'dash' => false, 'm' => array( 'name' => '', 'group' => '', 'icon' => 'dashicons-admin-generic' ) );
	return $kq;
}

add_filter( 'admin_body_class', function ( $c ) {
	return smlife_core_trang_hien_tai() ? $c . ' smlife-admin' : $c;
} );

add_action( 'all_admin_notices', function () {
	$t = smlife_core_trang_hien_tai();
	if ( ! $t || ! empty( $t['dash'] ) ) {
		return;
	}
	$m  = $t['m'];
	$gs = smlife_core_groups();
	$g  = isset( $gs[ $m['group'] ] ) ? $gs[ $m['group'] ][0] : '';
	echo '<div class="smc-bar">';
	echo '<a class="smc-bar-home" href="' . esc_url( admin_url( 'admin.php?page=smlife-core' ) ) . '"><span class="dashicons dashicons-screenoptions"></span>SMLIFE Core</a>';
	if ( $g ) {
		echo '<span class="smc-bar-sep">/</span><span class="smc-bar-grp">' . esc_html( $g ) . '</span>';
	}
	if ( ! empty( $m['name'] ) ) {
		echo '<span class="smc-bar-sep">/</span><span class="smc-bar-cur">' . esc_html( $m['name'] ) . '</span>';
	}
	echo '<span class="smc-bar-ver">v' . esc_html( SMLIFE_CORE_VER ) . '</span>';
	echo '</div>';
}, 1 );

add_action( 'admin_footer', function () {
	$t = smlife_core_trang_hien_tai();
	if ( ! $t || ! empty( $t['dash'] ) || empty( $t['m']['icon'] ) ) {
		return;
	}
	echo '<script>(function(){var h=document.querySelector("#wpbody-content .wrap h1");if(!h||h.querySelector(".smc-h1-ic"))return;';
	echo 'var s=document.createElement("span");s.className="smc-h1-ic dashicons ' . esc_js( $t['m']['icon'] ) . '";h.insertBefore(s,h.firstChild);})();</script>';
} );

/* ===== Bang dieu khien ===== */
function smlife_core_dashboard() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}
	$mods   = smlife_core_modules();
	$groups = smlife_core_groups();
	$st     = smlife_core_state();
	$confl  = smlife_core_conflicts();
	$on     = count( array_filter( $st ) );

	echo '<div class="wrap smc smc-dash">';

	echo '<div class="smc-hero">';
	echo '<div class="smc-hero-tx"><h1>SMLIFE Core</h1>';
	echo '<p>Bộ công cụ vận hành website. Bật tắt từng tính năng ngay tại đây, mỗi tính năng có trang cài đặt riêng trong menu bên trái.</p></div>';
	echo '<div class="smc-hero-stat"><b>' . (int) $on . '<small>/' . count( $mods ) . '</small></b><span>tính năng đang bật</span></div>';
	echo '<div class="smc-hero-stat"><b>' . esc_html( SMLIFE_CORE_VER ) . '</b><span>phiên bản</span></div>';
	echo '</div>';
	if ( function_exists( 'smlife_gh_trang_thai' ) ) {
		echo smlife_gh_trang_thai(); // phpcs:ignore
	}

	if ( isset( $_GET['saved'] ) ) {
		echo '<div class="notice notice-success is-dismissible"><p>Đã lưu.</p></div>';
	}

	if ( ! empty( $confl ) ) {
		echo '<div class="smc-warn"><b>Phát hiện snippet cũ còn bật</b><p>Các tính năng sau đang chạy từ snippet WPCode nên plugin tạm không nạp bản của mình, tránh lỗi trùng tên hàm. Hãy tắt snippet tương ứng trong Code Snippets rồi tải lại trang này.</p><ul>';
		foreach ( $confl as $k ) {
			echo '<li>' . esc_html( $mods[ $k ]['name'] ) . '</li>';
		}
		echo '</ul></div>';
	}

	echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '">';
	echo '<input type="hidden" name="action" value="smlife_core_save_modules" />';
	wp_nonce_field( 'smlife_core_save_modules', 'smlife_core_nonce' );

	foreach ( $groups as $gkey => $g ) {
		$trong = array();
		foreach ( $mods as $k => $m ) {
			if ( $m['group'] === $gkey ) {
				$trong[ $k ] = $m;
			}
		}
		$bat = 0;
		foreach ( $trong as $k => $m ) {
			$bat += empty( $st[ $k ] ) ? 0 : 1;
		}
		echo '<section class="smc-group">';
		echo '<div class="smc-group-hd">';
		echo '<span class="smc-group-ic dashicons ' . esc_attr( isset( $g[2] ) ? $g[2] : 'dashicons-admin-generic' ) . '"></span>';
		echo '<div class="smc-group-tx"><h2>' . esc_html( $g[0] ) . '</h2><p>' . esc_html( $g[1] ) . '</p></div>';
		echo '<span class="smc-group-count">' . (int) $bat . ' / ' . count( $trong ) . ' đang bật</span>';
		echo '</div>';
		echo '<div class="smc-grid">';
		foreach ( $trong as $k => $m ) {
			$active = ! empty( $st[ $k ] );
			$bad    = in_array( $k, $confl, true );
			$ic     = ! empty( $m['icon'] ) ? $m['icon'] : 'dashicons-admin-plugins';
			echo '<div class="smc-card' . ( $active ? ' is-on' : '' ) . ( $bad ? ' is-bad' : '' ) . '">';
			echo '<div class="smc-card-top">';
			echo '<span class="smc-card-ic dashicons ' . esc_attr( $ic ) . '"></span>';
			echo '<span class="smc-card-name">' . esc_html( $m['name'] ) . '</span>';
			echo '<label class="smc-sw" title="Bật / tắt"><input type="checkbox" name="mods[' . esc_attr( $k ) . ']" value="1" ' . checked( true, $active, false ) . ' /><i></i></label>';
			echo '</div>';
			echo '<p class="smc-card-desc">' . esc_html( $m['desc'] ) . '</p>';
			echo '<div class="smc-card-foot">';
			if ( $bad ) {
				echo '<span class="smc-tag warn">Đang chạy từ snippet</span>';
			} elseif ( ! $active ) {
				echo '<span class="smc-tag off">Đang tắt</span>';
			} else {
				echo '<span class="smc-tag on">Đang chạy</span>';
			}
			if ( $active && ! empty( $m['page'] ) && ! $bad ) {
				echo '<a class="smc-link" href="' . esc_url( admin_url( 'admin.php?page=' . $m['page'] ) ) . '">Cài đặt <span aria-hidden="true">&rarr;</span></a>';
			}
			echo '</div></div>';
		}
		echo '</div></section>';
	}

	/* Canh bao: module moi them vao plugin nhung chua tung duoc luu tren site nay.
	 * Khi nang cap plugin tren mot website dang chay, nhung module moi se tu bat
	 * theo mac dinh. Liet ke ro ra de chu web biet ma kiem lai, tranh truong hop
	 * vua cap nhat xong la giao dien doi ma khong hieu tai sao. */
	$da_luu = get_option( 'smlife_core_modules', null );
	if ( is_array( $da_luu ) ) {
		$moi_them = array();
		foreach ( smlife_core_modules() as $k => $m ) {
			if ( ! isset( $da_luu[ $k ] ) ) {
				$moi_them[] = $m['name'] . ( empty( $st[ $k ] ) ? ' (đang tắt)' : ' (đang bật)' );
			}
		}
		if ( $moi_them ) {
			echo '<div class="notice notice-warning"><p><b>' . count( $moi_them ) . ' tính năng mới</b> được thêm vào plugin sau lần lưu gần nhất của website này: <b>';
			echo esc_html( implode( ', ', $moi_them ) );
			echo '</b>.</p><p>Xem lại danh sách, bật hoặc tắt theo nhu cầu rồi bấm Lưu. Sau khi lưu một lần thì thông báo này biến mất.</p></div>';
		}
	}

	/* O chon mau thuong hieu, dung chung cho moi khoi do plugin ve ra */
	$mau_now = smlife_mau();
	echo '<div class="smlife-card"><h2>Màu thương hiệu</h2><div class="in">';
	echo '<p class="description" style="margin:0 0 10px">Màu dùng chung cho mọi khối do plugin vẽ ra: nút gửi form liên hệ, badge mã giảm giá, bậc giá đang áp, giá sản phẩm mua kèm, chữ khi di chuột lên tiêu đề. Website vệ tinh chỉ cần đổi đúng ô này là cả plugin đổi theo, không phải sửa CSS từng chỗ.</p>';
	echo '<input type="text" id="smc-mau-text" name="smc_mau" value="' . esc_attr( $mau_now ) . '" class="regular-text" style="max-width:130px" placeholder="#ee2468" /> ';
	echo '<input type="color" id="smc-mau-pick" value="' . esc_attr( $mau_now ) . '" style="vertical-align:middle;width:44px;height:32px;padding:0;border:1px solid #dcdcde;border-radius:4px" />';
	echo '<script>(function(){var t=document.getElementById("smc-mau-text"),p=document.getElementById("smc-mau-pick");';
	echo 'if(!t||!p)return;p.addEventListener("input",function(){t.value=p.value;});';
	echo 't.addEventListener("input",function(){if(/^#[0-9a-fA-F]{6}$/.test(t.value))p.value=t.value;});})();</script>';
	echo '</div></div>';

	echo '<div class="smc-actions">';
	echo '<span class="smc-hint">Tắt một tính năng sẽ ẩn luôn trang cài đặt của nó. Cài đặt đã lưu vẫn được giữ, bật lại là dùng tiếp.</span>';
	echo '<button type="button" class="button" id="smc-bat-het">Bật tất cả</button>';
	echo '<button type="button" class="button" id="smc-tat-het">Tắt tất cả</button>';
	echo '<script>(function(){function dat(v){document.querySelectorAll(\'input[type="checkbox"][name^="mods["]\').forEach(function(c){c.checked=v;});}';
	echo 'var a=document.getElementById("smc-bat-het"),b=document.getElementById("smc-tat-het");';
	echo 'if(a)a.addEventListener("click",function(){dat(true);});';
	echo 'if(b)b.addEventListener("click",function(){dat(false);});})();</script>';
	submit_button( 'Lưu thay đổi', 'primary', 'submit', false );
	echo '</div>';

	echo '</form></div>';
}

add_action( 'admin_post_smlife_core_save_modules', function () {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( 'Không đủ quyền.' );
	}
	check_admin_referer( 'smlife_core_save_modules', 'smlife_core_nonce' );

	$in  = isset( $_POST['mods'] ) && is_array( $_POST['mods'] ) ? wp_unslash( $_POST['mods'] ) : array();
	$out = array();
	foreach ( smlife_core_modules() as $k => $m ) {
		$out[ $k ] = empty( $in[ $k ] ) ? 0 : 1;
	}
	update_option( 'smlife_core_modules', $out );

	$cfg = get_option( 'smlife_core_settings', array() );
	if ( ! is_array( $cfg ) ) {
		$cfg = array();
	}
	$mau        = isset( $_POST['smc_mau'] ) ? sanitize_hex_color( sanitize_text_field( wp_unslash( $_POST['smc_mau'] ) ) ) : '';
	$cfg['mau'] = $mau ? $mau : '#ee2468';
	update_option( 'smlife_core_settings', $cfg );

	/* Bat/tat module can thay ngay (vd tat gap mot module dang loi) */
	smlife_xoa_cache( 'Bảng điều khiển', true );

	wp_safe_redirect( admin_url( 'admin.php?page=smlife-core&saved=1' ) );
	exit;
} );

/* ===== Nhac nho khi snippet WPCode cu con bat ===== */
add_action( 'admin_notices', function () {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}
	$c = smlife_core_conflicts();
	if ( empty( $c ) ) {
		return;
	}
	$mods  = smlife_core_modules();
	$names = array();
	foreach ( $c as $k ) {
		$names[] = $mods[ $k ]['name'];
	}
	echo '<div class="notice notice-warning"><p><b>SMLIFE Core:</b> ';
	echo esc_html( count( $names ) ) . ' tính năng đang chạy từ snippet WPCode cũ nên plugin tạm không nạp bản của mình: ';
	echo esc_html( implode( ', ', $names ) ) . '. ';
	echo 'Hãy tắt các snippet đó trong Code Snippets, plugin sẽ tiếp quản ngay. ';
	echo '<a href="' . esc_url( admin_url( 'admin.php?page=smlife-core' ) ) . '">Mở Bảng điều khiển</a></p></div>';
} );

/* ===== Link Cai dat o danh sach plugin ===== */
add_filter( 'plugin_action_links_' . plugin_basename( SMLIFE_CORE_FILE ), function ( $links ) {
	$l = '<a href="' . esc_url( admin_url( 'admin.php?page=smlife-core' ) ) . '">Bảng điều khiển</a>';
	array_unshift( $links, $l );
	return $links;
} );
