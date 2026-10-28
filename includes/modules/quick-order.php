<?php
/**
 * SMLIFE Core - Module: Form Mua ngay
 * Form dat hang nhanh co honeypot va captcha, khong can Contact Form 7.
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

/**
 * SMLIFE CORE - 06. Form Mua ngay (native, AJAX)
 * ---------------------------------------------
 * Form Dat hang nhanh tu xu ly, thay cho CF7:
 * - Shortcode [smlife_quick_order] de nhung vao popup Mua ngay.
 * - Gui AJAX: sai captcha / thieu truong -> GIU form, bao loi tai cho, doi phep tinh moi.
 * - Tuy chinh noi dung (tieu de, nhan, placeholder, nut, mau nut).
 * - Bat/tat truong Email.
 * - Honeypot + Math captcha chong spam.
 * - Gui email don hang qua Email Center (mau Dat hang nhanh + xac nhan khach).
 * Cau hinh trong SMLIFE Core > Form Mua ngay.
 *
 * WPCode: Run everywhere. Khi dan: KHONG copy dong mo PHP o tren cung.
 */

/* ===== Cau hinh ===== */
function smlife_qo_opt() {
	$def = array(
		'heading'     => 'ĐẶT HÀNG NHANH',
		'l_name'      => 'Tên của bạn',
		'ph_name'     => 'Nhập họ tên',
		'l_phone'     => 'Số điện thoại',
		'ph_phone'    => 'Đúng định dạng: 09xxxxxxxx',
		'l_address'   => 'Địa chỉ nhận hàng',
		'ph_address'  => 'Số nhà, đường, phường...',
		'l_note'      => 'Bạn cần lưu ý gì cho chúng tôi?',
		'ph_note'     => 'Màu sắc, thời gian giao hàng ...',
		'show_email'  => 1,
		'l_email'     => 'Email (nhập nếu muốn nhận email xác nhận đơn hàng)',
		'ph_email'    => 'Email của bạn (không bắt buộc)',
		'btn'         => 'GỬI',
		'btn_color'   => '#EE2468',
		'honeypot'    => 1,
		'captcha'     => 1,
		'success_msg' => 'Cảm ơn bạn! Đơn đặt hàng đã được gửi, chúng tôi sẽ liên hệ trong thời gian sớm nhất.',
		'redirect_url'=> '',
		'notify_to'   => '',
		'gtm_on'      => 1,
		'gtm_event'   => 'form_muangay',
		'gtm_value'   => 0,
	);
	$s = get_option( 'smlife_qo', array() );
	return is_array( $s ) ? array_merge( $def, $s ) : $def;
}

/* Nguoi nhan email noi bo: uu tien option, roi Email Center, roi admin_email. */
function smlife_qo_notify_to() {
	$o = smlife_qo_opt();
	if ( is_email( $o['notify_to'] ) ) {
		return $o['notify_to'];
	}
	if ( function_exists( 'smlife_ec_all' ) ) {
		$all = smlife_ec_all();
		if ( isset( $all['cf7']['notify_to'] ) && is_email( $all['cf7']['notify_to'] ) ) {
			return $all['cf7']['notify_to'];
		}
	}
	return get_option( 'admin_email' );
}

/* Chu ky captcha.
 *
 * Ban cu ky moi dap an: HMAC("7") luc nao cung ra mot chuoi, khong het han.
 * Bot giai dung mot lan la dung lai duoc mai mai. Nay gan them moc thoi gian
 * va mot so ngau nhien, het han sau 48 gio (smlife_qo_han), va moi chu ky chi dung mot lan. */
/* ===== Han dung cua form: 48 gio =====
 * Form nam san trong HTML da cache. Cache xoa mot lan luc 00:05 moi dem nen
 * mot trang co the song gan 24 gio. Nonce mac dinh cua WordPress chi song
 * 12-24 gio, phep tinh captcha cu chi 3 gio: khach mo trang cache cu va JS lay
 * ma moi (smlife_qo_tok) bi cham hoac loi la bi bao het phien. Nang ca hai len
 * 48 gio, chi cho rieng action 'smlife_qo', nonce khac cua website giu nguyen. */
function smlife_qo_han() {
	return 2 * DAY_IN_SECONDS;
}

/* WordPress 6.1 tro len truyen them $action vao bo loc nonce_life.
 * Ban cu hon chi truyen $life, $action nhan gia tri mac dinh -1, tra ve
 * nguyen $life, khong anh huong gi. */
add_filter( 'nonce_life', function ( $life, $action = -1 ) {
	return ( 'smlife_qo' === $action ) ? smlife_qo_han() : $life;
}, 10, 2 );

/* Ghi lai moi lan khach bi tu choi vi het phien, de biet loi co xay ra that
 * hay khong. Luu 20 lan gan nhat vao option smlife_qo_loi (khong autoload)
 * va ghi them mot dong vao error_log. $loai: nonce | captcha_het */
function smlife_qo_ghi_loi( $loai ) {
	$ip  = function_exists( 'smlife_ip' ) ? smlife_ip() : '';
	$ref = isset( $_SERVER['HTTP_REFERER'] ) ? esc_url_raw( wp_unslash( $_SERVER['HTTP_REFERER'] ) ) : '';
	$d   = get_option( 'smlife_qo_loi', array() );
	if ( ! is_array( $d ) ) {
		$d = array();
	}
	if ( ! isset( $d['dem'] ) || ! is_array( $d['dem'] ) ) {
		$d['dem'] = array();
	}
	$d['dem'][ $loai ] = isset( $d['dem'][ $loai ] ) ? (int) $d['dem'][ $loai ] + 1 : 1;
	$ds = isset( $d['ds'] ) && is_array( $d['ds'] ) ? $d['ds'] : array();
	array_unshift( $ds, array( 't' => time(), 'loai' => $loai, 'ip' => $ip, 'url' => $ref ) );
	$d['ds'] = array_slice( $ds, 0, 20 );
	update_option( 'smlife_qo_loi', $d, false );
	error_log( '[SMLIFE Core] Form Mua ngay - tu choi: ' . $loai . ' - IP ' . $ip . ' - ' . $ref ); // phpcs:ignore
}

/* Dem so lan nonce 24-48 gio tuoi van duoc chap nhan, tuc la so don da cuu
 * duoc nho keo dai han. Chi dem, khong ghi chi tiet. */
function smlife_qo_dem_cuu() {
	$d = get_option( 'smlife_qo_loi', array() );
	if ( ! is_array( $d ) ) {
		$d = array();
	}
	$d['cuu'] = isset( $d['cuu'] ) ? (int) $d['cuu'] + 1 : 1;
	update_option( 'smlife_qo_loi', $d, false );
}

function smlife_qo_sign( $answer, $t = 0, $r = '' ) {
	return hash_hmac( 'sha256', (string) $answer . '|' . (int) $t . '|' . (string) $r, wp_salt( 'auth' ) . 'smlife_qo' );
}

/* Sinh phep tinh moi cho captcha. */
function smlife_qo_new_captcha() {
	$a = wp_rand( 1, 9 );
	$b = wp_rand( 1, 9 );
	$t = time();
	$r = wp_generate_password( 10, false, false );
	return array( 'a' => $a, 'b' => $b, 't' => $t, 'r' => $r, 'h' => smlife_qo_sign( $a + $b, $t, $r ) );
}

/* ===== Doc san pham dang xem =====
 * Dung khi form duoc nhung thang vao trang bang shortcode, luc do khong co popup
 * nen khong co JS nao dien ten san pham, link va gia. Doc o phia may chu cho chac.
 * Trang khong phai san pham thi tra ve rong, may chu se lay URL tu HTTP_REFERER. */
function smlife_qo_current_product() {
	$out = array( 'name' => '', 'link' => '', 'price' => '' );
	if ( ! function_exists( 'is_product' ) || ! is_product() ) {
		return $out;
	}
	$id = get_queried_object_id();
	if ( ! $id ) {
		return $out;
	}
	$out['name'] = get_the_title( $id );
	$out['link'] = get_permalink( $id );
	if ( function_exists( 'wc_get_product' ) ) {
		$p = wc_get_product( $id );
		if ( $p ) {
			$gia = $p->get_price();
			if ( '' === $gia || null === $gia ) {
				$gia = $p->get_regular_price();
			}
			$gia = preg_replace( '/[^0-9]/', '', (string) $gia );
			$out['price'] = $gia ? $gia : '';
		}
	}
	return $out;
}

/* ===== Shortcode form ===== */
add_shortcode( 'smlife_quick_order', 'smlife_qo_shortcode' );
function smlife_qo_shortcode() {
	$o     = smlife_qo_opt();
	$cap   = smlife_qo_new_captcha();
	$ajax  = esc_url( admin_url( 'admin-ajax.php' ) );
	$nonce = wp_create_nonce( 'smlife_qo' );
	$sp    = smlife_qo_current_product();

	$h  = smlife_qo_assets();
	$h .= '<form class="smlife-qo-form" method="post" action="' . $ajax . '" data-ajax="' . $ajax . '">';
	$h .= '<div class="qo-alert" style="display:none"></div>';
	$h .= '<input type="hidden" name="qo_price" value="' . esc_attr( $sp['price'] ) . '" />';

	$h .= '<p class="qo-row"><label>' . esc_html( $o['l_name'] ) . ' <span class="qo-req">*</span></label>';
	$h .= '<input type="text" name="qo_name" required placeholder="' . esc_attr( $o['ph_name'] ) . '" /></p>';

	$h .= '<p class="qo-row"><label>' . esc_html( $o['l_phone'] ) . ' <span class="qo-req">*</span></label>';
	$h .= '<input type="tel" name="qo_phone" required pattern="[0-9 .+]{8,15}" placeholder="' . esc_attr( $o['ph_phone'] ) . '" /></p>';

	$h .= '<p class="qo-row"><label>' . esc_html( $o['l_address'] ) . '</label>';
	$h .= '<input type="text" name="qo_address" placeholder="' . esc_attr( $o['ph_address'] ) . '" /></p>';

	$h .= '<p class="qo-row"><label>' . esc_html( $o['l_note'] ) . '</label>';
	$h .= '<input type="text" name="qo_note" placeholder="' . esc_attr( $o['ph_note'] ) . '" /></p>';

	if ( ! empty( $o['show_email'] ) ) {
		$h .= '<p class="qo-row"><label>' . esc_html( $o['l_email'] ) . '</label>';
		$h .= '<input type="email" name="qo_email" placeholder="' . esc_attr( $o['ph_email'] ) . '" /></p>';
	}

	$h .= '<input type="hidden" name="action" value="smlife_qo_submit" />';
	$h .= '<input type="hidden" name="smlife_qo_nonce" value="' . esc_attr( $nonce ) . '" />';
	$h .= '<input type="hidden" name="qo_product" value="' . esc_attr( $sp['name'] ) . '" />';
	$h .= '<input type="hidden" name="qo_link" value="' . esc_attr( $sp['link'] ) . '" />';

	if ( ! empty( $o['honeypot'] ) ) {
		$h .= '<div class="qo-hp" aria-hidden="true"><label>Để trống ô này<input type="text" name="qo_website" value="" autocomplete="off" tabindex="-1" /></label></div>';
	}

	if ( ! empty( $o['captcha'] ) ) {
		$h .= '<p class="qo-row qo-captcha"><label>Chống spam: <span class="qo-cap-q">' . (int) $cap['a'] . ' + ' . (int) $cap['b'] . '</span> = ? <span class="qo-req">*</span></label>';
		$h .= '<input type="number" name="qo_cap" required inputmode="numeric" placeholder="Nhập kết quả" />';
		$h .= '<input type="hidden" name="qo_cap_h" value="' . esc_attr( $cap['h'] ) . '" />';
		$h .= '<input type="hidden" name="qo_cap_t" value="' . (int) $cap['t'] . '" />';
		$h .= '<input type="hidden" name="qo_cap_r" value="' . esc_attr( $cap['r'] ) . '" /></p>';
	}

	$h .= '<button type="submit" class="smlife-qo-submit" style="background:' . esc_attr( $o['btn_color'] ) . ' !important;">' . esc_html( $o['btn'] ) . '</button>';
	$h .= '</form>';

	return $h;
}

/* CSS + JS, chi in 1 lan. */
function smlife_qo_assets() {
	static $done = false;
	if ( $done ) {
		return '';
	}
	$done    = true;
	$o       = smlife_qo_opt();
	$success = esc_js( $o['success_msg'] );

	$css  = '<style>';
	$css .= '.smlife-qo-form .qo-row{margin:0 0 12px;}';
	$css .= '.smlife-qo-form label{display:block;font-weight:600;margin-bottom:5px;font-size:14px;}';
	$css .= '.smlife-qo-form input{width:100%;padding:10px 12px;border:1px solid #ddd;border-radius:6px;box-sizing:border-box;}';
	$css .= '.smlife-qo-form .qo-req{color:#EE2468;margin-left:2px;}';
	$css .= '.smlife-qo-form .smlife-qo-submit{width:100%;color:#fff !important;border:none;border-radius:6px;padding:12px;margin-top:6px;font-weight:700;cursor:pointer;}';
	$css .= '.smlife-qo-form .smlife-qo-submit[disabled]{opacity:.6;cursor:default;}';
	$css .= '.smlife-qo-form .qo-hp{position:absolute !important;left:-9999px !important;top:-9999px !important;height:0;width:0;overflow:hidden;}';
	$css .= '.smlife-qo-form .qo-alert{padding:10px 12px;border-radius:6px;margin-bottom:12px;font-size:14px;line-height:1.4;}';
	$css .= '.smlife-qo-form .qo-alert.err{background:#fdecea;color:#c62828;border:1px solid #f5c6cb;}';
	$css .= '.smlife-qo-done{text-align:center;padding:24px 12px;font-size:16px;color:#2e7d32;font-weight:600;}';
	/* Dien thoai: thu gon khoang cach cho form ngan lai, nut Gui luon trong tam nhin */
	$css .= '@media (max-width:849px){';
	$css .= '.smlife-qo-form .qo-row{margin:0 0 5px;}';
	$css .= '.smlife-qo-form label{margin-bottom:2px;font-size:12.5px;line-height:1.25;}';
	$css .= '.smlife-qo-form input{padding:7px 10px;font-size:14px;min-height:38px;}';
	$css .= '.smlife-qo-form .smlife-qo-submit{padding:11px;margin-top:4px;font-size:15px;}';
	$css .= '.smlife-qo-form .qo-alert{padding:8px 10px;margin-bottom:8px;font-size:13px;}';
	$css .= '}';
	$css .= '</style>';

	$js  = '<script>(function(){';
	$js .= 'function byn(f,n){return f.querySelector("[name=\'"+n+"\']");}';
	$js .= 'function cap(f,c){if(!c)return;var q=f.querySelector(".qo-cap-q");if(q){q.textContent=c.a+" + "+c.b;}';
	$js .= '["h","t","r"].forEach(function(k){var x=byn(f,"qo_cap_"+k);if(x){x.value=c[k];}});}';
	$js .= 'function tok(f){if(f.getAttribute("data-tok"))return;f.setAttribute("data-tok","1");';
	$js .= 'var q=new FormData();q.append("action","smlife_qo_tok");';
	$js .= 'fetch(f.getAttribute("data-ajax"),{method:"POST",body:q,credentials:"same-origin"}).then(function(r){return r.json();}).then(function(d){';
	$js .= 'if(!d||!d.success)return;var n=byn(f,"smlife_qo_nonce");if(n){n.value=d.data.n;}cap(f,d.data.cap);}).catch(function(){});}';
	$js .= 'document.addEventListener("focusin",function(e){var f=e.target&&e.target.closest?e.target.closest("form.smlife-qo-form"):null;if(f)tok(f);});';
	$js .= 'document.addEventListener("submit",function(e){';
	$js .= 'var f=e.target;if(!f.classList||!f.classList.contains("smlife-qo-form")){return;}';
	$js .= 'e.preventDefault();';
	$js .= 'var url=f.getAttribute("data-ajax");';
	$js .= 'var btn=f.querySelector(".smlife-qo-submit");';
	$js .= 'var box=f.querySelector(".qo-alert");';
	$js .= 'if(btn){btn.disabled=true;btn.dataset.t=btn.textContent;btn.textContent="Đang gửi...";}';
	$js .= 'var fd=new FormData(f);';
	$js .= 'fetch(url,{method:"POST",body:fd,credentials:"same-origin"}).then(function(r){return r.json();}).then(function(res){';
	$js .= 'if(btn){btn.disabled=false;btn.textContent=btn.dataset.t||"GỬI";}';
	$js .= 'if(res&&res.success){';
	/* Gui su kien chuyen doi len dataLayer, cho GTM ban xong tag roi moi chuyen trang.
	 * Neu chuyen trang ngay lap tuc thi yeu cau cua Google Ads bi huy giua chung. */
	$js .= 'var L=res.data&&res.data.lead;';
	$js .= 'var rd=(res.data&&res.data.redirect)||"";';
	$js .= 'var msg=(res.data&&res.data.msg)||"' . $success . '";';
	$js .= 'var done=false;';
	$js .= 'var go=function(){if(done){return;}done=true;';
	$js .= 'if(rd){window.location.href=rd;}else{f.innerHTML="<div class=\'smlife-qo-done\'>"+msg+"</div>";}};';
	$js .= 'if(L&&L.event){window.dataLayer=window.dataLayer||[];';
	$js .= 'window.dataLayer.push({';
	$js .= 'event:L.event,';
	$js .= 'transaction_id:L.id,';
	$js .= 'value:L.value,';
	$js .= 'currency:"VND",';
	$js .= 'lead_source:"form_mua_ngay",';
	$js .= 'qo_phone:L.phone,';
	$js .= 'qo_email:L.email,';
	$js .= 'qo_name:L.name,';
	$js .= 'qo_product:L.product,';
	$js .= 'qo_url:L.url,';
	$js .= 'user_data:{email_address:L.email,phone_number:L.phone,address:{first_name:L.name}},';
	$js .= 'eventCallback:go,eventTimeout:1500';
	$js .= '});';
	$js .= 'setTimeout(go,1600);}else{go();}';
	$js .= 'return;}';
	$js .= 'var d=(res&&res.data)||{};';
	$js .= 'if(box){box.className="qo-alert err";box.style.display="block";box.textContent=d.msg||"Gửi chưa thành công, vui lòng thử lại.";}';
	$js .= 'if(d.n){var nn=byn(f,"smlife_qo_nonce");if(nn){nn.value=d.n;}}';
	$js .= 'if(d.cap){cap(f,d.cap);var ci=byn(f,"qo_cap");if(ci){ci.value="";ci.focus();}}';
	$js .= '}).catch(function(){if(btn){btn.disabled=false;btn.textContent=btn.dataset.t||"GỬI";}if(box){box.className="qo-alert err";box.style.display="block";box.textContent="Có lỗi kết nối, vui lòng thử lại.";}});';
	$js .= '});';
	$js .= '})();</script>';

	return $css . $js;
}

/* ===== So dien thoai dang quoc te E.164 (+84...) =====
 * Google Ads yeu cau dung dinh dang nay cho Chuyen doi nang cao.
 * 0912345678 -> +84912345678 ; 84912345678 -> +84912345678
 */
function smlife_qo_phone_e164( $phone ) {
	$p = preg_replace( '/[^0-9]/', '', (string) $phone );
	if ( '' === $p ) {
		return '';
	}
	if ( 0 === strpos( $p, '0' ) ) {
		$p = '84' . substr( $p, 1 );
	} elseif ( 0 !== strpos( $p, '84' ) ) {
		$p = '84' . $p;
	}
	return '+' . $p;
}

/* ===== Xu ly submit (AJAX) ===== */
add_action( 'wp_ajax_smlife_qo_submit', 'smlife_qo_ajax' );
add_action( 'wp_ajax_nopriv_smlife_qo_submit', 'smlife_qo_ajax' );
/* Cap ma phien va phep tinh moi khi khach bat dau go. Qua admin-ajax nen
   khong nam trong cache: moi khach mot phep tinh rieng, ma phien luon con han. */
add_action( 'wp_ajax_smlife_qo_tok', 'smlife_qo_tok' );
add_action( 'wp_ajax_nopriv_smlife_qo_tok', 'smlife_qo_tok' );
function smlife_qo_tok() {
	nocache_headers();
	wp_send_json_success( array( 'n' => wp_create_nonce( 'smlife_qo' ), 'cap' => smlife_qo_new_captcha() ) );
}

/* Gia tri chuyen doi lay o may chu, khong tin con so trinh duyet gui len.
 * Truoc day ai sua o an qo_price cung bom duoc gia tri chuyen doi ao len
 * Google Ads, lam lech chien luoc gia tROAS. Gia khach gui chi duoc chap nhan
 * neu nam trong khoang gia that cua san pham (bien the re nhat toi dat nhat). */
function smlife_qo_gia_that( $link, $gui_len, $mac_dinh ) {
	$pid = $link ? url_to_postid( $link ) : 0;
	$p   = ( $pid && function_exists( 'wc_get_product' ) ) ? wc_get_product( $pid ) : null;
	if ( ! $p ) {
		return (int) $mac_dinh;
	}
	if ( $p->is_type( 'variable' ) ) {
		$thap = (float) $p->get_variation_price( 'min', true );
		$cao  = (float) $p->get_variation_price( 'max', true );
	} else {
		$thap = $cao = (float) $p->get_price();
	}
	if ( $cao <= 0 ) {
		return (int) $mac_dinh;
	}
	if ( $gui_len >= $thap && $gui_len <= $cao ) {
		return (int) $gui_len;
	}
	return (int) round( $thap );
}

function smlife_qo_ajax() {
	$o = smlife_qo_opt();

	/* Gioi han moi IP 10 lan thu moi gio, tinh ca lan sai. Dat truoc moi kiem
	   tra khac de bot khong the do dap an captcha hay xa thu ra mail. */
	$ip = function_exists( 'smlife_ip' ) ? smlife_ip() : '';
	if ( function_exists( 'smlife_gioi_han' ) && ! smlife_gioi_han( 'qo', $ip, 10, HOUR_IN_SECONDS ) ) {
		wp_send_json_error( array( 'msg' => 'Bạn đã gửi khá nhiều lần. Vui lòng gọi hotline để được hỗ trợ ngay.' ) );
	}

	// Nonce. wp_verify_nonce tra 1 neu ma con tre (nua dau han), 2 neu da qua nua han.
	$nv = isset( $_POST['smlife_qo_nonce'] ) ? wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['smlife_qo_nonce'] ) ), 'smlife_qo' ) : false;
	if ( ! $nv ) {
		smlife_qo_ghi_loi( 'nonce' );
		/* Gui kem ma phien va phep tinh moi: khach bam Gui lai la duoc, khong
		   bat buoc tai lai trang moi gui duoc. */
		$r = array(
			'msg' => 'Phiên làm việc đã hết hạn, vui lòng tải lại trang và gửi lại.',
			'n'   => wp_create_nonce( 'smlife_qo' ),
		);
		if ( ! empty( $o['captcha'] ) ) {
			$r['cap'] = smlife_qo_new_captcha();
		}
		wp_send_json_error( $r );
	}
	if ( 2 === $nv ) {
		smlife_qo_dem_cuu();
	}

	// Honeypot: im lang, coi nhu thanh cong de bot khong biet.
	if ( ! empty( $o['honeypot'] ) && ! empty( $_POST['qo_website'] ) ) {
		wp_send_json_success( array( 'msg' => $o['success_msg'] ) );
	}

	// Captcha: sai thi tra phep tinh moi, giu form.
	if ( ! empty( $o['captcha'] ) ) {
		$ans = isset( $_POST['qo_cap'] ) ? (int) wp_unslash( $_POST['qo_cap'] ) : -1;
		$sig = isset( $_POST['qo_cap_h'] ) ? sanitize_text_field( wp_unslash( $_POST['qo_cap_h'] ) ) : '';
		$ct  = isset( $_POST['qo_cap_t'] ) ? (int) $_POST['qo_cap_t'] : 0;
		$cr  = isset( $_POST['qo_cap_r'] ) ? sanitize_text_field( wp_unslash( $_POST['qo_cap_r'] ) ) : '';
		$kd  = 'smlife_qo_u_' . md5( $sig );
		$het = ( $ct < 1 || ( time() - $ct ) > smlife_qo_han() );
		$da  = ( '' !== $sig && get_transient( $kd ) );
		if ( '' !== $sig ) {
			/* Dot chu ky ngay, du dung hay sai: moi phep tinh chi cho thu mot lan.
			   Giu dau dot bang dung han cua phep tinh de khong dung lai duoc. */
			set_transient( $kd, 1, smlife_qo_han() );
		}
		if ( $het && $ct > 0 ) {
			smlife_qo_ghi_loi( 'captcha_het' );
		}
		if ( $het || $da || ! hash_equals( smlife_qo_sign( $ans, $ct, $cr ), $sig ) ) {
			wp_send_json_error( array(
				'msg' => 'Kết quả chống spam chưa đúng, bạn vui lòng nhập lại.',
				'cap' => smlife_qo_new_captcha(),
			) );
		}
	}

	$name  = isset( $_POST['qo_name'] ) ? sanitize_text_field( wp_unslash( $_POST['qo_name'] ) ) : '';
	$phone = isset( $_POST['qo_phone'] ) ? sanitize_text_field( wp_unslash( $_POST['qo_phone'] ) ) : '';
	if ( '' === $name || '' === $phone ) {
		$r = array( 'msg' => 'Vui lòng nhập đủ Tên và Số điện thoại.' );
		if ( ! empty( $o['captcha'] ) ) {
			$r['cap'] = smlife_qo_new_captcha();
		}
		wp_send_json_error( $r );
	}

	$ref = ! empty( $_POST['qo_link'] ) ? esc_url_raw( wp_unslash( $_POST['qo_link'] ) ) : ( ! empty( $_SERVER['HTTP_REFERER'] ) ? esc_url_raw( wp_unslash( $_SERVER['HTTP_REFERER'] ) ) : home_url( '/' ) );

	// Email khach: neu khong nhap thi ghi ro, tranh Email Center dien gia tri mau cu.
	$cust_email = isset( $_POST['qo_email'] ) ? sanitize_email( wp_unslash( $_POST['qo_email'] ) ) : '';

	$extra = array(
		'ten'     => $name,
		'email'   => is_email( $cust_email ) ? $cust_email : '(khách không nhập email)',
		'sdt'     => $phone,
		'diachi'  => isset( $_POST['qo_address'] ) ? sanitize_text_field( wp_unslash( $_POST['qo_address'] ) ) : '',
		'ghichu'  => isset( $_POST['qo_product'] ) ? sanitize_text_field( wp_unslash( $_POST['qo_product'] ) ) : '',
		'luuy'    => isset( $_POST['qo_note'] ) ? sanitize_text_field( wp_unslash( $_POST['qo_note'] ) ) : '',
		'link_sp' => $ref,
	);

	$headers = array( 'Content-Type: text/html; charset=UTF-8' );

	if ( function_exists( 'smlife_ec_render' ) && function_exists( 'smlife_ec_subject' ) ) {
		// 1) Email noi bo cho shop (mau Dat hang nhanh cua Email Center).
		$to = smlife_qo_notify_to();
		$h  = $headers;
		if ( is_email( $extra['email'] ) ) {
			$h[] = 'Reply-To: ' . $extra['email'];
		}
		wp_mail( $to, smlife_ec_subject( 'quick_order', null, $extra ), smlife_ec_render( 'quick_order', null, $extra ), $h );

		// 2) Email xac nhan cho khach (neu co email hop le).
		/* Moi dia chi toi da 2 thu xac nhan moi ngay. Khong co chot nay, ke xau
		   dien email nguoi la vao form la website gui thu hang loat toi ho,
		   ten mien smlife.vn bi danh dau spam va thu don hang that vao thu rac. */
		if ( is_email( $extra['email'] ) && ( ! function_exists( 'smlife_gioi_han' ) || smlife_gioi_han( 'qo_mail', strtolower( $extra['email'] ), 2, DAY_IN_SECONDS ) ) ) {
			wp_mail( $extra['email'], smlife_ec_subject( 'quick_customer', null, $extra ), smlife_ec_render( 'quick_customer', null, $extra ), $headers );
		}
	} else {
		// Du phong neu chua bat Email Center.
		$body  = 'Đơn Mua ngay mới:<br>Tên: ' . esc_html( $extra['ten'] ) . '<br>SĐT: ' . esc_html( $extra['sdt'] );
		$body .= '<br>Địa chỉ: ' . esc_html( $extra['diachi'] ) . '<br>Sản phẩm: ' . esc_html( $extra['ghichu'] );
		$body .= '<br>Ghi chú: ' . esc_html( $extra['luuy'] ) . '<br>Email KH: ' . esc_html( $extra['email'] );
		$body .= '<br>Link: ' . esc_html( $extra['link_sp'] );
		wp_mail( smlife_qo_notify_to(), 'Đơn Mua ngay mới', $body, $headers );
	}

	$resp = array( 'msg' => $o['success_msg'] );
	if ( ! empty( $o['redirect_url'] ) ) {
		$resp['redirect'] = $o['redirect_url'];
	}

	/* Du lieu cho Google Tag Manager / Google Ads.
	 * Chi tra khi don di qua het cac buoc kiem tra that, nen bot bi honeypot
	 * hay sai captcha deu khong tao duoc chuyen doi ao. */
	if ( ! empty( $o['gtm_on'] ) ) {
		/* Gia tri chuyen doi: uu tien gia that cua san pham khach dang xem,
		 * khong co thi lay con so co dinh trong cau hinh. */
		$gia_sp    = isset( $_POST['qo_price'] ) ? absint( preg_replace( '/[^0-9]/', '', (string) wp_unslash( $_POST['qo_price'] ) ) ) : 0;
		$gtm_value = smlife_qo_gia_that( $ref, $gia_sp, (int) $o['gtm_value'] );

		$resp['lead'] = array(
			'id'      => 'QO-' . wp_date( 'Ymd-His' ) . '-' . wp_rand( 100, 999 ),
			'event'   => '' !== trim( (string) $o['gtm_event'] ) ? sanitize_key( $o['gtm_event'] ) : 'form_muangay',
			'value'   => $gtm_value,
			'phone'   => smlife_qo_phone_e164( $extra['sdt'] ),
			'email'   => is_email( $extra['email'] ) ? strtolower( trim( $extra['email'] ) ) : '',
			'name'    => $extra['ten'],
			'product' => $extra['ghichu'],
			'url'     => $extra['link_sp'],
		);
	}

	wp_send_json_success( $resp );
}

/* ===== Submenu: SMLIFE Core > Form Mua ngay ===== */
add_action( 'admin_menu', function () {
	add_submenu_page(
		'smlife-core',
		'Form Mua ngay',
		'Form Mua ngay',
		'manage_options',
		'smlife-form-mua-ngay',
		'smlife_qo_page'
	);
}, 24 );

function smlife_qo_field( $o, $k, $ph = '' ) {
	return '<input type="text" name="qo[' . esc_attr( $k ) . ']" value="' . esc_attr( $o[ $k ] ) . '" class="regular-text" placeholder="' . esc_attr( $ph ) . '" />';
}

function smlife_qo_page() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}
	$o = smlife_qo_opt();
	echo '<div class="wrap smlife-wrap">';
	echo '<div class="smc-hd"><h1>Form Mua ngay</h1>';
	echo '<p>Nội dung và chống spam cho form đặt hàng nhanh hiện trong popup Mua ngay.</p></div>';
	echo '<p>Form này thay cho Contact Form 7. Nhúng vào bất kỳ đâu bằng shortcode <code>[smlife_quick_order]</code> - popup Mua ngay đã tự nhúng sẵn, không cần đặt thêm. Đặt trong trang sản phẩm thì tên sản phẩm, link và giá tự điền, không cần khách mở popup.</p>';
	if ( isset( $_GET['updated'] ) ) {
		echo '<div class="notice notice-success is-dismissible"><p>Đã lưu.</p></div>';
	}
	echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '">';
	echo '<input type="hidden" name="action" value="smlife_qo_save" />';
	wp_nonce_field( 'smlife_qo_save', 'smlife_qo_nonce_f' );

	echo '<div class="smlife-card">';
	echo '<h2>Nội dung form</h2>';
	echo '<table class="form-table" role="presentation">';
	echo '<tr><th scope="row">Tiêu đề</th><td>' . smlife_qo_field( $o, 'heading' ) . '</td></tr>';
	echo '<tr><th scope="row">Nhãn + gợi ý: Tên</th><td>' . smlife_qo_field( $o, 'l_name' ) . '<br>' . smlife_qo_field( $o, 'ph_name' ) . '</td></tr>';
	echo '<tr><th scope="row">Nhãn + gợi ý: SĐT</th><td>' . smlife_qo_field( $o, 'l_phone' ) . '<br>' . smlife_qo_field( $o, 'ph_phone' ) . '</td></tr>';
	echo '<tr><th scope="row">Nhãn + gợi ý: Địa chỉ</th><td>' . smlife_qo_field( $o, 'l_address' ) . '<br>' . smlife_qo_field( $o, 'ph_address' ) . '</td></tr>';
	echo '<tr><th scope="row">Nhãn + gợi ý: Lưu ý</th><td>' . smlife_qo_field( $o, 'l_note' ) . '<br>' . smlife_qo_field( $o, 'ph_note' ) . '</td></tr>';
	echo '<tr><th scope="row">Trường Email</th><td><label><input type="checkbox" name="qo[show_email]" value="1" ' . checked( 1, $o['show_email'], false ) . ' /> Hiển thị trường Email trong form</label><br>' . smlife_qo_field( $o, 'l_email' ) . '<br>' . smlife_qo_field( $o, 'ph_email' ) . '</td></tr>';
	echo '<tr><th scope="row">Chữ nút gửi</th><td>' . smlife_qo_field( $o, 'btn' ) . '</td></tr>';
	echo '<tr><th scope="row">Màu nút gửi</th><td><input type="color" name="qo[btn_color]" value="' . esc_attr( $o['btn_color'] ) . '" /></td></tr>';
	echo '</table>';
	echo '</div>';

	echo '<div class="smlife-card">';
	echo '<h2>Chống spam và sau khi gửi</h2>';
	echo '<table class="form-table" role="presentation">';
	echo '<tr><th scope="row">Honeypot</th><td><label><input type="checkbox" name="qo[honeypot]" value="1" ' . checked( 1, $o['honeypot'], false ) . ' /> Bật bẫy honeypot (ẩn, chặn bot điền tự động)</label></td></tr>';
	echo '<tr><th scope="row">Math captcha</th><td><label><input type="checkbox" name="qo[captcha]" value="1" ' . checked( 1, $o['captcha'], false ) . ' /> Bắt khách giải phép cộng đơn giản</label></td></tr>';
	echo '<tr><th scope="row">Email nhận đơn</th><td>' . smlife_qo_field( $o, 'notify_to', 'Để trống = lấy theo Email Setting' ) . '<br><span class="description">Email nội bộ nhận đơn Mua ngay. Để trống sẽ dùng cấu hình Email Setting.</span></td></tr>';
	echo '<tr><th scope="row">Thông báo thành công</th><td><input type="text" name="qo[success_msg]" value="' . esc_attr( $o['success_msg'] ) . '" class="large-text" /></td></tr>';
	echo '<tr><th scope="row">Redirect sau khi gửi</th><td>' . smlife_qo_field( $o, 'redirect_url', 'Để trống = hiện thông báo tại chỗ' ) . '<br><span class="description">Nhập URL nếu muốn chuyển trang sau khi gửi thành công (vd: trang cảm ơn).</span></td></tr>';
	echo '</table>';
	echo '</div>';

	echo '<div class="smlife-card">';
	echo '<h2>Theo dõi chuyển đổi (Google Tag Manager)</h2>';
	echo '<table class="form-table" role="presentation">';
	echo '<tr><th scope="row">Bắn sự kiện chuyển đổi</th><td><label><input type="checkbox" name="qo[gtm_on]" value="1" ' . checked( 1, $o['gtm_on'], false ) . ' /> Đẩy sự kiện lên dataLayer mỗi khi có đơn gửi thành công</label><p class="description">Chỉ bắn khi đơn đi qua hết bước kiểm tra thật, nên bot dính honeypot hoặc sai captcha không tạo được chuyển đổi ảo.</p></td></tr>';
	echo '<tr><th scope="row">Tên sự kiện</th><td><input type="text" name="qo[gtm_event]" value="' . esc_attr( $o['gtm_event'] ) . '" class="regular-text" placeholder="form_muangay" /><p class="description">Mặc định <code>form_muangay</code> - đúng tên mà trigger &quot;Click - Mua Ngay&quot; bên GTM đang nghe, nên không phải sửa gì trong GTM. Chỉ dùng chữ thường, số và dấu gạch dưới.</p></td></tr>';
	echo '<tr><th scope="row">Giá trị quy đổi (VND)</th><td><input type="number" min="0" step="1000" name="qo[gtm_value]" value="' . esc_attr( $o['gtm_value'] ) . '" class="regular-text" /><p class="description">Chỉ dùng khi không đọc được giá trên trang. Bình thường hệ thống tự lấy đúng giá sản phẩm khách đang xem, kể cả giá của biến thể đã chọn.</p></td></tr>';
	echo '</table>';
	echo '<div class="smlife-note" style="margin:0 16px 16px"><b>Dữ liệu đẩy lên dataLayer:</b><ul>';
	echo '<li><code>event</code> - tên sự kiện ở trên</li>';
	echo '<li><code>transaction_id</code> - mã đơn duy nhất, giúp Google chống đếm trùng</li>';
	echo '<li><code>value</code> và <code>currency</code> (VND)</li>';
	echo '<li><code>qo_phone</code> - số điện thoại dạng quốc tế +84..., dùng cho Chuyển đổi nâng cao</li>';
	echo '<li><code>qo_email</code>, <code>qo_name</code>, <code>qo_product</code>, <code>qo_url</code></li>';
	echo '<li><code>user_data</code> - khối gộp sẵn theo chuẩn của Google (email_address, phone_number, address.first_name)</li>';
	echo '</ul></div>';
	echo '</div>';

	submit_button( 'Lưu cấu hình' );
	echo '</form>';

	/* --- Theo doi het phien --- */
	global $wp_version;
	$d    = get_option( 'smlife_qo_loi', array() );
	$d    = is_array( $d ) ? $d : array();
	$dem  = isset( $d['dem'] ) && is_array( $d['dem'] ) ? $d['dem'] : array();
	$ds   = isset( $d['ds'] ) && is_array( $d['ds'] ) ? $d['ds'] : array();
	$moi  = version_compare( (string) $wp_version, '6.1', '>=' );
	echo '<div class="smlife-card"><h2>Theo dõi hết phiên</h2>';
	echo '<table class="form-table" role="presentation">';
	echo '<tr><th scope="row">Hạn của form</th><td>' . ( $moi ? '<b style="color:#16a34a">48 giờ</b> - WordPress ' . esc_html( $wp_version ) . ' hỗ trợ hạn riêng cho từng loại mã.' : '<b style="color:#b91c1c">Chưa áp dụng</b> - WordPress ' . esc_html( $wp_version ) . ' cũ hơn 6.1, mã vẫn theo hạn mặc định 24 giờ. Cập nhật WordPress để dùng.' ) . '</td></tr>';
	echo '<tr><th scope="row">Bị từ chối vì hết phiên</th><td>' . (int) ( isset( $dem['nonce'] ) ? $dem['nonce'] : 0 ) . ' lần</td></tr>';
	echo '<tr><th scope="row">Phép tính chống spam quá hạn</th><td>' . (int) ( isset( $dem['captcha_het'] ) ? $dem['captcha_het'] : 0 ) . ' lần</td></tr>';
	echo '<tr><th scope="row">Đơn cứu được nhờ hạn 48 giờ</th><td>' . (int) ( isset( $d['cuu'] ) ? $d['cuu'] : 0 ) . ' đơn <span class="description">(mã đã quá 24 giờ, bản cũ sẽ từ chối)</span></td></tr>';
	echo '</table>';
	if ( ! empty( $ds ) ) {
		echo '<table class="widefat striped" style="margin:0 16px 16px;width:calc(100% - 32px)"><thead><tr><th style="width:150px">Thời điểm</th><th style="width:110px">Loại</th><th style="width:130px">IP</th><th>Trang</th></tr></thead><tbody>';
		foreach ( $ds as $r ) {
			echo '<tr><td>' . esc_html( wp_date( 'H:i d/m/Y', (int) $r['t'] ) ) . '</td><td>' . ( 'nonce' === $r['loai'] ? 'Hết phiên' : 'Captcha quá hạn' ) . '</td><td>' . esc_html( $r['ip'] ) . '</td><td style="word-break:break-all">' . esc_html( $r['url'] ) . '</td></tr>';
		}
		echo '</tbody></table>';
	}
	echo '</div>';
	echo '</div>';
}

add_action( 'admin_post_smlife_qo_save', function () {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( 'Không đủ quyền.' );
	}
	check_admin_referer( 'smlife_qo_save', 'smlife_qo_nonce_f' );
	$in  = isset( $_POST['qo'] ) && is_array( $_POST['qo'] ) ? wp_unslash( $_POST['qo'] ) : array();
	$txt = function ( $k, $d ) use ( $in ) {
		return isset( $in[ $k ] ) ? sanitize_text_field( $in[ $k ] ) : $d;
	};
	$def = smlife_qo_opt();
	$new = array(
		'heading'     => $txt( 'heading', $def['heading'] ),
		'l_name'      => $txt( 'l_name', $def['l_name'] ),
		'ph_name'     => $txt( 'ph_name', $def['ph_name'] ),
		'l_phone'     => $txt( 'l_phone', $def['l_phone'] ),
		'ph_phone'    => $txt( 'ph_phone', $def['ph_phone'] ),
		'l_address'   => $txt( 'l_address', $def['l_address'] ),
		'ph_address'  => $txt( 'ph_address', $def['ph_address'] ),
		'l_note'      => $txt( 'l_note', $def['l_note'] ),
		'ph_note'     => $txt( 'ph_note', $def['ph_note'] ),
		'show_email'  => empty( $in['show_email'] ) ? 0 : 1,
		'l_email'     => $txt( 'l_email', $def['l_email'] ),
		'ph_email'    => $txt( 'ph_email', $def['ph_email'] ),
		'btn'         => $txt( 'btn', $def['btn'] ),
		'btn_color'   => sanitize_hex_color( isset( $in['btn_color'] ) ? $in['btn_color'] : '' ) ? sanitize_hex_color( $in['btn_color'] ) : $def['btn_color'],
		'honeypot'    => empty( $in['honeypot'] ) ? 0 : 1,
		'captcha'     => empty( $in['captcha'] ) ? 0 : 1,
		'success_msg' => isset( $in['success_msg'] ) ? sanitize_text_field( $in['success_msg'] ) : $def['success_msg'],
		'redirect_url'=> isset( $in['redirect_url'] ) ? esc_url_raw( $in['redirect_url'] ) : '',
		'notify_to'   => isset( $in['notify_to'] ) && is_email( $in['notify_to'] ) ? sanitize_email( $in['notify_to'] ) : '',
		'gtm_on'      => empty( $in['gtm_on'] ) ? 0 : 1,
		'gtm_event'   => isset( $in['gtm_event'] ) && '' !== trim( (string) $in['gtm_event'] ) ? sanitize_key( $in['gtm_event'] ) : 'form_muangay',
		'gtm_value'   => isset( $in['gtm_value'] ) ? max( 0, absint( $in['gtm_value'] ) ) : 0,
	);
	update_option( 'smlife_qo', $new );
	wp_safe_redirect( admin_url( 'admin.php?page=smlife-form-mua-ngay&updated=1' ) );
	exit;
} );
