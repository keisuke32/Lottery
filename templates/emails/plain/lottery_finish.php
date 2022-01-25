<?php
/**
 * Admin lottery finished email (plain)
 *
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

global $woocommerce;

$product_data = wc_get_product(  $product_id );

echo $email_heading . "\n\n";

printf( __( "Lottery %s has finished.", 'wc_lottery' ), $product_data->get_title() );  
echo "\n\n";
echo get_permalink($product_id);
echo "\n\n";

echo apply_filters( 'woocommerce_email_footer_text', get_option( 'woocommerce_email_footer_text' ) );