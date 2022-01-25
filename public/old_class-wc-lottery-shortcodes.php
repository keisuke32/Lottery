<?php
/**
 * Wc lotteries Shortcode
 *
 */

class WC_Shortcode_Lottery extends WC_Shortcodes {

	public function __construct() {
			// Regular shortcodes
			add_shortcode( 'lotteries', array( $this, 'lotteries' ) );
			add_shortcode( 'recent_lotteries', array( $this, 'recent_lotteries' ) );
			add_shortcode( 'featured_lotteries', array( $this, 'featured_lotteries' ) );
			add_shortcode( 'ending_soon_lotteries', array( $this, 'ending_soon_lotteries' ) );
			add_shortcode( 'future_lotteries', array( $this, 'future_lotteries' ) );
			add_shortcode( 'finished_lotteries', array( $this, 'finished_lotteries' ) );
			add_shortcode( 'my_lotteries', array( $this, 'my_lotteries' ) );
			add_shortcode( 'winned_lotteries', array( $this, 'winned_lotteries' ) );
			add_shortcode( 'lotteries_winners', array( $this, 'lotteries_winners' ) );
	}
	/**
	 * Featured lotteries shortcode
	 *
	 * @access public
	 * @param array $atts
	 * @return string
	 */
	public function featured_lotteries( $atts ) {

		global $woocommerce_loop;

		extract(shortcode_atts(array(
			'per_page' 	=> '12',
			'columns' 	=> '4',
			'orderby' => 'date',
			'order' => 'desc'
		), $atts));

		$args = array(
			'post_type'	=> 'product',
			'post_status' => 'publish',
			'ignore_sticky_posts'	=> 1,
			'posts_per_page' => $per_page,
			'orderby' => $orderby,
			'order' => $order,
			'tax_query' => array(array('taxonomy' => 'product_type' , 'field' => 'slug', 'terms' => 'lottery')),
			'is_lottery_archive' => TRUE,

		);

		if ( version_compare( WC_VERSION, '2.7', '<' ) ) {
			$args['meta_query'][] = array(
				'key' => '_featured',
				'value' => 'yes'
			);
			$args['meta_query'][] = array(
					'key' => '_visibility',
					'value' => array('catalog', 'visible'),
					'compare' => 'IN'
				);
		} else {
			$args['tax_query'][] = array(
					'taxonomy' => 'product_visibility',
					'field'    => 'name',
					'terms'    => 'featured',
				);
		}


		ob_start();

		$products = new WP_Query( $args );

		$woocommerce_loop['columns'] = $columns;

		if ( $products->have_posts() ) : ?>

			<?php woocommerce_product_loop_start(); ?>

				<?php while ( $products->have_posts() ) : $products->the_post(); ?>

					<?php wc_get_template_part( 'content', 'product' ); ?>

				<?php endwhile; // end of the loop. ?>

			<?php woocommerce_product_loop_end(); ?>

		<?php endif;

		wp_reset_postdata();

		return '<div class="woocommerce">' . ob_get_clean() . '</div>';
	}

	/**
	 * Recent lotteries shortcode
	 *
	 * @access public
	 * @param array $atts
	 * @return string
	 */
	public function recent_lotteries( $atts ) {

		global $woocommerce_loop, $woocommerce;

		extract(shortcode_atts(array(
			'per_page' 	=> '12',
			'columns' 	=> '4',
			'orderby' => 'date',
			'order' => 'desc'
		), $atts));

		$meta_query = $woocommerce->query->get_meta_query();

		$args = array(
			'post_type'	=> 'product',
			'post_status' => 'publish',
			'ignore_sticky_posts'	=> 1,
			'posts_per_page' => $per_page,
			'orderby' => $orderby,
			'order' => $order,
			'meta_query' => $meta_query,
			'tax_query' => array(array('taxonomy' => 'product_type' , 'field' => 'slug', 'terms' => 'lottery')),
			'is_lottery_archive' => TRUE
		);

		ob_start();

		$products = new WP_Query( $args );

		$woocommerce_loop['columns'] = $columns;

		if ( $products->have_posts() ) : ?>

			<?php woocommerce_product_loop_start(); ?>

				<?php while ( $products->have_posts() ) : $products->the_post(); ?>

					<?php wc_get_template_part( 'content', 'product' ); ?>

				<?php endwhile; // end of the loop. ?>

			<?php woocommerce_product_loop_end(); ?>
	   <?php else : ?>
			<?php wc_get_template( 'loop/no-products-found.php' ); ?>

		<?php endif;

		wp_reset_postdata();

		return '<div class="woocommerce">' . ob_get_clean() . '</div>';
	}

	/**
	 * List multiple lotteries shortcode
	 *
	 * @access public
	 * @param array $atts
	 * @return string
	 */
	public function lotteries( $atts ) {
		global $woocommerce_loop;

		if (empty($atts)) return;

		extract(shortcode_atts(array(
			'columns' 	=> '4',
			'orderby'   => 'title',
			'order'     => 'asc'
			), $atts));

		$args = array(
			'post_type'	=> 'product',
			'post_status' => 'publish',
			'ignore_sticky_posts'	=> 1,
			'orderby' => $orderby,
			'order' => $order,
			'posts_per_page' => -1,
			'tax_query' => array(array('taxonomy' => 'product_type' , 'field' => 'slug', 'terms' => 'lottery')),
			'is_lottery_archive' => TRUE,

		);


		if ( version_compare( WC_VERSION, '2.7', '<' ) ) {
			$args['meta_query'][] = array(
					'key' => '_visibility',
					'value' => array('catalog', 'visible'),
					'compare' => 'IN'
				);
		} else {
			$product_visibility_terms  = wc_get_product_visibility_term_ids();
			$product_visibility_not_in = $product_visibility_terms['exclude-from-catalog'];
			if ( ! empty( $product_visibility_not_in ) ) {
						$tax_query[] = array(
							'taxonomy' => 'product_visibility',
							'field'    => 'term_taxonomy_id',
							'terms'    => $product_visibility_not_in,
							'operator' => 'NOT IN',
						);
					}

		}

		if(isset($atts['skus'])){
			$skus = explode(',', $atts['skus']);
			$skus = array_map('trim', $skus);
			$args['meta_query'][] = array(
				'key' 		=> '_sku',
				'value' 	=> $skus,
				'compare' 	=> 'IN'
			);
		}

		if(isset($atts['ids'])){
			$ids = explode(',', $atts['ids']);
			$ids = array_map('trim', $ids);
			$args['post__in'] = $ids;
		}

		ob_start();

		$products = new WP_Query( $args );

		$woocommerce_loop['columns'] = $columns;

		if ( $products->have_posts() ) : ?>

			<?php woocommerce_product_loop_start(); ?>

				<?php while ( $products->have_posts() ) : $products->the_post(); ?>

					<?php wc_get_template_part( 'content', 'product' ); ?>

				<?php endwhile; // end of the loop. ?>

			<?php woocommerce_product_loop_end(); ?>
	   <?php else : ?>
		<?php wc_get_template( 'loop/no-products-found.php' ); ?>

		<?php endif;

		wp_reset_postdata();

		return '<div class="woocommerce">' . ob_get_clean() . '</div>';
	}

	/**
	 * Ending soon lotteries lotteries shortcode
	 *
	 * @access public
	 * @param array $atts
	 * @return string
	 */
	public function ending_soon_lotteries( $atts ) {

		global $woocommerce_loop, $woocommerce;

		extract(shortcode_atts(array(
			'per_page' 	=> '12',
			'columns' 	=> '4',
			'future' =>'yes',
			'order' => 'asc'
		), $atts));

		$meta_query = $woocommerce->query->get_meta_query();
		$meta_query []=   array(
							'key'     => '_lottery_closed',
							'compare' => 'NOT EXISTS',
							);
		

		$args = array(
			'post_type'	=> 'product',
			'post_status' => 'publish',
			'ignore_sticky_posts'	=> 1,
			'posts_per_page' => $per_page,
			'orderby' => 'meta_value',
			'order' => $order,
			'meta_query' => $meta_query,
			'tax_query' => array(array('taxonomy' => 'product_type' , 'field' => 'slug', 'terms' => 'lottery')),
			'meta_key' => '_lottery_dates_to',
			'is_lottery_archive' => TRUE
		);

		if($future == 'yes'){
			$args [ 'show_future_lotteries' ] = true;
		} else{
			$args [ 'show_future_lotteries' ] = false;
		}


		ob_start();

		$products = new WP_Query( $args );

		$woocommerce_loop['columns'] = $columns;

		if ( $products->have_posts() ) : ?>

			<?php woocommerce_product_loop_start(); ?>

				<?php while ( $products->have_posts() ) : $products->the_post(); ?>

					<?php wc_get_template_part( 'content', 'product' ); ?>

				<?php endwhile; // end of the loop. ?>

			<?php woocommerce_product_loop_end(); ?>
	   <?php else : ?>
			<?php wc_get_template( 'loop/no-products-found.php' ); ?>

		<?php endif;

		wp_reset_postdata();

		return '<div class="woocommerce">' . ob_get_clean() . '</div>';
	}
	/**
	 * Future lotteries shortcode
	 *
	 * @access public
	 * @param array $atts
	 * @return string
	 */
	public function future_lotteries( $atts ) {

		global $woocommerce_loop, $woocommerce;

		extract(shortcode_atts(array(
			'per_page' 	=> '12',
			'columns' 	=> '4',

			'order' => 'desc'
		), $atts));

		$meta_query = $woocommerce->query->get_meta_query();
		 $meta_query []=   array(
							'key'     => '_lottery_closed',
							'compare' => 'NOT EXISTS',
							);

		$meta_query []=  array( 'key' => '_lottery_started',
									'value'=> '0',);
		$args = array(
			'post_type'	=> 'product',
			'post_status' => 'publish',
			'ignore_sticky_posts'	=> 1,
			'posts_per_page' => $per_page,
			'orderby' => 'meta_value',
			'order' => $order,
			'meta_query' => $meta_query,
			'tax_query' => array(array('taxonomy' => 'product_type' , 'field' => 'slug', 'terms' => 'lottery')),
			'meta_key' => '_lottery_dates_to',
			'is_lottery_archive' => TRUE,
			'show_future_lotteries' => true,
		);



		ob_start();

		$products = new WP_Query( $args );

		$woocommerce_loop['columns'] = $columns;

		if ( $products->have_posts() ) : ?>

			<?php woocommerce_product_loop_start(); ?>

				<?php while ( $products->have_posts() ) : $products->the_post(); ?>

					<?php wc_get_template_part( 'content', 'product' ); ?>

				<?php endwhile; // end of the loop. ?>

			<?php woocommerce_product_loop_end(); ?>
	   <?php else : ?>
			<?php wc_get_template( 'loop/no-products-found.php' ); ?>

		<?php endif;

		wp_reset_postdata();

		return '<div class="woocommerce">' . ob_get_clean() . '</div>';
	}
	/**
	 * Finished lotteries shortcode
	 *
	 * @access public
	 * @param array $atts
	 * @return string
	 */
	public function finished_lotteries( $atts ) {

		global $woocommerce_loop, $woocommerce;

		extract(shortcode_atts(array(
			'per_page' 	=> '12',
			'columns' 	=> '4',

			'order' => 'desc'
		), $atts));

		$meta_query = $woocommerce->query->get_meta_query();
		 $meta_query []= array(
			'key'     => '_lottery_closed',
			'compare' => 'EXISTS',
		);

		$meta_query []=  array( 
			'key' => '_lottery_started',
			'compare' => 'NOT EXISTS',
		);
		$args = array(
			'post_type'	=> 'product',
			'post_status' => 'publish',
			'ignore_sticky_posts'	=> 1,
			'posts_per_page' => $per_page,
			'orderby' => 'meta_value',
			'order' => $order,
			'meta_query' => $meta_query,
			'tax_query' => array(array('taxonomy' => 'product_type' , 'field' => 'slug', 'terms' => 'lottery')),
			'meta_key' => '_lottery_dates_to',
			'is_lottery_archive' => TRUE,
			'show_past_lottery' => TRUE
		);



		ob_start();

		$products = new WP_Query( $args );

		$woocommerce_loop['columns'] = $columns;

		if ( $products->have_posts() ) : ?>

			<?php woocommerce_product_loop_start(); ?>

				<?php while ( $products->have_posts() ) : $products->the_post(); ?>

					<?php wc_get_template_part( 'content', 'product' ); ?>

				<?php endwhile; // end of the loop. ?>

			<?php woocommerce_product_loop_end(); ?>
	   <?php else : ?>
			<?php wc_get_template( 'loop/no-products-found.php' ); ?>

		<?php endif;

		wp_reset_postdata();

		return '<div class="woocommerce">' . ob_get_clean() . '</div>';
	}

	/**
	 * My lotteries shortcode
	 *
	 * @access public
	 * @param array $atts
	 * @return void
	 *
	 */
	public static function my_lotteries( $atts ) {
		global $woocommerce, $wpdb;

		extract(shortcode_atts(array(
			'show_buy_it_now' 	=> 'false',
		), $atts));


		if ( is_user_logged_in() ) {

			$user_id  = get_current_user_id();
			$postids = get_user_meta($user_id, 'my_lotteries', false); 
			
			?>

			<div class="wc-lotterys active-lotteries clearfix woocommerce">
				<h2><?php _e( 'Active Lottery', 'wc_lottery' ); ?></h2>

				<?php

				$args = array(
					'post__in' 			=> $postids ,
					'post_type' 		=> 'product',
					'posts_per_page' 	=> '-1',
					'order'		=> 'ASC',
					'orderby'	=> 'meta_value',
					//'meta_key' 	=> '_lottery_dates_to',
					'tax_query' 		=> array(
						array(
							'taxonomy' => 'product_type',
							'field' => 'slug',
							'terms' => 'lottery'
						)
					),
					'meta_query' => array(

							array(
									'key'     => '_lottery_closed',
									'compare' => 'NOT EXISTS',
							),
					   ),
					'is_lottery_archive' => TRUE,
					'show_past_lottery' 	=>  FALSE,
				);
				$activeloop = new WP_Query( $args );

				if ( $activeloop->have_posts() && !empty($postids) ) {
					woocommerce_product_loop_start();
					while ( $activeloop->have_posts() ):$activeloop->the_post();
						wc_get_template_part( 'content', 'product' );
					endwhile;
					woocommerce_product_loop_end();

				} else {

					_e('<p class="no-active-lottery">You are not participating in lottery.</p>',"wc_lottery" );
				}

				//wp_reset_postdata();
			

				?>
			</div>
			<div class="wc-lotteries active-lotteries clearfix woocommerce"  >
				<h2><?php _e( 'Won lotteries', 'wc_lottery' ); ?></h2>

				<?php
				$lottery_closed_type[] = '2';
				if($show_buy_it_now == 'true'){
					$lottery_closed_type[] = '3';
				}

				$args = array(

					'post_type' 		=> 'product',
					'posts_per_page' 	=> '-1',
					'order'		=> 'ASC',
					'orderby'	=> 'meta_value',
					'meta_key' 	=> '_lottery_dates_to',
					'meta_query' => array(
						   array(
							   'key' => '_lottery_closed',
							   'value' => $lottery_closed_type,
							   'compare' => 'IN'
						   ),
							 array(
							   'key' => '_lottery_winners',
							   'value' => $user_id,
						   )
					   ),
					'show_past_lottery' 	=>  TRUE,
					'is_lottery_archive' => TRUE,
				);
				
				$winningloop = new WP_Query( $args );


				if ( $winningloop->have_posts() ) {
					   woocommerce_product_loop_start();
					while ( $winningloop->have_posts()): $winningloop->the_post() ;
						wc_get_template_part( 'content', 'product' );
					endwhile;
						woocommerce_product_loop_end();
				} else {
					_e('<p class="no-winned-lottery">You did not win any lotteries yet.</p>',"wc_lottery" );
				}

				wp_reset_postdata();
				echo "</div>";
			} else {
				echo '<div class="woocommerce"><p class="woocommerce-info">'.__('Please log in to see your lotteries.','wc_lottery' ).'</p></div>';
			}
		
		}



	/**
	 * Show lottery winners shortcode
	 *
	 * @access public
	 * @param array $atts
	 * @return string
	 */
	public function lotteries_winners ( $atts ) {
		global $woocommerce_loop, $woocommerce;

		extract(shortcode_atts(array(
			'per_page' 	=> '20',
			'order' => 'desc'
		), $atts));

		$meta_query[] = $woocommerce->query->visibility_meta_query();
		$meta_query []= array(
							   'key' => '_lottery_closed',
							   'value' => '2'    );
		$args = array(
			'post_type'	=> 'product',
			'post_status' => 'publish',
			'ignore_sticky_posts'	=> 1,
			'posts_per_page' => $per_page,
			'orderby' => 'meta_value',
			'order' => $order,
			'meta_query' => $meta_query,
			'tax_query' => array(array('taxonomy' => 'product_type' , 'field' => 'slug', 'terms' => 'lottery')),
			'meta_key' => '_lottery_dates_to',
			'is_lottery_archive' => TRUE,
			'show_past_lottery' 	=>  TRUE,
		);
		set_query_var( 'lottery_show_winners', 'true' );

		ob_start();
		$products = new WP_Query( $args );

		if ( $products->have_posts() ) : ?>
			<table style="width:100%">
				  <tr>
					<th><?php _e('Date','wc_lottery') ?></th>
					<th><?php _e('Lottery','wc_lottery') ?></th>
					<th><?php _e('Winner','wc_lottery') ?></th>
				  </tr>

				<?php while ( $products->have_posts() ) : $products->the_post();

					global $product, $post;

				?>
				<tr>
					<td><?php echo date_i18n( get_option( 'date_format' ), strtotime( $product->get_lottery_dates_to() )).' '.date_i18n( get_option( 'time_format' ), strtotime( $product->get_lottery_dates_to() )) ?> </td>
					<td><?php the_title( sprintf( '<a href="%s" rel="bookmark">', esc_url( get_permalink() ) ), '</a>' ); ?></td>
					<td>
						<?php $lottery_winers = get_post_meta($post->ID, '_lottery_winners');?>
						<?php 	if(!empty($lottery_winers) && !empty($lottery_winers[0])){
						if (count($lottery_winers) > 1) {
							$winners = '';
							foreach ($lottery_winers as $winner_id) {

									$winners .=  get_userdata($winner_id)->display_name.', ';

							}
							echo rtrim ($winners, ', ');
						} else {
							echo get_userdata($lottery_winers[0])->display_name; 
							}
						} ?>
					</td>
				</tr>
				<?php endwhile; // end of the loop. ?>

			</table>
	   <?php else : ?>
			<?php wc_get_template( 'loop/no-lotteries-winners-found.php' ); ?>
		<?php endif;
		wp_reset_postdata();
		return '<div class="woocommerce">' . ob_get_clean() . '</div>';
	}
}
