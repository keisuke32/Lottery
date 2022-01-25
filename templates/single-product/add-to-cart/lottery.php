<?php
/**
 * Lottery product add to cart template
 *
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly
global $product, $post;

$price = method_exists( $product, 'get_price' ) ? $product->get_price() : $product->price;

if ( ! $product->is_purchasable() OR ! $product->is_in_stock() OR $product->is_closed()  ) return;

?>

<?php do_action('woocommerce_before_add_to_cart_form'); ?>

<form class="buy-now cart" method="post" enctype='multipart/form-data' data-product_id="<?php echo $post->ID; ?>">
    
    <?php 

        do_action('woocommerce_before_add_to_cart_button');

//             if ( ! $product->is_sold_individually() )
//                 woocommerce_quantity_input( array(
//                         'min_value' => apply_filters( 'woocommerce_quantity_input_min', 1, $product ),
//                         'max_value' => apply_filters( 'woocommerce_quantity_input_max', $product->get_max_purchase_quantity(), $product )
//                 ) );
     ?>

    <input type="hidden" name="add-to-cart" value="<?php echo esc_attr( $product->get_id() ); ?>" />

    <button type="submit" class="single_add_to_cart_button button alt"  disabled="disabled" style="margin-top: 20px;"><?php echo wp_kses_post( $product->single_add_to_cart_text() ); ?></button>
    
    <div>
        <input type="hidden" name="add-to-cart" value="<?php echo $product->get_id(); ?>" />
        <input type="hidden" name="product_id" value="<?php echo esc_attr( $product->get_id() ); ?>" />
        <input type="hidden" id="selected_ticket_number" name="selected_ticket_number">
    </div>

    <?php do_action('woocommerce_after_add_to_cart_button'); ?>

</form>

<?php do_action('woocommerce_after_add_to_cart_form');