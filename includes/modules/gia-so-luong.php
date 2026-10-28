<?php
/**
 * SMLIFE Core - Module: Gia theo so luong
 *
 * San pham nao bat tinh nang nay thi co bang gia theo bac so luong, cong
 * khai cho khach thay, va GIA AP THAT trong gio hang + thanh toan chu
 * khong chi hien cho dep. Bang bac gia CHI LA DINH HUONG (khong bam duoc):
 * khach doi so luong that (o quantity) thi bac dang ap sang len VA gia
 * chinh tren trang tu nhay theo bac. Bang nam trong the bo tron LUON MO
 * (khong toggle - khuyen mai phai dap vao mat khach).
 *
 * Ho tro SAN PHAM DON GIAN va SAN PHAM BIEN THE: voi bien the (vd mau),
 * bac gia doc tu san pham CHA va ap CHUNG cho moi bien the - cac bien the
 * nen cung gia goc, vi gia bac la gia tuyet doi. Khi khach chon bien the,
 * gia bien the hien ra van bi bac so luong de len theo so luong dang chon.
 *
 * Dung: sua san pham -> hop "Gia theo so luong" -> bat + nhap bac.
 * Dat bang ra trang bang shortcode [smlife_gia_sl] trong Product Layout.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/* ===== 1. Cai dat chung ===== */

function smlife_gsl_defaults() {
	return array(
		'enabled' => 1,
		'tieu_de' => 'Giá tốt khi mua số lượng lớn',
		'donvi'   => 'cái',
	);
}

function smlife_gsl_opt() {
	$o = get_option( 'smlife_gsl', array() );
	return is_array( $o ) ? array_merge( smlife_gsl_defaults(), $o ) : smlife_gsl_defaults();
}

/* Doc bac gia cua mot san pham. Tra ve mang da sap theo 'tu' tang dan. */
function smlife_gsl_rows( $product_id ) {
	$m = get_post_meta( $product_id, '_smlife_gia_sl', true );
	if ( ! is_array( $m ) || empty( $m['on'] ) || empty( $m['rows'] ) || ! is_array( $m['rows'] ) ) {
		return array();
	}
	$rows = array();
	foreach ( $m['rows'] as $r ) {
		$tu  = isset( $r['tu'] ) ? (int) $r['tu'] : 0;
		$gia = isset( $r['gia'] ) ? (float) $r['gia'] : 0;
		if ( $tu >= 1 && $gia > 0 ) {
			$rows[ $tu ] = array( 'tu' => $tu, 'gia' => $gia );
		}
	}
	ksort( $rows );
	return array_values( $rows );
}

/* Gia theo bac cho mot so luong. Tra ve 0 neu khong co bac nao khop. */
function smlife_gsl_price( $rows, $qty ) {
	$qty = max( 1, (int) $qty );
	$gia = 0;
	foreach ( $rows as $r ) {
		if ( $qty >= $r['tu'] ) {
			$gia = $r['gia'];
		} else {
			break;
		}
	}
	return $gia;
}

/* Nhan cho tung bac: "1", "2 - 5", "> 5". */
function smlife_gsl_labels( $rows ) {
	$out = array();
	$n   = count( $rows );
	for ( $i = 0; $i < $n; $i++ ) {
		$tu  = $rows[ $i ]['tu'];
		$den = ( $i + 1 < $n ) ? $rows[ $i + 1 ]['tu'] - 1 : 0;
		if ( $i + 1 === $n ) {
			$label = ( 1 === $tu ) ? '1+' : '>= ' . $tu;
		} elseif ( $den === $tu ) {
			$label = (string) $tu;
		} else {
			$label = $tu . ' - ' . $den;
		}
		$out[] = array( 'label' => $label, 'tu' => $tu, 'den' => $den, 'gia' => $rows[ $i ]['gia'] );
	}
	return $out;
}

/* ===== 2. Ap gia that trong gio hang ===== */

add_action( 'woocommerce_before_calculate_totals', function ( $cart ) {
	$o = smlife_gsl_opt();
	if ( empty( $o['enabled'] ) ) { return; }
	if ( is_admin() && ! defined( 'DOING_AJAX' ) ) { return; }
	if ( ! is_object( $cart ) ) { return; }
	foreach ( $cart->get_cart() as $item ) {
		if ( empty( $item['data'] ) || ! is_object( $item['data'] ) ) { continue; }
		$p = $item['data'];
		if ( ! $p->is_type( 'simple' ) && ! $p->is_type( 'variation' ) ) { continue; }
		$pid  = $p->is_type( 'variation' ) ? $p->get_parent_id() : $p->get_id();
		$rows = smlife_gsl_rows( $pid );
		if ( ! $rows ) { continue; }
		$gia = smlife_gsl_price( $rows, isset( $item['quantity'] ) ? $item['quantity'] : 1 );
		if ( $gia > 0 ) {
			$p->set_price( $gia );
		}
	}
}, 20 );

/* ===== 2b. Gia hien thi mac dinh = bac thap nhat =====
 *
 * Tren trang san pham co bat bac gia, gia chinh luc moi mo trang hien
 * GIA BAC THAP NHAT (vd 340.000) cho hap dan, dung tinh than "Gia chi".
 * Khi khach dung vao o so luong thi JS ben duoi moi de gia bac thuc te
 * len (qty 1 -> 520.000). Chay SAU filter cua module Cau truc gia
 * (priority 20 > 10), chi thay chuoi gia hien tai bang gia bac thap nhat,
 * khong khop chuoi thi giu nguyen cho an toan.
 */
add_filter( 'woocommerce_get_price_html', function ( $price, $product ) {
	if ( ! function_exists( 'is_product' ) || ! is_product() ) { return $price; }
	$o = smlife_gsl_opt();
	if ( empty( $o['enabled'] ) ) { return $price; }
	$pid = $product->is_type( 'variation' ) ? $product->get_parent_id() : $product->get_id();
	if ( (int) get_queried_object_id() !== (int) $pid ) { return $price; }
	$rows = smlife_gsl_rows( $pid );
	if ( ! $rows ) { return $price; }
	$min = 0;
	foreach ( $rows as $r ) {
		if ( $min <= 0 || $r['gia'] < $min ) { $min = $r['gia']; }
	}
	$cur = (float) $product->get_price();
	if ( $min <= 0 || $cur <= 0 || $min >= $cur ) { return $price; }
	$old = wc_price( $cur );
	if ( false !== strpos( $price, $old ) ) {
		return str_replace( $old, wc_price( $min ), $price );
	}
	return $price;
}, 20, 2 );

/* ===== 3. Shortcode bang gia + JS to sang bac dang ap ===== */

add_shortcode( 'smlife_gia_sl', 'smlife_gsl_sc' );
function smlife_gsl_sc() {
	if ( is_admin() ) { return ''; }
	$o = smlife_gsl_opt();
	if ( empty( $o['enabled'] ) ) { return ''; }
	$pid = get_the_ID();
	if ( ! $pid ) { return ''; }
	$p = function_exists( 'wc_get_product' ) ? wc_get_product( $pid ) : null;
	if ( ! $p || ( ! $p->is_type( 'simple' ) && ! $p->is_type( 'variable' ) ) ) { return ''; }
	$rows = smlife_gsl_rows( $pid );
	if ( ! $rows ) { return ''; }

	$ic_tag = '<svg class="smlife-gsl-ic" width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="' . esc_attr( smlife_mau() ) . '" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20.59 13.41l-7.17 7.17a2 2 0 0 1-2.83 0L2 12V2h10l8.59 8.59a2 2 0 0 1 0 2.83z"/><line x1="7" y1="7" x2="7.01" y2="7"/></svg>';

	$h  = '<div class="smlife-gsl">';
	$h .= '<div class="smlife-gsl-hd"><span class="smlife-gsl-t">' . $ic_tag . esc_html( $o['tieu_de'] ) . '</span></div>';
	$h .= '<div class="smlife-gsl-row">';
	foreach ( $rows as $i => $r ) {
		$h .= '<div class="smlife-gsl-o' . ( 0 === $i ? ' dang-ap' : '' ) . '" data-tu="' . (int) $r['tu'] . '">';
		$h .= '<span class="n">≥ ' . (int) $r['tu'] . '</span>';
		$h .= '<span class="dv">' . esc_html( mb_strtoupper( $o['donvi'] ) ) . '</span>';
		$h .= '<span class="g">' . wc_price( $r['gia'] ) . '</span>';
		$h .= '</div>';
	}
	$h .= '</div>';
	$h .= '<p class="smlife-gsl-gc">Giá tự áp theo số lượng bạn chọn khi đặt hàng.</p>';
	$h .= '</div>';
	return $h;
}

add_action( 'wp_head', function () {
	if ( ! function_exists( 'is_product' ) || ! is_product() ) { return; }
	$o = smlife_gsl_opt();
	if ( empty( $o['enabled'] ) ) { return; }
	$mau = smlife_mau();
	$c  = '.smlife-gsl{border:1px solid #ececec;border-radius:12px;margin:16px 0 0;background:#fff;overflow:hidden}';
	$c .= '.smlife-gsl-hd{padding:13px 16px 0}';
	$c .= '.smlife-gsl-t{display:inline-flex;align-items:center;gap:8px;font-size:15px;font-weight:700;color:#4a4a4a;line-height:1.3}';
	$c .= '.smlife-gsl-ic{flex:0 0 auto}';
	$c .= '.smlife-gsl-row{display:flex;gap:10px;flex-wrap:nowrap;overflow-x:auto;-webkit-overflow-scrolling:touch;scrollbar-width:thin;scrollbar-color:#ddd transparent;padding:12px 16px 2px}';
	$c .= '.smlife-gsl-row::-webkit-scrollbar{height:6px}';
	$c .= '.smlife-gsl-row::-webkit-scrollbar-thumb{background:#ddd;border-radius:3px}';
	$c .= '.smlife-gsl-row::-webkit-scrollbar-track{background:transparent}';
	$c .= '.smlife-gsl-o{flex:1 0 100px;max-width:160px;text-align:center;border:1px solid #e8e8e8;border-radius:10px;padding:10px 8px;background:#fff;transition:border-color .15s,background .15s}';
	$c .= '.smlife-gsl-o .n{display:block;font-size:17px;font-weight:800;color:#4a4a4a;line-height:1.2}';
	$c .= '.smlife-gsl-o .dv{display:block;font-size:11px;color:#9a9a9a;margin:1px 0 6px}';
	$c .= '.smlife-gsl-o .g{display:block;font-size:15px;font-weight:800;color:#4a4a4a;white-space:nowrap}';
	$c .= '.smlife-gsl-o .g .woocommerce-Price-amount{font-weight:800;color:#4a4a4a;font-size:15px}';
	$c .= '.smlife-gsl-o.dang-ap{border-color:' . $mau . ';background:' . smlife_mau_nhat( 0.95 ) . '}';
	$c .= '.smlife-gsl-o.dang-ap .g,.smlife-gsl-o.dang-ap .g .woocommerce-Price-amount{color:' . $mau . '}';
	$c .= '.smlife-gsl-gc{font-size:12.5px;color:#9a9a9a;margin:9px 0 0;padding:0 16px 13px}';
	$c .= '@media (max-width:549px){.smlife-gsl-hd{padding:12px 13px}.smlife-gsl-row{padding:12px 13px 0}.smlife-gsl-gc{padding:0 13px 12px}.smlife-gsl-o .n{font-size:15px}}';
	echo '<style id="smlife-gsl">' . $c . '</style>'; // phpcs:ignore
	$js  = 'document.addEventListener("DOMContentLoaded",function(){var box=document.querySelector(".smlife-gsl");if(!box)return;';
	$js .= 'var qty=document.querySelector("form.cart input.qty");';
	$js .= 'var pr=document.querySelector(".product-info .price-wrapper .price,.product-info p.price,.product-info .price,.summary p.price");';
	$js .= 'function amt(){';
	$js .= 'var vp=document.querySelector(".woocommerce-variation-price");';
	$js .= 'if(vp&&vp.offsetParent!==null){var va=vp.querySelector("ins .woocommerce-Price-amount")||vp.querySelector(".woocommerce-Price-amount");if(va)return va;}';
	$js .= 'if(!pr)return null;return pr.querySelector("ins .woocommerce-Price-amount")||pr.querySelector(".woocommerce-Price-amount");}';
	$js .= 'var touched=false;';
	$js .= 'function cap(){var q=qty?(parseInt(qty.value||"1",10)||1):1;var os=box.querySelectorAll(".smlife-gsl-o");var act=null;';
	$js .= 'os.forEach(function(o){var tu=parseInt(o.getAttribute("data-tu"),10);if(q>=tu){act=o;}});';
	$js .= 'os.forEach(function(o){o.classList.remove("dang-ap");});';
	$js .= 'if(act){act.classList.add("dang-ap");';
	$js .= 'if(touched){var g=act.querySelector(".g .woocommerce-Price-amount");var a=amt();if(g&&a){a.innerHTML=g.innerHTML;}}}}';
	$js .= 'if(qty){qty.addEventListener("change",function(){touched=true;cap();});qty.addEventListener("input",function(){touched=true;cap();});}';
	$js .= 'document.addEventListener("click",function(e){if(e.target.closest(".quantity")){touched=true;setTimeout(cap,60);}});';
	$js .= 'if(window.jQuery){jQuery(document.body).on("show_variation hide_variation",function(){setTimeout(cap,40);});';
	$js .= 'jQuery("form.variations_form").on("woocommerce_variation_has_changed",function(){setTimeout(cap,40);});}';
	$js .= 'cap();});';
	echo '<script>' . $js . '</script>';
}, 99 );

/* ===== 4. Hop nhap bac gia trong trang sua san pham ===== */

add_action( 'add_meta_boxes', function () {
	add_meta_box( 'smlife_gsl_box', 'Giá theo số lượng (SMLIFE)', 'smlife_gsl_box', 'product', 'normal', 'default' );
} );

function smlife_gsl_box( $post ) {
	$m    = get_post_meta( $post->ID, '_smlife_gia_sl', true );
	$on   = is_array( $m ) && ! empty( $m['on'] ) ? 1 : 0;
	$rows = is_array( $m ) && isset( $m['rows'] ) && is_array( $m['rows'] ) ? $m['rows'] : array();
	if ( ! $rows ) { $rows = array( array( 'tu' => 1, 'gia' => '' ) ); }
	wp_nonce_field( 'smlife_gsl_box', 'smlife_gsl_nonce' );
	echo '<p><label><input type="checkbox" name="gsl_on" value="1" ' . checked( 1, $on, false ) . ' /> <b>Bật giá theo số lượng cho sản phẩm này</b></label>';
	echo ' <span class="description">Áp cho sản phẩm đơn giản và sản phẩm biến thể. Giá áp thật trong giỏ hàng. Lưu ý: với sản phẩm biến thể, giá bậc áp CHUNG cho mọi biến thể (các biến thể nên cùng giá gốc), nên chỉ giữ biến thể kiểu màu sắc, bỏ biến thể kiểu số lượng.</span></p>';
	echo '<table id="smlife-gsl-rows" style="border-collapse:collapse">';
	echo '<tr><th style="text-align:left;padding:4px 10px 4px 0">Từ số lượng</th><th style="text-align:left;padding:4px 10px 4px 0">Giá mỗi đơn vị (đ)</th><th></th></tr>';
	foreach ( $rows as $r ) {
		echo '<tr><td style="padding:3px 10px 3px 0"><input type="number" min="1" name="gsl_tu[]" value="' . esc_attr( isset( $r['tu'] ) ? $r['tu'] : '' ) . '" class="small-text" /></td>';
		echo '<td style="padding:3px 10px 3px 0"><input type="number" min="0" step="1000" name="gsl_gia[]" value="' . esc_attr( isset( $r['gia'] ) ? $r['gia'] : '' ) . '" style="width:140px" /></td>';
		echo '<td><a href="#" onclick="this.closest(\'tr\').remove();return false;">Xóa</a></td></tr>';
	}
	echo '</table>';
	echo '<p><a href="#" class="button" onclick="var t=document.getElementById(\'smlife-gsl-rows\');var r=t.rows[t.rows.length-1].cloneNode(true);r.querySelectorAll(\'input\').forEach(function(i){i.value=\'\';});t.appendChild(r);return false;">+ Thêm bậc</a></p>';
	echo '<p class="description">Ví dụ: Từ 1 = 2.553.000 | Từ 2 = 2.450.000 | Từ 6 = 2.350.000. Bậc cao nhất mà số lượng đạt tới sẽ được áp. Hiện bảng ra trang bằng shortcode <code>[smlife_gia_sl]</code> trong Product Layout.</p>';
}

add_action( 'save_post_product', function ( $post_id ) {
	if ( ! isset( $_POST['smlife_gsl_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['smlife_gsl_nonce'] ) ), 'smlife_gsl_box' ) ) { return; }
	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) { return; }
	if ( ! current_user_can( 'edit_post', $post_id ) ) { return; }
	$tu  = isset( $_POST['gsl_tu'] ) && is_array( $_POST['gsl_tu'] ) ? array_map( 'intval', wp_unslash( $_POST['gsl_tu'] ) ) : array();
	$gia = isset( $_POST['gsl_gia'] ) && is_array( $_POST['gsl_gia'] ) ? array_map( 'floatval', wp_unslash( $_POST['gsl_gia'] ) ) : array();
	$rows = array();
	foreach ( $tu as $i => $t ) {
		$g = isset( $gia[ $i ] ) ? $gia[ $i ] : 0;
		if ( $t >= 1 && $g > 0 ) {
			$rows[] = array( 'tu' => (int) $t, 'gia' => (float) $g );
		}
	}
	$on = empty( $_POST['gsl_on'] ) ? 0 : 1;
	if ( ! $on && ! $rows ) {
		delete_post_meta( $post_id, '_smlife_gia_sl' );
	} else {
		update_post_meta( $post_id, '_smlife_gia_sl', array( 'on' => $on, 'rows' => $rows ) );
	}
} );

/* ===== 5. Trang cai dat ===== */

add_action( 'admin_menu', function () {
	add_submenu_page( 'smlife-core', 'Giá theo số lượng', 'Giá theo số lượng', 'manage_options', 'smlife-gia-so-luong', 'smlife_gsl_page' );
}, 20 );

function smlife_gsl_page() {
	if ( ! current_user_can( 'manage_options' ) ) { return; }
	$o = smlife_gsl_opt();
	echo '<div class="wrap smlife-wrap">';
	echo '<div class="smc-hd"><h1>Giá theo số lượng</h1>';
	echo '<p>Bật tắt chung và chữ trên bảng. Bậc giá nhập ở từng sản phẩm: sửa sản phẩm, hộp "Giá theo số lượng (SMLIFE)". Bảng nằm trong thẻ đóng/mở, chỉ là định hướng; khách chọn số lượng thật thì giá trên trang và trong giỏ tự nhảy theo bậc. Hỗ trợ cả sản phẩm biến thể (bậc giá áp chung mọi biến thể).</p></div>';
	if ( isset( $_GET['updated'] ) ) { echo '<div class="notice notice-success is-dismissible"><p>Đã lưu.</p></div>'; }
	echo '<div class="smlife-card"><form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '">';
	echo '<input type="hidden" name="action" value="smlife_gsl_save" />';
	wp_nonce_field( 'smlife_gsl_save', 'smlife_gsl_pnonce' );
	echo '<table class="form-table">';
	echo '<tr><th>Bật module</th><td><label><input type="checkbox" name="gsl[enabled]" value="1" ' . checked( 1, $o['enabled'], false ) . ' /> Áp giá theo bậc và hiện bảng ở sản phẩm đã bật</label></td></tr>';
	echo '<tr><th>Tiêu đề bảng</th><td><input type="text" name="gsl[tieu_de]" value="' . esc_attr( $o['tieu_de'] ) . '" class="regular-text" /></td></tr>';
	echo '<tr><th>Đơn vị</th><td><input type="text" name="gsl[donvi]" value="' . esc_attr( $o['donvi'] ) . '" class="small-text" /> <span class="description">cái, bộ, tấm...</span></td></tr>';
	echo '</table>';
	submit_button( 'Lưu' );
	echo '</form></div></div>';
}

add_action( 'admin_post_smlife_gsl_save', function () {
	if ( ! current_user_can( 'manage_options' ) ) { wp_die( 'Không đủ quyền.' ); }
	check_admin_referer( 'smlife_gsl_save', 'smlife_gsl_pnonce' );
	$in = isset( $_POST['gsl'] ) && is_array( $_POST['gsl'] ) ? wp_unslash( $_POST['gsl'] ) : array();
	$d  = smlife_gsl_defaults();
	update_option( 'smlife_gsl', array(
		'enabled' => empty( $in['enabled'] ) ? 0 : 1,
		'tieu_de' => isset( $in['tieu_de'] ) && '' !== trim( $in['tieu_de'] ) ? sanitize_text_field( $in['tieu_de'] ) : $d['tieu_de'],
		'donvi'   => isset( $in['donvi'] ) && '' !== trim( $in['donvi'] ) ? sanitize_text_field( $in['donvi'] ) : $d['donvi'],
	), false );
	if ( function_exists( 'rocket_clean_domain' ) ) { smlife_xoa_cache(); }
	wp_safe_redirect( admin_url( 'admin.php?page=smlife-gia-so-luong&updated=1' ) );
	exit;
} );
