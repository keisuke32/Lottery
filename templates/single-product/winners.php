<?php
/**
 * Winners block template
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/single-product/winners.php.
 * 
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

global  $product, $post;
$current_user       = wp_get_current_user();
$lottery_winers     = get_post_meta($post->ID, '_lottery_winners');
$lottery_pn_winers  = get_post_meta($post->ID, '_lottery_pn_winners',true);
$use_ticket_numbers = 'yes';
$answers            = maybe_unserialize( get_post_meta( $post->ID, '_lottery_pn_answers', true ) );
$winners = apply_filters('lottery_get_all_winners','');
?>


<?php if(get_post_meta($post->ID, '_order_hold_on')) { ?>

	<p><?php esc_html_e('Please be patient. We are waiting for some orders to be paid!','wc-lottery') ?></p>

<?php } elseif( '2' === $product->get_lottery_closed() && 'yes' === get_post_meta( $post->ID, '_lottery_manualy_winners', true ) && empty($lottery_winers) ){
		esc_html_e('Please be patient. We are picking winners!','wc-lottery'); 
	} else { 
	
		if ( $product->get_lottery_closed() == 2 ) {?>
			<?php if ($product->is_user_participating()) : ?>
			<?php
			$is_winner = false;
			$is_pending = false;
			foreach($winners as $winner) :
			    if(empty($winner['tickets'])){
			        $is_pending = true;
			    }
			    foreach($winner['tickets'] as $winner_ticket){
			        foreach($winner_ticket[1] as $winner_user){
			            if($winner_user['user'] == $current_user->ID){
			                $is_winner = true;
			                ?>
                            <p class="user_prize"><?php _e('<span class="dashicons dashicons-awards user_winner_span""></span> You won '.$winner['prize_name'].' - '.$winner['pp'].' '.$winner['pt_plural'].'!','wc_lottery') ?></p>
                        <?php
			            }
			        }
			    }
			endforeach;
			if (!$is_winner) : ?>
                <p class="user_no_prize"><?php _e('<span class="dashicons dashicons-coffee user_winner_span"></span> Better luck next time','wc_lottery') ?></p>
            <?php endif;
            if ($is_pending) : ?>
                <p class="user_pending_prize"><?php _e('<span class="dashicons dashicons-backup user_winner_span"></span> Pending Results...','wc_lottery') ?></p>
			<?php endif; ?>
			<?php endif;?>
			<div class="winner_panel">
				<p class="winner_line winner_line_header">
					<span class="winner_category">Category</span>
					<span class="winner_Match">Match</span>
					<span class="winner_Winners">Winners</span>
					<span class="winner_Payout">Payout</span>
				</p>
				<?php
			        // echo '<pre>';
			        // print_r($this->lottery_get_all_winners());
			        // echo '</pre>';
					$lottery_match_alphabet_options = [
			            1 => [
			                6 => '7D',
			                5 => '6D',
			                4 => '5D',
			                3 => '4D',
			                2 => '3D',
			                1 => '2D'
			            ],
			            3 => [
			                1 => '3D',
			            ],
			            4 => [
			                1 => '4D',
			            ],
			            6 => [
			                1 => '2D Suffix',
			                2 => '3D Prefix',
			                3 => '3D Suffix',
			                4 => '4D Prefix',
			                5 => '4D Suffix',
			                6 => '6D',
			            ]
			        ];
			        foreach($winners as $winner)
			        {
			    ?>
			    	<p class="winner_line winner_line_prize">
						<span class="winner_category"><?php echo $winner['prize_name']; ?></span>
						<span class="winner_Match">
							<?php echo $lottery_match_alphabet_options[$winner['pa']][$winner['pm']]; ?>	
						</span>
						<span class="winner_Winners"><?php echo count($winner['tickets']); ?>x</span>
						<span class="winner_Payout"><?php echo $winner['pp']; ?> Credits</span>
					</p>
					<p class="winner_line winner_line_ticket">
						<?php
						foreach($winner['tickets'] as $winner_ticket) {
						?>
							<span><?php echo apply_filters('adjust_ticket_number', $winner_ticket[0], $winner['pa']) ?></span>
						<?php
						}
						?>
					</p>
			    <?php
					}
			    ?>
			</div>
		<?php } else{ 
			if ( $product->get_lottery_fail_reason() == '1' ) { ?>
				<p><?php _e('Lottery failed because there were no participants','wc_lottery') ?></p>
			<?php } elseif ( $product->get_lottery_fail_reason() == '2' ) { ?>
				<p><?php _e('Lottery failed because there was not enough participants','wc_lottery') ?></p>
			<?php } ?>
		<?php } ?>
	
<?php } ?>
<?php if ( ! empty( $lottery_pn_winers ) ) {

	if (count($lottery_pn_winers) > 1) { ?>

		<h3><?php esc_html_e('Winners:','wc-lottery') ?></h3>

		<ol class="lottery-winners">
		<?php

	        foreach ($lottery_pn_winers as $winner) {

	                echo "<li>";
	                if ( intval( $winner ) > 0){
	                echo get_userdata($winner['userid'])->display_name;
		                echo '<br>';
		                if( $use_ticket_numbers === 'yes'){
		                	echo "<span class='ticket-number'>";
		                	esc_html_e( 'Ticket number: ', 'wc-lottery' );
		                	echo apply_filters( 'ticket_number_display_html' , $winner['ticket_number'], $product ) ;
		                	echo " </span>";
		                }

	                }
	                echo "</li>";
	        }
		?>
		</ol>

		<?php } elseif( 1 === count( $lottery_pn_winers )  ) {

			$winner = reset($lottery_pn_winers);

			if ( ! empty ( $winner ) ) {
			?>
				<div class="lottery-winners">
				<h3><?php esc_html_e('Winner is:','wc-lottery') ?> <?php echo get_userdata($winner['userid'])->display_name; ?></h3>
					<?php if( $use_ticket_numbers === 'yes'){
						echo " <span class='ticket-number'>";
						esc_html_e( 'Ticket number: ', 'wc-lottery' );
						echo apply_filters( 'ticket_number_display_html' , $winner['ticket_number'], $product );
						echo "</span>";
					}

				echo '</div>';
			} else {
				echo '<h3>';
				esc_html_e( 'There is no winner for this lottery', 'wc-lottery' );
				echo '</h3>';
			}


		} else {
			echo '<h3>';
			esc_html_e( 'There is no winner for this lottery', 'wc-lottery' );
			echo '</h3>';
		}

} else {

	if( is_array($lottery_winers) && ! empty ( $lottery_winers ) ){

		if (count($lottery_winers) > 1) { ?>

			<h3><?php esc_html_e('Winners:','wc-lottery') ?></h3>

			<ol class="lottery-winners">
			<?php

				foreach ($lottery_winers as $winner_id) {
						echo "<li>";
						echo intval($winner_id) > 0 ? get_userdata($winner_id)->display_name : esc_html_e( 'N/A ', 'wc-lottery' );;
						echo "</li>";
				}
			?>
			</ol>

		<?php } elseif ( isset( $lottery_winers[0] ) ) { ?>

			<h3><?php esc_html_e('Winner is:','wc-lottery') ?> <?php echo get_userdata( $lottery_winers[0] )->display_name; ?></h3>

		<?php } ?>

	<?php }
}

$pick_text = get_post_meta($post->ID, '_lottery_manualy_pick_text', true );
if ( $pick_text ){
	echo '<p>';
	echo wp_kses_post( $pick_text );
	echo '</p>';
}
