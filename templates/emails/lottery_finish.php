<?php
/**
 * Admin lottery finish email
 *
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly 
$product_data = wc_get_product(  $product_id );
$lottery_winers = get_post_meta($product_id, '_lottery_winners');


?>

<?php do_action('woocommerce_email_header', $email_heading); ?>

<p><?php printf( __( "Lottery <a href='%s'>%s</a> has finished.", 'wc_lottery' ),get_permalink($product_id), $product_data->get_title() ); ?>
<?php 
if ( $lottery_winers ) {

	if ( count( $lottery_winers ) === 1 ) { 

		$winnerid = reset( $lottery_winers );
		if ( ! empty( $winnerid ) ) {
		?>
				<p>
					<?php _e( 'Lottery winner is', 'wc_lottery' ); ?>: <span><a href='<?php echo get_edit_user_link( $winnerid ); ?>'><?php echo get_userdata( $winnerid )->display_name; ?></a></span>
				</p>
		<?php } ?>
	<?php } else { ?>

	<p><?php _e( 'Lottery winners are', 'wc_lottery' ); ?>:
		<ul>
		<?php
		foreach ( $lottery_winers as $key => $winnerid ) {
			if ( $winnerid > 0 ) {
			?>
				<li><a href='<?php get_edit_user_link( $winnerid ); ?>'><?php echo get_userdata( $winnerid )->display_name; ?></a></li>
		<?php
			}
		}
		?>
		</ul>
	</p>

<?php }
} ?>



<?php do_action('woocommerce_email_footer');