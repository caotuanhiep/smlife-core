<?php
/**
 * SMLIFE Core - Module: Schema san pham (Merchant Listings)
 *
 * Bo sung 3 truong Google Merchant Listings dang thieu tren toan bo san pham:
 *   - shippingDetails          (phi va thoi gian giao hang)
 *   - hasMerchantReturnPolicy  (chinh sach doi tra)
 *   - validFrom                (ngay bat dau hieu luc cua gia)
 *
 * Dong thoi don cac loi lam Google tu choi rich result:
 *   - aggregateRating rong (reviewCount = 0, ratingCount = 0, rating ngoai 1-5)
 *   - SKU sai dinh dang
 *   - productGroupID rong tren san pham don
 *
 * Phi giao hang tinh ngay tren tung san pham theo cong thuc Viettel Post:
 *   phi = phi co dinh + (so kg cua san pham x phi moi kg)
 * Google khong hieu cong thuc, nen plugin tu tinh ra so tien that cho tung
 * san pham roi mo ta cho Google bang mot con so cu the.
 *
 * Ho tro Rank Math (chinh) va Yoast WooCommerce SEO (du phong).
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

/* ===== Cau hinh ===== */
function smlife_sch_opt() {
	$def = array(
		'enabled'        => 1,

		/* Giao hang */
		'hcm_free'       => 1,      /* Mien phi noi thanh HCM */
		'hcm_rate'       => 0,      /* Phi HCM neu khong mien phi */
		'tinh_co_dinh'   => 30000,  /* Phi co dinh cho tinh khac */
		'tinh_moi_kg'    => 5000,   /* Phi cong them moi kg */
		'tinh_toi_da'    => 0,      /* Tran phi, 0 = khong gioi han */
		'kg_mac_dinh'    => 10,     /* Dung khi san pham chua khai can nang */

		/* Thoi gian giao, don vi ngay */
		'hcm_xuly_min'   => 1,
		'hcm_xuly_max'   => 2,
		'hcm_giao_min'   => 2,
		'hcm_giao_max'   => 3,
		'tinh_xuly_min'  => 1,
		'tinh_xuly_max'  => 2,
		'tinh_giao_min'  => 4,
		'tinh_giao_max'  => 6,

		/* Doi tra */
		'doi_tra_ngay'   => 7,
		'ai_tra_phi'     => 'khach',  /* shop | khach */
		'cach_tra'       => 'ReturnByMail',
		'link_doi_tra'   => 'https://smlife.vn/chinh-sach-bao-hanh-doi-tra',

		/* Don du lieu */
		'don_rating'     => 1,
		'sua_sku'        => 1,
	);
	$s = get_option( 'smlife_sch', array() );
	return is_array( $s ) ? array_merge( $def, $s ) : $def;
}

/* ===== Can nang san pham, quy ve kg =====
 * Tra ve mang array( min_kg, max_kg ). San pham don thi min = max.
 */
function smlife_sch_can_nang( $product ) {
	$o  = smlife_sch_opt();
	$ds = array();

	if ( $product->is_type( 'variable' ) ) {
		foreach ( $product->get_children() as $cid ) {
			$v = wc_get_product( $cid );
			if ( ! $v ) { continue; }
			$w = (float) $v->get_weight();
			if ( $w > 0 ) { $ds[] = $w; }
		}
	}
	if ( empty( $ds ) ) {
		$w = (float) $product->get_weight();
		if ( $w > 0 ) { $ds[] = $w; }
	}
	if ( empty( $ds ) ) {
		$ds[] = (float) $o['kg_mac_dinh'];
	}

	/* Doi sang kg neu website dang dung don vi khac */
	if ( function_exists( 'wc_get_weight' ) ) {
		$don_vi = get_option( 'woocommerce_weight_unit', 'kg' );
		if ( 'kg' !== $don_vi ) {
			foreach ( $ds as $i => $w ) {
				$ds[ $i ] = (float) wc_get_weight( $w, 'kg', $don_vi );
			}
		}
	}

	return array( min( $ds ), max( $ds ) );
}

/* Phi giao tinh khac theo so kg */
function smlife_sch_phi_tinh( $kg ) {
	$o   = smlife_sch_opt();
	$phi = (float) $o['tinh_co_dinh'] + ( (float) $kg * (float) $o['tinh_moi_kg'] );
	if ( (float) $o['tinh_toi_da'] > 0 ) {
		$phi = min( $phi, (float) $o['tinh_toi_da'] );
	}
	return (int) round( $phi );
}

/* Khoi MonetaryAmount. Bang nhau thi dung value, khac nhau thi dung khoang. */
function smlife_sch_tien( $min, $max ) {
	$a = array( '@type' => 'MonetaryAmount', 'currency' => get_woocommerce_currency() );
	if ( (int) $min === (int) $max ) {
		$a['value'] = (string) (int) $min;
	} else {
		$a['minValue'] = (string) (int) $min;
		$a['maxValue'] = (string) (int) $max;
	}
	return $a;
}

function smlife_sch_thoi_gian( $xuly_min, $xuly_max, $giao_min, $giao_max ) {
	return array(
		'@type'        => 'ShippingDeliveryTime',
		'handlingTime' => array(
			'@type'    => 'QuantitativeValue',
			'minValue' => (int) $xuly_min,
			'maxValue' => (int) $xuly_max,
			'unitCode' => 'DAY',
		),
		'transitTime'  => array(
			'@type'    => 'QuantitativeValue',
			'minValue' => (int) $giao_min,
			'maxValue' => (int) $giao_max,
			'unitCode' => 'DAY',
		),
	);
}

/* ===== shippingDetails: 2 vung, HCM va cac tinh con lai ===== */
function smlife_sch_giao_hang( $product ) {
	$o = smlife_sch_opt();
	list( $kg_min, $kg_max ) = smlife_sch_can_nang( $product );

	$hcm_phi = empty( $o['hcm_free'] ) ? (int) $o['hcm_rate'] : 0;

	$vung_hcm = array(
		'@type'               => 'OfferShippingDetails',
		'shippingRate'        => smlife_sch_tien( $hcm_phi, $hcm_phi ),
		'shippingDestination' => array(
			'@type'          => 'DefinedRegion',
			'addressCountry' => 'VN',
			'addressRegion'  => 'VN-SG',
		),
		'deliveryTime'        => smlife_sch_thoi_gian(
			$o['hcm_xuly_min'], $o['hcm_xuly_max'], $o['hcm_giao_min'], $o['hcm_giao_max']
		),
	);

	$vung_tinh = array(
		'@type'               => 'OfferShippingDetails',
		'shippingRate'        => smlife_sch_tien(
			smlife_sch_phi_tinh( $kg_min ),
			smlife_sch_phi_tinh( $kg_max )
		),
		'shippingDestination' => array(
			'@type'          => 'DefinedRegion',
			'addressCountry' => 'VN',
		),
		'deliveryTime'        => smlife_sch_thoi_gian(
			$o['tinh_xuly_min'], $o['tinh_xuly_max'], $o['tinh_giao_min'], $o['tinh_giao_max']
		),
	);

	return array( $vung_hcm, $vung_tinh );
}

/* ===== hasMerchantReturnPolicy ===== */
function smlife_sch_doi_tra() {
	$o = smlife_sch_opt();

	$cach = in_array( $o['cach_tra'], array( 'ReturnByMail', 'ReturnInStore', 'ReturnAtKiosk' ), true )
		? $o['cach_tra'] : 'ReturnByMail';

	$pol = array(
		'@type'                => 'MerchantReturnPolicy',
		'applicableCountry'    => 'VN',
		'returnPolicyCategory' => 'https://schema.org/MerchantReturnFiniteReturnWindow',
		'merchantReturnDays'   => (int) $o['doi_tra_ngay'],
		'returnMethod'         => 'https://schema.org/' . $cach,
	);

	if ( 'shop' === $o['ai_tra_phi'] ) {
		$pol['returnFees'] = 'https://schema.org/FreeReturn';
	} else {
		$pol['returnFees']          = 'https://schema.org/ReturnShippingFees';
		$pol['returnShippingFeesAmount'] = array(
			'@type'    => 'MonetaryAmount',
			'value'    => '0',
			'currency' => get_woocommerce_currency(),
		);
	}

	if ( ! empty( $o['link_doi_tra'] ) ) {
		$pol['merchantReturnLink'] = esc_url_raw( $o['link_doi_tra'] );
	}

	return $pol;
}

/* ===== Lay doi tuong san pham dang hien ===== */
function smlife_sch_product() {
	global $product;
	if ( $product instanceof WC_Product ) {
		return $product;
	}
	$p = function_exists( 'wc_get_product' ) ? wc_get_product( get_the_ID() ) : null;
	return ( $p instanceof WC_Product ) ? $p : false;
}

/* ===== Don aggregateRating rong ===== */
function smlife_sch_don_rating( $entity, $product ) {
	$o = smlife_sch_opt();
	if ( empty( $o['don_rating'] ) ) {
		return $entity;
	}

	$so  = (int) $product->get_review_count();
	$tb  = (float) $product->get_average_rating();

	if ( $so < 1 || $tb <= 0 ) {
		unset( $entity['aggregateRating'], $entity['review'] );
		return $entity;
	}

	$tb = max( 1, min( 5, $tb ) );
	$entity['aggregateRating'] = array(
		'@type'       => 'AggregateRating',
		'ratingValue' => number_format( $tb, 1, '.', '' ),
		'reviewCount' => $so,
		'ratingCount' => $so,
		'bestRating'  => '5',
		'worstRating' => '1',
	);
	return $entity;
}

/* ===== SKU va productGroupID ===== */
function smlife_sch_don_sku( $entity, $product ) {
	$o = smlife_sch_opt();
	if ( empty( $o['sua_sku'] ) ) {
		return $entity;
	}

	$sku = trim( (string) $product->get_sku() );
	if ( '' === $sku || ! preg_match( '/^[A-Za-z0-9._\-]{1,50}$/', $sku ) ) {
		$sku = 'SM-' . $product->get_id();
	}
	$entity['sku'] = $sku;
	if ( empty( $entity['mpn'] ) ) {
		$entity['mpn'] = $sku;
	}

	if ( isset( $entity['productGroupID'] ) ) {
		if ( empty( $entity['productGroupID'] ) || ! $product->is_type( 'variable' ) ) {
			unset( $entity['productGroupID'] );
		}
	}
	return $entity;
}

/* ===== Bo sung vao offers ===== */
function smlife_sch_vao_offers( $entity, $product ) {
	if ( empty( $entity['offers'] ) || ! is_array( $entity['offers'] ) ) {
		return $entity;
	}

	$giao  = smlife_sch_giao_hang( $product );
	$tra   = smlife_sch_doi_tra();
	$tu    = gmdate( 'Y-m-d', strtotime( '-1 day' ) );
	$den   = gmdate( 'Y-m-d', strtotime( '+1 year' ) );

	$la_ds = isset( $entity['offers'][0] );
	$ds    = $la_ds ? $entity['offers'] : array( $entity['offers'] );

	foreach ( $ds as $i => $of ) {
		if ( ! is_array( $of ) ) { continue; }

		$of['validFrom']               = $tu;
		$of['shippingDetails']         = $giao;
		$of['hasMerchantReturnPolicy'] = $tra;

		if ( empty( $of['priceValidUntil'] ) ) {
			$of['priceValidUntil'] = $den;
		}
		if ( empty( $of['priceCurrency'] ) ) {
			$of['priceCurrency'] = get_woocommerce_currency();
		}
		if ( empty( $of['itemCondition'] ) ) {
			$of['itemCondition'] = 'https://schema.org/NewCondition';
		}
		if ( empty( $of['availability'] ) ) {
			$of['availability'] = $product->is_in_stock()
				? 'https://schema.org/InStock'
				: 'https://schema.org/OutOfStock';
		}
		if ( empty( $of['seller'] ) ) {
			$of['seller'] = array(
				'@type' => 'Organization',
				'name'  => get_bloginfo( 'name' ),
				'url'   => home_url( '/' ),
			);
		}

		$ds[ $i ] = $of;
	}

	$entity['offers'] = $la_ds ? $ds : $ds[0];
	return $entity;
}

/* ===== Diem vao chung ===== */
function smlife_sch_va( $entity ) {
	$o = smlife_sch_opt();
	if ( empty( $o['enabled'] ) || ! is_array( $entity ) ) {
		return $entity;
	}
	$product = smlife_sch_product();
	if ( ! $product ) {
		return $entity;
	}

	$entity = smlife_sch_don_rating( $entity, $product );
	$entity = smlife_sch_don_sku( $entity, $product );
	$entity = smlife_sch_vao_offers( $entity, $product );

	return $entity;
}

/* Rank Math */
add_filter( 'rank_math/snippet/rich_snippet_product_entity', 'smlife_sch_va', 20, 1 );

/* Yoast WooCommerce SEO (du phong, chi chay khi khong co Rank Math) */
add_filter( 'wpseo_schema_product', function ( $data ) {
	if ( class_exists( 'RankMath' ) ) {
		return $data;
	}
	return smlife_sch_va( $data );
}, 20, 1 );

/* ===== Submenu: SMLIFE Core > Schema sản phẩm ===== */
add_action( 'admin_menu', function () {
	add_submenu_page(
		'smlife-core',
		'Schema sản phẩm',
		'Schema sản phẩm',
		'manage_options',
		'smlife-schema-san-pham',
		'smlife_sch_page'
	);
}, 12 );

function smlife_sch_page() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}
	$o = smlife_sch_opt();

	/* Vi du phi de chu web doi chieu voi bang gia Viettel Post */
	$vd = array();
	foreach ( array( 5, 10, 20, 40 ) as $kg ) {
		$vd[ $kg ] = number_format( smlife_sch_phi_tinh( $kg ), 0, ',', '.' );
	}

	echo '<div class="wrap smlife-wrap">';
	echo '<div class="smc-hd"><h1>Schema sản phẩm (Google Merchant Listings)</h1>';
	echo '<p>Khai báo phí giao hàng, thời gian giao và chính sách đổi trả cho Google. Thiếu ba thông tin này thì sản phẩm không được đưa vào tab Shopping và khối sản phẩm phổ biến trên trang kết quả tìm kiếm.</p></div>';

	if ( isset( $_GET['updated'] ) ) {
		echo '<div class="notice notice-success is-dismissible"><p>Đã lưu. Nhớ xoá cache rồi kiểm tra lại bằng công cụ Rich Results Test của Google.</p></div>';
	}

	echo '<div class="smlife-card">';
	echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '">';
	echo '<input type="hidden" name="action" value="smlife_sch_save" />';
	wp_nonce_field( 'smlife_sch_save', 'smlife_sch_nonce' );

	echo '<table class="form-table" role="presentation">';

	echo '<tr><th scope="row">Bật tính năng</th><td><label><input type="checkbox" name="sc[enabled]" value="1" ' . checked( 1, $o['enabled'], false ) . ' /> Bổ sung shippingDetails, hasMerchantReturnPolicy và validFrom vào schema sản phẩm</label></td></tr>';

	echo '<tr><th colspan="2"><h2 style="margin:18px 0 0">Giao hàng nội thành TP.HCM</h2></th></tr>';
	echo '<tr><th scope="row">Miễn phí</th><td><label><input type="checkbox" name="sc[hcm_free]" value="1" ' . checked( 1, $o['hcm_free'], false ) . ' /> Miễn phí giao hàng trong TP.HCM</label></td></tr>';
	echo '<tr><th scope="row">Phí nếu không miễn</th><td><input type="number" name="sc[hcm_rate]" value="' . esc_attr( $o['hcm_rate'] ) . '" min="0" step="1000" class="small-text" /> đ <span class="description">Chỉ dùng khi bỏ chọn ô Miễn phí ở trên</span></td></tr>';
	echo '<tr><th scope="row">Thời gian</th><td>Chuẩn bị hàng <input type="number" name="sc[hcm_xuly_min]" value="' . esc_attr( $o['hcm_xuly_min'] ) . '" min="0" class="small-text" /> đến <input type="number" name="sc[hcm_xuly_max]" value="' . esc_attr( $o['hcm_xuly_max'] ) . '" min="0" class="small-text" /> ngày, vận chuyển <input type="number" name="sc[hcm_giao_min]" value="' . esc_attr( $o['hcm_giao_min'] ) . '" min="0" class="small-text" /> đến <input type="number" name="sc[hcm_giao_max]" value="' . esc_attr( $o['hcm_giao_max'] ) . '" min="0" class="small-text" /> ngày</td></tr>';

	echo '<tr><th colspan="2"><h2 style="margin:18px 0 0">Giao hàng tỉnh khác (Viettel Post)</h2></th></tr>';
	echo '<tr><th scope="row">Phí cố định</th><td><input type="number" name="sc[tinh_co_dinh]" value="' . esc_attr( $o['tinh_co_dinh'] ) . '" min="0" step="1000" class="regular-text" style="max-width:160px" /> đ <span class="description">Phần không đổi, cộng vào mọi đơn đi tỉnh</span></td></tr>';
	echo '<tr><th scope="row">Phí mỗi kg</th><td><input type="number" name="sc[tinh_moi_kg]" value="' . esc_attr( $o['tinh_moi_kg'] ) . '" min="0" step="500" class="regular-text" style="max-width:160px" /> đ/kg <span class="description">Nhân với cân nặng khai trong từng sản phẩm</span></td></tr>';
	echo '<tr><th scope="row">Trần phí</th><td><input type="number" name="sc[tinh_toi_da]" value="' . esc_attr( $o['tinh_toi_da'] ) . '" min="0" step="10000" class="regular-text" style="max-width:160px" /> đ <span class="description">Để 0 nếu không giới hạn</span></td></tr>';
	echo '<tr><th scope="row">Cân nặng mặc định</th><td><input type="number" name="sc[kg_mac_dinh]" value="' . esc_attr( $o['kg_mac_dinh'] ) . '" min="1" step="1" class="small-text" /> kg <span class="description">Dùng cho sản phẩm chưa khai cân nặng. Khai thiếu thì phí báo cho Google sẽ sai.</span></td></tr>';
	echo '<tr><th scope="row">Thời gian</th><td>Chuẩn bị hàng <input type="number" name="sc[tinh_xuly_min]" value="' . esc_attr( $o['tinh_xuly_min'] ) . '" min="0" class="small-text" /> đến <input type="number" name="sc[tinh_xuly_max]" value="' . esc_attr( $o['tinh_xuly_max'] ) . '" min="0" class="small-text" /> ngày, vận chuyển <input type="number" name="sc[tinh_giao_min]" value="' . esc_attr( $o['tinh_giao_min'] ) . '" min="0" class="small-text" /> đến <input type="number" name="sc[tinh_giao_max]" value="' . esc_attr( $o['tinh_giao_max'] ) . '" min="0" class="small-text" /> ngày</td></tr>';
	echo '<tr><th scope="row">Thử công thức</th><td><code>5kg = ' . esc_html( $vd[5] ) . 'đ &nbsp;|&nbsp; 10kg = ' . esc_html( $vd[10] ) . 'đ &nbsp;|&nbsp; 20kg = ' . esc_html( $vd[20] ) . 'đ &nbsp;|&nbsp; 40kg = ' . esc_html( $vd[40] ) . 'đ</code><br><span class="description">So với bảng giá Viettel Post. Lệch nhiều thì chỉnh lại hai ô phí ở trên rồi lưu để xem lại.</span></td></tr>';

	echo '<tr><th colspan="2"><h2 style="margin:18px 0 0">Đổi trả</h2></th></tr>';
	echo '<tr><th scope="row">Số ngày đổi trả</th><td><input type="number" name="sc[doi_tra_ngay]" value="' . esc_attr( $o['doi_tra_ngay'] ) . '" min="0" max="365" class="small-text" /> ngày</td></tr>';
	echo '<tr><th scope="row">Ai trả phí ship khi đổi trả</th><td><select name="sc[ai_tra_phi]">';
	echo '<option value="shop" ' . selected( 'shop', $o['ai_tra_phi'], false ) . '>Shop trả, khách không mất phí</option>';
	echo '<option value="khach" ' . selected( 'khach', $o['ai_tra_phi'], false ) . '>Khách tự trả phí gửi hàng về</option>';
	echo '</select> <span class="description">Khai sai là lý do Google loại sản phẩm khỏi Shopping</span></td></tr>';
	echo '<tr><th scope="row">Cách gửi hàng về</th><td><select name="sc[cach_tra]">';
	echo '<option value="ReturnByMail" ' . selected( 'ReturnByMail', $o['cach_tra'], false ) . '>Gửi qua đơn vị vận chuyển</option>';
	echo '<option value="ReturnInStore" ' . selected( 'ReturnInStore', $o['cach_tra'], false ) . '>Mang tới showroom</option>';
	echo '</select></td></tr>';
	echo '<tr><th scope="row">Link chính sách</th><td><input type="url" name="sc[link_doi_tra]" value="' . esc_attr( $o['link_doi_tra'] ) . '" class="large-text" /></td></tr>';

	echo '<tr><th colspan="2"><h2 style="margin:18px 0 0">Dọn dữ liệu lỗi</h2></th></tr>';
	echo '<tr><th scope="row">Đánh giá rỗng</th><td><label><input type="checkbox" name="sc[don_rating]" value="1" ' . checked( 1, $o['don_rating'], false ) . ' /> Bỏ khối aggregateRating ở sản phẩm chưa có đánh giá nào</label><br><span class="description">Xuất rating rỗng là lỗi, Google bỏ luôn ngôi sao của cả website</span></td></tr>';
	echo '<tr><th scope="row">SKU</th><td><label><input type="checkbox" name="sc[sua_sku]" value="1" ' . checked( 1, $o['sua_sku'], false ) . ' /> Tự sinh SKU hợp lệ cho sản phẩm bỏ trống hoặc có ký tự lạ</label></td></tr>';

	echo '</table>';
	submit_button( 'Lưu' );
	echo '</form></div>';

	echo '<div class="smlife-card"><h2>Kiểm tra sau khi lưu</h2><ol>';
	echo '<li>Xoá cache website (WP Rocket và Redis nếu có).</li>';
	echo '<li>Mở <a href="https://search.google.com/test/rich-results" target="_blank" rel="noopener">Rich Results Test</a>, dán link một sản phẩm bất kỳ.</li>';
	echo '<li>Trong kết quả, mở mục Merchant listings và kiểm tra có đủ <code>shippingDetails</code>, <code>hasMerchantReturnPolicy</code>, <code>validFrom</code>.</li>';
	echo '<li>Thử thêm một sản phẩm chưa có đánh giá, xác nhận không còn <code>aggregateRating</code>.</li>';
	echo '<li>Vào Search Console, mục Merchant listings, bấm VALIDATE FIX từng dòng lỗi.</li>';
	echo '</ol></div>';

	echo '</div>';
}

add_action( 'admin_post_smlife_sch_save', function () {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( 'Không đủ quyền.' );
	}
	check_admin_referer( 'smlife_sch_save', 'smlife_sch_nonce' );

	$in  = isset( $_POST['sc'] ) && is_array( $_POST['sc'] ) ? wp_unslash( $_POST['sc'] ) : array();
	$def = smlife_sch_opt();
	$so  = function ( $k, $min = 0 ) use ( $in, $def ) {
		return isset( $in[ $k ] ) ? max( $min, (int) $in[ $k ] ) : (int) $def[ $k ];
	};

	$new = array(
		'enabled'       => empty( $in['enabled'] ) ? 0 : 1,
		'hcm_free'      => empty( $in['hcm_free'] ) ? 0 : 1,
		'hcm_rate'      => $so( 'hcm_rate' ),
		'tinh_co_dinh'  => $so( 'tinh_co_dinh' ),
		'tinh_moi_kg'   => $so( 'tinh_moi_kg' ),
		'tinh_toi_da'   => $so( 'tinh_toi_da' ),
		'kg_mac_dinh'   => $so( 'kg_mac_dinh', 1 ),
		'hcm_xuly_min'  => $so( 'hcm_xuly_min' ),
		'hcm_xuly_max'  => $so( 'hcm_xuly_max' ),
		'hcm_giao_min'  => $so( 'hcm_giao_min' ),
		'hcm_giao_max'  => $so( 'hcm_giao_max' ),
		'tinh_xuly_min' => $so( 'tinh_xuly_min' ),
		'tinh_xuly_max' => $so( 'tinh_xuly_max' ),
		'tinh_giao_min' => $so( 'tinh_giao_min' ),
		'tinh_giao_max' => $so( 'tinh_giao_max' ),
		'doi_tra_ngay'  => $so( 'doi_tra_ngay' ),
		'ai_tra_phi'    => ( isset( $in['ai_tra_phi'] ) && 'shop' === $in['ai_tra_phi'] ) ? 'shop' : 'khach',
		'cach_tra'      => ( isset( $in['cach_tra'] ) && 'ReturnInStore' === $in['cach_tra'] ) ? 'ReturnInStore' : 'ReturnByMail',
		'link_doi_tra'  => isset( $in['link_doi_tra'] ) ? esc_url_raw( $in['link_doi_tra'] ) : $def['link_doi_tra'],
		'don_rating'    => empty( $in['don_rating'] ) ? 0 : 1,
		'sua_sku'       => empty( $in['sua_sku'] ) ? 0 : 1,
	);

	update_option( 'smlife_sch', $new );
	wp_safe_redirect( admin_url( 'admin.php?page=smlife-schema-san-pham&updated=1' ) );
	exit;
} );
