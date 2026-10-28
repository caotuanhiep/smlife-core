<?php
/**
 * SMLIFE Core - Module: Chan bam so dien thoai
 *
 * Muc dich: khach di tu quang cao vao, lo tay cham trung so dien thoai la
 * trinh duyet mo trinh quay so, va he thong do luong ghi nhan mot chuyen
 * doi "goi dien" khong co that. Bot quet trang cung gay ra dieu tuong tu.
 *
 * Cach xu ly: khi trang tai xong, module doi tat ca the <a href="tel:...">
 * thanh lien ket tro, so that cat vao thuoc tinh data-smtel. Nho vay moi
 * trinh do luong dang bat theo mau "lien ket bat dau bang tel:" deu khong
 * con khop, ke ca khi bot bam vao.
 *
 * Khi khach that bam vao so:
 *   - May tinh : khong quay so, tu copy so vao bo nho tam va hien loi nhac.
 *   - Dien thoai: mo bang "Chat tu van" cua menu mobile.
 *
 * NGOAI LE: cac so nam trong bang "Goi tu van" cua menu mobile van goi duoc
 * binh thuong, vi khach phai mo bang roi chon nguoi moi bam duoc - do la
 * hanh vi co chu dich, dung la chuyen doi that. Muon chan ca cho do thi bo
 * trong o "Khu vuc van cho goi".
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

/* ===== 1. Cai dat ===== */

function smlife_pg_defaults() {
	return array(
		'enabled' => 1,
		'pc'      => 1,
		'mb'      => 1,
		'copy'    => 1,
		'bp'      => 850,
		'tru'     => '#smm-p-call',
		'nhac'    => 'Đã copy số, bạn dán vào ứng dụng gọi nhé',
	);
}

function smlife_pg_opt() {
	$o = get_option( 'smlife_pg', array() );
	return is_array( $o ) ? array_merge( smlife_pg_defaults(), $o ) : smlife_pg_defaults();
}

/* ===== 2. Chay tren trang ===== */

add_action( 'wp_footer', function () {
	$o = smlife_pg_opt();
	if ( empty( $o['enabled'] ) || ( empty( $o['pc'] ) && empty( $o['mb'] ) ) ) { return; }
	if ( is_admin() ) { return; }

	$c  = '.smlife-telx{cursor:pointer}';
	$c .= '#smlife-pg-toast{position:fixed;left:50%;bottom:86px;transform:translateX(-50%) translateY(14px);z-index:100000;';
	$c .= 'background:#2f3133;color:#fff;font-size:13.5px;line-height:1.4;font-weight:500;padding:11px 18px;border-radius:999px;';
	$c .= 'box-shadow:0 6px 20px rgba(0,0,0,.22);opacity:0;pointer-events:none;transition:opacity .2s,transform .2s;max-width:86vw;text-align:center}';
	$c .= '#smlife-pg-toast.hien{opacity:1;transform:translateX(-50%) translateY(0)}';
	echo '<style id="smlife-pg-css">' . $c . '</style>'; // phpcs:ignore

	$bp  = max( 480, min( 1200, (int) $o['bp'] ) );
	$js  = '(function(){';
	$js .= 'var BP=' . (int) $bp . ',PC=' . ( empty( $o['pc'] ) ? 0 : 1 ) . ',MB=' . ( empty( $o['mb'] ) ? 0 : 1 ) . ',CP=' . ( empty( $o['copy'] ) ? 0 : 1 ) . ';';
	$js .= 'var TRU=' . wp_json_encode( trim( (string) $o['tru'] ) ) . ',NHAC=' . wp_json_encode( (string) $o['nhac'] ) . ';';
	$js .= 'function mobi(){return window.innerWidth<BP;}';
	/* Link nao duoc tha */
	$js .= 'function thaRa(a){if(!TRU)return false;try{return !!a.closest(TRU);}catch(e){return false;}}';
	/* Doi tel: thanh lien ket tro */
	$js .= 'function doi(root){var ds=(root||document).querySelectorAll(\'a[href^="tel:"]\');';
	$js .= 'for(var i=0;i<ds.length;i++){var a=ds[i];if(thaRa(a))continue;';
	$js .= 'var so=(a.getAttribute("href")||"").replace(/^tel:/i,"");';
	$js .= 'a.setAttribute("data-smtel",so);a.setAttribute("href","javascript:void(0)");a.classList.add("smlife-telx");}}';
	/* Loi nhac ngan */
	$js .= 'var tm=null;function nhac(t){var b=document.getElementById("smlife-pg-toast");';
	$js .= 'if(!b){b=document.createElement("div");b.id="smlife-pg-toast";document.body.appendChild(b);}';
	$js .= 'b.textContent=t;b.classList.add("hien");clearTimeout(tm);tm=setTimeout(function(){b.classList.remove("hien");},2600);}';
	/* Mo bang chat cua menu mobile */
	$js .= 'function moChat(){var t=document.querySelector(\'.smlife-mm [data-smm="panel"][data-target="smm-p-chat"]\');';
	$js .= 'if(t){t.click();return true;}return false;}';
	$js .= 'function copy(so){try{if(navigator.clipboard){navigator.clipboard.writeText(so);return true;}}catch(e){}';
	$js .= 'try{var x=document.createElement("textarea");x.value=so;x.style.position="fixed";x.style.left="-9999px";';
	$js .= 'document.body.appendChild(x);x.select();document.execCommand("copy");document.body.removeChild(x);return true;}catch(e){}return false;}';
	/* Bat click */
	$js .= 'document.addEventListener("click",function(e){var a=e.target.closest?e.target.closest("a.smlife-telx"):null;';
	$js .= 'if(!a)return;var m=mobi();if(m&&!MB)return;if(!m&&!PC)return;';
	$js .= 'e.preventDefault();e.stopPropagation();';
	$js .= 'var so=a.getAttribute("data-smtel")||"";';
	$js .= 'if(m){if(moChat())return;nhac(so?("Gọi: "+so):NHAC);return;}';
	$js .= 'if(CP&&so&&copy(so)){nhac(NHAC+": "+so);}else if(so){nhac("Số liên hệ: "+so);}';
	$js .= '},true);';
	/* Chay lan dau va theo doi noi dung nap them */
	$js .= 'function chay(){doi(document);}';
	$js .= 'if(document.readyState==="loading"){document.addEventListener("DOMContentLoaded",chay);}else{chay();}';
	/* 1.24.0: gom nhieu thay doi DOM lien tiep (slider, lazy load) thanh 1 lan quet sau 250ms */
	$js .= 'var hen=0;try{new MutationObserver(function(ms){if(hen)return;for(var i=0;i<ms.length;i++){if(ms[i].addedNodes&&ms[i].addedNodes.length){hen=setTimeout(function(){hen=0;doi(document);},250);break;}}})';
	$js .= '.observe(document.body,{childList:true,subtree:true});}catch(e){}';
	$js .= '})();';
	echo '<script id="smlife-pg-js">' . $js . '</script>'; // phpcs:ignore
}, 98 );

/* ===== 3. Trang cai dat ===== */

add_action( 'admin_menu', function () {
	add_submenu_page( 'smlife-core', 'Chặn bấm số điện thoại', 'Chặn bấm số ĐT', 'manage_options', 'smlife-chan-so', 'smlife_pg_page' );
}, 60 );

function smlife_pg_page() {
	if ( ! current_user_can( 'manage_options' ) ) { return; }
	$o = smlife_pg_opt();
	echo '<div class="wrap smlife-wrap">';
	echo '<div class="smc-hd"><h1>Chặn bấm số điện thoại</h1>';
	echo '<p>Số điện thoại trên web vẫn hiện bình thường nhưng không còn là liên kết quay số, nên khách lỡ tay chạm trúng hoặc bot quét trang đều không bị tính thành chuyển đổi "gọi điện" ảo.</p></div>';
	if ( isset( $_GET['updated'] ) ) { echo '<div class="notice notice-success is-dismissible"><p>Đã lưu.</p></div>'; }

	echo '<div class="smlife-card"><form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '">';
	echo '<input type="hidden" name="action" value="smlife_pg_save" />';
	wp_nonce_field( 'smlife_pg_save', 'smlife_pg_nonce' );
	echo '<table class="form-table">';
	echo '<tr><th>Bật tính năng</th><td><label><input type="checkbox" name="pg[enabled]" value="1" ' . checked( 1, $o['enabled'], false ) . ' /> Gỡ liên kết quay số khỏi mọi số điện thoại trên web</label></td></tr>';
	echo '<tr><th>Trên máy tính</th><td><label><input type="checkbox" name="pg[pc]" value="1" ' . checked( 1, $o['pc'], false ) . ' /> Bấm vào số thì không làm gì</label></td></tr>';
	echo '<tr><th>Tự copy số</th><td><label><input type="checkbox" name="pg[copy]" value="1" ' . checked( 1, $o['copy'], false ) . ' /> Trên máy tính, bấm vào số thì copy sẵn vào bộ nhớ tạm cho khách</label></td></tr>';
	echo '<tr><th>Trên điện thoại</th><td><label><input type="checkbox" name="pg[mb]" value="1" ' . checked( 1, $o['mb'], false ) . ' /> Bấm vào số thì mở bảng Chat tư vấn thay vì quay số</label></td></tr>';
	echo '<tr><th>Ngưỡng điện thoại</th><td><input type="number" min="480" max="1200" name="pg[bp]" value="' . esc_attr( $o['bp'] ) . '" class="small-text" /> px <span class="description">Màn hình hẹp hơn mức này tính là điện thoại. Flatsome đang dùng 850.</span></td></tr>';
	echo '<tr><th>Khu vực vẫn cho gọi</th><td><input type="text" name="pg[tru]" value="' . esc_attr( $o['tru'] ) . '" class="regular-text" placeholder="#smm-p-call" />';
	echo '<br /><span class="description">Số nằm trong khu vực này vẫn quay số bình thường. Mặc định <code>#smm-p-call</code> là bảng "Gọi tư vấn" của menu mobile: khách phải chủ động mở bảng rồi chọn nhân viên mới gọi được, nên đây là chuyển đổi thật, <b>nên giữ lại</b>. Xoá trống là chặn tuyệt đối, khách sẽ không còn cách nào gọi trực tiếp từ web.</span></td></tr>';
	echo '<tr><th>Lời nhắc khi copy</th><td><input type="text" name="pg[nhac]" value="' . esc_attr( $o['nhac'] ) . '" class="large-text" /></td></tr>';
	echo '</table>';
	submit_button( 'Lưu' );
	echo '</form></div></div>';
}

add_action( 'admin_post_smlife_pg_save', function () {
	if ( ! current_user_can( 'manage_options' ) ) { wp_die( 'Không đủ quyền.' ); }
	check_admin_referer( 'smlife_pg_save', 'smlife_pg_nonce' );
	$in = isset( $_POST['pg'] ) && is_array( $_POST['pg'] ) ? wp_unslash( $_POST['pg'] ) : array();
	$d  = smlife_pg_defaults();
	update_option( 'smlife_pg', array(
		'enabled' => empty( $in['enabled'] ) ? 0 : 1,
		'pc'      => empty( $in['pc'] ) ? 0 : 1,
		'mb'      => empty( $in['mb'] ) ? 0 : 1,
		'copy'    => empty( $in['copy'] ) ? 0 : 1,
		'bp'      => max( 480, min( 1200, isset( $in['bp'] ) ? (int) $in['bp'] : 850 ) ),
		'tru'     => isset( $in['tru'] ) ? sanitize_text_field( $in['tru'] ) : $d['tru'],
		'nhac'    => isset( $in['nhac'] ) && '' !== trim( $in['nhac'] ) ? sanitize_text_field( $in['nhac'] ) : $d['nhac'],
	), true );
	if ( function_exists( 'rocket_clean_domain' ) ) { smlife_xoa_cache(); }
	wp_safe_redirect( admin_url( 'admin.php?page=smlife-chan-so&updated=1' ) );
	exit;
} );
