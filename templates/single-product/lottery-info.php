<?php
/**
 * Lottery info template
 *
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly
global $product, $post;

$min_tickets                = $product->get_min_tickets();
$max_tickets                = $product->get_max_tickets();
$lottery_participants_count = !empty($product->get_lottery_participants_count()) ? $product->get_lottery_participants_count() : '0';
$lottery_dates_to           = $product->get_lottery_dates_to();
$lottery_dates_from         = $product->get_lottery_dates_from();
$lottery_num_winners        = $product->get_lottery_num_winners();

/*
	Author : Adonis
*/
$lottery_auto_pick = $product->get_lottery_auto_pick();
$lottery_alphabet_line = $product->get_lottery_alphabet_line();
$lottery_number_range_to = $product->get_lottery_number_range_to();
$lottery_user_pick = $product->get_lottery_user_pick();
$lottery_bonus_number_range_from = $product->get_lottery_bonus_number_range_from();
$lottery_bonus_number_range_to = $product->get_lottery_bonus_number_range_to();
$lottery_bonus_number_type = $product->get_lottery_bonus_number_type();
$lottery_bonus_number_popup = $product->get_lottery_bonus_number_popup();
$lottery_bonus_number_name = $product->get_lottery_bonus_number_name();
$lottery_bonus_allow_pick = $product->get_lottery_bonus_allow_pick();
$lottery_bonus_enabled = $product->get_lottery_bonus_enabled();

?>
<p class="lottery-end"><?php echo __( 'Lottery ends:', 'wc_lottery' ); ?> <?php echo  date_i18n( get_option( 'date_format' ),  strtotime( $lottery_dates_to ));  ?>  <?php echo  date_i18n( get_option( 'time_format' ),  strtotime( $lottery_dates_to ));  ?> <br />
        <?php printf(__('Timezone: %s','wc_lottery') , get_option('timezone_string') ? get_option('timezone_string') : __('UTC+','wc_lottery').get_option('gmt_offset')) ?>
</p>

<?php if($min_tickets &&($min_tickets > 0)  ) : ?>
        <p class="min-pariticipants"><?php  printf( __( "This lottery has a minimum of %d tickets", 'wc_lottery'), $min_tickets ); ?></p>
<?php endif; ?>	

<?php if( $max_tickets  &&( $max_tickets > 0 )  ) : ?>
        <p class="max-pariticipants"><?php  printf( __( "This lottery is limited to %s tickets", 'wc_lottery' ),$max_tickets ) ; ?></p>
<?php endif; ?>

<p class="cureent-participating"> <?php _e( 'Tickets sold:', 'wc_lottery' )?> <?php echo  $lottery_participants_count ;?></p>

<?php if(  $lottery_num_winners > 0  ) : ?>

<p class="max-pariticipants"><?php  printf( _n( "This lottery will have %d winner" , "This lottery will have %d winners", $lottery_num_winners , 'wc_lottery' ) ,$lottery_num_winners ) ; ?></p>

<?php endif; ?>

<?php
	echo '<h3>'. esc_html__('Pick your ticket number(s)' , 'wc-lottery-pn' ) . '</h3>';

	echo '<div class="wc_lottery_pn"';
	$max_tickets_per_user = $product->get_max_tickets_per_user() ? $product->get_max_tickets_per_user() : false;
	if ( ! is_user_logged_in() &&  $max_tickets_per_user > 0  && $max_tickets_per_user != $product->get_max_tickets() ) {
		echo 'class=" guest"';
	}
	echo '>';

	do_action('wc_lottery_before_ticket_numbers');

	echo '<div class="ticket_number_head">';
	echo '<div class="tn_head">
			<div alt="f182" class="dashicons dashicons-trash reset_ticket" id="reset_ticket"></div>';
	if($lottery_auto_pick == "yes")
		echo '<button id="auto_pick_btn" class="auto_pick_btn">Auto Pick</button>';
	echo '</div>Pick ' . $lottery_user_pick . ' Number(s)</div>';
	echo '<div class="tickets_numbers_panel" data-product-id="' . $product->get_id() . '">';

	if($lottery_alphabet_line != 1)
	{
		for($i=0;$i<$lottery_alphabet_line * 10;$i++)
		{
			if($i % $lottery_alphabet_line == 0)
				echo '<p class="ticket_number_line">';
			echo '<span class="ticket_number_span">';
			echo '<input class="ticket_number" readonly="readonly" value="' . (int)($i/$lottery_alphabet_line) . '" alphabet="' . chr(65+($i % $lottery_alphabet_line)) . '">';
			echo '<label class="ticket_alphabet">' . chr(65+($i % $lottery_alphabet_line)) . '</label>';
			echo '</span>';
			if($i % $lottery_alphabet_line == 5)
				echo '</p>';
		}
		echo '</div>';
	} else {
		for($i=0;$i<$lottery_number_range_to;$i++)
		{
			if($i % 6 == 0)
				echo '<p class="ticket_number_line">';
			echo '<span class="ticket_number_span">';
			echo '<input class="ticket_number" readonly="readonly" value="' . ($i + 1) . '" alphabet="A">';
			echo '<label class="ticket_alphabet">A</label>';
			echo '</span>';
			if($i % 6 == 5)
				echo '</p>';
		}
		echo '</div>';
	}
	if($lottery_bonus_enabled != "0")
	{
		echo '<div class="bonus_panel">';
		echo '<div class="bonus_wrapper">';
		if($lottery_bonus_number_popup == "yes")
		{
			echo '<div class="bonus_plus_button">
					<input class="ticket_number" value="+" readonly="readonly">
					<label class="bonus_ticket_alphabet">L</label>
				</div>';
		}

		echo '<p>';
		echo '<b>' . $lottery_bonus_number_name . '</b>';
		echo '<br>';
		echo 'Pick ' . $lottery_bonus_allow_pick . ' Number(s)';
		echo '</p>';
		echo '</div>';
		if($lottery_bonus_number_popup == "yes")
		{
			echo '<div style="display : flex; justify-content:flex-end;">';
			echo '<div class="bonus_plus_button">
					<input class="show_bonus_number" value="" readonly="readonly" id="show_bonus_number1">
					<label class="bonus_ticket_alphabet">L</label>
				</div>';
			if($lottery_bonus_allow_pick == 2)
			{
				echo '<div class="bonus_plus_button">
					<input class="show_bonus_number" value="" readonly="readonly" id="show_bonus_number2">
					<label class="bonus_ticket_alphabet">L</label>
				</div>';
			}
			echo '</div>';
		}
		echo '</div>';

		if($lottery_bonus_number_popup == "no")
		{
			echo '<div class="bonus_number_panel">';
			for($i=$lottery_bonus_number_range_from;$i<=$lottery_bonus_number_range_to;$i++)
			{
				if(($i - $lottery_bonus_number_range_from) % 6 == 0)
					echo '<p class="ticket_number_line">';
				echo '<span class="ticket_number_span">';
				if($lottery_bonus_number_type == 1)
					echo '<input class="ticket_number" readonly="readonly" value="' . ($i - $lottery_bonus_number_range_from + 1) . '" alphabet="L">';
				else if($lottery_bonus_number_type == 2)
				{
					if($i - $lottery_bonus_number_range_from < 10)
						echo '<input class="ticket_number" readonly="readonly" value="0' . ($i - $lottery_bonus_number_range_from) . '" alphabet="L">';
					else
						echo '<input class="ticket_number" readonly="readonly" value="' . ($i - $lottery_bonus_number_range_from) . '" alphabet="L">';
				}
				echo '<label class="ticket_alphabet">L</label>';
				echo '</span>';
				if(($i - $lottery_bonus_number_range_from) % 6 == 5)
					echo '</p>';
			}
			echo '</div>';
		}
	}
// 	echo '<button id="participate_btn" class="participate_btn">Participate now for $</button>';
	echo '<input type="hidden" id="user_pick_per_line" value="' . $lottery_user_pick . '">';
	echo '<input type="hidden" id="allow_pick_per_line" value="' . $lottery_bonus_allow_pick . '">';
	echo '<input type="hidden" id="alphabet_line_value" value="' . $lottery_alphabet_line . '">';
	echo '<input type="hidden" id="bonus_number_range_from" value="' . $lottery_bonus_number_range_from . '">';
	echo '<input type="hidden" id="bonus_number_range_to" value="' . $lottery_bonus_number_range_to . '">';
	echo '<input type="hidden" id="bonus_number_type" value="' . $lottery_bonus_number_type . '">';
	echo '<input type="hidden" id="bonus_number_popup" value="' . $lottery_bonus_number_popup . '">';
	echo '<input type="hidden" id="number_range_to" value="' . $lottery_number_range_to . '">';
	echo '<input type="hidden" id="bonus_number_name" value="' . $lottery_bonus_number_name . '">';
	echo '<input type="hidden" id="bonus_enabled" value="' . $lottery_bonus_enabled . '">';
	echo '</div>';
?>

<!-- The Modal -->
<div id="myModal" class="modal">

  <!-- Modal content -->
  <div class="modal-content">
    <span class="close">&times;</span>
    <?php
	    echo '<div class="bonus_panel">
			<p>';
		echo '<b>' . $lottery_bonus_number_name . '</b>';
		echo '<br>';
		echo 'Pick ' . $lottery_bonus_allow_pick . ' Number(s)';
		echo '</p></div>';
    	for($i=$lottery_bonus_number_range_from;$i<=$lottery_bonus_number_range_to;$i++)
		{
			if(($i - $lottery_bonus_number_range_from) % 6 == 0)
				echo '<p class="ticket_number_line">';
			echo '<span class="ticket_number_span">';
			if($lottery_bonus_number_type == 1)
				echo '<input class="ticket_number" readonly="readonly" value="' . ($i - $lottery_bonus_number_range_from + 1) . '" alphabet="L">';
			else if($lottery_bonus_number_type == 2)
			{
				if($i - $lottery_bonus_number_range_from < 10)
					echo '<input class="ticket_number" readonly="readonly" value="0' . ($i - $lottery_bonus_number_range_from) . '" alphabet="L">';
				else
					echo '<input class="ticket_number" readonly="readonly" value="' . ($i - $lottery_bonus_number_range_from) . '" alphabet="L">';
			}
			echo '<label class="ticket_alphabet">L</label>';
			echo '</span>';
			if(($i - $lottery_bonus_number_range_from) % 6 == 5)
				echo '</p>';
		}
	?>
  </div>

</div>

<script type="text/javascript">
	// Get the modal
	var modal = document.getElementById("myModal");

	// Get the button that opens the modal
	var btn = document.getElementById("myBtn");

	// Get the <span> element that closes the modal
	var span = document.getElementsByClassName("close")[0];

	// When the user clicks on <span> (x), close the modal
	span.onclick = function() {
	  modal.style.display = "none";
	}

	// When the user clicks anywhere outside of the modal, close it
	window.onclick = function(event) {
	  if (event.target == modal) {
	    modal.style.display = "none";
	  }
	}
	jQuery('.ticket_number').on('click', function (event) {
		//jQuery('.single_add_to_cart_button').removeAttr('disabled'); // when select all numbers
		//jQuery('.single_add_to_cart_button').attr({'disabled': 'disabled'}); //when not select all numbers
		if(event.target.value == "+")
		{
			modal.style.display = "block";
		} else {
			if(!jQuery(this).hasClass("selected_ticket_number"))
			{
				var per_line = jQuery("#user_pick_per_line").val();
				if(jQuery(this).next().html() == "L")
					per_line = jQuery("#allow_pick_per_line").val();
				var re = new RegExp(jQuery(this).next().html(), "g");
				if(jQuery("#selected_ticket_number").val().match(re) != null)
					if(jQuery("#selected_ticket_number").val().match(re).length >= parseInt(per_line))
					{
						return;
					}
				jQuery(this).addClass("selected_ticket_number");
				jQuery(this).next().addClass("selected_ticket_alphabet");
				jQuery("#selected_ticket_number").val(jQuery("#selected_ticket_number").val() + jQuery(this).next().html() + "." +  jQuery(this).val() + ",");
				if(jQuery(this).next().html() == "L")
				{
					for(var i=1;i<=per_line;i++)
					{
						if(jQuery("#show_bonus_number" + i).val() == "")
						{
							jQuery("#show_bonus_number" + i).val(jQuery(this).val());
							jQuery("#show_bonus_number" + i).addClass("show_selected_bonus_number");
							jQuery("#show_bonus_number" + i).next().addClass("selected_ticket_alphabet");
							break;
						}
					}
					if(jQuery("#selected_ticket_number").val().match(re).length == parseInt(per_line))
					{
						modal.style.display = "none";
					}
				}
			} else {
				jQuery(this).removeClass("selected_ticket_number");
				jQuery(this).next().removeClass("selected_ticket_alphabet");
				var per_line = jQuery("#allow_pick_per_line").val();
				for(var i=1;i<=per_line;i++)
				{
					if(jQuery("#show_bonus_number" + i).val() == jQuery(this).val())
					{
						jQuery("#show_bonus_number" + i).val("");
						jQuery("#show_bonus_number" + i).removeClass("show_selected_bonus_number");
						jQuery("#show_bonus_number" + i).next().removeClass("selected_ticket_alphabet");
						break;
					}
				}
				jQuery("#selected_ticket_number").val(jQuery("#selected_ticket_number").val().replace(jQuery(this).next().html() + "." +  jQuery(this).val() + ",", ""));
			}

			var total_allow_ticket = parseInt(jQuery("#user_pick_per_line").val()) * parseInt(jQuery("#alphabet_line_value").val());
			if(jQuery("#bonus_enabled").val() != "0")
				total_allow_ticket += parseInt(jQuery("#allow_pick_per_line").val());
			if(jQuery("#selected_ticket_number").val().split(",").length - 1 == total_allow_ticket)
			{
				jQuery(".single_add_to_cart_button").prop("disabled", false);
			} else{
				jQuery(".single_add_to_cart_button").prop("disabled", true);
			}
		}
    });

    jQuery('#participate_btn').on('click', function (event) {
    	var ajaxurl = '<?php echo esc_js( admin_url( 'admin-ajax.php', 'relative' ) ); ?>';
		jQuery.ajax({
            type: "post",
            url: ajaxurl,
            data: {action: "participate_lottery", numbers:jQuery("#selected_ticket_number").val()},
            success: function (response) {
            	if(response == -1){
            		alert("Please login");
            	} else {
            		location.reload();
            	}
            }
        });
    });

    jQuery('#reset_ticket').on('click', function (event) {
    	reset_numbers();
    	jQuery(".single_add_to_cart_button").prop("disabled", true);
    });

    jQuery('#auto_pick_btn').on('click', function (event) {
    	reset_numbers();
    	var alphabet_line = jQuery("#alphabet_line_value").val();
    	if(alphabet_line == 1)
    	{
    		var numbers = [];
    		for(var i=1;i<=parseInt(jQuery("#number_range_to").val());i++)
	    	{
	    		numbers.push(i);
	    	}
    		for(var i=0;i<jQuery("#user_pick_per_line").val();i++)
    		{
    			var rand_value = Math.floor(Math.random() * numbers.length);
    			jQuery("input[value='" + numbers[rand_value] + "'][alphabet='A']").addClass("selected_ticket_number");
    			jQuery("input[value='" + numbers[rand_value] + "'][alphabet='A']").next().addClass("selected_ticket_alphabet");
    			jQuery("#selected_ticket_number").val(jQuery("#selected_ticket_number").val() + "A" + "." +  numbers[rand_value] + ",");
    			numbers.splice(rand_value, 1);
    		}
    	} else {
    		for(var i=0;i<alphabet_line;i++)
    		{
    			var rand_value = Math.floor(Math.random() * 10);
    			jQuery("input[value='" + rand_value + "'][alphabet='" + String.fromCharCode(65 + i) + "']").addClass("selected_ticket_number");
    			jQuery("input[value='" + rand_value + "'][alphabet='" + String.fromCharCode(65 + i) + "']").next().addClass("selected_ticket_alphabet");
    			jQuery("#selected_ticket_number").val(jQuery("#selected_ticket_number").val() + String.fromCharCode(65 + i) + "." +  rand_value + ",");
    		}
    	}
    	if(jQuery("#bonus_enabled").val() != "0")
    	{
    		var allow_pick = jQuery("#allow_pick_per_line").val();
	    	var bonus_number_type = jQuery("#bonus_number_type").val();
	    	var bonus_number_popup = jQuery("#bonus_number_popup").val();
	    	var numbers = [];
	    	for(var i=parseInt(jQuery("#bonus_number_range_from").val());i<=parseInt(jQuery("#bonus_number_range_to").val());i++)
	    	{
	    		var num = i;
	    		if(bonus_number_type == 2 && i<10)
	    			num = "0" + i;
	    		numbers.push(num);
	    	}
	    	for(var i=1;i<=allow_pick;i++)
	    	{
	    		var rand_value = Math.floor(Math.random() * numbers.length);
	    		jQuery("input[value='" + numbers[rand_value] + "'][alphabet='L']").addClass("selected_ticket_number");
				jQuery("input[value='" + numbers[rand_value] + "'][alphabet='L']").next().addClass("selected_ticket_alphabet");
				jQuery("#selected_ticket_number").val(jQuery("#selected_ticket_number").val() + "L" + "." +  numbers[rand_value] + ",");

				if(bonus_number_popup == "yes")
				{
					jQuery("#show_bonus_number" + i).val(numbers[rand_value]);
					jQuery("#show_bonus_number" + i).addClass("show_selected_bonus_number");
					jQuery("#show_bonus_number" + i).next().addClass("selected_ticket_alphabet");
				}
				numbers.splice(rand_value, 1);
	    	}
    	}
    	jQuery(".single_add_to_cart_button").removeAttr('disabled');
    });

    function reset_numbers()
    {
    	jQuery(".ticket_number").removeClass("selected_ticket_number");
		jQuery(".ticket_alphabet").removeClass("selected_ticket_alphabet");
		per_line = jQuery("#allow_pick_per_line").val();
		for(var i=1;i<=per_line;i++)
		{
			jQuery("#show_bonus_number" + i).val("");
			jQuery("#show_bonus_number" + i).removeClass("show_selected_bonus_number");
			jQuery("#show_bonus_number" + i).next().removeClass("selected_ticket_alphabet");
		}
		jQuery("#selected_ticket_number").val("");
    }
</script>