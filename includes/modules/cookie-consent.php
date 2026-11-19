<?php
/**
 * SMLIFE Core - Module: Dong y cookie (Luat Bao ve du lieu ca nhan 91/2025, ND 356/2025)
 *
 * - The nho lan dau truy cap: "Chi can thiet" / "Dong y tat ca", hai nut ngang
 *   nhau, khong mac dinh dong y. Chon xong nho 180 ngay (cookie smlife_ck).
 * - Google Consent Mode v2: in lenh "consent default" o dau <head>, TRUOC ma GTM.
 *   Chua chon / chon "Chi can thiet" -> denied, Google tu uoc tinh chuyen doi.
 *   Bam "Dong y tat ca" -> update granted + day su kien smlife_consent.
 * - Dong chu dong y duoi nut Gui dat hang (form Mua ngay) va nut Dat hang.
 * - Hien thi quyet dinh o trinh duyet nen trang cache (WP Rocket) van dung.
 * - Link hoac nut co class "smlife-ck-mo" (hoac shortcode [smlife_cookie]) mo lai the.
 * - 1.32.2: tuy chon "Tu dong y khi xem tiep" (mac dinh bat): the van hien o trang dau;
 *   khach bam link sang trang khac, gui form dat hang, hoac mo trang thu 2 trong phien
 *   ma chua bam nut nao -> tu ghi nhan "Dong y tat ca". Khach da bam "Chi can thiet"
 *   thi khong tu dong y. Lua chon luu ca cookie lan localStorage (trinh duyet trong
 *   app nhu Zalo hay xoa cookie).
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

function smlife_ck_opt() {
	$brand = function_exists( 'smlife_ten_thuong_hieu' ) ? smlife_ten_thuong_hieu() : get_bloginfo( 'name' );
	$def   = array(
		'tieu_de'   => $brand . ' tôn trọng quyền riêng tư của bạn',
		'noi_dung'  => 'Chúng tôi dùng cookie để ghi nhớ giỏ hàng, đo lường quảng cáo và gợi ý sản phẩm phù hợp.',
		'link'      => function_exists( 'get_privacy_policy_url' ) ? (string) get_privacy_policy_url() : '',
		'link_chu'  => 'Chính sách bảo vệ dữ liệu',
		'nut_co'    => 'Đồng ý tất cả',
		'nut_khong' => 'Chỉ cần thiết',
		'mau'       => '#EE2468',
		'ngay'      => 180,
		'consent'   => 1,
		'form'      => 1,
		'form_chu'  => 'Bằng việc đặt hàng, bạn đồng ý với',
		'tu_dong'   => 1,
		'tu_dong_chu' => 'Tiếp tục xem trang khác nghĩa là bạn đồng ý.',
	);
	$s = get_option( 'smlife_ck', array() );
	return is_array( $s ) ? array_merge( $def, $s ) : $def;
}

/* Khoang cach tu day man hinh dien thoai: tranh thanh Menu Mobile */
function smlife_ck_cach_day() {
	if ( function_exists( 'smlife_mm_opt' ) && function_exists( 'smlife_core_is_on' ) && smlife_core_is_on( 'mobile-menu' ) ) {
		$m = smlife_mm_opt();
		if ( ! empty( $m['enabled'] ) ) {
			return (int) $m['height'] + 10;
		}
	}
	return 12;
}

/* ===== 1. Consent Mode mac dinh, in som nhat trong <head> ===== */
add_action( 'wp_head', function () {
	$o = smlife_ck_opt();
	if ( empty( $o['consent'] ) || is_admin() ) {
		return;
	}
	$tuoi = (int) ( max( 1, (int) $o['ngay'] ) * 86400 );
	echo "<script>(function(){window.dataLayer=window.dataLayer||[];function g(){dataLayer.push(arguments);}window.gtag=window.gtag||g;";
	echo "function sc(x){document.cookie='smlife_ck='+x+';path=/;max-age=" . $tuoi . ";SameSite=Lax'+(location.protocol==='https:'?';Secure':'');try{localStorage.setItem('smlife_ck',x);}catch(e){}}";
	echo "var m=document.cookie.match(/(?:^|; )smlife_ck=([^;]+)/),v=m?m[1]:'',tu=0;";
	/* Cookie bi xoa (trinh duyet trong app) nhung localStorage con: dung lai lua chon cu */
	echo "if(!v){try{var l=localStorage.getItem('smlife_ck');if(l==='all'||l==='need'){v=l;sc(v);}}catch(e){}}";
	if ( ! empty( $o['tu_dong'] ) ) {
		/* Trang thu 2 (khac duong dan) trong cung phien ma chua chon: tu dong y */
		echo "if(!v){try{var p=sessionStorage.getItem('smlife_ck_p1');if(p&&p!==location.pathname){v='all';tu=1;sc('all');}else if(!p){sessionStorage.setItem('smlife_ck_p1',location.pathname);}}catch(e){}}";
	}
	echo "var y=(v==='all')?'granted':'denied';";
	echo "gtag('consent','default',{ad_storage:y,ad_user_data:y,ad_personalization:y,analytics_storage:y,functionality_storage:'granted',security_storage:'granted',wait_for_update:500});";
	echo "gtag('set','ads_data_redaction',y==='denied');gtag('set','url_passthrough',true);";
	echo "if(tu){dataLayer.push({event:'smlife_consent',smlife_consent:'all',smlife_consent_kieu:'tu_dong'});}})();</script>\n";
}, -9999 );

/* ===== 2. The dong y ===== */
add_action( 'wp_footer', function () {
	if ( is_admin() ) {
		return;
	}
	$o    = smlife_ck_opt();
	$mau  = sanitize_hex_color( $o['mau'] ) ? $o['mau'] : '#EE2468';
	$ngay = max( 1, (int) $o['ngay'] );
	$day  = smlife_ck_cach_day();
	$link = '' !== trim( (string) $o['link'] ) ? ' Xem <a href="' . esc_url( $o['link'] ) . '" target="_blank" rel="noopener">' . esc_html( $o['link_chu'] ) . '</a>.' : '';
	?>
<style>
#smlife-ck{display:none;position:fixed;left:24px;bottom:24px;width:360px;max-width:calc(100% - 20px);z-index:99998;background:#fff;color:#222;border:1px solid #f0f0f0;border-radius:12px;box-shadow:0 6px 24px rgba(0,0,0,.18);padding:12px 14px;font-size:13px;line-height:1.45;font-family:inherit}
#smlife-ck.on{display:block}
#smlife-ck .ck-h{font-weight:700;margin:0 0 4px}
#smlife-ck a{color:#2b8ee6;text-decoration:underline}
#smlife-ck .ck-r{display:flex;gap:8px;margin-top:10px}
#smlife-ck button{flex:1;padding:9px 8px;border:0;border-radius:6px;font-weight:700;font-size:13px;cursor:pointer;line-height:1.2;text-transform:none;margin:0;min-height:0}
#smlife-ck .ck-no{background:#f3f4f6;color:#333}
#smlife-ck .ck-ok{background:<?php echo esc_attr( $mau ); ?>;color:#fff}
@media(max-width:849px){#smlife-ck{left:10px;right:10px;width:auto;bottom:<?php echo (int) $day; ?>px}}
.smlife-ck-form{font-size:12px;color:#666;margin:8px 0 0;line-height:1.4;text-align:center}
.smlife-ck-form a{color:inherit;text-decoration:underline}
</style>
<div id="smlife-ck" role="dialog" aria-live="polite" aria-label="Đồng ý cookie">
<p class="ck-h">🍪 <?php echo esc_html( $o['tieu_de'] ); ?></p>
<div><?php echo esc_html( $o['noi_dung'] ) . $link . ( ! empty( $o['tu_dong'] ) && '' !== trim( (string) $o['tu_dong_chu'] ) ? ' ' . esc_html( $o['tu_dong_chu'] ) : '' ); // phpcs:ignore -- da escape ?></div>
<div class="ck-r"><button type="button" class="ck-no" data-ck="need"><?php echo esc_html( $o['nut_khong'] ); ?></button><button type="button" class="ck-ok" data-ck="all"><?php echo esc_html( $o['nut_co'] ); ?></button></div>
</div>
<script>(function(){
var box=document.getElementById('smlife-ck');if(!box)return;
function doc(){var m=document.cookie.match(/(?:^|; )smlife_ck=([^;]+)/);if(m)return m[1];try{var l=localStorage.getItem('smlife_ck');if(l==='all'||l==='need')return l;}catch(e){}return '';}
function chon(v,kieu){
 document.cookie='smlife_ck='+v+';path=/;max-age=<?php echo (int) ( $ngay * 86400 ); ?>;SameSite=Lax'+(location.protocol==='https:'?';Secure':'');
 try{localStorage.setItem('smlife_ck',v);}catch(e){}
 var y=v==='all'?'granted':'denied';window.dataLayer=window.dataLayer||[];
 if(typeof window.gtag==='function'){window.gtag('consent','update',{ad_storage:y,ad_user_data:y,ad_personalization:y,analytics_storage:y});}
 window.dataLayer.push({event:'smlife_consent',smlife_consent:v,smlife_consent_kieu:kieu||'bam_nut'});
 box.classList.remove('on');
}
box.addEventListener('click',function(e){var b=e.target.closest?e.target.closest('[data-ck]'):null;if(b){chon(b.getAttribute('data-ck'));}});
document.addEventListener('click',function(e){var a=e.target.closest?e.target.closest('.smlife-ck-mo'):null;if(a){e.preventDefault();box.classList.add('on');}});
if(!doc()||/[?&]smlife_ck_xem=1/.test(location.search)){box.classList.add('on');}
<?php if ( ! empty( $o['tu_dong'] ) ) : ?>
/* Tu dong y: bam link sang trang khac cung web, hoac gui form (dat hang). Chay o pha capture, truoc tag chuyen doi trong GTM */
function tu(){if(!doc()){chon('all','tu_dong');}}
document.addEventListener('click',function(e){
 if(!e.target.closest||e.target.closest('#smlife-ck')||e.target.closest('.smlife-ck-mo'))return;
 var a=e.target.closest('a[href]');
 if(a){var h=a.getAttribute('href')||'';if(h.charAt(0)==='#'||/^(javascript|tel|mailto|zalo|sms):/i.test(h))return;try{var u=new URL(a.href,location.href);if(u.host===location.host&&u.pathname!==location.pathname){tu();}}catch(x){}return;}
 if(e.target.closest('button[type=submit],input[type=submit],.smlife-qo-submit,.single_add_to_cart_button,.checkout-button')){tu();}
},true);
document.addEventListener('submit',function(){tu();},true);
<?php endif; ?>
})();</script>
	<?php
}, 99 );

/* Shortcode mo lai the: [smlife_cookie]Cài đặt cookie[/smlife_cookie] */
add_shortcode( 'smlife_cookie', function ( $a, $c = '' ) {
	return '<a href="#" class="smlife-ck-mo">' . esc_html( '' !== trim( (string) $c ) ? $c : 'Cài đặt cookie' ) . '</a>';
} );

/* ===== 3. Dong dong y duoi nut dat hang ===== */
function smlife_ck_dong_form() {
	$o = smlife_ck_opt();
	if ( empty( $o['form'] ) ) {
		return '';
	}
	$ten = esc_html( function_exists( 'smlife_ten_thuong_hieu' ) ? smlife_ten_thuong_hieu() : get_bloginfo( 'name' ) );
	$chu = esc_html( $o['form_chu'] ) . ' ';
	$chu .= '' !== trim( (string) $o['link'] ) ? '<a href="' . esc_url( $o['link'] ) . '" target="_blank" rel="noopener">Chính sách bảo vệ dữ liệu cá nhân</a>' : 'Chính sách bảo vệ dữ liệu cá nhân';
	return '<p class="smlife-ck-form">' . $chu . ' của ' . $ten . '.</p>';
}
add_filter( 'smlife_qo_sau_nut', function ( $h ) {
	return $h . smlife_ck_dong_form();
} );
add_action( 'woocommerce_review_order_before_submit', function () {
	echo smlife_ck_dong_form(); // phpcs:ignore -- da escape
} );

/* ===== Trang cai dat ===== */
add_action( 'admin_menu', function () {
	add_submenu_page( 'smlife-core', 'Đồng ý cookie', 'Đồng ý cookie', 'manage_options', 'smlife-dong-y-cookie', 'smlife_ck_page' );
}, 12 );

function smlife_ck_page() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}
	$o = smlife_ck_opt();
	echo '<div class="wrap smlife-wrap"><div class="smc-hd"><h1>Đồng ý cookie</h1>';
	echo '<p>Thẻ nhỏ hỏi khách đồng ý cookie lần đầu truy cập, nối với Google Consent Mode v2 để quảng cáo vẫn ước tính được chuyển đổi. Đáp ứng Luật Bảo vệ dữ liệu cá nhân 2025 và Nghị định 356/2025: không mặc định đồng ý, hai nút ngang nhau.</p></div>';
	if ( isset( $_GET['updated'] ) ) { // phpcs:ignore
		echo '<div class="notice notice-success is-dismissible"><p>Đã lưu. Trang trong cache sẽ hiện chữ mới sau lần xoá cache đêm.</p></div>';
	}
	if ( '' === trim( (string) $o['link'] ) ) {
		echo '<div class="notice notice-warning"><p>Chưa có link trang chính sách. Điền ô "Link chính sách" hoặc chọn trang ở Cài đặt > Quyền riêng tư.</p></div>';
	}
	$txt = function ( $k, $ten, $mo = '', $w = 'regular-text' ) use ( $o ) {
		echo '<tr><th scope="row">' . esc_html( $ten ) . '</th><td><input type="text" name="ck[' . esc_attr( $k ) . ']" value="' . esc_attr( $o[ $k ] ) . '" class="' . esc_attr( $w ) . '" />' . ( $mo ? '<p class="description">' . esc_html( $mo ) . '</p>' : '' ) . '</td></tr>';
	};
	echo '<div class="smlife-card"><form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '">';
	echo '<input type="hidden" name="action" value="smlife_ck_save" />';
	wp_nonce_field( 'smlife_ck_save', 'smlife_ck_nonce' );
	echo '<table class="form-table" role="presentation">';
	$txt( 'tieu_de', 'Tiêu đề', '', 'large-text' );
	echo '<tr><th scope="row">Nội dung</th><td><textarea name="ck[noi_dung]" rows="2" class="large-text">' . esc_textarea( $o['noi_dung'] ) . '</textarea></td></tr>';
	$txt( 'link', 'Link chính sách', 'Đường dẫn trang Chính sách bảo vệ dữ liệu cá nhân.', 'large-text' );
	$txt( 'link_chu', 'Chữ của link' );
	$txt( 'nut_khong', 'Nút từ chối' );
	$txt( 'nut_co', 'Nút đồng ý' );
	echo '<tr><th scope="row">Màu nút đồng ý</th><td><input type="text" name="ck[mau]" value="' . esc_attr( $o['mau'] ) . '" class="small-text" style="width:90px" /></td></tr>';
	echo '<tr><th scope="row">Nhớ lựa chọn</th><td><input type="number" name="ck[ngay]" value="' . esc_attr( $o['ngay'] ) . '" min="1" max="395" class="small-text" /> ngày</td></tr>';
	echo '<tr><th scope="row">Google Consent Mode v2</th><td><label><input type="checkbox" name="ck[consent]" value="1" ' . checked( 1, $o['consent'], false ) . ' /> Gửi tín hiệu đồng ý cho Google Tag Manager</label><p class="description">Trong GTM, tag Google Ads và GA4 tự đọc tín hiệu này. Khách chưa đồng ý thì tag chạy chế độ không cookie, Google tự ước tính chuyển đổi.</p></td></tr>';
	echo '<tr><th scope="row">Dòng đồng ý ở form đặt hàng</th><td><label><input type="checkbox" name="ck[form]" value="1" ' . checked( 1, $o['form'], false ) . ' /> Hiện dưới nút Gửi đặt hàng (form Mua ngay) và nút Đặt hàng (thanh toán)</label><br><input type="text" name="ck[form_chu]" value="' . esc_attr( $o['form_chu'] ) . '" class="large-text" style="margin-top:6px" /></td></tr>';
	echo '<tr><th scope="row">Tự đồng ý khi xem tiếp</th><td><label><input type="checkbox" name="ck[tu_dong]" value="1" ' . checked( 1, $o['tu_dong'], false ) . ' /> Khách chưa bấm nút nào mà bấm sang trang khác, mở trang thứ 2 hoặc gửi form đặt hàng thì tính là Đồng ý tất cả</label><br><input type="text" name="ck[tu_dong_chu]" value="' . esc_attr( $o['tu_dong_chu'] ) . '" class="large-text" style="margin-top:6px" /><p class="description">Câu này hiện cuối thẻ để báo cho khách. Khách đã bấm "' . esc_html( $o['nut_khong'] ) . '" thì không tự đồng ý. Lưu ý: Luật 91/2025 coi im lặng không phải là đồng ý, bật tùy chọn này là doanh nghiệp tự chịu rủi ro pháp lý.</p></td></tr>';
	echo '</table>';
	submit_button( 'Lưu' );
	echo '</form>';
	echo '<p>Xem thử: <a href="' . esc_url( add_query_arg( 'smlife_ck_xem', '1', home_url( '/' ) ) ) . '" target="_blank">mở trang chủ với thẻ cookie</a>. Nút mở lại thẻ cho khách: đặt shortcode <code>[smlife_cookie]Cài đặt cookie[/smlife_cookie]</code> ở chân trang, hoặc gán class <code>smlife-ck-mo</code> cho một link menu.</p></div>';
	echo '<div class="smlife-card"><h2>Mẫu trang Chính sách bảo vệ dữ liệu cá nhân</h2><p>Chép nội dung dưới đây vào trang chính sách, sửa các chỗ trong ngoặc vuông.</p>';
	echo '<textarea readonly rows="18" class="large-text" onclick="this.select()">' . esc_textarea( smlife_ck_mau_chinh_sach() ) . '</textarea></div></div>';
}

function smlife_ck_mau_chinh_sach() {
	$b = function_exists( 'smlife_ten_thuong_hieu' ) ? smlife_ten_thuong_hieu() : get_bloginfo( 'name' );
	$d = (string) wp_parse_url( home_url(), PHP_URL_HOST );
	return "CHÍNH SÁCH BẢO VỆ DỮ LIỆU CÁ NHÂN\n"
		. "Áp dụng từ ngày [01/12/2026] cho website {$d}, theo Luật Bảo vệ dữ liệu cá nhân số 91/2025/QH15 và Nghị định 356/2025/NĐ-CP.\n\n"
		. "1. Đơn vị xử lý dữ liệu\n[Tên công ty], MST [mã số thuế], địa chỉ [địa chỉ]. Liên hệ về dữ liệu cá nhân: [email], hotline [số điện thoại].\n\n"
		. "2. Dữ liệu chúng tôi thu thập\n- Khi bạn đặt hàng hoặc gửi form: họ tên, số điện thoại, email, địa chỉ nhận hàng, ghi chú đơn hàng.\n- Khi bạn truy cập website: cookie và dữ liệu kỹ thuật (trình duyệt, thiết bị, trang đã xem, nguồn truy cập).\n- Chúng tôi không thu thập dữ liệu cá nhân nhạy cảm và không yêu cầu ảnh giấy tờ tùy thân.\n\n"
		. "3. Mục đích sử dụng\n- Xử lý, xác nhận, giao lắp và bảo hành đơn hàng; liên hệ tư vấn khi bạn yêu cầu.\n- Gửi tin nhắn xác nhận đơn hàng qua Zalo (ZNS) hoặc email tới số điện thoại, email bạn cung cấp.\n- Xuất hóa đơn, thực hiện nghĩa vụ kế toán, thuế theo quy định.\n- Đo lường, cải thiện website và quảng cáo (chỉ khi bạn đồng ý cookie đo lường, quảng cáo).\n\n"
		. "4. Cookie\n- Cookie cần thiết: giỏ hàng, đăng nhập, bảo mật. Luôn bật để website hoạt động.\n- Cookie đo lường và quảng cáo (Google Analytics, Google Ads, Google Tag Manager, Facebook...): chỉ bật khi bạn chọn \"Đồng ý tất cả\" hoặc tiếp tục xem trang khác sau khi đã được thông báo. Bạn có thể đổi lựa chọn bất cứ lúc nào qua link \"Cài đặt cookie\" ở chân trang.\n\n"
		. "5. Chia sẻ dữ liệu\nChỉ chia sẻ ở mức cần thiết cho: đơn vị vận chuyển, đối tác thanh toán, nhà cung cấp hóa đơn điện tử, Zalo (gửi tin xác nhận), cơ quan nhà nước khi có yêu cầu hợp pháp. Chúng tôi không mua bán dữ liệu cá nhân.\n\n"
		. "6. Thời gian lưu trữ\nDữ liệu đơn hàng lưu trong thời hạn kế toán, thuế theo luật định. Dữ liệu tư vấn chưa phát sinh đơn lưu tối đa 24 tháng rồi xóa.\n\n"
		. "7. Quyền của bạn\nBạn có quyền được biết, đồng ý, truy cập, chỉnh sửa, xóa, hạn chế xử lý, rút lại sự đồng ý và khiếu nại. Gửi yêu cầu tới [email] hoặc hotline [số điện thoại]; chúng tôi phản hồi trong thời hạn luật định.\n\n"
		. "8. Bảo mật\nDữ liệu được lưu trên máy chủ có mã hóa kết nối (HTTPS), phân quyền truy cập theo vai trò, sao lưu định kỳ. Nếu xảy ra sự cố lộ lọt có thể gây tổn hại, chúng tôi thông báo cơ quan chức năng và người bị ảnh hưởng theo quy định.\n\n"
		. "9. Thay đổi chính sách\nMọi thay đổi được cập nhật tại trang này kèm ngày áp dụng.\n\n"
		. "{$b} - {$d}";
}

add_action( 'admin_post_smlife_ck_save', function () {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( 'Không đủ quyền.' );
	}
	check_admin_referer( 'smlife_ck_save', 'smlife_ck_nonce' );
	$in = isset( $_POST['ck'] ) && is_array( $_POST['ck'] ) ? wp_unslash( $_POST['ck'] ) : array(); // phpcs:ignore
	$d  = smlife_ck_opt();
	$t  = function ( $k ) use ( $in, $d ) {
		return isset( $in[ $k ] ) && '' !== trim( (string) $in[ $k ] ) ? sanitize_text_field( $in[ $k ] ) : $d[ $k ];
	};
	update_option( 'smlife_ck', array(
		'tieu_de'   => $t( 'tieu_de' ),
		'noi_dung'  => isset( $in['noi_dung'] ) && '' !== trim( (string) $in['noi_dung'] ) ? sanitize_textarea_field( $in['noi_dung'] ) : $d['noi_dung'],
		'link'      => isset( $in['link'] ) ? esc_url_raw( trim( (string) $in['link'] ) ) : '',
		'link_chu'  => $t( 'link_chu' ),
		'nut_co'    => $t( 'nut_co' ),
		'nut_khong' => $t( 'nut_khong' ),
		'mau'       => sanitize_hex_color( isset( $in['mau'] ) ? (string) $in['mau'] : '' ) ? sanitize_hex_color( $in['mau'] ) : '#EE2468',
		'ngay'      => isset( $in['ngay'] ) ? max( 1, min( 395, (int) $in['ngay'] ) ) : 180,
		'consent'   => empty( $in['consent'] ) ? 0 : 1,
		'form'      => empty( $in['form'] ) ? 0 : 1,
		'form_chu'  => $t( 'form_chu' ),
		'tu_dong'   => empty( $in['tu_dong'] ) ? 0 : 1,
		'tu_dong_chu' => isset( $in['tu_dong_chu'] ) ? sanitize_text_field( $in['tu_dong_chu'] ) : $d['tu_dong_chu'],
	) );
	if ( function_exists( 'smlife_xoa_cache' ) ) {
		smlife_xoa_cache( 'Đồng ý cookie' );
	}
	wp_safe_redirect( admin_url( 'admin.php?page=smlife-dong-y-cookie&updated=1' ) );
	exit;
} );
