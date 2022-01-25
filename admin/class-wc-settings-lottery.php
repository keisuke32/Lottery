<?php
/**
 * WooCommerce Lottery Settings
 *
 */
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

if (  class_exists( 'WC_Settings_Page' ) ) :

/**
 * WC_Settings_Accounts
 */
class WC_Settings_Lottery extends WC_Settings_Page {

	/**
	 * Constructor.
	 */
	public function __construct() {
            
            $this->id    = 'simple_lottery';
            $this->label = __( 'Lottery', 'wc_lottery' );

            add_filter( 'woocommerce_settings_tabs_array', array( $this, 'add_settings_page' ), 20 );
            add_action( 'woocommerce_settings_' . $this->id, array( $this, 'output' ) );
            add_action( 'woocommerce_settings_save_' . $this->id, array( $this, 'save' ) );

	}

	/**
	 * Get settings array
	 *
	 * @return array
	 */
	public function get_settings() {

		return apply_filters( 'woocommerce_' . $this->id . '_settings', array(

			array(	'title' => __( 'WC Lottery options', 'wc_lottery' ), 'type' => 'title','desc' => '', 'id' => 'simple_lottery_options' ),
                                        array(
                                                'title' 			=> __( 'Past lottery', 'wc_lottery' ),
                                                'desc'                          => __( 'Show finished lotteries', 'wc_lottery' ),
                                                'type' 				=> 'checkbox',
                                                'id'				=> 'simple_lottery_finished_enabled',
                                                'default' 			=> 'no'											
                                        ),
                                        array(
                                                'title' 			=> __( 'Future lottery', 'wc_lottery' ),
                                                'desc'                          => __( 'Show lotteries that did not start yet', 'wc_lottery' ),
                                                'type' 				=> 'checkbox',
                                                'id'				=> 'simple_lottery_future_enabled',
                                                'default' 			=> 'yes'
                                        ),
                                        array(
                                                'title' 			=> __( "Do not show lottery on shop page", 'wc_lottery' ),
                                                'desc'                          => __( 'Do not mix lottery and regular products on shop page. Just show lottery on the lottery page (lottery base page)', 'wc_lottery' ),
                                                'type' 				=> 'checkbox',
                                                'id'				=> 'simple_lottery_dont_mix_shop',
                                                'default' 			=> 'no'
                                        ),
                                        array(
                                                'title' 			=> __( "Do not show lottery on product search page", 'wc_lottery' ),
                                                'desc'                          => __( 'Do not mix lottery and regular products on product search page (show lotteries only when using lottery search)', 'wc_lottery' ),
                                                'type' 				=> 'checkbox',
                                                'id'				=> 'simple_lottery_dont_mix_search',
                                                'default' 			=> 'no'
                                        ),
                                        array(
                                                'title' 			=> __( "Do not show lottery on product category page", 'wc_lottery' ),
                                                'desc'                          => __( 'Do not mix lottery and regular products on product category page. Just show lottery on the lottery page (lottery base page)', 'wc_lottery' ),
                                                'type' 				=> 'checkbox',
                                                'id'				=> 'simple_lottery_dont_mix_cat',
                                                'default' 			=> 'no'
                                        ),
                                        array(
                                                'title' 			=> __( "Do not show lottery on product tag page", 'wc_lottery' ),
                                                'desc'                          => __( 'Do not mix lottery and regular products on product tag page. Just show lottery on the lottery page (lottery base page)', 'wc_lottery' ),
                                                'type' 				=> 'checkbox',
                                                'id'				=> 'simple_lottery_dont_mix_tag',
                                                'default' 			=> 'no'
                                        ),
                                        array(
                                                'title' 			=> __( "Countdown format", 'wc_lottery' ),
                                                'desc'				=> __( "The format for the countdown display. Default is yowdHMS", 'wc_lottery' ),
                                                'desc_tip' 			=> __( "Use the following characters (in order) to indicate which periods you want to display: 'Y' for years, 'O' for months, 'W' for weeks, 'D' for days, 'H' for hours, 'M' for minutes, 'S' for seconds. Use upper-case characters for mandatory periods, or the corresponding lower-case characters for optional periods, i.e. only display if non-zero. Once one optional period is shown, all the ones after that are also shown.", 'wc_lottery' ),
                                                'type' 				=> 'text',
                                                'id'				=> 'simple_lottery_countdown_format',
                                                'default' 			=> 'yowdHMS'
                                        ),
                                        array(
                                            'title'             => __( "Use compact countdown ", 'wc_lottery' ),
                                            'desc'              => __( 'Indicate whether or not the countdown should be displayed in a compact format.', 'wc_lottery' ),
                                            'type'              => 'checkbox',
                                            'id'                => 'simple_lottery_compact_countdown',
                                            'default'           => 'no'
                                        ),
                                        array(
                                                'title'         => __( 'WC Lottery Base Page', 'wc_lottery' ),
                                                'desc' 		=> __( 'Set the base page for your lottery - this is where your lottery archive page will be.', 'wc_lottery' ),
                                                'id' 		=> 'woocommerce_lottery_page_id',
                                                'type' 		=> 'single_select_page',
                                                'default'	=> '',
                                                'class'		=> 'chosen_select_nostd',
                                                'css' 		=> 'min-width:300px;',
                                                'desc_tip'	=>  true
                                                ),
                                        array(
                                                'title'         => __( 'Lottery history tab', 'wc_lottery' ),
                                                'desc' 		=> __( 'Show lottery history tab on single product page (lottery details page)', 'wc_lottery' ),
                                                'id' 		=> 'simple_lottery_history',
                                                'type' 		=> 'checkbox',
                                                'default'	=> 'yes'												
                                        ),

                                        array(
                                                'title'         => __( 'Show progress bar', 'wc_lottery' ),
                                                'desc' 		=> __( 'Show lottery progress bar on single product page (lottery details page)', 'wc_lottery' ),
                                                'id' 		=> 'simple_lottery_progressbar',
                                                'type' 		=> 'checkbox',
                                                'default'	=> 'yes'												
                                        ),
                                         array(
                                                'title'     => __( 'Lottery badge', 'wc_lottery' ),
                                                'desc'      => __( 'Show lottery badge in loop', 'wc_lottery' ),
                                                'id'        => 'simple_lottery_bage',
                                                'type'      => 'checkbox',
                                                'default'   => 'yes'                                                
                                        ),
                                        array(
                                                'title'             => __( 'Close lottery when maximum ticket was sold', 'wc_lottery' ),
                                                'desc'              => __( 'Option to instantly finish lottery when maximum number  of tickets was sold', 'wc_lottery' ),
                                                'type'              => 'checkbox',
                                                'id'                => 'simple_lottery_close_when_max',
                                                'default'           => 'no'
                                        ), 
                                        array(
                                                'title'             => __( 'Show lottery history in admin product page', 'wc_lottery' ),
                                                'desc'              => __( 'Option to show history table in admin page', 'wc_lottery' ),
                                                'type'              => 'checkbox',
                                                'id'                => 'simple_lottery_history_admin',
                                                'default'           => 'yes'
                                        ),

                                        array( 'type' => 'sectionend', 'id' => 'simple_lottery_options'),

		)); // End pages settings
	}
}
return new WC_Settings_Lottery();

endif;