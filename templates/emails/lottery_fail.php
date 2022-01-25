<?php
/**
 * Lottery failed email
 *
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly 
$product_data = wc_get_product(  $product_id );
?>

<?php do_action('woocommerce_email_header', $email_heading); ?>

<p><?php printf( __( "Sorry. Lottery <a href='%s'>%s</a> has failed.", 'wc_lottery' ),get_permalink($product_id), $product_data->get_title()); ?></p>


<?php do_action('woocommerce_email_footer');