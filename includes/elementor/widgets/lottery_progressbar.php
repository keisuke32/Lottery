<?php
namespace ElementorPro\Modules\Woocommerce\Widgets;

use Elementor\Controls_Manager;
use Elementor\Core\Kits\Documents\Tabs\Global_Colors;
use Elementor\Core\Kits\Documents\Tabs\Global_Typography;
use Elementor\Group_Control_Typography;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

class Lottery_Progressbar extends Base_Widget {

	public function get_name() {
		return 'woocommerce-lottery-progressbar';
	}

	public function get_title() {
		return __( 'Lottery Progressbar', 'wc_lottery' );
	}

	public function get_icon() {
		return 'eicon-lottery-progressbar';
	}

	public function get_keywords() {
		return [ 'woocommerce', 'shop', 'store', 'lottery', 'progressbar' ];
	}

	protected function _register_controls() {

		$this->start_controls_section(
			'section_lottery_progressbar_style',
			[
				'label' => __( 'Lottery Progressbar', 'elementor-pro' ),
				'tab' => Controls_Manager::TAB_STYLE,
			]
		);

		$this->add_control(
			'wc_style_warning',
			[
				'type' => Controls_Manager::RAW_HTML,
				'raw' => __( 'The style of this widget is often affected by your theme and plugins. If you experience any such issue, try to switch to a basic theme and deactivate related plugins.', 'elementor-pro' ),
				'content_classes' => 'elementor-panel-alert elementor-panel-alert-info',
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

		$this->add_control(
			'lottery_progressbar_text_color',
			[
				'label' => __( 'Text Color', 'elementor-pro' ),
				'type' => Controls_Manager::COLOR,
				'global' => [
					'default' => Global_Colors::COLOR_PRIMARY,
				],
				'selectors' => [
					'.woocommerce {{WRAPPER}} ' => 'color: {{VALUE}}',
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
			'Lottery_progressbar_heading',
			[
				'label' => __( 'Lottery progressbar', 'elementor-pro' ),
				'type' => Controls_Manager::HEADING,
				'separator' => 'before',
			]
		);

		$this->add_control(
			'lottery_progressbar_color',
			[
				'label' => __( 'Bar Color', 'elementor-pro' ),
				'type' => Controls_Manager::COLOR,
				'selectors' => [
					'.woocommerce {{WRAPPER}} progress[value]::-webkit-progress-value' => 'background-color: {{VALUE}};',
				],
			]
		);
		$this->add_control(
			'lottery_progressbar_full_color',
			[
				'label' => __( 'Bar Color Full state', 'elementor-pro' ),
				'type' => Controls_Manager::COLOR,
				'selectors' => [
					'.woocommerce {{WRAPPER}} .full > progress[value]::-webkit-progress-value' => 'background-color: {{VALUE}};',
				],
			]
		);
		$this->add_control(
			'lottery_progressbar_bg_color',
			[
				'label' => __( 'Bar Background Color', 'elementor-pro' ),
				'type' => Controls_Manager::COLOR,
				'selectors' => [
					'.woocommerce {{WRAPPER}} progress[value]::-webkit-progress-bar' => 'background-color: {{VALUE}};',
				],
			]
		);

		$this->add_responsive_control(
			'lottery_progressbar_spacing',
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
					'body:not(.rtl) {{WRAPPER}}:not(.elementor-lottery-progressbar-block-yes)' => 'margin-right: {{SIZE}}{{UNIT}}',
					'body.rtl {{WRAPPER}}:not(.elementor-lottery-progressbar-block-yes)' => 'margin-left: {{SIZE}}{{UNIT}}',
					'{{WRAPPER}}.elementor-lottery-progressbar-block-yes' => 'margin-bottom: {{SIZE}}{{UNIT}}',
				],
			]
		);

		$this->end_controls_section();
	}

	protected function render() {

		global $product;
		$product = wc_get_product();

		if ( empty( $product ) || $product->get_type() !== 'lottery' || $product->is_closed()  ) {
			return;
		}
		wc_get_template( '/global/lottery-progressbar.php' );
	}

	public function render_plain_content() {}
}
