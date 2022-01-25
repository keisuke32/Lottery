<?php
/**
 * Lottery badge template
 *
 * @author 	WooThemes
 * @package 	WooCommerce/Templates
 * @version     1.6.4
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

global $product;

?>

<?php if ( method_exists( $product, 'get_type') && $product->get_type() == 'lottery' ) : ?>
	<?php echo apply_filters('woocommerce_lottery_bage', '<span class="lottery-bage"></span>',  $product); ?>
<?php endif;