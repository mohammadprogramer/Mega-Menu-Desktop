<?php
namespace RamkaMega;

defined( 'ABSPATH' ) || exit;

final class Term_Fields {
	private const FIELDS = array( 'icon_key', 'image', 'banner', 'banner_url', 'order' );

	public static function init(): void {
		add_action( 'product_cat_add_form_fields', array( __CLASS__, 'add_fields' ) );
		add_action( 'product_cat_edit_form_fields', array( __CLASS__, 'edit_fields' ) );
		add_action( 'created_product_cat', array( __CLASS__, 'save' ) );
		add_action( 'edited_product_cat', array( __CLASS__, 'save' ) );
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'admin_assets' ) );
	}

	public static function admin_assets( string $hook ): void {
		if ( ! in_array( $hook, array( 'edit-tags.php', 'term.php' ), true ) ) {
			return;
		}

		$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
		if ( ! $screen || 'product_cat' !== (string) $screen->taxonomy || 'product' !== (string) $screen->post_type ) {
			return;
		}

		wp_enqueue_media();
		wp_enqueue_style( 'ramka-mega-admin', RAMKA_MEGA_URL . 'assets/css/admin.css', array(), RAMKA_MEGA_VERSION );
		wp_enqueue_script( 'ramka-mega-admin', RAMKA_MEGA_URL . 'assets/js/admin.js', array(), RAMKA_MEGA_VERSION, true );
	}

	public static function add_fields(): void {
		wp_nonce_field( 'ramka_mega_save_term', 'ramka_mega_term_nonce' );
		foreach ( self::FIELDS as $field ) {
			echo '<div class="form-field ramka-mega-term-field ramka-mega-term-field--' . esc_attr( $field ) . '">';
			self::field( $field, '' );
			echo '</div>';
		}
	}

	public static function edit_fields( \WP_Term $term ): void {
		echo '<tr class="form-field"><th></th><td>';
		wp_nonce_field( 'ramka_mega_save_term', 'ramka_mega_term_nonce' );
		echo '</td></tr>';
		foreach ( self::FIELDS as $field ) {
			echo '<tr class="form-field ramka-mega-term-field ramka-mega-term-field--' . esc_attr( $field ) . '"><th scope="row"><label for="ramka_mega_' . esc_attr( $field ) . '">' . esc_html( self::label( $field ) ) . '</label></th><td>';
			self::field( $field, get_term_meta( $term->term_id, 'ramka_mega_' . $field, true ), false );
			echo '</td></tr>';
		}
	}

	private static function label( string $field ): string {
		$labels = array(
			'icon_key'   => __( 'Category icon in mega menu', 'ramka-elementor-product-mega-menu' ),
			'image'      => __( 'Custom category logo / image', 'ramka-elementor-product-mega-menu' ),
			'banner'     => __( 'Banner image', 'ramka-elementor-product-mega-menu' ),
			'banner_url' => __( 'Banner URL', 'ramka-elementor-product-mega-menu' ),
			'order'      => __( 'Custom order', 'ramka-elementor-product-mega-menu' ),
		);
		return $labels[ $field ];
	}

	private static function field( string $field, $value, bool $label = true ): void {
		$id = 'ramka_mega_' . $field;
		if ( $label ) {
			echo '<label for="' . esc_attr( $id ) . '">' . esc_html( self::label( $field ) ) . '</label>';
		}

		if ( 'icon_key' === $field ) {
			$current = sanitize_key( (string) $value );
			if ( ! Icons::valid( $current ) ) {
				$current = 'auto';
			}

			echo '<div class="ramka-mega-icon-picker" role="radiogroup" aria-label="' . esc_attr( self::label( $field ) ) . '">';
			foreach ( Icons::choices() as $key => $option_label ) {
				$input_id = $id . '_' . $key;
				$preview_key = 'auto' === $key ? 'category' : $key;
				echo '<span class="ramka-mega-icon-choice">';
				echo '<input type="radio" id="' . esc_attr( $input_id ) . '" name="' . esc_attr( $id ) . '" value="' . esc_attr( $key ) . '"' . checked( $current, $key, false ) . ' />';
				echo '<label class="ramka-mega-icon-option" for="' . esc_attr( $input_id ) . '">';
				echo '<span class="ramka-mega-icon-option__svg">' . Icons::render( $preview_key, 'ramka-mega-admin-svg' ) . '</span>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				echo '<span class="ramka-mega-icon-option__label">' . esc_html( $option_label ) . '</span>';
				echo '</label></span>';
			}
			echo '</div>';
			echo '<p class="description">' . esc_html__( 'Choose a unique icon for this product category. If a custom category logo/image is selected below, that image has priority in the mega menu.', 'ramka-elementor-product-mega-menu' ) . '</p>';
			return;
		}

		if ( in_array( $field, array( 'image', 'banner' ), true ) ) {
			$attachment_id = absint( $value );
			$preview       = $attachment_id ? wp_get_attachment_image_url( $attachment_id, 'thumbnail' ) : '';
			echo '<input type="hidden" id="' . esc_attr( $id ) . '" name="' . esc_attr( $id ) . '" value="' . esc_attr( (string) $attachment_id ) . '" />';
			echo '<div class="ramka-mega-media-preview" data-preview-for="' . esc_attr( $id ) . '">';
			if ( $preview ) {
				echo '<img src="' . esc_url( $preview ) . '" alt="" width="88" height="88" />';
			}
			echo '</div>';
			echo '<div class="ramka-mega-media-actions"><button type="button" class="button ramka-mega-media" data-target="' . esc_attr( $id ) . '">' . esc_html__( 'Choose image', 'ramka-elementor-product-mega-menu' ) . '</button> ';
			echo '<button type="button" class="button ramka-mega-media-remove" data-target="' . esc_attr( $id ) . '">' . esc_html__( 'Remove image', 'ramka-elementor-product-mega-menu' ) . '</button></div>';
			if ( 'image' === $field ) {
				echo '<p class="description">' . esc_html__( 'Recommended: a square PNG, WebP or SVG-style logo. This image has first priority for this category in the mega menu.', 'ramka-elementor-product-mega-menu' ) . '</p>';
			}
			return;
		}

		$type = 'order' === $field ? 'number' : ( 'banner_url' === $field ? 'url' : 'text' );
		echo '<input type="' . esc_attr( $type ) . '" id="' . esc_attr( $id ) . '" name="' . esc_attr( $id ) . '" value="' . esc_attr( (string) $value ) . '" />';
	}

	public static function save( int $term_id ): void {
		if ( ! current_user_can( 'manage_product_terms' ) || ! isset( $_POST['ramka_mega_term_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['ramka_mega_term_nonce'] ) ), 'ramka_mega_save_term' ) ) {
			return;
		}

		foreach ( self::FIELDS as $field ) {
			$key = 'ramka_mega_' . $field;
			if ( ! isset( $_POST[ $key ] ) || ! is_scalar( $_POST[ $key ] ) ) {
				continue;
			}
			$raw = wp_unslash( $_POST[ $key ] );
			switch ( $field ) {
				case 'image':
				case 'banner':
					$value = absint( $raw );
					break;
				case 'icon_key':
					$clean = sanitize_key( $raw );
					$value = Icons::valid( $clean ) ? $clean : 'auto';
					break;
				case 'order':
					$value = (int) $raw;
					break;
				case 'banner_url':
					$value = esc_url_raw( $raw );
					break;
				default:
					$value = '';
			}
			update_term_meta( $term_id, $key, $value );
		}
	}
}
