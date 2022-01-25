<?php
/**
 * Participate in lottery template
 *
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly
global $woocommerce, $product, $post;


$lottery_dates_to           = $product->get_lottery_dates_to();
$lottery_dates_from         = $product->get_lottery_dates_from();


 if(($product->is_closed() === FALSE ) and ($product->is_started() === TRUE )) :
    do_action( 'woocommerce_lottery_before_participate')
  ?>

    <div class='lottery-ajax-change1'>
            <?php do_action( 'woocommerce_lottery_ajax_change_participate') ?>
    </div>

<?php elseif (($product->is_closed() === FALSE ) and ($product->is_started() === FALSE )):
    do_action( 'woocommerce_lottery_participate_future');
endif; 
