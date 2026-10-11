<?php
namespace RamkaMega;

defined( 'ABSPATH' ) || exit;

class Widget extends \Elementor\Widget_Base {
	public function get_name(): string { return 'ramka_product_mega_menu'; }
	public function get_title(): string { return __( 'Ramka Product Mega Menu', 'ramka-elementor-product-mega-menu' ); }
	public function get_icon(): string { return 'eicon-menu-bar'; }

	/**
	 * Keep the branded category, but also expose the widget through Elementor's
	 * built-in General category. Elementor 4.x has multiple panel/category
	 * implementations and this fallback prevents a custom-category visibility
	 * issue from making an otherwise registered widget impossible to discover.
	 */
	public function get_categories(): array { return array( 'ramka-elements', 'general' ); }

	/** Explicit panel visibility for Elementor 4.x and later. */
	public function show_in_panel(): bool { return true; }

	/** Keep the widget discoverable by the Elementor panel search. */
	public function hide_on_search(): bool { return false; }

	/** Keep frontend output uncached because category data can change. */
	protected function is_dynamic_content(): bool { return true; }


	public function get_keywords(): array { return array( 'ramka', 'ramka product mega menu', 'mega', 'mega menu', 'menu', 'woocommerce', 'category', 'categories', 'product', 'product category', 'منو', 'مگا منو', 'مگا منوی محصولات رامکا', 'دسته‌بندی', 'دسته بندی', 'محصولات', 'ووکامرس', 'رامکا' ); }

	/**
	 * Elementor's editor shell and preview must stay isolated from the frontend
	 * menu runtime. The full CSS/JS dependencies are only needed on the real
	 * frontend; editor requests get a lightweight PHP-only placeholder.
	 */
	private static function is_editor_request(): bool {
		if ( is_admin() && isset( $_GET['action'] ) && 'elementor' === sanitize_key( wp_unslash( $_GET['action'] ) ) ) {
			return true;
		}
		if ( isset( $_GET['elementor-preview'] ) ) {
			return true;
		}
		if ( class_exists( '\\Elementor\\Plugin' ) && ! empty( \Elementor\Plugin::$instance ) && isset( \Elementor\Plugin::$instance->editor ) ) {
			try {
				return (bool) \Elementor\Plugin::$instance->editor->is_edit_mode();
			} catch ( \Throwable $e ) {
				return false;
			}
		}
		return false;
	}

	public function get_style_depends(): array {
		return array( 'ramka-mega-menu', 'dashicons' );
	}

	public function get_script_depends(): array {
		return array( 'ramka-mega-menu' );
	}

	private function term_options(): array {
		$terms = get_terms( array( 'taxonomy' => 'product_cat', 'hide_empty' => false, 'fields' => 'id=>name' ) );
		return is_wp_error( $terms ) ? array() : $terms;
	}

	protected function register_controls(): void {
		$c = '\\Elementor\\Controls_Manager';
		$this->start_controls_section( 'ramka_content', array( 'label' => __( 'Menu content', 'ramka-elementor-product-mega-menu' ) ) );
		$this->add_control( 'label', array( 'label' => __( 'Button label', 'ramka-elementor-product-mega-menu' ), 'type' => $c::TEXT, 'default' => __( 'Product categories', 'ramka-elementor-product-mega-menu' ) ) );
		$this->add_control( 'button_icon', array( 'label' => __( 'Button icon', 'ramka-elementor-product-mega-menu' ), 'type' => $c::ICONS, 'default' => array( 'value' => 'fas fa-bars', 'library' => 'fa-solid' ) ) );
		$this->add_control( 'default_icon', array( 'label' => __( 'Fallback category icon (Dashicons class)', 'ramka-elementor-product-mega-menu' ), 'type' => $c::TEXT, 'default' => 'dashicons dashicons-category' ) );
		$this->add_control( 'trigger', array( 'label' => __( 'Desktop opening', 'ramka-elementor-product-mega-menu' ), 'type' => $c::SELECT, 'options' => array( 'click' => __( 'Click', 'ramka-elementor-product-mega-menu' ), 'hover' => __( 'Hover', 'ramka-elementor-product-mega-menu' ) ), 'default' => 'click' ) );
		$this->add_control( 'source', array( 'label' => __( 'Main categories', 'ramka-elementor-product-mega-menu' ), 'type' => $c::SELECT, 'options' => array( 'all' => __( 'All roots', 'ramka-elementor-product-mega-menu' ), 'selected' => __( 'Selected roots', 'ramka-elementor-product-mega-menu' ) ), 'default' => 'all' ) );
		$this->add_control( 'selected', array( 'label' => __( 'Include categories', 'ramka-elementor-product-mega-menu' ), 'type' => $c::SELECT2, 'multiple' => true, 'options' => $this->term_options(), 'condition' => array( 'source' => 'selected' ) ) );
		$this->add_control( 'excluded', array( 'label' => __( 'Exclude categories', 'ramka-elementor-product-mega-menu' ), 'type' => $c::SELECT2, 'multiple' => true, 'options' => $this->term_options() ) );
		$this->add_control( 'hide_empty', array( 'label' => __( 'Show empty categories', 'ramka-elementor-product-mega-menu' ), 'type' => $c::SWITCHER, 'return_value' => 'yes', 'default' => 'yes' ) );
		$this->add_control( 'sort', array( 'label' => __( 'Sort by', 'ramka-elementor-product-mega-menu' ), 'type' => $c::SELECT, 'options' => array( 'custom' => __( 'Custom order', 'ramka-elementor-product-mega-menu' ), 'name' => __( 'Name', 'ramka-elementor-product-mega-menu' ), 'id' => __( 'ID', 'ramka-elementor-product-mega-menu' ), 'count' => __( 'Product count', 'ramka-elementor-product-mega-menu' ) ), 'default' => 'custom' ) );
		$this->add_control( 'order', array( 'label' => __( 'Direction', 'ramka-elementor-product-mega-menu' ), 'type' => $c::SELECT, 'options' => array( 'ASC' => __( 'Ascending', 'ramka-elementor-product-mega-menu' ), 'DESC' => __( 'Descending', 'ramka-elementor-product-mega-menu' ) ), 'default' => 'ASC' ) );
		$this->add_control( 'depth', array( 'label' => __( 'Maximum depth', 'ramka-elementor-product-mega-menu' ), 'type' => $c::NUMBER, 'min' => 1, 'max' => 8, 'default' => 3 ) );
		$this->add_control( 'limit', array( 'label' => __( 'Maximum roots (0 = all)', 'ramka-elementor-product-mega-menu' ), 'type' => $c::NUMBER, 'min' => 0, 'max' => 500, 'default' => 0 ) );
		$this->add_control( 'default_category', array( 'label' => __( 'Initially active category', 'ramka-elementor-product-mega-menu' ), 'type' => $c::SELECT, 'options' => array( '' => __( 'First category', 'ramka-elementor-product-mega-menu' ) ) + $this->term_options() ) );
		foreach ( array( 'show_image' => __( 'Show category visual', 'ramka-elementor-product-mega-menu' ), 'show_all' => __( 'Show view all link', 'ramka-elementor-product-mega-menu' ), 'show_banner' => __( 'Show category banner', 'ramka-elementor-product-mega-menu' ) ) as $key => $label ) {
			$this->add_control( $key, array( 'label' => $label, 'type' => $c::SWITCHER, 'return_value' => 'yes', 'default' => 'yes' ) );
		}
		$this->add_control( 'header_safe', array( 'label' => __( 'Header Safe Mode', 'ramka-elementor-product-mega-menu' ), 'description' => __( 'Recommended for Elementor headers. Prevents overlays, page scroll locking and full-screen layers from affecting the rest of the page.', 'ramka-elementor-product-mega-menu' ), 'type' => $c::SWITCHER, 'return_value' => 'yes', 'default' => 'yes' ) );
		$this->add_control( 'mobile_backdrop', array( 'label' => __( 'Darken page behind mobile menu', 'ramka-elementor-product-mega-menu' ), 'type' => $c::SWITCHER, 'return_value' => 'yes', 'default' => '', 'condition' => array( 'header_safe!' => 'yes' ) ) );
		$this->add_control( 'new_tab', array( 'label' => __( 'Open links in a new tab', 'ramka-elementor-product-mega-menu' ), 'type' => $c::SWITCHER, 'return_value' => 'yes' ) );
		$this->add_control( 'editor_open', array( 'label' => __( 'Keep open in Elementor editor', 'ramka-elementor-product-mega-menu' ), 'description' => __( 'Shows the real menu inside Elementor using a safe inline preview. No overlay or page scroll locking is used in the editor.', 'ramka-elementor-product-mega-menu' ), 'type' => $c::SWITCHER, 'return_value' => 'yes', 'default' => 'no' ) );
		$this->end_controls_section();

		$this->start_controls_section( 'ramka_layout', array( 'label' => __( 'Layout and responsive', 'ramka-elementor-product-mega-menu' ) ) );
		$this->add_control( 'width_mode', array( 'label' => __( 'Desktop width', 'ramka-elementor-product-mega-menu' ), 'type' => $c::SELECT, 'options' => array( 'viewport' => __( 'Viewport', 'ramka-elementor-product-mega-menu' ), 'container' => __( 'Elementor container', 'ramka-elementor-product-mega-menu' ), 'custom' => __( 'Custom', 'ramka-elementor-product-mega-menu' ) ), 'default' => 'container' ) );
		$this->add_responsive_control( 'custom_width', array( 'label' => __( 'Custom width', 'ramka-elementor-product-mega-menu' ), 'type' => $c::SLIDER, 'size_units' => array( 'px', 'vw' ), 'range' => array( 'px' => array( 'min' => 300, 'max' => 1920 ) ), 'selectors' => array( '{{WRAPPER}} .ramka-mega' => '--ramka-width: {{SIZE}}{{UNIT}};' ), 'condition' => array( 'width_mode' => 'custom' ) ) );
		$this->add_control( 'max_height', array( 'label' => __( 'Maximum menu height (px)', 'ramka-elementor-product-mega-menu' ), 'type' => $c::NUMBER, 'min' => 240, 'max' => 1200, 'default' => 660 ) );
		$this->add_control( 'breakpoint', array( 'label' => __( 'Mobile menu breakpoint (px)', 'ramka-elementor-product-mega-menu' ), 'type' => $c::NUMBER, 'min' => 480, 'max' => 1600, 'default' => 767 ) );
		$this->add_control( 'tablet_breakpoint', array( 'label' => __( 'Tablet columns breakpoint (px)', 'ramka-elementor-product-mega-menu' ), 'type' => $c::NUMBER, 'min' => 480, 'max' => 1600, 'default' => 768 ) );
		$this->add_control( 'mobile_mode', array( 'label' => __( 'Mobile layout', 'ramka-elementor-product-mega-menu' ), 'type' => $c::SELECT, 'options' => array( 'drawer' => __( 'Right drawer', 'ramka-elementor-product-mega-menu' ), 'fullscreen' => __( 'Full screen', 'ramka-elementor-product-mega-menu' ), 'accordion' => __( 'Accordion below button', 'ramka-elementor-product-mega-menu' ), 'bottom' => __( 'Bottom sheet', 'ramka-elementor-product-mega-menu' ) ), 'default' => 'accordion' ) );
		$this->add_control( 'visual_preset', array( 'label' => __( 'Visual preset', 'ramka-elementor-product-mega-menu' ), 'type' => $c::SELECT, 'options' => array( 'storefront' => __( 'Marketplace mega menu', 'ramka-elementor-product-mega-menu' ), 'soft' => __( 'Soft cards', 'ramka-elementor-product-mega-menu' ), 'minimal' => __( 'Minimal', 'ramka-elementor-product-mega-menu' ) ), 'default' => 'storefront' ) );
		$this->add_control( 'wide_layout', array( 'label' => __( 'Wide desktop mega menu', 'ramka-elementor-product-mega-menu' ), 'description' => __( 'Recommended for store headers. Expands the menu beyond a narrow header container and centers it inside the viewport.', 'ramka-elementor-product-mega-menu' ), 'type' => $c::SWITCHER, 'return_value' => 'yes', 'default' => 'yes' ) );
		$this->add_responsive_control( 'storefront_width', array( 'label' => __( 'Wide menu width', 'ramka-elementor-product-mega-menu' ), 'type' => $c::SLIDER, 'size_units' => array( 'px', 'vw' ), 'range' => array( 'px' => array( 'min' => 900, 'max' => 1600 ), 'vw' => array( 'min' => 70, 'max' => 98 ) ), 'default' => array( 'size' => 1280, 'unit' => 'px' ), 'selectors' => array( '{{WRAPPER}} .ramka-mega' => '--ramka-wide-width: {{SIZE}}{{UNIT}};' ), 'condition' => array( 'wide_layout' => 'yes' ) ) );
		$this->add_responsive_control( 'columns', array( 'label' => __( 'Subcategory columns', 'ramka-elementor-product-mega-menu' ), 'type' => $c::NUMBER, 'min' => 1, 'max' => 7, 'default' => 4, 'tablet_default' => 2, 'mobile_default' => 1, 'selectors' => array( '{{WRAPPER}} .ramka-mega' => '--ramka-cols: {{VALUE}};' ) ) );
		$this->add_control( 'animation', array( 'label' => __( 'Animation', 'ramka-elementor-product-mega-menu' ), 'type' => $c::SELECT, 'options' => array( 'fade' => __( 'Fade', 'ramka-elementor-product-mega-menu' ), 'slide' => __( 'Slide', 'ramka-elementor-product-mega-menu' ), 'none' => __( 'None', 'ramka-elementor-product-mega-menu' ) ), 'default' => 'fade' ) );
		$this->add_control( 'duration', array( 'label' => __( 'Animation duration (ms)', 'ramka-elementor-product-mega-menu' ), 'type' => $c::NUMBER, 'min' => 0, 'max' => 1000, 'default' => 180 ) );
		$this->end_controls_section();

		$this->style_controls();
	}

	private function style_controls(): void {
		$c = '\\Elementor\\Controls_Manager';
		$typo = '\\Elementor\\Group_Control_Typography';
		$border = '\\Elementor\\Group_Control_Border';
		$shadow = '\\Elementor\\Group_Control_Box_Shadow';
		$background = '\\Elementor\\Group_Control_Background';
		$this->start_controls_section( 'style_button', array( 'label' => __( 'Button', 'ramka-elementor-product-mega-menu' ), 'tab' => $c::TAB_STYLE ) );
		$this->add_group_control( $typo::get_type(), array( 'name' => 'button_typo', 'selector' => '{{WRAPPER}} .ramka-mega__trigger' ) );
		$this->add_control( 'button_color', array( 'label' => __( 'Text color', 'ramka-elementor-product-mega-menu' ), 'type' => $c::COLOR, 'selectors' => array( '{{WRAPPER}} .ramka-mega__trigger' => 'color: {{VALUE}}' ) ) );
		$this->add_control( 'button_bg', array( 'label' => __( 'Background', 'ramka-elementor-product-mega-menu' ), 'type' => $c::COLOR, 'selectors' => array( '{{WRAPPER}} .ramka-mega__trigger' => 'background-color: {{VALUE}}' ) ) );
		$this->add_control( 'button_hover', array( 'label' => __( 'Hover text', 'ramka-elementor-product-mega-menu' ), 'type' => $c::COLOR, 'selectors' => array( '{{WRAPPER}} .ramka-mega__trigger:hover' => 'color: {{VALUE}}' ) ) );
		$this->add_control( 'button_hover_bg', array( 'label' => __( 'Hover background', 'ramka-elementor-product-mega-menu' ), 'type' => $c::COLOR, 'selectors' => array( '{{WRAPPER}} .ramka-mega__trigger:hover' => 'background-color: {{VALUE}}' ) ) );
		$this->add_control( 'button_icon_color', array( 'label' => __( 'Icon color', 'ramka-elementor-product-mega-menu' ), 'type' => $c::COLOR, 'selectors' => array( '{{WRAPPER}} .ramka-mega__trigger-icon' => 'color: {{VALUE}}' ) ) );
		$this->add_responsive_control( 'button_icon_size', array( 'label' => __( 'Icon size', 'ramka-elementor-product-mega-menu' ), 'type' => $c::SLIDER, 'selectors' => array( '{{WRAPPER}} .ramka-mega__trigger-icon' => 'font-size: {{SIZE}}{{UNIT}}' ) ) );
		$this->add_responsive_control( 'button_gap', array( 'label' => __( 'Icon gap', 'ramka-elementor-product-mega-menu' ), 'type' => $c::SLIDER, 'selectors' => array( '{{WRAPPER}} .ramka-mega__trigger' => 'gap: {{SIZE}}{{UNIT}}' ) ) );
		$this->add_responsive_control( 'button_padding', array( 'label' => __( 'Padding', 'ramka-elementor-product-mega-menu' ), 'type' => $c::DIMENSIONS, 'size_units' => array( 'px', 'em' ), 'selectors' => array( '{{WRAPPER}} .ramka-mega__trigger' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}}' ) ) );
		$this->add_group_control( $border::get_type(), array( 'name' => 'button_border', 'selector' => '{{WRAPPER}} .ramka-mega__trigger' ) );
		$this->add_control( 'button_radius', array( 'label' => __( 'Radius', 'ramka-elementor-product-mega-menu' ), 'type' => $c::DIMENSIONS, 'selectors' => array( '{{WRAPPER}} .ramka-mega__trigger' => 'border-radius: {{TOP}}px {{RIGHT}}px {{BOTTOM}}px {{LEFT}}px' ) ) );
		$this->add_group_control( $shadow::get_type(), array( 'name' => 'button_shadow', 'selector' => '{{WRAPPER}} .ramka-mega__trigger' ) );
		$this->add_responsive_control( 'button_width', array( 'label' => __( 'Width', 'ramka-elementor-product-mega-menu' ), 'type' => $c::SLIDER, 'size_units' => array( 'px', '%' ), 'selectors' => array( '{{WRAPPER}} .ramka-mega__trigger' => 'width: {{SIZE}}{{UNIT}}' ) ) );
		$this->add_responsive_control( 'button_height', array( 'label' => __( 'Height', 'ramka-elementor-product-mega-menu' ), 'type' => $c::SLIDER, 'selectors' => array( '{{WRAPPER}} .ramka-mega__trigger' => 'min-height: {{SIZE}}{{UNIT}}' ) ) );
		$this->end_controls_section();

		$this->start_controls_section( 'style_panel', array( 'label' => __( 'Main panel', 'ramka-elementor-product-mega-menu' ), 'tab' => $c::TAB_STYLE ) );
		$this->add_group_control( $background::get_type(), array( 'name' => 'panel_background', 'types' => array( 'classic' ), 'selector' => '{{WRAPPER}} .ramka-mega__panel' ) );
		$this->add_responsive_control( 'panel_padding', array( 'label' => __( 'Padding', 'ramka-elementor-product-mega-menu' ), 'type' => $c::DIMENSIONS, 'selectors' => array( '{{WRAPPER}} .ramka-mega__panel' => 'padding: {{TOP}}px {{RIGHT}}px {{BOTTOM}}px {{LEFT}}px' ) ) );
		$this->add_group_control( $border::get_type(), array( 'name' => 'panel_border', 'selector' => '{{WRAPPER}} .ramka-mega__panel' ) );
		$this->add_control( 'panel_radius', array( 'label' => __( 'Radius', 'ramka-elementor-product-mega-menu' ), 'type' => $c::DIMENSIONS, 'selectors' => array( '{{WRAPPER}} .ramka-mega__panel' => 'border-radius: {{TOP}}px {{RIGHT}}px {{BOTTOM}}px {{LEFT}}px' ) ) );
		$this->add_group_control( $shadow::get_type(), array( 'name' => 'panel_shadow', 'selector' => '{{WRAPPER}} .ramka-mega__panel' ) );
		$this->add_control( 'panel_z', array( 'label' => __( 'Z-index', 'ramka-elementor-product-mega-menu' ), 'type' => $c::NUMBER, 'default' => 9999, 'selectors' => array( '{{WRAPPER}} .ramka-mega__panel' => 'z-index: {{VALUE}}' ) ) );
		$this->end_controls_section();

		$this->start_controls_section( 'style_roots', array( 'label' => __( 'Main categories', 'ramka-elementor-product-mega-menu' ), 'tab' => $c::TAB_STYLE ) );
		$this->add_responsive_control( 'sidebar_width', array( 'label' => __( 'Sidebar width', 'ramka-elementor-product-mega-menu' ), 'type' => $c::SLIDER, 'selectors' => array( '{{WRAPPER}} .ramka-mega' => '--ramka-sidebar: {{SIZE}}{{UNIT}}' ) ) );
		$this->add_control( 'sidebar_bg', array( 'label' => __( 'Background', 'ramka-elementor-product-mega-menu' ), 'type' => $c::COLOR, 'selectors' => array( '{{WRAPPER}} .ramka-mega__roots' => 'background-color: {{VALUE}}' ) ) );
		$this->add_group_control( $typo::get_type(), array( 'name' => 'roots_typo', 'selector' => '{{WRAPPER}} .ramka-mega__root' ) );
		foreach ( array( 'root_color' => array( __( 'Text', 'ramka-elementor-product-mega-menu' ), '.ramka-mega__root', 'color' ), 'root_hover' => array( __( 'Hover text', 'ramka-elementor-product-mega-menu' ), '.ramka-mega__root:hover', 'color' ), 'root_active' => array( __( 'Active text', 'ramka-elementor-product-mega-menu' ), '.ramka-mega__root[aria-selected="true"]', 'color' ), 'root_active_bg' => array( __( 'Active background', 'ramka-elementor-product-mega-menu' ), '.ramka-mega__root[aria-selected="true"]', 'background-color' ) ) as $key => $spec ) {
			$this->add_control( $key, array( 'label' => $spec[0], 'type' => $c::COLOR, 'selectors' => array( '{{WRAPPER}} ' . $spec[1] => $spec[2] . ': {{VALUE}}' ) ) );
		}
		$this->add_control( 'category_icon_color', array( 'label' => __( 'Category icon color', 'ramka-elementor-product-mega-menu' ), 'type' => $c::COLOR, 'selectors' => array( '{{WRAPPER}} .ramka-mega__root-media, {{WRAPPER}} .ramka-mega__group-media' => 'color: {{VALUE}}' ) ) );
		$this->add_control( 'category_icon_bg', array( 'label' => __( 'Category icon background', 'ramka-elementor-product-mega-menu' ), 'type' => $c::COLOR, 'selectors' => array( '{{WRAPPER}} .ramka-mega__root-media, {{WRAPPER}} .ramka-mega__group-media' => 'background: {{VALUE}}' ) ) );
		$this->add_responsive_control( 'category_icon_box_size', array( 'label' => __( 'Category icon box size', 'ramka-elementor-product-mega-menu' ), 'type' => $c::SLIDER, 'range' => array( 'px' => array( 'min' => 28, 'max' => 80 ) ), 'selectors' => array( '{{WRAPPER}} .ramka-mega__root-media, {{WRAPPER}} .ramka-mega__group-media' => 'width: {{SIZE}}{{UNIT}}; height: {{SIZE}}{{UNIT}}' ) ) );
		$this->add_responsive_control( 'category_icon_svg_size', array( 'label' => __( 'Category icon size', 'ramka-elementor-product-mega-menu' ), 'type' => $c::SLIDER, 'range' => array( 'px' => array( 'min' => 14, 'max' => 48 ) ), 'selectors' => array( '{{WRAPPER}} .ramka-mega__svg-icon' => 'width: {{SIZE}}{{UNIT}}; height: {{SIZE}}{{UNIT}}' ) ) );
		$this->end_controls_section();

		$this->start_controls_section( 'style_children', array( 'label' => __( 'Subcategories', 'ramka-elementor-product-mega-menu' ), 'tab' => $c::TAB_STYLE ) );
		$this->add_group_control( $typo::get_type(), array( 'name' => 'group_typo', 'selector' => '{{WRAPPER}} .ramka-mega__group-title' ) );
		$this->add_group_control( $typo::get_type(), array( 'name' => 'links_typo', 'selector' => '{{WRAPPER}} .ramka-mega__child-link' ) );
		$this->add_control( 'link_color', array( 'label' => __( 'Link color', 'ramka-elementor-product-mega-menu' ), 'type' => $c::COLOR, 'selectors' => array( '{{WRAPPER}} .ramka-mega__child-link' => 'color: {{VALUE}}' ) ) );
		$this->add_control( 'link_hover', array( 'label' => __( 'Link hover', 'ramka-elementor-product-mega-menu' ), 'type' => $c::COLOR, 'selectors' => array( '{{WRAPPER}} .ramka-mega__child-link:hover' => 'color: {{VALUE}}' ) ) );
		$this->add_responsive_control( 'column_gap', array( 'label' => __( 'Column gap', 'ramka-elementor-product-mega-menu' ), 'type' => $c::SLIDER, 'selectors' => array( '{{WRAPPER}} .ramka-mega__grid' => 'column-gap: {{SIZE}}{{UNIT}}' ) ) );
		$this->add_responsive_control( 'row_gap', array( 'label' => __( 'Row gap', 'ramka-elementor-product-mega-menu' ), 'type' => $c::SLIDER, 'selectors' => array( '{{WRAPPER}} .ramka-mega__grid' => 'row-gap: {{SIZE}}{{UNIT}}' ) ) );
		$this->add_responsive_control( 'image_size', array( 'label' => __( 'Category image size', 'ramka-elementor-product-mega-menu' ), 'type' => $c::SLIDER, 'selectors' => array( '{{WRAPPER}} .ramka-mega__image' => 'width: {{SIZE}}{{UNIT}}; height: {{SIZE}}{{UNIT}}' ) ) );
		$this->add_control( 'divider', array( 'label' => __( 'Divider color', 'ramka-elementor-product-mega-menu' ), 'type' => $c::COLOR, 'selectors' => array( '{{WRAPPER}} .ramka-mega__group' => 'border-color: {{VALUE}}' ) ) );
		$this->end_controls_section();

		$this->start_controls_section( 'style_overlay', array( 'label' => __( 'Backdrop and animation', 'ramka-elementor-product-mega-menu' ), 'tab' => $c::TAB_STYLE ) );
		$this->add_control( 'overlay_color', array( 'label' => __( 'Backdrop color', 'ramka-elementor-product-mega-menu' ), 'type' => $c::COLOR, 'selectors' => array( '{{WRAPPER}} .ramka-mega__overlay' => 'background-color: {{VALUE}}' ) ) );
		$this->end_controls_section();
	}

	/**
	 * Render only the inner mega-menu markup.
	 *
	 * Used by the published Theme Builder header bridge so the editor keeps
	 * the native Ramka widget while the live header receives deterministic HTML.
	 */
	public function render_runtime_markup(): string {
		ob_start();
		$this->render();
		return (string) ob_get_clean();
	}

	protected function render(): void {
		$s = $this->get_settings_for_display();

		$tree = Categories::tree( 'yes' !== ( $s['hide_empty'] ?? '' ), in_array( $s['sort'] ?? '', array( 'custom', 'name', 'id', 'count' ), true ) ? $s['sort'] : 'custom', 'DESC' === ( $s['order'] ?? '' ) ? 'DESC' : 'ASC' );
		if ( 'selected' === ( $s['source'] ?? '' ) ) {
			$include = array_map( 'absint', (array) ( $s['selected'] ?? array() ) );
			$tree = array_values( array_filter( $tree, static fn( $node ) => in_array( $node['term']->term_id, $include, true ) ) );
		}
		$exclude = array_map( 'absint', (array) ( $s['excluded'] ?? array() ) );
		$prune = static function ( array $items ) use ( &$prune, $exclude ): array {
			foreach ( $items as $index => &$item ) {
				if ( in_array( $item['term']->term_id, $exclude, true ) ) { unset( $items[ $index ] ); continue; }
				$item['children'] = $prune( $item['children'] );
			}
			unset( $item );
			return array_values( $items );
		};
		$tree = $prune( $tree );
		$limit = min( 500, max( 0, (int) ( $s['limit'] ?? 0 ) ) );
		if ( $limit ) { $tree = array_slice( $tree, 0, $limit ); }
		$depth = min( 8, max( 1, (int) ( $s['depth'] ?? 3 ) ) );
		$id = wp_unique_id( 'ramka-mega-' );
		$is_editor = self::is_editor_request();

		/*
		 * Render atomically. If a future template regression throws an Error or
		 * Exception, do not leave the rest of the Elementor document truncated.
		 */
		$buffer_level = ob_get_level();
		ob_start();
		try {
			require RAMKA_MEGA_PATH . 'templates/mega-menu.php';
			$output = ob_get_clean();
			echo $output; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- template escapes dynamic values.
		} catch ( \Throwable $error ) {
			while ( ob_get_level() > $buffer_level ) {
				ob_end_clean();
			}
			error_log( 'Ramka Mega Menu render error: ' . $error->getMessage() ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
			$label = isset( $s['label'] ) && is_scalar( $s['label'] ) ? (string) $s['label'] : __( 'Product categories', 'ramka-elementor-product-mega-menu' );
			echo '<div class="ramka-mega ramka-mega--fallback"><button type="button" class="ramka-mega__trigger" disabled>' . esc_html( $label ) . '</button></div>';
		}
	}
}
