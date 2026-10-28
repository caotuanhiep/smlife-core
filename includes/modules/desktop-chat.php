<?php
/**
 * SMLIFE Core - Module: Desktop Chat Button
 * =====================================================================
 * Cum nut lien he dang thanh doc, bam sat canh phai man hinh,
 * bo tron canh trai. CHI hien tren may tinh.
 *
 * Admin : SMLIFE Core > Desktop Chat Button
 * Option: smlife_dcb  (ver 2)
 * Nguon : co the nap nhanh tu smlife_mm (SMLIFE Core > Menu Mobile)
 *
 * Toan bo la lop phu position:fixed, KHONG dung den chieu rong body.
 * =====================================================================
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }
if ( defined( 'SMLIFE_DCB_LOADED' ) ) { return; }
define( 'SMLIFE_DCB_LOADED', true );
define( 'SMLIFE_DCB_VER', 2 );
define( 'SMLIFE_DCB_ROWS', 6 );

/* =====================================================================
 * 1. MAC DINH
 * ===================================================================== */
function smlife_dcb_blank_item() {
	return array(
		'on'     => 0,
		'name'   => '',
		'sub'    => '',
		'url'    => '',
		'img'    => '',
		'bg'     => '#ffffff',
		'color'  => '',
		'ibg'    => '#e8641e',
		'badge'  => '',
		'newtab' => 1,
	);
}

function smlife_dcb_seed_items() {
	$mm = get_option( 'smlife_mm', array() );
	$chat = ( is_array( $mm ) && ! empty( $mm['chat_items'] ) && is_array( $mm['chat_items'] ) ) ? $mm['chat_items'] : array();

	$pick = function ( $needle ) use ( $chat ) {
		foreach ( $chat as $c ) {
			if ( empty( $c['name'] ) ) { continue; }
			if ( false !== mb_stripos( $c['name'], $needle ) ) { return $c; }
		}
		return array();
	};

	$zalo = $pick( 'zalo' );
	$mess = $pick( 'messenger' );

	$items = array();

	$items[0] = array_merge( smlife_dcb_blank_item(), array(
		'on'     => 1,
		'name'   => 'Chat Zalo',
		'sub'    => 'Hỗ trợ 24/7',
		'url'    => ! empty( $zalo['url'] ) ? $zalo['url'] : '',
		'img'    => ! empty( $zalo['img'] ) ? $zalo['img'] : '',
		'bg'     => '#ffffff',
		'ibg'    => '#0068ff',
		'badge'  => '',
	) );

	$items[1] = array_merge( smlife_dcb_blank_item(), array(
		'on'     => 1,
		'name'   => 'Chat Messenger',
		'sub'    => 'Nhắn tin Facebook',
		'url'    => ! empty( $mess['url'] ) ? $mess['url'] : '',
		'img'    => ! empty( $mess['img'] ) ? $mess['img'] : '',
		'bg'     => '#ffffff',
		'ibg'    => '#0084ff',
	) );

	$items[2] = array_merge( smlife_dcb_blank_item(), array(
		'on'     => 1,
		'name'   => 'Google Map',
		'sub'    => 'Showroom Quận 7',
		'url'    => 'https://www.google.com/maps/search/?api=1&query=SMLIFE+14-16+Duong+73+Tan+Phong+Quan+7+TPHCM',
		'img'    => '',
		'bg'     => '#ffffff',
		'ibg'    => '#34a853',
	) );

	for ( $i = 3; $i < SMLIFE_DCB_ROWS; $i++ ) {
		$items[ $i ] = smlife_dcb_blank_item();
	}

	return $items;
}

function smlife_dcb_defaults() {
	return array(
		'ver'        => SMLIFE_DCB_VER,
		'enabled'    => 1,
		'bp'         => 850,
		'v_align'    => 'middle',
		'v_offset'   => 0,
		'gap'        => 10,
		'radius'     => 30,
		'height'     => 56,
		'icon'       => 38,
		'width'      => 236,
		'mode'       => 'collapse',
		'hover_fx'   => 'slide',
		'shadow'     => 1,
		'sub_first'  => 1,
		'lift_totop' => 1,
		'bg'         => '#ffffff',
		'color'      => '#222222',
		'sub_color'  => '#8a8a8a',
		'hide_on'    => '',
		'items'      => smlife_dcb_seed_items(),
	);
}

function smlife_dcb_get() {
	$o = get_option( 'smlife_dcb', array() );
	if ( ! is_array( $o ) || empty( $o['ver'] ) || (int) $o['ver'] !== SMLIFE_DCB_VER ) {
		return smlife_dcb_defaults();
	}
	$o = array_merge( smlife_dcb_defaults(), $o );
	if ( empty( $o['items'] ) || ! is_array( $o['items'] ) ) {
		$o['items'] = smlife_dcb_seed_items();
	}
	for ( $i = 0; $i < SMLIFE_DCB_ROWS; $i++ ) {
		$o['items'][ $i ] = isset( $o['items'][ $i ] ) && is_array( $o['items'][ $i ] )
			? array_merge( smlife_dcb_blank_item(), $o['items'][ $i ] )
			: smlife_dcb_blank_item();
	}
	ksort( $o['items'] );
	return $o;
}

function smlife_dcb_active_items( $o ) {
	$out = array();
	foreach ( $o['items'] as $it ) {
		if ( empty( $it['on'] ) || '' === trim( (string) $it['url'] ) || '' === trim( (string) $it['name'] ) ) { continue; }
		$out[] = $it;
	}
	return $out;
}

/* =====================================================================
 * 2. TRANG CAU HINH
 * ===================================================================== */
add_action( 'admin_menu', 'smlife_dcb_menu', 56 );
function smlife_dcb_menu() {
	add_submenu_page(
		'smlife-core',
		'Desktop Chat Button',
		'Desktop Chat Button',
		'manage_options',
		'smlife-desktop-chat',
		'smlife_dcb_page'
	);
}

add_action( 'admin_enqueue_scripts', 'smlife_dcb_admin_assets' );
function smlife_dcb_admin_assets() {
	if ( isset( $_GET['page'] ) && 'smlife-desktop-chat' === $_GET['page'] ) {
		wp_enqueue_media();
	}
}

function smlife_dcb_save_post() {
	$in  = isset( $_POST['dcb'] ) && is_array( $_POST['dcb'] ) ? wp_unslash( $_POST['dcb'] ) : array();
	$def = smlife_dcb_defaults();
	$new = $def;

	foreach ( array( 'enabled', 'shadow', 'sub_first', 'lift_totop' ) as $k ) {
		$new[ $k ] = empty( $in[ $k ] ) ? 0 : 1;
	}
	foreach ( array( 'bp', 'gap', 'radius', 'height', 'icon', 'width' ) as $k ) {
		$new[ $k ] = isset( $in[ $k ] ) ? absint( $in[ $k ] ) : $def[ $k ];
	}
	$new['v_offset'] = isset( $in['v_offset'] ) ? intval( $in['v_offset'] ) : 0;

	$align = isset( $in['v_align'] ) ? $in['v_align'] : 'middle';
	$new['v_align'] = in_array( $align, array( 'top', 'middle', 'bottom' ), true ) ? $align : 'middle';

	$mode = isset( $in['mode'] ) ? $in['mode'] : 'collapse';
	$new['mode'] = in_array( $mode, array( 'always', 'collapse' ), true ) ? $mode : 'collapse';

	$fx = isset( $in['hover_fx'] ) ? $in['hover_fx'] : 'slide';
	$new['hover_fx'] = in_array( $fx, array( 'slide', 'zoom', 'none' ), true ) ? $fx : 'slide';

	foreach ( array( 'bg', 'color', 'sub_color' ) as $k ) {
		$v = isset( $in[ $k ] ) ? sanitize_hex_color( $in[ $k ] ) : '';
		$new[ $k ] = $v ? $v : $def[ $k ];
	}
	$new['hide_on'] = isset( $in['hide_on'] ) ? sanitize_text_field( $in['hide_on'] ) : '';

	// Gioi han an toan
	if ( $new['bp'] < 480 )     { $new['bp'] = 480; }
	if ( $new['height'] < 36 )  { $new['height'] = 36; }
	if ( $new['height'] > 90 )  { $new['height'] = 90; }
	if ( $new['icon'] < 20 )    { $new['icon'] = 20; }
	if ( $new['icon'] > $new['height'] - 6 ) { $new['icon'] = $new['height'] - 6; }
	if ( $new['width'] < 140 )  { $new['width'] = 140; }
	if ( $new['width'] > 420 )  { $new['width'] = 420; }
	if ( $new['radius'] > 60 )  { $new['radius'] = 60; }
	unset( $new['auto_open'] );

	$rows = isset( $in['items'] ) && is_array( $in['items'] ) ? $in['items'] : array();
	$items = array();
	for ( $i = 0; $i < SMLIFE_DCB_ROWS; $i++ ) {
		$r = isset( $rows[ $i ] ) && is_array( $rows[ $i ] ) ? $rows[ $i ] : array();
		$items[ $i ] = array(
			'on'     => empty( $r['on'] ) ? 0 : 1,
			'name'   => isset( $r['name'] ) ? sanitize_text_field( $r['name'] ) : '',
			'sub'    => isset( $r['sub'] ) ? sanitize_text_field( $r['sub'] ) : '',
			'url'    => isset( $r['url'] ) ? esc_url_raw( trim( $r['url'] ) ) : '',
			'img'    => isset( $r['img'] ) ? esc_url_raw( trim( $r['img'] ) ) : '',
			'bg'     => isset( $r['bg'] ) && sanitize_hex_color( $r['bg'] ) ? sanitize_hex_color( $r['bg'] ) : '#ffffff',
			'color'  => isset( $r['color'] ) && sanitize_hex_color( $r['color'] ) ? sanitize_hex_color( $r['color'] ) : '',
			'ibg'    => isset( $r['ibg'] ) && sanitize_hex_color( $r['ibg'] ) ? sanitize_hex_color( $r['ibg'] ) : '#e8641e',
			'badge'  => isset( $r['badge'] ) ? sanitize_text_field( $r['badge'] ) : '',
			'newtab' => empty( $r['newtab'] ) ? 0 : 1,
		);
	}
	$new['items'] = $items;
	$new['ver']   = SMLIFE_DCB_VER;

	update_option( 'smlife_dcb', $new );
	/* 1.24.0: truoc day luu xong khong xoa cache, so moi chi len tung trang. Gio vao danh sach cho dem */
	smlife_xoa_cache( 'Desktop Chat' );
}

function smlife_dcb_page() {
	if ( ! current_user_can( 'manage_options' ) ) { return; }

	$msg = '';
	if ( isset( $_POST['smlife_dcb_nonce'] ) && wp_verify_nonce( $_POST['smlife_dcb_nonce'], 'smlife_dcb_save' ) ) {
		if ( isset( $_POST['dcb_reset'] ) ) {
			delete_option( 'smlife_dcb' );
			$msg = 'Đã khôi phục về cấu hình mặc định và nạp lại dữ liệu từ Menu Mobile.';
		} else {
			smlife_dcb_save_post();
			$msg = 'Đã lưu cấu hình.';
		}
	}

	$o     = smlife_dcb_get();
	$live  = smlife_dcb_active_items( $o );
	$mmurl = admin_url( 'admin.php?page=smlife-menu-mobile' );
	?>
	<div class="wrap smlife-wrap smlife-dcb-wrap">
		<h1>Desktop Chat Button</h1>
		<p class="description">
			Thanh nút liên hệ dọc, bám sát cạnh phải màn hình, bo tròn cạnh trái.
			<strong>Chỉ hiện trên máy tính</strong> từ <?php echo (int) $o['bp']; ?>px trở lên, mobile đã có thanh
			<a href="<?php echo esc_url( $mmurl ); ?>">Menu Mobile</a> lo rồi.
			Đây là lớp phủ nổi, không đụng tới chiều rộng trang.
		</p>

		<?php if ( $msg ) : ?>
			<div class="notice notice-success is-dismissible"><p><?php echo esc_html( $msg ); ?></p></div>
		<?php endif; ?>

		<form method="post">
			<?php wp_nonce_field( 'smlife_dcb_save', 'smlife_dcb_nonce' ); ?>

			<h2 class="title">Các nút</h2>
			<p class="description">Kéo thứ tự bằng cách sửa nội dung từng dòng. Dòng nào bỏ trống Tên hoặc Link thì tự động không hiện.</p>
			<table class="widefat striped smlife-dcb-table" style="width:100%">
				<thead>
					<tr>
						<th style="width:46px">Bật</th>
						<th style="width:128px">Dòng chính</th>
						<th style="width:128px">Dòng phụ</th>
						<th style="min-width:230px">Link</th>
						<th style="width:186px">Ảnh icon</th>
						<th style="width:86px">Badge</th>
						<th style="width:58px">Nền</th>
						<th style="width:58px">Chữ</th>
						<th style="width:58px">Nền icon</th>
						<th style="width:72px">Tab mới</th>
					</tr>
				</thead>
				<tbody>
				<?php for ( $i = 0; $i < SMLIFE_DCB_ROWS; $i++ ) : $it = $o['items'][ $i ]; ?>
					<tr>
						<td><input type="checkbox" name="dcb[items][<?php echo $i; ?>][on]" value="1" <?php checked( $it['on'], 1 ); ?>></td>
						<td><input type="text" name="dcb[items][<?php echo $i; ?>][name]" value="<?php echo esc_attr( $it['name'] ); ?>" style="width:100%" placeholder="Chat Zalo"></td>
						<td><input type="text" name="dcb[items][<?php echo $i; ?>][sub]" value="<?php echo esc_attr( $it['sub'] ); ?>" style="width:100%" placeholder="Hỗ trợ 24/7"></td>
						<td><input type="text" name="dcb[items][<?php echo $i; ?>][url]" value="<?php echo esc_attr( $it['url'] ); ?>" style="width:100%" placeholder="https://zalo.me/..."></td>
						<td>
							<input type="text" name="dcb[items][<?php echo $i; ?>][img]" id="dcb_img_<?php echo $i; ?>" value="<?php echo esc_attr( $it['img'] ); ?>" style="width:108px">
							<button type="button" class="button dcb-pick" data-target="dcb_img_<?php echo $i; ?>">Chọn</button>
						</td>
						<td><input type="text" name="dcb[items][<?php echo $i; ?>][badge]" value="<?php echo esc_attr( $it['badge'] ); ?>" style="width:100%" placeholder="24/7"></td>
						<td><input type="color" name="dcb[items][<?php echo $i; ?>][bg]" value="<?php echo esc_attr( $it['bg'] ); ?>"></td>
						<td><input type="color" name="dcb[items][<?php echo $i; ?>][color]" value="<?php echo esc_attr( $it['color'] ? $it['color'] : $o['color'] ); ?>"></td>
						<td><input type="color" name="dcb[items][<?php echo $i; ?>][ibg]" value="<?php echo esc_attr( $it['ibg'] ? $it['ibg'] : '#e8641e' ); ?>"></td>
						<td style="text-align:center"><input type="checkbox" name="dcb[items][<?php echo $i; ?>][newtab]" value="1" <?php checked( $it['newtab'], 1 ); ?>></td>
					</tr>
				<?php endfor; ?>
				</tbody>
			</table>
			<p class="description">
				Ảnh icon nên vuông từ 80x80px. Để trống thì dùng chữ cái đầu của Dòng chính trên màu Nền icon.
				Badge là nhãn nhỏ nổi bên cạnh, ví dụ 24/7. Màu Nền áp cho cả thẻ, màu Chữ áp cho Dòng chính.
			</p>

			<h2 class="title">Hiển thị</h2>
			<table class="form-table" role="presentation">
				<tr>
					<th scope="row">Bật thanh nút</th>
					<td><label><input type="checkbox" name="dcb[enabled]" value="1" <?php checked( $o['enabled'], 1 ); ?>> Hiện thanh nút liên hệ trên desktop</label></td>
				</tr>
				<tr>
					<th scope="row">Kiểu thanh</th>
					<td>
						<select name="dcb[mode]">
							<option value="collapse" <?php selected( $o['mode'], 'collapse' ); ?>>Thu gọn, rê chuột mới bung chữ</option>
							<option value="always"   <?php selected( $o['mode'], 'always' ); ?>>Luôn bung đầy đủ chữ</option>
						</select>
						<p class="description">Thu gọn thì mọi trang chỉ thấy icon tròn, đỡ che nội dung. Chỉ khi rê chuột vào mới trượt ra hiện chữ, không tự bung khi vào trang.</p>
					</td>
				</tr>
				<tr>
					<th scope="row">Hiệu ứng rê chuột</th>
					<td>
						<select name="dcb[hover_fx]">
							<option value="slide" <?php selected( $o['hover_fx'], 'slide' ); ?>>Trượt nhẹ sang trái</option>
							<option value="zoom"  <?php selected( $o['hover_fx'], 'zoom' ); ?>>Phóng to nhẹ</option>
							<option value="none"  <?php selected( $o['hover_fx'], 'none' ); ?>>Không hiệu ứng</option>
						</select>
					</td>
				</tr>
				<tr>
					<th scope="row">Thứ tự 2 dòng chữ</th>
					<td><label><input type="checkbox" name="dcb[sub_first]" value="1" <?php checked( $o['sub_first'], 1 ); ?>> Dòng phụ nằm trên, dòng chính in đậm nằm dưới</label></td>
				</tr>
				<tr>
					<th scope="row">Chỉ hiện từ (px)</th>
					<td><input type="number" name="dcb[bp]" value="<?php echo (int) $o['bp']; ?>" min="480" max="1600" class="small-text"> px trở lên</td>
				</tr>
				<tr>
					<th scope="row">Ẩn ở trang</th>
					<td>
						<input type="text" name="dcb[hide_on]" value="<?php echo esc_attr( $o['hide_on'] ); ?>" class="regular-text" placeholder="thanh-toan, gio-hang">
						<p class="description">Nhập slug trang, cách nhau bằng dấu phẩy. Để trống là hiện mọi trang.</p>
					</td>
				</tr>
				<tr>
					<th scope="row">Nút Lên đầu trang</th>
					<td><label><input type="checkbox" name="dcb[lift_totop]" value="1" <?php checked( $o['lift_totop'], 1 ); ?>> Đẩy nút mũi tên lên đầu trang sang trái để không bị thanh nút che</label></td>
				</tr>
			</table>

			<h2 class="title">Vị trí và kích thước</h2>
			<table class="form-table" role="presentation">
				<tr>
					<th scope="row">Căn dọc</th>
					<td>
						<select name="dcb[v_align]">
							<option value="middle" <?php selected( $o['v_align'], 'middle' ); ?>>Giữa màn hình</option>
							<option value="top"    <?php selected( $o['v_align'], 'top' ); ?>>Phía trên</option>
							<option value="bottom" <?php selected( $o['v_align'], 'bottom' ); ?>>Phía dưới</option>
						</select>
						&nbsp; Dịch thêm <input type="number" name="dcb[v_offset]" value="<?php echo (int) $o['v_offset']; ?>" min="-400" max="400" class="small-text"> px
						<p class="description">Số dương đẩy xuống, số âm đẩy lên.</p>
					</td>
				</tr>
				<tr>
					<th scope="row">Chiều cao mỗi nút</th>
					<td><input type="number" name="dcb[height]" value="<?php echo (int) $o['height']; ?>" min="36" max="90" class="small-text"> px</td>
				</tr>
				<tr>
					<th scope="row">Bề ngang khi bung</th>
					<td><input type="number" name="dcb[width]" value="<?php echo (int) $o['width']; ?>" min="140" max="420" class="small-text"> px</td>
				</tr>
				<tr>
					<th scope="row">Cỡ ảnh icon</th>
					<td><input type="number" name="dcb[icon]" value="<?php echo (int) $o['icon']; ?>" min="20" max="80" class="small-text"> px</td>
				</tr>
				<tr>
					<th scope="row">Bo góc trái</th>
					<td>
						<input type="number" name="dcb[radius]" value="<?php echo (int) $o['radius']; ?>" min="0" max="60" class="small-text"> px
						<p class="description">Mép phải luôn vuông và dính sát biên màn hình. Để bằng nửa chiều cao nút là thành hình viên thuốc.</p>
					</td>
				</tr>
				<tr>
					<th scope="row">Khoảng cách giữa các nút</th>
					<td><input type="number" name="dcb[gap]" value="<?php echo (int) $o['gap']; ?>" min="0" max="40" class="small-text"> px</td>
				</tr>
			</table>

			<h2 class="title">Màu chung</h2>
			<table class="form-table" role="presentation">
				<tr>
					<th scope="row">Nền mặc định</th>
					<td><input type="color" name="dcb[bg]" value="<?php echo esc_attr( $o['bg'] ); ?>"> <span class="description">Dùng khi dòng đó không đặt màu nền riêng.</span></td>
				</tr>
				<tr>
					<th scope="row">Màu dòng chính</th>
					<td><input type="color" name="dcb[color]" value="<?php echo esc_attr( $o['color'] ); ?>"></td>
				</tr>
				<tr>
					<th scope="row">Màu dòng phụ</th>
					<td><input type="color" name="dcb[sub_color]" value="<?php echo esc_attr( $o['sub_color'] ); ?>"></td>
				</tr>
				<tr>
					<th scope="row">Đổ bóng</th>
					<td><label><input type="checkbox" name="dcb[shadow]" value="1" <?php checked( $o['shadow'], 1 ); ?>> Có bóng cho nổi khỏi nền trang</label></td>
				</tr>
			</table>

			<p class="submit">
				<?php submit_button( 'Lưu cấu hình', 'primary', 'submit', false ); ?>
				&nbsp;
				<button type="submit" name="dcb_reset" value="1" class="button"
					onclick="return confirm('Khôi phục mặc định và nạp lại Zalo, Messenger, Google Map từ Menu Mobile?');">Khôi phục mặc định</button>
			</p>
		</form>

		<h2 class="title">Đang hiển thị <?php echo count( $live ); ?> nút</h2>
		<?php if ( empty( $live ) ) : ?>
			<p>Chưa có nút nào bật kèm đủ Tên và Link.</p>
		<?php else : ?>
			<ol style="margin-left:18px">
				<?php foreach ( $live as $it ) : ?>
					<li><strong><?php echo esc_html( $it['name'] ); ?></strong><?php echo $it['sub'] ? ' &middot; ' . esc_html( $it['sub'] ) : ''; ?></li>
				<?php endforeach; ?>
			</ol>
		<?php endif; ?>
	</div>

	<script>
	jQuery(function($){
		$('.dcb-pick').on('click', function(e){
			e.preventDefault();
			var target = $(this).data('target');
			var frame = wp.media({ title: 'Chọn ảnh icon', multiple: false, library: { type: 'image' } });
			frame.on('select', function(){
				var a = frame.state().get('selection').first().toJSON();
				$('#' + target).val(a.url);
			});
			frame.open();
		});
	});
	</script>
	<?php
}

/* =====================================================================
 * 3. FRONTEND
 * ===================================================================== */
function smlife_dcb_is_hidden_here( $o ) {
	if ( '' === trim( (string) $o['hide_on'] ) ) { return false; }
	$slugs = array_filter( array_map( 'trim', explode( ',', $o['hide_on'] ) ) );
	if ( empty( $slugs ) ) { return false; }
	$post = get_post();
	$cur  = $post ? $post->post_name : '';
	if ( $cur && in_array( $cur, $slugs, true ) ) { return true; }
	foreach ( $slugs as $s ) {
		if ( is_page( $s ) ) { return true; }
	}
	return false;
}

add_action( 'wp_footer', 'smlife_dcb_render', 95 );
function smlife_dcb_render() {
	if ( is_admin() ) { return; }

	$o = smlife_dcb_get();
	if ( empty( $o['enabled'] ) ) { return; }
	if ( smlife_dcb_is_hidden_here( $o ) ) { return; }

	$items = smlife_dcb_active_items( $o );
	if ( empty( $items ) ) { return; }

	$bp       = (int) $o['bp'];
	$h        = (int) $o['height'];
	$icon     = (int) $o['icon'];
	$w        = (int) $o['width'];
	$gap      = (int) $o['gap'];
	$rad      = (int) $o['radius'];
	$pad      = max( 4, (int) round( ( $h - $icon ) / 2 ) );
	$collapse = ( 'collapse' === $o['mode'] );
	$closed_w = $icon + ( $pad * 2 );
	$shadow   = ! empty( $o['shadow'] ) ? '0 3px 14px rgba(0,0,0,.17)' : 'none';

	if ( 'top' === $o['v_align'] ) {
		$anchor = 'top:' . max( 0, 90 + (int) $o['v_offset'] ) . 'px;';
	} elseif ( 'bottom' === $o['v_align'] ) {
		$anchor = 'bottom:' . max( 0, 90 - (int) $o['v_offset'] ) . 'px;';
	} else {
		$anchor = 'top:50%;transform:translateY(calc(-50% + ' . (int) $o['v_offset'] . 'px));';
	}
	?>
	<style id="smlife-dcb-css">
	.smldb{display:none}
	@media (min-width:<?php echo $bp; ?>px){
		.smldb{
			display:flex;flex-direction:column;align-items:flex-end;
			gap:<?php echo $gap; ?>px;
			position:fixed;right:0;<?php echo $anchor; ?>
			z-index:9980;pointer-events:none;margin:0;padding:0;list-style:none;
		}
		.smldb *{box-sizing:border-box}
		.smldb__item{
			pointer-events:auto;
			display:flex;align-items:center;gap:10px;
			height:<?php echo $h; ?>px;
			width:<?php echo $collapse ? $closed_w : $w; ?>px;
			padding-left:<?php echo $pad; ?>px;padding-right:<?php echo $pad; ?>px;
			border-radius:<?php echo $rad; ?>px 0 0 <?php echo $rad; ?>px;
			background:<?php echo esc_attr( $o['bg'] ); ?>;
			color:<?php echo esc_attr( $o['color'] ); ?>;
			box-shadow:<?php echo $shadow; ?>;
			text-decoration:none;overflow:hidden;white-space:nowrap;
			transition:width .28s cubic-bezier(.4,0,.2,1),transform .22s ease,box-shadow .22s ease;
		}
		<?php if ( $collapse ) : ?>
		.smldb__item:hover,.smldb__item:focus-visible{width:<?php echo $w; ?>px}
		<?php endif; ?>
		<?php if ( 'slide' === $o['hover_fx'] ) : ?>
		.smldb__item:hover{box-shadow:0 6px 20px rgba(0,0,0,.26)}
		<?php elseif ( 'zoom' === $o['hover_fx'] ) : ?>
		.smldb__item:hover{transform:scale(1.04);transform-origin:right center;box-shadow:0 6px 20px rgba(0,0,0,.26)}
		<?php endif; ?>
		.smldb__item:focus-visible{outline:2px solid currentColor;outline-offset:-4px}
		.smldb__ava{
			flex:0 0 <?php echo $icon; ?>px;width:<?php echo $icon; ?>px;height:<?php echo $icon; ?>px;
			border-radius:50%;overflow:hidden;display:flex;align-items:center;justify-content:center;
			color:#fff;font-weight:700;font-size:<?php echo max( 12, (int) round( $icon * .4 ) ); ?>px;
		}
		.smldb__ava img{width:100%;height:100%;object-fit:cover;display:block}
		.smldb__ava svg{width:62%;height:62%;fill:currentColor}
		.smldb__txt{
			display:flex;flex-direction:column;justify-content:center;line-height:1.25;
			min-width:0;flex:1 1 auto;
		}
		.smldb__main{
			font-size:14px;font-weight:700;
			overflow:hidden;text-overflow:ellipsis;
		}
		.smldb__sub{
			font-size:11px;font-weight:400;
			color:<?php echo esc_attr( $o['sub_color'] ); ?>;
			overflow:hidden;text-overflow:ellipsis;
		}
		.smldb__badge{
			flex:0 0 auto;margin-left:6px;font-size:9px;font-weight:700;line-height:1.6;
			background:#19b55a;color:#fff;border-radius:10px;padding:0 7px;
		}
		<?php if ( ! empty( $o['lift_totop'] ) ) : ?>
		#top-link{right:<?php echo $closed_w + 16; ?>px !important}
		<?php endif; ?>
	}
	@media (prefers-reduced-motion:reduce){
		.smldb__item{transition:none !important}
	}
	</style>

	<nav class="smldb" id="smldb" aria-label="Kênh liên hệ nhanh"
		data-mode="<?php echo esc_attr( $o['mode'] ); ?>">
		<?php foreach ( $items as $it ) :
			$bg    = $it['bg'] ? $it['bg'] : $o['bg'];
			$col   = $it['color'] ? $it['color'] : $o['color'];
			$first = mb_substr( trim( $it['name'] ), 0, 1 );
			$main  = '<span class="smldb__main">' . esc_html( $it['name'] ) . '</span>';
			$sub   = $it['sub'] ? '<span class="smldb__sub">' . esc_html( $it['sub'] ) . '</span>' : '';
			?>
			<a class="smldb__item"
				href="<?php echo esc_url( $it['url'] ); ?>"
				style="background:<?php echo esc_attr( $bg ); ?>;color:<?php echo esc_attr( $col ); ?>"
				<?php echo ! empty( $it['newtab'] ) ? 'target="_blank" rel="noopener nofollow"' : ''; ?>
				title="<?php echo esc_attr( trim( $it['name'] . ' ' . $it['sub'] ) ); ?>">
				<span class="smldb__ava" style="background:<?php echo esc_attr( $it['img'] ? 'transparent' : ( $it['ibg'] ? $it['ibg'] : '#e8641e' ) ); ?>">
					<?php if ( $it['img'] ) : ?>
						<img src="<?php echo esc_url( $it['img'] ); ?>" alt="" loading="lazy"
							width="<?php echo $icon; ?>" height="<?php echo $icon; ?>">
					<?php else : ?>
						<?php echo esc_html( $first ); ?>
					<?php endif; ?>
				</span>
				<span class="smldb__txt">
					<?php
					echo ! empty( $o['sub_first'] ) ? $sub . $main : $main . $sub;
					?>
				</span>
				<?php if ( ! empty( $it['badge'] ) ) : ?>
					<span class="smldb__badge"><?php echo esc_html( $it['badge'] ); ?></span>
				<?php endif; ?>
			</a>
		<?php endforeach; ?>
	</nav>

	<?php
}
