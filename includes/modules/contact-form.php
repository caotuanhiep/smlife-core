<?php
/**
 * SMLIFE Core - Module: Form lien he
 *
 * Mot form lien he gon nhe cho cac trang contact. Khong dung plugin ngoai,
 * khong tai them thu vien, gui bang AJAX nen khong tai lai trang.
 *
 * Dat vao trang bang shortcode [smlife_lien_he]
 * Thuoc tinh tuy chon:
 *   tieu_de="..."  doi tieu de rieng cho trang do
 *   nut="..."      doi chu tren nut gui
 *   chu_de="A|B|C" doi danh sach chu de rieng cho trang do
 *
 * Chong spam 4 lop, khong can captcha:
 *   1. Nonce cua WordPress
 *   2. O moi nhu (honeypot) - bot dien vao la chan
 *   3. Bay thoi gian - gui duoi 3 giay la bot
 *   4. Gioi han 5 lan moi gio cho mot IP
 *
 * Lien he gui ve luu trong Bai viet dang "Liên hệ" de xem lai, dong thoi
 * gui mail bao ve hop thu cau hinh san. Mail dat Reply-To la email khach
 * nen bam Tra loi la tra lai dung khach.
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

/* ===== 1. Cai dat ===== */

function smlife_lh_defaults() {
	return array(
		'enabled' => 1,
		'tieu_de' => 'Gửi yêu cầu tư vấn',
		'mo_ta'   => 'Để lại thông tin, SMLIFE gọi lại trong giờ làm việc.',
		'nut'     => 'Gửi yêu cầu',
		'email'   => get_option( 'admin_email' ),
		'cc'      => '',
		'tieu_mail' => '[SMLIFE] Liên hệ mới từ website',
		'cam_on'  => 'Đã nhận được thông tin. SMLIFE sẽ liên hệ lại với bạn sớm nhất.',
		'chu_de'  => "Tư vấn sản phẩm\nBáo giá dự án, số lượng lớn\nBảo hành, đổi trả\nHợp tác, đại lý\nKhác",
		'bat_email' => 1,
	);
}

function smlife_lh_opt() {
	$o = get_option( 'smlife_lh', array() );
	return is_array( $o ) ? array_merge( smlife_lh_defaults(), $o ) : smlife_lh_defaults();
}

function smlife_lh_topics( $raw = null ) {
	$o   = smlife_lh_opt();
	$raw = ( null === $raw ) ? $o['chu_de'] : $raw;
	$raw = str_replace( '|', "\n", (string) $raw );
	$out = array();
	foreach ( explode( "\n", $raw ) as $x ) {
		$x = trim( $x );
		if ( '' !== $x ) { $out[] = $x; }
		if ( count( $out ) >= 12 ) { break; }
	}
	return $out;
}

/* ===== 2. (Da bo) Khu vuc quan ly lien he gui ve =====
 * Truoc day moi lien he con duoc luu thanh mot bai viet rieng de xem lai
 * trong admin. Bo di theo yeu cau: lien he chi gui thang ve hop thu, khong
 * luu lai trong website nua, admin cung gon bot mot muc.
 */

/* ===== 3. Shortcode ===== */

add_shortcode( 'smlife_lien_he', 'smlife_lh_sc' );
function smlife_lh_sc( $atts ) {
	$o = smlife_lh_opt();
	if ( empty( $o['enabled'] ) ) { return ''; }
	$a = shortcode_atts( array(
		'tieu_de' => '',
		'mo_ta'   => '',
		'nut'     => '',
		'chu_de'  => '',
	), $atts, 'smlife_lien_he' );

	$tieu_de = '' !== trim( $a['tieu_de'] ) ? $a['tieu_de'] : $o['tieu_de'];
	$mo_ta   = '' !== trim( $a['mo_ta'] ) ? $a['mo_ta'] : $o['mo_ta'];
	$nut     = '' !== trim( $a['nut'] ) ? $a['nut'] : $o['nut'];
	$topics  = smlife_lh_topics( '' !== trim( $a['chu_de'] ) ? $a['chu_de'] : null );
	$uid     = 'smlh' . wp_rand( 1000, 9999 );
	$GLOBALS['smlife_lh_need'] = true;

	ob_start();
	?>
	<form class="smlife-lh" id="<?php echo esc_attr( $uid ); ?>" method="post" novalidate>
		<?php if ( '' !== trim( (string) $tieu_de ) ) : ?>
			<div class="smlife-lh-t"><?php echo esc_html( $tieu_de ); ?></div>
		<?php endif; ?>
		<?php if ( '' !== trim( (string) $mo_ta ) ) : ?>
			<div class="smlife-lh-m"><?php echo esc_html( $mo_ta ); ?></div>
		<?php endif; ?>

		<div class="smlife-lh-g">
			<label class="smlife-lh-f">
				<span>Họ và tên <i>*</i></span>
				<input type="text" name="lh_ten" maxlength="80" required placeholder="Nguyễn Văn A" />
			</label>
			<label class="smlife-lh-f">
				<span>Số điện thoại <i>*</i></span>
				<input type="tel" name="lh_phone" maxlength="20" required inputmode="tel" placeholder="09xx xxx xxx" />
			</label>
		</div>

		<div class="smlife-lh-g">
			<label class="smlife-lh-f">
				<span>Email</span>
				<input type="email" name="lh_mail" maxlength="100" placeholder="email@example.com" />
			</label>
			<?php if ( $topics ) : ?>
			<label class="smlife-lh-f">
				<span>Chủ đề</span>
				<select name="lh_topic">
					<?php foreach ( $topics as $t ) : ?>
						<option value="<?php echo esc_attr( $t ); ?>"><?php echo esc_html( $t ); ?></option>
					<?php endforeach; ?>
				</select>
			</label>
			<?php endif; ?>
		</div>

		<label class="smlife-lh-f">
			<span>Nội dung cần hỗ trợ <i>*</i></span>
			<textarea name="lh_msg" rows="4" maxlength="2000" required placeholder="Mô tả ngắn nhu cầu, kích thước, ngân sách..."></textarea>
		</label>

		<div class="smlife-lh-hp" aria-hidden="true">
			<label>Website<input type="text" name="lh_web" tabindex="-1" autocomplete="off" /></label>
		</div>
		<input type="hidden" name="lh_t0" value="" />
		<input type="hidden" name="lh_ts" value="" />
		<input type="hidden" name="lh_from" value="<?php echo esc_attr( home_url( add_query_arg( null, null ) ) ); ?>" />
		<?php wp_nonce_field( 'smlife_lh_send', 'lh_nonce' ); ?>

		<button type="submit" class="smlife-lh-btn"><?php echo esc_html( $nut ); ?></button>
		<div class="smlife-lh-kq" role="status"></div>
	</form>
	<?php
	return trim( preg_replace( '/\n\s*/', "\n", ob_get_clean() ) );
}

/* ===== 4. CSS + JS ===== */

add_action( 'wp_footer', function () {
	if ( ! isset( $GLOBALS['smlife_lh_need'] ) ) {
		global $post;
		$has = ( $post instanceof WP_Post ) && has_shortcode( (string) $post->post_content, 'smlife_lien_he' );
		if ( ! $has ) { return; }
	}
	$mau = smlife_mau();
	$dam = smlife_mau_dam();
	$c  = '.smlife-lh{border:1px solid #ececec;border-radius:12px;background:#fff;padding:20px 22px;margin:18px 0;max-width:680px}';
	$c .= '.smlife-lh-t{font-size:19px;font-weight:700;color:#4a4a4a;line-height:1.3;margin:0 0 4px}';
	$c .= '.smlife-lh-m{font-size:13.5px;color:#777;line-height:1.5;margin:0 0 16px}';
	$c .= '.smlife-lh-g{display:flex;gap:14px;flex-wrap:wrap}';
	$c .= '.smlife-lh-g>.smlife-lh-f{flex:1 1 220px;min-width:0}';
	$c .= '.smlife-lh-f{display:block;margin:0 0 14px}';
	$c .= '.smlife-lh-f>span{display:block;font-size:13px;font-weight:600;color:#4a4a4a;margin:0 0 5px}';
	$c .= '.smlife-lh-f>span i{color:' . $mau . ';font-style:normal}';
	$c .= '.smlife-lh input,.smlife-lh select,.smlife-lh textarea{width:100%;margin:0;padding:10px 12px;font-family:inherit;font-size:14.5px;color:#4a4a4a;';
	$c .= 'border:1px solid #e2e2e2;border-radius:8px;background:#fff;box-shadow:none;height:auto;min-height:0}';
	$c .= '.smlife-lh textarea{resize:vertical;line-height:1.5}';
	$c .= '.smlife-lh input:focus,.smlife-lh select:focus,.smlife-lh textarea:focus{border-color:' . $mau . ';outline:0;box-shadow:0 0 0 3px ' . smlife_mau_rgba( 0.09 ) . '}';
	$c .= '.smlife-lh .loi{border-color:#e0325c;background:#fff8fa}';
	$c .= '.smlife-lh-hp{position:absolute;left:-9999px;width:1px;height:1px;overflow:hidden}';
	$c .= '.smlife-lh-btn{background:' . $mau . ';color:#fff;border:0;border-radius:8px;font-size:15px;font-weight:700;line-height:1;';
	$c .= 'padding:14px 30px;margin:2px 0 0;cursor:pointer;min-height:0;transition:background .15s}';
	$c .= '.smlife-lh-btn:hover{background:' . $dam . ';color:#fff}';
	$c .= '.smlife-lh-btn[disabled]{opacity:.6;cursor:default}';
	$c .= '.smlife-lh-kq{font-size:14px;line-height:1.5;margin:12px 0 0;display:none}';
	$c .= '.smlife-lh-kq.hien{display:block}';
	$c .= '.smlife-lh-kq.ok{color:#15803d;background:#f0fdf4;border:1px solid #bbf7d0;border-radius:8px;padding:11px 13px}';
	$c .= '.smlife-lh-kq.er{color:#b3253f;background:#fff5f7;border:1px solid #f8c7d4;border-radius:8px;padding:11px 13px}';
	$c .= '@media (max-width:549px){.smlife-lh{padding:16px 14px}.smlife-lh-g{gap:0}.smlife-lh-t{font-size:17px}}';
	echo '<style id="smlife-lh-css">' . $c . '</style>'; // phpcs:ignore

	$js  = '(function(){';
	$js .= 'var U=' . wp_json_encode( admin_url( 'admin-ajax.php' ) ) . ';';
	/* Ma phien va moc thoi gian lay tu may chu luc khach bat dau go.
	   Neu in san vao trang thi WP Rocket dong bang no trong cache hang gio,
	   ma phien het han la khach that bam Gui khong duoc. */
	$js .= 'function tok(f,cb){if(f.getAttribute("data-tok")==="1"){cb();return;}';
	$js .= 'var q=new FormData();q.append("action","smlife_lh_tok");';
	$js .= 'fetch(U,{method:"POST",body:q,credentials:"same-origin"}).then(function(r){return r.json();}).then(function(d){';
	$js .= 'if(d&&d.success){f.elements["lh_nonce"].value=d.data.n;f.elements["lh_t0"].value=d.data.t;if(f.elements["lh_ts"])f.elements["lh_ts"].value=d.data.s||"";f.setAttribute("data-tok","1");}cb();';
	$js .= '}).catch(function(){cb();});}';
	$js .= 'document.addEventListener("focusin",function(e){var f=e.target&&e.target.closest?e.target.closest("form.smlife-lh"):null;if(f)tok(f,function(){});});';
	$js .= 'document.addEventListener("submit",function(e){';
	$js .= 'var f=e.target;if(!f.classList||!f.classList.contains("smlife-lh"))return;e.preventDefault();';
	$js .= 'var kq=f.querySelector(".smlife-lh-kq"),btn=f.querySelector(".smlife-lh-btn");';
	$js .= 'f.querySelectorAll(".loi").forEach(function(x){x.classList.remove("loi");});';
	$js .= 'var bad=null;["lh_ten","lh_phone","lh_msg"].forEach(function(n){var el=f.elements[n];if(el&&!el.value.trim()){el.classList.add("loi");if(!bad)bad=el;}});';
	$js .= 'var ph=f.elements["lh_phone"];if(ph&&ph.value.replace(/[^0-9]/g,"").length<9){ph.classList.add("loi");if(!bad)bad=ph;}';
	$js .= 'if(bad){kq.className="smlife-lh-kq hien er";kq.textContent="Vui lòng điền đủ họ tên, số điện thoại hợp lệ và nội dung.";bad.focus();return;}';
	$js .= 'var lb=btn.textContent;btn.disabled=true;btn.textContent="Đang gửi...";kq.className="smlife-lh-kq";';
	$js .= 'tok(f,function(){var fd=new FormData(f);fd.append("action","smlife_lh_send");';
	$js .= 'fetch(U,{method:"POST",body:fd,credentials:"same-origin"}).then(function(r){return r.json();}).then(function(d){';
	$js .= 'btn.disabled=false;btn.textContent=lb;';
	$js .= 'if(d&&d.success){kq.className="smlife-lh-kq hien ok";kq.textContent=d.data;f.reset();f.removeAttribute("data-tok");}';
	$js .= 'else{kq.className="smlife-lh-kq hien er";kq.textContent=(d&&d.data)?d.data:"Gửi chưa được, vui lòng thử lại.";}';
	$js .= '}).catch(function(){btn.disabled=false;btn.textContent=lb;kq.className="smlife-lh-kq hien er";kq.textContent="Mạng đang chậm, vui lòng thử lại.";});});';
	$js .= '});})();';
	echo '<script id="smlife-lh-js">' . $js . '</script>'; // phpcs:ignore
}, 95 );

/* ===== 5. Nhan form ===== */

/* Cap ma phien moi. Goi qua admin-ajax nen khong bao gio nam trong cache. */
add_action( 'wp_ajax_smlife_lh_tok', 'smlife_lh_tok' );
add_action( 'wp_ajax_nopriv_smlife_lh_tok', 'smlife_lh_tok' );
function smlife_lh_tok() {
	nocache_headers();
	$t = time();
	/* 1.24.0: ky thoi diem cap ma bang khoa bi mat cua website. Truoc day lh_t0 do
	   trinh duyet gui len, bot gui lh_t0=1 la qua kiem tra 3 giay. */
	wp_send_json_success( array( 'n' => wp_create_nonce( 'smlife_lh_send' ), 't' => $t, 's' => smlife_lh_ky( $t ) ) );
}
function smlife_lh_ky( $t ) {
	return substr( hash_hmac( 'sha256', 'smlife_lh|' . (int) $t, wp_salt( 'nonce' ) ), 0, 20 );
}

add_action( 'wp_ajax_smlife_lh_send', 'smlife_lh_recv' );
add_action( 'wp_ajax_nopriv_smlife_lh_send', 'smlife_lh_recv' );
function smlife_lh_recv() {
	$o = smlife_lh_opt();
	if ( empty( $o['enabled'] ) ) {
		wp_send_json_error( 'Form đang tạm khoá.' );
	}
	if ( ! isset( $_POST['lh_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['lh_nonce'] ) ), 'smlife_lh_send' ) ) {
		wp_send_json_error( 'Phiên đã hết hạn, vui lòng tải lại trang rồi gửi lại.' );
	}
	/* Lop chong spam */
	if ( ! empty( $_POST['lh_web'] ) ) {
		wp_send_json_error( 'Gửi chưa được, vui lòng thử lại.' );
	}
	$t0 = isset( $_POST['lh_t0'] ) ? (int) $_POST['lh_t0'] : 0;
	$ts = isset( $_POST['lh_ts'] ) ? sanitize_text_field( wp_unslash( $_POST['lh_ts'] ) ) : '';
	if ( $t0 < 1 || ! hash_equals( smlife_lh_ky( $t0 ), $ts ) || ( time() - $t0 ) > 2 * DAY_IN_SECONDS ) {
		wp_send_json_error( 'Phiên đã hết hạn, vui lòng tải lại trang rồi gửi lại.' );
	}
	/* 1.24.0: tran tong moi ngay luu thang DB (transient nam trong Redis, bi xoa moi
	   dem va mat khi Redis day). Bot doi IP lien tuc van khong lam can han muc
	   gui mail cua tai khoan SMTP (dung chung voi email don hang). */
	$dem = smlife_opt_db( 'smlife_lh_dem', array() );
	$hn  = wp_date( 'Y-m-d' );
	$dem = ( is_array( $dem ) && isset( $dem['ngay'] ) && $hn === $dem['ngay'] ) ? $dem : array( 'ngay' => $hn, 'n' => 0 );
	if ( (int) $dem['n'] >= (int) apply_filters( 'smlife_lh_tran_ngay', 60 ) ) {
		wp_send_json_error( 'Hệ thống đang nhận quá nhiều yêu cầu. Vui lòng gọi hotline để được hỗ trợ ngay.' );
	}
	if ( ( time() - $t0 ) < 3 ) {
		wp_send_json_error( 'Bạn gửi hơi nhanh, vui lòng thử lại sau vài giây.' );
	}
	$ip  = function_exists( 'smlife_ip' ) ? smlife_ip() : ( isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '0' );
	$key = 'smlife_lh_' . md5( $ip );
	$n   = (int) get_transient( $key );
	if ( $n >= 5 ) {
		wp_send_json_error( 'Bạn đã gửi khá nhiều lần. Vui lòng gọi hotline để được hỗ trợ ngay.' );
	}

	$ten   = isset( $_POST['lh_ten'] ) ? sanitize_text_field( wp_unslash( $_POST['lh_ten'] ) ) : '';
	$phone = isset( $_POST['lh_phone'] ) ? sanitize_text_field( wp_unslash( $_POST['lh_phone'] ) ) : '';
	$mail  = isset( $_POST['lh_mail'] ) ? sanitize_email( wp_unslash( $_POST['lh_mail'] ) ) : '';
	$topic = isset( $_POST['lh_topic'] ) ? sanitize_text_field( wp_unslash( $_POST['lh_topic'] ) ) : '';
	$msg   = isset( $_POST['lh_msg'] ) ? sanitize_textarea_field( wp_unslash( $_POST['lh_msg'] ) ) : '';
	$from  = isset( $_POST['lh_from'] ) ? esc_url_raw( wp_unslash( $_POST['lh_from'] ) ) : '';

	if ( '' === $ten || '' === $msg || strlen( preg_replace( '/[^0-9]/', '', $phone ) ) < 9 ) {
		wp_send_json_error( 'Vui lòng điền đủ họ tên, số điện thoại hợp lệ và nội dung.' );
	}

	set_transient( $key, $n + 1, HOUR_IN_SECONDS );
	$dem['n'] = (int) $dem['n'] + 1;
	update_option( 'smlife_lh_dem', $dem, false );

	/* Gui mail bao. Uu tien dung mau cua Email Setting de thu khach nhan
	 * duoc trong cung mot bo khung voi cac email khac cua website. Email
	 * Setting tat thi tu quay ve ban don gian. */
	if ( ! empty( $o['bat_email'] ) ) {
		$to = array();
		foreach ( explode( ',', $o['email'] . ',' . $o['cc'] ) as $e ) {
			$e = sanitize_email( trim( $e ) );
			if ( $e && ! in_array( $e, $to, true ) ) { $to[] = $e; }
		}
		if ( $to ) {
			$tieu = $o['tieu_mail'] . ' - ' . $ten;
			$than = '';
			if ( function_exists( 'smlife_ec_render' ) && function_exists( 'smlife_ec_email_cfg' ) ) {
				$cfg = smlife_ec_email_cfg( 'lien_he' );
				if ( $cfg && ! empty( $cfg['enabled'] ) ) {
					$extra = array(
						'ten'      => $ten,
						'email'    => $mail,
						'sdt'      => $phone,
						'noi_dung' => nl2br( esc_html( $msg ) ),
						'chu_de'   => $topic ? $topic : 'Không chọn',
						'trang'    => $from,
					);
					$than = smlife_ec_render( 'lien_he', null, $extra );
					if ( '' !== trim( (string) $cfg['subject'] ) && function_exists( 'smlife_ec_apply_tokens' ) ) {
						$dd   = smlife_ec_order_data( null, $extra );
						$tieu = smlife_ec_apply_tokens( $cfg['subject'], $dd );
					}
				}
			}
			if ( '' === trim( $than ) ) {
				$than  = '<p><b>Họ tên:</b> ' . esc_html( $ten ) . '</p>';
				$than .= '<p><b>Điện thoại:</b> ' . esc_html( $phone ) . '</p>';
				if ( $mail )  { $than .= '<p><b>Email:</b> ' . esc_html( $mail ) . '</p>'; }
				if ( $topic ) { $than .= '<p><b>Chủ đề:</b> ' . esc_html( $topic ) . '</p>'; }
				$than .= '<p><b>Nội dung:</b><br />' . nl2br( esc_html( $msg ) ) . '</p>';
				if ( $from ) { $than .= '<p><b>Gửi từ trang:</b> <a href="' . esc_url( $from ) . '">' . esc_html( $from ) . '</a></p>'; }
			}
			$h = array( 'Content-Type: text/html; charset=UTF-8' );
			if ( $mail ) { $h[] = 'Reply-To: "' . str_replace( array( '"', '<', '>', ',', ';' ), '', $ten ) . '" <' . $mail . '>'; }
			wp_mail( $to, $tieu, $than, $h );
		}
	}

	wp_send_json_success( $o['cam_on'] );
}

/* ===== 6. Trang cai dat ===== */

add_action( 'admin_menu', function () {
	add_submenu_page( 'smlife-core', 'Form liên hệ', 'Form liên hệ', 'manage_options', 'smlife-form-lien-he', 'smlife_lh_page' );
}, 50 );

function smlife_lh_page() {
	if ( ! current_user_can( 'manage_options' ) ) { return; }
	$o = smlife_lh_opt();
	echo '<div class="wrap smlife-wrap">';
	echo '<div class="smc-hd"><h1>Form liên hệ</h1>';
	echo '<p>Dán <code>[smlife_lien_he]</code> vào trang Liên hệ hoặc bất kỳ trang nào cần form. Liên hệ gửi thẳng về hộp thư cấu hình bên dưới, dùng chung bộ khung email với <b>Email Setting</b>.</p></div>';
	if ( isset( $_GET['updated'] ) ) { echo '<div class="notice notice-success is-dismissible"><p>Đã lưu.</p></div>'; }

	echo '<div class="smlife-card"><form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '">';
	echo '<input type="hidden" name="action" value="smlife_lh_save" />';
	wp_nonce_field( 'smlife_lh_save', 'smlife_lh_nonce' );
	echo '<table class="form-table">';
	echo '<tr><th>Bật form</th><td><label><input type="checkbox" name="lh[enabled]" value="1" ' . checked( 1, $o['enabled'], false ) . ' /> Cho phép khách gửi liên hệ</label></td></tr>';
	echo '<tr><th>Tiêu đề</th><td><input type="text" name="lh[tieu_de]" value="' . esc_attr( $o['tieu_de'] ) . '" class="regular-text" /></td></tr>';
	echo '<tr><th>Dòng mô tả</th><td><input type="text" name="lh[mo_ta]" value="' . esc_attr( $o['mo_ta'] ) . '" class="large-text" /></td></tr>';
	echo '<tr><th>Chữ trên nút</th><td><input type="text" name="lh[nut]" value="' . esc_attr( $o['nut'] ) . '" class="regular-text" style="max-width:220px" /></td></tr>';
	echo '<tr><th>Danh sách chủ đề</th><td><textarea name="lh[chu_de]" rows="6" class="large-text">' . esc_textarea( $o['chu_de'] ) . '</textarea> <span class="description">Mỗi dòng một chủ đề. Để trống là bỏ hẳn ô chọn chủ đề.</span></td></tr>';
	echo '<tr><th colspan="2"><h2 style="margin:18px 0 0">Nhận thông báo</h2></th></tr>';
	echo '<tr><th>Gửi mail báo</th><td><label><input type="checkbox" name="lh[bat_email]" value="1" ' . checked( 1, $o['bat_email'], false ) . ' /> Có mail báo mỗi khi khách gửi</label></td></tr>';
	echo '<tr><th>Email nhận</th><td><input type="text" name="lh[email]" value="' . esc_attr( $o['email'] ) . '" class="regular-text" /> <span class="description">Nhiều email thì cách nhau dấu phẩy.</span></td></tr>';
	echo '<tr><th>Email nhận thêm (CC)</th><td><input type="text" name="lh[cc]" value="' . esc_attr( $o['cc'] ) . '" class="regular-text" /></td></tr>';
	echo '<tr><th>Tiêu đề mail</th><td><input type="text" name="lh[tieu_mail]" value="' . esc_attr( $o['tieu_mail'] ) . '" class="large-text" /> <span class="description">Tên khách tự nối vào cuối.</span></td></tr>';
	echo '<tr><th>Câu cảm ơn</th><td><textarea name="lh[cam_on]" rows="2" class="large-text">' . esc_textarea( $o['cam_on'] ) . '</textarea></td></tr>';
	echo '</table>';
	submit_button( 'Lưu' );
	echo '</form></div></div>';
}

add_action( 'admin_post_smlife_lh_save', function () {
	if ( ! current_user_can( 'manage_options' ) ) { wp_die( 'Không đủ quyền.' ); }
	check_admin_referer( 'smlife_lh_save', 'smlife_lh_nonce' );
	$in = isset( $_POST['lh'] ) && is_array( $_POST['lh'] ) ? wp_unslash( $_POST['lh'] ) : array();
	$d  = smlife_lh_defaults();
	$t  = function ( $k, $def ) use ( $in ) {
		return ( isset( $in[ $k ] ) && '' !== trim( (string) $in[ $k ] ) ) ? sanitize_text_field( $in[ $k ] ) : $def;
	};
	update_option( 'smlife_lh', array(
		'enabled'   => empty( $in['enabled'] ) ? 0 : 1,
		'bat_email' => empty( $in['bat_email'] ) ? 0 : 1,
		'tieu_de'   => $t( 'tieu_de', $d['tieu_de'] ),
		'mo_ta'     => isset( $in['mo_ta'] ) ? sanitize_text_field( $in['mo_ta'] ) : $d['mo_ta'],
		'nut'       => $t( 'nut', $d['nut'] ),
		'email'     => isset( $in['email'] ) ? sanitize_text_field( $in['email'] ) : $d['email'],
		'cc'        => isset( $in['cc'] ) ? sanitize_text_field( $in['cc'] ) : '',
		'tieu_mail' => $t( 'tieu_mail', $d['tieu_mail'] ),
		'cam_on'    => isset( $in['cam_on'] ) ? sanitize_textarea_field( $in['cam_on'] ) : $d['cam_on'],
		'chu_de'    => isset( $in['chu_de'] ) ? sanitize_textarea_field( $in['chu_de'] ) : $d['chu_de'],
	), false );
	if ( function_exists( 'rocket_clean_domain' ) ) { smlife_xoa_cache(); }
	wp_safe_redirect( admin_url( 'admin.php?page=smlife-form-lien-he&updated=1' ) );
	exit;
} );
