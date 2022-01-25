<?php
/**
 * WooCommerce Random Loteries Widget
 *
 * @author 		WooThemes
 * @version 	1.0.0
 * @extends 	WP_Widget
 * 
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

class WC_Lottery_Widget_Random_Loteries extends WP_Widget {

	/**
	 * Constructor
	 *
	 * @access public
	 * @return void
     * 
	 */
	function __construct() {
		$this->id_base = 'woocommerce_random_lotteries';
		$this->name    = __( 'WooCommerce Random lotteries', 'wc_lottery' );
		$this->widget_options = array(
			'classname'   => 'woocommerce widget_random_lotteries',
			'description' => __( 'Display a list of random lotteries on your site.', 'wc_lottery' ),
		);

		parent::__construct( $this->id_base, $this->name, $this->widget_options );
	}

	/**
	 * Widget function
	 *
	 * @see WP_Widget
	 * @access public
	 * @param array $args
	 * @param array $instance
	 * @return void
     * 
	 */
	function widget( $args, $instance ) {
		global $woocommerce;

		// Use default title as fallback
		$title = ( '' === $instance['title'] ) ? __('Random lotteries', 'wc_lottery' ) : $instance['title'];
		$title = apply_filters('widget_title', $title, $instance, $this->id_base);

		// Setup product query
		$query_args = array(
			'post_type'      => 'product',
			'post_status'    => 'publish',
			'posts_per_page' => $instance['number'],
			'orderby'        => 'rand',
			'no_found_rows'  => 1
		);

		$query_args['meta_query'] = array();
	    $query_args['meta_query'][] = $woocommerce->query->stock_status_meta_query();
	    $query_args['meta_query']   = array_filter( $query_args['meta_query'] );		
		$query_args['tax_query'] = array(array('taxonomy' => 'product_type' , 'field' => 'slug', 'terms' => 'lottery')); 
		$query_args['is_lottery_archive'] = TRUE; 	

		$query = new WP_Query( $query_args );

		if ( $query->have_posts() ) {
			$hide_time = empty( $instance['hide_time'] ) ? 0 : 1;
			echo $args['before_widget'];

			if ( '' !== $title ) {
				echo $args['before_title'], $title, $args['after_title'];
			} ?>

			<ul class="product_list_widget">
				<?php while ($query->have_posts()) : $query->the_post(); global $product;
				$time = '';
				$timetext = __('Time left', 'wc_lottery');
				$datatime = $product->get_seconds_remaining();
				$product_id = $product->get_id();
				if(!$product->is_started()){
					$timetext = __('Starting in', 'wc_lottery');
					$datatime = $product->get_seconds_to_lottery();
				}
				if($hide_time != 1 && !$product->is_closed()){
					$futureclass = ( $product->is_closed() === FALSE ) && ($product->is_started() === FALSE ) ? 'future' : '';
					$time = '<span class="time-left">'.apply_filters('time_text',$timetext,$product_id).'</span>
					<div class="lottery-time-countdown ' . $futureclass . ' " data-time="'.$datatime.'" data-lotteryid="'.$product_id.'" data-format="'.get_option( 'simple_lottery_countdown_format' ).'"></div>';
				}
				if($product->is_closed())
						$time = '<span class="has-finished">'.apply_filters('time_text',__('Lottery finished', 'wc_lottery'),$product_id).'</span>';
				 ?>
					<li>
						<a href="<?php the_permalink() ?>">
							<?php
								if ( has_post_thumbnail() )
									the_post_thumbnail( 'shop_thumbnail' );
								else
									echo wc_placeholder_img( 'shop_thumbnail' );
							?>
							<?php the_title() ?>
						</a>
						<?php echo $product->get_price_html() ?>
						<?php echo $time ?>
					</li>
				<?php endwhile; ?>
			</ul>

			<?php
			echo $args['after_widget'];
		}
		wp_reset_postdata();
	}

	/**
	 * Update function
	 *
	 * @see WP_Widget->update
	 * @access public
	 * @param array $new_instance
	 * @param array $old_instance
	 * @return array
     * 
	 */
	function update( $new_instance, $old_instance ) {
		$instance = array(
			'title'           => strip_tags($new_instance['title']),
			'number'          => absint( $new_instance['number'] ),
			'hide_time'       => empty( $new_instance['hide_time'] ) ? 0 : 1,
		);
		return $instance;
	}

	/**
	 * Form function
	 *
	 * @see WP_Widget->form
	 * @access public
	 * @param array $instance
	 * @return void
     * 
	 */
	function form( $instance ) {
		$title           = isset( $instance['title'] ) ? $instance['title'] : '';
		$number          = isset( $instance['number'] ) ? (int) $instance['number'] : 5;
		$hide_time = empty( $instance['hide_time'] ) ? 0 : 1;
		?>
		<p>
			<label for="<?php echo $this->get_field_id( 'title' ) ?>"><?php _e( 'Title:', 'wc_lottery' ) ?></label>
			<input class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'title' ) ) ?>" name="<?php echo esc_attr( $this->get_field_name('title') ) ?>" type="text" value="<?php echo esc_attr( $title ) ?>" />
		</p>
		<p>
			<label for="<?php echo $this->get_field_id( 'number' ) ?>"><?php _e( 'Number of lotteries to show:', 'wc_lottery' ) ?></label>
			<input id="<?php echo esc_attr( $this->get_field_id( 'number' ) ) ?>" name="<?php echo esc_attr( $this->get_field_name('number') ) ?>" type="text" value="<?php echo esc_attr( $number ) ?>" size="3" />
		</p>
		<p><input type="checkbox" class="checkbox" id="<?php echo esc_attr( $this->get_field_id('hide_time') ); ?>" name="<?php echo esc_attr( $this->get_field_name('hide_time') ); ?>"<?php checked( $hide_time ); ?> />
		<label for="<?php echo $this->get_field_id('hide_time'); ?>"><?php _e( 'Hide time left', 'wc_lottery' ); ?></label></p>
		<?php
	}
}