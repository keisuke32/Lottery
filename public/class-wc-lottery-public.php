<?php

/**
 * The public-facing functionality of the plugin.
 *
 * @link       http://wpgenie.org
 * @since      1.0.0-rc7
 *
 * @package    wc_lottery
 * @subpackage wc_lottery/public
 */

/**
 * The public-facing functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the admin-specific stylesheet and JavaScript.
 *
 * @package    wc_lottery
 * @subpackage wc_lottery/public
 * @author     wpgenie <info@wpgenie.org>
 */
class wc_lottery_Public {

	/**
	 * The ID of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $wc_lottery    The ID of this plugin.
	 */
	private $wc_lottery;

	/**
	 * The version of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $version    The current version of this plugin.
	 */
	private $version;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 * @param      string    $wc_lottery       The name of the plugin.
	 * @param      string    $version    The version of this plugin.
	 */
	public function __construct( $wc_lottery, $version ) {

			$this->wc_lottery = $wc_lottery;
			$this->version    = $version;

	}

	/**
	 * Register the stylesheets for the public-facing side of the site.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_styles() {

		wp_enqueue_style( $this->wc_lottery, plugin_dir_url( __FILE__ ) . 'css/wc-lottery-public.css', array(), null, 'all' );

	}

	/**
	 * Register the JavaScript for the public-facing side of the site.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_scripts() {

		wp_register_script( $this->wc_lottery, plugin_dir_url( __FILE__ ) . 'js/wc-lottery-public.js', array( 'jquery', 'wc-lottery-countdown' ), $this->version, false );

		wp_register_script( 'wc-lottery-jquery-plugin', plugin_dir_url( __FILE__ ) . 'js/jquery.plugin.min.js', array( 'jquery' ), $this->version, false );

		wp_register_script( 'wc-lottery-countdown', plugin_dir_url( __FILE__ ) . 'js/jquery.countdown.min.js', array( 'wc-lottery-jquery-plugin' ), $this->version, false );

		wp_register_script( 'wc-lottery-countdown-language', plugin_dir_url( __FILE__ ) . 'js/jquery.countdown.language.js', array( 'jquery', 'wc-lottery-countdown' ), $this->version, false );

		$language_data = array(
			'labels'        => array(
				'Years'   => __( 'Years', 'wc_lottery' ),
				'Months'  => __( 'Months', 'wc_lottery' ),
				'Weeks'   => __( 'Weeks', 'wc_lottery' ),
				'Days'    => __( 'Days', 'wc_lottery' ),
				'Hours'   => __( 'Hours', 'wc_lottery' ),
				'Minutes' => __( 'Minutes', 'wc_lottery' ),
				'Seconds' => __( 'Seconds', 'wc_lottery' ),
			),
			'labels1'       => array(
				'Year'   => __( 'Year', 'wc_lottery' ),
				'Month'  => __( 'Month', 'wc_lottery' ),
				'Week'   => __( 'Week', 'wc_lottery' ),
				'Day'    => __( 'Day', 'wc_lottery' ),
				'Hour'   => __( 'Hour', 'wc_lottery' ),
				'Minute' => __( 'Minute', 'wc_lottery' ),
				'Second' => __( 'Second', 'wc_lottery' ),
			),
			'compactLabels' => array(
				'y' => __( 'y', 'wc_lottery' ),
				'm' => __( 'm', 'wc_lottery' ),
				'w' => __( 'w', 'wc_lottery' ),
				'd' => __( 'd', 'wc_lottery' ),
				'h' => __( 'h', 'wc_lottery' ),
				'min' => __( 'min', 'wc_lottery' ),
				's' => __( 's', 'wc_lottery' ),
			),
		);

		wp_localize_script( 'wc-lottery-countdown-language', 'wc_lottery_language_data', $language_data );

		$custom_data = array(
			'finished'        => __( 'Lottery has finished! Please refresh page to see winners.', 'wc_lottery' ),
			'gtm_offset'      => get_option( 'gmt_offset' ),
			'started'         => __( 'Lottery has started! Please refresh page.', 'wc_lottery' ),
			'compact_counter' => get_option( 'simple_lottery_compact_countdown', 'no' ),
			'price_decimals' =>  esc_js( wc_get_price_decimals() ),
			'price_decimal_separator' =>  esc_js( wc_get_price_decimal_separator() ),
			'price_thousand_separator' =>  esc_js( wc_get_price_thousand_separator() ),
		);

		$wc_lottery_live_check = get_option( 'wc_lottery_live_check' );

		$wc_lottery_check_interval = get_option( 'wc_lottery_live_check_interval' );

		wp_localize_script( $this->wc_lottery, 'wc_lottery_data', $custom_data );

		wp_enqueue_script( 'wc-lottery-countdown-language' );

		wp_enqueue_script( $this->wc_lottery );


	}


	/**
	 * register_widgets function
	 *
	 * @access public
	 * @return void
	 *
	 */
	function register_widgets() {

		// Include - no need to use autoload as WP loads them anyway
		include_once 'widgets/class-wc-lottery-widget-featured-lotteries.php';
		include_once 'widgets/class-wc-lottery-widget-random-lotteries.php';
		include_once 'widgets/class-wc-lottery-widget-recent-lotteries.php';
		include_once 'widgets/class-wc-lottery-widget-recently-lotteries.php';
		include_once 'widgets/class-wc-lottery-widget-ending-soon-lotteries.php';
		include_once 'widgets/class-wc-widget-lottery-search.php';
		include_once 'widgets/class-wc-lottery-widget-future-lotteries.php';

		// Register widgets
		register_widget( 'WC_Lottery_Widget_Ending_Soon_Lotteries' );
		register_widget( 'WC_Lottery_Widget_Featured_Lotteries' );
		register_widget( 'WC_Lottery_Widget_Future_Lottery' );
		register_widget( 'WC_Lottery_Widget_Random_Loteries' );
		register_widget( 'WC_Lottery_Widget_Recent_Lotteries' );
		register_widget( 'WC_Lottery_Widget_Recently_Viewed_Lottery' );
		register_widget( 'WC_Widget_Lotteries_Search' );
	}
	/**
	 * Write the lottery tab on the product view page for WooCommerce v2.0+
	 * In WooCommerce these are handled by templates.
	 *
	 * @access public
	 * @param  array
	 * @return array
	 *
	 */
	public function lottery_tab( $tabs ) {

		global $product;

		if ( is_object($product) && 'lottery' === $product->get_type() ) {

			$wc_lottery_history = get_option( 'simple_lottery_history', 'yes' );

			if ( $wc_lottery_history !== 'yes' ) {
					return $tabs;
			}

			$tabs['lottery_history'] = array(
				'title'    => __( 'Lottery history', 'wc_lottery' ),
				'priority' => 25,
				'callback' => array( $this, 'lottery_tab_callback' ),
				'content'  => 'lottery-history',
			);
		}
		return $tabs;
	}
	/**
	 * Lottery call back from lottery_tab
	 *
	 * @access public
	 * @param  array
	 * @return void
	 *
	 */
	public function lottery_tab_callback( $tabs ) {
		wc_get_template( 'single-product/tabs/lottery-history.php' );
	}
	/**
	 * Templating with plugin folder
	 *
	 * @param int $post_id the post (product) identifier
	 * @param stdClass $post the post (product)
	 *
	 */
	function woocommerce_locate_template( $template, $template_name, $template_path ) {

		$_template = $template;
		if ( ! $template_path ) {
			$template_path = wc()->template_url;
		}
			  $plugin_path = plugin_dir_path( dirname( __FILE__ ) ) . 'templates/';

		// Look within passed path within the theme - this is priority
		$template = locate_template(
			array(
				$template_path . $template_name,
				$template_name,
			)
		);

		// Modification: Get the template from this plugin, if it exists
		if ( ! $template && file_exists( $plugin_path . $template_name ) ) {
			  $template = $plugin_path . $template_name;
		}

		// Use default template
		if ( ! $template ) {
			  $template = $_template;
		}

		// Return what we found
		return $template;
	}
	/**
	 *  Filter lottery based on settings
	 *
	 * @access public
	 * @param  bolean, string
	 * @return bolean
	 *
	 */
	function filter_lottery( $visible, $product_id ) {

		global $product;

		if ( ! $product ) {
				return $visible;
		}

		if ( method_exists( $product, 'get_type' ) && $product->get_type() !== 'lottery' ) {

			return $visible;
		}

		$simple_lottery_finished_enabled = get_option( 'simple_lottery_finished_enabled' );
		$simple_lottery_future_enabled   = get_option( 'simple_lottery_future_enabled' );
		$simple_lottery_dont_mix_shop    = get_option( 'simple_lottery_dont_mix_shop' );

		if ( $simple_lottery_future_enabled !== 'yes' && $visible == true ) {
			$visible = $product->is_started();
		}

		if ( $simple_lottery_finished_enabled !== 'yes' && $visible == true ) {
			$visible = ! $product->is_finished();
		}

		return $visible;
	}
	/**
	 *  Shortcode for my lottery
	 *
	 * @access public
	 * @param  array
	 * @return
	 *
	 */
	function shortcode_my_lottery( $atts ) {
		return WC_Shortcodes::shortcode_wrapper( array( 'WC_Shortcode_Simple_Lottery_My_Lotteries', 'output' ), $atts );
	}
	/**
	 *  Add lottery badge for lottery product
	 *
	 * @access public
	 *
	 */
	function add_lottery_bage() {

		if ( get_option( 'simple_lottery_bage', 'yes' ) === 'yes' ) {
			wc_get_template( 'loop/lottery-bage.php' );
		}

	}
	/**
	 * Get template for lottery archive page
	 *
	 * @access public
	 * @param string
	 * @return string
	 *
	 */
	function lottery_page_template( $template ) {
		if ( get_query_var( 'is_lottery_archive', false ) ) {
			$template = locate_template( WC()->template_path() . 'archive-product-lottery.php' );
			if ( $template ) {
				wc_get_template( 'archive-product-lottery.php' );
			} else {
				wc_get_template( 'archive-product.php' );
			}
			return false;
		}
		return $template;
	}
	/**
	 * Output body classes for lottery archive page
	 *
	 * @access public
	 * @param array
	 * @return array
	 *
	 */
	function output_body_class( $classes ) {
		if ( is_page( wc_get_page_id( 'lottery' ) ) ) {
				$classes [] = 'woocommerce lottery-page';
		}
		return $classes;
	}
	/**
	 * Remove lottery products from woocommerce product query
	 *
	 * @access public
	 * @param object
	 * @return void
	 *
	 */
	function remove_lottery_from_woocommerce_product_query( $q ) {

		// We only want to affect the main query
		if ( ! $q->is_main_query() or get_query_var( 'is_lottery_archive', false ) ) {
			return;
		}

		if ( ! $q->is_post_type_archive( 'product' ) && ! $q->is_tax( get_object_taxonomies( 'product' ) ) ) {
			return;
		}

		$simple_lottery_dont_mix_shop = get_option( 'simple_lottery_dont_mix_shop' );
		$simple_lottery_dont_mix_cat  = get_option( 'simple_lottery_dont_mix_cat' );
		$simple_lottery_dont_mix_tag  = get_option( 'simple_lottery_dont_mix_tag' );

		if ( $simple_lottery_dont_mix_cat !== 'yes' && is_product_category() ) {
			return;
		}
		if ( $simple_lottery_dont_mix_tag !== 'yes' && is_product_tag() ) {
			return;
		}

		if ( $simple_lottery_dont_mix_shop === 'yes' ) {
			$taxquery = $q->get( 'tax_query' );
			if ( ! is_array( $taxquery ) ) {
					$taxquery = array();
			}
			$taxquery [] =
			array(
				'taxonomy' => 'product_type',
				'field'    => 'slug',
				'terms'    => 'lottery',
				'operator' => 'NOT IN',
			);
			$q->set( 'tax_query', $taxquery );
		}
	}
	/**
	 * Define query modification based on settings
	 *
	 * @access public
	 * @param object
	 * @return void
	 *
	 */
	function pre_get_posts( $q ) {
		if ( is_admin() ) {
			return;
		}

		$lottery = array();

		$simple_lottery_finished_enabled = get_option( 'simple_lottery_finished_enabled' );
		$simple_lottery_future_enabled   = get_option( 'simple_lottery_future_enabled' );
		$simple_lottery_dont_mix_shop    = get_option( 'simple_lottery_dont_mix_shop' );
		$simple_lottery_dont_mix_cat     = get_option( 'simple_lottery_dont_mix_cat' );
		$simple_lottery_dont_mix_tag     = get_option( 'simple_lottery_dont_mix_tag' );

		if ( isset( $q->query_vars['is_lottery_archive'] ) && $q->query_vars['is_lottery_archive'] == 'true' ) {

			$taxquery = $q->get( 'tax_query' );
			if ( ! is_array( $taxquery ) ) {
					$taxquery = array();
			}
			$taxquery[] =
			array(
				'taxonomy' => 'product_type',
				'field'    => 'slug',
				'terms'    => 'lottery',
			);

			 $q->set( 'tax_query', $taxquery );
			 add_filter( 'woocommerce_is_filtered', array( $this, 'add_is_filtered' ), 99 ); // hack for displaying when Shop Page Display is set to show categories
		}
		if ( ( $simple_lottery_future_enabled !== 'yes' && ( ! isset( $q->query['show_future_lotteries'] ) or ! $q->query['show_future_lotteries'] ) )
				or ( isset( $q->query['show_future_lotteries'] ) && $q->query['show_future_lotteries'] == false ) ) {

			$metaquery = $q->get( 'meta_query' );

			if ( ! is_array( $metaquery ) ) {
				 $metaquery = array();
			}

			$metaquery [] =
							array(
								'key'     => '_lottery_started',
								'compare' => 'NOT EXISTS',
							);
			$q->set( 'meta_query', $metaquery );
		}

		if ( ( $simple_lottery_finished_enabled !== 'yes' && ( ! isset( $q->query['show_past_lottery'] ) or ! $q->query['show_past_lottery'] )
				or ( isset( $q->query['show_past_lottery'] ) && $q->query['show_past_lottery'] == false ) ) ) {

			$metaquery = $q->get( 'meta_query' );
			if ( ! is_array( $metaquery ) ) {
				$metaquery = array();
			}
			$metaquery [] = array(
				'key'     => '_lottery_closed',
				'compare' => 'NOT EXISTS',
			);
			$q->set( 'meta_query', $metaquery );
		}

		if ( $simple_lottery_dont_mix_cat !== 'yes' && is_product_category() ) {
			return;
		}

		if ( $simple_lottery_dont_mix_tag !== 'yes' && is_product_tag() ) {
			return;
		}

		if ( ! isset( $q->query['is_lottery_archive'] ) && get_query_var( 'is_lottery_archive', false ) == false ) {

			if ( $simple_lottery_dont_mix_shop == 'yes' ) {
				$taxquery = $q->get( 'tax_query' );
				if ( ! is_array( $taxquery ) ) {
					$taxquery = array();
				}
				$taxquery [] =
				array(
					'taxonomy' => 'product_type',
					'field'    => 'slug',
					'terms'    => 'lottery',
					'operator' => 'NOT IN',
				);
				$q->set( 'tax_query', $taxquery );
				return;
			}
		}
	}
	/**
	 * Run query modification based on settings
	 *
	 * @access public
	 * @param object
	 * @return void
	 *
	 */
	function lottery_archive_pre_get_posts( $q ) {
		if ( isset( $q->query['lottery_archive'] ) or ( ! isset( $q->query['lottery_archive'] ) && ( isset( $q->query['post_type'] ) && $q->query['post_type'] == 'product' && ! $q->is_main_query() ) ) ) {
			$this->pre_get_posts( $q );
		}
	}

	function query_is_lottery_archive( $q ) {

		if ( ! $q->is_main_query() ) {
			return;
		}

		if ( isset( $q->queried_object->ID ) && $q->queried_object->ID === wc_get_page_id( 'lottery' ) ) {

			$q->set( 'post_type', 'product' );
			$q->set( 'page', '' );
			$q->set( 'pagename', '' );
			$q->set( 'lottery_arhive', 'true' );
			$q->set( 'is_lottery_archive', 'true' );

			// Fix conditional Functions
			$q->is_archive           = true;
			$q->is_post_type_archive = true;
			$q->is_singular          = false;
			$q->is_page              = false;

		}

		if ( ( $q->is_page() && 'page' === get_option( 'show_on_front' ) && absint( $q->get( 'page_id' ) ) === wc_get_page_id( 'lottery' ) ) or ( $q->is_home() && absint( get_option( 'page_on_front' ) ) === wc_get_page_id( 'lottery' ) ) ) {

			$q->set( 'post_type', 'product' );

			// This is a front-page shop
			$q->set( 'post_type', 'product' );
			$q->set( 'page_id', '' );
			$q->set( 'lottery_arhive', 'true' );
			$q->set( 'is_lottery_archive', 'true' );

			if ( isset( $q->query['paged'] ) ) {
				$q->set( 'paged', $q->query['paged'] );
			}

			// Define a variable so we know this is the front page shop later on
			define( 'lotteryS_IS_ON_FRONT', true );

			// Get the actual WP page to avoid errors and let us use is_front_page()
			// This is hacky but works. Awaiting https://core.trac.wordpress.org/ticket/21096
			global $wp_post_types;

			$lottery_page = get_post( wc_get_page_id( 'lottery' ) );

			$wp_post_types['product']->ID         = $lottery_page->ID;
			$wp_post_types['product']->post_title = $lottery_page->post_title;
			$wp_post_types['product']->post_name  = $lottery_page->post_name;
			$wp_post_types['product']->post_type  = $lottery_page->post_type;
			$wp_post_types['product']->ancestors  = get_ancestors( $lottery_page->ID, $lottery_page->post_type );

			// Fix conditional Functions like is_front_page
			$q->is_singular          = false;
			$q->is_post_type_archive = true;
			$q->is_archive           = true;
			$q->is_page              = true;

			// Remove post type archive name from front page title tag
			add_filter( 'post_type_archive_title', '__return_empty_string', 5 );

			// Fix WP SEO
			if ( class_exists( 'WPSEO_Meta' ) ) {
				add_filter( 'wpseo_metadesc', WPSEO_Meta::get_value( 'metadesc', wc_get_page_id( 'lottery' ) ) );
				add_filter( 'wpseo_metakey', WPSEO_Meta::get_value( 'metakey', wc_get_page_id( 'lottery' ) ) );
			}
		}

	}

	/**
	 * Cron action
	 *
	 * Checks for a valid request, check lottery and closes lottery if is finished
	 *
	 * @access public
	 * @param bool $url (default: false)
	 * @return void
	 *
	 */
	function simple_lottery_cron( $url = false ) {

		if ( empty( $_REQUEST['lottery-cron'] ) ) {
			return;
		}

		if ( $_REQUEST['lottery-cron'] == 'check' ) {

			update_option( 'Wc_lottery_cron_check', 'yes' );

			set_time_limit( 0 );

			ignore_user_abort( 1 );

			$args = array(
				'post_type'           => 'product',
				'posts_per_page'      => '-1',
				'meta_query'          => array(
					'relation' => 'AND', // Optional, defaults to "AND"

					array(
						'key'     => '_lottery_closed',
						'compare' => 'NOT EXISTS',
					),
					array(
						'key'     => '_lottery_dates_to',
						'compare' => 'EXISTS',
					),
				),
				'meta_key'            => '_lottery_dates_to',
				'orderby'             => 'meta_value',
				'order'               => 'ASC',
				'tax_query'           => array(
					array(
						'taxonomy' => 'product_type',
						'field'    => 'slug',
						'terms'    => 'lottery',
					),
				),
				'lottery_archive'     => true,
				'show_past_lottery' => true,
				'show_future_lottery' => true,
			);

			for ( $i = 0; $i < 3; $i++ ) {

				$the_query = new WP_Query( $args );
				$time      = microtime( 1 );

				if ( $the_query->have_posts() ) {
					while ( $the_query->have_posts() ) :
						$the_query->the_post();

						$product_data      = wc_get_product( $the_query->post->ID );
						$product_data_type = method_exists( $product_data, 'get_type' ) ? $product_data->get_type() : $product_data->product_type;
						if ( $product_data_type == 'lottery' ) {
								$product_data->is_closed();
						}
					endwhile;
				}
				$time = microtime( 1 ) - $time;
				$i < 3 and sleep( 20 - $time );
			}
		}
		exit;
	}

	/**
	 * Add to cart validation
	 *
	 */
	public function add_to_cart_validation( $pass, $product_id, $quantity, $variation_id = 0 ) {
		$checked_ids = $product_quantities = array();

		foreach ( wc()->cart->get_cart() as $cart_item_key => $values ) {

			if ( ! isset( $product_quantities[ $values['product_id'] ] ) ) {
				$product_quantities[ $values['product_id'] ] = 0;
			}
			
			$product_quantities[ $values['product_id'] ] += $values['quantity'];

		}

		if ( function_exists( 'wc_get_product' ) ) {

			$product = wc_get_product( $product_id );

		} else {

			$product = new WC_Product( $product_id );
		}

		if ( method_exists( $product, 'get_type' ) && $product->get_type() == 'lottery' ) {

			$max_tickets_per_user = $product->get_max_tickets_per_user() ? $product->get_max_tickets_per_user() : false;
			if ( $max_tickets_per_user == false  OR $max_tickets_per_user == $product->get_max_tickets() ) {
						return true;
			 }

			if ( ! is_user_logged_in() ) {

				wc_add_notice( sprintf( __( 'Sorry, you must be logged in to participate in lottery. <a href="%s" class="button">Login &rarr;</a>', 'wc_lottery' ), get_permalink( wc_get_page_id( 'myaccount' ) ) ), 'error' );
				return false;
			}

			$user_ID = get_current_user_id();

			$max_tickets_per_user = $product->get_max_tickets_per_user() ? $product->get_max_tickets_per_user() : false;

			if ( ! $max_tickets_per_user && $product->is_sold_individually() ) {
				$max_tickets_per_user = 1;
			}

			if ( $max_tickets_per_user == false ) {

					return $pass;

			} else {

				$users_qty = array_count_values( get_post_meta( $product_id, '_participant_id' ) );

				$current_user_qty = isset( $users_qty[ $user_ID ] ) ? intval( $users_qty[ $user_ID ] ) : 0;

				$product_qty_in_cart = isset( $product_quantities[ $product_id ] ) ? intval( $product_quantities[ $product_id ] ) : 0;

				$qty = $current_user_qty + intval( $quantity ) + $product_qty_in_cart;

				if ( ( $current_user_qty > 0 ) && ( $qty > $max_tickets_per_user ) ) {

					wc_add_notice( sprintf( __( 'The maximum allowed quantity for %1$s is %2$d . You already have %3$d, so you can not add %4$d more.', 'wc_lottery' ), $product->get_title(), $max_tickets_per_user, $current_user_qty, $quantity ), 'error' );
					$pass = false;
				}

				if ( ( $current_user_qty == 0 ) && ( $qty > $max_tickets_per_user ) ) {

					wc_add_notice( sprintf( __( 'The maximum allowed quantity for %1$s is %2$d . So you can not add %3$d to your cart.', 'wc_lottery' ), $product->get_title(), $max_tickets_per_user, $qty ), 'error' );
					$pass = false;
				}
			}
		}
		return $pass;
	}

	/**
	 * Validate cart items against set rules
	 *
	 * @access public
	 * @return void
	 */
	public function check_cart_items() {

		$checked_ids = $product_quantities = array();

		foreach ( wc()->cart->get_cart() as $cart_item_key => $values ) {

			if ( ! isset( $product_quantities[ $values['product_id'] ] ) ) {

				$product_quantities[ $values['product_id'] ] = 0;
			}

			$product_quantities[ $values['product_id'] ] += $values['quantity'];

		}

		foreach ( wc()->cart->get_cart() as $cart_item_key => $values ) {

			$product = wc_get_product( $values['product_id'] );

			if ( method_exists( $product, 'get_type' ) && $product->get_type() == 'lottery' ) {
//                $max_tickets_per_user = 1;
				 $max_tickets_per_user = $product->get_max_tickets_per_user() ? $product->get_max_tickets_per_user() : false;
				if ( $max_tickets_per_user == false  OR $max_tickets_per_user == $product->get_max_tickets() ) {
							return true;
				 }

				if ( ! is_user_logged_in() ) {

					wc_add_notice( sprintf( __( 'Sorry, you must be logged in to participate in lottery. <a href="%s" class="button">Login &rarr;</a>', 'wc_lottery' ), get_permalink( wc_get_page_id( 'myaccount' ) ) ), 'error' );

					return false;
				}

				$user_ID = get_current_user_id();

//                $max_tickets_per_user = 1;

				$max_tickets_per_user = $product->get_max_tickets_per_user() ? $product->get_max_tickets_per_user() : false;

				if ( ! $max_tickets_per_user && $product->is_sold_individually() ) {
					$max_tickets_per_user = 1;
				}

				if ( $max_tickets_per_user !== false ) {

					$users_qty = array_count_values( get_post_meta( $values['product_id'], '_participant_id' ) );

					$current_user_qty = isset( $users_qty[ $user_ID ] ) ? intval( $users_qty[ $user_ID ] ) : 0;

					$qty = $current_user_qty + intval( $product_quantities[ $values['product_id'] ] );

					if ( ( $current_user_qty > 0 ) && ( $qty > $max_tickets_per_user ) ) {

						wc_add_notice( sprintf( __( 'The maximum allowed quantity for %1$s is %2$d . You already have %3$d, so you can not add %4$d more.', 'wc_lottery' ), $product->get_title(), $max_tickets_per_user, $current_user_qty, intval( $product_quantities[ $values['product_id'] ] ) ), 'error' );

					}

					if ( ( $current_user_qty == 0 ) && ( $qty > $max_tickets_per_user ) ) {

						wc_add_notice( sprintf( __( 'The maximum allowed quantity for %1$s is %2$d . So you can not add %3$d to your cart.', 'wc_lottery' ), $product->get_title(), $max_tickets_per_user, $qty ), 'error' );

					}
				}
			}
		}
	}
	/**
	 * Make product not purchasable if lottery is full
	 *
	 * @access public
	 * @return bolean
	 */
	public function is_purchasable( $purchasable, $product ) {

		if ( method_exists( $product, 'get_type' ) && $product->get_type() == 'lottery' && $purchasable === true ) {

			if ( ! $product->is_started() or $product->is_closed() ) {
				return false;
			}

			return ! $product->is_max_tickets_met();
		}

		return $purchasable;

	}
	/**
	 * Add some classes to post_class()
	 *
	 * @access public
	 * @return array
	 */
	public function add_post_class( $classes ) {

		global $post,$product;

		if ( method_exists( $product, 'get_type' ) && $product->get_type() == 'lottery' ) {

			if ( $product->is_max_tickets_met() ) {
				$classes[] = 'lottery-full';
			}
		}

		return $classes;

	}

	/**
	 * Add particpate message before single product
	 *
	 * @access public
	 * @return void
	 */
	public function participating_message( $product_id ) {

		global $product;

		if ( ! $product ) {
			return false;
		}

		if ( method_exists( $product, 'get_type' ) && $product->get_type() != 'lottery' ) {
					return false;
		}
		if ( $product->is_closed() ) {
					return false;
		}
		$current_user = wp_get_current_user();

		if ( ! $current_user->ID ) {
					return false;
		}

		if ( $product->is_user_participating() == false ) {
					return false;
		}

		$ticket_count = $product->count_user_tickets();

		$message = sprintf( _n( 'You have bought a ticket for this lottery!', 'You have bought %d tickets for this lottery!', $ticket_count, 'wc_lottery' ), $ticket_count );

		wc_add_notice( apply_filters( 'woocommerce_lottery_participating_message', $message ) );

	}

	 /**
	 * Translate onsale page url
	 */
	function translate_ls_lottery_url( $languages, $debug_mode = false ) {
		global $sitepress;
		global $wp_query;

		$lottery_page = (int) wc_get_page_id( 'lottery' );

		foreach ( $languages as $language ) {
			// shop page
			// obsolete?
			if ( get_query_var( 'lottery_archive', false ) || $debug_mode ) {

					$sitepress->switch_lang( $language['language_code'] );
					$url = get_permalink( apply_filters( 'translate_object_id', $lottery_page, 'page', true, $language['language_code'] ) );
					$sitepress->switch_lang();
					$languages[ $language['language_code'] ]['url'] = $url;

			}
		}

		return $languages;
	}

	/**
	 *
	 * Add wpml support for lottery base page
	 *
	 * @param int
	 * @return int
	 *
	 */
	function lottery_page_wpml( $page_id ) {

					global $sitepress;

		if ( function_exists( 'icl_object_id' ) ) {
			$id = icl_object_id( $page_id, 'page', false );

		} else {
			$id = $page_id;
		}
					return $id;

	}

	/**
	 *
	 * Track lottery views
	 *
	 * @param void
	 * @return int
	 *
	 */
	function track_lotteries_view() {

		if ( ! is_singular( 'product' ) || ! is_active_widget( false, false, 'recently_viewed_lotteries', true ) ) {
			return;
		}

		global $post;

		if ( empty( $_COOKIE['woocommerce_recently_viewed_lotteries'] ) ) {
			$viewed_products = array();
		} else {
			$viewed_products = (array) explode( '|', $_COOKIE['woocommerce_recently_viewed_lotteries'] );
		}

		if ( ! in_array( $post->ID, $viewed_products ) ) {
			$viewed_products[] = $post->ID;
		}

		if ( sizeof( $viewed_products ) > 15 ) {
			array_shift( $viewed_products );
		}

		// Store for session only
		wc_setcookie( 'woocommerce_recently_viewed_lotteries', implode( '|', $viewed_products ) );
	}

	/**
	 * Set is filtered to true to skip displaying categories only on page
	 *
	 * @access public
	 * @return bolean
	 *
	 */
	function add_is_filtered( $id ) {

		return true;
	}

	/**
	*
	* Fix active class in nav for Lottery page.
	*
	* @access public
	* @param array $menu_items
	* @return array
	*
	*/
	function lottery_nav_menu_item_classes( $menu_items ) {

		if ( ! get_query_var( 'is_lottery_archive', false ) ) {
			return $menu_items;
		}

		$bgoupbuy_page = (int) wc_get_page_id( 'lottery' );

		foreach ( (array) $menu_items as $key => $menu_item ) {

			$classes = (array) $menu_item->classes;

			// Unset active class

			$menu_items[ $key ]->current = false;

			if ( in_array( 'current_page_parent', $classes ) ) {
				unset( $classes[ array_search( 'current_page_parent', $classes ) ] );
			}

			if ( in_array( 'current-menu-item', $classes ) ) {
				unset( $classes[ array_search( 'current-menu-item', $classes ) ] );
			}

			if ( in_array( 'current_page_item', $classes ) ) {
				unset( $classes[ array_search( 'current_page_item', $classes ) ] );
			}

			// Set active state if this is the shop page link
			if ( $bgoupbuy_page == $menu_item->object_id && 'page' === $menu_item->object ) {
				$menu_items[ $key ]->current = true;
				$classes[]                   = 'current-menu-item';
				$classes[]                   = 'current_page_item';

			}

			$menu_items[ $key ]->classes = array_unique( $classes );

		}

		return $menu_items;
	}
	/**
	 *
	 * Fix for Lottery base page breadcrumbs
	 *
	 * @access public
	 * @param string
	 * @return string
	 *
	 */
	public function lottery_get_breadcrumb( $crumbs, $WC_Breadcrumb ) {

		if ( get_query_var( 'is_lottery_archive', false ) == 'true' ) {

			$auction_page_id = wc_get_page_id( 'lottery' );
			$crumbs[1]       = array( get_the_title( $auction_page_id ), get_permalink( $auction_page_id ) );
		}

		return $crumbs;
	}

	function lottery_filter_wp_title( $title ) {

		global $paged, $page;

		if ( ! get_query_var( 'is_lottery_archive', false ) ) {
			return $title;
		}

		$auction_page_id = wc_get_page_id( 'lottery' );
		$title           = get_the_title( $auction_page_id );

		return $title;
	}
	/**
	*
	* Fix for Lottery base page title
	*
	* @access public
	* @param string
	* @return string
	*
	*/
	function lottery_page_title( $title ) {

		if ( get_query_var( 'is_lottery_archive', false ) == 'true' ) {

			$auction_page_id = wc_get_page_id( 'lottery' );

			$title = get_the_title( $auction_page_id );

		}

		return $title;

	}

	function add_redirect_previous_page() {
		if ( isset( $_SERVER['HTTP_REFERER'] ) ) {
			echo '<input type="hidden" name="redirect" value="' . esc_url( $_SERVER['HTTP_REFERER'] ) . ' " >';
		}
	}

	/**
	 * Remove finished auctions from related products
	 *
	 * @access public
	 * @return var
	 *
	 */

	public function remove_finished_lotteries_from_related_products( $query ) {


		$simple_lottery_finished_enabled = get_option( 'simple_lottery_finished_enabled', 'no' );
		$simple_lottery_future_enabled   = get_option( 'simple_lottery_future_enabled', 'yes' );

		if ( $simple_lottery_finished_enabled == 'no' ) {
			$finished_auctions = woocommerce_lottery_get_finished_lotteries_id();
		}

		if ( $simple_lottery_future_enabled == 'no' ) {
			$future_auctions = woocommerce_lottery_get_future_lotteries_id();
		}

		if ( $simple_lottery_finished_enabled == 'no' && count( $finished_auctions ) ) {
				$query['where'] .= ' AND p.ID NOT IN ( ' . implode( ',', array_map( 'absint', $finished_auctions ) ) . ' )';
		}

		if ( $simple_lottery_future_enabled == 'no' && count( $future_auctions ) ) {
				$query['where'] .= ' AND p.ID NOT IN ( ' . implode( ',', array_map( 'absint', $future_auctions ) ) . ' )';
		}

		return $query;
	}

	
	public function add_ticket_number_to_cart_item( $cart_item_data, $product_id, $variation_id ) {
        $ticket_numbers = filter_input( INPUT_POST, 'selected_ticket_number' );

		if ( strlen( $ticket_numbers ) == 0 ) {
			return $cart_item_data;
		}

		$cart_item_data['lottery_tickets_number'] = substr($ticket_numbers, 0, -1);

		return $cart_item_data;
	}

	public function display_ticket_numbers_cart( $item_data, $cart_item ) {
		if ( empty( $cart_item['lottery_tickets_number'] ) ) {
			return $item_data;
		}
        $ticket_number = $cart_item['lottery_tickets_number'];
        $ticket_number_arr = explode(",", $ticket_number);
        $ticket_number_sort = [];
        $ticket_bonus_number_comp = [];
        $alphabet_line = substr_count($cart_item['lottery_tickets_number'], "A");
    	foreach ($ticket_number_arr as $number) {
            if(explode(".", $number)[0] != "L")
                if($alphabet_line == 1) {
                	$number_exp = explode('.', $number);
                    $ticket_number_sort[$number_exp[0]] = $number_exp[1];
                }else{
                    $ticket_number_sort[] = explode(".", $number)[1];
                }
            else {
                array_push($ticket_bonus_number_comp, explode(".", $number)[1]);
            }
        }
        $ticket_rearranged = "";
        if($alphabet_line > 1)
        {
            sort($ticket_number_sort);
        	$ticket_rearranged = implode('-', $ticket_number_sort);
        }
        else
        {
        	ksort($ticket_number_sort);
        	$ticket_rearranged = implode('', $ticket_number_sort);
        }
        if(!empty($ticket_bonus_number_comp)){
        	if(count($ticket_bonus_number_comp) > 1)
	    		if($ticket_bonus_number_comp[0] > $ticket_bonus_number_comp[1])
	    		{
	    			$temp = $ticket_bonus_number_comp[0];
	    			$ticket_bonus_number_comp[0] = $ticket_bonus_number_comp[1];
	    			$ticket_bonus_number_comp[1] = $temp;
	    		}
	    		$ticket_rearranged .= '+' . implode('+', $ticket_bonus_number_comp);
        }
		$item_data[] = array(
			'key'     => esc_html__( 'Ticket number', 'wc-lottery' ),
			'value'   => wc_clean( $ticket_rearranged ),
			'display' => '',
		);	

		return $item_data;
	}

	public function wc_lottery_get_taken_numbers( $product_id = false, $user_id = false ) {
        global $product;

        $wheredatefrom = '';

        if ( ! $product_id && $product ) {
                $product_id = $product->get_id();
        }
        global $wpdb;

        $relisteddate = get_post_meta( $product_id, '_lottery_dates_from', true );

        if ( $relisteddate ) {
            $wheredatefrom = ' AND CAST(' . $wpdb->prefix . "wc_lottery_log.date AS DATETIME) > '$relisteddate' ";
        }

        $result = $wpdb->get_col( $wpdb->prepare( 'SELECT ' . $wpdb->prefix . 'wc_lottery_pick_number.content FROM ' . $wpdb->prefix . 'wc_lottery_pick_number LEFT JOIN ' . $wpdb->prefix . 'wc_lottery_log ON ' . $wpdb->prefix . 'wc_lottery_log.lottery_id = ' . $wpdb->prefix . 'wc_lottery_pick_number.productid WHERE ' . $wpdb->prefix . 'wc_lottery_pick_number.productid = %d ' . $wheredatefrom, $product_id ) );

        return $result;
    }

	public function check_cart_ticket_numbers( $session_data, $values, $key) {
		if ( ! empty( $session_data['lottery_tickets_number'] ) ) {

			$product = wc_get_product( $session_data['product_id'] );
			$ticket_numbers =  substr($session_data['lottery_tickets_number'], 0, -1);

			$taken_numbers = $this->wc_lottery_get_taken_numbers($session_data['product_id']);
			// $taken_numbers = [];
			if( $taken_numbers && $ticket_numbers != '' &&  in_array($ticket_numbers, $taken_numbers) ) {
				wc_add_notice( sprintf( __( 'Product %1$s has been removed from your cart because someone purchase that ticket number. Please add it to your cart again by <a href="%2$s">clicking here</a>.', 'wc-lottery' ), $product->get_name(), $product->get_permalink() ), 'error' );
				return false;
			}
			
		}
		return $session_data;
	}

	public function check_cart_for_duplicate_ticket_numbers( $cart_object) {
		$cart = WC()->session->get( 'cart', null );
		$tickets = array();

		if ( ! empty( $cart ) ) {
			foreach ( $cart as $key => $cart_item ) {
				if( isset( $cart_item['lottery_tickets_number'] ) &&  $cart_item['lottery_tickets_number'] ){
					if($cart_item['quantity'] >= 1){
						$product = wc_get_product($cart_item['product_id']);
                        $cart[$key]['quantity'] = 1;
						WC()->session->set('cart', $cart);
						// wc_add_notice( sprintf( __( 'Please check cart for duplicate ticket numbers.', 'wc-lottery' ), $product->get_name(), $product->get_permalink() ), 'error' );
						return;
					}

					$tickets[$cart_item['product_id']][] = $cart_item['lottery_tickets_number'];
				}
			}
		}
    }

	public function woocommerce_quantity_input_max_callback($max, $product){
		$max = 1;  
		return $max;
	}
}

