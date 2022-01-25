<?php
/**
 * Lottery info template
 *
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly
global $product, $post;

$lottery_dates_to           = $product->get_lottery_dates_to();
$lottery_dates_from         = $product->get_lottery_dates_from();
?>
<p class="lottery-starts"><?php echo  __( 'Lottery starts:', 'wc_lottery' ) ?> <?php echo  date_i18n( get_option( 'date_format' ),  strtotime( $lottery_dates_from ));  ?>  <?php echo  date_i18n( get_option( 'time_format' ),  strtotime( $lottery_dates_from ));  ?></p>
<p class="lottery-end"><?php echo  __( 'Lottery ends:', 'wc_lottery' ); ?> <?php echo  date_i18n( get_option( 'date_format' ),  strtotime( $lottery_dates_to ));  ?>  <?php echo  date_i18n( get_option( 'time_format' ),  strtotime( $lottery_dates_to ));  ?> </p>
