<?php
/**
 * SMLIFE Core - Module: Danh gia khach hang
 *
 * Khoi "khach hang noi gi" dat o trang chu hoac bat ky trang nao, dung
 * shortcode [smlife_danh_gia]. Moi danh gia gom anh thuc te khach gui,
 * so sao, noi dung va ten khach. Nhap het trong admin, khong dung plugin
 * danh gia ngoai nen khong keo theo script la.
 *
 * Hai kieu bay: luoi (desktop chia cot, mobile truot ngang) hoac truot
 * ngang o moi kich co man hinh.
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

/* ===== 1. Cai dat ===== */

function smlife_dg_defaults() {
	return array(
		'enabled' => 1,
		'khung'   => 0,
		'tieu_de' => '1000+ đánh giá từ khách hàng',
		'mo_ta'   => '',
		'nen'     => '#fce4ec',
		'the_nen' => '#ffffff',
		'sao_mau' => '#f5a623',
		'cot'     => 4,
		'kieu'    => 'luoi',
		'goc'     => 12,
		'items'   => array(),
	);
}

function smlife_dg_opt() {
	$o = get_option( 'smlife_dg', array() );
	$o = is_array( $o ) ? array_merge( smlife_dg_defaults(), $o ) : smlife_dg_defaults();
	if ( ! is_array( $o['items'] ) ) { $o['items'] = array(); }
	return $o;
}

function smlife_dg_rows() {
	$o   = smlife_dg_opt();
	$out = array();
	foreach ( $o['items'] as $r ) {
		if ( ! is_array( $r ) || empty( $r['on'] ) ) { continue; }
		$nd = isset( $r['nd'] ) ? trim( (string) $r['nd'] ) : '';
		if ( '' === $nd ) { continue; }
		$out[] = array(
			'anh' => isset( $r['anh'] ) ? trim( (string) $r['anh'] ) : '',
			'sao' => max( 1, min( 5, isset( $r['sao'] ) ? (int) $r['sao'] : 5 ) ),
			'nd'  => $nd,
			'ten' => isset( $r['ten'] ) ? trim( (string) $r['ten'] ) : '',
		);
	}
	return $out;
}

function smlife_dg_stars( $n, $mau ) {
	$h = '<span class="smlife-dg-sao" aria-label="' . (int) $n . ' trên 5 sao">';
	for ( $i = 1; $i <= 5; $i++ ) {
		$fill = ( $i <= $n ) ? $mau : '#dcdcdc';
		$h   .= '<svg width="14" height="14" viewBox="0 0 24 24" fill="' . esc_attr( $fill ) . '" aria-hidden="true">';
		$h   .= '<path d="M12 2.6l2.9 5.9 6.5.9-4.7 4.6 1.1 6.5-5.8-3-5.8 3 1.1-6.5L2.6 9.4l6.5-.9z"/></svg>';
	}
	return $h . '</span>';
}

/* ===== 2. Shortcode ===== */

add_shortcode( 'smlife_danh_gia', 'smlife_dg_sc' );
function smlife_dg_sc( $atts ) {
	$o = smlife_dg_opt();
	if ( empty( $o['enabled'] ) ) { return ''; }
	$rows = smlife_dg_rows();
	if ( ! $rows ) { return ''; }
	$a = shortcode_atts( array( 'tieu_de' => '', 'mo_ta' => '', 'khung' => '' ), $atts, 'smlife_danh_gia' );
	/* khung = 0: chi in dai the danh gia, de tu dat trong khoi cua UX Builder
	   (tieu de va nen hong tu lam ben ngoai). khung = 1: in ca nen va tieu de. */
	$khung = ( '' !== $a['khung'] ) ? ( '1' === (string) $a['khung'] ) : ! empty( $o['khung'] );

	$tieu_de = '' !== trim( $a['tieu_de'] ) ? $a['tieu_de'] : $o['tieu_de'];
	$mo_ta   = '' !== trim( $a['mo_ta'] ) ? $a['mo_ta'] : $o['mo_ta'];
	$mau     = sanitize_hex_color( $o['sao_mau'] ) ? $o['sao_mau'] : '#f5a623';

	$GLOBALS['smlife_dg_need'] = true;

	$h  = '<div class="smlife-dg' . ( 'truot' === $o['kieu'] ? ' kieu-truot' : '' ) . ( $khung ? '' : ' tran' ) . '">';
	$h .= '<div class="smlife-dg-in">';
	if ( $khung && '' !== trim( (string) $tieu_de ) ) {
		$h .= '<h2 class="smlife-dg-t">' . esc_html( $tieu_de ) . '</h2>';
	}
	if ( $khung && '' !== trim( (string) $mo_ta ) ) {
		$h .= '<p class="smlife-dg-m">' . esc_html( $mo_ta ) . '</p>';
	}
	$h .= '<div class="smlife-dg-ds">';
	foreach ( $rows as $r ) {
		$h .= '<div class="smlife-dg-c">';
		if ( '' !== $r['anh'] ) {
			$h .= '<span class="anh"><img src="' . esc_url( $r['anh'] ) . '" alt="' . esc_attr( $r['ten'] ? 'Đánh giá của ' . $r['ten'] : 'Ảnh khách hàng gửi' ) . '" loading="lazy" /></span>';
		}
		$h .= '<div class="noi">';
		$h .= smlife_dg_stars( $r['sao'], $mau );
		$h .= '<p class="nd">' . esc_html( $r['nd'] ) . '</p>';
		if ( '' !== $r['ten'] ) {
			$h .= '<p class="ten">' . esc_html( $r['ten'] ) . '</p>';
		}
		$h .= '</div></div>';
	}
	$h .= '</div></div></div>';
	return $h;
}

/* ===== 3. CSS ===== */

add_action( 'wp_footer', function () {
	if ( empty( $GLOBALS['smlife_dg_need'] ) ) { return; }
	$o   = smlife_dg_opt();
	$nen = sanitize_hex_color( $o['nen'] ) ? $o['nen'] : '#fce4ec';
	$tn  = sanitize_hex_color( $o['the_nen'] ) ? $o['the_nen'] : '#ffffff';
	$cot = max( 2, min( 5, (int) $o['cot'] ) );
	$goc = max( 0, min( 24, (int) $o['goc'] ) );
	$gap = 16;
	$fb  = 'calc((100% - ' . ( ( $cot - 1 ) * $gap ) . 'px)/' . $cot . ')';

	$c  = '.smlife-dg{background:' . $nen . ';padding:38px 0 42px;margin:0}';
	$c .= '.smlife-dg.tran{background:none;padding:0;margin:0}';
	$c .= '.smlife-dg.tran .smlife-dg-in{max-width:none;padding:0}';
	$c .= '.smlife-dg-in{max-width:1180px;margin:0 auto;padding:0 20px}';
	$c .= '.smlife-dg-t{font-size:25px;font-weight:700;color:#2b2b2b;line-height:1.3;margin:0 0 6px;text-transform:none;letter-spacing:0}';
	$c .= '.smlife-dg-m{font-size:14.5px;color:#6a6a6a;line-height:1.55;margin:0 0 20px;max-width:720px}';
	$c .= '.smlife-dg-t+.smlife-dg-ds{margin-top:20px}';
	$c .= '.smlife-dg-ds{display:flex;flex-wrap:wrap;row-gap:' . $gap . 'px;gap:' . $gap . 'px;align-items:stretch}';
	$c .= '.smlife-dg-c{flex:0 0 ' . $fb . ';max-width:' . $fb . ';background:' . $tn . ';border-radius:' . $goc . 'px;overflow:hidden;display:flex;flex-direction:column}';
	$c .= '.smlife-dg-c .anh{display:block}';
	$c .= '.smlife-dg-c .anh img{display:block;width:100%;height:auto;aspect-ratio:1/1;object-fit:cover}';
	$c .= '.smlife-dg-c .noi{padding:13px 15px 16px;display:flex;flex-direction:column;flex:1 1 auto}';
	$c .= '.smlife-dg-sao{display:flex;gap:2px;margin:0 0 8px}';
	$c .= '.smlife-dg-sao svg{display:block;flex:0 0 auto}';
	$c .= '.smlife-dg-c .nd{font-size:13px;line-height:1.6;color:#5a5a5a;margin:0 0 12px;flex:1 1 auto}';
	$c .= '.smlife-dg-c .ten{font-size:13px;font-weight:700;color:#2b2b2b;margin:0}';
	/* Truot ngang */
	$c .= '.smlife-dg.kieu-truot .smlife-dg-ds{flex-wrap:nowrap;overflow-x:auto;scroll-snap-type:x mandatory;';
	$c .= '-webkit-overflow-scrolling:touch;scrollbar-width:thin;scrollbar-color:rgba(0,0,0,.18) transparent;padding-bottom:8px}';
	$c .= '.smlife-dg.kieu-truot .smlife-dg-c{scroll-snap-align:start}';
	$c .= '.smlife-dg-ds::-webkit-scrollbar{height:6px}';
	$c .= '.smlife-dg-ds::-webkit-scrollbar-thumb{background:rgba(0,0,0,.18);border-radius:3px}';
	$c .= '.smlife-dg-ds::-webkit-scrollbar-track{background:transparent}';
	/* Mobile: luon truot ngang, thay 1.6 the */
	$c .= '@media (max-width:849px){.smlife-dg{padding:28px 0 30px}.smlife-dg-in{padding:0 14px}.smlife-dg-t{font-size:20px}';
	$c .= '.smlife-dg-ds{flex-wrap:nowrap;overflow-x:auto;scroll-snap-type:x mandatory;-webkit-overflow-scrolling:touch;padding-bottom:8px;gap:12px}';
	$c .= '.smlife-dg-c{flex:0 0 calc((100% - 12px)/1.75);max-width:calc((100% - 12px)/1.75);scroll-snap-align:start}}';
	echo '<style id="smlife-dg-css">' . $c . '</style>'; // phpcs:ignore
}, 94 );

/* ===== 4. Trang quan tri ===== */

add_action( 'admin_menu', function () {
	add_submenu_page( 'smlife-core', 'Đánh giá khách hàng', 'Đánh giá khách hàng', 'manage_options', 'smlife-danh-gia', 'smlife_dg_page' );
}, 42 );

add_action( 'admin_enqueue_scripts', function ( $hook ) {
	if ( false !== strpos( (string) $hook, 'smlife-danh-gia' ) ) {
		wp_enqueue_media();
	}
} );

function smlife_dg_page() {
	if ( ! current_user_can( 'manage_options' ) ) { return; }
	$o    = smlife_dg_opt();
	$rows = $o['items'];
	for ( $i = count( $rows ); $i < 16; $i++ ) {
		$rows[] = array( 'on' => 0, 'anh' => '', 'sao' => 5, 'nd' => '', 'ten' => '' );
	}

	echo '<div class="wrap smlife-wrap">';
	echo '<div class="smc-hd"><h1>Đánh giá khách hàng</h1>';
	echo '<p>Khối đánh giá đặt bằng shortcode <code>[smlife_danh_gia]</code>. Mỗi dòng là một đánh giá: ảnh khách gửi, số sao, nội dung và tên khách. Dòng nào không tick Hiện thì bỏ qua.</p></div>';
	if ( isset( $_GET['updated'] ) ) { echo '<div class="notice notice-success is-dismissible"><p>Đã lưu.</p></div>'; }

	echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '">';
	echo '<input type="hidden" name="action" value="smlife_dg_save" />';
	wp_nonce_field( 'smlife_dg_save', 'smlife_dg_nonce' );

	echo '<div class="smlife-card"><h2>Cài đặt chung</h2><div class="in"><table class="form-table">';
	echo '<tr><th>Bật khối</th><td><label><input type="checkbox" name="dg[enabled]" value="1" ' . checked( 1, $o['enabled'], false ) . ' /> Hiện khối đánh giá ở nơi có đặt shortcode</label></td></tr>';
	echo '<tr><th>Khung bao ngoài</th><td><label><input type="checkbox" name="dg[khung]" value="1" ' . checked( 1, $o['khung'], false ) . ' /> Tự vẽ nền màu và tiêu đề quanh dải thẻ</label>';
	echo '<br /><span class="description">Tắt (mặc định) thì shortcode chỉ in ra dải thẻ đánh giá, để bạn tự đặt tiêu đề và nền trong UX Builder. Bật thì khối tự có nền và tiêu đề như cài bên dưới.</span></td></tr>';
	echo '<tr><th>Tiêu đề</th><td><input type="text" name="dg[tieu_de]" value="' . esc_attr( $o['tieu_de'] ) . '" class="regular-text" /></td></tr>';
	echo '<tr><th>Dòng mô tả</th><td><input type="text" name="dg[mo_ta]" value="' . esc_attr( $o['mo_ta'] ) . '" class="large-text" /> <span class="description">Để trống thì bỏ hẳn dòng này.</span></td></tr>';
	echo '<tr><th>Màu nền khối</th><td><input type="text" name="dg[nen]" value="' . esc_attr( $o['nen'] ) . '" class="regular-text" style="max-width:120px" placeholder="#fce4ec" /> <span class="description">Mẫu dùng hồng nhạt. Để <code>#ffffff</code> nếu muốn nền trắng.</span></td></tr>';
	echo '<tr><th>Màu nền thẻ</th><td><input type="text" name="dg[the_nen]" value="' . esc_attr( $o['the_nen'] ) . '" class="regular-text" style="max-width:120px" placeholder="#ffffff" /></td></tr>';
	echo '<tr><th>Màu ngôi sao</th><td><input type="text" name="dg[sao_mau]" value="' . esc_attr( $o['sao_mau'] ) . '" class="regular-text" style="max-width:120px" placeholder="#f5a623" /></td></tr>';
	echo '<tr><th>Số thẻ trên một hàng</th><td><input type="number" min="2" max="5" name="dg[cot]" value="' . esc_attr( $o['cot'] ) . '" class="small-text" /> <span class="description">Trên máy tính. Điện thoại luôn trượt ngang.</span></td></tr>';
	echo '<tr><th>Bo góc thẻ</th><td><input type="number" min="0" max="24" name="dg[goc]" value="' . esc_attr( $o['goc'] ) . '" class="small-text" /> px</td></tr>';
	echo '<tr><th>Kiểu bày</th><td><select name="dg[kieu]">';
	foreach ( array( 'luoi' => 'Lưới - máy tính chia cột, điện thoại trượt ngang', 'truot' => 'Trượt ngang ở mọi màn hình' ) as $k => $lb ) {
		echo '<option value="' . esc_attr( $k ) . '" ' . selected( $o['kieu'], $k, false ) . '>' . esc_html( $lb ) . '</option>';
	}
	echo '</select></td></tr>';
	echo '</table></div></div>';

	echo '<div class="smlife-card"><h2>Danh sách đánh giá</h2><div class="in">';
	echo '<table class="widefat striped smlife-dg-tb"><thead><tr>';
	echo '<th style="width:52px">Hiện</th><th style="width:290px">Ảnh khách gửi</th><th style="width:72px">Sao</th><th>Nội dung đánh giá</th><th style="width:150px">Tên khách</th>';
	echo '</tr></thead><tbody>';
	foreach ( $rows as $i => $r ) {
		$anh = isset( $r['anh'] ) ? $r['anh'] : '';
		echo '<tr>';
		echo '<td><input type="checkbox" name="dg[items][' . (int) $i . '][on]" value="1" ' . checked( 1, ! empty( $r['on'] ) ? 1 : 0, false ) . ' /></td>';
		echo '<td><input type="text" class="dg-anh widefat" name="dg[items][' . (int) $i . '][anh]" value="' . esc_attr( $anh ) . '" placeholder="Dán link ảnh hoặc bấm Chọn ảnh" />';
		echo '<button type="button" class="button button-small dg-pick" style="margin-top:4px">Chọn ảnh</button> ';
		echo '<img class="dg-xem" src="' . esc_url( $anh ) . '" style="' . ( $anh ? 'display:inline-block;' : 'display:none;' ) . 'width:40px;height:40px;object-fit:cover;border-radius:5px;vertical-align:middle;margin-left:7px" /></td>';
		echo '<td><select name="dg[items][' . (int) $i . '][sao]">';
		for ( $s = 5; $s >= 1; $s-- ) {
			echo '<option value="' . $s . '" ' . selected( isset( $r['sao'] ) ? (int) $r['sao'] : 5, $s, false ) . '>' . $s . '</option>';
		}
		echo '</select></td>';
		echo '<td><textarea name="dg[items][' . (int) $i . '][nd]" rows="3" class="widefat" placeholder="Nội dung khách viết">' . esc_textarea( isset( $r['nd'] ) ? $r['nd'] : '' ) . '</textarea></td>';
		echo '<td><input type="text" class="widefat" name="dg[items][' . (int) $i . '][ten]" value="' . esc_attr( isset( $r['ten'] ) ? $r['ten'] : '' ) . '" placeholder="Chị Lan, Q7" /></td>';
		echo '</tr>';
	}
	echo '</tbody></table>';
	echo '<p class="description" style="margin-top:10px">Ảnh nên là ảnh vuông hoặc gần vuông, khối tự cắt về khung vuông cho đều nhau. Ảnh khách gửi qua Zalo tải lên Thư viện rồi chọn ở đây là được.</p>';
	echo '</div></div>';

	submit_button( 'Lưu đánh giá' );
	echo '</form>';

	echo '<script>(function(){document.querySelectorAll(".dg-pick").forEach(function(b){b.addEventListener("click",function(e){';
	echo 'e.preventDefault();var td=this.closest("td"),f=td.querySelector(".dg-anh"),im=td.querySelector(".dg-xem");';
	echo 'if(!window.wp||!window.wp.media)return;var fr=wp.media({title:"Chọn ảnh đánh giá",multiple:false});';
	echo 'fr.on("select",function(){var a=fr.state().get("selection").first().toJSON();if(f)f.value=a.url;';
	echo 'if(im){im.src=a.url;im.style.display="inline-block";}});fr.open();});});})();</script>';
	echo '</div>';
}

add_action( 'admin_post_smlife_dg_save', function () {
	if ( ! current_user_can( 'manage_options' ) ) { wp_die( 'Không đủ quyền.' ); }
	check_admin_referer( 'smlife_dg_save', 'smlife_dg_nonce' );
	$in = isset( $_POST['dg'] ) && is_array( $_POST['dg'] ) ? wp_unslash( $_POST['dg'] ) : array();
	$d  = smlife_dg_defaults();

	$items = array();
	if ( isset( $in['items'] ) && is_array( $in['items'] ) ) {
		foreach ( $in['items'] as $r ) {
			if ( ! is_array( $r ) ) { continue; }
			$nd  = isset( $r['nd'] ) ? sanitize_textarea_field( $r['nd'] ) : '';
			$anh = isset( $r['anh'] ) ? esc_url_raw( trim( $r['anh'] ) ) : '';
			$ten = isset( $r['ten'] ) ? sanitize_text_field( $r['ten'] ) : '';
			if ( '' === $nd && '' === $anh && '' === $ten ) { continue; }
			$items[] = array(
				'on'  => empty( $r['on'] ) ? 0 : 1,
				'anh' => $anh,
				'sao' => max( 1, min( 5, isset( $r['sao'] ) ? (int) $r['sao'] : 5 ) ),
				'nd'  => $nd,
				'ten' => $ten,
			);
		}
	}

	update_option( 'smlife_dg', array(
		'enabled' => empty( $in['enabled'] ) ? 0 : 1,
		'khung'   => empty( $in['khung'] ) ? 0 : 1,
		'tieu_de' => isset( $in['tieu_de'] ) ? sanitize_text_field( $in['tieu_de'] ) : $d['tieu_de'],
		'mo_ta'   => isset( $in['mo_ta'] ) ? sanitize_text_field( $in['mo_ta'] ) : '',
		'nen'     => ( isset( $in['nen'] ) && sanitize_hex_color( $in['nen'] ) ) ? sanitize_hex_color( $in['nen'] ) : $d['nen'],
		'the_nen' => ( isset( $in['the_nen'] ) && sanitize_hex_color( $in['the_nen'] ) ) ? sanitize_hex_color( $in['the_nen'] ) : $d['the_nen'],
		'sao_mau' => ( isset( $in['sao_mau'] ) && sanitize_hex_color( $in['sao_mau'] ) ) ? sanitize_hex_color( $in['sao_mau'] ) : $d['sao_mau'],
		'cot'     => max( 2, min( 5, isset( $in['cot'] ) ? (int) $in['cot'] : 4 ) ),
		'goc'     => max( 0, min( 24, isset( $in['goc'] ) ? (int) $in['goc'] : 12 ) ),
		'kieu'    => ( isset( $in['kieu'] ) && 'truot' === $in['kieu'] ) ? 'truot' : 'luoi',
		'items'   => $items,
	), false );
	if ( function_exists( 'rocket_clean_domain' ) ) { smlife_xoa_cache(); }
	wp_safe_redirect( admin_url( 'admin.php?page=smlife-danh-gia&updated=1' ) );
	exit;
} );
