<?php
/**
 * SMLIFE Core - Module: SMTP
 * Gui email qua SMTP de khong roi vao spam.
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

/**
 * SMLIFE - Snippet 09: SMTP (submenu SMLIFE Core)
 * -----------------------------------------------
 * Thay snippet "PHP - Gửi email từ ..." (cấu hình SMTP cứng trong code).
 * Tạo mục "SMTP" trong menu SMLIFE Core để nhập thông tin gửi mail.
 *
 * WPCode: chọn "Run everywhere". Khi dán: KHÔNG copy dòng <?php ở trên cùng.
 *
 * SAU KHI DÙNG: nhập thông tin SMTP ở SMLIFE Core > SMTP, bật, gửi thử.
 * Chạy OK rồi thì TẮT snippet "PHP - Gửi email từ smlifecorp@gmail.com" cũ
 * để tránh cấu hình chạy chồng.
 */

/* ===== Lấy cấu hình (trộn mặc định) ===== */
function smlife_smtp_opt() {
	$def = array(
		'enabled'    => 0,
		'host'       => 'smtp.gmail.com',
		'port'       => 587,
		'enc'        => 'tls', // tls | ssl | none
		'user'       => '',
		'pass'       => '',
		'from_email' => '',
		'from_name'  => 'Shopdepot',
		'force_from' => 1,
	);
	$s = get_option( 'smlife_smtp', array() );
	return is_array( $s ) ? array_merge( $def, $s ) : $def;
}

/* ===== Submenu trong SMLIFE Core ===== */
add_action( 'admin_menu', function () {
	add_submenu_page(
		'smlife-core',
		'SMTP',
		'SMTP',
		'manage_options',
		'smlife-smtp',
		'smlife_smtp_page'
	);
}, 95 );

/* ===== Trang cấu hình ===== */
function smlife_smtp_page() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}
	$o = smlife_smtp_opt();
	echo '<div class="wrap smlife-wrap">';
	echo '<div class="smc-hd"><h1>SMTP</h1>';
	echo '<p>Cấu hình máy chủ gửi mail để email đơn hàng không rơi vào thư rác.</p></div>';

	if ( isset( $_GET['updated'] ) ) {
		echo '<div class="notice notice-success is-dismissible"><p>Đã lưu cấu hình SMTP.</p></div>';
	}
	if ( isset( $_GET['test_sent'] ) ) {
		$ok = '1' === $_GET['test_sent'];
		echo '<div class="notice ' . ( $ok ? 'notice-success' : 'notice-error' ) . ' is-dismissible"><p>' . ( $ok ? 'Đã gửi email thử. Kiểm tra hộp thư.' : 'Gửi thử thất bại. Kiểm tra lại host/port/tài khoản/mật khẩu ứng dụng.' ) . '</p></div>';
	}

	echo '<div class="smlife-card">';
	echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '">';
	echo '<input type="hidden" name="action" value="smlife_smtp_save" />';
	wp_nonce_field( 'smlife_smtp_save', 'smlife_smtp_nonce' );
	echo '<table class="form-table" role="presentation">';
	echo '<tr><th scope="row">Bật SMTP</th><td><label><input type="checkbox" name="smtp[enabled]" value="1" ' . checked( 1, $o['enabled'], false ) . ' /> Gửi mail qua máy chủ SMTP bên dưới</label></td></tr>';
	echo '<tr><th scope="row">Máy chủ (Host)</th><td><input type="text" name="smtp[host]" value="' . esc_attr( $o['host'] ) . '" class="regular-text" placeholder="smtp.gmail.com" /></td></tr>';
	echo '<tr><th scope="row">Cổng (Port)</th><td><input type="number" name="smtp[port]" value="' . esc_attr( $o['port'] ) . '" class="small-text" /> <span class="description">587 = TLS, 465 = SSL</span></td></tr>';
	echo '<tr><th scope="row">Bảo mật</th><td><select name="smtp[enc]">';
	foreach ( array( 'tls' => 'TLS (587)', 'ssl' => 'SSL (465)', 'none' => 'Không' ) as $k => $lbl ) {
		echo '<option value="' . esc_attr( $k ) . '" ' . selected( $k, $o['enc'], false ) . '>' . esc_html( $lbl ) . '</option>';
	}
	echo '</select></td></tr>';
	echo '<tr><th scope="row">Tài khoản (Username)</th><td><input type="text" name="smtp[user]" value="' . esc_attr( $o['user'] ) . '" class="regular-text" placeholder="email@gmail.com" autocomplete="off" /></td></tr>';
	/* Khong in mat khau nguoc ra trang. Truoc day khoa hien nguyen van, chup
	   man hinh trang cai dat gui ai la lo luon quyen gui thu bang hop mail cua
	   cong ty. Gio chi cho biet da luu hay chua, kem 4 ky tu cuoi de doi chieu. */
	$co   = '' !== (string) $o['pass'];
	$duoi = $co ? substr( preg_replace( '/\s+/', '', (string) $o['pass'] ), -4 ) : '';
	echo '<tr><th scope="row">Mật khẩu ứng dụng</th><td><input type="password" id="smlife-smtp-pass" name="smtp[pass]" value="" class="regular-text" autocomplete="new-password" spellcheck="false" placeholder="' . esc_attr( $co ? 'Đã lưu, kết thúc bằng ' . $duoi . ' - để trống là giữ nguyên' : 'Dán App Password của Gmail' ) . '" style="font-family:Menlo,Consolas,monospace;letter-spacing:.5px" />';
	if ( $co ) {
		echo '<label style="display:block;margin-top:6px"><input type="checkbox" name="smtp[xoa_pass]" value="1" /> Gỡ khoá đang lưu</label>';
	}
	echo '<p class="description">Gmail: dùng <strong>App Password</strong> (16 ký tự), không phải mật khẩu đăng nhập. Khoá không còn hiện nguyên văn để tránh lộ khi chụp màn hình; chỉ hiện 4 ký tự cuối để đối chiếu.</p></td></tr>';
	echo '<tr><th scope="row">Email người gửi (From)</th><td><input type="email" name="smtp[from_email]" value="' . esc_attr( $o['from_email'] ) . '" class="regular-text" placeholder="sales@smlife.vn" /></td></tr>';
	echo '<tr><th scope="row">Tên người gửi</th><td><input type="text" name="smtp[from_name]" value="' . esc_attr( $o['from_name'] ) . '" class="regular-text" /></td></tr>';
	echo '<tr><th scope="row">Ép From</th><td><label><input type="checkbox" name="smtp[force_from]" value="1" ' . checked( 1, $o['force_from'], false ) . ' /> Luôn dùng From ở trên cho mọi email</label></td></tr>';
	echo '</table>';
	submit_button( 'Lưu cấu hình' );
	echo '</form></div>';

	echo '<div class="smlife-card"><h2>Gửi thử</h2>';
	echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '">';
	echo '<input type="hidden" name="action" value="smlife_smtp_test" />';
	wp_nonce_field( 'smlife_smtp_test', 'smlife_smtp_nonce' );
	echo '<input type="email" name="test_to" value="' . esc_attr( get_option( 'admin_email' ) ) . '" class="regular-text" /> ';
	submit_button( 'Gửi email thử', 'secondary', 'submit', false );
	echo '</form></div>';
	echo '</div>';
}

/* ===== Lưu cấu hình ===== */
add_action( 'admin_post_smlife_smtp_save', function () {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( 'Không đủ quyền.' );
	}
	check_admin_referer( 'smlife_smtp_save', 'smlife_smtp_nonce' );
	$in  = isset( $_POST['smtp'] ) && is_array( $_POST['smtp'] ) ? wp_unslash( $_POST['smtp'] ) : array();
	$cur = smlife_smtp_opt();

	$enc = isset( $in['enc'] ) ? sanitize_key( $in['enc'] ) : 'tls';
	if ( ! in_array( $enc, array( 'tls', 'ssl', 'none' ), true ) ) {
		$enc = 'tls';
	}
	$new = array(
		'enabled'    => empty( $in['enabled'] ) ? 0 : 1,
		'host'       => isset( $in['host'] ) ? sanitize_text_field( $in['host'] ) : '',
		'port'       => isset( $in['port'] ) ? absint( $in['port'] ) : 587,
		'enc'        => $enc,
		'user'       => isset( $in['user'] ) ? sanitize_text_field( $in['user'] ) : '',
		// Mat khau: de trong = giu cu, tich o go khoa = xoa.
		'pass'       => ! empty( $in['xoa_pass'] ) ? '' : ( ( isset( $in['pass'] ) && '' !== trim( (string) $in['pass'] ) ) ? trim( (string) $in['pass'] ) : $cur['pass'] ),
		'from_email' => isset( $in['from_email'] ) && is_email( $in['from_email'] ) ? sanitize_email( $in['from_email'] ) : '',
		'from_name'  => isset( $in['from_name'] ) ? sanitize_text_field( $in['from_name'] ) : '',
		'force_from' => empty( $in['force_from'] ) ? 0 : 1,
	);
	update_option( 'smlife_smtp', $new, false );
	wp_safe_redirect( admin_url( 'admin.php?page=smlife-smtp&updated=1' ) );
	exit;
} );

/* ===== Áp dụng cấu hình vào PHPMailer ===== */
add_action( 'phpmailer_init', function ( $phpmailer ) {
	$o = smlife_smtp_opt();
	if ( empty( $o['enabled'] ) || empty( $o['host'] ) ) {
		return;
	}
	$phpmailer->isSMTP();
	$phpmailer->Host = $o['host'];
	$phpmailer->Port = (int) $o['port'];
	if ( 'none' === $o['enc'] ) {
		$phpmailer->SMTPSecure = '';
		$phpmailer->SMTPAutoTLS = false;
	} else {
		$phpmailer->SMTPSecure = $o['enc'];
	}
	if ( '' !== $o['user'] ) {
		$phpmailer->SMTPAuth = true;
		$phpmailer->Username = $o['user'];
		$phpmailer->Password = $o['pass'];
	}
	if ( ! empty( $o['from_email'] ) ) {
		$phpmailer->setFrom( $o['from_email'], $o['from_name'], (bool) $o['force_from'] );
	}
} );

/* ===== Ép địa chỉ From ===== */
add_filter( 'wp_mail_from', function ( $email ) {
	$o = smlife_smtp_opt();
	return ( ! empty( $o['enabled'] ) && ! empty( $o['force_from'] ) && ! empty( $o['from_email'] ) ) ? $o['from_email'] : $email;
}, 99 );
add_filter( 'wp_mail_from_name', function ( $name ) {
	$o = smlife_smtp_opt();
	return ( ! empty( $o['enabled'] ) && ! empty( $o['force_from'] ) && ! empty( $o['from_name'] ) ) ? $o['from_name'] : $name;
}, 99 );

/* ===== Gửi thử ===== */
add_action( 'admin_post_smlife_smtp_test', function () {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( 'Không đủ quyền.' );
	}
	check_admin_referer( 'smlife_smtp_test', 'smlife_smtp_nonce' );
	$to = isset( $_POST['test_to'] ) ? sanitize_email( wp_unslash( $_POST['test_to'] ) ) : '';
	$ok = false;
	if ( is_email( $to ) ) {
		$ok = wp_mail(
			$to,
			'SMLIFE - Thử SMTP',
			'<p>Đây là email thử từ cấu hình SMTP của SMLIFE Core. Nếu bạn nhận được email này nghĩa là SMTP đã chạy đúng.</p>',
			array( 'Content-Type: text/html; charset=UTF-8' )
		);
	}
	wp_safe_redirect( admin_url( 'admin.php?page=smlife-smtp&test_sent=' . ( $ok ? '1' : '0' ) ) );
	exit;
} );
