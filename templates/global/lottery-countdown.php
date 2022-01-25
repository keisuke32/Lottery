<?php
/**
 * Lottery countdown template
 *
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly
global  $product, $post;

if ( ( $product->is_closed() === FALSE ) && ($product->is_started() === TRUE ) ) : ?>
	<div class="lottery-time" id="countdown"><?php echo apply_filters('time_text', __( 'Time left:', 'wc_lottery' ), $product->get_type()); ?> 
		<div class="main-lottery lottery-time-countdown" data-time="<?php echo $product->get_seconds_remaining() ?>" data-lotteryid="<?php echo $product->get_id() ?>" data-format="<?php echo get_option( 'simple_lottery_countdown_format' ) ?>"></div>
	</div>

<?php 
elseif ( ( $product->is_closed() === FALSE ) && ($product->is_started() === FALSE ) ) :?>
	<div class="lottery-time future" id="countdown"><?php echo  __( 'Lottery starts in:', 'wc_lottery' ) ?> 
		<div class="lottery-time-countdown future" data-time="<?php echo $product->get_seconds_to_lottery() ?>" data-format="<?php echo get_option( 'simple_lottery_countdown_format' ) ?>"></div>
	</div>
<?php endif; 
