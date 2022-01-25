<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Lottery Search Widget
 *
  * @extends  WC_Widget
 */
class WC_Widget_Lotteries_Search extends WC_Widget {

	/**
	 * Constructor
	 */
	public function __construct() {
		$this->widget_cssclass    = 'woocommerce widget_lottery_search';
		$this->widget_description = __( 'A Search box for lotteries only.', 'wc_lottery' );
		$this->widget_id          = 'woocommerce_lottery_search';
		$this->widget_name        = __( 'WooCommerce Lottery Search', 'wc_lottery' );
		$this->settings           = array(
			'title'  => array(
				'type'  => 'text',
				'std'   => '',
				'label' => __( 'Title', 'wc_lottery' )
			)
		);

		parent::__construct();
	}

	/**
	 * widget function.
	 *
	 * @see WP_Widget
	 *
	 * @param array $args
	 * @param array $instance
	 *
	 * @return void
	 */
	function widget( $args, $instance ) {
		$this->widget_start( $args, $instance );

		ob_start();

		do_action( 'pre_get_lottery_search_form'  );

		wc_get_template( 'lotteries-searchform.php' );

		$form = apply_filters( 'get_lottery_search_form', ob_get_clean() );

		
		echo $form;
		

		$this->widget_end( $args );
	}
}
