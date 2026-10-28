<?php
/**
 * SMLIFE Core - Module: Menu Mobile
 * Thanh menu co dinh duoi man hinh dien thoai.
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

/**
 * SMLIFE CORE - 07. Menu Mobile
 * ---------------------------------------------
 * Thanh menu co dinh duoi cung, chi hien tren mobile.
 * - Toi da 5 nut: chinh icon, chu, link va hanh dong.
 * - Hanh dong: link thuong, mo Menu mobile (off-canvas), goi dien,
 *   mo tab moi, mo Bang Chat, mo Bang Goi hotline.
 * - Bang Chat / Bang Goi truot len tu duoi, chon kenh chat hoac chon nhan vien.
 * - Trang san pham doi layout: an bot nut va hien nut Mua ngay (mo popup Dat hang nhanh).
 * Cau hinh trong SMLIFE Core > Menu Mobile.
 *
 * WPCode: Run everywhere. Khi dan: KHONG copy dong mo PHP o tren cung.
 */

/* ===== Cau hinh ===== */
function smlife_mm_opt() {
	$def = array(
		'enabled'     => 1,
		'bg'          => '#ffffff',
		'color'       => '#333333',
		'icon_color'  => '#e8641e',
		'height'      => 56,
		'buy_on'      => 1,
		'buy_label'   => 'Mua Ngay',
		'buy_bg'      => '#2b95ec',
		'buy_color'   => '#ffffff',
		'buy_w'       => 33,
		'buy_r'       => 12,
		'lift_totop'  => 1,
		'items'       => array(
			array( 'on' => 1, 'icon' => 'home',  'label' => 'Trang chủ',   'url' => '/', 'act' => 'link', 'hp' => 1 ),
			array( 'on' => 1, 'icon' => 'grid',  'label' => 'Danh mục',    'url' => '',  'act' => 'menu', 'hp' => 0 ),
			array( 'on' => 1, 'icon' => 'chat',  'label' => 'Chat Zalo',   'url' => '',  'act' => 'chat', 'hp' => 0 ),
			array( 'on' => 1, 'icon' => 'phone', 'label' => 'Gọi Hotline', 'url' => '',  'act' => 'call', 'hp' => 0 ),
			array( 'on' => 0, 'icon' => 'map',   'label' => 'Showroom',    'url' => '',  'act' => 'blank', 'hp' => 0 ),
		),
		'chat_title'  => 'Chat với ' . smlife_ten_thuong_hieu(),
		'chat_items'  => array(
			array( 'on' => 1, 'name' => 'Zalo',      'desc' => 'Nhắn tin qua Zalo',     'url' => 'https://zalo.me/0914913179', 'color' => '#0068ff', 'feat' => 1, 'badge' => 'Kênh chính' ),
			array( 'on' => 1, 'name' => 'Messenger', 'desc' => 'Nhắn tin qua Facebook', 'url' => 'https://m.me/shopdepot',     'color' => '#0084ff' ),
			array( 'on' => 0, 'name' => '',          'desc' => '',                      'url' => '',                           'color' => '#e8641e' ),
			array( 'on' => 0, 'name' => '',          'desc' => '',                      'url' => '',                           'color' => '#e8641e' ),
		),
		'call_title'  => 'Gọi tư vấn',
		'call_items'  => array(
			array( 'on' => 1, 'name' => 'HẢI',   'desc' => 'trách nhiệm', 'url' => '0914913179', 'color' => '#e8641e' ),
			array( 'on' => 1, 'name' => 'PHÚC',  'desc' => 'tin tưởng',   'url' => '0909553363', 'color' => '#2bbf5e' ),
			array( 'on' => 1, 'name' => 'PHONG', 'desc' => 'tử tế',       'url' => '0909303363', 'color' => '#2b95ec' ),
			array( 'on' => 0, 'name' => '',      'desc' => '',            'url' => '',           'color' => '#8c54d6' ),
		),
	);
	$s = get_option( 'smlife_mm', array() );
	if ( ! is_array( $s ) ) {
		return $def;
	}
	$out = array_merge( $def, $s );
	foreach ( array( 'items', 'chat_items', 'call_items' ) as $k ) {
		if ( empty( $out[ $k ] ) || ! is_array( $out[ $k ] ) ) {
			$out[ $k ] = $def[ $k ];
		}
	}
	return $out;
}

/* ===== Bo icon SVG ===== */
function smlife_mm_icons() {
	return array(
		'home'   => '<path d="M3 10.8 12 3.5l9 7.3"/><path d="M5.8 9.6V20h12.4V9.6"/>',
		'grid'   => '<rect x="3.5" y="3.5" width="7" height="7" rx="1.5"/><rect x="13.5" y="3.5" width="7" height="7" rx="1.5"/><rect x="3.5" y="13.5" width="7" height="7" rx="1.5"/><rect x="13.5" y="13.5" width="7" height="7" rx="1.5"/>',
		'chat'   => '<path d="M20.5 12c0 4.1-3.8 7.4-8.5 7.4-1 0-2-.2-2.9-.5L4 20.5l1.4-3.6C4.2 15.6 3.5 13.9 3.5 12c0-4.1 3.8-7.4 8.5-7.4s8.5 3.3 8.5 7.4Z"/>',
		'phone'  => '<path d="M6.3 3.8h3l1.5 3.8-1.9 1.4a12 12 0 0 0 5.1 5.1l1.4-1.9 3.8 1.5v3c0 .9-.7 1.6-1.6 1.6A14.8 14.8 0 0 1 4.7 5.4c0-.9.7-1.6 1.6-1.6Z"/>',
		'map'    => '<path d="M12 21s7-5.6 7-10.5A7 7 0 0 0 5 10.5C5 15.4 12 21 12 21Z"/><circle cx="12" cy="10.3" r="2.6"/>',
		'cart'   => '<circle cx="9.5" cy="19.5" r="1.4"/><circle cx="17.5" cy="19.5" r="1.4"/><path d="M3 4h2.2l2.3 11.2h11l2-7.6H6.2"/>',
		'user'   => '<circle cx="12" cy="8.2" r="3.7"/><path d="M4.8 20.2c0-3.6 3.2-5.8 7.2-5.8s7.2 2.2 7.2 5.8"/>',
		'search' => '<circle cx="11" cy="11" r="6.5"/><path d="M16 16l4.5 4.5"/>',
	);
}

function smlife_mm_icon_labels() {
	return array(
		'home'   => 'Trang chủ (nhà)',
		'grid'   => 'Danh mục (ô vuông)',
		'chat'   => 'Chat (bong bóng)',
		'phone'  => 'Điện thoại',
		'map'    => 'Bản đồ',
		'cart'   => 'Giỏ hàng',
		'user'   => 'Tài khoản',
		'search' => 'Tìm kiếm',
	);
}

function smlife_mm_icon_svg( $key, $w = 23 ) {
	$ic = smlife_mm_icons();
	$p  = isset( $ic[ $key ] ) ? $ic[ $key ] : $ic['grid'];
	return '<svg viewBox="0 0 24 24" width="' . (int) $w . '" height="' . (int) $w . '" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' . $p . '</svg>';
}

/* Chu cai dau cua ten, dung cho avatar tron. */
function smlife_mm_initial( $name ) {
	$name = trim( wp_strip_all_tags( (string) $name ) );
	if ( '' === $name ) {
		return '?';
	}
	if ( function_exists( 'mb_substr' ) ) {
		return mb_strtoupper( mb_substr( $name, 0, 1, 'UTF-8' ), 'UTF-8' );
	}
	return strtoupper( substr( $name, 0, 1 ) );
}

/* ===== Hien thanh menu ngoai web ===== */
add_action( 'wp_footer', function () {
	$o = smlife_mm_opt();
	if ( empty( $o['enabled'] ) || is_admin() ) {
		return;
	}

	$items = array();
	foreach ( $o['items'] as $it ) {
		if ( ! empty( $it['on'] ) && '' !== trim( (string) $it['label'] ) ) {
			$items[] = $it;
		}
	}
	if ( empty( $items ) ) {
		return;
	}

	$is_prod  = function_exists( 'is_product' ) && is_product();
	$show_buy = ( $is_prod && ! empty( $o['buy_on'] ) );
	$h        = (int) $o['height'];
	$bw       = max( 22, min( 60, (int) $o['buy_w'] ) );
	$br       = max( 0, min( 28, (int) $o['buy_r'] ) );

	$chat_rows = array();
	foreach ( $o['chat_items'] as $r ) {
		if ( ! empty( $r['on'] ) && '' !== trim( (string) $r['name'] ) ) {
			$chat_rows[] = $r;
		}
	}
	$call_rows = array();
	foreach ( $o['call_items'] as $r ) {
		if ( ! empty( $r['on'] ) && '' !== trim( (string) $r['name'] ) ) {
			$call_rows[] = $r;
		}
	}

	/* ===== CSS ===== */
	$css  = '.smlife-mm,.smm-panel,.smm-bd{display:none}';
	$css .= '@media(max-width:849px){';
	$css .= '.smlife-mm{display:flex;position:fixed;left:0;right:0;bottom:0;z-index:9999;height:' . $h . 'px;';
	$css .= 'background:' . esc_attr( $o['bg'] ) . ';border-top:1px solid rgba(0,0,0,.07);';
	$css .= 'box-shadow:0 -3px 16px rgba(0,0,0,.13);align-items:stretch}';
	$css .= '.smlife-mm .smm-items{display:flex;flex:1 1 auto;min-width:0}';
	$css .= '.smlife-mm a.smm-i{flex:1 1 0;min-width:0;display:flex;flex-direction:column;align-items:center;justify-content:center;gap:1px;';
	$css .= 'text-decoration:none;color:' . esc_attr( $o['color'] ) . ';font-size:10.5px;line-height:1.25;padding:4px 2px;text-align:center;-webkit-tap-highlight-color:transparent}';
	$css .= '.smlife-mm a.smm-i svg{color:' . esc_attr( $o['icon_color'] ) . ';flex:0 0 auto}';
	$css .= '.smlife-mm a.smm-i span{display:block;width:100%;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}';
	$css .= '.smlife-mm a.smm-i.is-open{background:rgba(0,0,0,.04)}';
	$css .= '.smlife-mm .smm-buy{flex:0 0 auto;width:' . $bw . '%;max-width:148px;min-width:84px;';
	$css .= 'display:flex;align-items:center;justify-content:center;border:0;cursor:pointer;';
	$css .= 'background:' . esc_attr( $o['buy_bg'] ) . ';color:' . esc_attr( $o['buy_color'] ) . ';font-size:15px;font-weight:700;';
	$css .= 'letter-spacing:.2px;white-space:nowrap;border-radius:' . $br . 'px 0 0 ' . $br . 'px;margin:0;padding:0}';
	/* Bang truot */
	$css .= '.smm-bd{display:block;position:fixed;left:0;right:0;top:0;bottom:0;z-index:9997;background:rgba(0,0,0,.38);opacity:0;pointer-events:none;transition:opacity .22s}';
	$css .= '.smm-bd.open{opacity:1;pointer-events:auto}';
	$css .= '.smm-panel{display:block;position:fixed;left:0;right:0;bottom:0;z-index:9998;background:#fff;padding-bottom:' . $h . 'px;';
	$css .= 'border-radius:16px 16px 0 0;box-shadow:0 -6px 24px rgba(0,0,0,.16);transform:translateY(100%);overflow:hidden;visibility:hidden;';
	$css .= 'transition:transform .26s cubic-bezier(.3,.8,.4,1),visibility 0s linear .26s}';
	$css .= '.smm-panel.open{visibility:visible;transition:transform .26s cubic-bezier(.3,.8,.4,1),visibility 0s linear 0s}';
	$css .= '.smm-panel.open{transform:translateY(0)}';
	$css .= '.smm-ph{display:flex;align-items:center;justify-content:space-between;padding:13px 16px 11px;border-bottom:1px solid #f0f0f1}';
	$css .= '.smm-ph b{font-size:14px;color:#1d2327;font-weight:700}';
	$css .= '.smm-x{border:0;background:#f0f0f1;color:#666;width:26px;height:26px;border-radius:50%;font-size:15px;line-height:1;cursor:pointer;padding:0}';
	$css .= '.smm-row{display:flex;align-items:center;gap:12px;padding:11px 16px;text-decoration:none;border-bottom:1px solid #f6f6f7;-webkit-tap-highlight-color:transparent}';
	$css .= '.smm-row:last-child{border-bottom:0}';
	$css .= '.smm-row:active{background:#fafafa}';
	$css .= '.smm-av{flex:0 0 38px;width:38px;height:38px;border-radius:50%;display:flex;align-items:center;justify-content:center;color:#fff;font-weight:700;font-size:15px;overflow:hidden}';
	$css .= '.smm-av img{width:100%;height:100%;object-fit:cover;display:block;border-radius:50%}';
	$css .= '.smm-av.has-img{background:#f0f0f1 !important}';
	$css .= '.smm-tx{flex:1 1 auto;min-width:0}';
	$css .= '.smm-tx b{display:block;font-size:14px;color:#1d2327;font-weight:600;line-height:1.3}';
	$css .= '.smm-tx i{display:block;font-size:12px;color:#787c82;font-style:normal;line-height:1.4;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}';
	/* Kenh chat noi bat (vd Zalo) */
	$css .= '.smm-row.smm-feat{background:#f6f9ff;background:color-mix(in srgb,var(--smm-fc) 7%,#fff);border-left:3px solid var(--smm-fc);padding-left:13px}';
	$css .= '.smm-row.smm-feat .smm-av{flex:0 0 44px;width:44px;height:44px;box-shadow:0 0 0 3px #e4edff;box-shadow:0 0 0 3px color-mix(in srgb,var(--smm-fc) 18%,#fff)}';
	$css .= '.smm-row.smm-feat .smm-tx b{font-size:15px;font-weight:700;display:flex;align-items:center;gap:7px}';
	$css .= '.smm-row.smm-feat .smm-tx i{color:#5c6169}';
	$css .= '.smm-row.smm-feat .smm-go{color:var(--smm-fc)}';
	$css .= '.smm-bdg{display:inline-block;font-style:normal;font-size:10px;font-weight:700;letter-spacing:.03em;text-transform:uppercase;color:#fff;background:var(--smm-fc);border-radius:20px;padding:2px 8px;line-height:1.5;white-space:nowrap}';
	$css .= '.smm-go{flex:0 0 auto;color:#c3c4c7}';
	$css .= 'body{padding-bottom:' . $h . 'px !important}';
	if ( ! empty( $o['lift_totop'] ) ) {
		$css .= '.back-to-top{bottom:' . ( $h + 14 ) . 'px !important}';
	}
	$css .= '}';
	/* Man hinh rat nho: thu nho nut va chu de 3 nut kia luon du cho. */
	$css .= '@media(max-width:360px){';
	$css .= '.smlife-mm .smm-buy{font-size:13px;min-width:76px}';
	$css .= '.smlife-mm a.smm-i{font-size:9.5px}';
	$css .= '.smlife-mm a.smm-i svg{width:20px;height:20px}';
	$css .= '}';
	echo '<style id="smlife-mm-css">' . $css . '</style>'; // phpcs:ignore

	/* ===== Thanh menu ===== */
	echo '<nav class="smlife-mm" aria-label="Menu mobile"><div class="smm-items">';
	foreach ( $items as $it ) {
		if ( $is_prod && ! empty( $it['hp'] ) ) {
			continue;
		}
		$act  = isset( $it['act'] ) ? $it['act'] : 'link';
		$url  = trim( (string) $it['url'] );
		$attr = '';
		$href = '#';

		if ( 'menu' === $act ) {
			$attr = ' data-smm="menu"';
		} elseif ( 'chat' === $act ) {
			$attr = ' data-smm="panel" data-target="smm-p-chat"';
		} elseif ( 'call' === $act ) {
			$attr = ' data-smm="panel" data-target="smm-p-call"';
		} elseif ( 'tel' === $act ) {
			$href = 'tel:' . preg_replace( '/[^0-9+]/', '', $url );
		} elseif ( 'blank' === $act ) {
			$href = $url ? $url : '#';
			$attr = ' target="_blank" rel="noopener"';
		} else {
			$href = $url ? $url : home_url( '/' );
		}

		echo '<a class="smm-i" href="' . esc_url( $href ) . '"' . $attr . '>'; // phpcs:ignore
		echo smlife_mm_icon_svg( isset( $it['icon'] ) ? $it['icon'] : 'grid', 22 ); // phpcs:ignore
		echo '<span>' . esc_html( $it['label'] ) . '</span></a>';
	}
	echo '</div>';
	if ( $show_buy ) {
		echo '<button type="button" class="smm-buy">' . esc_html( $o['buy_label'] ) . '</button>';
	}
	echo '</nav>';

	/* ===== Backdrop + 2 bang truot ===== */
	echo '<div class="smm-bd" id="smm-bd"></div>';

	$arrow = '<svg class="smm-go" viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 5l7 7-7 7"/></svg>';

	if ( ! empty( $chat_rows ) ) {
		echo '<div class="smm-panel" id="smm-p-chat">';
		echo '<div class="smm-ph"><b>' . esc_html( $o['chat_title'] ) . '</b><button type="button" class="smm-x" data-smm="close" aria-label="Đóng">&times;</button></div>';
		/* Kenh noi bat len dau bang */
		$feat_rows = array();
		$norm_rows = array();
		foreach ( $chat_rows as $r ) {
			if ( ! empty( $r['feat'] ) ) {
				$feat_rows[] = $r;
			} else {
				$norm_rows[] = $r;
			}
		}
		foreach ( array_merge( $feat_rows, $norm_rows ) as $r ) {
			$c  = sanitize_hex_color( $r['color'] ) ? $r['color'] : '#0068ff';
			$is = ! empty( $r['feat'] );
			$st = $is ? ' style="--smm-fc:' . esc_attr( $c ) . '"' : '';
			echo '<a class="smm-row' . ( $is ? ' smm-feat' : '' ) . '"' . $st . ' href="' . esc_url( $r['url'] ? $r['url'] : '#' ) . '" target="_blank" rel="noopener">'; // phpcs:ignore
			$rimg = isset( $r['img'] ) ? trim( (string) $r['img'] ) : '';
			if ( '' !== $rimg ) {
				echo '<span class="smm-av has-img"><img src="' . esc_url( $rimg ) . '" alt="' . esc_attr( $r['name'] ) . '" loading="lazy" /></span>';
			} else {
				echo '<span class="smm-av" style="background:' . esc_attr( $c ) . '">' . smlife_mm_icon_svg( 'chat', 19 ) . '</span>'; // phpcs:ignore
			}
			$bdg = '';
			if ( $is ) {
				$bt  = trim( (string) ( isset( $r['badge'] ) ? $r['badge'] : '' ) );
				$bdg = '' !== $bt ? '<em class="smm-bdg">' . esc_html( $bt ) . '</em>' : '';
			}
			echo '<span class="smm-tx"><b>' . esc_html( $r['name'] ) . $bdg . '</b><i>' . esc_html( $r['desc'] ) . '</i></span>'; // phpcs:ignore
			echo $arrow . '</a>'; // phpcs:ignore
		}
		echo '</div>';
	}

	if ( ! empty( $call_rows ) ) {
		echo '<div class="smm-panel" id="smm-p-call">';
		echo '<div class="smm-ph"><b>' . esc_html( $o['call_title'] ) . '</b><button type="button" class="smm-x" data-smm="close" aria-label="Đóng">&times;</button></div>';
		foreach ( $call_rows as $r ) {
			$c   = sanitize_hex_color( $r['color'] ) ? $r['color'] : '#e8641e';
			$num    = preg_replace( '/[^0-9+]/', '', (string) $r['url'] );
			$pretty = ( 10 === strlen( $num ) )
				? substr( $num, 0, 4 ) . ' ' . substr( $num, 4, 3 ) . ' ' . substr( $num, 7, 3 )
				: trim( chunk_split( $num, 4, ' ' ) );
			$dcls = function_exists( 'smlife_duty_class' ) ? smlife_duty_class( $num ) : '';
			$dbdg = function_exists( 'smlife_duty_badge' ) ? smlife_duty_badge( $num, 'mobile' ) : '';
			echo '<a class="smm-row' . $dcls . '" href="tel:' . esc_attr( $num ) . '">';
			$rimg = isset( $r['img'] ) ? trim( (string) $r['img'] ) : '';
			if ( '' !== $rimg ) {
				echo '<span class="smm-av has-img"><img src="' . esc_url( $rimg ) . '" alt="' . esc_attr( $r['name'] ) . '" loading="lazy" /></span>';
			} else {
				echo '<span class="smm-av" style="background:' . esc_attr( $c ) . '">' . esc_html( smlife_mm_initial( $r['name'] ) ) . '</span>';
			}
			echo '<span class="smm-tx"><b>' . esc_html( $r['name'] ) . ' <span style="font-weight:400;color:#787c82">' . esc_html( $r['desc'] ) . '</span>' . $dbdg . '</b><i>' . esc_html( $pretty ) . '</i></span>';
			echo smlife_mm_icon_svg( 'phone', 18 ) . '</a>'; // phpcs:ignore
		}
		echo '</div>';
	}

	/* ===== JS ===== */
	$js  = '<script>(function(){';
	$js .= 'function close(){document.querySelectorAll(".smm-panel.open").forEach(function(p){p.classList.remove("open");});';
	$js .= 'var b=document.getElementById("smm-bd");if(b){b.classList.remove("open");}';
	$js .= 'document.querySelectorAll(".smm-i.is-open").forEach(function(a){a.classList.remove("is-open");});}';
	$js .= 'function menuOn(){return !!document.querySelector(".mfp-wrap");}';
	$js .= 'function menuOff(){if(window.jQuery&&window.jQuery.magnificPopup){try{window.jQuery.magnificPopup.close();}catch(err){}}}';
	$js .= 'document.addEventListener("click",function(e){';
	$js .= 'var t=e.target;if(!t.closest){return;}';
	/* mo menu off-canvas */
	$js .= 'var m=t.closest("[data-smm=\'menu\']");';
	$js .= 'if(m){e.preventDefault();var wasMenu=menuOn();close();';
	$js .= 'if(wasMenu){menuOff();return;}';
	$js .= 'var L=document.querySelectorAll("a[data-open=\'#main-menu\']"),g=null;';
	$js .= 'for(var i=0;i<L.length;i++){var r=L[i].getBoundingClientRect();if(r.width>0&&r.height>0){g=L[i];break;}}';
	$js .= 'if(!g){g=L[0];}';
	$js .= 'if(g){if(window.jQuery){window.jQuery(g).trigger("click");}else{g.dispatchEvent(new MouseEvent("click",{bubbles:true,cancelable:true,view:window}));}}return;}';
	/* mo bang */
	$js .= 'var p=t.closest("[data-smm=\'panel\']");';
	$js .= 'if(p){e.preventDefault();menuOff();var id=p.getAttribute("data-target");var el=document.getElementById(id);';
	$js .= 'var wasOpen=el&&el.classList.contains("open");close();';
	$js .= 'if(el&&!wasOpen){el.classList.add("open");var b2=document.getElementById("smm-bd");if(b2){b2.classList.add("open");}p.classList.add("is-open");}return;}';
	/* dong */
	$js .= 'if(t.closest("[data-smm=\'close\']")||t.id==="smm-bd"){e.preventDefault();close();return;}';
	/* bam vao 1 dong trong bang thi dong lai */
	$js .= 'if(t.closest(".smm-row")){setTimeout(close,120);return;}';
	/* nut mua ngay */
	$js .= 'var b3=t.closest(".smm-buy");';
	$js .= 'if(b3){e.preventDefault();close();menuOff();var q=document.querySelector(".purchase-form");if(q){q.click();}else{var f=document.querySelector("form.cart button[type=submit],form.cart .single_add_to_cart_button");if(f){f.click();}}}';
	$js .= '});';
	$js .= 'document.addEventListener("keydown",function(e){if(e.key==="Escape"){close();}});';
	$js .= '})();</script>';
	echo $js; // phpcs:ignore
}, 99 );

/* ===== Submenu: SMLIFE Core > Menu Mobile ===== */
add_action( 'admin_menu', function () {
	add_submenu_page(
		'smlife-core',
		'Menu Mobile',
		'Menu Mobile',
		'manage_options',
		'smlife-menu-mobile',
		'smlife_mm_page'
	);
}, 54 );

/* Tai thu vien anh cua WordPress cho nut Chon anh */
add_action( 'admin_enqueue_scripts', function ( $hook ) {
	if ( false !== strpos( (string) $hook, 'smlife-menu-mobile' ) ) {
		wp_enqueue_media();
	}
} );

function smlife_mm_rows_table( $o, $key, $cols ) {
	$feat = ( 'chat_items' === $key );
	echo '<table class="smm-tb"><tr><th style="width:52px">Bật</th><th style="width:130px">' . esc_html( $cols[0] ) . '</th><th style="width:140px">' . esc_html( $cols[1] ) . '</th><th>' . esc_html( $cols[2] ) . '</th><th style="width:250px">Ảnh đại diện</th><th style="width:60px">Màu</th>';
	if ( $feat ) {
		echo '<th style="width:170px">Nổi bật</th>';
	}
	echo '</tr>';
	for ( $i = 0; $i < 4; $i++ ) {
		$r = isset( $o[ $key ][ $i ] ) ? $o[ $key ][ $i ] : array( 'on' => 0, 'name' => '', 'desc' => '', 'url' => '', 'color' => '#e8641e' );
		echo '<tr>';
		echo '<td><label class="smm-sw" style="transform:scale(.85)"><input type="checkbox" name="mm[' . esc_attr( $key ) . '][' . $i . '][on]" value="1" ' . checked( 1, isset( $r['on'] ) ? $r['on'] : 0, false ) . ' /><i></i></label></td>';
		echo '<td><input type="text" name="mm[' . esc_attr( $key ) . '][' . $i . '][name]" value="' . esc_attr( isset( $r['name'] ) ? $r['name'] : '' ) . '" /></td>';
		echo '<td><input type="text" name="mm[' . esc_attr( $key ) . '][' . $i . '][desc]" value="' . esc_attr( isset( $r['desc'] ) ? $r['desc'] : '' ) . '" /></td>';
		echo '<td><input type="text" name="mm[' . esc_attr( $key ) . '][' . $i . '][url]" value="' . esc_attr( isset( $r['url'] ) ? $r['url'] : '' ) . '" /></td>';
		echo '<td><input type="url" class="smm-img-field" name="mm[' . esc_attr( $key ) . '][' . $i . '][img]" value="' . esc_attr( isset( $r['img'] ) ? $r['img'] : '' ) . '" placeholder="Để trống = dùng icon mặc định" style="width:62%" /> <button type="button" class="button smm-pick">Chọn ảnh</button></td>';
		echo '<td><input type="color" name="mm[' . esc_attr( $key ) . '][' . $i . '][color]" value="' . esc_attr( isset( $r['color'] ) ? $r['color'] : '#e8641e' ) . '" /></td>';
		if ( $feat ) {
			echo '<td><label class="smm-sw" style="transform:scale(.85)"><input type="checkbox" name="mm[' . esc_attr( $key ) . '][' . $i . '][feat]" value="1" ' . checked( 1, isset( $r['feat'] ) ? $r['feat'] : 0, false ) . ' /><i></i></label>';
			echo ' <input type="text" name="mm[' . esc_attr( $key ) . '][' . $i . '][badge]" value="' . esc_attr( isset( $r['badge'] ) ? $r['badge'] : '' ) . '" placeholder="Nhãn, vd Kênh chính" style="width:105px" /></td>';
		}
		echo '</tr>';
	}
	echo '</table>';
}

function smlife_mm_page() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}
	$o    = smlife_mm_opt();
	$ics  = smlife_mm_icon_labels();
	$acts = array(
		'link'  => 'Mở link thường',
		'menu'  => 'Mở Menu mobile (như nút burger)',
		'chat'  => 'Mở bảng Chat (Zalo, Messenger)',
		'call'  => 'Mở bảng Gọi (chọn nhân viên)',
		'tel'   => 'Gọi thẳng 1 số (nhập ở ô Link)',
		'blank' => 'Mở link ở tab mới',
	);

	echo '<div class="wrap smlife-wrap">';
	

	echo '<div class="smm-hd"><h1>Menu Mobile</h1>';
	echo '<p>Thanh menu cố định dưới cùng, chỉ hiện trên điện thoại (dưới 850px). Trang sản phẩm tự đổi bố cục: ẩn bớt nút và hiện nút Mua ngay mở popup Đặt hàng nhanh.</p></div>';

	if ( isset( $_GET['updated'] ) ) {
		echo '<div class="notice notice-success is-dismissible"><p>Đã lưu.</p></div>';
	}

	echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '">';
	echo '<input type="hidden" name="action" value="smlife_mm_save" />';
	wp_nonce_field( 'smlife_mm_save', 'smlife_mm_nonce' );

	echo '<div class="smm-card"><h2>Bật tính năng</h2><div class="in">';
	echo '<div class="smm-opt"><label class="smm-sw"><input type="checkbox" name="mm[enabled]" value="1" ' . checked( 1, $o['enabled'], false ) . ' /><i></i></label>';
	echo '<span class="tx"><strong>Bật thanh Menu Mobile</strong><em>Hiện thanh menu cố định dưới cùng khi khách xem bằng điện thoại.</em></span></div>';
	echo '<div class="smm-opt" style="padding-bottom:0"><label class="smm-sw"><input type="checkbox" name="mm[lift_totop]" value="1" ' . checked( 1, $o['lift_totop'], false ) . ' /><i></i></label>';
	echo '<span class="tx"><strong>Đẩy nút Lên đầu trang lên trên</strong><em>Tránh nút mũi tên lên đầu trang bị thanh menu che mất.</em></span></div>';
	echo '</div></div>';

	echo '<div class="smm-card"><h2>Các nút trên thanh (tối đa 5)</h2><div class="in">';
	echo '<table class="smm-tb"><tr><th style="width:52px">Bật</th><th style="width:150px">Icon</th><th style="width:140px">Chữ</th><th>Link / Số điện thoại</th><th style="width:250px">Khi bấm</th><th style="width:100px">Ẩn ở trang SP</th></tr>';
	for ( $i = 0; $i < 5; $i++ ) {
		$it = isset( $o['items'][ $i ] ) ? $o['items'][ $i ] : array( 'on' => 0, 'icon' => 'grid', 'label' => '', 'url' => '', 'act' => 'link', 'hp' => 0 );
		echo '<tr>';
		echo '<td><label class="smm-sw" style="transform:scale(.85)"><input type="checkbox" name="mm[items][' . $i . '][on]" value="1" ' . checked( 1, isset( $it['on'] ) ? $it['on'] : 0, false ) . ' /><i></i></label></td>';
		echo '<td><select name="mm[items][' . $i . '][icon]">';
		foreach ( $ics as $ik => $iname ) {
			echo '<option value="' . esc_attr( $ik ) . '" ' . selected( $ik, isset( $it['icon'] ) ? $it['icon'] : '', false ) . '>' . esc_html( $iname ) . '</option>';
		}
		echo '</select></td>';
		echo '<td><input type="text" name="mm[items][' . $i . '][label]" value="' . esc_attr( isset( $it['label'] ) ? $it['label'] : '' ) . '" /></td>';
		echo '<td><input type="text" name="mm[items][' . $i . '][url]" value="' . esc_attr( isset( $it['url'] ) ? $it['url'] : '' ) . '" placeholder="Để trống nếu mở bảng hoặc mở menu" /></td>';
		echo '<td><select name="mm[items][' . $i . '][act]">';
		foreach ( $acts as $ak => $aname ) {
			echo '<option value="' . esc_attr( $ak ) . '" ' . selected( $ak, isset( $it['act'] ) ? $it['act'] : 'link', false ) . '>' . esc_html( $aname ) . '</option>';
		}
		echo '</select></td>';
		echo '<td style="text-align:center"><input type="checkbox" name="mm[items][' . $i . '][hp]" value="1" ' . checked( 1, isset( $it['hp'] ) ? $it['hp'] : 0, false ) . ' /></td>';
		echo '</tr>';
	}
	echo '</table></div></div>';

	echo '<div class="smm-card"><h2>Bảng Chat</h2><div class="in">';
	echo '<p class="description" style="margin:0 0 12px">Hiện ra khi bấm nút có kiểu "Mở bảng Chat". Ô Link nhập link Zalo hoặc Messenger đầy đủ.</p>';
	echo '<table class="form-table" role="presentation" style="margin:0 0 6px"><tr><th scope="row" style="width:140px">Tiêu đề bảng</th><td><input type="text" name="mm[chat_title]" value="' . esc_attr( $o['chat_title'] ) . '" class="regular-text" /></td></tr></table>';
	smlife_mm_rows_table( $o, 'chat_items', array( 'Tên kênh', 'Mô tả ngắn', 'Link' ) );
	echo '</div></div>';

	echo '<div class="smm-card"><h2>Bảng Gọi Hotline</h2><div class="in">';
	echo '<p class="description" style="margin:0 0 12px">Hiện ra khi bấm nút có kiểu "Mở bảng Gọi". Ô Số điện thoại chỉ cần nhập số, ví dụ 0914913179.</p>';
	echo '<table class="form-table" role="presentation" style="margin:0 0 6px"><tr><th scope="row" style="width:140px">Tiêu đề bảng</th><td><input type="text" name="mm[call_title]" value="' . esc_attr( $o['call_title'] ) . '" class="regular-text" /></td></tr></table>';
	smlife_mm_rows_table( $o, 'call_items', array( 'Tên nhân viên', 'Mô tả ngắn', 'Số điện thoại' ) );
	echo '</div></div>';

	echo '<div class="smm-card"><h2>Nút Mua ngay (trang sản phẩm)</h2><div class="in">';
	echo '<div class="smm-opt"><label class="smm-sw"><input type="checkbox" name="mm[buy_on]" value="1" ' . checked( 1, $o['buy_on'], false ) . ' /><i></i></label>';
	echo '<span class="tx"><strong>Hiện nút Mua ngay trên trang sản phẩm</strong><em>Bấm vào sẽ mở popup Đặt hàng nhanh, giống nút Mua ngay trong trang.</em></span></div>';
	echo '<table class="form-table" role="presentation">';
	echo '<tr><th scope="row">Chữ trên nút</th><td><input type="text" name="mm[buy_label]" value="' . esc_attr( $o['buy_label'] ) . '" class="regular-text" /></td></tr>';
	echo '<tr><th scope="row">Màu nền nút</th><td><input type="color" name="mm[buy_bg]" value="' . esc_attr( $o['buy_bg'] ) . '" /></td></tr>';
	echo '<tr><th scope="row">Màu chữ nút</th><td><input type="color" name="mm[buy_color]" value="' . esc_attr( $o['buy_color'] ) . '" /></td></tr>';
	echo '<tr><th scope="row">Chiều rộng nút (%)</th><td><input type="number" name="mm[buy_w]" value="' . esc_attr( $o['buy_w'] ) . '" min="22" max="60" /> <span class="description">Phần trăm chiều ngang thanh, tối đa 148px. Mặc định 33, ưu tiên chỗ cho 3 nút bên trái.</span></td></tr>';
	echo '<tr><th scope="row">Bo góc trái nút (px)</th><td><input type="number" name="mm[buy_r]" value="' . esc_attr( $o['buy_r'] ) . '" min="0" max="28" /> <span class="description">Chỉ bo 2 góc bên trái, mép phải vẫn tràn sát biên màn hình. Để 0 nếu muốn vuông.</span></td></tr>';
	echo '</table></div></div>';

	echo '<div class="smm-card"><h2>Giao diện thanh</h2><div class="in"><table class="form-table" role="presentation">';
	echo '<tr><th scope="row">Màu nền thanh</th><td><input type="color" name="mm[bg]" value="' . esc_attr( $o['bg'] ) . '" /></td></tr>';
	echo '<tr><th scope="row">Màu chữ</th><td><input type="color" name="mm[color]" value="' . esc_attr( $o['color'] ) . '" /></td></tr>';
	echo '<tr><th scope="row">Màu icon</th><td><input type="color" name="mm[icon_color]" value="' . esc_attr( $o['icon_color'] ) . '" /></td></tr>';
	echo '<tr><th scope="row">Chiều cao thanh (px)</th><td><input type="number" name="mm[height]" value="' . esc_attr( $o['height'] ) . '" min="44" max="90" /></td></tr>';
	echo '</table></div></div>';

	echo '<div class="smm-note"><b>Ghi chú</b><ul>';
	echo '<li>Nút kiểu <b>Mở Menu mobile</b> sẽ mở đúng menu của nút burger góc phải, không cần tạo danh mục riêng.</li>';
	echo '<li>Nút tick <b>Ẩn ở trang SP</b> sẽ biến mất khi khách vào trang sản phẩm, nhường chỗ cho nút Mua ngay.</li>';
	echo '<li>Bảng Chat và Bảng Gọi trượt lên từ dưới, bấm lại chính nút đó hoặc bấm ra ngoài để đóng.</li>';
	echo '<li>Ô <b>Ảnh đại diện</b>: bấm Chọn ảnh để lấy từ Thư viện. Nên dùng ảnh vuông từ 80x80px trở lên. Để trống thì dùng icon mặc định (bảng Chat) hoặc chữ cái đầu tên (bảng Gọi).</li>';
	echo '</ul></div>';

	submit_button( 'Lưu cài đặt' );
	echo '</form>';
	echo '<script>(function(){document.querySelectorAll(".smm-pick").forEach(function(btn){btn.addEventListener("click",function(e){e.preventDefault();var f=this.previousElementSibling;while(f&&!f.classList.contains("smm-img-field")){f=f.previousElementSibling;}if(!window.wp||!window.wp.media){return;}var fr=wp.media({title:"Chọn ảnh",multiple:false});fr.on("select",function(){var a=fr.state().get("selection").first().toJSON();if(f){f.value=a.url;}});fr.open();});});})();</script>';
	echo '</div>';
}

add_action( 'admin_post_smlife_mm_save', function () {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( 'Không đủ quyền.' );
	}
	check_admin_referer( 'smlife_mm_save', 'smlife_mm_nonce' );
	$in  = isset( $_POST['mm'] ) && is_array( $_POST['mm'] ) ? wp_unslash( $_POST['mm'] ) : array();
	$def = smlife_mm_opt();
	$ic  = smlife_mm_icons();
	$ok  = array( 'link', 'menu', 'chat', 'call', 'tel', 'blank' );

	$items = array();
	$rows  = isset( $in['items'] ) && is_array( $in['items'] ) ? $in['items'] : array();
	for ( $i = 0; $i < 5; $i++ ) {
		$r       = isset( $rows[ $i ] ) && is_array( $rows[ $i ] ) ? $rows[ $i ] : array();
		$items[] = array(
			'on'    => empty( $r['on'] ) ? 0 : 1,
			'icon'  => ( isset( $r['icon'] ) && isset( $ic[ $r['icon'] ] ) ) ? $r['icon'] : 'grid',
			'label' => isset( $r['label'] ) ? sanitize_text_field( $r['label'] ) : '',
			'url'   => isset( $r['url'] ) ? sanitize_text_field( $r['url'] ) : '',
			'act'   => ( isset( $r['act'] ) && in_array( $r['act'], $ok, true ) ) ? $r['act'] : 'link',
			'hp'    => empty( $r['hp'] ) ? 0 : 1,
		);
	}

	$grab = function ( $key, $dcolor ) use ( $in ) {
		$out  = array();
		$rows = isset( $in[ $key ] ) && is_array( $in[ $key ] ) ? $in[ $key ] : array();
		for ( $i = 0; $i < 4; $i++ ) {
			$r     = isset( $rows[ $i ] ) && is_array( $rows[ $i ] ) ? $rows[ $i ] : array();
			$col   = isset( $r['color'] ) ? sanitize_hex_color( $r['color'] ) : '';
			$out[] = array(
				'on'    => empty( $r['on'] ) ? 0 : 1,
				'name'  => isset( $r['name'] ) ? sanitize_text_field( $r['name'] ) : '',
				'desc'  => isset( $r['desc'] ) ? sanitize_text_field( $r['desc'] ) : '',
				'url'   => isset( $r['url'] ) ? sanitize_text_field( $r['url'] ) : '',
				'img'   => isset( $r['img'] ) ? esc_url_raw( $r['img'] ) : '',
				'color' => $col ? $col : $dcolor,
				'feat'  => empty( $r['feat'] ) ? 0 : 1,
				'badge' => isset( $r['badge'] ) ? sanitize_text_field( $r['badge'] ) : '',
			);
		}
		return $out;
	};

	$hexf = function ( $k, $d ) use ( $in ) {
		$v = isset( $in[ $k ] ) ? sanitize_hex_color( $in[ $k ] ) : '';
		return $v ? $v : $d;
	};

	$new = array(
		'enabled'    => empty( $in['enabled'] ) ? 0 : 1,
		'bg'         => $hexf( 'bg', $def['bg'] ),
		'color'      => $hexf( 'color', $def['color'] ),
		'icon_color' => $hexf( 'icon_color', $def['icon_color'] ),
		'height'     => isset( $in['height'] ) ? max( 44, min( 90, absint( $in['height'] ) ) ) : 56,
		'buy_on'     => empty( $in['buy_on'] ) ? 0 : 1,
		'buy_label'  => isset( $in['buy_label'] ) ? sanitize_text_field( $in['buy_label'] ) : 'Mua Ngay',
		'buy_bg'     => $hexf( 'buy_bg', $def['buy_bg'] ),
		'buy_color'  => $hexf( 'buy_color', $def['buy_color'] ),
		'buy_w'      => isset( $in['buy_w'] ) ? max( 22, min( 60, absint( $in['buy_w'] ) ) ) : 33,
		'buy_r'      => isset( $in['buy_r'] ) ? max( 0, min( 28, absint( $in['buy_r'] ) ) ) : 12,
		'lift_totop' => empty( $in['lift_totop'] ) ? 0 : 1,
		'items'      => $items,
		'chat_title' => isset( $in['chat_title'] ) ? sanitize_text_field( $in['chat_title'] ) : $def['chat_title'],
		'chat_items' => $grab( 'chat_items', '#0068ff' ),
		'call_title' => isset( $in['call_title'] ) ? sanitize_text_field( $in['call_title'] ) : $def['call_title'],
		'call_items' => $grab( 'call_items', '#e8641e' ),
	);
	update_option( 'smlife_mm', $new );
	/* 1.24.0: truoc day luu xong khong xoa cache, so moi chi len tung trang. Gio vao danh sach cho dem */
	smlife_xoa_cache( 'Menu Mobile' );
	wp_safe_redirect( admin_url( 'admin.php?page=smlife-menu-mobile&updated=1' ) );
	exit;
} );
