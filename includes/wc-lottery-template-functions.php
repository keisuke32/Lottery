<?php

if ( ! function_exists( 'woocommerce_lottery_participate_template' ) ) {

	/**
	 * Load participate template part
	 *
	 */
	function woocommerce_lottery_participate_template() {
		global $product;

		if ( $product->get_type() === 'lottery' ){
			wc_get_template( 'single-product/participate.php' );
		}

	}

}

if ( ! function_exists( 'woocommerce_lottery_winners_template' ) ) {
	/**
	 * Load winners template part
	 *
	 */
	function woocommerce_lottery_winners_template() {
		global $product;
		if ( $product->get_type() === 'lottery' ){
			wc_get_template( 'single-product/winners.php' );
		}
	}
}

if ( ! function_exists( 'woocommerce_lottery_add_to_cart_template' ) ) {
	/**
	 * Load lottery product add to cart template part.
	 *
	 */
	function woocommerce_lottery_add_to_cart_template() {
		wc_get_template( 'single-product/add-to-cart/lottery.php' );
	}
}

if ( ! function_exists( 'woocommerce_lottery_countdown_template' ) ) {
	/**
	 * Load lottery product add to cart template part.
	 *
	 */
	function woocommerce_lottery_countdown_template() {
		wc_get_template( 'global/lottery-countdown.php' );
	}
}

if ( ! function_exists( 'woocommerce_lottery_info_template' ) ) {
	/**
	 * Load lottery product add to cart template part.
	 *
	 */
	function woocommerce_lottery_info_template() {
		wc_get_template( 'single-product/lottery-info.php' );
	}
}

if ( ! function_exists( 'woocommerce_lottery_info_future_template' ) ) {
	/**
	 * Load lottery product add to cart template part.
	 *
	 */
	function woocommerce_lottery_info_future_template() {
		wc_get_template( 'single-product/lottery-info-future.php' );
	}
}
if ( ! function_exists( 'woocommerce_lottery_progressbar_template' ) ) {
	/**
	 * Load lottery product add to cart template part.
	 *
	 */
	function woocommerce_lottery_progressbar_template () {
		wc_get_template( 'global/lottery-progressbar.php' );
	}
}
if ( ! function_exists( 'woocommerce_lottery_get_finished_auctions_id' ) ) {

    /**
     * Return finished auctions IDs
     *
     * @subpackage  Loop
     * 
     */
    function woocommerce_lottery_get_finished_lotteries_id() {
    		$args = array(
					'post_type' => 'product',
					'posts_per_page' => '-1',
					'tax_query' => array(array('taxonomy' => 'product_type', 'field' => 'slug', 'terms' => 'lottery')),
					'meta_query' => array(
						array(
							'key' => '_lottery_closed',
							'compare' => 'EXISTS',
						)
					),
					'is_lottery_archive' => TRUE,
					'show_past_lottery' => TRUE,
					'fields' => 'ids',
			);
	    	$query = new WP_Query( $args );
	    	$woocommerce_lottery_finished_auctions_ids = $query->posts;
			return $woocommerce_lottery_finished_auctions_ids;
	}    
    
}

if ( ! function_exists( 'woocommerce_lottery_get_future_auctions_id' ) ) {

    /**
     * Return future auctions IDs
     *
     * @subpackage  Loop
     * 
     */
    function woocommerce_lottery_get_future_lotteries_id() {
    		$args = array(
					'post_type' => 'product',
					'posts_per_page' => '-1',
					'tax_query' => array(array('taxonomy' => 'product_type', 'field' => 'slug', 'terms' => 'lottery')),
					'meta_query' => array(
						array(
							'key' => '_lottery_closed',
							'compare' => 'NOT EXIST',
						),
						array(
							'key' => '_lottery_started',
							'value' => '0',
						)
					),
					'is_lottery_archive' => TRUE,
					'show_future_lotteries' => TRUE,
					'fields' => 'ids',
			);
	    	$query = new WP_Query( $args );
	    	$woocommerce_lottery_future_auctions_ids = $query->posts;
			return $woocommerce_lottery_future_auctions_ids;
	}    
    
}
