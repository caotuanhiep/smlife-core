<?php
/**
 * SMLIFE Core - Module: Kieu hien thi bo sung
 *
 * Ba tinh chinh giao dien hay phai lam di lam lai, gom ve mot cho:
 *
 *   1. VIDEO YOUTUBE DUNG TI LE. Nhung iframe YouTube dan tay thuong bi
 *      ghim san height="600". Tren dien thoai khung rong chi 360px ma cao
 *      600px nen khung hinh bi ep, nhin nhu dang bi cat. Ep lai 16:9 thi
 *      man hinh nao cung hien dung bang video day du.
 *
 *   2. TAB KIEU VIEN THUOC. Tab cua UX Builder mac dinh la gach chan.
 *      Them lop smlife-tab-pill vao o Class cua phan tu Tabs la doi sang
 *      kieu vien thuoc nhu mau: nut bo tron, nut dang chon to mau dam.
 *
 *   3. KHOI BAI VIET KIEU TAP CHI. Them lop smlife-tapchi vao o Class cua
 *      hang chua khoi Blog Posts: anh bo goc ti le 4:3, chu canh trai,
 *      ten chuyen muc dang the nho, tieu de gon hai dong, mo ta ba dong.
 *
 * Hai muc 2 va 3 chi chay khi co lop tuong ung nen khong anh huong gi toi
 * cac khoi khac dang co tren web.
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

/* ===== 1. Cai dat ===== */

function smlife_ts_defaults() {
	return array(
		'enabled'  => 1,
		'video'    => 1,
		'tab'      => 1,
		'tab_mau'  => '#2b2b2b',
		'tap_chi'  => 1,
		'tc_goc'   => 14,
		'tc_ti_le' => '4/3',
		'vd_goc'   => 14,
		'tab_goc'  => 8,
		'tab_canh' => 'trai',
		'tieu_de'  => 1,
		'td_co'    => 20,
		'td_line'  => 'mo',
		'td_mau'   => '#ececec',
		'nut_them' => 1,
		'nut_goc'  => 10,
		'the_sp'   => 1,
		'blog_goc' => 14,
	);
}

function smlife_ts_opt() {
	$o = get_option( 'smlife_ts', array() );
	return is_array( $o ) ? array_merge( smlife_ts_defaults(), $o ) : smlife_ts_defaults();
}

/* ===== 2. CSS ra trang ===== */

add_action( 'wp_head', function () {
	$o = smlife_ts_opt();
	if ( empty( $o['enabled'] ) ) { return; }
	$c = '';

	/* --- Video YouTube dung ti le 16:9 --- */
	if ( ! empty( $o['video'] ) ) {
		$sel  = 'iframe[src*="youtube.com"],iframe[src*="youtube-nocookie.com"],iframe[src*="youtu.be"],';
		$sel .= 'iframe[data-src*="youtube.com"],iframe[src*="player.vimeo.com"]';
		$vg   = max( 0, min( 40, (int) $o['vd_goc'] ) );
		$c   .= $sel . '{width:100% !important;height:auto !important;aspect-ratio:16/9;display:block;border:0;max-width:100%;border-radius:' . $vg . 'px;overflow:hidden}';
		/* Trinh duyet qua cu khong hieu aspect-ratio thi van co chieu cao hop ly */
		$c   .= '@supports not (aspect-ratio:16/9){' . $sel . '{height:56.25vw !important;max-height:70vh}}';
	}

	/* --- Tab kieu vien thuoc --- */
	if ( ! empty( $o['tab'] ) ) {
		$tm  = sanitize_hex_color( $o['tab_mau'] ) ? $o['tab_mau'] : '#2b2b2b';
		$tg  = max( 0, min( 999, (int) $o['tab_goc'] ) );
		$tc  = ( 'giua' === $o['tab_canh'] ) ? 'center' : 'flex-start';
		$c  .= '.smlife-tab-pill ul.nav{border-bottom:0 !important;display:flex;flex-wrap:wrap;gap:9px;justify-content:' . $tc . ';margin:0 0 22px;padding:0}';
		$c  .= '.smlife-tab-pill ul.nav>li{margin:0 !important;border:0 !important}';
		$c  .= '.smlife-tab-pill ul.nav>li>a{display:block;margin:0 !important;padding:9px 19px !important;border:1px solid #e3e3e3 !important;';
		$c  .= 'border-radius:' . $tg . 'px !important;background:#fff;color:#4a4a4a !important;font-size:14px;font-weight:500;line-height:1.3;';
		$c  .= 'letter-spacing:0;text-transform:none;transition:background .15s,border-color .15s,color .15s}';
		$c  .= '.smlife-tab-pill ul.nav>li>a:after,.smlife-tab-pill ul.nav>li>a:before{display:none !important}';
		$c  .= '.smlife-tab-pill ul.nav>li>a:hover{border-color:' . $tm . ' !important;color:' . $tm . ' !important}';
		$c  .= '.smlife-tab-pill ul.nav>li.active>a{background:' . $tm . ' !important;border-color:' . $tm . ' !important;color:#fff !important}';
		$c  .= '@media (max-width:849px){.smlife-tab-pill ul.nav{gap:7px;justify-content:flex-start;flex-wrap:nowrap;overflow-x:auto;';
		$c  .= '-webkit-overflow-scrolling:touch;scrollbar-width:none;padding:0 0 4px}';
		$c  .= '.smlife-tab-pill ul.nav::-webkit-scrollbar{display:none}';
		$c  .= '.smlife-tab-pill ul.nav>li{flex:0 0 auto}.smlife-tab-pill ul.nav>li>a{padding:8px 15px !important;font-size:13.5px}}';
	}

	/* --- Khoi bai viet kieu tap chi --- */
	if ( ! empty( $o['tap_chi'] ) ) {
		$g  = max( 0, min( 30, (int) $o['tc_goc'] ) );
		$tl = preg_match( '#^\d{1,2}/\d{1,2}$#', (string) $o['tc_ti_le'] ) ? $o['tc_ti_le'] : '4/3';
		$c .= '.smlife-tapchi .box-image{border-radius:' . $g . 'px;overflow:hidden}';
		$c .= '.smlife-tapchi .box-image img{display:block;width:100%;height:auto;aspect-ratio:' . $tl . ';object-fit:cover;transition:transform .4s ease}';
		$c .= '.smlife-tapchi .col-inner:hover .box-image img{transform:scale(1.04)}';
		$c .= '.smlife-tapchi .box-text{text-align:left !important;padding:13px 2px 0 !important}';
		$c .= '.smlife-tapchi .post-meta{display:inline-flex;align-items:center;gap:6px;font-size:11.5px;font-weight:600;color:#5a5a5a;';
		$c .= 'background:#f1f2f4;border-radius:999px;padding:5px 11px;margin:0 0 9px;text-transform:none;letter-spacing:0;opacity:1}';
		$c .= '.smlife-tapchi .post-meta a{color:#5a5a5a}';
		$c .= '.smlife-tapchi .post-title{display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden;';
		$c .= 'font-size:17px;font-weight:700;line-height:1.35;color:#2b2b2b;margin:0 0 7px;text-transform:none;letter-spacing:0}';
		$c .= '.smlife-tapchi .post-title a{color:#2b2b2b}';
		$c .= '.smlife-tapchi .col-inner:hover .post-title a{color:' . smlife_mau() . '}';
		$c .= '.smlife-tapchi .from_the_blog_excerpt,.smlife-tapchi .is-divider+p,.smlife-tapchi .box-text p{display:-webkit-box;';
		$c .= '-webkit-line-clamp:3;-webkit-box-orient:vertical;overflow:hidden;font-size:13.5px;line-height:1.6;color:#777;margin:0}';
		$c .= '.smlife-tapchi .is-divider{display:none !important}';
		$c .= '.smlife-tapchi .box-text .button,.smlife-tapchi .more-link{display:none !important}';
		$c .= '@media (max-width:549px){.smlife-tapchi .post-title{font-size:15.5px}.smlife-tapchi .box-text{padding-top:11px !important}}';
	}

	/* --- Tieu de muc tren dien thoai: can giua va nho lai --- */
	if ( ! empty( $o['tieu_de'] ) ) {
		$td = max( 13, min( 34, (float) $o['td_co'] ) );
		$lm = sanitize_hex_color( $o['td_mau'] ) ? $o['td_mau'] : '#ececec';
		/* Chi dong vao tieu de do minh dat trong phan tu Text cua UX Builder,
		   khong dung vao ten san pham hay tieu de bai viet. */
		$b   = 'body:not(.single-product):not(.single-post) .col-inner>.text ';
		$ts  = $b . 'h2,' . $b . 'h3';
		/* May tinh: ep can trai. Giao dien dang co mot rule nao do keo tieu de
		   ve giua nen phai danh dau quan trong moi thang duoc. */
		$c  .= $ts . '{text-align:left}';
		/* Duong ke phan cach duoi tieu de */
		if ( 'khoi' === $o['td_line'] ) {
			/* Mot thanh nho mau thuong hieu dung truoc chu. Dung flex de tren
			   dien thoai ca thanh va chu cung don ve giua, khong bi lech. */
			$c .= $ts . '{display:flex;align-items:center;gap:11px;justify-content:flex-start}';
			$c .= $b . 'h2::before,' . $b . 'h3::before{content:"";flex:0 0 auto;width:5px;height:1em;border-radius:3px;background:' . smlife_mau() . '}';
			$c .= '@media (max-width:849px){' . $ts . '{justify-content:center;gap:9px}}';
		} elseif ( 'mo' === $o['td_line'] ) {
			/* Gach manh nam duoi chu, dam o dau roi tan dan sang phai.
			   Dung rgba cua chinh mau thuong hieu voi do mo 0 thay vi tu
			   "transparent", vi transparent la den trong suot nen vai trinh
			   duyet ve ra mot vet xam o khuc giua. */
			$tan = smlife_mau_rgba( 0 );
			$c  .= $ts . '{padding-bottom:13px;margin-bottom:18px;position:relative}';
			$c  .= $b . 'h2::after,' . $b . 'h3::after{content:"";position:absolute;left:0;bottom:0;width:240px;max-width:60%;height:2px;border-radius:2px;background:linear-gradient(90deg,' . smlife_mau() . ',' . $tan . ')}';
			/* Dien thoai tieu de can giua nen gach cung phai can giua va tan ve hai phia */
			$c  .= '@media (max-width:849px){' . $b . 'h2::after,' . $b . 'h3::after{left:50%;transform:translateX(-50%);width:170px;max-width:70%;';
			$c  .= 'background:linear-gradient(90deg,' . $tan . ',' . smlife_mau() . ',' . $tan . ')}}';
		} elseif ( 'ngang' === $o['td_line'] ) {
			$c .= $ts . '{padding-bottom:12px;margin-bottom:18px;border-bottom:1px solid ' . $lm . '}';
		} elseif ( 'nhan' === $o['td_line'] ) {
			$c .= $ts . '{padding-bottom:12px;margin-bottom:18px;position:relative}';
			$c .= $ts . '::after{content:"";position:absolute;left:0;bottom:0;width:56px;height:3px;border-radius:2px;background:' . smlife_mau() . '}';
			$c .= '@media (max-width:849px){' . $b . 'h2::after,' . $b . 'h3::after{left:50%;transform:translateX(-50%)}}';
		}
		/* Dien thoai: can giua va nho lai */
		$c  .= '@media (max-width:849px){' . $ts . '{text-align:center !important;font-size:' . $td . 'px !important;line-height:1.3 !important}}';
	}

	/* --- The san pham trong cac khoi Product: ten can trai --- */
	if ( ! empty( $o['the_sp'] ) ) {
		$ps  = '.product-small .box-text,.product-small .box-text .name,.product-small .box-text .price,';
		$ps .= '.product-small .box-text .category,.product-small .box-text .star-rating,.product-small .box-text .add-to-cart-button';
		$c  .= $ps . '{text-align:left !important}';
		$c  .= '.product-small .box-text .star-rating{margin-left:0 !important}';
		$c  .= '.product-small .box-text{padding-left:0;padding-right:0}';
	}

	/* --- The bai viet: bo goc anh --- */
	if ( (int) $o['blog_goc'] > 0 ) {
		$bg = max( 0, min( 30, (int) $o['blog_goc'] ) );
		$c .= '.post-item .box-image,.blog-archive .post-item .box-image{border-radius:' . $bg . 'px;overflow:hidden}';
		$c .= '.post-item .box-image img{display:block;width:100%;height:auto}';
	}

	/* --- Nut Tai them trong cac khoi San pham --- */
	if ( ! empty( $o['nut_them'] ) ) {
		$ng  = max( 0, min( 999, (int) $o['nut_goc'] ) );
		$mau = smlife_mau();
		$c  .= '.ux-relay__load-more-button,.ux-relay__load-more-button.primary{background:' . smlife_mau_nhat( 0.9 ) . ' !important;';
		$c  .= 'color:' . $mau . ' !important;border:1px solid ' . smlife_mau_nhat( 0.74 ) . ' !important;border-radius:' . $ng . 'px !important;';
		$c  .= 'box-shadow:none !important;text-transform:none;letter-spacing:0;font-weight:600;font-size:14.5px;min-height:0;padding:12px 28px}';
		$c  .= '.ux-relay__load-more-button:hover,.ux-relay__load-more-button.primary:hover{background:' . $mau . ' !important;color:#fff !important;border-color:' . $mau . ' !important}';
		$c  .= '.ux-relay__control{text-align:center}';
	}

	if ( '' === $c ) { return; }
	echo '<style id="smlife-ts-css">' . $c . '</style>'; // phpcs:ignore
}, 22 );

/* ===== 3. Trang cai dat ===== */

add_action( 'admin_menu', function () {
	add_submenu_page( 'smlife-core', 'Kiểu hiển thị bổ sung', 'Kiểu hiển thị', 'manage_options', 'smlife-kieu-hien-thi', 'smlife_ts_page' );
}, 40 );

function smlife_ts_page() {
	if ( ! current_user_can( 'manage_options' ) ) { return; }
	$o = smlife_ts_opt();
	echo '<div class="wrap smlife-wrap">';
	echo '<div class="smc-hd"><h1>Kiểu hiển thị bổ sung</h1>';
	echo '<p>Ba tinh chỉnh giao diện hay phải làm đi làm lại, gom về một chỗ. Hai mục tab và tạp chí chỉ chạy ở khối nào có gắn lớp CSS tương ứng nên không đụng gì tới các khối khác.</p></div>';
	if ( isset( $_GET['updated'] ) ) { echo '<div class="notice notice-success is-dismissible"><p>Đã lưu.</p></div>'; }

	echo '<div class="smlife-card"><form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '">';
	echo '<input type="hidden" name="action" value="smlife_ts_save" />';
	wp_nonce_field( 'smlife_ts_save', 'smlife_ts_nonce' );
	echo '<table class="form-table">';
	echo '<tr><th>Bật module</th><td><label><input type="checkbox" name="ts[enabled]" value="1" ' . checked( 1, $o['enabled'], false ) . ' /> Áp dụng các tinh chỉnh bên dưới</label></td></tr>';

	echo '<tr><th colspan="2"><h2 style="margin:18px 0 0">1. Video YouTube</h2></th></tr>';
	echo '<tr><th>Bo góc video</th><td><input type="number" min="0" max="40" name="ts[vd_goc]" value="' . esc_attr( $o['vd_goc'] ) . '" class="small-text" /> px</td></tr>';
	echo '<tr><th>Ép đúng tỉ lệ 16:9</th><td><label><input type="checkbox" name="ts[video]" value="1" ' . checked( 1, $o['video'], false ) . ' /> Mọi video YouTube hiện đủ khung hình trên mọi màn hình</label>';
	echo '<br /><span class="description">Iframe dán tay thường bị ghim sẵn <code>height="600"</code>. Trên điện thoại khung chỉ rộng 360px mà cao 600px nên video nhìn như bị cắt. Bật mục này là khỏi phải sửa từng chỗ.</span></td></tr>';

	echo '<tr><th colspan="2"><h2 style="margin:18px 0 0">2. Tab kiểu viên thuốc</h2></th></tr>';
	echo '<tr><th>Bật kiểu tab này</th><td><label><input type="checkbox" name="ts[tab]" value="1" ' . checked( 1, $o['tab'], false ) . ' /> Dùng được ở mọi khối Tabs</label>';
	echo '<br /><span class="description">Mở UX Builder, chọn phần tử <b>Tabs</b>, ô <b>Class</b> điền <code>smlife-tab-pill</code>. Trên điện thoại hàng tab tự trượt ngang.</span></td></tr>';
	echo '<tr><th>Bo góc tab</th><td><input type="number" min="0" max="999" name="ts[tab_goc]" value="' . esc_attr( $o['tab_goc'] ) . '" class="small-text" /> px <span class="description">Để <code>8</code> cho tab chữ nhật bo nhẹ, <code>999</code> cho kiểu viên thuốc.</span></td></tr>';
	echo '<tr><th>Hàng tab căn</th><td><select name="ts[tab_canh]">';
	foreach ( array( 'trai' => 'Căn trái, thẳng hàng với tiêu đề', 'giua' => 'Căn giữa' ) as $k => $lb ) {
		echo '<option value="' . esc_attr( $k ) . '" ' . selected( $o['tab_canh'], $k, false ) . '>' . esc_html( $lb ) . '</option>';
	}
	echo '</select></td></tr>';
	echo '<tr><th>Màu tab đang chọn</th><td><input type="text" name="ts[tab_mau]" value="' . esc_attr( $o['tab_mau'] ) . '" class="regular-text" style="max-width:120px" placeholder="#2b2b2b" /> <span class="description">Mẫu dùng đen đậm. Muốn theo thương hiệu thì để <code>#ee2468</code>.</span></td></tr>';

	echo '<tr><th colspan="2"><h2 style="margin:18px 0 0">3. Khối bài viết kiểu tạp chí</h2></th></tr>';
	echo '<tr><th>Bật kiểu khối này</th><td><label><input type="checkbox" name="ts[tap_chi]" value="1" ' . checked( 1, $o['tap_chi'], false ) . ' /> Dùng cho khối Tạp chí nhà đẹp ngoài trang chủ</label>';
	echo '<br /><span class="description">Mở UX Builder, chọn <b>hàng (Row)</b> chứa khối Blog Posts, ô <b>Class</b> điền <code>smlife-tapchi</code>.</span></td></tr>';
	echo '<tr><th>Bo góc ảnh</th><td><input type="number" min="0" max="30" name="ts[tc_goc]" value="' . esc_attr( $o['tc_goc'] ) . '" class="small-text" /> px</td></tr>';
	echo '<tr><th>Tỉ lệ ảnh</th><td><input type="text" name="ts[tc_ti_le]" value="' . esc_attr( $o['tc_ti_le'] ) . '" class="small-text" placeholder="4/3" /> <span class="description">Ví dụ <code>4/3</code>, <code>3/2</code>, <code>16/9</code>. Ảnh dài ngắn khác nhau vẫn thành một cỡ đều nhau.</span></td></tr>';
	echo '<tr><th colspan="2"><h2 style="margin:18px 0 0">4. Tiêu đề mục trên điện thoại</h2></th></tr>';
	echo '<tr><th>Căn giữa và thu nhỏ</th><td><label><input type="checkbox" name="ts[tieu_de]" value="1" ' . checked( 1, $o['tieu_de'], false ) . ' /> Tiêu đề mục căn trái trên máy tính, tự căn giữa và nhỏ lại trên điện thoại</label>';
	echo '<br /><span class="description">Chỉ áp dụng cho tiêu đề đặt trong phần tử Text của UX Builder, không đụng tới tên sản phẩm hay tiêu đề bài viết.</span></td></tr>';
	echo '<tr><th>Trang trí tiêu đề</th><td><select name="ts[td_line]">';
	foreach ( array( 'mo' => 'Gạch mảnh dưới chữ, đậm đầu tan dần sang phải', 'khoi' => 'Thanh nhỏ màu thương hiệu đứng trước chữ', 'nhan' => 'Gạch ngắn đặc nằm dưới chữ', 'ngang' => 'Đường kẻ ngang hết chiều rộng', 'khong' => 'Không trang trí' ) as $k => $lb ) {
		echo '<option value="' . esc_attr( $k ) . '" ' . selected( $o['td_line'], $k, false ) . '>' . esc_html( $lb ) . '</option>';
	}
	echo '</select></td></tr>';
	echo '<tr><th>Nút Tải thêm</th><td><label><input type="checkbox" name="ts[nut_them]" value="1" ' . checked( 1, $o['nut_them'], false ) . ' /> Đổi nút Tải thêm trong các khối Sản phẩm sang nền hồng nhạt, bo góc</label></td></tr>';
	echo '<tr><th>Bo góc nút Tải thêm</th><td><input type="number" min="0" max="999" name="ts[nut_goc]" value="' . esc_attr( $o['nut_goc'] ) . '" class="small-text" /> px</td></tr>';
	echo '<tr><th>Màu đường kẻ ngang</th><td><input type="text" name="ts[td_mau]" value="' . esc_attr( $o['td_mau'] ) . '" class="regular-text" style="max-width:120px" placeholder="#ececec" /></td></tr>';
	echo '<tr><th>Cỡ chữ trên điện thoại</th><td><input type="number" min="13" max="34" step="0.5" name="ts[td_co]" value="' . esc_attr( $o['td_co'] ) . '" class="small-text" /> px</td></tr>';

	echo '<tr><th colspan="2"><h2 style="margin:18px 0 0">5. Thẻ sản phẩm và thẻ bài viết</h2></th></tr>';
	echo '<tr><th>Thẻ sản phẩm căn trái</th><td><label><input type="checkbox" name="ts[the_sp]" value="1" ' . checked( 1, $o['the_sp'], false ) . ' /> Tên, giá và sao trong các khối Sản phẩm căn trái thay vì căn giữa</label></td></tr>';
	echo '<tr><th>Bo góc ảnh bài viết</th><td><input type="number" min="0" max="30" name="ts[blog_goc]" value="' . esc_attr( $o['blog_goc'] ) . '" class="small-text" /> px <span class="description">Áp cho mọi thẻ bài viết, điền <code>0</code> để tắt.</span></td></tr>';
	echo '</table>';
	submit_button( 'Lưu' );
	echo '</form></div></div>';
}

add_action( 'admin_post_smlife_ts_save', function () {
	if ( ! current_user_can( 'manage_options' ) ) { wp_die( 'Không đủ quyền.' ); }
	check_admin_referer( 'smlife_ts_save', 'smlife_ts_nonce' );
	$in = isset( $_POST['ts'] ) && is_array( $_POST['ts'] ) ? wp_unslash( $_POST['ts'] ) : array();
	$d  = smlife_ts_defaults();
	update_option( 'smlife_ts', array(
		'enabled'  => empty( $in['enabled'] ) ? 0 : 1,
		'video'    => empty( $in['video'] ) ? 0 : 1,
		'tab'      => empty( $in['tab'] ) ? 0 : 1,
		'tab_mau'  => ( isset( $in['tab_mau'] ) && sanitize_hex_color( $in['tab_mau'] ) ) ? sanitize_hex_color( $in['tab_mau'] ) : $d['tab_mau'],
		'tap_chi'  => empty( $in['tap_chi'] ) ? 0 : 1,
		'tc_goc'   => max( 0, min( 30, isset( $in['tc_goc'] ) ? (int) $in['tc_goc'] : 14 ) ),
		'vd_goc'   => max( 0, min( 40, isset( $in['vd_goc'] ) ? (int) $in['vd_goc'] : 14 ) ),
		'tab_goc'  => max( 0, min( 999, isset( $in['tab_goc'] ) ? (int) $in['tab_goc'] : 8 ) ),
		'tab_canh' => ( isset( $in['tab_canh'] ) && 'giua' === $in['tab_canh'] ) ? 'giua' : 'trai',
		'tieu_de'  => empty( $in['tieu_de'] ) ? 0 : 1,
		'td_co'    => max( 13, min( 34, isset( $in['td_co'] ) ? (float) $in['td_co'] : 20 ) ),
		'td_line'  => ( isset( $in['td_line'] ) && in_array( $in['td_line'], array( 'mo', 'khoi', 'ngang', 'nhan', 'khong' ), true ) ) ? $in['td_line'] : 'mo',
		'nut_them' => empty( $in['nut_them'] ) ? 0 : 1,
		'nut_goc'  => max( 0, min( 999, isset( $in['nut_goc'] ) ? (int) $in['nut_goc'] : 10 ) ),
		'td_mau'   => ( isset( $in['td_mau'] ) && sanitize_hex_color( $in['td_mau'] ) ) ? sanitize_hex_color( $in['td_mau'] ) : '#ececec',
		'the_sp'   => empty( $in['the_sp'] ) ? 0 : 1,
		'blog_goc' => max( 0, min( 30, isset( $in['blog_goc'] ) ? (int) $in['blog_goc'] : 14 ) ),
		'tc_ti_le' => ( isset( $in['tc_ti_le'] ) && preg_match( '#^\d{1,2}/\d{1,2}$#', trim( $in['tc_ti_le'] ) ) ) ? trim( $in['tc_ti_le'] ) : $d['tc_ti_le'],
	), true );
	if ( function_exists( 'rocket_clean_domain' ) ) { smlife_xoa_cache(); }
	wp_safe_redirect( admin_url( 'admin.php?page=smlife-kieu-hien-thi&updated=1' ) );
	exit;
} );
