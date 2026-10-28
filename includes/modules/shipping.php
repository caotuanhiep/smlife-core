<?php
/**
 * SMLIFE Core - Module: Phi giao hang (SMLIFE Ship), thay Table Rate Shipping.
 *
 * Cach dung:
 * 1. Bat module o Bang dieu khien. Chua gan vao vung nao thi KHONG anh huong gi.
 * 2. SMLIFE Core > Phi giao hang > "Nhap tu Table Rate" de chep bang phi cu.
 * 3. "So sanh voi Table Rate": chay nhieu gio hang mau, doi chieu phi 2 ben.
 * 4. Khop het thi WooCommerce > Cai dat > Giao hang > tung vung: them "SMLIFE Ship",
 *    tat Table Rate. Muon quay lai chi can bat lai Table Rate, tat SMLIFE Ship.
 *
 * Cong thuc moi lua chon: Phi co ban + Don gia/kg x tong can nang
 *   + Don gia/SP x so luong + phu phi theo hang. Mien phi tu X dong thi = 0.
 * Can nang lay dung don vi cua cua hang (giong Table Rate), khong doi don vi.
 * Dieu kien theo hang: "Gio co hang" = it nhat 1 san pham thuoc hang; "Ca gio
 * chi thuoc hang" = moi san pham deu thuoc hang do (giong Table Rate tren smlife).
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

/* ============================================================
 * 1. CAU HINH
 * ============================================================ */
function smlife_ship_opt() {
	$o = get_option( 'smlife_ship', array() );
	if ( ! is_array( $o ) ) {
		$o = array();
	}
	$o = array_merge( array( 'round_kg' => 0, 'zones' => array() ), $o );
	if ( ! is_array( $o['zones'] ) ) {
		$o['zones'] = array();
	}
	return $o;
}

function smlife_ship_conds() {
	return array(
		'all'  => 'Mọi đơn',
		'has'  => 'Giỏ có hạng',
		'only' => 'Cả giỏ thuộc hạng',
		'not'  => 'Giỏ không có hạng',
	);
}

/* Danh sach vung WooCommerce: id => ten. 0 = ngoai cac vung tren */
function smlife_ship_zones() {
	$out = array();
	if ( ! class_exists( 'WC_Shipping_Zones' ) ) {
		return $out;
	}
	foreach ( WC_Shipping_Zones::get_zones( 'admin' ) as $z ) {
		$out[ (int) $z['id'] ] = (string) $z['zone_name'];
	}
	$out[0] = 'Ngoài các vùng trên';
	return $out;
}

function smlife_ship_classes() {
	$out = array();
	if ( function_exists( 'WC' ) && WC()->shipping() ) {
		foreach ( (array) WC()->shipping()->get_shipping_classes() as $c ) {
			$out[ (int) $c->term_id ] = (string) $c->name;
		}
	}
	return $out;
}

/* Lam moi bo nho tam phi ship cua WooCommerce sau khi doi bang phi */
function smlife_ship_flush() {
	if ( class_exists( 'WC_Cache_Helper' ) ) {
		WC_Cache_Helper::get_transient_version( 'shipping', true );
	}
}

/* Cac phuong thuc dang bat trong vung, theo id phuong thuc */
function smlife_ship_methods_in( $zid, $method_id ) {
	$out = array();
	if ( ! class_exists( 'WC_Shipping_Zone' ) ) {
		return $out;
	}
	$zone = new WC_Shipping_Zone( (int) $zid );
	foreach ( $zone->get_shipping_methods( true ) as $m ) {
		if ( $method_id === $m->id ) {
			$out[] = $m;
		}
	}
	return $out;
}

/* ============================================================
 * 2. TINH PHI
 * ============================================================ */
function smlife_ship_quote_raw( $zid, $kg, $qty, $classes, $subtotal ) {
	$o    = smlife_ship_opt();
	$zone = isset( $o['zones'][ $zid ] ) && is_array( $o['zones'][ $zid ] ) ? $o['zones'][ $zid ] : array();
	$rows = ! empty( $zone['rows'] ) ? (array) $zone['rows'] : array();
	$sur  = ! empty( $zone['sur'] ) ? (array) $zone['sur'] : array();
	if ( ! empty( $o['round_kg'] ) ) {
		$kg = ceil( $kg );
	}
	$classes = (array) $classes;

	/* Phu phi theo hang: cong vao moi lua chon cua vung khi gio co hang do */
	$extra = 0;
	foreach ( $sur as $cid => $amt ) {
		if ( isset( $classes[ (int) $cid ] ) ) {
			$extra += (float) $amt;
		}
	}

	$out = array();
	foreach ( $rows as $r ) {
		if ( empty( $r['on'] ) ) {
			continue;
		}
		$cls  = isset( $r['cls'] ) ? (int) $r['cls'] : 0;
		$cond = isset( $r['cond'] ) ? (string) $r['cond'] : 'all';
		$has  = $cls > 0 && isset( $classes[ $cls ] );
		if ( $cls > 0 ) {
			if ( 'has' === $cond && ! $has ) {
				continue;
			}
			/* $classes co ca khoa 0 = san pham khong hang, nen gio tron se co >= 2 khoa */
			if ( 'only' === $cond && ! ( $has && 1 === count( $classes ) ) ) {
				continue;
			}
			if ( 'not' === $cond && $has ) {
				continue;
			}
		}
		$cost = (float) $r['base'] + (float) $r['kg'] * (float) $kg + (float) $r['item'] * (float) $qty + $extra;
		$free = isset( $r['free'] ) ? (float) $r['free'] : 0;
		if ( $free > 0 && (float) $subtotal >= $free ) {
			$cost = 0;
		}
		$out[] = array(
			'label' => (string) $r['label'],
			'cost'  => max( 0, round( $cost ) ),
		);
	}
	return $out;
}

/* Tom tat gio hang: can nang (don vi cua cua hang), so luong, cac hang, gia tri */
function smlife_ship_tom_tat( $package ) {
	$kg      = 0;
	$qty     = 0;
	$classes = array();
	foreach ( (array) ( isset( $package['contents'] ) ? $package['contents'] : array() ) as $item ) {
		$p = isset( $item['data'] ) ? $item['data'] : null;
		if ( ! $p instanceof WC_Product || ! $p->needs_shipping() ) {
			continue;
		}
		$q    = (float) $item['quantity'];
		$kg  += (float) $p->get_weight() * $q;
		$qty += $q;
		/* Ghi ca san pham khong co hang (khoa 0), de dieu kien "Ca gio chi
		   thuoc hang" biet gio dang tron hang thuong. */
		$classes[ (int) $p->get_shipping_class_id() ] = true;
	}
	return array(
		'kg'  => $kg,
		'qty' => $qty,
		'cls' => $classes,
		'sub' => isset( $package['contents_cost'] ) ? (float) $package['contents_cost'] : 0,
	);
}

function smlife_ship_quote( $zid, $package ) {
	$t = smlife_ship_tom_tat( $package );
	return smlife_ship_quote_raw( $zid, $t['kg'], $t['qty'], $t['cls'], $t['sub'] );
}

/* ============================================================
 * 3. PHUONG THUC GIAO HANG
 * ============================================================ */
add_action( 'woocommerce_shipping_init', function () {
	if ( class_exists( 'SMLIFE_Ship_Method' ) || ! class_exists( 'WC_Shipping_Method' ) ) {
		return;
	}
	class SMLIFE_Ship_Method extends WC_Shipping_Method {
		public function __construct( $instance_id = 0 ) {
			$this->id                   = 'smlife_ship';
			$this->instance_id          = absint( $instance_id );
			$this->method_title         = 'SMLIFE Ship';
			$this->method_description   = 'Tính phí theo bảng tại SMLIFE Core > Phí giao hàng.';
			$this->supports             = array( 'shipping-zones', 'instance-settings' );
			$this->instance_form_fields = array(
				'title'      => array(
					'title'       => 'Tên quản trị',
					'type'        => 'text',
					'default'     => 'SMLIFE Ship',
					'description' => 'Chỉ hiện trong trang quản trị. Tên khách thấy đặt ở bảng phí của module.',
				),
				'tax_status' => array(
					'title'   => 'Thuế',
					'type'    => 'select',
					'default' => 'none',
					'options' => array(
						'none'    => 'Không tính thuế',
						'taxable' => 'Tính thuế',
					),
				),
			);
			$this->title      = $this->get_option( 'title', 'SMLIFE Ship' );
			$this->tax_status = $this->get_option( 'tax_status', 'none' );
			add_action( 'woocommerce_update_options_shipping_' . $this->id, array( $this, 'process_admin_options' ) );
		}

		public function calculate_shipping( $package = array() ) {
			$zone = WC_Shipping_Zones::get_zone_by( 'instance_id', $this->instance_id );
			$zid  = $zone ? (int) $zone->get_id() : 0;
			foreach ( smlife_ship_quote( $zid, $package ) as $i => $r ) {
				$this->add_rate( array(
					'id'      => $this->get_rate_id( $i ),
					'label'   => $r['label'],
					'cost'    => $r['cost'],
					'package' => $package,
				) );
			}
		}
	}
} );

add_filter( 'woocommerce_shipping_methods', function ( $methods ) {
	if ( class_exists( 'SMLIFE_Ship_Method' ) ) {
		$methods['smlife_ship'] = 'SMLIFE_Ship_Method';
	}
	return $methods;
} );

/* ============================================================
 * 4. SO SANH VOI TABLE RATE
 * Dung gio hang gia, goi thang phuong thuc Table Rate dang bat trong vung,
 * so phi tung lua chon voi bang phi SMLIFE. Khong tao don, khong ghi gi.
 * ============================================================ */

/* Mot tinh bat ky thuoc vung, de lam dia chi giao gia */
function smlife_ship_dia_chi( $zid ) {
	$dc = array( 'country' => 'VN', 'state' => '', 'postcode' => '', 'city' => '', 'address' => '', 'address_1' => '', 'address_2' => '' );
	if ( ! class_exists( 'WC_Shipping_Zone' ) || ! $zid ) {
		return $dc;
	}
	$zone = new WC_Shipping_Zone( (int) $zid );
	foreach ( $zone->get_zone_locations() as $l ) {
		if ( 'state' === $l->type && false !== strpos( $l->code, ':' ) ) {
			list( $c, $s ) = explode( ':', $l->code, 2 );
			$dc['country'] = $c;
			$dc['state']   = $s;
			return $dc;
		}
		if ( 'country' === $l->type ) {
			$dc['country'] = $l->code;
		}
	}
	return $dc;
}

function smlife_ship_goi_gia( $items, $zid ) {
	$contents = array();
	$sub      = 0;
	foreach ( $items as $i => $it ) {
		$p = $it['p'];
		$q = (int) $it['q'];
		$t = (float) $p->get_price() * $q;
		$contents[ 'smls' . $i ] = array(
			'key'               => 'smls' . $i,
			'product_id'        => $p->get_parent_id() ? $p->get_parent_id() : $p->get_id(),
			'variation_id'      => $p->get_parent_id() ? $p->get_id() : 0,
			'variation'         => array(),
			'quantity'          => $q,
			'data'              => $p,
			'line_total'        => $t,
			'line_tax'          => 0,
			'line_subtotal'     => $t,
			'line_subtotal_tax' => 0,
		);
		$sub += $t;
	}
	return array(
		'contents'        => $contents,
		'contents_cost'   => $sub,
		'applied_coupons' => array(),
		'user'            => array( 'ID' => 0 ),
		'destination'     => smlife_ship_dia_chi( $zid ),
		'cart_subtotal'   => $sub,
	);
}

/* Phi cua cac phuong thuc $method_id trong vung: label => tong phi (gom thue) */
function smlife_ship_phi_cua( $zid, $method_id, $package ) {
	$out = array();
	foreach ( smlife_ship_methods_in( $zid, $method_id ) as $m ) {
		$rates = array();
		try {
			$rates = $m->get_rates_for_package( $package );
		} catch ( Throwable $e ) {
			$out['LOI: ' . $e->getMessage()] = 0;
			continue;
		}
		foreach ( (array) $rates as $r ) {
			$out[ (string) $r->get_label() ] = round( (float) $r->get_cost() + array_sum( (array) $r->get_taxes() ) );
		}
	}
	return $out;
}

/* Gio mau: 1 den 3 san pham ngau nhien, mot so gio co san pham thuoc hang van chuyen */
function smlife_ship_gio_mau( $so ) {
	$ds   = wc_get_products( array( 'status' => 'publish', 'type' => array( 'simple' ), 'limit' => 60, 'orderby' => 'rand', 'return' => 'objects' ) );
	$hang = array();
	foreach ( smlife_ship_classes() as $cid => $cn ) {
		$t = get_term( $cid, 'product_shipping_class' );
		if ( $t && ! is_wp_error( $t ) ) {
			$hang = array_merge( $hang, wc_get_products( array( 'status' => 'publish', 'type' => array( 'simple' ), 'limit' => 10, 'orderby' => 'rand', 'shipping_class' => $t->slug ) ) );
		}
	}
	$ds = array_values( array_filter( $ds, function ( $p ) {
		return $p->needs_shipping() && '' !== (string) $p->get_price();
	} ) );
	$hang = array_values( array_filter( $hang, function ( $p ) {
		return $p->needs_shipping() && '' !== (string) $p->get_price();
	} ) );
	$gio = array();
	for ( $i = 0; $i < $so && $ds; $i++ ) {
		$items = array();
		$n     = wp_rand( 1, 3 );
		for ( $k = 0; $k < $n; $k++ ) {
			$items[] = array( 'p' => $ds[ wp_rand( 0, count( $ds ) - 1 ) ], 'q' => wp_rand( 1, 2 ) );
		}
		/* 1/3 so gio co them 1 san pham thuoc hang (SMLIFE NOW...) */
		if ( $hang && 0 === $i % 3 ) {
			$items[] = array( 'p' => $hang[ wp_rand( 0, count( $hang ) - 1 ) ], 'q' => 1 );
		}
		/* 1/6 so gio CHI co san pham thuoc hang */
		if ( $hang && 1 === $i % 6 ) {
			$items = array( array( 'p' => $hang[ wp_rand( 0, count( $hang ) - 1 ) ], 'q' => 1 ) );
		}
		$gio[] = $items;
	}
	return $gio;
}

function smlife_ship_so_sanh( $so = 20 ) {
	$kq = array();
	if ( ! function_exists( 'WC' ) || ! WC()->shipping() ) {
		return $kq;
	}
	/* Trang admin khong co phien gio hang, ma Table Rate doc WC()->session.
	   Nap phien + khach + gio giong trang khach truoc khi goi Table Rate. */
	if ( function_exists( 'wc_load_cart' ) && ( null === WC()->session || null === WC()->cart || null === WC()->customer ) ) {
		try {
			wc_load_cart();
		} catch ( Throwable $e ) {
			error_log( '[SMLIFE Core] Phi giao hang - khong nap duoc phien gio hang: ' . $e->getMessage() );
		}
	}
	WC()->shipping()->load_shipping_methods();
	$gio = smlife_ship_gio_mau( $so );
	foreach ( smlife_ship_zones() as $zid => $zn ) {
		if ( ! smlife_ship_methods_in( $zid, 'table_rate' ) ) {
			continue;
		}
		foreach ( $gio as $items ) {
			$pk  = smlife_ship_goi_gia( $items, $zid );
			$cu  = smlife_ship_phi_cua( $zid, 'table_rate', $pk );
			$moi = array();
			foreach ( smlife_ship_quote( $zid, $pk ) as $r ) {
				$moi[ $r['label'] ] = $r['cost'];
			}
			ksort( $cu );
			ksort( $moi );
			$t     = smlife_ship_tom_tat( $pk );
			$mo_ta = array();
			foreach ( $items as $it ) {
				$mo_ta[] = $it['q'] . 'x ' . $it['p']->get_name() . ' (' . (float) $it['p']->get_weight() . ')';
			}
			$kq[] = array(
				'zone' => $zn,
				'gio'  => implode( ' + ', $mo_ta ),
				'kg'   => $t['kg'],
				'cu'   => $cu,
				'moi'  => $moi,
				'khop' => $cu == $moi, // phpcs:ignore
			);
		}
	}
	return $kq;
}

/* ============================================================
 * 5. TRANG QUAN TRI
 * ============================================================ */
add_action( 'admin_menu', function () {
	add_submenu_page( 'smlife-core', 'Phí giao hàng', 'Phí giao hàng', 'manage_options', 'smlife-shipping', 'smlife_ship_page' );
}, 46 );

/* ===== Tinh thanh cua tung vung =====
 * Vung WooCommerce luu ma tinh dang VN:HANOI. Lay ten tinh tu danh sach tinh
 * dang dung o trang thanh toan. Ma khong con trong danh sach (ma 63 tinh cu
 * truoc sap nhap) chi dem, khong hien ten. */
function smlife_ship_ds_tinh() {
	static $ds = null;
	if ( null === $ds ) {
		$ds = ( function_exists( 'WC' ) && WC()->countries ) ? (array) WC()->countries->get_states( 'VN' ) : array();
	}
	return $ds;
}

function smlife_ship_tinh_cua_vung( $zid ) {
	$kq = array( 'ten' => array(), 'cu' => 0, 'khac' => array() );
	if ( ! class_exists( 'WC_Shipping_Zone' ) || ! $zid ) {
		return $kq;
	}
	$ds   = smlife_ship_ds_tinh();
	$zone = new WC_Shipping_Zone( (int) $zid );
	foreach ( $zone->get_zone_locations() as $l ) {
		if ( 'state' === $l->type ) {
			$ma = substr( (string) $l->code, strpos( (string) $l->code, ':' ) + 1 );
			if ( isset( $ds[ $ma ] ) ) {
				$kq['ten'][] = $ds[ $ma ];
			} else {
				$kq['cu']++;
			}
		} elseif ( 'country' === $l->type ) {
			$kq['khac'][] = 'Cả nước ' . $l->code;
		} elseif ( 'postcode' === $l->type ) {
			$kq['khac'][] = 'Mã bưu chính ' . $l->code;
		}
	}
	sort( $kq['ten'] );
	return $kq;
}

/* Tinh -> vung, dung chinh ham chon vung cua WooCommerce de khop that */
function smlife_ship_tinh_vung_map() {
	$out = array();
	if ( ! class_exists( 'WC_Shipping_Zones' ) ) {
		return $out;
	}
	foreach ( smlife_ship_ds_tinh() as $ma => $ten ) {
		$z = WC_Shipping_Zones::get_zone_matching_package( array( 'destination' => array( 'country' => 'VN', 'state' => $ma, 'postcode' => '', 'city' => '' ) ) );
		$out[ $ma ] = array( 'n' => $ten, 'z' => $z ? (int) $z->get_id() : 0 );
	}
	return $out;
}

/* Mo ta phi mot dong bang chu de doc nhanh */
function smlife_ship_mo_ta_dong( $r, $classes, $dv ) {
	$f = function ( $v ) {
		return number_format( (float) $v, 0, ',', '.' ) . 'đ';
	};
	$p = array();
	if ( (float) $r['base'] > 0 || ( (float) $r['kg'] <= 0 && (float) $r['item'] <= 0 ) ) {
		$p[] = $f( $r['base'] );
	}
	if ( (float) $r['kg'] > 0 ) {
		$p[] = $f( $r['kg'] ) . '/' . $dv;
	}
	if ( (float) $r['item'] > 0 ) {
		$p[] = $f( $r['item'] ) . '/sản phẩm';
	}
	$s = implode( ' + ', $p );
	if ( ! empty( $r['cls'] ) && isset( $classes[ (int) $r['cls'] ] ) ) {
		$dk = array( 'has' => 'giỏ có hàng', 'only' => 'cả giỏ là hàng', 'not' => 'giỏ không có hàng' );
		$s .= ' (' . ( isset( $dk[ $r['cond'] ] ) ? $dk[ $r['cond'] ] : 'hạng' ) . ' ' . $classes[ (int) $r['cls'] ] . ')';
	}
	if ( ! empty( $r['free'] ) ) {
		$s .= ', miễn phí từ ' . $f( $r['free'] );
	}
	return $s;
}

function smlife_ship_row_html( $zid, $i, $r, $classes, $conds ) {
	$r = array_merge( array( 'on' => 1, 'label' => '', 'base' => 0, 'kg' => 0, 'item' => 0, 'cond' => 'all', 'cls' => 0, 'free' => 0 ), (array) $r );
	$n = 'ship[' . $zid . '][rows][' . $i . ']';
	$h  = '<tr>';
	$h .= '<td class="c-on"><input type="checkbox" name="' . esc_attr( $n ) . '[on]" value="1"' . checked( ! empty( $r['on'] ), true, false ) . ' /></td>';
	$h .= '<td class="c-ten"><input type="text" name="' . esc_attr( $n ) . '[label]" value="' . esc_attr( $r['label'] ) . '" placeholder="VD: Giao tỉnh qua Viettel Post" /></td>';
	foreach ( array( 'base', 'kg', 'item' ) as $k ) {
		$h .= '<td class="c-so"><input type="number" min="0" step="any" name="' . esc_attr( $n ) . '[' . $k . ']" value="' . esc_attr( (float) $r[ $k ] ) . '" /></td>';
	}
	$h .= '<td class="c-dk"><select name="' . esc_attr( $n ) . '[cond]">';
	foreach ( $conds as $k => $lb ) {
		$h .= '<option value="' . esc_attr( $k ) . '"' . selected( $r['cond'], $k, false ) . '>' . esc_html( $lb ) . '</option>';
	}
	$h .= '</select></td>';
	$h .= '<td class="c-hang"><select name="' . esc_attr( $n ) . '[cls]"><option value="0">Mọi hàng</option>';
	foreach ( $classes as $cid => $cn ) {
		$h .= '<option value="' . (int) $cid . '"' . selected( (int) $r['cls'], (int) $cid, false ) . '>' . esc_html( $cn ) . '</option>';
	}
	$h .= '</select></td>';
	$h .= '<td class="c-free"><input type="number" min="0" step="any" name="' . esc_attr( $n ) . '[free]" value="' . esc_attr( (float) $r['free'] ) . '" /></td>';
	$h .= '<td class="c-x"><button type="button" class="button-link-delete sms-del" title="Xoá dòng">&times;</button></td>';
	$h .= '</tr>';
	return $h;
}

function smlife_ship_page() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}
	echo '<div class="wrap smlife-wrap"><div class="smc-hd"><h1>Phí giao hàng</h1>';
	echo '<p>Bảng phí theo vùng giao hàng, thay cho Table Rate Shipping. Mỗi vùng gồm một nhóm tỉnh; khách chọn tỉnh ở trang thanh toán thì rơi vào vùng chứa tỉnh đó và thấy các lựa chọn giao hàng của vùng. Đổi phí chỉ cần sửa ở đây.</p></div>';
	if ( ! class_exists( 'WooCommerce' ) ) {
		echo '<p>Module cần WooCommerce.</p></div>';
		return;
	}
	$o       = smlife_ship_opt();
	$zones   = smlife_ship_zones();
	$classes = smlife_ship_classes();
	$conds   = smlife_ship_conds();
	$dv      = (string) get_option( 'woocommerce_weight_unit', 'kg' );

	if ( isset( $_GET['saved'] ) ) {
		echo '<div class="notice notice-success is-dismissible"><p>Đã lưu bảng phí.</p></div>';
	}
	if ( isset( $_GET['imported'] ) ) {
		echo '<div class="notice notice-info is-dismissible"><p>Đã nhập bảng phí của ' . (int) $_GET['imported'] . ' vùng từ Table Rate. Bấm "Chạy so sánh" bên dưới để đối chiếu.</p></div>';
	}

	/* Trang thai tung vung + du lieu tra cuu */
	$tt    = array();
	$co_tr = false;
	$tom   = array();
	foreach ( $zones as $zid => $zn ) {
		$tr = count( smlife_ship_methods_in( $zid, 'table_rate' ) );
		$sm = count( smlife_ship_methods_in( $zid, 'smlife_ship' ) );
		$co_tr = $co_tr || $tr > 0;
		if ( $tr && $sm ) {
			$tt[ $zid ] = '<span class="smc-pill bad">Bật cả hai, khách thấy 2 bộ phí</span>';
		} elseif ( $sm ) {
			$tt[ $zid ] = '<span class="smc-pill ok">Đang tính bằng SMLIFE Ship</span>';
		} elseif ( $tr ) {
			$tt[ $zid ] = '<span class="smc-pill mute">Đang tính bằng Table Rate</span>';
		} else {
			$tt[ $zid ] = '<span class="smc-pill warn">Chưa có phương thức tính phí</span>';
		}
		$dong = array();
		$z    = isset( $o['zones'][ $zid ] ) ? $o['zones'][ $zid ] : array();
		foreach ( ! empty( $z['rows'] ) ? (array) $z['rows'] : array() as $r ) {
			if ( ! empty( $r['on'] ) ) {
				$dong[] = array( 'l' => (string) $r['label'], 'p' => smlife_ship_mo_ta_dong( $r, $classes, $dv ) );
			}
		}
		$tom[ $zid ] = array( 'n' => $zn, 'd' => $dong, 't' => wp_strip_all_tags( $tt[ $zid ] ) );
	}

	/* --- Tra cuu tinh --- */
	$map = smlife_ship_tinh_vung_map();
	echo '<div class="smlife-card"><h2>Tra cứu: khách ở tỉnh nào thì tính phí vùng nào</h2><div class="in"><div class="sms-tra">';
	echo '<div><select id="sms-tinh" style="width:100%"><option value="">Chọn tỉnh / thành phố...</option>';
	foreach ( $map as $ma => $x ) {
		echo '<option value="' . esc_attr( $ma ) . '">' . esc_html( $x['n'] ) . '</option>';
	}
	echo '</select><p class="description" style="margin:8px 0 0">Dùng đúng cách WooCommerce chọn vùng ở trang thanh toán, nên kết quả ở đây là kết quả khách thấy.</p></div>';
	echo '<div class="sms-tra-kq" id="sms-tra-kq">Chọn một tỉnh để xem vùng và các mức phí.</div>';
	echo '</div></div></div>';

	/* --- Bang phi tung vung --- */
	echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '">';
	echo '<input type="hidden" name="action" value="smlife_ship_save" />';
	wp_nonce_field( 'smlife_ship_save', 'smlife_ship_nonce' );
	echo '<div class="smc-note" style="margin-bottom:18px"><b>Cách tính mỗi lựa chọn:</b> Phí cơ bản + Đơn giá/' . esc_html( $dv ) . ' × tổng cân nặng + Đơn giá/SP × số lượng + phụ phí theo hạng. Ô "Miễn phí từ" để 0 là tắt. Cột Điều kiện chỉ có tác dụng khi chọn một Hạng vận chuyển cụ thể. Ví dụ Grab chỉ cho giỏ toàn hàng SMLIFE NOW: Điều kiện "Cả giỏ thuộc hạng" + Hạng "SMLIFE NOW".</div>';

	foreach ( $zones as $zid => $zname ) {
		$z    = isset( $o['zones'][ $zid ] ) && is_array( $o['zones'][ $zid ] ) ? $o['zones'][ $zid ] : array();
		$rows = ! empty( $z['rows'] ) ? (array) $z['rows'] : array();
		$sur  = ! empty( $z['sur'] ) ? (array) $z['sur'] : array();
		$tinh = smlife_ship_tinh_cua_vung( $zid );
		echo '<div class="sms-zone"><div class="sms-zone-hd"><span class="dashicons dashicons-location"></span><div class="sms-zone-tx">';
		echo '<h3>' . esc_html( $zname ) . ' ' . $tt[ $zid ] . '</h3>'; // phpcs:ignore
		echo '<div class="sms-tinh">';
		if ( 0 === (int) $zid ) {
			echo '<em>Mọi địa chỉ không thuộc vùng nào ở trên.</em>';
		} else {
			foreach ( $tinh['ten'] as $t ) {
				echo '<span>' . esc_html( $t ) . '</span>';
			}
			foreach ( $tinh['khac'] as $t ) {
				echo '<span>' . esc_html( $t ) . '</span>';
			}
			if ( $tinh['cu'] ) {
				echo '<em>+ ' . (int) $tinh['cu'] . ' mã tỉnh cũ trước sáp nhập</em>';
			}
			if ( ! $tinh['ten'] && ! $tinh['khac'] ) {
				echo '<em>Chưa gán tỉnh nào.</em>';
			}
		}
		echo '</div></div></div>';
		echo '<div class="sms-zone-bd">';
		echo '<table class="widefat sms-tb" data-zone="' . (int) $zid . '">';
		echo '<colgroup><col style="width:4%" /><col style="width:31%" /><col style="width:8.5%" /><col style="width:8.5%" /><col style="width:8.5%" /><col style="width:15.5%" /><col style="width:13%" /><col style="width:8%" /><col style="width:3%" /></colgroup><thead><tr>';
		echo '<th class="c-on">Bật</th><th class="c-ten">Tên khách thấy ở trang thanh toán</th>';
		echo '<th class="c-so">Phí cơ bản<span class="sms-don">đồng</span></th><th class="c-so">Đơn giá<span class="sms-don">đồng/' . esc_html( $dv ) . '</span></th><th class="c-so">Đơn giá<span class="sms-don">đồng/sản phẩm</span></th>';
		echo '<th class="c-dk">Điều kiện</th><th class="c-hang">Hạng vận chuyển</th><th class="c-free">Miễn phí từ<span class="sms-don">giá trị đơn</span></th><th class="c-x"></th>';
		echo '</tr></thead><tbody>';
		foreach ( $rows as $i => $r ) {
			echo smlife_ship_row_html( $zid, (int) $i, $r, $classes, $conds ); // phpcs:ignore
		}
		echo '</tbody></table>';
		echo '<div class="sms-them"><button type="button" class="button sms-add" data-zone="' . (int) $zid . '">+ Thêm lựa chọn giao hàng</button>';
		if ( $classes ) {
			echo '<div class="sms-sur"><b>Phụ phí theo hạng</b>';
			foreach ( $classes as $cid => $cn ) {
				$v = isset( $sur[ $cid ] ) ? (float) $sur[ $cid ] : 0;
				echo '<label>' . esc_html( $cn ) . ' <input type="number" min="0" step="any" name="ship[' . (int) $zid . '][sur][' . (int) $cid . ']" value="' . esc_attr( $v ) . '" /></label>';
			}
			echo '</div>';
		}
		echo '</div></div></div>';
	}
	echo '<div class="smlife-card"><div class="in" style="display:flex;align-items:center;gap:16px;flex-wrap:wrap">';
	echo '<label style="flex:1 1 360px"><input type="checkbox" name="round_kg" value="1"' . checked( ! empty( $o['round_kg'] ), true, false ) . ' /> Làm tròn lên tổng cân nặng (VD 2,3 tính 3). Table Rate không làm tròn, để trống nếu muốn khớp phí cũ.</label>';
	submit_button( 'Lưu bảng phí', 'primary', 'submit', false );
	echo '</div></div></form>';
	echo '<script type="text/html" id="sms-tpl">' . smlife_ship_row_html( '__Z__', '__I__', array(), $classes, $conds ) . '</script>'; // phpcs:ignore

	/* --- So sanh --- */
	echo '<div class="smlife-card" id="sosanh"><h2>So sánh với Table Rate</h2><div class="in">';
	if ( ! $co_tr ) {
		echo '<p class="description" style="margin:0">Không còn vùng nào bật Table Rate, không có gì để so sánh.</p>';
	} else {
		echo '<p class="description" style="margin:0 0 12px">Tạo giỏ hàng mẫu từ sản phẩm thật (có cả giỏ chứa hàng thuộc hạng vận chuyển), tính phí bằng Table Rate đang chạy và bằng bảng phí ở trên rồi so từng lựa chọn. Không tạo đơn, không ghi gì vào website.</p>';
		echo '<p style="margin:0"><a class="button button-primary" href="' . esc_url( admin_url( 'admin.php?page=smlife-shipping&sosanh=1' ) ) . '#sosanh">Chạy so sánh 20 giỏ mẫu</a></p>';
		if ( isset( $_GET['sosanh'] ) ) {
			$kq   = smlife_ship_so_sanh( 20 );
			$lech = count( array_filter( $kq, function ( $x ) {
				return ! $x['khop'];
			} ) );
			echo '<p style="margin:14px 0 10px"><span class="smc-pill ' . ( $lech ? 'bad' : 'ok' ) . '" style="font-size:13px;padding:4px 12px">' . (int) ( count( $kq ) - $lech ) . '/' . (int) count( $kq ) . ' trường hợp khớp</span>' . ( $lech ? ' <span class="description">Các dòng lệch được đưa lên đầu.</span>' : '' ) . '</p>';
			usort( $kq, function ( $a, $b ) {
				return (int) $a['khop'] - (int) $b['khop'];
			} );
			$fmt = function ( $arr ) {
				$s = array();
				foreach ( $arr as $l => $c ) {
					$s[] = esc_html( $l ) . ': <b>' . esc_html( number_format( (float) $c, 0, ',', '.' ) ) . 'đ</b>';
				}
				return $s ? implode( '<br>', $s ) : '<i>không có lựa chọn</i>';
			};
			echo '<table class="widefat"><thead><tr><th style="width:120px">Vùng</th><th>Giỏ mẫu (cân nặng)</th><th style="width:70px">Tổng ' . esc_html( $dv ) . '</th><th>Table Rate</th><th>SMLIFE Ship</th><th style="width:70px"></th></tr></thead><tbody>';
			foreach ( $kq as $x ) {
				echo '<tr' . ( $x['khop'] ? '' : ' style="background:#fef6f6"' ) . '><td>' . esc_html( $x['zone'] ) . '</td><td style="font-size:12px">' . esc_html( $x['gio'] ) . '</td><td>' . esc_html( round( $x['kg'], 2 ) ) . '</td><td style="font-size:12px">' . $fmt( $x['cu'] ) . '</td><td style="font-size:12px">' . $fmt( $x['moi'] ) . '</td><td>' . ( $x['khop'] ? '<span class="smc-pill ok">Khớp</span>' : '<span class="smc-pill bad">Lệch</span>' ) . '</td></tr>'; // phpcs:ignore
			}
			echo '</tbody></table>';
		}
	}
	echo '</div></div>';

	/* --- Tinh thu --- */
	$tz  = isset( $_GET['tz'] ) ? (int) $_GET['tz'] : 0;
	$tkg = isset( $_GET['tkg'] ) ? (float) $_GET['tkg'] : 10;
	$tq  = isset( $_GET['tq'] ) ? max( 1, (int) $_GET['tq'] ) : 1;
	$tc  = isset( $_GET['tc'] ) ? (int) $_GET['tc'] : 0;
	$ts  = isset( $_GET['ts'] ) ? (float) $_GET['ts'] : 3000000;
	echo '<div class="smlife-card" id="tinhthu"><h2>Tính thử</h2><div class="in">';
	echo '<form method="get" style="display:flex;gap:10px;align-items:flex-end;flex-wrap:wrap"><input type="hidden" name="page" value="smlife-shipping" />';
	echo '<label>Vùng<br /><select name="tz">';
	foreach ( $zones as $zid => $zname ) {
		echo '<option value="' . (int) $zid . '"' . selected( $tz, $zid, false ) . '>' . esc_html( $zname ) . '</option>';
	}
	echo '</select></label>';
	echo '<label>Cân nặng (' . esc_html( $dv ) . ')<br /><input type="number" step="any" name="tkg" value="' . esc_attr( $tkg ) . '" style="width:100px" /></label>';
	echo '<label>Số sản phẩm<br /><input type="number" name="tq" value="' . esc_attr( $tq ) . '" style="width:90px" /></label>';
	echo '<label>Hạng<br /><select name="tc"><option value="0">Hàng thường</option>';
	foreach ( $classes as $cid => $cn ) {
		echo '<option value="' . (int) $cid . '"' . selected( $tc, $cid, false ) . '>' . esc_html( $cn ) . '</option>';
	}
	echo '</select></label>';
	echo '<label>Giá trị đơn (đ)<br /><input type="number" name="ts" value="' . esc_attr( $ts ) . '" style="width:130px" /></label>';
	echo '<button class="button button-primary" name="sms_test" value="1">Tính</button></form>';
	if ( isset( $_GET['sms_test'] ) ) {
		$res = smlife_ship_quote_raw( $tz, $tkg, $tq, array( $tc => true ), $ts );
		echo '<div class="sms-tra-kq" style="margin-top:14px">';
		if ( ! $res ) {
			echo 'Không có lựa chọn nào phù hợp.';
		}
		foreach ( $res as $r ) {
			echo esc_html( $r['label'] ) . ': <b>' . esc_html( number_format( (float) $r['cost'], 0, ',', '.' ) ) . 'đ</b><br />';
		}
		echo '</div>';
	}
	echo '</div></div>';

	/* --- Nhap + huong dan chuyen --- */
	echo '<div class="smlife-card"><h2>Chuyển từ Table Rate Shipping</h2><div class="in">';
	echo '<ol style="margin:0 0 14px 18px;line-height:1.9;color:var(--smc-tx-mid)">';
	echo '<li>Bấm <b>Nhập từ Table Rate</b> để chép quy tắc cũ vào bảng phí ở trên.</li>';
	echo '<li>Bấm <b>Chạy so sánh</b> vài lần, phải khớp hết.</li>';
	echo '<li>WooCommerce > Cài đặt > Giao hàng > vào từng vùng: thêm phương thức <b>SMLIFE Ship</b>, tắt Table Rate của vùng đó. Làm 1 vùng trước, đặt thử 1 đơn rồi mới làm các vùng còn lại.</li>';
	echo '<li>Chạy ổn vài ngày rồi mới gỡ plugin Table Rate.</li></ol>';
	echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '" onsubmit="return confirm(\'Ghi đè bảng phí SMLIFE của các vùng có Table Rate?\')">';
	echo '<input type="hidden" name="action" value="smlife_ship_import" />';
	wp_nonce_field( 'smlife_ship_import', 'smlife_ship_nonce' );
	submit_button( 'Nhập từ Table Rate', 'secondary', 'submit', false );
	echo ' <span class="description">Vùng nào có Table Rate sẽ bị ghi đè bảng phí SMLIFE của vùng đó. Table Rate không bị đụng tới.</span>';
	echo '</form></div></div></div>';
	?>
	<script>
	jQuery(function ($) {
		var MAP = <?php echo wp_json_encode( $map ); ?>, TOM = <?php echo wp_json_encode( $tom ); ?>;
		function esc(s) { return $('<div>').text(s).html(); }
		$('#sms-tinh').on('change', function () {
			var m = MAP[this.value], o = $('#sms-tra-kq');
			if (!m) { o.html('Chọn một tỉnh để xem vùng và các mức phí.'); return; }
			var z = TOM[m.z] || { n: 'Ngoài các vùng', d: [], t: '' };
			var h = '<b>' + esc(m.n) + '</b> thuộc vùng <b>' + esc(z.n) + '</b>' + (z.t ? ' &middot; ' + esc(z.t) : '');
			if (z.d.length) {
				h += '<ul>';
				z.d.forEach(function (d) { h += '<li><b>' + esc(d.l) + '</b>: ' + esc(d.p) + '</li>'; });
				h += '</ul>';
			} else {
				h += '<br>Vùng này chưa có dòng phí nào trong bảng SMLIFE.';
			}
			o.html(h);
		});
		$(document).on('click', '.sms-add', function () {
			var z = $(this).data('zone'), i = 'n' + Date.now();
			var h = $('#sms-tpl').html().split('__Z__').join(z).split('__I__').join(i);
			$('table.sms-tb[data-zone="' + z + '"] tbody').append(h);
		});
		$(document).on('click', '.sms-del', function () {
			$(this).closest('tr').remove();
		});
	});
	</script>
	<?php
}

/* ===== Luu ===== */
add_action( 'admin_post_smlife_ship_save', function () {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( 'Không đủ quyền.' );
	}
	check_admin_referer( 'smlife_ship_save', 'smlife_ship_nonce' );
	$in    = isset( $_POST['ship'] ) && is_array( $_POST['ship'] ) ? wp_unslash( $_POST['ship'] ) : array();
	$conds = smlife_ship_conds();
	$zones = array();
	foreach ( $in as $zid => $z ) {
		$z    = is_array( $z ) ? $z : array();
		$rows = array();
		foreach ( (array) ( isset( $z['rows'] ) ? $z['rows'] : array() ) as $r ) {
			$r     = is_array( $r ) ? $r : array();
			$label = sanitize_text_field( isset( $r['label'] ) ? $r['label'] : '' );
			if ( '' === $label ) {
				continue;
			}
			$cond   = isset( $r['cond'] ) ? sanitize_key( $r['cond'] ) : 'all';
			$rows[] = array(
				'on'    => empty( $r['on'] ) ? 0 : 1,
				'label' => $label,
				'base'  => max( 0, (float) ( isset( $r['base'] ) ? $r['base'] : 0 ) ),
				'kg'    => max( 0, (float) ( isset( $r['kg'] ) ? $r['kg'] : 0 ) ),
				'item'  => max( 0, (float) ( isset( $r['item'] ) ? $r['item'] : 0 ) ),
				'cond'  => isset( $conds[ $cond ] ) ? $cond : 'all',
				'cls'   => absint( isset( $r['cls'] ) ? $r['cls'] : 0 ),
				'free'  => max( 0, (float) ( isset( $r['free'] ) ? $r['free'] : 0 ) ),
			);
		}
		$sur = array();
		foreach ( (array) ( isset( $z['sur'] ) ? $z['sur'] : array() ) as $cid => $v ) {
			$v = max( 0, (float) $v );
			if ( $v > 0 ) {
				$sur[ absint( $cid ) ] = $v;
			}
		}
		$zones[ (int) $zid ] = array( 'rows' => $rows, 'sur' => $sur );
	}
	update_option( 'smlife_ship', array(
		'round_kg' => empty( $_POST['round_kg'] ) ? 0 : 1,
		'zones'    => $zones,
	), false );
	smlife_ship_flush();
	wp_safe_redirect( admin_url( 'admin.php?page=smlife-shipping&saved=1' ) );
	exit;
} );

/* ===== Nhap tu Table Rate =====
 * Doc bang woocommerce_shipping_table_rates (cua plugin WooCommerce Table Rate
 * Shipping). Moi quy tac cua phuong thuc dang bat thanh 1 dong. Quy tac co hang
 * van chuyen -> dieu kien "Gio co hang". */
add_action( 'admin_post_smlife_ship_import', function () {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( 'Không đủ quyền.' );
	}
	check_admin_referer( 'smlife_ship_import', 'smlife_ship_nonce' );
	global $wpdb;
	$table = $wpdb->prefix . 'woocommerce_shipping_table_rates';
	$n     = 0;
	if ( $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table ) ) === $table ) {
		$o = smlife_ship_opt();
		foreach ( array_keys( smlife_ship_zones() ) as $zid ) {
			$rows = array();
			foreach ( smlife_ship_methods_in( $zid, 'table_rate' ) as $m ) {
				$rates = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$table} WHERE shipping_method_id = %d ORDER BY rate_order ASC", $m->instance_id ), ARRAY_A ); // phpcs:ignore
				foreach ( (array) $rates as $t ) {
					$label  = trim( (string) $t['rate_label'] );
					$rows[] = array(
						'on'    => 1,
						'label' => '' !== $label ? $label : (string) $m->get_title(),
						'base'  => (float) $t['rate_cost'],
						'kg'    => (float) $t['rate_cost_per_weight_unit'],
						'item'  => (float) $t['rate_cost_per_item'],
						/* Table Rate tinh theo don: gio tron hang thuong thi hang co
						   uu tien cao hon (khong hang) thang, quy tac theo hang khong khop.
						   Tren smlife da doi chieu: Grab chi hien khi ca gio la SMLIFE NOW. */
						'cond'  => ! empty( $t['rate_class'] ) ? 'only' : 'all',
						'cls'   => (int) $t['rate_class'],
						'free'  => 0,
					);
				}
			}
			if ( $rows ) {
				$o['zones'][ $zid ] = array(
					'rows' => $rows,
					'sur'  => isset( $o['zones'][ $zid ]['sur'] ) ? $o['zones'][ $zid ]['sur'] : array(),
				);
				$n++;
			}
		}
		update_option( 'smlife_ship', $o, false );
		smlife_ship_flush();
	}
	wp_safe_redirect( admin_url( 'admin.php?page=smlife-shipping&imported=' . $n ) );
	exit;
} );
