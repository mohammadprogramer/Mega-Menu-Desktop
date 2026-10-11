<?php
namespace RamkaMega;

defined( 'ABSPATH' ) || exit;

final class Categories {
	private const VERSION_KEY = 'ramka_mega_cache_version';

	public static function init(): void {
		foreach ( array( 'created_product_cat', 'edited_product_cat', 'delete_product_cat', 'set_object_terms', 'woocommerce_product_set_stock_status' ) as $hook ) {
			add_action( $hook, array( __CLASS__, 'invalidate' ) );
		}
		add_action( 'updated_term_meta', array( __CLASS__, 'meta_changed' ), 10, 4 );
		add_action( 'added_term_meta', array( __CLASS__, 'meta_changed' ), 10, 4 );
		add_action( 'deleted_term_meta', array( __CLASS__, 'meta_changed' ), 10, 4 );
		add_action( 'clean_term_cache', array( __CLASS__, 'term_cache_changed' ), 10, 3 );
	}

	public static function term_cache_changed( $ids, $taxonomy, $clean_taxonomy ): void {
		if ( 'product_cat' === $taxonomy ) { self::invalidate(); }
	}

	public static function meta_changed( $meta_id, $term_id, $meta_key, $value ): void {
		if ( get_term( (int) $term_id, 'product_cat' ) instanceof \WP_Term ) {
			self::invalidate();
		}
	}

	public static function invalidate(): void {
		update_option( self::VERSION_KEY, wp_generate_uuid4(), false );
	}

	/** One query with primed term meta; tree shape is shared by all widget instances. */
	public static function tree( bool $hide_empty, string $order_by, string $order ): array {
		$version = get_option( self::VERSION_KEY, '1' );
		$key     = 'ramka_mega_' . md5( $version . (int) $hide_empty . $order_by . $order );
		$cached  = get_transient( $key );
		if ( false !== $cached ) {
			return $cached;
		}
		$terms = get_terms( array( 'taxonomy' => 'product_cat', 'hide_empty' => $hide_empty, 'orderby' => 'term_id', 'update_term_meta_cache' => true ) );
		if ( is_wp_error( $terms ) || ! is_array( $terms ) ) {
			return array();
		}
		$nodes = array();
		foreach ( $terms as $term ) {
			$nodes[ $term->term_id ] = array( 'term' => $term, 'children' => array() );
		}
		$roots = array();
		foreach ( $nodes as $id => &$node ) {
			$parent = (int) $node['term']->parent;
			if ( $parent && isset( $nodes[ $parent ] ) ) {
				$nodes[ $parent ]['children'][] = &$node;
			} else {
				$roots[] = &$node;
			}
		}
		unset( $node );
		$sort = static function ( array &$items ) use ( &$sort, $order_by, $order ): void {
			usort( $items, static function ( $a, $b ) use ( $order_by, $order ): int {
				$x = $a['term']; $y = $b['term'];
				switch ( $order_by ) {
					case 'name': $cmp = strnatcasecmp( $x->name, $y->name ); break;
					case 'count': $cmp = $x->count <=> $y->count; break;
					case 'id': $cmp = $x->term_id <=> $y->term_id; break;
					default: $cmp = (int) get_term_meta( $x->term_id, 'ramka_mega_order', true ) <=> (int) get_term_meta( $y->term_id, 'ramka_mega_order', true );
				}
				return ( 'DESC' === $order ? -1 : 1 ) * ( $cmp ?: $x->term_id <=> $y->term_id );
			} );
			foreach ( $items as &$item ) {
				$sort( $item['children'] );
			}
			unset( $item );
		};
		$sort( $roots );
		set_transient( $key, $roots, DAY_IN_SECONDS );
		return $roots;
	}
}
