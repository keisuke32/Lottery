<?php
namespace ElementorPro\Modules\Woocommerce\Widgets;

use Elementor\Controls_Manager;
use Elementor\Core\Kits\Documents\Tabs\Global_Colors;
use Elementor\Core\Kits\Documents\Tabs\Global_Typography;
use Elementor\Group_Control_Typography;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

class Lottery_Winners extends Base_Widget {

	public function get_name() {
		return 'woocommerce-lottery-winner';
	}

	public function get_title() {
		return __( 'Lottery Winner', 'wc_lottery' );
	}

	public function get_icon() {
		return 'eicon-lottery-winner';
	}

	public function get_keywords() {
		return [ 'woocommerce', 'shop', 'store', 'lottery' ];
	}

	protected function _register_controls() {

		$this->start_controls_section(
			'section_lottery_winner_style',
			[
				'label' => __( 'Lottery Winner', 'elementor-pro' ),
				'tab' => Controls_Manager::TAB_STYLE,
			]
		);

		$this->add_control(
			'wc_style_warning',
			[
				'type' => Controls_Manager::RAW_HTML,
				'raw' => __( 'The style of this widget is often affected by your theme and plugins. If you experience any such issue, try to switch to a basic theme and deactivate related plugins.', 'elementor-pro' ),
				'content_classes' => 'elementor-panel-alert elementor-panel-alert-winner',
			]
		);

		$this->add_responsive_control(
			'text_align',
			[
				'label' => __( 'Alignment', 'elementor-pro' ),
				'type' => Controls_Manager::CHOOSE,
				'options' => [
					'left' => [
						'title' => __( 'Left', 'elementor-pro' ),
						'icon' => 'eicon-text-align-left',
					],
					'center' => [
						'title' => __( 'Center', 'elementor-pro' ),
						'icon' => 'eicon-text-align-center',
					],
					'right' => [
						'title' => __( 'Right', 'elementor-pro' ),
						'icon' => 'eicon-text-align-right',
					],
				],
				'selectors' => [
					'{{WRAPPER}}' => 'text-align: {{VALUE}}',
				],
			]
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			[
				'name' => 'typography',
				'global' => [
					'default' => Global_Typography::TYPOGRAPHY_PRIMARY,
				],
				'selector' => '.woocommerce {{WRAPPER}}',
			]
		);

		$this->add_control(
			'Lottery_winner_heading',
			[
				'label' => __( 'Lottery winner', 'elementor-pro' ),
				'type' => Controls_Manager::HEADING,
				'separator' => 'before',
			]
		);

		$this->add_control(
			'lottery_winner_color',
			[
				'label' => __( 'Color', 'elementor-pro' ),
				'type' => Controls_Manager::COLOR,
				'selectors' => [
					'.woocommerce {{WRAPPER}}' => 'color: {{VALUE}};',
				],
			]
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			[
				'name' => 'lottery_winner_typography',
				'selector' => '.woocommerce {{WRAPPER}}',
			]
		);

		$this->add_control(
			'lottery_winner_block',
			[
				'label' => __( 'Stacked', 'elementor-pro' ),
				'type' => Controls_Manager::SWITCHER,
				'return_value' => 'yes',
				'prefix_class' => 'elementor-lottery-winner-block-',
			]
		);

		$this->add_responsive_control(
			'lottery_winner_spacing',
			[
				'label' => __( 'Spacing', 'elementor-pro' ),
				'type' => Controls_Manager::SLIDER,
				'size_units' => [ 'px', 'em' ],
				'range' => [
					'em' => [
						'min' => 0,
						'max' => 5,
						'step' => 0.1,
					],
				],
				'selectors' => [
					'body:not(.rtl) {{WRAPPER}}:not(.elementor-lottery-winner-block-yes)' => 'margin-right: {{SIZE}}{{UNIT}}',
					'body.rtl {{WRAPPER}}:not(.elementor-lottery-winner-block-yes)' => 'margin-left: {{SIZE}}{{UNIT}}',
					'{{WRAPPER}}.elementor-lottery-winner-block-yes' => 'margin-bottom: {{SIZE}}{{UNIT}}',
				],
			]
		);

		$this->end_controls_section();
	}

	protected function render() {
		global $product;

		$product = wc_get_product();

		if ( empty( $product ) || $product->get_type() !== 'lottery' ) {
			return;
		}
		if( is_admin() ){
			wc_get_template( '/single-product/winners-elementor.php');
		} else {
			wc_get_template( '/single-product/winners.php', array( 'elementor_edit' => $elementor_edit ) );
		}
		
	}

	public function render_plain_content() {}
}
