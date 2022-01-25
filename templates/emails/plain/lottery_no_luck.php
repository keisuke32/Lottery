<?php
/**
 * Email lottery not won - better luck next time (plain)
 *
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

global $woocommerce;

$product_data = wc_get_product(  $product_id );

echo $email_heading . "\n\n";

printf(__("We are sorry. You are not picked as winner of %s.  Better luck next time.", 'wc_lottery'),  $product_data -> get_title(), wc_price($current_bid));  
echo "\n\n";
echo get_permalink($product_id);
echo "\n\n";

echo apply_filters( 'woocommerce_email_footer_text', get_option( 'woocommerce_email_footer_text' ) );