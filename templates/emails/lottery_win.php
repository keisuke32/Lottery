<?php
/**
 * Email lottery won
 *
 */

if (!defined('ABSPATH')) exit ; // Exit if accessed directly

$product_data = wc_get_product($product_id);
?>

<?php do_action('woocommerce_email_header', $email_heading); ?>

<p><?php printf(__("Congratulations. You are picked as winner of <a href='%s'>%s</a>.", 'wc_lottery'), get_permalink($product_id), $product_data -> get_title()); ?></p>

<?php do_action('woocommerce_email_footer');