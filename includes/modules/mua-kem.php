<?php
/**
 * SMLIFE Core - Module: Mua kem san pham
 *
 * Khoi "Khach thuong mua kem" tren trang san pham, format kieu
 * bedkingdom: MOT THE bo tron dong/mo duoc. Header (luon hien, bam de
 * mo/dong) la MOT DONG duy nhat: icon qua tang + tieu de (vd "Mua kem
 * nem giam 5%", tu cat "..." neu dai) + badge "Ma: XXX" (bam la copy,
 * khong lam dong the, khong bao gio nhay dong) + chevron. Khong hien
 * "Gia tu". Body (mac dinh DONG): dai the san pham toi gian (anh + ten
 * 2 dong cat "..." + gia), moi the co vien rieng, desktop thay 3 the +
 * mot phan the thu 4, mobile 2 the + mot phan, truot ngang. Toi da 10
 * san pham. Chu dung mau xam dam #4a4a4a (mau base Flatsome).
 *
 * Moi NHOM ap dung cho NHIEU danh muc cung luc. San pham thuoc danh muc
 * nao co nhom bat thi hien khoi nay (nhom khop dau tien theo thu tu tao).
 * Dat khoi bang shortcode [smlife_mua_kem] trong Product Layout.
 *
 * Ghi chu ky thuat:
 *   - Nhom luu trong option smlife_mk['groups'][gid] voi truong 'terms'
 *     la mang term_id. Ban cu (1.6.0) luu theo 1 term_id lam khoa, duoc
 *     tu dong doc tuong thich: thieu 'terms' thi coi khoa chinh la term.
 *   - The san pham tu dung (khong dung [products]) de nhe va dong nhat.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/* ===== 1. Cai dat ===== */

function smlife_mk_defaults() {
	return array(
		'enabled' => 1,
		'tieu_de' => 'Khách thường mua kèm',
		'cols'    => 3,
		'groups'  => array(),
	);
}

function smlife_mk_opt() {
	$o = get_option( 'smlife_mk', array() );
	$o = is_array( $o ) ? array_merge( smlife_mk_defaults(), $o ) : smlife_mk_defaults();
	if ( ! is_array( $o['groups'] ) ) { $o['groups'] = array(); }
	return $o;
}

/* Danh sach nhom da chuan hoa: luon co 'terms' (mang int) va 'mota'. */
function smlife_mk_groups() {
	$o   = smlife_mk_opt();
	$out = array();
	foreach ( $o['groups'] as $gid => $g ) {
		if ( ! is_array( $g ) ) { continue; }
		if ( empty( $g['terms'] ) || ! is_array( $g['terms'] ) ) {
			$g['terms'] = array( (int) $gid ); /* ban cu: khoa = term_id */
		}
		$g['terms'] = array_values( array_filter( array_map( 'intval', $g['terms'] ) ) );
		if ( ! isset( $g['mota'] ) )    { $g['mota'] = ''; }
		if ( ! isset( $g['rang'] ) )    { $g['rang'] = 1; }
		if ( ! isset( $g['tieu_de'] ) ) { $g['tieu_de'] = ''; }
		if ( ! isset( $g['ma'] ) )      { $g['ma'] = ''; }
		$g['ma_id'] = isset( $g['ma_id'] ) ? (int) $g['ma_id'] : 0;
		if ( ! isset( $g['ghi_chu'] ) ) { $g['ghi_chu'] = ''; }
		if ( ! isset( $g['sp'] ) )      { $g['sp'] = ''; }
		if ( ! isset( $g['dm'] ) )      { $g['dm'] = 0; }
		$g['dm'] = (int) $g['dm'];
		$out[ $gid ] = $g;
	}
	return $out;
}

/* ===== Ma giam gia lien ket coupon (1.23.0) =====
 * Nhom luu ID coupon (ma_id). Ma hien tren web luon doc tu coupon, nen ben
 * Coupon doi ma thi mua kem doi theo. 'ma' chi con la ban sao du phong cho
 * nhom cu chua gan ID. */
function smlife_mk_cp_post( $id ) {
	$p = $id ? get_post( (int) $id ) : null;
	return ( $p && 'shop_coupon' === $p->post_type ) ? $p : null;
}

/* Ma dang dung cua nhom (chu hoa), rong neu khong co / coupon da bi xoa */
function smlife_mk_ma( $g ) {
	if ( ! empty( $g['ma_id'] ) ) {
		$p = smlife_mk_cp_post( $g['ma_id'] );
		return ( $p && 'trash' !== $p->post_status ) ? strtoupper( (string) $p->post_title ) : '';
	}
	return strtoupper( trim( (string) $g['ma'] ) );
}

/* Tinh trang coupon de canh bao trong trang quan tri */
function smlife_mk_cp_tt( $id ) {
	$p = smlife_mk_cp_post( $id );
	if ( ! $p || 'trash' === $p->post_status ) {
		return 'xoa';
	}
	if ( 'publish' !== $p->post_status ) {
		return 'nhap';
	}
	if ( class_exists( 'WC_Coupon' ) ) {
		$c   = new WC_Coupon( (int) $id );
		$het = $c->get_date_expires();
		if ( $het && $het->getTimestamp() < time() ) {
			return 'het_han';
		}
		if ( $c->get_usage_limit() && $c->get_usage_count() >= $c->get_usage_limit() ) {
			return 'het_luot';
		}
	}
	return 'ok';
}

/* Nhom cu chi luu chu: tim coupon trung ma va gan ID, chay 1 lan khi mo trang quan tri */
function smlife_mk_gan_id_cu() {
	if ( ! function_exists( 'wc_get_coupon_id_by_code' ) ) {
		return;
	}
	$o  = smlife_mk_opt();
	$ch = false;
	foreach ( $o['groups'] as $gid => $g ) {
		if ( ! is_array( $g ) || ! empty( $g['ma_id'] ) || '' === trim( (string) ( isset( $g['ma'] ) ? $g['ma'] : '' ) ) ) {
			continue;
		}
		$id = (int) wc_get_coupon_id_by_code( trim( (string) $g['ma'] ) );
		if ( $id ) {
			$o['groups'][ $gid ]['ma_id'] = $id;
			$ch = true;
		}
	}
	if ( $ch ) {
		update_option( 'smlife_mk', $o, false );
	}
}

/* Tim nhom cau hinh cho mot san pham: nhom bat dau tien co danh muc khop. */
function smlife_mk_group_for( $product_id ) {
	$o = smlife_mk_opt();
	if ( empty( $o['enabled'] ) ) {
		return null;
	}
	$groups = smlife_mk_groups();
	if ( ! $groups ) {
		return null;
	}
	$terms = wp_get_post_terms( $product_id, 'product_cat', array( 'fields' => 'ids' ) );
	if ( is_wp_error( $terms ) || ! $terms ) {
		return null;
	}
	$terms = array_map( 'intval', $terms );
	foreach ( $groups as $g ) {
		if ( empty( $g['on'] ) ) { continue; }
		if ( array_intersect( $terms, $g['terms'] ) ) {
			return $g;
		}
	}
	return null;
}

function smlife_mk_ids( $g ) {
	$ids = array();
	foreach ( explode( ',', (string) $g['sp'] ) as $x ) {
		$x = (int) trim( $x );
		if ( $x > 0 && ! in_array( $x, $ids, true ) ) { $ids[] = $x; }
		if ( count( $ids ) >= 10 ) { break; }
	}
	return $ids;
}

/* ===== 1b. Rang buoc ma giam gia theo danh muc nhom =====
 *
 * Van de: coupon Woo / Smart Coupons chi gioi han "giam cho san pham nao"
 * (tab Han che su dung > Danh muc san pham), KHONG co dieu kien "gio hang
 * phai co san pham thuoc danh muc khac". Vi du ma GOINEM5 giam 5% cho
 * nem/goi nhung chi khi gio co GIUONG - Woo khong lam duoc.
 *
 * Giai phap: nhom mua kem da biet san ma + danh muc kich hoat (giuong).
 * Hook woocommerce_coupon_is_valid: ma nao trung voi ma cua nhom co bat
 * "Rang buoc ma" thi gio hang PHAI co it nhat 1 san pham thuoc danh muc
 * cua nhom, khong thi bao loi ro rang cho khach. Phan "giam cho nem/goi"
 * van cau hinh trong coupon (Danh muc san pham = Nem, Goi).
 *
 * Rang buoc ap dung ca khi nhom dang tat hien thi (tranh truong hop tat
 * khoi hien thi lam ma tro thanh vo dieu kien).
 */

add_filter( 'woocommerce_coupon_is_valid', 'smlife_mk_coupon_valid', 10, 2 );
function smlife_mk_coupon_valid( $valid, $coupon ) {
	if ( ! $valid || ! is_object( $coupon ) || ! method_exists( $coupon, 'get_code' ) ) {
		return $valid;
	}
	$code = strtolower( trim( (string) $coupon->get_code() ) );
	if ( '' === $code ) {
		return $valid;
	}
	foreach ( smlife_mk_groups() as $g ) {
		if ( empty( $g['rang'] ) ) { continue; }
		if ( ! empty( $g['ma_id'] ) ) {
			if ( (int) $coupon->get_id() !== (int) $g['ma_id'] ) { continue; }
		} elseif ( '' === trim( (string) $g['ma'] ) || strtolower( trim( (string) $g['ma'] ) ) !== $code ) {
			continue;
		}

		if ( ! function_exists( 'WC' ) || ! WC()->cart ) { return $valid; }
		foreach ( WC()->cart->get_cart() as $item ) {
			$pid = ! empty( $item['product_id'] ) ? (int) $item['product_id'] : 0;
			if ( ! $pid ) { continue; }
			$terms = wp_get_post_terms( $pid, 'product_cat', array( 'fields' => 'ids' ) );
			if ( ! is_wp_error( $terms ) && array_intersect( array_map( 'intval', $terms ), $g['terms'] ) ) {
				return $valid; /* gio co san pham thuoc danh muc nhom -> ma hop le */
			}
		}
		$names = array();
		foreach ( $g['terms'] as $tid ) {
			$t = get_term( $tid, 'product_cat' );
			if ( $t && ! is_wp_error( $t ) ) { $names[] = $t->name; }
		}
		throw new Exception( sprintf(
			'Mã %s chỉ áp dụng khi giỏ hàng có sản phẩm thuộc: %s.',
			strtoupper( $coupon->get_code() ),
			$names ? implode( ', ', array_unique( $names ) ) : 'danh mục áp dụng'
		) );
	}
	return $valid;
}

/* ===== 2. Shortcode ===== */

add_shortcode( 'smlife_mua_kem', 'smlife_mk_sc' );
function smlife_mk_sc() {
	if ( is_admin() ) { return ''; }
	$pid = get_the_ID();
	if ( ! $pid ) { return ''; }
	$g = smlife_mk_group_for( $pid );
	if ( ! $g ) { return ''; }
	$ids = smlife_mk_ids( $g );
	if ( ! $ids ) { return ''; }

	$o = smlife_mk_opt();

	/* Nap truoc bai viet, thuoc tinh va danh muc cua ca 10 san pham trong
	   mot luot. Khong lam buoc nay thi moi vong lap duoi day la mot lan hoi
	   co so du lieu rieng, 10 san pham thanh ca chuc cau lenh. */
	if ( function_exists( '_prime_post_caches' ) ) {
		_prime_post_caches( $ids, true, true );
	}

	/* The san pham toi gian */
	$cards = '';
	foreach ( $ids as $id ) {
		$p = function_exists( 'wc_get_product' ) ? wc_get_product( $id ) : null;
		if ( ! $p || 'publish' !== $p->get_status() ) { continue; }
		$cur = (float) $p->get_price();
		$reg = (float) $p->get_regular_price();
		if ( $cur > 0 ) {
			$gia = ( $reg > $cur ) ? '<del>' . wc_price( $reg ) . '</del> ' . wc_price( $cur ) : wc_price( $cur );
		} else {
			$gia = 'Liên hệ';
		}
		$cards .= '<a class="smlife-mk-card" href="' . esc_url( get_permalink( $id ) ) . '">';
		$cards .= '<span class="anh">' . $p->get_image( 'woocommerce_thumbnail' ) . '</span>';
		$cards .= '<span class="ten">' . esc_html( $p->get_name() ) . '</span>';
		$cards .= '<span class="gia">' . $gia . '</span>';
		$cards .= '</a>';
	}
	if ( '' === $cards ) { return ''; }

	/* The xanh cuoi day: dan sang danh muc cua nhom san pham mua kem */
	if ( (int) $g['dm'] > 0 ) {
		$tdm = get_term( (int) $g['dm'], 'product_cat' );
		if ( $tdm && ! is_wp_error( $tdm ) ) {
			$udm = get_term_link( $tdm );
			if ( ! is_wp_error( $udm ) ) {
				$cards .= '<a class="smlife-mk-xem" href="' . esc_url( $udm ) . '">';
				$cards .= '<span class="ic"><svg width="19" height="19" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polyline points="9 18 15 12 9 6"/></svg></span>';
				$cards .= '<span class="l1">Xem tất cả</span>';
				$cards .= '<span class="l2">' . esc_html( $tdm->name ) . '</span>';
				$cards .= '</a>';
			}
		}
	}

	$tieu_de = '' !== trim( (string) $g['tieu_de'] ) ? $g['tieu_de'] : $o['tieu_de'];

	$ic_qua = '<svg class="smlife-mk-ic" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="' . esc_attr( smlife_mau() ) . '" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polyline points="20 12 20 22 4 22 4 12"/><rect x="2" y="7" width="20" height="5"/><line x1="12" y1="22" x2="12" y2="7"/><path d="M12 7H7.5a2.5 2.5 0 0 1 0-5C11 2 12 7 12 7z"/><path d="M12 7h4.5a2.5 2.5 0 0 0 0-5C13 2 12 7 12 7z"/></svg>';
	$ic_mt  = '<svg class="smlife-mk-mt" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#9a9a9a" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polyline points="6 9 12 15 18 9"/></svg>';

	$h  = '<div class="smlife-mk">';
	$h .= '<div class="smlife-mk-hd" role="button" tabindex="0" aria-expanded="false" title="Bấm để xem sản phẩm">';
	$h .= $ic_qua;
	$h .= '<span class="smlife-mk-t">' . esc_html( $tieu_de ) . '</span>';
	/* Badge chi in ma cho gon, phan dien giai da co dong rieng ben duoi */
	$ma_web = smlife_mk_ma( $g );
	/* 1.24.0: coupon het han / het luot / nhap thi khong hien ma, khach copy ve
	   ra checkout bao loi */
	if ( '' !== $ma_web && ! empty( $g['ma_id'] ) && 'ok' !== smlife_mk_cp_tt( $g['ma_id'] ) ) {
		$ma_web = '';
	}
	if ( '' !== $ma_web ) {
		$h .= '<button type="button" class="smlife-mk-ma" data-ma="' . esc_attr( $ma_web ) . '" title="Bấm để copy mã">';
		$h .= 'Mã: <b>' . esc_html( $ma_web ) . '</b>';
		$h .= '</button>';
	}
	$h .= $ic_mt;
	$h .= '</div>';

	$dg = '';
	if ( '' !== trim( (string) $g['ghi_chu'] ) ) {
		$dg = '<p class="smlife-mk-dg">' . nl2br( esc_html( $g['ghi_chu'] ) ) . '</p>';
	}
	$h .= '<div class="smlife-mk-bd"><div class="smlife-mk-in">' . $dg . '<div class="smlife-mk-sp">' . $cards . '</div></div></div>';
	$h .= '</div>';
	return $h;
}

/* ===== 3. CSS + JS copy ma ===== */

add_action( 'wp_head', function () {
	if ( ! function_exists( 'is_product' ) || ! is_product() ) { return; }
	$o = smlife_mk_opt();
	if ( empty( $o['enabled'] ) ) { return; }
	$fb  = 'calc((100% - 30px)/3.3)';
	$mau = smlife_mau();
	$dam = smlife_mau_dam();
	$c  = '.smlife-mk{border:1px solid #ececec;border-radius:12px;background:#fff;margin:22px 0 0;overflow:hidden}';
	$c .= '.smlife-mk-hd{display:flex;align-items:center;gap:10px;padding:13px 16px;cursor:pointer;-webkit-user-select:none;user-select:none}';
	$c .= '.smlife-mk-hd:hover .smlife-mk-t{color:' . $mau . '}';
	$c .= '.smlife-mk-t{flex:0 1 auto;min-width:0;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;font-size:15px;font-weight:700;color:#4a4a4a;line-height:1.3;transition:color .15s}';
	$c .= '.smlife-mk-ic{flex:0 0 auto}';
	$c .= '.smlife-mk-mt{flex:0 0 auto;margin-left:auto;transition:transform .25s ease}';
	$c .= '.smlife-mk.mo .smlife-mk-mt{transform:rotate(180deg)}';
	$c .= '.smlife-mk-ma{flex:0 0 auto;background:' . $mau . ';color:#fff;border:0;border-radius:6px;font-size:12px;font-weight:600;line-height:1;padding:6px 10px;margin:0;cursor:pointer;min-height:0;white-space:nowrap}';
	$c .= '.smlife-mk-ma b{color:#fff}';
	$c .= '.smlife-mk-ma:hover{background:' . $dam . ';color:#fff}';
	$c .= '.smlife-mk-gc{font-size:13px;color:#777}';
	$c .= '.smlife-mk-bd{display:grid;grid-template-rows:0fr;transition:grid-template-rows .28s ease}';
	$c .= '.smlife-mk.mo .smlife-mk-bd{grid-template-rows:1fr}';
	$c .= '.smlife-mk.mo .smlife-mk-hd{border-bottom:1px solid #f1f1f1}';
	$c .= '.smlife-mk-in{overflow:hidden;min-height:0}';
	$c .= '.smlife-mk-sp{display:flex;gap:10px;overflow-x:auto;scroll-snap-type:x mandatory;-webkit-overflow-scrolling:touch;scrollbar-width:thin;scrollbar-color:#ddd transparent;padding:13px 16px 10px}';
	$c .= '.smlife-mk-sp::-webkit-scrollbar{height:6px}';
	$c .= '.smlife-mk-sp::-webkit-scrollbar-thumb{background:#ddd;border-radius:3px}';
	$c .= '.smlife-mk-sp::-webkit-scrollbar-track{background:transparent}';
	$c .= '.smlife-mk-card{flex:0 0 ' . $fb . ';max-width:' . $fb . ';scroll-snap-align:start;display:block;text-decoration:none;border:1px solid #f0f0f0;border-radius:10px;padding:8px 8px 10px;background:#fff;transition:border-color .15s,box-shadow .15s}';
	$c .= '.smlife-mk-card:hover{border-color:' . smlife_mau_nhat( 0.62 ) . ';box-shadow:0 2px 8px rgba(0,0,0,.05)}';
	$c .= '.smlife-mk-card .anh{display:block}';
	$c .= '.smlife-mk-card .anh img{display:block;width:100%;height:auto;border-radius:7px}';
	$c .= '.smlife-mk-card .ten{display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden;font-size:13.5px;font-weight:500;line-height:1.35;min-height:37px;color:#4a4a4a;margin:8px 0 3px}';
	$c .= '.smlife-mk-card:hover .ten{color:' . $mau . '}';
	$c .= '.smlife-mk-card .gia{display:block;font-size:14px;font-weight:700;color:' . $mau . ';line-height:1.3}';
	$c .= '.smlife-mk-card .gia .woocommerce-Price-amount{font-weight:700;color:' . $mau . '}';
	$c .= '.smlife-mk-card .gia del,.smlife-mk-card .gia del .woocommerce-Price-amount{color:#9a9a9a;font-weight:400;font-size:12px}';
	/* Dong dien giai chuong trinh, chi thay khi bung the */
	$c .= '.smlife-mk-dg{font-size:12.5px;line-height:1.55;color:#8d8d8d;padding:12px 16px 0;margin:0}';
	/* The xanh Xem tat ca, cao bang the san pham nho flex */
	$c .= '.smlife-mk-xem{flex:0 0 ' . $fb . ';max-width:' . $fb . ';scroll-snap-align:start;display:flex;flex-direction:column;align-items:center;justify-content:center;gap:10px;text-align:center;text-decoration:none !important;border-radius:10px;padding:14px 10px;background:linear-gradient(160deg,#1aa553,#0c8038);box-shadow:0 3px 10px rgba(13,128,60,.22);transition:transform .15s,box-shadow .15s}';
	$c .= '.smlife-mk-xem:hover{transform:translateY(-2px);box-shadow:0 6px 16px rgba(13,128,60,.3)}';
	$c .= '.smlife-mk-xem .ic{width:40px;height:40px;border-radius:50%;background:rgba(255,255,255,.2);display:flex;align-items:center;justify-content:center}';
	$c .= '.smlife-mk-xem .l1{font-size:14px;font-weight:700;line-height:1.25;color:#fff}';
	$c .= '.smlife-mk-xem .l2{font-size:11.5px;font-weight:500;line-height:1.35;color:rgba(255,255,255,.88)}';
	$c .= '@media (max-width:549px){.smlife-mk-dg{padding:11px 13px 0;font-size:12px}.smlife-mk-xem{flex:0 0 calc((100% - 13px)/2.25);max-width:calc((100% - 13px)/2.25)}}';
	$c .= '@media (max-width:549px){.smlife-mk-hd{padding:12px 13px;gap:8px}.smlife-mk-t{font-size:14px}.smlife-mk-sp{padding:12px 13px 8px}.smlife-mk-card{flex:0 0 calc((100% - 13px)/2.25);max-width:calc((100% - 13px)/2.25)}}';
	echo '<style id="smlife-mk">' . $c . '</style>'; // phpcs:ignore
	$js  = 'document.addEventListener("click",function(e){';
	$js .= 'var b=e.target.closest(".smlife-mk-ma");';
	$js .= 'if(b){var m=b.getAttribute("data-ma")||"";if(navigator.clipboard){navigator.clipboard.writeText(m);}var t=b.innerHTML;b.innerHTML="Đã copy mã!";setTimeout(function(){b.innerHTML=t;},1400);return;}';
	$js .= 'var hd=e.target.closest(".smlife-mk-hd");';
	$js .= 'if(hd){var w=hd.closest(".smlife-mk");w.classList.toggle("mo");hd.setAttribute("aria-expanded",w.classList.contains("mo")?"true":"false");}';
	$js .= '});';
	$js .= 'document.addEventListener("keydown",function(e){if(e.key!=="Enter"&&e.key!==" ")return;var hd=e.target.closest?e.target.closest(".smlife-mk-hd"):null;if(hd){e.preventDefault();hd.click();}});';
	echo '<script>' . $js . '</script>';
}, 99 );

/* ===== 4. Trang cai dat ===== */

add_action( 'admin_menu', function () {
	add_submenu_page( 'smlife-core', 'Mua kèm sản phẩm', 'Mua kèm sản phẩm', 'manage_options', 'smlife-mua-kem', 'smlife_mk_page' );
}, 18 );

/* ===== 4. QUAN TRI (1.23.0) =====
 * Bang nhom ve bang JS tu du lieu JSON: sap xep theo cot, loc, tim kiem,
 * sua nhanh ngay trong dong. Moi thao tac luu qua AJAX, khong chuyen trang.
 */

/* Danh muc san pham: id => array( ten, duong dan cha > con, so san pham ) */
function smlife_mk_cats() {
	$terms = get_terms( array( 'taxonomy' => 'product_cat', 'hide_empty' => false ) );
	$out   = array();
	if ( is_wp_error( $terms ) || ! $terms ) {
		return $out;
	}
	$by = array();
	foreach ( $terms as $t ) {
		$by[ (int) $t->term_id ] = $t;
	}
	foreach ( $by as $id => $t ) {
		$path = array( $t->name );
		$p    = (int) $t->parent;
		$i    = 0;
		while ( $p && isset( $by[ $p ] ) && $i < 8 ) {
			array_unshift( $path, $by[ $p ]->name );
			$p = (int) $by[ $p ]->parent;
			$i++;
		}
		$out[ $id ] = array( 'n' => $t->name, 'p' => implode( ' › ', $path ), 'c' => (int) $t->count, 'par' => (int) $t->parent );
	}
	uasort( $out, function ( $a, $b ) {
		return strcmp( $a['p'], $b['p'] );
	} );
	return $out;
}

/* Chuan hoa du lieu nhom gui len tu trinh duyet */
function smlife_mk_lam_sach( $in ) {
	$in    = is_array( $in ) ? $in : array();
	$terms = isset( $in['terms'] ) ? array_values( array_unique( array_filter( array_map( 'intval', (array) $in['terms'] ) ) ) ) : array();
	$sp    = isset( $in['sp'] ) ? preg_replace( '/[^0-9,\s]/', '', (string) $in['sp'] ) : '';
	$ids   = array();
	foreach ( explode( ',', $sp ) as $x ) {
		$x = (int) trim( $x );
		if ( $x > 0 && ! in_array( $x, $ids, true ) ) {
			$ids[] = $x;
		}
	}
	$kq = array(
		'on'      => empty( $in['on'] ) ? 0 : 1,
		'terms'   => $terms,
		'tieu_de' => isset( $in['tieu_de'] ) ? sanitize_text_field( $in['tieu_de'] ) : '',
		'mota'    => '',
		'ma'      => '',
		'ma_id'   => 0,
		'ghi_chu' => isset( $in['ghi_chu'] ) ? sanitize_textarea_field( $in['ghi_chu'] ) : '',
		'sp'      => implode( ', ', array_slice( $ids, 0, 10 ) ),
		'rang'    => empty( $in['rang'] ) ? 0 : 1,
		'dm'      => isset( $in['dm'] ) ? absint( $in['dm'] ) : 0,
	);
	/* Ma giam gia: chon coupon theo ID, luu kem ban sao ma de du phong */
	$cid = isset( $in['ma_id'] ) ? absint( $in['ma_id'] ) : 0;
	$cp  = smlife_mk_cp_post( $cid );
	if ( $cp ) {
		$kq['ma_id'] = $cid;
		$kq['ma']    = (string) $cp->post_title;
	}
	return $kq;
}

/* Du lieu 1 nhom gui ve trinh duyet */
function smlife_mk_ra_js( $gid, $g ) {
	return array(
		'gid'     => (int) $gid,
		'on'      => empty( $g['on'] ) ? 0 : 1,
		'terms'   => array_values( array_map( 'intval', (array) $g['terms'] ) ),
		'tieu_de' => (string) $g['tieu_de'],
		'ma'      => smlife_mk_ma( $g ),
		'ma_id'   => isset( $g['ma_id'] ) ? (int) $g['ma_id'] : 0,
		'ma_tt'   => ! empty( $g['ma_id'] ) ? smlife_mk_cp_tt( $g['ma_id'] ) : ( '' !== trim( (string) $g['ma'] ) ? 'chu' : '' ),
		'ghi_chu' => (string) $g['ghi_chu'],
		'sp'      => (string) $g['sp'],
		'rang'    => empty( $g['rang'] ) ? 0 : 1,
		'dm'      => (int) $g['dm'],
	);
}

function smlife_mk_ajax_kt() {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_send_json_error( array( 'm' => 'Không đủ quyền.' ), 403 );
	}
	check_ajax_referer( 'smlife_mk_ajax', '_n' );
}

/* Luu xong lam moi cache trang (giong ban cu) */
function smlife_mk_luu_opt( $o ) {
	update_option( 'smlife_mk', $o, false );
	if ( function_exists( 'rocket_clean_domain' ) && function_exists( 'smlife_xoa_cache' ) ) {
		smlife_xoa_cache();
	}
}

/* Them / sua mot nhom */
add_action( 'wp_ajax_smlife_mk_luu', function () {
	smlife_mk_ajax_kt();
	$raw = isset( $_POST['g'] ) ? json_decode( wp_unslash( $_POST['g'] ), true ) : array(); // phpcs:ignore
	$g   = smlife_mk_lam_sach( $raw );
	if ( ! $g['terms'] ) {
		wp_send_json_error( array( 'm' => 'Chưa chọn danh mục áp dụng.' ) );
	}
	$o   = smlife_mk_opt();
	$gid = isset( $_POST['gid'] ) ? (int) $_POST['gid'] : 0;
	/* Chon "Giu ma chu cu": giu nguyen chu ma cua nhom cu chua lien ket coupon */
	if ( ! empty( $raw['ma_cu'] ) && isset( $o['groups'][ $gid ]['ma'] ) && empty( $g['ma_id'] ) ) {
		$g['ma'] = sanitize_text_field( (string) $o['groups'][ $gid ]['ma'] );
	}
	if ( $gid <= 0 || ! isset( $o['groups'][ $gid ] ) ) {
		$gid = $o['groups'] ? max( array_map( 'intval', array_keys( $o['groups'] ) ) ) + 1 : 1;
	}
	$o['groups'][ $gid ] = $g;
	smlife_mk_luu_opt( $o );
	wp_send_json_success( array( 'g' => smlife_mk_ra_js( $gid, $g ) ) );
} );

/* Bat / tat nhanh mot nhom */
add_action( 'wp_ajax_smlife_mk_bat', function () {
	smlife_mk_ajax_kt();
	$gid = isset( $_POST['gid'] ) ? (int) $_POST['gid'] : 0;
	$o   = smlife_mk_opt();
	if ( ! isset( $o['groups'][ $gid ] ) ) {
		wp_send_json_error( array( 'm' => 'Không tìm thấy nhóm.' ) );
	}
	$o['groups'][ $gid ]['on'] = empty( $_POST['on'] ) ? 0 : 1;
	smlife_mk_luu_opt( $o );
	wp_send_json_success();
} );

/* Xoa nhom */
add_action( 'wp_ajax_smlife_mk_xoa', function () {
	smlife_mk_ajax_kt();
	$gid = isset( $_POST['gid'] ) ? (int) $_POST['gid'] : 0;
	$o   = smlife_mk_opt();
	unset( $o['groups'][ $gid ] );
	smlife_mk_luu_opt( $o );
	wp_send_json_success();
} );

/* Doi thu tu uu tien: dua nhom len / xuong mot bac */
add_action( 'wp_ajax_smlife_mk_doi', function () {
	smlife_mk_ajax_kt();
	$gid  = isset( $_POST['gid'] ) ? (int) $_POST['gid'] : 0;
	$huong = isset( $_POST['h'] ) && 'len' === $_POST['h'] ? -1 : 1;
	$o    = smlife_mk_opt();
	$keys = array_keys( $o['groups'] );
	$i    = array_search( $gid, array_map( 'intval', $keys ), true );
	$j    = false === $i ? false : $i + $huong;
	if ( false !== $j && $j >= 0 && $j < count( $keys ) ) {
		$tmp        = $keys[ $i ];
		$keys[ $i ] = $keys[ $j ];
		$keys[ $j ] = $tmp;
		$moi = array();
		foreach ( $keys as $k ) {
			$moi[ $k ] = $o['groups'][ $k ];
		}
		$o['groups'] = $moi;
		smlife_mk_luu_opt( $o );
	}
	wp_send_json_success( array( 'thu_tu' => array_map( 'intval', array_keys( $o['groups'] ) ) ) );
} );

/* Bat / tat ca khoi mua kem tren website */
add_action( 'wp_ajax_smlife_mk_module', function () {
	smlife_mk_ajax_kt();
	$o            = smlife_mk_opt();
	$o['enabled'] = empty( $_POST['on'] ) ? 0 : 1;
	smlife_mk_luu_opt( $o );
	wp_send_json_success();
} );

/* 10 san pham dau danh muc, dung thu tu dang hien tren trang danh muc
 * (Cai dat WooCommerce > San pham > Thu tu sap xep mac dinh). */
function smlife_mk_dau_danh_muc( $dm, $so = 10 ) {
	$orderby = (string) get_option( 'woocommerce_default_catalog_orderby', 'menu_order' );
	$args    = array(
		'post_type'           => 'product',
		'post_status'         => 'publish',
		'posts_per_page'      => (int) $so,
		'fields'              => 'ids',
		'no_found_rows'       => true,
		'ignore_sticky_posts' => true,
		'tax_query'           => array( // phpcs:ignore
			array( 'taxonomy' => 'product_cat', 'field' => 'term_id', 'terms' => array( (int) $dm ), 'include_children' => true ),
		),
	);
	/* An san pham bi an khoi danh muc va hang het neu cua hang dang an */
	$an = array();
	if ( function_exists( 'wc_get_product_visibility_term_ids' ) ) {
		$vis = wc_get_product_visibility_term_ids();
		if ( ! empty( $vis['exclude-from-catalog'] ) ) {
			$an[] = (int) $vis['exclude-from-catalog'];
		}
		if ( 'yes' === get_option( 'woocommerce_hide_out_of_stock_items' ) && ! empty( $vis['outofstock'] ) ) {
			$an[] = (int) $vis['outofstock'];
		}
	}
	if ( $an ) {
		$args['tax_query'][] = array( 'taxonomy' => 'product_visibility', 'field' => 'term_taxonomy_id', 'terms' => $an, 'operator' => 'NOT IN' );
	}
	switch ( $orderby ) {
		case 'date':
			$args['orderby'] = array( 'date' => 'DESC', 'ID' => 'DESC' );
			break;
		case 'popularity':
			$args['meta_key'] = 'total_sales'; // phpcs:ignore
			$args['orderby']  = array( 'meta_value_num' => 'DESC', 'ID' => 'DESC' );
			break;
		case 'rating':
			$args['meta_key'] = '_wc_average_rating'; // phpcs:ignore
			$args['orderby']  = array( 'meta_value_num' => 'DESC', 'ID' => 'DESC' );
			break;
		case 'price':
		case 'price-desc':
			$args['meta_key'] = '_price'; // phpcs:ignore
			$args['orderby']  = array( 'meta_value_num' => 'price' === $orderby ? 'ASC' : 'DESC', 'ID' => 'DESC' );
			break;
		default:
			$args['orderby'] = array( 'menu_order' => 'ASC', 'title' => 'ASC' );
	}
	$q = new WP_Query( $args );
	return array_map( 'intval', (array) $q->posts );
}

/* Ten + anh nho cua danh sach ID, de soat lai */
function smlife_mk_ten_sp( $ids ) {
	$out = array();
	foreach ( $ids as $id ) {
		$p = function_exists( 'wc_get_product' ) ? wc_get_product( $id ) : null;
		if ( ! $p ) {
			$out[] = array( 'id' => (int) $id, 'n' => '', 'loi' => 'Không tồn tại' );
			continue;
		}
		$out[] = array(
			'id'  => (int) $id,
			'n'   => $p->get_name(),
			'img' => (string) wp_get_attachment_image_url( $p->get_image_id(), 'thumbnail' ),
			'gia' => wp_strip_all_tags( wc_price( (float) $p->get_price() ) ),
			'loi' => 'publish' !== $p->get_status() ? 'Chưa đăng' : ( $p->is_in_stock() ? '' : 'Hết hàng' ),
		);
	}
	return $out;
}

add_action( 'wp_ajax_smlife_mk_lay', function () {
	smlife_mk_ajax_kt();
	if ( ! empty( $_POST['dm'] ) ) {
		$ids = smlife_mk_dau_danh_muc( absint( $_POST['dm'] ), 10 );
	} else {
		$ids = array();
		foreach ( explode( ',', isset( $_POST['ids'] ) ? sanitize_text_field( wp_unslash( $_POST['ids'] ) ) : '' ) as $x ) {
			$x = (int) trim( $x );
			if ( $x > 0 ) {
				$ids[] = $x;
			}
		}
		$ids = array_slice( array_values( array_unique( $ids ) ), 0, 10 );
	}
	wp_send_json_success( array( 'ids' => $ids, 'sp' => smlife_mk_ten_sp( $ids ) ) );
} );

add_action( 'admin_enqueue_scripts', function ( $hook ) {
	if ( false === strpos( (string) $hook, 'smlife-mua-kem' ) ) {
		return;
	}
	wp_enqueue_script( 'smlife-mk-admin', SMLIFE_CORE_URL . 'assets/mk-admin.js', array(), SMLIFE_CORE_VER, true );
} );

/* Danh sach coupon cho o chon ma: id => array( ma, mo ta giam, trang thai ) */
function smlife_mk_ds_cp() {
	$out = array();
	if ( ! class_exists( 'WC_Coupon' ) ) {
		return $out;
	}
	$ids = get_posts( array( 'post_type' => 'shop_coupon', 'post_status' => array( 'publish', 'draft', 'pending', 'future', 'private' ), 'numberposts' => 500, 'fields' => 'ids', 'orderby' => 'title', 'order' => 'ASC' ) );
	foreach ( $ids as $id ) {
		$c = new WC_Coupon( (int) $id );
		if ( ! $c->get_code() ) {
			continue;
		}
		$giam = 'percent' === $c->get_discount_type() ? rtrim( rtrim( number_format( (float) $c->get_amount(), 2, '.', '' ), '0' ), '.' ) . '%' : wp_strip_all_tags( wc_price( (float) $c->get_amount() ) );
		$het  = $c->get_date_expires();
		$out[ (int) $id ] = array(
			'm'  => strtoupper( $c->get_code() ),
			'g'  => $giam,
			'h'  => $het ? wp_date( 'd/m/Y', $het->getTimestamp() ) : '',
			'tt' => smlife_mk_cp_tt( (int) $id ),
		);
	}
	return $out;
}

function smlife_mk_page() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}
	smlife_mk_gan_id_cu();
	$o    = smlife_mk_opt();
	$data = array();
	foreach ( smlife_mk_groups() as $gid => $g ) {
		$data[] = smlife_mk_ra_js( $gid, $g );
	}
	$cats = smlife_mk_cats();
	$cps  = smlife_mk_ds_cp();

	echo '<div class="wrap smlife-wrap smlife-mk-wrap">';
	echo '<div class="smc-hd"><h1>Mua kèm sản phẩm</h1>';
	echo '<p>Mỗi nhóm gắn với một hoặc nhiều <b>danh mục áp dụng</b>: sản phẩm thuộc các danh mục đó sẽ hiện khối mua kèm gồm tối đa 10 sản phẩm lấy từ <b>danh mục bán kèm</b>, kèm mã giảm giá. Đặt <code>[smlife_mua_kem]</code> trong Product Layout. Một sản phẩm khớp nhiều nhóm thì chỉ nhóm ưu tiên cao hơn (số nhỏ hơn) được hiện.</p></div>';

	echo '<div class="smlife-card mk-box">';
	echo '<div class="mk-bar">';
	echo '<input type="search" id="mk-tim" placeholder="Tìm tiêu đề, mã, danh mục..." />';
	echo '<select id="mk-loc-dm"><option value="">Mọi danh mục bán kèm</option><option value="0">Chưa đặt danh mục bán kèm</option></select>';
	echo '<select id="mk-loc-tt"><option value="">Mọi trạng thái</option><option value="1">Đang bật</option><option value="0">Đang tắt</option><option value="trung">Trùng danh mục</option><option value="ma">Mã có vấn đề</option></select>';
	echo '<span class="mk-dem" id="mk-dem"></span>';
	echo '<label class="mk-module" title="Tắt là ẩn khối mua kèm trên toàn website, các nhóm vẫn được giữ"><span class="smc-sw"><input type="checkbox" id="mk-module"' . checked( ! empty( $o['enabled'] ), true, false ) . ' /><i></i></span> Hiện khối mua kèm trên website</label>';
	echo '<button type="button" class="button button-primary" id="mk-them">+ Thêm nhóm</button>';
	echo '</div>';
	echo '<div class="mk-scroll"><table class="widefat mk-tb" id="mk-tb"><thead><tr>';
	echo '<th class="c-ut" data-s="ut">Ưu tiên</th>';
	echo '<th class="c-on" data-s="on">Bật</th>';
	echo '<th class="c-ap" data-s="ap">Danh mục áp dụng</th>';
	echo '<th class="c-dm" data-s="dm">Danh mục bán kèm</th>';
	echo '<th class="c-ma" data-s="ma">Mã</th>';
	echo '<th class="c-sp" data-s="sp">SP kèm</th>';
	echo '<th class="c-td" data-s="td">Tiêu đề và diễn giải</th>';
	echo '<th class="c-tt"></th>';
	echo '</tr></thead><tbody></tbody></table></div>';
	echo '<p class="mk-trong" id="mk-trong" hidden>Không có nhóm nào khớp bộ lọc.</p>';
	echo '</div>';

	echo '<script>window.SMLIFE_MK=' . wp_json_encode( array(
		'nhom'  => $data,
		'dm'    => $cats,
		'cp'    => $cps,
		'ajax'  => admin_url( 'admin-ajax.php' ),
		'n'     => wp_create_nonce( 'smlife_mk_ajax' ),
		'xem'   => admin_url( 'edit.php?post_type=product&product_cat=' ),
		'sua'   => admin_url( 'post.php?action=edit&post=' ),
		'sx'    => (string) get_option( 'woocommerce_default_catalog_orderby', 'menu_order' ),
	) ) . ';</script>';
	echo '</div>';
}

