<?php
/** @var array $s */
defined( 'ABSPATH' ) || exit;

$target = 'yes' === ( $s['new_tab'] ?? '' ) ? ' target="_blank" rel="noopener noreferrer"' : '';
$show_image = 'yes' === ( $s['show_image'] ?? '' );
$visual_preset = in_array( $s['visual_preset'] ?? '', array( 'storefront', 'soft', 'minimal' ), true ) ? $s['visual_preset'] : 'storefront';
$arrow_glyph = is_rtl() ? '‹' : '›';
$category_url = static function ( \WP_Term $term ): string {
	$url = get_term_link( $term );
	return is_wp_error( $url ) ? '' : $url;
};
$fallback_icon = preg_match( '/^dashicons dashicons-[a-z0-9-]+$/', (string) ( $s['default_icon'] ?? '' ) ) ? $s['default_icon'] : 'dashicons dashicons-category';
$picture = static function ( \WP_Term $term, string $class = 'ramka-mega__image' ) use ( $show_image, $fallback_icon ): void {
	if ( ! $show_image ) { return; }

	$custom_image = absint( get_term_meta( $term->term_id, 'ramka_mega_image', true ) );
	$thumbnail    = absint( get_term_meta( $term->term_id, 'thumbnail_id', true ) );
	$attachment   = $custom_image ?: $thumbnail;

	if ( $attachment ) {
		echo wp_get_attachment_image( $attachment, 'thumbnail', false, array( 'class' => $class . ' ramka-mega__image--photo', 'loading' => 'lazy', 'alt' => $term->name ) );
		return;
	}

	$icon_key = sanitize_key( (string) get_term_meta( $term->term_id, 'ramka_mega_icon_key', true ) );
	$legacy   = (string) get_term_meta( $term->term_id, 'ramka_mega_icon', true );
	if ( ( ! $icon_key || 'auto' === $icon_key ) && preg_match( '/^dashicons dashicons-[a-z0-9-]+$/', $legacy ) ) {
		echo '<span class="' . esc_attr( $legacy . ' ' . $class . ' ramka-mega__legacy-icon' ) . '" aria-hidden="true"></span>';
		return;
	}

	if ( class_exists( '\\RamkaMega\\Icons' ) ) {
		echo \RamkaMega\Icons::render( \RamkaMega\Icons::for_term( $term ), $class . ' ramka-mega__svg-icon' );
	} else {
		echo '<span class="' . esc_attr( $fallback_icon . ' ' . $class . ' ramka-mega__legacy-icon' ) . '" aria-hidden="true"></span>';
	}
};
$link_text = static function ( \WP_Term $term ): void {
	echo '<span class="ramka-mega__text">' . esc_html( $term->name ) . '</span>';
};

$descendants = static function ( array $nodes, int $level ) use ( &$descendants, $depth, $category_url, $link_text, $picture, $target ): void {
	if ( $level > $depth || ! $nodes ) { return; }
	echo '<ul class="ramka-mega__children">';
	foreach ( $nodes as $node ) {
		$term = $node['term'];
		echo '<li><a class="ramka-mega__child-link" href="' . esc_url( $category_url( $term ) ) . '"' . $target . '><span class="ramka-mega__leaf-media">';
		$picture( $term, 'ramka-mega__leaf-image' );
		echo '</span><span class="ramka-mega__leaf-label">';
		$link_text( $term );
		echo '</span>';
		echo '</a>';
		$descendants( $node['children'], $level + 1 );
		echo '</li>';
	}
	echo '</ul>';
};
$mobile_descendants = static function ( array $nodes, int $level ) use ( &$mobile_descendants, $depth, $category_url, $link_text, $picture, $target ): void {
	if ( $level > $depth ) { return; }
	foreach ( $nodes as $node ) {
		$term = $node['term'];
		if ( $node['children'] && $level < $depth ) {
			echo '<details class="ramka-mega__details"><summary><span class="ramka-mega__leaf-media">';
			$picture( $term, 'ramka-mega__leaf-image' );
			echo '</span><span class="ramka-mega__leaf-label">';
			$link_text( $term );
			echo '</span>';
			echo '</summary><div class="ramka-mega__mobile-children"><a class="ramka-mega__child-link ramka-mega__child-link--all" href="' . esc_url( $category_url( $term ) ) . '"' . $target . '>' . esc_html__( 'View all products', 'ramka-elementor-product-mega-menu' ) . '</a>';
			$mobile_descendants( $node['children'], $level + 1 );
			echo '</div></details>';
		} else {
			echo '<a class="ramka-mega__child-link" href="' . esc_url( $category_url( $term ) ) . '"' . $target . '><span class="ramka-mega__leaf-media">';
			$picture( $term, 'ramka-mega__leaf-image' );
			echo '</span><span class="ramka-mega__leaf-label">';
			$link_text( $term );
			echo '</span>';
			echo '</a>';
		}
	}
};
$active = (int) ( $s['default_category'] ?? 0 );
if ( ! in_array( $active, array_map( static fn( $node ) => $node['term']->term_id, $tree ), true ) ) {
	$active = $tree ? $tree[0]['term']->term_id : 0;
}
$breakpoint = min( 1600, max( 480, (int) ( $s['breakpoint'] ?? 767 ) ) );
$tablet = min( $breakpoint, max( 480, (int) ( $s['tablet_breakpoint'] ?? 768 ) ) );
$mode = in_array( $s['mobile_mode'] ?? '', array( 'drawer', 'fullscreen', 'accordion', 'bottom' ), true ) ? $s['mobile_mode'] : 'accordion';
$width_mode = in_array( $s['width_mode'] ?? '', array( 'viewport', 'container', 'custom' ), true ) ? $s['width_mode'] : 'container';
$height = min( 1200, max( 240, (int) ( $s['max_height'] ?? 680 ) ) );
$duration = min( 1000, max( 0, (int) ( $s['duration'] ?? 180 ) ) );
$animation = in_array( $s['animation'] ?? '', array( 'none', 'fade', 'slide' ), true ) ? $s['animation'] : 'fade';
$header_safe = $is_editor || 'no' !== ( $s['header_safe'] ?? 'yes' );
$desktop_backdrop = false;
$mobile_backdrop = ! $is_editor && ! $header_safe && 'yes' === ( $s['mobile_backdrop'] ?? '' );
$wide_layout = 'no' !== ( $s['wide_layout'] ?? 'yes' );
?>
<div id="<?php echo esc_attr( $id ); ?>" class="ramka-mega" data-visual="<?php echo esc_attr( $visual_preset ); ?>" data-wide-layout="<?php echo esc_attr( $wide_layout ? 'yes' : 'no' ); ?>" data-editor="<?php echo esc_attr( $is_editor ? 'yes' : 'no' ); ?>" dir="<?php echo esc_attr( is_rtl() ? 'rtl' : 'ltr' ); ?>" data-trigger="<?php echo esc_attr( 'hover' === ( $s['trigger'] ?? '' ) ? 'hover' : 'click' ); ?>" data-width="<?php echo esc_attr( $width_mode ); ?>" data-mobile-mode="<?php echo esc_attr( $mode ); ?>" data-breakpoint="<?php echo esc_attr( $breakpoint ); ?>" data-tablet="<?php echo esc_attr( $tablet ); ?>" data-editor-open="<?php echo esc_attr( $is_editor && 'yes' === ( $s['editor_open'] ?? 'no' ) ? 'yes' : 'no' ); ?>" data-animation="<?php echo esc_attr( $animation ); ?>" data-header-safe="<?php echo esc_attr( $header_safe ? 'yes' : 'no' ); ?>" data-desktop-backdrop="<?php echo esc_attr( $desktop_backdrop ? 'yes' : 'no' ); ?>" data-mobile-backdrop="<?php echo esc_attr( $mobile_backdrop ? 'yes' : 'no' ); ?>" style="--ramka-max-height:<?php echo esc_attr( $height ); ?>px;--ramka-duration:<?php echo esc_attr( $duration ); ?>ms">
	<button class="ramka-mega__trigger" type="button" aria-expanded="false" aria-controls="<?php echo esc_attr( $id . '-panel' ); ?>" aria-haspopup="dialog">
		<span class="ramka-mega__trigger-icon" aria-hidden="true"><?php
		if ( ! empty( $s['button_icon']['value'] ) && class_exists( '\\Elementor\\Icons_Manager' ) ) {
			try { \Elementor\Icons_Manager::render_icon( $s['button_icon'], array( 'aria-hidden' => 'true' ) ); } catch ( \Throwable $e ) { echo '<span class="dashicons dashicons-menu"></span>'; }
		} else {
			echo '<span class="dashicons dashicons-menu"></span>';
		}
		?></span>
		<span class="ramka-mega__trigger-label"><?php echo esc_html( $s['label'] ?? '' ); ?></span>
	</button>
	<?php if ( $desktop_backdrop || $mobile_backdrop ) : ?><div class="ramka-mega__overlay" hidden></div><?php endif; ?>
	<div id="<?php echo esc_attr( $id . '-panel' ); ?>" class="ramka-mega__panel" role="dialog" aria-modal="false" aria-hidden="true" aria-label="<?php echo esc_attr( $s['label'] ?? __( 'Product categories', 'ramka-elementor-product-mega-menu' ) ); ?>" hidden>
		<div class="ramka-mega__mobile-head"><button type="button" class="ramka-mega__back" aria-label="<?php echo esc_attr__( 'Back', 'ramka-elementor-product-mega-menu' ); ?>">‹</button><strong><?php echo esc_html( $s['label'] ?? '' ); ?></strong><button type="button" class="ramka-mega__close" aria-label="<?php echo esc_attr__( 'Close menu', 'ramka-elementor-product-mega-menu' ); ?>">×</button></div>
		<?php if ( ! $tree ) : ?>
			<p class="ramka-mega__empty"><?php echo esc_html__( 'No product categories to display.', 'ramka-elementor-product-mega-menu' ); ?></p>
		<?php else : ?>
		<div class="ramka-mega__desktop">
			<div class="ramka-mega__roots" role="tablist" aria-label="<?php echo esc_attr__( 'Main categories', 'ramka-elementor-product-mega-menu' ); ?>">
				<?php foreach ( $tree as $node ) : $term = $node['term']; $selected = $active === $term->term_id; ?>
					<button type="button" class="ramka-mega__root" id="<?php echo esc_attr( $id . '-tab-' . $term->term_id ); ?>" role="tab" aria-selected="<?php echo $selected ? 'true' : 'false'; ?>" aria-controls="<?php echo esc_attr( $id . '-cat-' . $term->term_id ); ?>" tabindex="<?php echo $selected ? '0' : '-1'; ?>">
						<span class="ramka-mega__root-media"><?php $picture( $term ); ?></span>
						<span class="ramka-mega__root-body"><span class="ramka-mega__root-label"><?php $link_text( $term ); ?></span><span class="ramka-mega__root-arrow" aria-hidden="true"><?php echo esc_html( $arrow_glyph ); ?></span></span>
					</button>
				<?php endforeach; ?>
			</div>
			<div class="ramka-mega__panels">
				<?php foreach ( $tree as $node ) : $term = $node['term']; ?>
				<section class="ramka-mega__category" id="<?php echo esc_attr( $id . '-cat-' . $term->term_id ); ?>" role="tabpanel" aria-labelledby="<?php echo esc_attr( $id . '-tab-' . $term->term_id ); ?>" <?php echo $active === $term->term_id ? '' : 'hidden'; ?>>
					<div class="ramka-mega__heading"><div class="ramka-mega__heading-copy"><span class="ramka-mega__eyebrow"><?php echo esc_html__( 'Browse this category', 'ramka-elementor-product-mega-menu' ); ?></span><h3><?php echo esc_html( $term->name ); ?></h3></div><?php if ( 'yes' === ( $s['show_all'] ?? '' ) ) : ?><a class="ramka-mega__all-link" href="<?php echo esc_url( $category_url( $term ) ); ?>"<?php echo $target; ?>><?php echo esc_html__( 'View all products', 'ramka-elementor-product-mega-menu' ); ?> <span aria-hidden="true"><?php echo esc_html( $arrow_glyph ); ?></span></a><?php endif; ?></div>
					<?php if ( $depth > 1 ) : ?><div class="ramka-mega__grid"><?php foreach ( $node['children'] as $child ) : ?><div class="ramka-mega__group"><a class="ramka-mega__group-title" href="<?php echo esc_url( $category_url( $child['term'] ) ); ?>"<?php echo $target; ?>><span class="ramka-mega__group-media"><?php $picture( $child['term'] ); ?></span><span class="ramka-mega__group-label"><?php $link_text( $child['term'] ); ?></span></a><?php $descendants( $child['children'], 3 ); ?></div><?php endforeach; ?></div><?php endif; ?>
					<?php $banner = absint( get_term_meta( $term->term_id, 'ramka_mega_banner', true ) ); if ( $banner && 'yes' === ( $s['show_banner'] ?? '' ) ) : $banner_url = esc_url( get_term_meta( $term->term_id, 'ramka_mega_banner_url', true ) ); ?><div class="ramka-mega__banner"><?php if ( $banner_url ) : ?><a href="<?php echo $banner_url; ?>"<?php echo $target; ?>><?php endif; ?><?php echo wp_get_attachment_image( $banner, 'large', false, array( 'loading' => 'lazy', 'alt' => $term->name ) ); ?><?php if ( $banner_url ) : ?></a><?php endif; ?></div><?php endif; ?>
				</section>
				<?php endforeach; ?>
			</div>
		</div>
		<div class="ramka-mega__mobile">
			<?php foreach ( $tree as $node ) : $term = $node['term']; ?>
			<details class="ramka-mega__details"><summary><span class="ramka-mega__root-media"><?php $picture( $term ); ?></span><span class="ramka-mega__root-label"><?php $link_text( $term ); ?></span></summary><div class="ramka-mega__mobile-children"><a class="ramka-mega__child-link ramka-mega__child-link--all" href="<?php echo esc_url( $category_url( $term ) ); ?>"<?php echo $target; ?>><?php echo esc_html__( 'View all products', 'ramka-elementor-product-mega-menu' ); ?></a><?php if ( $depth > 1 ) { $mobile_descendants( $node['children'], 2 ); } ?></div></details>
			<?php endforeach; ?>
		</div>
		<?php endif; ?>
	</div>
</div>
