<?php
/**
 * Lottery history tab template
 * 
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

global $woocommerce, $post, $product;

$heading = esc_html(  __( 'Lottery history', 'wc_lottery' )  );
$lottery_winers = get_post_meta($post->ID, '_lottery_winners');
$users_names = '';

?>

<h2><?php echo $heading; ?></h2>


<?php if(($product->is_closed() === TRUE ) and ($product->is_started() === TRUE )) : ?>
    
	<p><?php _e('Lottery has finished', 'wc_lottery') ?></p>
	<?php if ($product->get_lottery_fail_reason() == '1'){
		 _e('Lottery failed because there were no minimum users', 'wc_lottery');
	} else{

    	
    	if (count($lottery_winers) > 1) { ?>
           <p><?php _e('Lottery winners are', 'wc_lottery') ?>: <?php foreach ($lottery_winers as $winner_id) {
            if( intval( $winner_id ) > 0) {
                    $users_names .= "<span>";
                    $users_names .= get_userdata($winner_id)->display_name;
                    $users_names .= "</span>, ";
                }
            } ?><?php echo rtrim( $users_names , ', '); ?></p>
        <?php } elseif(count($lottery_winers) == 1) { ?>
    		<p><?php _e('Lottery winner is', 'wc_lottery') ?>: <span><?php echo get_userdata($lottery_winers[0])->display_name ?></span></p>
    	<?php } 
    } ?>
    						
<?php endif; ?>	

<table>
    <thead>
        <tr>
            <th><?php _e('Date', 'wc_lottery') ?></th>
            <th><?php _e('User', 'wc_lottery') ?></th>
        </tr>
    </thead>
    <?php 
        $lottery_history = $product->lottery_history();
        
        if( $lottery_history ) {
        
            foreach ($lottery_history as $history_value) {
                echo "<tr>";
                echo "<td class='date'>".date_i18n( get_option( 'date_format' ), strtotime( $history_value->date )).' '.date_i18n( get_option( 'time_format' ), strtotime( $history_value->date ))."</td>";
                echo $history_value->userid ? "<td class='username'>".get_userdata($history_value->userid)->display_name."</td>" : '';
                echo "</tr>";
            }        
        }
    ?>    
    <tr class="start">            
    <?php 
        $lottery_dates_to = $product->get_lottery_dates_from();
        if ($product->is_started() === TRUE ){                
            echo '<td class="date">'.date_i18n( get_option( 'date_format' ), strtotime( $lottery_dates_to )).' '.date_i18n( get_option( 'time_format' ),  strtotime( $lottery_dates_to )).'</td>';             
            echo '<td class="started">';
            echo apply_filters('lottery_history_started_text', __( 'Lottery started', 'wc_lottery' ), $product);
            echo '</td>';
        } else {
            echo '<td class="date">'.date_i18n( get_option( 'date_format' ), strtotime( $lottery_dates_to )).' '.date_i18n( get_option( 'time_format' ),  strtotime( $lottery_dates_to )).'</td>';                 
            echo '<td  class="starting">';
            echo apply_filters('lottery_history_starting_text', __( 'Lottery starting', 'wc_lottery' ), $product);
            echo '</td>' ;
        }?>
    </tr>
</table>