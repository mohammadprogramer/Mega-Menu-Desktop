<?php
namespace RamkaMega;

defined( 'ABSPATH' ) || exit;

final class Icons {
	/**
	 * Curated internal SVG icon set. No external font/CDN is required.
	 */
	public static function choices(): array {
		return array(
			'auto'         => __( 'Automatic (recommended)', 'ramka-elementor-product-mega-menu' ),
			'category'     => __( 'General category', 'ramka-elementor-product-mega-menu' ),
			'mobile'       => __( 'Mobile / phone', 'ramka-elementor-product-mega-menu' ),
			'laptop'       => __( 'Laptop / computer', 'ramka-elementor-product-mega-menu' ),
			'tv'           => __( 'TV / display', 'ramka-elementor-product-mega-menu' ),
			'fridge'       => __( 'Refrigerator', 'ramka-elementor-product-mega-menu' ),
			'washer'       => __( 'Washing machine', 'ramka-elementor-product-mega-menu' ),
			'kitchen'      => __( 'Kitchen', 'ramka-elementor-product-mega-menu' ),
			'vacuum'       => __( 'Vacuum / cleaning', 'ramka-elementor-product-mega-menu' ),
			'air'          => __( 'Cooling / air conditioner', 'ramka-elementor-product-mega-menu' ),
			'home'         => __( 'Home', 'ramka-elementor-product-mega-menu' ),
			'bath'         => __( 'Bathroom', 'ramka-elementor-product-mega-menu' ),
			'light'        => __( 'Lighting', 'ramka-elementor-product-mega-menu' ),
			'tools'        => __( 'Tools', 'ramka-elementor-product-mega-menu' ),
			'audio'        => __( 'Audio / headphones', 'ramka-elementor-product-mega-menu' ),
			'gaming'       => __( 'Gaming', 'ramka-elementor-product-mega-menu' ),
			'camera'       => __( 'Camera', 'ramka-elementor-product-mega-menu' ),
			'watch'        => __( 'Watch', 'ramka-elementor-product-mega-menu' ),
		);
	}

	public static function valid( string $key ): bool {
		return isset( self::choices()[ $key ] );
	}

	public static function for_term( \WP_Term $term ): string {
		$selected = sanitize_key( (string) get_term_meta( $term->term_id, 'ramka_mega_icon_key', true ) );
		if ( $selected && 'auto' !== $selected && self::valid( $selected ) ) {
			return $selected;
		}

		$text = trim( $term->slug . ' ' . $term->name );
		if ( function_exists( 'mb_strtolower' ) ) {
			$text = mb_strtolower( $text, 'UTF-8' );
		} else {
			$text = strtolower( $text );
		}

		$rules = array(
			'mobile'  => array( 'mobile', 'phone', 'smartphone', 'موبایل', 'گوشی', 'تلفن' ),
			'laptop'  => array( 'laptop', 'notebook', 'computer', 'pc', 'لپ تاپ', 'لپ‌تاپ', 'کامپیوتر', 'رایانه' ),
			'tv'      => array( 'television', 'display', 'monitor', 'tv', 'تلویزیون', 'مانیتور', 'نمایشگر' ),
			'fridge'  => array( 'fridge', 'refrigerator', 'freezer', 'یخچال', 'فریزر' ),
			'washer'  => array( 'washer', 'washing', 'laundry', 'لباسشویی', 'ماشین لباسشویی', 'خشک کن', 'خشک‌کن' ),
			'vacuum'  => array( 'vacuum', 'cleaner', 'جارو', 'نظافت', 'شستشو' ),
			'air'     => array( 'air-condition', 'conditioner', 'cooler', 'ac', 'کولر', 'تهویه', 'سرمایش', 'گرمایش' ),
			'kitchen' => array( 'kitchen', 'cook', 'oven', 'microwave', 'dishwasher', 'آشپزخانه', 'پخت', 'فر', 'مایکروویو', 'ظرفشویی' ),
			'bath'    => array( 'bath', 'toilet', 'sanitary', 'حمام', 'سرویس', 'توالت', 'بهداشتی', 'روشویی' ),
			'light'   => array( 'light', 'lamp', 'lighting', 'چراغ', 'روشنایی', 'لامپ' ),
			'tools'   => array( 'tool', 'drill', 'hardware', 'ابزار', 'دریل', 'یراق' ),
			'audio'   => array( 'audio', 'speaker', 'headphone', 'earbud', 'صوت', 'اسپیکر', 'هدفون', 'هندزفری' ),
			'gaming'  => array( 'game', 'gaming', 'console', 'playstation', 'xbox', 'بازی', 'کنسول', 'پلی استیشن', 'پلی‌استیشن' ),
			'camera'  => array( 'camera', 'photo', 'دوربین', 'عکاسی' ),
			'watch'   => array( 'watch', 'wearable', 'ساعت', 'پوشیدنی' ),
			'home'    => array( 'home', 'furniture', 'decor', 'خانه', 'مبلمان', 'دکور' ),
		);
		foreach ( $rules as $key => $needles ) {
			foreach ( $needles as $needle ) {
				if ( false !== strpos( $text, $needle ) ) {
					return $key;
				}
			}
		}
		return 'category';
	}

	public static function render( string $key, string $class = 'ramka-mega__svg-icon' ): string {
		if ( ! self::valid( $key ) || 'auto' === $key ) {
			$key = 'category';
		}
		$paths = self::paths();
		$body  = $paths[ $key ] ?? $paths['category'];
		return '<svg class="' . esc_attr( $class ) . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">' . $body . '</svg>';
	}

	private static function paths(): array {
		return array(
			'category' => '<rect x="3" y="3" width="7" height="7" rx="2"/><rect x="14" y="3" width="7" height="7" rx="2"/><rect x="3" y="14" width="7" height="7" rx="2"/><rect x="14" y="14" width="7" height="7" rx="2"/>',
			'mobile'   => '<rect x="7" y="2" width="10" height="20" rx="2.5"/><path d="M10 5h4M11 18h2"/>',
			'laptop'   => '<rect x="4" y="4" width="16" height="11" rx="2"/><path d="M2.5 19h19M8 19l1-2h6l1 2"/>',
			'tv'       => '<rect x="3" y="5" width="18" height="12" rx="2"/><path d="M8 21h8M12 17v4M9 2l3 3 3-3"/>',
			'fridge'   => '<rect x="7" y="2" width="10" height="20" rx="2"/><path d="M7 9h10M14 5v2M14 12v3"/>',
			'washer'   => '<rect x="4" y="2" width="16" height="20" rx="2"/><circle cx="12" cy="13" r="5"/><path d="M7 6h.01M10 6h2"/>',
			'kitchen'  => '<path d="M6 2v8M9 2v8M6 6h3M7.5 10v12M16 2c2 2 2 6 0 8v12"/>',
			'vacuum'   => '<path d="M8 4h4v7a4 4 0 1 1-4-4h4M15 5c3 1 4 4 4 7v5"/><circle cx="19" cy="19" r="2"/>',
			'air'      => '<rect x="3" y="4" width="18" height="7" rx="2"/><path d="M7 8h10M8 15c0 2 2 3 4 3M16 14c0 3-2 5-5 6"/>',
			'home'     => '<path d="M3 11.5 12 4l9 7.5"/><path d="M5.5 10.5V21h13V10.5M9 21v-6h6v6"/>',
			'bath'     => '<path d="M4 13h16v2a5 5 0 0 1-5 5H9a5 5 0 0 1-5-5v-2Z"/><path d="M7 13V6a3 3 0 0 1 6 0v1M3 13h18M8 20v2M16 20v2"/>',
			'light'    => '<path d="M9 18h6M10 22h4M8.5 14.5A7 7 0 1 1 15.5 14.5C14.5 15.3 14 16 14 18h-4c0-2-.5-2.7-1.5-3.5Z"/>',
			'tools'    => '<path d="m14.5 6.5 3-3a5 5 0 0 1-6.2 6.2L5 16l3 3 6.3-6.3a5 5 0 0 1 6.2-6.2l-3 3-3-3Z"/><path d="m4 17-1 4 4-1"/>',
			'audio'    => '<path d="M4 14v-4a8 8 0 0 1 16 0v4"/><rect x="3" y="13" width="4" height="7" rx="2"/><rect x="17" y="13" width="4" height="7" rx="2"/>',
			'gaming'   => '<path d="M8 7h8a5 5 0 0 1 4.7 6.7l-1.2 3.6a2 2 0 0 1-3.2.9L14 16h-4l-2.3 2.2a2 2 0 0 1-3.2-.9l-1.2-3.6A5 5 0 0 1 8 7Z"/><path d="M7 12h4M9 10v4M16 11h.01M18 13h.01"/>',
			'camera'   => '<path d="M5 7h3l1.5-2h5L16 7h3a2 2 0 0 1 2 2v9H3V9a2 2 0 0 1 2-2Z"/><circle cx="12" cy="13" r="4"/>',
			'watch'    => '<rect x="7" y="6" width="10" height="12" rx="3"/><path d="M9 6l1-4h4l1 4M9 18l1 4h4l1-4M12 9v4l2 1"/>',
		);
	}
}
