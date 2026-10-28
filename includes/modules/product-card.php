<?php
/**
 * SMLIFE Core - Module: Thẻ sản phẩm
 *
 * Gom nguyen cum "the san pham o trang danh muc" ve mot cho. Truoc day cum
 * nay nam rai o hai snippet:
 *   - snippet 11 (PHP): MODULE 1 tao ra 3 tag va badge tu truong ACF.
 *   - snippet 20 (CSS): mau tag, badge vang, va mot khoi @media mobile ep
 *     co chu tag ve 9px !important.
 * Khoi @media do la ly do tag khong tu thu nho duoc tren dien thoai, nen
 * module nay bo han no va thay bang don vi cqi.
 *
 * Ba viec module nay lam them so voi hien trang:
 *   1. Vien ngoai bo cong, om sat anh - cung be ngang, khong co khoang ho.
 *   2. Gia in dam hon.
 *   2b. Badge goc trai vuong vuc, nho gon, tut xuong ne goc bo cong. Khoang
 *      tut lay theo dung do bo goc nen doi bo goc la badge tu ne theo.
 *   3. Hai tag dau luon nam mot hang, chat thi chu tu thu nho.
 *
 * Ghi chu ky thuat:
 *   - Anh om sat vien: .col-inner de padding 0 va overflow:hidden, goc tren
 *     cua anh bi chinh cai vien cat theo, khong can bo goc rieng cho anh.
 *   - Hai tag mot hang: .price-wrapper thanh luoi 2 cot. Chu trong tag dung
 *     don vi cqi - phan tram be ngang cua CHINH THE san pham - nen the hep
 *     bao nhieu chu cung tu co lai, khong can @media.
 *     Do thuc te: the 300px -> chu 12px, 220px -> 9.2px, 170px -> 8.5px la
 *     cham san. Duoi 168px thi ep tiep se tran ra ngoai vien, nen tu nguong
 *     do cho hai tag xuong dong.
 *   - Tag thu 3 (neu san pham co) nam rieng mot hang ben duoi.
 *   - Chieu cao the van de Flatsome tu can bang nhu cu, module khong dung toi.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/* =====================================================================
 * 1. CAI DAT
 * Mac dinh giu DUNG mau va kich thuoc dang chay, bat module len khong
 * doi mau gi ca, chi them ba viec o tren.
 * ===================================================================== */

function smlife_pc_defaults() {
	return array(
		/* Khung the */
		'vien'    => 1,
		'radius'  => 12,
		'border'  => '#ececec',
		'gap'     => 15,
		'day'     => 20,
		/* Gia */
		'gia_co'  => 17,
		'gia_doi' => 0,
		'gia_mau' => '#ee2468',
		/* Tag - mau y nguyen snippet cu */
		'tag'     => 1,
		'tag_r'   => 11,
		'b1a'     => '#1c4b72', 'b1b' => '#7c6e6c',
		'b2a'     => '#a70101', 'b2b' => '#fb0005',
		'b3a'     => '#f44336', 'b3b' => '#fb0005',
		/* Badge goc trai */
		'badge'   => 1,
		'bg_a'    => '#ffd93c', 'bg_b' => '#ffce04',
		'bg_tx'   => '#000000',
	);
}

function smlife_pc_opt() {
	$o = get_option( 'smlife_pc', array() );
	if ( ! is_array( $o ) ) {
		$o = array();
	}
	return array_merge( smlife_pc_defaults(), $o );
}

function smlife_pc_hex( $v, $fallback ) {
	$c = sanitize_hex_color( $v );
	return $c ? $c : $fallback;
}

/* =====================================================================
 * 2. TAG VA BADGE
 * Chuyen tu MODULE 1 cua snippet 11 sang. Noi dung van lay tu ACF nhu cu,
 * khong doi ten truong nao.
 * ===================================================================== */

add_action( 'woocommerce_after_shop_loop_item_title', 'smlife_pc_tags', 999 );
function smlife_pc_tags() {
	$o = smlife_pc_opt();
	if ( empty( $o['tag'] ) || ! function_exists( 'get_field' ) ) {
		return;
	}
	$map = array(
		'btn_1_pt' => 'button_qua_tang',
		'btn_2_pt' => 'button_tra_gop',
		'btn_3_pt' => 'button_extra',
	);
	foreach ( $map as $cls => $field ) {
		$v = get_field( $field );
		if ( is_string( $v ) && '' !== trim( $v ) ) {
			echo '<div class="' . esc_attr( $cls ) . '">' . esc_html( $v ) . '</div>';
		}
	}
}

add_action( 'woocommerce_before_shop_loop_item', 'smlife_pc_badge', 0 );
function smlife_pc_badge() {
	$o = smlife_pc_opt();
	if ( empty( $o['badge'] ) || ! function_exists( 'get_field' ) ) {
		return;
	}
	$v = get_field( 'button_sale' );
	if ( is_string( $v ) && '' !== trim( $v ) ) {
		echo '<span class="onsale_custom">' . esc_html( $v ) . '</span>';
	}
}

/* =====================================================================
 * 3. CSS RA TRANG
 * ===================================================================== */

add_action( 'wp_head', 'smlife_pc_css', 99 );
function smlife_pc_css() {
	$o   = smlife_pc_opt();
	$css = '';

	/* --- Khung the: vien om sat anh --- */
	$day = max( 4, min( 40, (int) $o['day'] ) );

	if ( ! empty( $o['vien'] ) ) {
		$r  = max( 0, min( 30, (int) $o['radius'] ) );
		$bd = smlife_pc_hex( $o['border'], '#ececec' );
		$css .= '
.product-small.col > .col-inner{
	border:1px solid ' . $bd . ';border-radius:' . $r . 'px;background:#fff;
	padding:0;overflow:hidden;
	transition:border-color .18s ease,box-shadow .18s ease}
.product-small.col > .col-inner:hover{border-color:#dcdcdc;box-shadow:0 6px 18px rgba(0,0,0,.08)}
.product-small .box-image{margin:0;border-radius:0}
.product-small .box-image img{display:block;width:100%;border-radius:0}
.product-small .box-text{padding:11px 11px ' . $day . 'px !important}
';
	}

	/* Thu hep khe giua cac the de lay them dien tich cho anh.
	   Phai chinh ca le am cua hang lan dem cua cot, khong thi mep hang lech. */
	$gap = max( 0, min( 30, (int) $o['gap'] ) );
	$half = $gap / 2;
	$css .= '
.products.row{margin-left:-' . $half . 'px;margin-right:-' . $half . 'px}
.products.row > .product-small.col{
	padding-left:' . $half . 'px;padding-right:' . $half . 'px;padding-bottom:' . $gap . 'px}
';

	/* container-type phai co du co bat vien hay khong, vi don vi cqi
	   cua tag dua vao no. */
	$css .= '
.product-small.col > .col-inner{container-type:inline-size}
';

	/* --- Hai tag dau luon mot hang --- */
	$css .= '
.product-small .price-wrapper{
	display:grid;grid-template-columns:auto 1fr;align-items:center;column-gap:6px}
.product-small .price-wrapper > .star-rating{grid-column:1/-1;float:none;margin:0 0 6px}
.product-small .price-wrapper > .price{grid-column:1/-1;margin:0 0 9px}
.product-small .btn_1_pt{grid-column:1;justify-self:start}
.product-small .btn_2_pt{grid-column:2;justify-self:start}
.product-small .btn_3_pt{grid-column:1/-1;justify-self:start;margin-top:5px !important}

/* The qua hep thi chu da cham san 8.5px, ep tiep la tag tran ra ngoai vien.
   Den nguong do thi cho hai tag xuong dong, tran van xau hon xuong dong. */
@container (max-width:168px){
	.product-small .btn_1_pt,
	.product-small .btn_2_pt{grid-column:1/-1;justify-self:start}
	.product-small .btn_2_pt{margin-top:5px !important}}
';

	/* --- Gia in dam --- */
	$gs   = max( 12, min( 26, (int) $o['gia_co'] ) );
	$css .= '
.product-small .price{display:flex;align-items:baseline;flex-wrap:wrap;gap:5px}
.product-small .price .gia-tu-do-dam{font-size:clamp(9.5px,4.4cqi,12px);font-weight:400}
.product-small .price > ins,
.product-small .price > .amount,
.product-small .price > ins .amount{
	text-decoration:none;font-size:clamp(13px,6.4cqi,' . $gs . 'px);font-weight:800;letter-spacing:-.2px}
.product-small .price del,
.product-small .price del .amount{font-size:clamp(9.5px,4.2cqi,12px);font-weight:400}
.product-small .price .screen-reader-text{display:none}
@container (max-width:150px){.product-small .price .gia-tu-do-dam{display:none}}
';
	if ( ! empty( $o['gia_doi'] ) ) {
		$gc   = smlife_pc_hex( $o['gia_mau'], '#ee2468' );
		$css .= '
.product-small .price > ins,
.product-small .price > .amount,
.product-small .price > ins .amount{color:' . $gc . '}
';
	}

	/* --- Tag: co chu tu co theo be ngang the, thay cho khoi @media cu --- */
	if ( ! empty( $o['tag'] ) ) {
		$tr   = max( 0, min( 20, (int) $o['tag_r'] ) );
		$css .= '
.product-small .btn_1_pt,
.product-small .btn_2_pt,
.product-small .btn_3_pt{
	display:inline-block;color:#fff;font-weight:600;line-height:1.35;
	white-space:nowrap;max-width:100%;margin:0;border-radius:' . $tr . 'px;
	font-size:clamp(8.5px,4.2cqi,12px);
	padding:clamp(3px,1.5cqi,5px) clamp(5px,2.8cqi,9px)}
.product-small .btn_1_pt{background:linear-gradient(90deg,' . smlife_pc_hex( $o['b1a'], '#1c4b72' ) . ' 4.5%,' . smlife_pc_hex( $o['b1b'], '#7c6e6c' ) . ' 90.3%)}
.product-small .btn_2_pt{background:linear-gradient(90deg,' . smlife_pc_hex( $o['b2a'], '#a70101' ) . ' 4.5%,' . smlife_pc_hex( $o['b2b'], '#fb0005' ) . ' 90.3%)}
.product-small .btn_3_pt{background:linear-gradient(90deg,' . smlife_pc_hex( $o['b3a'], '#f44336' ) . ' 4.5%,' . smlife_pc_hex( $o['b3b'], '#fb0005' ) . ' 90.3%)}
';
	}

	/* --- Badge goc tren trai --- */
	if ( ! empty( $o['badge'] ) ) {
		$a    = smlife_pc_hex( $o['bg_a'], '#ffd93c' );
		$b    = smlife_pc_hex( $o['bg_b'], '#ffce04' );
		$tx   = smlife_pc_hex( $o['bg_tx'], '#000000' );
		$css .= '
.onsale_custom{
	position:absolute;z-index:2;top:5px;left:-7px;
	background:linear-gradient(90deg,' . $a . ' 0%,' . $b . ' 100%);
	color:' . $tx . ';font-weight:bold;font-size:14px;
	padding:2px 2px 2px 12px;border-radius:10px 0 0 0;will-change:transform}
.onsale_custom:before{
	content:"";position:absolute;width:7px;height:7px;bottom:-7px;left:0;z-index:1;
	background:' . $b . ';filter:brightness(.8);border-radius:10px 0 0 10px}
';
		/* Co vien bo goc thi badge phai keo vao trong va tut xuong, khong thi
		   goc bo cong cat mat mot mieng. Khoang tut lay theo dung do bo goc,
		   doi bo goc thi badge tu ne theo, khong phai chinh tay. */
		if ( ! empty( $o['vien'] ) ) {
			$top  = max( 6, min( 30, (int) $o['radius'] ) + 2 );
			$css .= '
.product-small.col > .col-inner .onsale_custom{
	top:' . $top . 'px;left:0;
	font-size:11px;font-weight:700;line-height:1.35;letter-spacing:.2px;
	padding:4px 9px 4px 10px;border-radius:0 4px 4px 0}
.product-small.col > .col-inner .onsale_custom:before{display:none}
';
		}
	}

	echo "\n<style id=\"smlife-pc-css\">" . smlife_pc_min( $css ) . "</style>\n";
}

function smlife_pc_min( $css ) {
	$css = preg_replace( '#/\*.*?\*/#s', '', $css );
	$css = preg_replace( '/\s*\n\s*/', '', $css );
	$css = preg_replace( '/\s*([{}:;,])\s*/', '$1', $css );
	return trim( $css );
}

/* =====================================================================
 * 4. TRANG QUAN TRI
 * ===================================================================== */

add_action( 'admin_menu', 'smlife_pc_menu', 62 );
function smlife_pc_menu() {
	add_submenu_page(
		'smlife-core',
		'Thẻ sản phẩm',
		'Thẻ sản phẩm',
		'manage_options',
		'smlife-the-san-pham',
		'smlife_pc_page'
	);
}

function smlife_pc_row_grad( $label, $ka, $kb, $o, $note = '' ) {
	echo '<tr><th scope="row">' . esc_html( $label ) . '</th><td>';
	echo '<input type="color" name="' . esc_attr( $ka ) . '" value="' . esc_attr( $o[ $ka ] ) . '" /> ';
	echo '<input type="color" name="' . esc_attr( $kb ) . '" value="' . esc_attr( $o[ $kb ] ) . '" />';
	if ( '' !== $note ) {
		echo '<p class="description">' . wp_kses_post( $note ) . '</p>';
	}
	echo '</td></tr>';
}

function smlife_pc_page() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}
	$o   = smlife_pc_opt();
	$acf = function_exists( 'get_field' );

	echo '<div class="wrap smlife-wrap smlife-pc-wrap">';
	echo '<div class="smc-hd"><h1>Thẻ sản phẩm</h1>';
	echo '<p>Toàn bộ phần thẻ sản phẩm ở trang danh mục, trang tìm kiếm và các khối gợi ý: khung thẻ, giá, 3 tag và badge góc trái. Trước đây nằm rải ở hai snippet, nay gom về đây một chỗ.</p>';
	echo '</div>';

	if ( isset( $_GET['saved'] ) ) {
		echo '<div class="notice notice-success is-dismissible"><p>Đã lưu.</p></div>';
	}
	if ( ! $acf ) {
		echo '<div class="notice notice-warning"><p>Không tìm thấy ACF. Tag và badge lấy nội dung từ trường ACF của từng sản phẩm, thiếu ACF thì hai thứ đó không hiện.</p></div>';
	}

	echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '">';
	echo '<input type="hidden" name="action" value="smlife_pc_save" />';
	wp_nonce_field( 'smlife_pc_save', 'smlife_pc_nonce' );

	echo '<div class="smlife-card"><h2>Khung thẻ</h2><div class="in"><table class="form-table" role="presentation"><tbody>';
	echo '<tr><th scope="row">Viền bo góc</th><td>';
	echo '<label><input type="checkbox" name="vien" value="1" ' . checked( 1, (int) $o['vien'], false ) . ' /> Bật</label>';
	echo '<p class="description">Viền ôm sát ảnh, cùng bề ngang, không có khoảng hở. Góc trên của ảnh bị chính cái viền cắt theo.</p></td></tr>';
	echo '<tr><th scope="row">Độ bo góc</th><td>';
	echo '<input type="number" name="radius" min="0" max="30" value="' . esc_attr( $o['radius'] ) . '" class="small-text" /> px</td></tr>';
	echo '<tr><th scope="row">Màu viền</th><td>';
	echo '<input type="color" name="border" value="' . esc_attr( $o['border'] ) . '" />';
	echo '<p class="description">Để nhạt thôi. Viền đậm làm trang rối, mà bề dày dưới 1px thì màn hình thường không vẽ được, nên muốn mỏng hơn thì làm nhạt màu chứ không giảm bề dày.</p></td></tr>';
	echo '<tr><th scope="row">Khe giữa các thẻ</th><td>';
	echo '<input type="number" name="gap" min="0" max="30" value="' . esc_attr( $o['gap'] ) . '" class="small-text" /> px';
	echo '<p class="description">Khe càng hẹp thì ảnh sản phẩm càng to. Mặc định 15px, giao diện gốc của Flatsome là 20px.</p></td></tr>';
	echo '<tr><th scope="row">Khoảng hở dưới tag</th><td>';
	echo '<input type="number" name="day" min="4" max="40" value="' . esc_attr( $o['day'] ) . '" class="small-text" /> px';
	echo '<p class="description">Khoảng trống từ hàng tag xuống đáy thẻ.</p></td></tr>';
	echo '</tbody></table></div></div>';

	echo '<div class="smlife-card"><h2>Giá</h2><div class="in"><table class="form-table" role="presentation"><tbody>';
	echo '<tr><th scope="row">Cỡ chữ tối đa</th><td>';
	echo '<input type="number" name="gia_co" min="12" max="26" value="' . esc_attr( $o['gia_co'] ) . '" class="small-text" /> px';
	echo '<p class="description">Giá luôn in đậm. Thẻ hẹp thì giá tự thu nhỏ dần chứ không xuống dòng giữa chừng.</p></td></tr>';
	echo '<tr><th scope="row">Đổi màu giá</th><td>';
	echo '<label><input type="checkbox" name="gia_doi" value="1" ' . checked( 1, (int) $o['gia_doi'], false ) . ' /> Dùng màu riêng</label> ';
	echo '<input type="color" name="gia_mau" value="' . esc_attr( $o['gia_mau'] ) . '" />';
	echo '<p class="description">Không tick thì giữ nguyên màu giá web đang dùng.</p></td></tr>';
	echo '</tbody></table></div></div>';

	echo '<div class="smlife-card"><h2>Tag dưới giá</h2><div class="in"><table class="form-table" role="presentation"><tbody>';
	echo '<tr><th scope="row">Hiện tag</th><td>';
	echo '<label><input type="checkbox" name="tag" value="1" ' . checked( 1, (int) $o['tag'], false ) . ' /> Bật</label>';
	echo '<p class="description">Nội dung lấy từ trường ACF của từng sản phẩm: <code>button_qua_tang</code>, <code>button_tra_gop</code>, <code>button_extra</code>. Sản phẩm nào bỏ trống thì không hiện tag đó.</p>';
	echo '<p class="description">Hai tag đầu luôn nằm trên một hàng, thẻ bị bóp hẹp thì chữ tự thu nhỏ. Tag thứ 3 nằm riêng một hàng bên dưới.</p></td></tr>';
	echo '<tr><th scope="row">Độ bo góc tag</th><td>';
	echo '<input type="number" name="tag_r" min="0" max="20" value="' . esc_attr( $o['tag_r'] ) . '" class="small-text" /> px</td></tr>';
	smlife_pc_row_grad( 'Màu tag 1', 'b1a', 'b1b', $o, 'Quà tặng / Freeship. Màu trái sang màu phải.' );
	smlife_pc_row_grad( 'Màu tag 2', 'b2a', 'b2b', $o, 'Trả góp / Trả trước.' );
	smlife_pc_row_grad( 'Màu tag 3', 'b3a', 'b3b', $o, 'Tag phụ, ít sản phẩm dùng tới.' );
	echo '</tbody></table></div></div>';

	echo '<div class="smlife-card"><h2>Badge góc trên trái</h2><div class="in"><table class="form-table" role="presentation"><tbody>';
	echo '<tr><th scope="row">Hiện badge</th><td>';
	echo '<label><input type="checkbox" name="badge" value="1" ' . checked( 1, (int) $o['badge'], false ) . ' /> Bật</label>';
	echo '<p class="description">Nội dung lấy từ trường ACF <code>button_sale</code>. Bỏ tick là tắt hẳn cả thẻ lẫn CSS.</p></td></tr>';
	smlife_pc_row_grad( 'Màu nền', 'bg_a', 'bg_b', $o, 'Mặc định là màu vàng đang dùng.' );
	echo '<tr><th scope="row">Màu chữ</th><td><input type="color" name="bg_tx" value="' . esc_attr( $o['bg_tx'] ) . '" /></td></tr>';
	echo '</tbody></table></div></div>';

	submit_button( 'Lưu thay đổi' );
	echo '</form></div>';
}

/* =====================================================================
 * 5. LUU
 * ===================================================================== */

add_action( 'admin_post_smlife_pc_save', function () {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( 'Không đủ quyền.' );
	}
	check_admin_referer( 'smlife_pc_save', 'smlife_pc_nonce' );

	$d   = smlife_pc_defaults();
	$new = array(
		'vien'    => empty( $_POST['vien'] ) ? 0 : 1,
		'tag'     => empty( $_POST['tag'] ) ? 0 : 1,
		'badge'   => empty( $_POST['badge'] ) ? 0 : 1,
		'gia_doi' => empty( $_POST['gia_doi'] ) ? 0 : 1,
		'radius'  => isset( $_POST['radius'] ) ? max( 0, min( 30, (int) $_POST['radius'] ) ) : $d['radius'],
		'tag_r'   => isset( $_POST['tag_r'] ) ? max( 0, min( 20, (int) $_POST['tag_r'] ) ) : $d['tag_r'],
		'gia_co'  => isset( $_POST['gia_co'] ) ? max( 12, min( 26, (int) $_POST['gia_co'] ) ) : $d['gia_co'],
		'gap'     => isset( $_POST['gap'] ) ? max( 0, min( 30, (int) $_POST['gap'] ) ) : $d['gap'],
		'day'     => isset( $_POST['day'] ) ? max( 4, min( 40, (int) $_POST['day'] ) ) : $d['day'],
	);
	$colors = array( 'border', 'gia_mau', 'b1a', 'b1b', 'b2a', 'b2b', 'b3a', 'b3b', 'bg_a', 'bg_b', 'bg_tx' );
	foreach ( $colors as $k ) {
		$v         = isset( $_POST[ $k ] ) ? sanitize_hex_color( wp_unslash( $_POST[ $k ] ) ) : '';
		$new[ $k ] = $v ? $v : $d[ $k ];
	}

	update_option( 'smlife_pc', $new, true );

	smlife_xoa_cache( 'Thẻ sản phẩm' );

	wp_safe_redirect( admin_url( 'admin.php?page=smlife-the-san-pham&saved=1' ) );
	exit;
}, 14 );
