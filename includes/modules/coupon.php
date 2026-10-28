<?php
/**
 * SMLIFE Core - Module: Coupon nang cao, thay Smart Coupons.
 *
 * Them 3 dieu kien cho ma giam gia goc WooCommerce:
 *  - Tu dong ap khi du dieu kien (tab Chung cua trang sua ma)
 *  - Chi ap cho phuong thuc thanh toan chon truoc (tab Gioi han su dung)
 *  - Chi giam cho san pham co the (tag) chon truoc (tab Gioi han su dung)
 *
 * AN TOAN: khi Smart Coupons con bat, module CHI hien cai dat va bang xem truoc,
 * KHONG chay logic (tranh ap ma 2 lan). Tat Smart Coupons la module tu chay,
 * bat lai Smart Coupons la module tu dung. Du lieu cua Smart Coupons giu nguyen.
 *
 * Gioi han so ma tu ap cung luc (mac dinh 5, giong Smart Coupons).
 * Ma "Dung rieng le": giong WooCommerce, ma rieng le tu dong hop le thi day cac
 * ma khac ra va dung mot minh; khach tu nhap ma rieng le thi khong ap them ma nao.
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

/* ============================================================
 * 1. TIEN ICH
 * ============================================================ */
function smlife_cp_sc_active() {
	static $on = null;
	if ( null === $on ) {
		$on = class_exists( 'WC_Smart_Coupons' ) || in_array( 'woocommerce-smart-coupons/woocommerce-smart-coupons.php', (array) get_option( 'active_plugins', array() ), true );
	}
	return $on;
}

/* Module chi chay logic khi co WooCommerce va Smart Coupons da tat */
function smlife_cp_chay() {
	return class_exists( 'WooCommerce' ) && ! smlife_cp_sc_active();
}

function smlife_cp_opt() {
	$o = get_option( 'smlife_cp', array() );
	$o = is_array( $o ) ? $o : array();
	return array_merge( array( 'max' => 5 ), $o );
}

function smlife_cp_get( $coupon ) {
	$id   = is_object( $coupon ) ? (int) $coupon->get_id() : (int) $coupon;
	$pay  = get_post_meta( $id, '_smlife_cp_pay', true );
	$tags = get_post_meta( $id, '_smlife_cp_tags', true );
	return array(
		'auto' => 'yes' === get_post_meta( $id, '_smlife_cp_auto', true ),
		'pay'  => array_values( array_filter( is_array( $pay ) ? array_map( 'strval', $pay ) : array() ) ),
		'tags' => array_values( array_filter( array_map( 'intval', is_array( $tags ) ? $tags : array() ) ) ),
	);
}

/* Danh sach id ma tu dong (dang dang). Luu tam 1 ngay, xoa khi sua ma. */
function smlife_cp_auto_ids() {
	$ids = get_transient( 'smlife_cp_auto_ids' );
	if ( false === $ids ) {
		$ids = get_posts( array(
			'post_type'   => 'shop_coupon',
			'post_status' => 'publish',
			'numberposts' => -1,
			'fields'      => 'ids',
			'orderby'     => 'ID',
			'order'       => 'ASC',
			'meta_key'    => '_smlife_cp_auto', // phpcs:ignore
			'meta_value'  => 'yes', // phpcs:ignore
		) );
		set_transient( 'smlife_cp_auto_ids', $ids, DAY_IN_SECONDS );
	}
	return array_map( 'intval', (array) $ids );
}

function smlife_cp_flush( $post_id = 0 ) {
	if ( ! $post_id || 'shop_coupon' === get_post_type( $post_id ) ) {
		delete_transient( 'smlife_cp_auto_ids' );
	}
}
add_action( 'save_post_shop_coupon', 'smlife_cp_flush' );
add_action( 'trashed_post', 'smlife_cp_flush' );
add_action( 'untrashed_post', 'smlife_cp_flush' );
add_action( 'deleted_post', 'smlife_cp_flush' );

/* Phuong thuc thanh toan khach dang chon */
function smlife_cp_chosen_pay() {
	if ( ! empty( $_POST['payment_method'] ) ) { // phpcs:ignore
		return sanitize_text_field( wp_unslash( $_POST['payment_method'] ) ); // phpcs:ignore
	}
	$pm = ( function_exists( 'WC' ) && WC()->session ) ? WC()->session->get( 'chosen_payment_method' ) : '';
	if ( $pm ) {
		return (string) $pm;
	}
	/* Chua chon: lay phuong thuc dau tien dang bat, giong mac dinh cua trang thanh toan */
	if ( function_exists( 'WC' ) && WC()->payment_gateways() ) {
		foreach ( WC()->payment_gateways()->get_available_payment_gateways() as $id => $gw ) {
			return (string) $id;
		}
	}
	return '';
}

function smlife_cp_has_tags( $product, $tags ) {
	if ( ! $product instanceof WC_Product || ! $tags ) {
		return false;
	}
	$pid = $product->get_parent_id() ? $product->get_parent_id() : $product->get_id();
	return (bool) array_intersect( $tags, array_map( 'intval', wc_get_product_term_ids( $pid, 'product_tag' ) ) );
}

function smlife_cp_ten_pay( $ids ) {
	$all   = ( function_exists( 'WC' ) && WC()->payment_gateways() ) ? WC()->payment_gateways()->payment_gateways() : array();
	$names = array();
	foreach ( (array) $ids as $id ) {
		$names[] = isset( $all[ $id ] ) ? wp_strip_all_tags( $all[ $id ]->get_title() ) : $id;
	}
	return $names;
}

/* ============================================================
 * 2. KIEM TRA HOP LE (chi khi module chay)
 * ============================================================ */
add_filter( 'woocommerce_coupon_is_valid', function ( $valid, $coupon, $discounts = null ) {
	if ( ! $valid || ! smlife_cp_chay() || ! $coupon instanceof WC_Coupon ) {
		return $valid;
	}
	$c = smlife_cp_get( $coupon );

	if ( $c['pay'] && ! in_array( smlife_cp_chosen_pay(), $c['pay'], true ) ) {
		throw new Exception( sprintf( 'Mã %s chỉ áp dụng khi thanh toán bằng: %s.', strtoupper( $coupon->get_code() ), implode( ', ', smlife_cp_ten_pay( $c['pay'] ) ) ), 109 );
	}

	if ( $c['tags'] ) {
		$ok    = false;
		$items = ( is_object( $discounts ) && method_exists( $discounts, 'get_items' ) ) ? $discounts->get_items() : array();
		foreach ( $items as $it ) {
			if ( isset( $it->product ) && smlife_cp_has_tags( $it->product, $c['tags'] ) ) {
				$ok = true;
				break;
			}
		}
		if ( ! $items && function_exists( 'WC' ) && WC()->cart ) {
			foreach ( WC()->cart->get_cart() as $ci ) {
				if ( smlife_cp_has_tags( $ci['data'], $c['tags'] ) ) {
					$ok = true;
					break;
				}
			}
		}
		if ( ! $ok ) {
			throw new Exception( sprintf( 'Mã %s không áp dụng cho sản phẩm trong giỏ hàng.', strtoupper( $coupon->get_code() ) ), 109 );
		}
	}
	return $valid;
}, 10, 3 );

/* Chi giam cho san pham co the da chon */
add_filter( 'woocommerce_coupon_is_valid_for_product', function ( $valid, $product, $coupon, $values ) {
	if ( ! $valid || ! smlife_cp_chay() || ! $coupon instanceof WC_Coupon ) {
		return $valid;
	}
	$c = smlife_cp_get( $coupon );
	if ( $c['tags'] && ! smlife_cp_has_tags( $product, $c['tags'] ) ) {
		return false;
	}
	return $valid;
}, 10, 4 );

/* ============================================================
 * 3. TU DONG AP / GO MA
 * ============================================================ */
add_action( 'woocommerce_before_calculate_totals', function ( $cart ) {
	static $busy = false;
	if ( $busy || ! smlife_cp_chay() || ( is_admin() && ! wp_doing_ajax() ) ) {
		return;
	}
	if ( ! $cart instanceof WC_Cart || $cart->is_empty() ) {
		return;
	}
	$auto = smlife_cp_auto_ids();
	if ( ! $auto ) {
		return;
	}
	$busy = true;
	try {
		$coupons = array();
		foreach ( $auto as $id ) {
			$cp = new WC_Coupon( $id );
			if ( $cp->get_code() ) {
				$coupons[ wc_format_coupon_code( $cp->get_code() ) ] = $cp;
			}
		}
		$before  = array_map( 'wc_format_coupon_code', $cart->get_applied_coupons() );
		$applied = $before;
		$disc    = new WC_Discounts( $cart );

		/* 1. Go ma tu dong khong con hop le (VD khach doi phuong thuc thanh toan) */
		foreach ( $coupons as $code => $cp ) {
			if ( in_array( $code, $applied, true ) && true !== $disc->is_coupon_valid( $cp ) ) {
				$applied = array_values( array_diff( $applied, array( $code ) ) );
			}
		}

		/* 2. Dang co ma "dung rieng le" (tu dong hay khach tu nhap) thi khong ap them */
		$locked = false;
		foreach ( $applied as $code ) {
			$cp = isset( $coupons[ $code ] ) ? $coupons[ $code ] : new WC_Coupon( $code );
			if ( $cp->get_individual_use() ) {
				$locked = true;
				break;
			}
		}

		/* 3. Ap cac ma tu dong du dieu kien, toi da $max ma tu dong cung luc */
		if ( ! $locked ) {
			$o   = smlife_cp_opt();
			$max = max( 1, (int) $o['max'] );
			$dem = count( array_intersect( $applied, array_keys( $coupons ) ) );
			foreach ( $coupons as $code => $cp ) {
				if ( $dem >= $max ) {
					break;
				}
				if ( in_array( $code, $applied, true ) || true !== $disc->is_coupon_valid( $cp ) ) {
					continue;
				}
				if ( $cp->get_individual_use() ) {
					/* Giong WooCommerce apply_coupon (cach Smart Coupons dang chay):
					   ma rieng le hop le thi day het cac ma khac ra, dung mot minh. */
					$applied = array( $code );
					break;
				}
				$applied[] = $code;
				$dem++;
			}
		}

		if ( $applied !== $before ) {
			$cart->set_applied_coupons( $applied );
		}
	} catch ( Throwable $e ) {
		error_log( '[SMLIFE Core] Coupon tu dong loi: ' . $e->getMessage() );
	}
	$busy = false;
}, 5 );

/* Doi phuong thuc thanh toan thi tinh lai tong, de ma theo phuong thuc tu ap/go */
add_action( 'wp_footer', function () {
	if ( ! smlife_cp_chay() || ! function_exists( 'is_checkout' ) || ! is_checkout() ) {
		return;
	}
	echo "<script>jQuery(function($){\$(document.body).on('change','input[name=\"payment_method\"]',function(){\$(document.body).trigger('update_checkout');});});</script>";
} );

/* Gan chu "tu dong" canh ten ma o tong tien */
add_filter( 'woocommerce_cart_totals_coupon_label', function ( $label, $coupon ) {
	if ( smlife_cp_chay() && is_object( $coupon ) && in_array( (int) $coupon->get_id(), smlife_cp_auto_ids(), true ) ) {
		$label .= ' (tự động)';
	}
	return $label;
}, 10, 2 );

/* ============================================================
 * 4. O CAI DAT TRONG TRANG SUA MA GIAM GIA
 * ============================================================ */
add_action( 'woocommerce_coupon_options', function ( $coupon_id ) {
	woocommerce_wp_checkbox( array(
		'id'          => '_smlife_cp_auto',
		'label'       => 'Tự động áp (SMLIFE)',
		'description' => 'Tự gắn mã vào giỏ khi đủ điều kiện, tự gỡ khi hết điều kiện.' . ( smlife_cp_sc_active() ? ' Chỉ có tác dụng sau khi tắt Smart Coupons.' : '' ),
		'value'       => get_post_meta( $coupon_id, '_smlife_cp_auto', true ),
	) );
}, 10, 1 );

add_action( 'woocommerce_coupon_options_usage_restriction', function ( $coupon_id ) {
	$c   = smlife_cp_get( $coupon_id );
	$gws = ( function_exists( 'WC' ) && WC()->payment_gateways() ) ? WC()->payment_gateways()->payment_gateways() : array();
	echo '<div class="options_group">';
	echo '<p class="form-field"><label for="_smlife_cp_pay">Phương thức thanh toán (SMLIFE)</label>';
	echo '<select id="_smlife_cp_pay" name="_smlife_cp_pay[]" class="wc-enhanced-select" multiple style="width:50%" data-placeholder="Mọi phương thức">';
	foreach ( $gws as $id => $gw ) {
		$tag = 'yes' === $gw->enabled ? '' : ' (đang tắt)';
		echo '<option value="' . esc_attr( $id ) . '"' . selected( in_array( (string) $id, $c['pay'], true ), true, false ) . '>' . esc_html( wp_strip_all_tags( $gw->get_title() ) . $tag ) . '</option>';
	}
	echo '</select> ' . wc_help_tip( 'Để trống là áp cho mọi phương thức. Khách đổi phương thức ở trang thanh toán thì mã tự áp hoặc tự gỡ.' ) . '</p>'; // phpcs:ignore
	echo '<p class="form-field"><label for="_smlife_cp_tags">Thẻ sản phẩm (SMLIFE)</label>';
	echo '<select id="_smlife_cp_tags" name="_smlife_cp_tags[]" multiple style="width:50%" data-placeholder="Gõ để tìm thẻ">';
	foreach ( $c['tags'] as $tid ) {
		$t = get_term( $tid, 'product_tag' );
		if ( $t && ! is_wp_error( $t ) ) {
			echo '<option value="' . (int) $tid . '" selected>' . esc_html( $t->name ) . '</option>';
		}
	}
	echo '</select> ' . wc_help_tip( 'Chỉ giảm cho sản phẩm có ít nhất một thẻ đã chọn. Để trống là không giới hạn theo thẻ.' ) . '</p>'; // phpcs:ignore
	echo '</div>';
}, 10, 1 );

add_action( 'woocommerce_coupon_options_save', function ( $post_id ) {
	/* Nonce da duoc WooCommerce kiem tra truoc khi goi hook nay */
	$pay  = isset( $_POST['_smlife_cp_pay'] ) ? array_map( 'sanitize_text_field', (array) wp_unslash( $_POST['_smlife_cp_pay'] ) ) : array(); // phpcs:ignore
	$tags = isset( $_POST['_smlife_cp_tags'] ) ? array_map( 'absint', (array) wp_unslash( $_POST['_smlife_cp_tags'] ) ) : array(); // phpcs:ignore
	update_post_meta( $post_id, '_smlife_cp_auto', empty( $_POST['_smlife_cp_auto'] ) ? 'no' : 'yes' ); // phpcs:ignore
	update_post_meta( $post_id, '_smlife_cp_pay', array_values( array_filter( $pay ) ) );
	update_post_meta( $post_id, '_smlife_cp_tags', array_values( array_filter( $tags ) ) );
	smlife_cp_flush();
}, 10, 1 );

/* Tim the san pham cho o chon (AJAX) */
add_action( 'wp_ajax_smlife_cp_tags', function () {
	check_ajax_referer( 'smlife_cp_tags', 'nonce' );
	if ( ! current_user_can( 'edit_shop_coupons' ) ) {
		wp_send_json( array() );
	}
	$q     = isset( $_GET['q'] ) ? sanitize_text_field( wp_unslash( $_GET['q'] ) ) : '';
	$terms = get_terms( array( 'taxonomy' => 'product_tag', 'hide_empty' => false, 'search' => $q, 'number' => 30 ) );
	$out   = array();
	if ( ! is_wp_error( $terms ) ) {
		foreach ( $terms as $t ) {
			$out[] = array( 'id' => $t->term_id, 'text' => $t->name . ' (' . $t->count . ')' );
		}
	}
	wp_send_json( $out );
} );

add_action( 'admin_footer', function () {
	$s = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
	if ( ! $s || 'shop_coupon' !== $s->id ) {
		return;
	}
	?>
	<script>
	jQuery(function ($) {
		if (!$.fn.selectWoo) { return; }
		$('#_smlife_cp_tags').selectWoo({
			minimumInputLength: 1,
			width: '50%',
			ajax: {
				url: ajaxurl, dataType: 'json', delay: 250,
				data: function (p) { return { action: 'smlife_cp_tags', q: p.term, nonce: '<?php echo esc_js( wp_create_nonce( 'smlife_cp_tags' ) ); ?>' }; },
				processResults: function (d) { return { results: d }; }
			}
		});
	});
	</script>
	<?php
} );

/* ============================================================
 * 5. NHAP TU SMART COUPONS
 * Doc meta cua Smart Coupons, chep sang meta rieng cua SMLIFE.
 * Khong sua, khong xoa du lieu cua Smart Coupons.
 * ============================================================ */

/* Chuyen 1 gia tri the (id, slug hoac ten) thanh term_id product_tag */
function smlife_cp_term_id( $v ) {
	if ( is_numeric( $v ) ) {
		$t = get_term( (int) $v, 'product_tag' );
		return ( $t && ! is_wp_error( $t ) ) ? (int) $t->term_id : 0;
	}
	foreach ( array( 'slug', 'name' ) as $by ) {
		$t = get_term_by( $by, (string) $v, 'product_tag' );
		if ( $t ) {
			return (int) $t->term_id;
		}
	}
	return 0;
}

/* Doc gioi han the cua Smart Coupons. Tra ve array( tags => [], la => [] ).
 * 'la' la cac gioi han khong chuyen duoc (taxonomy khac, dieu kien loai tru...). */
function smlife_cp_sc_tags( $id ) {
	$raw  = maybe_unserialize( get_post_meta( $id, 'wc_sc_taxonomy_restrictions', true ) );
	$tags = array();
	$la   = array();
	if ( ! is_array( $raw ) || ! $raw ) {
		return array( 'tags' => $tags, 'la' => $la );
	}
	foreach ( $raw as $r ) {
		if ( ! is_array( $r ) ) {
			continue;
		}
		$tax = '';
		$op  = 'incl';
		$val = array();
		foreach ( $r as $k => $v ) {
			$k = strtolower( (string) $k );
			if ( in_array( $k, array( 'tax', 'taxonomy' ), true ) ) {
				$tax = (string) $v;
			} elseif ( in_array( $k, array( 'op', 'operator' ), true ) ) {
				$op = strtolower( (string) $v );
			} elseif ( in_array( $k, array( 'val', 'value', 'values', 'terms' ), true ) ) {
				$val = is_array( $v ) ? $v : explode( ',', (string) $v );
			}
		}
		if ( 'product_tag' !== $tax || 0 === strpos( $op, 'ex' ) ) {
			$la[] = ( $tax ? $tax : '?' ) . ' ' . $op;
			continue;
		}
		foreach ( $val as $v ) {
			$tid = smlife_cp_term_id( trim( (string) $v ) );
			if ( $tid ) {
				$tags[] = $tid;
			} else {
				$la[] = 'thẻ không tìm thấy: ' . $v;
			}
		}
	}
	return array( 'tags' => array_values( array_unique( $tags ) ), 'la' => $la );
}

/* Gia tri rong theo nghia sau: moi gia tri le ben trong deu la '', 'no', '0', null */
function smlife_cp_rong( $v ) {
	if ( is_array( $v ) ) {
		foreach ( $v as $k => $x ) {
			/* Khoa bo chon (loai, toan tu, taxonomy) khong phai gia tri cai dat */
			if ( in_array( (string) $k, array( 'type', 'condition', 'op', 'operator', 'tax', 'taxonomy' ), true ) ) {
				continue;
			}
			if ( ! smlife_cp_rong( $x ) ) {
				return false;
			}
		}
		return true;
	}
	return null === $v || false === $v || '' === trim( (string) $v ) || 'no' === $v || '0' === (string) $v;
}

/* Cac cai dat khac cua Smart Coupons dang co gia tri tren ma (de tham khao) */
function smlife_cp_sc_khac( $id ) {
	$bo  = array( 'wc_sc_auto_apply_coupon', 'wc_sc_payment_method_ids', 'wc_sc_taxonomy_restrictions' );
	$out = array();
	foreach ( (array) get_post_meta( $id ) as $k => $v ) {
		if ( 0 !== strpos( (string) $k, 'wc_sc_' ) || in_array( $k, $bo, true ) ) {
			continue;
		}
		$v = maybe_unserialize( is_array( $v ) ? reset( $v ) : $v );
		if ( smlife_cp_rong( $v ) ) {
			continue;
		}
		$out[] = $k;
	}
	return $out;
}

/* Gioi han so ma tu ap cua Smart Coupons, neu tim thay */
function smlife_cp_sc_max() {
	foreach ( array( 'wc_sc_max_auto_apply_coupons_limit', 'wc_sc_auto_apply_coupon_limit' ) as $k ) {
		$v = get_option( $k, '' );
		if ( '' !== $v && is_numeric( $v ) && (int) $v > 0 ) {
			return (int) $v;
		}
	}
	return 0;
}

add_action( 'admin_post_smlife_cp_import', function () {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( 'Không đủ quyền.' );
	}
	check_admin_referer( 'smlife_cp_import', 'smlife_cp_nonce' );
	$ids = get_posts( array( 'post_type' => 'shop_coupon', 'post_status' => array( 'publish', 'draft', 'pending', 'private', 'future' ), 'numberposts' => -1, 'fields' => 'ids' ) );
	$n   = 0;
	foreach ( $ids as $id ) {
		$auto = 'yes' === get_post_meta( $id, 'wc_sc_auto_apply_coupon', true ) ? 'yes' : 'no';
		$pay  = maybe_unserialize( get_post_meta( $id, 'wc_sc_payment_method_ids', true ) );
		$pay  = is_array( $pay ) ? array_values( array_filter( array_map( 'strval', $pay ) ) ) : array();
		$tg   = smlife_cp_sc_tags( $id );
		update_post_meta( $id, '_smlife_cp_auto', $auto );
		update_post_meta( $id, '_smlife_cp_pay', $pay );
		update_post_meta( $id, '_smlife_cp_tags', $tg['tags'] );
		$n++;
	}
	$sc_max = smlife_cp_sc_max();
	if ( $sc_max ) {
		$o        = smlife_cp_opt();
		$o['max'] = $sc_max;
		update_option( 'smlife_cp', $o, false );
	}
	smlife_cp_flush();
	wp_safe_redirect( admin_url( 'admin.php?page=smlife-coupon&imported=' . $n ) );
	exit;
} );

add_action( 'admin_post_smlife_cp_save', function () {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( 'Không đủ quyền.' );
	}
	check_admin_referer( 'smlife_cp_save', 'smlife_cp_nonce' );
	$o        = smlife_cp_opt();
	$o['max'] = max( 1, min( 50, isset( $_POST['max'] ) ? (int) $_POST['max'] : 5 ) );
	update_option( 'smlife_cp', $o, false );
	wp_safe_redirect( admin_url( 'admin.php?page=smlife-coupon&saved=1' ) );
	exit;
} );

/* ============================================================
 * 6. TRANG QUAN TRI
 * ============================================================ */
add_action( 'admin_menu', function () {
	add_submenu_page( 'smlife-core', 'Coupon nâng cao', 'Coupon nâng cao', 'manage_options', 'smlife-coupon', 'smlife_cp_page' );
}, 47 );

function smlife_cp_page() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}
	echo '<div class="wrap smlife-wrap"><div class="smc-hd"><h1>Coupon nâng cao</h1>';
	echo '<p>Tự động áp mã, giới hạn theo phương thức thanh toán và thẻ sản phẩm, thay cho Smart Coupons. Mã giảm giá vẫn là mã gốc WooCommerce.</p></div>';
	if ( ! class_exists( 'WooCommerce' ) ) {
		echo '<p>Module cần WooCommerce.</p></div>';
		return;
	}
	$o      = smlife_cp_opt();
	$sc_max = smlife_cp_sc_max();

	if ( smlife_cp_sc_active() ) {
		echo '<div class="notice notice-warning"><p><b>Smart Coupons đang bật, module đang ở chế độ chờ.</b> Chưa chạy tự áp và kiểm tra điều kiện, website vẫn do Smart Coupons xử lý như cũ. Các bước chuyển: 1) Bấm "Nhập từ Smart Coupons". 2) Đối chiếu bảng bên dưới, cột "Khớp" phải toàn OK. 3) Tắt Smart Coupons, module tự chạy. Có vấn đề thì bật lại Smart Coupons, module tự dừng.</p></div>';
	} else {
		echo '<div class="notice notice-success"><p><b>Module đang chạy</b> (Smart Coupons đã tắt).</p></div>';
	}
	if ( isset( $_GET['imported'] ) ) {
		echo '<div class="notice notice-info is-dismissible"><p>Đã nhập cài đặt cho ' . (int) $_GET['imported'] . ' mã từ Smart Coupons.</p></div>';
	}
	if ( isset( $_GET['saved'] ) ) {
		echo '<div class="notice notice-success is-dismissible"><p>Đã lưu.</p></div>';
	}

	echo '<div class="smlife-card"><h2>Cài đặt chung</h2>';
	echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '">';
	echo '<input type="hidden" name="action" value="smlife_cp_save" />';
	wp_nonce_field( 'smlife_cp_save', 'smlife_cp_nonce' );
	echo '<p>Tối đa <input type="number" min="1" max="50" name="max" value="' . esc_attr( (int) $o['max'] ) . '" style="width:70px" /> mã tự động áp cùng lúc trong một giỏ.';
	echo $sc_max ? ' <span class="description">Smart Coupons đang đặt: ' . (int) $sc_max . '.</span>' : ''; // phpcs:ignore
	echo '</p>';
	submit_button( 'Lưu', 'primary', 'submit', false );
	echo '</form>';
	echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '" style="margin-top:14px" onsubmit="return confirm(\'Chép cài đặt Smart Coupons sang SMLIFE cho mọi mã? Dữ liệu của Smart Coupons giữ nguyên.\')">';
	echo '<input type="hidden" name="action" value="smlife_cp_import" />';
	wp_nonce_field( 'smlife_cp_import', 'smlife_cp_nonce' );
	submit_button( 'Nhập từ Smart Coupons', 'secondary', 'submit', false );
	echo ' <span class="description">Ghi đè 3 ô SMLIFE (Tự động, Thanh toán, Thẻ) của mọi mã bằng cấu hình Smart Coupons hiện có.</span>';
	echo '</form></div>';

	/* Bang doi chieu */
	$ids = get_posts( array( 'post_type' => 'shop_coupon', 'post_status' => 'publish', 'numberposts' => -1, 'fields' => 'ids', 'orderby' => 'title', 'order' => 'ASC' ) );
	$lech = 0;
	$rows = '';
	foreach ( $ids as $id ) {
		$cp  = new WC_Coupon( $id );
		$c   = smlife_cp_get( $id );
		$sca = 'yes' === get_post_meta( $id, 'wc_sc_auto_apply_coupon', true );
		$scp = maybe_unserialize( get_post_meta( $id, 'wc_sc_payment_method_ids', true ) );
		$scp = is_array( $scp ) ? array_values( array_filter( array_map( 'strval', $scp ) ) ) : array();
		$sct = smlife_cp_sc_tags( $id );
		$khac = smlife_cp_sc_khac( $id );
		$a1 = $c['pay'];
		$a2 = $scp;
		sort( $a1 );
		sort( $a2 );
		$t1 = $c['tags'];
		$t2 = $sct['tags'];
		sort( $t1 );
		sort( $t2 );
		$khop = $c['auto'] === $sca && $a1 === $a2 && $t1 === $t2 && ! $sct['la'];
		if ( ! $khop ) {
			$lech++;
		}
		$amount = 'percent' === $cp->get_discount_type() ? $cp->get_amount() . '%' : wp_strip_all_tags( wc_price( $cp->get_amount() ) );
		$tags   = array();
		foreach ( $c['tags'] as $t ) {
			$term = get_term( $t, 'product_tag' );
			if ( $term && ! is_wp_error( $term ) ) {
				$tags[] = $term->name;
			}
		}
		$cats = array();
		foreach ( (array) $cp->get_product_categories() as $t ) {
			$term = get_term( $t, 'product_cat' );
			if ( $term && ! is_wp_error( $term ) ) {
				$cats[] = $term->name;
			}
		}
		$ghi = array();
		if ( $c['auto'] !== $sca ) {
			$ghi[] = 'Tự động khác SC';
		}
		if ( $a1 !== $a2 ) {
			$ghi[] = 'Thanh toán khác SC';
		}
		if ( $t1 !== $t2 ) {
			$ghi[] = 'Thẻ khác SC';
		}
		foreach ( $sct['la'] as $x ) {
			$ghi[] = 'SC có giới hạn chưa hỗ trợ: ' . $x;
		}
		$rows .= '<tr' . ( $khop ? '' : ' style="background:#fef2f2"' ) . '>';
		$rows .= '<td><a href="' . esc_url( get_edit_post_link( $id ) ) . '"><strong>' . esc_html( strtoupper( $cp->get_code() ) ) . '</strong></a></td>';
		$rows .= '<td>' . esc_html( $amount ) . '</td>';
		$rows .= '<td>' . ( $c['auto'] ? '✔' : '' ) . '</td>';
		$rows .= '<td>' . ( $cp->get_individual_use() ? '✔' : '' ) . '</td>';
		$rows .= '<td>' . esc_html( implode( ', ', smlife_cp_ten_pay( $c['pay'] ) ) ) . '</td>';
		$rows .= '<td>' . esc_html( implode( ', ', $tags ) ) . '</td>';
		$rows .= '<td style="font-size:12px">' . esc_html( implode( ', ', $cats ) ) . '</td>';
		$rows .= '<td>' . (int) $cp->get_usage_count() . ( $cp->get_usage_limit() ? '/' . (int) $cp->get_usage_limit() : '' ) . '</td>';
		$rows .= '<td>' . ( $khop ? '<span style="color:#16a34a">OK</span>' : '<b style="color:#b91c1c">' . esc_html( implode( '; ', $ghi ) ) . '</b>' ) . '</td>';
		$rows .= '<td style="font-size:12px;color:#b45309">' . esc_html( implode( ', ', $khac ) ) . '</td>';
		$rows .= '</tr>';
	}
	echo '<div class="smlife-card"><h2>Đối chiếu ' . count( $ids ) . ' mã đang dùng</h2>';
	echo '<p><b style="color:' . ( $lech ? '#b91c1c' : '#16a34a' ) . '">' . ( count( $ids ) - $lech ) . '/' . count( $ids ) . ' mã khớp với Smart Coupons.</b> Cột "Khớp" báo chỗ khác nhau. Cột cuối liệt kê cài đặt khác của Smart Coupons đang có giá trị trên mã: module không làm các cài đặt này, gửi Claude xem trước khi tắt Smart Coupons.</p>';
	echo '<table class="widefat striped"><thead><tr><th>Mã</th><th>Giảm</th><th>Tự động</th><th>Riêng lẻ</th><th>Thanh toán</th><th>Thẻ sản phẩm</th><th>Danh mục</th><th>Đã dùng</th><th>Khớp</th><th>Cài đặt SC khác (xem lại)</th></tr></thead><tbody>';
	echo $rows; // phpcs:ignore
	echo '</tbody></table></div></div>';
}
