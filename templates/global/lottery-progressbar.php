<?php
/**
 * Lottery progressbar template
 *
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly
global  $product, $post;
$min_tickets                = $product->get_min_tickets();
$max_tickets                = $product->get_max_tickets();
$lottery_participants_count = !empty($product->get_lottery_participants_count()) ? $product->get_lottery_participants_count() : '0';
?>

<?php if( $max_tickets  &&( $max_tickets > 0 )  && (get_option( 'simple_lottery_progressbar' ,'yes' ) == 'yes') ) : ?>
        
<div class="wcl-progress-meter <?php if($product->is_max_tickets_met()) echo 'full' ?>">
    <span class="zero">0</span>
    <span class="max"><?php echo $max_tickets ?></span>
    <progress  max="<?php echo $max_tickets ?>" value="<?php echo $lottery_participants_count ?>"  low="<?php echo $min_tickets ?>"></progress>
</div>

<?php endif; ?>	