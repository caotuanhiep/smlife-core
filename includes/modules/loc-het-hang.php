<?php
/**
 * SMLIFE Core - Module: Bo hang het khoi cac khoi goi y
 *
 * Thay cho doan 6.3 trong snippet WPCode "SMLIFE - 11. Danh muc - Trang san
 * pham (PHP)". Tac dung y het: loai san pham het hang khoi upsell, cross-sell
 * va san pham lien quan cua WooCommerce, loc o tang du lieu nen slider khong
 * bi o trong nhu khi an bang CSS.
 *
 * Khac o cach lam. Snippet cu, de biet mot san pham con hang hay khong, tai
 * NGUYEN doi tuong san pham kem toan bo thuoc tinh, tung cai mot. Bo loc lai
 * gan vao ham lay danh sach upsell, ma ham do bi goi nhieu lan trong mot trang,
 * nen vong lap chay lai moi lan goi. Query Monitor do duoc 72 cau truy van.
 *
 * O day chi doc dung mot gia tri _stock_status cho ca danh sach trong mot cau,
 * uu tien lay tu bo nho dem neu san pham da nap san, va nho ket qua trong suot
 * luot tai trang.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function smlife_lhh_loc( $ids ) {
	if ( ! is_array( $ids ) || ! $ids ) {
		return $ids;
	}
	/* Trong trang quan tri thi giu nguyen, chi loc khi hien cho khach */
	if ( is_admin() && ! wp_doing_ajax() ) {
		return $ids;
	}

	static $nho = array();

	$can = array();
	foreach ( $ids as $id ) {
		$i = absint( $id );
		if ( ! $i || isset( $nho[ $i ] ) ) {
			continue;
		}
		/* San pham da nap thuoc tinh san thi doc luon, khong hoi co so du lieu */
		$cache = wp_cache_get( $i, 'post_meta' );
		if ( is_array( $cache ) ) {
			$v          = isset( $cache['_stock_status'][0] ) ? $cache['_stock_status'][0] : 'instock';
			$nho[ $i ] = ( 'outofstock' !== $v );
			continue;
		}
		$can[ $i ] = $i;
	}

	if ( $can ) {
		global $wpdb;
		/* Thieu du lieu thi coi nhu con hang, giong cach WooCommerce mac dinh */
		foreach ( $can as $i ) {
			$nho[ $i ] = true;
		}
		foreach ( array_chunk( array_values( $can ), 500 ) as $lo ) {
			$ph   = implode( ',', array_fill( 0, count( $lo ), '%d' ) );
			$rows = $wpdb->get_results( $wpdb->prepare( // phpcs:ignore
				"SELECT post_id, meta_value FROM {$wpdb->postmeta} WHERE meta_key = '_stock_status' AND post_id IN ($ph)",
				$lo
			) );
			foreach ( (array) $rows as $r ) {
				$nho[ (int) $r->post_id ] = ( 'outofstock' !== $r->meta_value );
			}
		}
	}

	$out = array();
	foreach ( $ids as $id ) {
		$i = absint( $id );
		if ( $i && ! empty( $nho[ $i ] ) ) {
			$out[] = $id;
		}
	}
	return $out;
}

add_filter( 'woocommerce_product_get_upsell_ids', 'smlife_lhh_loc', 20 );
add_filter( 'woocommerce_product_get_cross_sell_ids', 'smlife_lhh_loc', 20 );
add_filter( 'woocommerce_related_products', 'smlife_lhh_loc', 20 );

/* Snippet cu con bat thi go bo loc cham cua no, de khong chay hai lan.
 * Go lai o hai thoi diem vi khong chac snippet duoc chay luc nao. */
function smlife_lhh_go_snippet() {
	foreach ( array( 'woocommerce_product_get_upsell_ids', 'woocommerce_product_get_cross_sell_ids', 'woocommerce_related_products' ) as $h ) {
		remove_filter( $h, 'smlife_bo_san_pham_het_hang', 20 );
	}
}
add_action( 'wp_loaded', 'smlife_lhh_go_snippet', 99 );
add_action( 'wp', 'smlife_lhh_go_snippet', 0 );
