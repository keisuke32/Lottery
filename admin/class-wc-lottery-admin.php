<?php

/**
 * The admin-specific functionality of the plugin.
 *
 * @link       http://wpgenie.org
 * @since      1.0.0
 *
 * @package    wc_lottery
 * @subpackage wc_lottery/admin
 */

/**
 * The admin-specific functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the admin-specific stylesheet and JavaScript.
 *
 * @package    wc_lottery
 * @subpackage wc_lottery/admin
 * @author     wpgenie <info@wpgenie.org>
 */
class wc_lottery_Admin
{

    /**
     * The current path of the plugin.
     *
     * @since    1.0.0
     * @access   protected
     * @var      string $version The current version of the plugin.
     */
    protected $path;
    /**
     * The ID of this plugin.
     *
     * @since    1.0.0
     * @access   private
     * @var      string $wc_lottery The ID of this plugin.
     */
    private $wc_lottery;
    /**
     * The version of this plugin.
     *
     * @since    1.0.0
     * @access   private
     * @var      string $version The current version of this plugin.
     */
    private $version;

    /**
     * Initialize the class and set its properties.
     *
     * @param string $wc_lottery The name of this plugin.
     * @param string $version The version of this plugin.
     * @since    1.0.0
     */
    public function __construct($wc_lottery, $version, $path)
    {
        $this->wc_lottery = $wc_lottery;
        $this->version = $version;
        $this->path = $path;
    }

    /**
     * Handle a refund via the edit order screen.
     */
    public static function lottery_refund()
    {

        check_ajax_referer('lottery-refund', 'security');

        if (!current_user_can('edit_shop_orders')) {
            die(-1);
        }

        $item_ids = array();
        $succes = array();
        $error = array();

        $product_id = absint($_POST['product_id']);
        $refund_amount = 0;
        $refund_reason = __('Lottery failed. No minimum ticket sold', 'wc_lottery');
        $refund = false;
        $response_data = array();

        $orders = self::get_product_orders($product_id);

        $lottery_order_refunded = get_post_meta($product_id, '_lottery_order_refunded');

        foreach ($orders as $key => $order_id) {

            if (in_array($order_id, $lottery_order_refunded)) {
                $error[$order_id] = __('Lottery amount allready returned', 'wc_lottery');
                continue;
            }

            try {

                // Validate that the refund can occur
                $order = wc_get_order($order_id);
                $order_items = $order->get_items();
                $refund_amount = 0;

                // Prepare line items which we are refunding
                $line_items = array();
                $item_ids = array();
                if ($order_items = $order->get_items()) {

                    foreach ($order_items as $item_id => $item) {

                        if (function_exists('wc_get_order_item_meta')) {
                            $item_meta = wc_get_order_item_meta($item_id, '');
                        } else {
                            $item_meta = method_exists($order, 'wc_get_order_item_meta') ? $order->wc_get_order_item_meta($item_id) : $order->get_item_meta($item_id);
                        }

                        $product_data = wc_get_product($item_meta['_product_id'][0]);
                        if ($product_data->get_type() == 'lottery' && $item_meta['_product_id'][0] == $product_id) {
                            $item_ids[] = $product_data->get_id();
                            $refund_amount = wc_format_decimal($refund_amount) + wc_format_decimal($item_meta['_line_total'] [0]);
                            $line_items[$product_data->get_id()] = array(
                                'qty' => $item_meta['_qty'],
                                'refund_total' => wc_format_decimal($item_meta['_line_total']),
                                'refund_tax' => array_map('wc_format_decimal', $item_meta['_line_tax_data']),
                            );

                        }
                    }
                }

                $max_refund = wc_format_decimal($refund_amount - $order->get_total_refunded());

                if (!$refund_amount || $max_refund < $refund_amount || 0 > $refund_amount) {
                    throw new exception(__('Invalid refund amount', 'wc_lottery'));
                }

                if (WC()->payment_gateways()) {
                    $payment_gateways = WC()->payment_gateways->payment_gateways();
                }

                $payment_method = method_exists($order, 'get_payment_method') ? $order->get_payment_method() : $order->payment_method;

                if (isset($payment_gateways[$payment_method]) && $payment_gateways[$payment_method]->supports('refunds')) {
                    $result = $payment_gateways[$payment_method]->process_refund($order_id, $refund_amount, $refund_reason);

                    do_action('woocommerce_refund_processed', $refund, $result);

                    if (is_wp_error($result)) {
                        throw new Exception($result->get_error_message());
                    } elseif (!$result) {
                        throw new Exception(__('Refund failed', 'wc_lottery'));
                    } else {
                        // Create the refund object
                        $refund = wc_create_refund(
                            array(
                                'amount' => $refund_amount,
                                'reason' => $refund_reason,
                                'order_id' => $order_id,
                                'line_items' => $line_items,
                            )
                        );

                        if (is_wp_error($refund)) {
                            throw new Exception($refund->get_error_message());
                        }

                        add_post_meta($product_id, '_lottery_order_refunded', $order_id);
                    }

                    // Trigger notifications and status changes
                    if ($order->get_remaining_refund_amount() > 0 || ($order->has_free_item() && $order->get_remaining_refund_items() > 0)) {
                        /**
                         * woocommerce_order_partially_refunded.
                         *
                         * @since 2.4.0
                         * Note: 3rd arg was added in err. Kept for bw compat. 2.4.3.
                         */
                        do_action('woocommerce_order_partially_refunded', $order_id, $refund->id, $refund->id);
                    } else {
                        do_action('woocommerce_order_fully_refunded', $order_id, $refund->id);

                        $order->update_status(apply_filters('woocommerce_order_fully_refunded_status', 'refunded', $order_id, $refund->id));
                        $response_data['status'] = 'fully_refunded';
                    }

                    do_action('woocommerce_order_refunded', $order_id, $refund->id);

                    // Clear transients
                    wc_delete_shop_order_transients($order_id);
                    $succes[$order_id] = __('Refunded', 'woocommerce');

                } elseif (isset($payment_gateways[$payment_method]) && !$payment_gateways[$payment_method]->supports('refunds')) {
                    $error[$order_id] = esc_html__('Payment gateway does not support refunds', 'wc_lottery');
                }
            } catch (Exception $e) {
                if ($refund && is_a($refund, 'WC_Order_Refund')) {
                    wp_delete_post($refund->id, true);
                }

                $error[$order_id] = $e->getMessage();
            }
        }

        wp_send_json(
            array(
                'error' => $error,
                'succes' => $succes,
            )
        );

    }

    /**
     * Get the orders for a product
     *
     * @param int $id the product ID to get orders for
     * @param string fields  fields to retrieve
     * @param string $filter filters to include in response
     * @param string $status the order status to retrieve
     * @param $page $page   page to retrieve
     * @return array
     * @since 1.0.1
     */
    public function get_product_orders($id)
    {
        global $wpdb;

        if (is_wp_error($id)) {
            return $id;
        }

        $order_ids = $wpdb->get_col(
            $wpdb->prepare(
                "SELECT order_id
                FROM {$wpdb->prefix}woocommerce_order_items
                WHERE order_item_id IN ( SELECT order_item_id FROM {$wpdb->prefix}woocommerce_order_itemmeta WHERE meta_key = '_product_id' AND meta_value = %d )
                AND order_item_type = 'line_item' ", $id
            )
        );

        if (empty($order_ids)) {
            return array('orders' => array());
        }

        return $order_ids;

    }

    /**
     * Register the stylesheets for the admin area.
     *
     * @since    1.0.0
     */
    public function enqueue_styles()
    {

        wp_enqueue_style($this->wc_lottery, plugin_dir_url(__FILE__) . 'css/wc-lottery-admin.css', array(), $this->version, 'all');

    }

    /**
     * Register the JavaScript for the admin area.
     *
     * @since    1.0.0
     */
    public function enqueue_scripts($hook)
    {

        if ($hook == 'post-new.php' || $hook == 'post.php') {
            if ('product' == get_post_type()) {
                /**
                 * @Author Igor
                 * ----------START ADD jquery ui for accordion, sort---------------
                 */
                wp_enqueue_script('jquery-ui-core');
                wp_enqueue_script('jquery-ui-accordion');
                /**
                 * ---------END-------------
                 */
                wp_register_script(
                    'wc-lottery-admin',
                    plugin_dir_url(__FILE__) . '/js/wc-lottery-admin.js',
                    array('jquery', 'jquery-ui-core', 'jquery-ui-datepicker', 'timepicker-addon'),
                    $this->version,
                    true
                );

                $params = array(
                    'i18_max_ticket_less_than_min_ticket_error' => __('Please enter in a value greater than the min tickets.', 'wc_lottery'),
                    'i18_minimum_winers_error' => __('You must set at least one lottery winner', 'wc_lottery'),
                    'lottery_refund_nonce' => wp_create_nonce('lottery-refund'),
                );

                wp_localize_script('wc-lottery-admin', 'woocommerce_lottery', $params);
                wp_enqueue_script('wc-lottery-admin');

                wp_enqueue_script(
                    'timepicker-addon',
                    plugin_dir_url(__FILE__) . '/js/jquery-ui-timepicker-addon.js',
                    array('jquery', 'jquery-ui-core', 'jquery-ui-datepicker'),
                    $this->version,
                    true
                );

                wp_enqueue_style('jquery-ui-datepicker');
            }
        }

    }

    /**
     * Add to mail class
     *
     * @access public
     * @return object
     *
     */
    public function add_to_mail_class($emails)
    {

        include_once 'emails/class-wc-email-lottery-win.php';
        include_once 'emails/class-wc-email-lottery-failed.php';
        include_once 'emails/class-wc-email-lottery-no-luck.php';
        include_once 'emails/class-wc-email-lottery-finished.php';
        include_once 'emails/class-wc-email-lottery-failed-users.php';
        include_once 'emails/class-wc-email-lottery-extended.php';

        $emails->emails['WC_Email_Lottery_Win'] = new WC_Email_Lottery_Win();
        $emails->emails['WC_Email_Lottery_Failed'] = new WC_Email_Lottery_Failed();
        $emails->emails['WC_Email_Lottery_Finished'] = new WC_Email_Lottery_Finished();
        $emails->emails['WC_Email_Lottery_No_Luck'] = new WC_Email_Lottery_No_Luck();
        $emails->emails['WC_Email_Lottery_Fail_Users'] = new WC_Email_Lottery_Fail_Users();
        $emails->emails['WC_Email_Lottery_Extended'] = new WC_Email_Lottery_Extended();

        return $emails;
    }

    /**
     * register_widgets function
     *
     * @access public
     * @return void
     *
     */
    function register_widgets()
    {

    }

    /**
     * Add link to plugin page
     *
     * @access public
     * @param array, string
     * @return array
     *
     */
    public function add_support_link($links, $file)
    {
        if (!current_user_can('install_plugins')) {
            return $links;
        }

        if ($file == 'woocommerce-lottery/wc-lottery.php') {
            $links[] = '<a href="https://wpgenie.org/woocommerce-lottery/documentation/" target="_blank">' . __('Docs', 'wc_lottery') . '</a>';
            $links[] = '<a href="https://codecanyon.net/user/wpgenie#contact" target="_blank">' . __('Support', 'wc_lottery') . '</a>';
            $links[] = '<a href="https://codecanyon.net/user/wpgenie/" target="_blank">' . __('More WooCommerce Extensions', 'wc_lottery') . '</a>';
        }
        return $links;
    }

    /**
     * Add admin notice
     *
     * @access public
     * @param array, string
     * @return array
     *
     */
    public function woocommerce_simple_lottery_admin_notice()
    {
        global $current_user;
        if (current_user_can('manage_options')) {
            $user_id = $current_user->ID;
            if (get_option('Wc_lottery_cron_check') != 'yes' && !get_user_meta($user_id, 'lottery_cron_check_ignore')) {
                echo '<div class="updated">
                <p>' . sprintf(__('Woocommerce Lottery recommends that you set up a cron job to check for finished lotteries: <b>%1$s/?lottery-cron=check</b>. Set it to every minute| <a href="%2$s">Hide Notice</a>', 'wc_lottery'), get_bloginfo('url'), add_query_arg('lottery_cron_check_ignore', '0')) . '</p>
                </div>';
            }
            if (get_option('woocommerce_enable_guest_checkout') == 'yes') {
                echo '<div class="error">
                <p>' . sprintf(__('Woocommerce Lottery can not work with enabled option "Allow customers to place orders without an account" please turn it off. <a href="%1$s">Accounts & Privacy settings</a>', 'wc_lottery'), get_admin_url() . 'admin.php?page=wc-settings&tab=account') . '</p>
                </div>';
            }
        }
    }

    /**
     * Add user meta to ignor notice about crons.
     * @access public
     *
     */
    public function woocommerce_simple_lottery_ignore_notices()
    {
        global $current_user;
        $user_id = $current_user->ID;

        /* If user clicks to ignore the notice, add that to their user meta */
        if (isset($_GET['lottery_cron_check_ignore']) && '0' == $_GET['lottery_cron_check_ignore']) {
            add_user_meta($user_id, 'lottery_cron_check_ignore', 'true', true);
        }

    }

    /**
     * Add product type
     * @param array
     * @return array
     *
     */
    public function add_product_type($types)
    {
        /**
         * @Author: Igor
         * -------------------START------------------
         */
//      $types['lottery'] = __( 'Lottery', 'wc_lottery' );
        $types['lottery'] = __('Lucky Draw', 'wc_lottery');
        /**
         * -----------------END--------------------
         */
        return $types;
    }

    /**
     * Adds a new tab to the Product Data postbox in the admin product interface
     *
     * @return void
     *
     */
    public function product_write_panel_tab($product_data_tabs)
    {
        $tab_icon = plugin_dir_url(__FILE__) . 'images/lottery.png';

        /**
         * @Author: Igor
         * -------------------START------------------
         */
//        $lottery_tab = array(
//            'lottery_tab' => array(
//                'label'  => __( 'Lottery', 'wc_lottery' ),
//                'target' => 'lottery_tab',
//                'class'  => array( 'lottery_tab', 'show_if_lottery', 'hide_if_grouped', 'hide_if_external', 'hide_if_variable', 'hide_if_simple' ),
//            ),
//        );
        $lottery_tab = array(
            'lottery_tab' => array(
                'label' => __('Lucky Draw', 'wc_lottery'),
                'target' => 'lottery_tab',
                'class' => array('lottery_tab', 'show_if_lottery', 'hide_if_grouped', 'hide_if_external', 'hide_if_variable', 'hide_if_simple'),
            ),
            'pick_winner_tab' => array(
                'label' => __('Winner Picking', 'wc_lottery'),
                'target' => 'pick_winner_tab',
                'class' => array('pick_winner_tab', 'show_if_lottery', 'hide_if_grouped', 'hide_if_external', 'hide_if_variable', 'hide_if_simple'),
            ),
        );

       return $lottery_tab + $product_data_tabs;
        // return $lottery_tab;
        /**
         * ---------------------END-------------------------
         */
    }

    /**
     * Adds the panel to the Product Data postbox in the product interface
     *
     * @return void
     *
     */
    public function product_write_panel()
    {
        global $post;
        $product = wc_get_product($post->ID);
        echo '<div id="lottery_tab" class="panel woocommerce_options_panel wc-metaboxes-wrapper">';
        /**
         * @Author: Igor
         * -------------------START ADD Price, SalePrice------------------
         */

        //************************** Price ******************************
        woocommerce_wp_text_input(
            array(
                'id' => '_lottery_price',
                'class' => 'input_text',
                'label' => __('Price', 'wc_lottery') . ' (' . get_woocommerce_currency_symbol() . ')',
                'data_type' => 'price',
                'desc_tip' => 'true',
                'description' => __('Lottery Price, put 0 for free lottery.', 'wc_lottery'),
            )
        );

        //************************** Sale Price ******************************
        woocommerce_wp_text_input(
            array(
                'id' => '_lottery_sale_price',
                'class' => 'input_text',
                'label' => __('Sale Price', 'wc_lottery') . ' (' . get_woocommerce_currency_symbol() . ')',
                'data_type' => 'price',
                'desc_tip' => 'true',
                'description' => __('Lottery Sale Price', 'wc_lottery'),
            )
        );

        //************************** Start Date & End Date ******************************
        $lottery_dates_from = ($value = get_post_meta($post->ID, '_lottery_dates_from', true)) ? $value : '';
        $lottery_dates_to = ($value = get_post_meta($post->ID, '_lottery_dates_to', true)) ? $value : '';
        echo '  <p class="form-field lottery_dates_fields">
                    <label for="_lottery_dates_from">' . __('Start Date', 'wc_lottery') . '</label>
                    <input type="text" class="short datetimepicker required" name="_lottery_dates_from" id="_lottery_dates_from" value="' . $lottery_dates_from . '" placeholder="' . _x('From&hellip;', 'placeholder', 'wc_lottery') . __('YYYY-MM-DD HH:MM') . '"maxlength="16" pattern="[0-9]{4}-(0[1-9]|1[012])-(0[1-9]|1[0-9]|2[0-9]|3[01])[ ](0[0-9]|1[0-9]|2[0-4]):(0[0-9]|1[0-9]|2[0-9]|3[0-9]|4[0-9]|5[0-9])" />
                 </p>
                 <p class="form-field lottery_dates_fields">
                    <label for="_lottery_dates_to">' . __('End Date', 'wc_lottery') . '</label>
                    <input type="text" class="short datetimepicker required" name="_lottery_dates_to" id="_lottery_dates_to" value="' . $lottery_dates_to . '" placeholder="' . _x('To&hellip;', 'placeholder', 'wc_lottery') . __('YYYY-MM-DD HH:MM') . '" maxlength="16" pattern="[0-9]{4}-(0[1-9]|1[012])-(0[1-9]|1[0-9]|2[0-9]|3[01])[ ](0[0-9]|1[0-9]|2[0-4]):(0[0-9]|1[0-9]|2[0-9]|3[0-9]|4[0-9]|5[0-9])" />
                </p>';

        //************************** Extend Lottery ******************************
        $product_type = method_exists($product, 'get_type') ? $product->get_type() : $product->product_type;
        if ('lottery' == $product_type && $product->get_lottery_closed() === '1') {
            echo '<p class="form-field extend_dates_fields"><a class="button extend" href="#" id="extendlottery">' . __('Extend Lottery', 'wc_lottery') . '</a>
                   <p class="form-field extend_lottery_dates_fields"> 
                        <label for="_extend_lottery_dates_from">' . __('Extend Date', 'wc_lottery') . '</label>
                        <input type="text" class="short datetimepicker" name="_extend_lottery_dates_to" id="_extend_lottery_dates_to" value="" placeholder="' . _x('To&hellip; YYYY-MM-DD HH:MM', 'placeholder', 'wc_lottery') . '" maxlength="16" pattern="[0-9]{4}-(0[1-9]|1[012])-(0[1-9]|1[0-9]|2[0-9]|3[01])[ ](0[0-9]|1[0-9]|2[0-4]):(0[0-9]|1[0-9]|2[0-9]|3[0-9]|4[0-9]|5[0-9])" />
                    </p>
                    </p>';
        }

        //************************** Alphabet Line ******************************
        $alphabet_line_value = ($value = get_post_meta($post->ID, '_lottery_pick_number_alphabet', true)) ? $value : '1';
        woocommerce_wp_select(
            array(
                'id' => '_lottery_pick_number_alphabet',
                'wrapper_class' => 'lottery_gray_wrapper',
                'label' => __('Alphabet Line', 'wc_lottery'),
                'options' => array(
                    1 => __('1 Line(A)', 'wc_lottery'),
                    3 => __('3 Line(ABC)', 'wc_lottery'),
                    4 => __('4 Line(ABCD)', 'wc_lottery'),
                    5 => __('5 Line(ABCDE)', 'wc_lottery'),
                    6 => __('6 Line(ABCDEF)', 'wc_lottery'),
                ),
                'description' => __('<br/><br/>1 Line (A), 3 Line (ABC), 4 Line (ABCD), 5 Line (ABCDE), 6 Line (ABCDEF)', 'wc_lottery'),
            )
        );

        //************************** Auto Pick ******************************
        woocommerce_wp_checkbox(
            array(
                'id' => '_lottery_pick_numbers_random',
                'wrapper_class' => '',
                'label' => __('Auto Pick', 'wc_lottery'),
                'description' => __('Auto Pick button will appear for user to use this feature', 'wc_lottery'),
                'desc_tip' => true,
            )
        );

        //************************** Number Range ******************************
        if($alphabet_line_value == 1)
        {
            $lottery_number_range_from = ($value = get_post_meta($post->ID, '_lottery_number_range_from', true)) ? $value : '1';
            $lottery_number_range_to = ($value = get_post_meta($post->ID, '_lottery_number_range_to', true)) ? $value : '48';
        }
        else
        {
            $lottery_number_range_from = ($value = get_post_meta($post->ID, '_lottery_number_range_from', true)) ? $value : '0';
            $lottery_number_range_to = ($value = get_post_meta($post->ID, '_lottery_number_range_to', true)) ? $value : '9';
        }
        echo '<p class="form-field">
                <span class="lottery_number_range_field">
                    <label for="_lottery_number_range_to">' . __('Number Range', 'wc_lottery') . '</label>
                    <input type="text" class="short required lottery_number_range_field_input" name="_lottery_number_range_from" id="_lottery_number_range_from" value="' . $lottery_number_range_from . '" placeholder="" maxlength="2" readonly/>
                    <span>&nbsp;-&nbsp;</span>
                    <input type="text" class="short required lottery_number_range_field_input" name="_lottery_number_range_to" id="_lottery_number_range_to" value="' . $lottery_number_range_to . '" placeholder="" maxlength="2"' . ($alphabet_line_value == 1 ? '' : ' readonly') . '/>
                    <span>&nbsp;per line</span>
                </span>
                    <span class="description">
                        <br/><br/>Enter number range
                        <br/>- 0 - 9 range only for 3 Line (ABC), 4 Line (ABCD), 5 Line (ABCDE) & 6 Line (ABCDEF)
                        <br/>- 1 - 99 range is only for 1 Line (A)
                    </span>
                 </p>';

        //************************** User Pick ******************************
        $lottery_user_pick = ($value = get_post_meta($post->ID, '_lottery_number_user_pick', true)) ? $value : '1';
        echo '<p class="form-field">
                <span class="">
                    <label for="_lottery_number_user_pick">' . __('User Pick', 'wc_lottery') . '</label>
                    <select name="_lottery_number_user_pick" id="_lottery_number_user_pick" class="select short"' . ($alphabet_line_value == 1 ? '' : ' disabled') . '>';
                        if($alphabet_line_value == 1)
                        {
                            echo '<option value=1' . ($lottery_user_pick == 1 ? ' selected' : '') . '>1</option>
                            <option value=2' . ($lottery_user_pick == 2 ? ' selected' : '') . '>2</option>
                            <option value=3' . ($lottery_user_pick == 3 ? ' selected' : '') . '>3</option>
                            <option value=4' . ($lottery_user_pick == 4 ? ' selected' : '') . '>4</option>
                            <option value=5' . ($lottery_user_pick == 5 ? ' selected' : '') . '>5</option>
                            <option value=6' . ($lottery_user_pick == 6 ? ' selected' : '') . '>6</option>
                            <option value=7' . ($lottery_user_pick == 7 ? ' selected' : '') . '>7</option>';
                        } else
                        {
                            echo '<option value=1' . ($lottery_user_pick == 1 ? ' selected' : '') . '>1</option>
                            <option value=2>2</option>
                            <option value=3>3</option>
                            <option value=4>4</option>
                            <option value=5>5</option>
                            <option value=6>6</option>
                            <option value=7>7</option>
                            ';
                        }
              echo '</select>
                    <span>&nbsp;per line</span>
                </span>
                    <span class="description">
                        <br/><br/>- Only 1 digit pick per line when selected - 3 Line (ABC), 4 Line (ABCD), 5 Line (ABCDE) & 6 Line (ABCDEF)
                        <br/>- Multiple numbers per line 5, 6 & 7 for - 1 Line (A)
                    </span>
                 </p>';

        //************************** Bonus Number & Enble Popup & Bonus Enabled ******************************
        $lottery_bonus_number = ($value = get_post_meta($post->ID, '_lottery_bonus_number', true)) ? $value : '';
        $lottery_bonus_number_popup = (get_post_meta($post->ID, '_lottery_bonus_number_popup', true) == "yes") ? 'checked' : '';
        $lottery_bonus_enabled = ($value = get_post_meta($post->ID, '_lottery_bonus_enabled', true)) ? $value : '0';
        echo '<p class="form-field lottery_gray_wrapper">
                <label for="_lottery_bonus_number">' . __('Bonus Number', 'wc_lottery') . '</label>
                <span class="lottery_bonus_number">
                    <span>
                        <input type="text" class="short lottery_bonus_number_input" name="_lottery_bonus_number" id="_lottery_bonus_number" value="' . $lottery_bonus_number . '" placeholder="Bonus number name" />
                        <input type="checkbox" class="checkbox" style="" name="_lottery_bonus_number_popup" id="_lottery_bonus_number_popup" value="yes" ' . $lottery_bonus_number_popup . '/>
                        <span>&nbsp;Enable Pop-up</span>
                    </span>
                    <select name="_lottery_bonus_enabled" id="_lottery_bonus_enabled">
                        <option value=1' . ($lottery_bonus_enabled == 1 ? ' selected' : '') . '>Enable</option>
                        <option value=0' . ($lottery_bonus_enabled == 0 ? ' selected' : '') . '>Disable</option>
                    </select>
                </span>
                 </p>';

        //************************** Bonus Number Range & Type ******************************
        $lottery_bonus_number_range_type = ($value = get_post_meta($post->ID, '_lottery_bonus_number_range_type', true)) ? $value : '1';
        $lottery_bonus_number_range_from = "";
        $lottery_bonus_number_range_to = "";
        if($lottery_bonus_number_range_type == 1)
        {
            $lottery_bonus_number_range_from = ($value = get_post_meta($post->ID, '_lottery_bonus_number_range_from', true)) ? $value : '1';
            $lottery_bonus_number_range_to = ($value = get_post_meta($post->ID, '_lottery_bonus_number_range_to', true)) ? $value : '42';
        } else
        {
            $lottery_bonus_number_range_from = ($value = get_post_meta($post->ID, '_lottery_bonus_number_range_from', true)) ? $value : '0';
            $lottery_bonus_number_range_to = ($value = get_post_meta($post->ID, '_lottery_bonus_number_range_to', true)) ? $value : '99';
        }
        echo '<p class="form-field">
                <span class="lottery_bonus_number_range_field">
                    <label for="_lottery_bonus_number_range_to">' . __('Number Range', 'wc_lottery') . '</label>
                    <input type="number" class="short required lottery_bonus_number_range_field_input" name="_lottery_bonus_number_range_from" id="_lottery_bonus_number_range_from" value="' . $lottery_bonus_number_range_from . '" placeholder="" maxlength="2" style="width: 7em"' . ($lottery_bonus_number_range_type == 2 ? ' readonly' : '') . '/>
                    <span>&nbsp;-&nbsp;</span>
                    <input type="number" class="short required lottery_bonus_number_range_field_input" name="_lottery_bonus_number_range_to" id="_lottery_bonus_number_range_to" value="' . $lottery_bonus_number_range_to . '" placeholder="" maxlength="2" style="width: 7em"' . ($lottery_bonus_number_range_type == 2 ? ' readonly' : '') . '/>
                    &nbsp;&nbsp;&nbsp;
                    <input name="_lottery_bonus_number_range_type" value="1" type="radio" class="" style=""' . ($lottery_bonus_number_range_type == 1 ? ' checked' : '') . '/>
                    1 Digit (Range: 1-42)
                    &nbsp;&nbsp;&nbsp;
                    <input name="_lottery_bonus_number_range_type" value="2" type="radio" class="" style=""' . ($lottery_bonus_number_range_type == 2 ? ' checked' : '') . '/>
                    2 Digit (Range: 00-99)
                </span>
                    <span class="description">
                        <br/><br/>Enter number range for Bonus Number (Range: 1-42 or 00-99)
                    </span>
                 </p>';

        //************************** Allow Pick ******************************
        $lottery_number_allow_pick = ($value = get_post_meta($post->ID, '_lottery_number_allow_pick', true)) ? $value : '1';
        echo '<p class="form-field">
                <span class="lottery_number_allow_pick">
                    <label for="_lottery_number_allow_pick">' . __('Allow Pick', 'wc_lottery') . '</label>
                    <select name="_lottery_number_allow_pick" id="_lottery_number_allow_pick" class="select short"' . ($lottery_bonus_number_range_type == 2 ? ' disabled' : '') . '>
                        <option value=1' . ($lottery_number_allow_pick == 1 ? ' selected' : '') . '>1</option>
                        <option value=2' . ($lottery_number_allow_pick == 2 ? ' selected' : '') . '>2</option>
                    </select>
                </span>
                <span class="description">
                    <br/><br/>Allow to pick per line for Bonus Number.
                </span>
             </p>';

        //************************** Prize Name ******************************
        /*echo '
        <div class="toolbar toolbar-top">

            <select name="attribute_taxonomy" class="attribute_taxonomy">
                <option value=""><?php esc_html_e( \'Custom product attribute\', \'woocommerce\' ); ?></option>
                <?php
                global $wc_product_attributes;

                // Array of defined attribute taxonomies.
                $attribute_taxonomies = wc_get_attribute_taxonomies();

                if ( ! empty( $attribute_taxonomies ) ) {
                    foreach ( $attribute_taxonomies as $tax ) {
                        $attribute_taxonomy_name = wc_attribute_taxonomy_name( $tax->attribute_name );
                        $label                   = $tax->attribute_label ? $tax->attribute_label : $tax->attribute_name;
                        echo \'<option value="\' . esc_attr( $attribute_taxonomy_name ) . \'">\' . esc_html( $label ) . \'</option>\';
                    }
                }
                ?>
            </select>
            <button type="button" class="button add_attribute"><?php esc_html_e( \'Add\', \'woocommerce\' ); ?></button>
        </div>';*/
        $lottery_prize_name = ($value = get_post_meta($post->ID, '_lottery_prize_name', true)) ? $value : '';
        echo '<p class="form-field lottery_gray_wrapper">
                <span class="lottery_prize_group">
                    <label for="_lottery_prize_name">' . __('Prize Name', 'wc_lottery') . '</label>
                    <input type="text" class="short lottery_prize_name_input" name="_lottery_prize_name" id="_lottery_prize_name" value="' . $lottery_prize_name . '" placeholder="Enter prize name" />
                    <span class="lottery_prize_control">
                        
                        <button class="button button-primary button-large" id="_lottery_add_prize">'.__( 'Add', 'wc_lottery' ).'</button>
                        <!--a href="#" id="_lottery_prize_group_collapse">Expand/Close</a-->
                        <span class="expand-close">
                            <a href="#" class="expand_all">'.__( 'Expand', 'wc_lottery' ).'</a> / <a href="#" class="close_all">'.__( 'Close', 'wc_lottery' ).'</a>
                        </span>
                    </span>
                </span>
                 </p>';

        //************************** Make Gami Point HTML ******************************
        // gamipress points select
        $lottery_gami_points = ($lbnf = get_post_meta($post->ID, '_lottery_prize_gami_points', true)) ? $lbnf : '0';
        $gami_points = gamipress_get_points_types();
        echo "<input type='hidden' value='" . json_encode($gami_points) . "' id='gami_point_types' />";
        $gami_points_html = '<span class="lottery_prize_gami_points">
                                <select style="" id="_lottery_prize_gami_points" name="_lottery_prize_gami_points" class="select short">
                                    <option value="0">Point type</option>
                                ';
        foreach ($gami_points as $gp) {
            if($lottery_gami_points == $gp['ID'])
                $gami_points_html .= '<option value="' . $gp['ID'] . '" selected>' . $gp['singular_name'] . '</option>';
            else
                $gami_points_html .= '<option value="' . $gp['ID'] . '">' . $gp['singular_name'] . '</option>';
        }
        $gami_points_html .= '</select>
                            </span>';

        //************************** Prize & Gami Point ******************************
        $lottery_prize = ($value = get_post_meta($post->ID, '_lottery_prize', true)) ? $value : '100';
        echo '<p class="form-field">
                <span class="lottery_prize">
                    <span class="lottery_prize_point">
                        <span>
                            <label for="_lottery_prize">' . __('Prize', 'wc_lottery') . '</label>
                            <input type="number" class="short required lottery_prize_input" name="_lottery_prize" id="_lottery_prize" value="' . $lottery_prize . '" placeholder=""/>
                            <span>&nbsp;points</span>
                        </span>
                        <span class="description">
                            <br/><br/>Total point for winner of this prize
                        </span>
                    </span>
                    <span class="lottery_prize_gami_points">
                        ' . $gami_points_html . '
                        <span class="description">
                            <br/><br/>The point type use when payout for winners
                        </span>
                    </span>
                </span>
             </p>';

        //************************** Alphabet Line ******************************
        $lottery_match_alphabet_options = [
            1 => [
                6 => '7 Digit Match (A)',
                5 => '6 Digit Match (A)',
                4 => '5 Digit Match (A)',
                3 => '4 Digit Match (A)',
                2 => '3 Digit Match (A)',
                1 => '2 Digit Match (A)'
            ],
            3 => [
                1 => '3 Digit (ABC)',
            ],
            4 => [
                1 => '4 Digit (ABCD)',
            ],
            5 => [
                1 => '5 Digit (ABCDE)',
                2 => '4D Prefix (ABCD)',
                3 => '4D Suffix (CDEF)',
                4 => '3D Prefix (ABCD)',
                5 => '3D Suffix (CDEF)',
                6 => '2D Suffix (EF)',
            ],
            6 => [
                6 => '6 Digit (ABCDEF)',
                5 => '4D Suffix (CDEF)',
                4 => '4D Prefix (ABCD)',
                3 => '3D Suffix (DEF)',
                2 => '3D Prefix (ABC)',
                1 => '2D Suffix (EF)',
            ]
        ];
        $lottery_match_alphbet_texts = [
            1 => "1 Line(A)",
            3 => "3 Line(ABC)",
            4 => "4 Line(ABCD)",
            5 => "5 Line(ABCDE)",
            6 => "6 Line(ABCDEF)",
        ];

        //alphabet line that was picked by user in Prize content
        $lottery_winnter_match_alphabet = ($value = get_post_meta($post->ID, '_lottery_match_alphabet', true)) ? $value : '0';
        
        $lottery_match_alphabet_option = $lottery_match_alphabet_options[$alphabet_line_value];
        $match_html = '<select id="_lottery_match_alphabet" name="_lottery_match_alphabet" style="width:100%">
                            <option value="0" selected disabled>' . $lottery_match_alphbet_texts[$alphabet_line_value] . '</option>';
        foreach ($lottery_match_alphabet_option as $pa => $pa_value) {
            if($lottery_winnter_match_alphabet == $pa)
                $match_html .= "<option value='$pa' selected>$pa_value</option>";
            else
                $match_html .= "<option value='$pa'>$pa_value</option>";
        }
        $match_html .= "</select>";

        //Draw Alphabet Match Line
        echo '<p class="form-field">
                <span class="lottery_match">
                    <label for="_lottery_match_alphabet">' . __('Match', 'wc_lottery') . '</label>
                    <span class="lottery_match_content">
                        <span class="lottery_match_alphabet">
                            <span class="top_desc">Alphabet Line</span>
                            <span class="">
                                ' . $match_html . '
                            </span>
                            <span class="description">
                                When Selected: 6 Digit (ABCDEF)<br/>Option: 2D Suffix (EF), 3D Prefix (ABC), 3D Suffix (DEF),<br/>4D Prefix (ABCD), 4D Suffix (CDEF), 6 Digit (ABCDEF)
                                <br/>When Selected: 4 Digit (ABCD) <br/>Option: 4 Digit (ABCD)<br/>When Selected: 3 Digit (ABC)<br/>Option: 3 Digit (ABC)
                                <br/>When selected: 1 Line (A) <br/>Option: 7 Digit Match (A), 6 Digit Match (A), 5 Digit Match (A)<br/>4 Digit Match (A), 3 Digit Match (A), 2 Digit Match (A)
                            </span>
                        </span>';

        //************************** Match Bonus Number ******************************
        $lottery_match_bonus = ($value = get_post_meta($post->ID, '_lottery_match_bonus', true)) ? $value : '0';
        echo '<span class="lottery_match_plus_symbol"' . ($lottery_bonus_enabled == 0 ? ' style="display : none;"' : '') . '><br/>+</span>
                    <span class="lottery_match_bonus"' . ($lottery_bonus_enabled == 0 ? ' style="display : none;"' : '') . '>
                        <span class="top_desc">Bonus Number</span>
                        <span>
                            <select name="_lottery_match_bonus" id="_lottery_match_bonus" style="width:100%">';
                            if($lottery_bonus_enabled == 0)
                            {
                                echo '<option value=0 selected>None</option>';
                                echo '<option value=1>1 Digit(L)</option>';
                                echo '<option value=2>2 Digit(L)</option>';
                            }
                            else
                            {
                                if($lottery_number_allow_pick == 1)
                                {
                                    echo '<option value=0' . ($lottery_match_bonus == 0 ? ' selected' : '') . '>None</option>
                                <option value=1' . ($lottery_match_bonus == 1 ? ' selected' : '') . '>1 Digit(L)</option>';
                                } else if($lottery_number_allow_pick == 2)
                                {
                                    echo '<option value=0' . ($lottery_match_bonus == 0 ? ' selected' : '') . '>None</option>
                                <option value=2' . ($lottery_match_bonus == 2 ? ' selected' : '') . '>2 Digit(L)</option>';
                                }
                            }
                        echo '</select>
                        </span>
                        <span class="description">
                            When Bonus Number Enable<br/>Option: None, 1 Digit(L), 2 Digit(L)
                        </span>             
                    </span>';
        echo '</span>
            </p>';

        //************************** Draw Prizes ******************************
        // prize content for collapse start
//        echo '<div class="lottery_prize_content">';
        echo '<div class="lottery_prizes wc-metaboxes">';
        $prize_content = $this->get_lottery_prizes($post->ID);
        foreach ($prize_content as $pc) {
            $values = unserialize($pc['content']);

            $gp_html = '<select name="lottery_prize_gp_point_type[]" class="select short">';
            foreach ($gami_points as $gp) {
                $gp_html .= '<option value="' . $gp['ID'] . '" ' . ($gp['ID'] == $values['pt'] ? 'selected' : '') . ' >' . $gp['singular_name'] . '</option>';
            }
            $gp_html .= '</select>';
            $match_html = "<select name='lottery_prize_match_alphabet[]' class='select short' style='width:100%'>";
            foreach ($lottery_match_alphabet_options[$values['pa']] as $pa => $pa_value) {
                $match_html .= "<option value='$pa' " . ($pa == $values['pm'] ? "selected" : "") . ">$pa_value</option>";
            }
            $match_html .= "</select>";
            echo '<div class="wc-metabox closed lottery_prize_item lottery_prize_' . $pc['id'] . '" id="lottery_prize_handle_' . $pc['id'] . '">';
            echo '<h3 class="lottery_gray_wrapper lottery_prize_handler lottery_prize_wrapper_controller_reorder">
                <a href="#" class="remove_row lottery_prize_wrapper_controller_remove delete" id="lottery_prize_remove_' . $pc['id'] . '">'. __( 'Remove', 'wc_lottery' ).'</a>
                <div class="handlediv" title="'. __( 'Click to toggle', 'wc_lottery' ).'"></div>
                <div class="tips sort lottery_prize_wrapper_controller_reorder" id="lottery_prize_reorder_' . $pc['id'] . '" data-tip="'. __( 'Drag and drop to set prize order', 'wc_lottery' ).'"></div>			
                <label class="lottery_prize_wrapper_label lottery_prize_wrapper_label_' . $pc['id'] . '">#' . $pc['order'] . '</label>
                <input type="text" class="short required lottery_prize_name_default" name="lottery_prize[]" value="' . $values['pn'] . '" placeholder="First Prize" />
            </h3>';
            /*echo '
                <p class="form-field lottery_gray_wrapper">
                  <span class="lottery_prize_wrapper">
                     <label class="lottery_prize_wrapper_label lottery_prize_wrapper_label_' . $pc['id'] . '">#' . $pc['order'] . '</label>
                     <span class="lottery_prize_wrapper_content">
                         <span class="lottery_prize_wrapper_content_name">
                            <input type="text" class="short required lottery_prize_name_default" name="lottery_prize[]" value="' . $values['pn'] . '" placeholder="First Prize" />
                         </span>
                         <span class="lottery_prize_wrapper_content_controller">
                             <a href="#" class="lottery_prize_wrapper_controller_remove" id="lottery_prize_remove_' . $pc['id'] . '">Remove</a>
                             <a href="#" class="lottery_prize_wrapper_controller_reorder sort" id="lottery_prize_reorder_' . $pc['id'] . '">&#9776;</i></a>
                             <a href="#" class="lottery_prize_wrapper_controller_collapse" id="lottery_prize_collapse_' . $pc['id'] . '">&#9650;</a>
                         </span>
                     </span>
                   </span>
                </p>';*/
            echo '
                <div class="wc-metabox-content hidden lottery_prize_content_' . $pc['id'] . '">
                    <p class="form-field">
                        <span class="lottery_prize">
                            <span class="lottery_prize_point">
                                <span>
                                    <label for="_lottery_prize">Prize</label>
                                    <input type="number" class="short required lottery_prize_input" name="lottery_prize_point[]" value="' . $values['pp'] . '" placeholder=""/>
                                    <span>&nbsp;points</span>
                                </span>
                                <span class="description">
                                    <br/><br/>Total point for winner of this prize
                                </span>
                            </span>
                            <span class="lottery_prize_gami_points">
                                ' . $gp_html . '
                                <span class="description">
                                    <br/><br/>The point type use when payout for winners
                                </span>
                            </span>
                        </span>
                    </p>
                    <p class="form-field">
                        <span class="lottery_prize_match_wrapper">
                            <label class="lottery_prize_match_label">Match</label>
                            <span class="lottery_match_content">
                                <span class="lottery_match_alphabet">
                                    <span class="top_desc">Lucky Draw</span>
                                    <span>
                                        ' . $match_html . '
                                    </span>
                                </span>';
            //Match Bonus Number
            echo '<span class="lottery_match_plus_symbol"' . ($lottery_bonus_enabled == 0 ? ' style="display : none;"' : '') . '><br/>+</span>
                    <span class="lottery_match_bonus"' . ($lottery_bonus_enabled == 0 ? ' style="display : none;"' : '') . '>
                        <span class="top_desc">Bonus Number</span>
                        <span>
                            <select class="select short lottery_prize_match_bonus_select" name="lottery_prize_match_bonus[]" style="width:100%">';
                            if($lottery_bonus_enabled == 0)
                            {
                                echo '<option value=0 selected>None</option>';
                                echo '<option value=1>1 Digit(L)</option>';
                                echo '<option value=2>2 Digit(L)</option>';
                            }
                            else
                            {
                                if($lottery_number_allow_pick == 1)
                                {
                                    echo '<option value=0 ' . ($values['pb'] == 0 ? 'selected' : '') . '>None</option>
                                <option value=1 ' . ($values['pb'] == 1 ? 'selected' : '') . '>1 Digit(L)</option>';
                                } else if($lottery_number_allow_pick == 2)
                                {
                                    echo '<option value=0 ' . ($values['pb'] == 0 ? 'selected' : '') . '>None</option>
                                <option value=2 ' . ($values['pb'] == 2 ? 'selected' : '') . '>2 Digit(L)</option>';
                                }
                            }
                      echo '</select>
                        </span>
                    </span>';
            echo '</span>
                    </span>
                </p>
            </div>
         </div>
         ';
        }
        echo '</div>';
//        echo '</div>';

        wp_nonce_field('save_lottery_data_' . $post->ID, 'save_lottery_data');
        do_action('woocommerce_product_options_lottery');

        echo '</div>';
        //************************** Draw Winner Picking Page ******************************
        echo '<div id="pick_winner_tab" class="panel woocommerce_options_panel">';
        echo '<p class="lottery_winner_p_wrapper">
                    <span class="lottery_winner_span_wrapper">
                        <span>Category</span>
                        <span>Winning Numbers</span>
                        <span>Prize</span>
                    </span>
            </p>';

        $prize_content = $this->get_lottery_prizes($post->ID);
        //1:total box count 2:enable box count 3:(0->prefix,1->suffix)
        $alphabetline_box_count = [
            1 => [
                6 => [1 => 7, 2 => 7, 3=> 0],
                5 => [1 => 6, 2 => 6, 3=> 0],
                4 => [1 => 5, 2 => 5, 3=> 0],
                3 => [1 => 4, 2 => 4, 3=> 0],
                2 => [1 => 3, 2 => 3, 3=> 0],
                1 => [1 => 2, 2 => 2, 3=> 0],
            ],
            3 => [
                1 => [1 => 3, 2 => 3, 3=> 0],
            ],
            4 => [
                1 => [1 => 4, 2 => 4, 3=> 0],
            ],
            5 => [
                1 => [1 => 5, 2 => 5, 3=> 0],
                2 => [1 => 5, 2 => 4, 3=> 0],
                3 => [1 => 5, 2 => 4, 3=> 1],
                4 => [1 => 5, 2 => 3, 3=> 0],
                5 => [1 => 5, 2 => 3, 3=> 1],
                6 => [1 => 5, 2 => 2, 3=> 1],
            ],
            6 => [
                1 => [1 => 6, 2 => 2, 3=> 1],
                2 => [1 => 6, 2 => 3, 3=> 0],
                3 => [1 => 6, 2 => 3, 3=> 1],
                4 => [1 => 6, 2 => 4, 3=> 0],
                5 => [1 => 6, 2 => 4, 3=> 1],
                6 => [1 => 6, 2 => 6, 3=> 0],
            ]
        ];
        $index = 0;
        foreach ($prize_content as $pc) {
            $index++;
            $prize_id = $pc['id'];
            echo '<div class="winner_prize_' . $index . '">';
            echo '<p class="lottery_winner_p_wrapper small_prize small_prize_1">';
            echo '<span class="lottery_winner_span_wrapper">
                        <span></span>
                        <span class="lottery_six_digit_panel alphabet_' . $index . '">';
            $values = unserialize($pc['content']);
            $total_count = $alphabetline_box_count[$values['pa']][$values['pm']][1];
            $enable_count = $alphabetline_box_count[$values['pa']][$values['pm']][2];
            if($values['pa'] == 1)
            {
                for($i=0;$i<$total_count;$i++)
                    echo '<span class="lottery_digit_number" style="line-height: 5px;">A</span>';
            } else {
                for($i=0;$i<$total_count;$i++)
                    echo '<span class="lottery_digit_number" style="line-height: 5px;">' . chr(65+$i) . '</span>';
            }
            if($values['pb'] == 1 || $values['pb'] == 2)
            {
                echo '<span></span>';
                echo '<span class="lottery_digit_number" style="line-height: 5px;">L</span>';
                if($values['pb'] == 2)
                    echo '<span class="lottery_digit_number" style="line-height: 5px;">L</span>';
            }
            echo '</span>
                        <span></span>
                        <span></span>
                </span>';
            echo '<span class="lottery_winner_span_wrapper">';
            echo '<span>' . $values['pn'] . '</span>';
            echo '<span class="lottery_six_digit_panel six_digit_' . $index . '">';
            $prize_winner_content = $this->get_lottery_winner_prizes($prize_id);
            $digits = $prize_winner_content[0]['content'];
            $digits = unserialize($digits);

            if($alphabetline_box_count[$values['pa']][$values['pm']][3] == 0)
            {
                for($i=0;$i<$enable_count;$i++)
                {
                    $digit_index = 'd' . (number_format($i) + 1);
                    $digit = $digits[$digit_index];
                    if($digit == "#" || $digit == "-")
                        $digit = "";
                    echo '<input class="lottery_digit_number" name="lottery_digit_number_' . (number_format($i) + 1) . '[]" value="' . $digit . '">';
                }
                for($i=$enable_count;$i<$total_count;$i++)
                    echo '<input class="lottery_digit_number lottery_digit_number_readonly" readonly="readonly" value="-" name="lottery_digit_number_' . (number_format($i) + 1) . '[]">';
                for($i=$total_count;$i<7;$i++)
                    echo '<input type="hidden" class="lottery_digit_number lottery_digit_number_readonly" readonly="readonly" value="#" name="lottery_digit_number_' . (number_format($i) + 1) . '[]">';
            }
            if($alphabetline_box_count[$values['pa']][$values['pm']][3] == 1)
            {
                for($i=$enable_count;$i<$total_count;$i++)
                    echo '<input class="lottery_digit_number lottery_digit_number_readonly" readonly="readonly" value="-" name="lottery_digit_number_' . (number_format($i) - $enable_count + 1) . '[]">';
                for($i=0;$i<$enable_count;$i++)
                {
                    $digit_index = 'd' . (number_format($i) + ($total_count - $enable_count) + 1);
                    $digit = $digits[$digit_index];
                    if($digit == "#" || $digit == "-")
                        $digit = "";
                    echo '<input class="lottery_digit_number" name="lottery_digit_number_' . (number_format($i) + ($total_count - $enable_count) + 1) . '[]" value="' . $digit . '">';
                }
                for($i=$total_count;$i<7;$i++)
                    echo '<input type="hidden" class="lottery_digit_number lottery_digit_number_readonly" readonly="readonly" value="#" name="lottery_digit_number_' . (number_format($i) + 1) . '[]">';
            }

            if($values['pb'] == 1)
            {
                echo '<input class="lottery_digit_number lottery_plus_digit" readonly="readonly" value="+">';
                if($digits['db1'] == "#" || $digits['db1'] == "-")
                    $digits['db1'] = "";
                echo '<input class="lottery_digit_number" name="lottery_bonus_digit_number_1[]" value="' . $digits['db1'] . '">';
                echo '<input type="hidden" class="lottery_digit_number" name="lottery_bonus_digit_number_2[]" value="#">';
            } else if($values['pb'] == 2)
            {
                echo '<input class="lottery_digit_number lottery_plus_digit" readonly="readonly" value="+">';
                if($digits['db1'] == "#" || $digits['db1'] == "-")
                    $digits['db1'] = "";
                echo '<input class="lottery_digit_number" name="lottery_bonus_digit_number_1[]" value="' . $digits['db1'] . '">';
                if($digits['db2'] == "#" || $digits['db2'] == "-")
                    $digits['db2'] = "";
                echo '<input class="lottery_digit_number" name="lottery_bonus_digit_number_2[]" value="' . $digits['db2'] . '">';
            } else {
                echo '<input type="hidden" class="lottery_digit_number lottery_plus_digit" readonly="readonly" value="">';
                echo '<input type="hidden" class="lottery_digit_number" name="lottery_bonus_digit_number_1[]" value="#">';
                echo '<input type="hidden" class="lottery_digit_number" name="lottery_bonus_digit_number_2[]" value="#">';
            }

            echo '<input type="hidden" name="lottery_prize_id[]" value="' . $prize_id . '">';
            echo '</span><span class="prize_' . $index . '">' . $values['pp'] . ' ' . gamipress_get_points_type_plural($values['pt']) . '</span><button class="button button-primary lottery_add_winner_prize" id="add_prize_' . $index .'" style="width:50%">Add</button>
                    </span>';
            echo '<span class="lottery_winner_span_wrapper"><span></span><span class="alphabet_line_' . $index . '">';
            echo $lottery_match_alphabet_options[$values['pa']][$values['pm']];
            echo '</span><span></span><span></span></span></p>';



            $prize_winner_content = $this->get_lottery_winner_prizes($prize_id);
            for($winner_index=1;$winner_index<count($prize_winner_content);$winner_index++)
            {
                echo '<p class="small_prize small_prize_1">';
                echo '<span class="lottery_winner_span_wrapper">
                            <span></span>
                            <span class="lottery_six_digit_panel">';
                if($values['pa'] == 1)
                {
                    for($i=0;$i<$total_count;$i++)
                        echo '<span class="lottery_digit_number" style="line-height: 5px;">A</span>';
                } else {
                    for($i=0;$i<$total_count;$i++)
                        echo '<span class="lottery_digit_number" style="line-height: 5px;">' . chr(65+$i) . '</span>';
                }
                if($values['pb'] == 1 || $values['pb'] == 2)
                {
                    echo '<span></span>';
                    echo '<span class="lottery_digit_number" style="line-height: 5px;">L</span>';
                    if($values['pb'] == 2)
                        echo '<span class="lottery_digit_number" style="line-height: 5px;">L</span>';
                }
                echo '</span>
                            <span></span>
                            <span></span>
                    </span>';
                echo '<span class="lottery_winner_span_wrapper">';
                echo '<span></span>';
                echo '<span class="lottery_six_digit_panel six_digit_' . $index . '">';
                $digits = $prize_winner_content[$winner_index]['content'];
                $digits = unserialize($digits);
                if($alphabetline_box_count[$values['pa']][$values['pm']][3] == 0)
                {
                    for($i=0;$i<$enable_count;$i++)
                    {
                        $digit_index = 'd' . (number_format($i) + 1);
                        $digit = $digits[$digit_index];
                        if($digit == "#" || $digit == "-")
                            $digit = "";
                        echo '<input class="lottery_digit_number" name="lottery_digit_number_' . (number_format($i) + 1) . '[]" value="' . $digit . '">';
                    }
                    for($i=$enable_count;$i<$total_count;$i++)
                        echo '<input class="lottery_digit_number lottery_digit_number_readonly" readonly="readonly" value="-" name="lottery_digit_number_' . (number_format($i) + 1) . '[]">';
                    for($i=$total_count;$i<7;$i++)
                        echo '<input type="hidden" class="lottery_digit_number lottery_digit_number_readonly" readonly="readonly" value="#" name="lottery_digit_number_' . (number_format($i) + 1) . '[]">';
                }
                if($alphabetline_box_count[$values['pa']][$values['pm']][3] == 1)
                {
                    for($i=$enable_count;$i<$total_count;$i++)
                        echo '<input class="lottery_digit_number lottery_digit_number_readonly" readonly="readonly" value="-" name="lottery_digit_number_' . (number_format($i) - $enable_count + 1) . '[]">';
                    for($i=0;$i<$enable_count;$i++)
                    {
                        $digit_index = 'd' . (number_format($i) + ($total_count - $enable_count) + 1);
                        $digit = $digits[$digit_index];
                        if($digit == "#" || $digit == "-")
                            $digit = "";
                        echo '<input class="lottery_digit_number" name="lottery_digit_number_' . (number_format($i) + ($total_count - $enable_count) + 1) . '[]" value="' . $digit . '">';
                    }
                    for($i=$total_count;$i<7;$i++)
                        echo '<input type="hidden" class="lottery_digit_number lottery_digit_number_readonly" readonly="readonly" value="#" name="lottery_digit_number_' . (number_format($i) + 1) . '[]">';
                }

                if($values['pb'] == 1)
                {
                    echo '<input class="lottery_digit_number lottery_plus_digit" readonly="readonly" value="+">';
                    if($digits['db1'] == "#" || $digits['db1'] == "-")
                        $digits['db1'] = "";
                    echo '<input class="lottery_digit_number" name="lottery_bonus_digit_number_1[]" value="' . $digits['db1'] . '">';
                    echo '<input type="hidden" class="lottery_digit_number" name="lottery_bonus_digit_number_2[]" value="#">';
                } else if($values['pb'] == 2)
                {
                    echo '<input class="lottery_digit_number lottery_plus_digit" readonly="readonly" value="+">';
                    if($digits['db1'] == "#" || $digits['db1'] == "-")
                        $digits['db1'] = "";
                    echo '<input class="lottery_digit_number" name="lottery_bonus_digit_number_1[]" value="' . $digits['db1'] . '">';
                    if($digits['db2'] == "#" || $digits['db2'] == "-")
                        $digits['db2'] = "";
                    echo '<input class="lottery_digit_number" name="lottery_bonus_digit_number_2[]" value="' . $digits['db2'] . '">';
                } else {
                    echo '<input type="hidden" class="lottery_digit_number lottery_plus_digit" readonly="readonly" value="">';
                    echo '<input type="hidden" class="lottery_digit_number" name="lottery_bonus_digit_number_1[]" value="#">';
                    echo '<input type="hidden" class="lottery_digit_number" name="lottery_bonus_digit_number_2[]" value="#">';
                }

                echo '<input type="hidden" name="lottery_prize_id[]" value="' . $prize_id . '">';
                echo '</span><span class="prize_' . $index . '">' . $values['pp'] . ' ' . gamipress_get_points_type_plural($values['pt']) . ' </span><button class="button button-primary lottery_remove_winner_prize" id="remove_prize_' . $index .'" style="width:50%;background-color: #dc3545;border-color: #dc3545;padding:0;">Remove</button>
                        </span>';
                echo '<span class="lottery_winner_span_wrapper"><span></span><span class="alphabet_line_' . $index . ' border_bottom_wrapper">';
                echo $lottery_match_alphabet_options[$values['pa']][$values['pm']];
                echo '</span><span class="border_bottom_wrapper"></span><span class="border_bottom_wrapper"></span></span></p>';
            }
            echo '</div>';
        }
        /**
         * @Author: Igor
         * -------------------END------------------
         */
        echo '</div>';
    }

    /**
     * Get lottery prizes
     * @Author Igor
     *
     * @param int $post_id
     * @return array
     */

    public function get_lottery_prizes($post_id)
    {
        global $wpdb;
        $table = $wpdb->prefix . 'wc_lottery_prizes';
        $prize_content = $wpdb->get_results("SELECT * FROM $table WHERE product=$post_id AND status!='trash' ORDER BY `order`", ARRAY_A);
        return $prize_content;
    }

    /**
     * Get lottery prizes
     * @Author Adonis
     *
     * @param int $post_id
     * @return array
     */

    public function get_lottery_winner_prizes($lottery_prize_id)
    {
        global $wpdb;
        $table = $wpdb->prefix . 'wc_lottery_winner_prizes';
        $prize_content = $wpdb->get_results("SELECT * FROM $table WHERE lottery_prize_id=$lottery_prize_id", ARRAY_A);
        return $prize_content;
    }

    /**
     * Saves lottery prize to lottery_prizes table
     * @Author Igor
     *
     * @param int $post_id the post (product) identifier
     * @param int order the prize order
     * @param string content the content of prize ( sanitized)
     * @param string status the status of prize (removed or published)
     * @return int id
     */


    public function lottery_prize_save_data()
    {
        global $wpdb;
        $post_id = $_POST['post_id'];
        $order = $_POST['order'];
        $prize_name = $_POST['prize_name'];
        $prize_point = $_POST['prize_point'];
        $prize_point_type = $_POST['prize_point_type'];
        $prize_match_line = $_POST['prize_match_line'];
        $prize_match_bonus = $_POST['prize_match_bonus'];
        $prize_alphabet_line = $_POST['alphabet_line'];
        $content = serialize(array(
            'pn' => $prize_name,
            'pp' => $prize_point,
            'pt' => $prize_point_type,
            'pm' => $prize_match_line,
            'pb' => $prize_match_bonus,
            'pa' => $prize_alphabet_line
        ));
        $table = $wpdb->prefix . 'wc_lottery_prizes';
        $prize_count = $wpdb->get_var("SELECT COUNT(*) FROM $table WHERE product=$post_id AND status!='trash'");
        $res = $wpdb->insert($table, array('product' => $post_id, 'content' => $content, 'order' => $prize_count + 1, 'status' => 'draft'));
        wp_send_json($res ? [$wpdb->insert_id, $prize_count + 1] : null);
        exit();
    }

    /**
     * Updates lottery prize to lottery_prizes table
     * @Author Igor
     *
     * @param int $post_id the post (product) identifier
     * @param int order the prize order
     * @param string content the content of prize ( sanitized)
     * @param string status the status of prize (removed or published)
     * @return int id
     */


    public function lottery_prize_update_data($product_id, $order, $prize_name, $prize_point, $prize_point_type, $prize_match_line, $prize_match_bonus, $lottery_alphabet)
    {
        global $wpdb;
        $table = $wpdb->prefix . 'wc_lottery_prizes';
        $prize_content = $wpdb->get_var("SELECT content from $table WHERE product=$product_id AND `order`=$order");
        $content = unserialize($prize_content);
        $content['pn'] = $prize_name;
        $content['pp'] = $prize_point;
        $content['pt'] = $prize_point_type;
        $content['pm'] = $prize_match_line;
        $content['pb'] = $prize_match_bonus;
        $content['pa'] = $lottery_alphabet;
        $content = serialize($content);
        $wpdb->update($table, ['content' => $content], ['product' => $product_id, 'order' => $order]);
    }

    /**
     * Updates lottery winner prize to lottery_winner_prizes table
     * @Author Adonis
     *
     * @return int id
     */


    public function lottery_winner_prize_save_data($prize_id, $digit1, $digit2, $digit3, $digit4, $digit5, $digit6, $digit7, $bonus1, $bonus2)
    {
        global $wpdb;
        $table = $wpdb->prefix . 'wc_lottery_winner_prizes';

        if($digit1 != "-" && $digit1 != "#")
            $content['d1'] = $digit1;
        if($digit2 != "-" && $digit2 != "#")
            $content['d2'] = $digit2;
        if($digit3 != "-" && $digit3 != "#")
            $content['d3'] = $digit3;
        if($digit4 != "-" && $digit4 != "#")
            $content['d4'] = $digit4;
        if($digit5 != "-" && $digit5 != "#")
            $content['d5'] = $digit5;
        if($digit6 != "-" && $digit6 != "#")
            $content['d6'] = $digit6;
        if($digit7 != "-" && $digit7 != "#")
            $content['d7'] = $digit7;
        if($bonus1 != "-" && $bonus1 != "#")
            $content['db1'] = $bonus1;
        if($bonus2 != "-" && $bonus2 != "#")
            $content['db2'] = $bonus2;
        $content = serialize($content);
        $res = $wpdb->insert($table, array('lottery_prize_id' => $prize_id, 'content' => $content));
    }


    /**
     * remove selected prize
     *
     * @Author Igor
     * @param int id
     * @return boolean
     */
    public function lottery_remove_prize()
    {
        global $wpdb;
        $table = $wpdb->prefix . 'wc_lottery_prizes';
        $prize_id = $_POST['prize_id'];
        try {
            $row = $wpdb->get_results("SELECT * FROM $table WHERE id=$prize_id", ARRAY_A);
            $product = $row[0]['product'];
            $order = $row[0]['order'];
            $wpdb->delete($table, array('id' => $prize_id));
            $prizes_reorder = $wpdb->get_results("SELECT * FROM $table WHERE product=$product AND `order`>$order", ARRAY_A);
            $res = [];
            foreach ($prizes_reorder as $pr) {
                $wpdb->update($table, ['order' => intval($pr['order']) - 1], ['id' => $pr['id']]);
                $res[] = ['id' => $pr['id'], 'order' => intval($pr['order']) - 1];
            }
            wp_send_json([1, $res]);
            exit();
        } catch (Exception $err) {
            wp_send_json([0, $err->getMessage()]);
            exit();
        }
    }

    /**
     * remove selected prize
     *
     * @Author Adonis
     * @param int id
     * @return boolean
     */
    public function lottery_remove_winner_prize($prize_id)
    {
        global $wpdb;
        $table = $wpdb->prefix . 'wc_lottery_winner_prizes';
        $wpdb->delete($table, array('lottery_prize_id' => $prize_id));
    }

    /**
     * update order of prizes
     * @Author Igor
     * @return void
     */
    public function lottery_sort_prize()
    {
        global $wpdb;
        $table = $wpdb->prefix . 'wc_lottery_prizes';
        $prize_ids = $_POST['prize_ids'];
        $order = 1;
        foreach ($prize_ids as $pi) {
            $wpdb->update($table, ['order' => $order], ['id' => $pi]);
            $order++;
        }
        exit();
    }

    /**
     * update order of prizes
     * @Author Igor
     * @return void
     */
    public function participate_lottery()
    {
        global $wpdb;
        $userid = get_current_user_id();
        if($userid == 0)
        {
            wp_send_json(-1);
        }else{
            $content = substr($_POST['numbers'], 0, -1);
            $table = $wpdb->prefix . 'wc_lottery_pick_number';
            $url     = wp_get_referer();
            $post_id = url_to_postid( $url );
            $res = $wpdb->insert($table, array('userid' => $userid, 'productid' => $post_id, 'content' => $content));
            wp_send_json(1);
        }
        exit();
    }

    public function redirect_to_specific_page()
    {
        auth_redirect(); 
    }
    /**
     * Saves the data inputed into the product boxes, as post meta data
     *
     *
     * @param int $post_id the post (product) identifier
     * @param stdClass $post the post (product)
     *
     */
    public function product_save_data($post_id, $post)
    {

        $product_type = empty($_POST['product-type']) ? 'simple' : sanitize_title(wc_clean($_POST['product-type']));

        if ($product_type == 'lottery') {

            $product = wc_get_product($post_id);

            if (isset($_POST['_lottery_pick_number_alphabet'])) {
                update_post_meta($post_id, '_lottery_pick_number_alphabet', $_POST['_lottery_pick_number_alphabet']);
            }

            if (isset($_POST['_lottery_pick_numbers_random']) && $_POST['_lottery_pick_numbers_random'] == 'yes') {
                update_post_meta($post_id, '_lottery_pick_numbers_random', $_POST['_lottery_pick_numbers_random']);
            } else {
                update_post_meta($post_id, '_lottery_pick_numbers_random', 'no');
            }

            $range_from = $_POST['_lottery_number_range_from'];
            $range_to = $_POST['_lottery_number_range_to'];
            $user_pick = $_POST['_lottery_number_user_pick'];

            if($_POST['_lottery_pick_number_alphabet'] != "1")
            {
                $range_from = 0;
                $range_to = 9;
                $user_pick = 1;
            }

            if (isset($range_from)) {
                update_post_meta($post_id, '_lottery_number_range_from', $range_from);
            }

            if (isset($range_to)) {
                update_post_meta($post_id, '_lottery_number_range_to', $range_to);
            }

            if (isset($user_pick)) {
                update_post_meta($post_id, '_lottery_number_user_pick', $user_pick);
            }else {
                update_post_meta($post_id, '_lottery_number_user_pick', '1');
            }

            if (isset($_POST['_lottery_bonus_number'])) {
                update_post_meta($post_id, '_lottery_bonus_number', $_POST['_lottery_bonus_number']);
            }

            if (isset($_POST['_lottery_bonus_enabled'])) {
                update_post_meta($post_id, '_lottery_bonus_enabled', $_POST['_lottery_bonus_enabled']);
            }

            if (isset($_POST['_lottery_bonus_number_popup']) && $_POST['_lottery_bonus_number_popup'] == 'yes') {
                update_post_meta($post_id, '_lottery_bonus_number_popup', $_POST['_lottery_bonus_number_popup']);
            } else {
                update_post_meta($post_id, '_lottery_bonus_number_popup', 'no');
            }

            if (isset($_POST['_lottery_bonus_number_range_from'])) {
                update_post_meta($post_id, '_lottery_bonus_number_range_from', $_POST['_lottery_bonus_number_range_from']);
            }

            if (isset($_POST['_lottery_bonus_number_range_to'])) {
                update_post_meta($post_id, '_lottery_bonus_number_range_to', $_POST['_lottery_bonus_number_range_to']);
            }

            if (isset($_POST['_lottery_bonus_number_range_type'])) {
                update_post_meta($post_id, '_lottery_bonus_number_range_type', $_POST['_lottery_bonus_number_range_type']);
            } else {
                update_post_meta($post_id, '_lottery_bonus_number_range_type', 1);
            }

            if (isset($_POST['_lottery_number_allow_pick'])) {
                update_post_meta($post_id, '_lottery_number_allow_pick', $_POST['_lottery_number_allow_pick']);
            }else {
                update_post_meta($post_id, '_lottery_number_allow_pick', '1');
            }

            if (isset($_POST['_lottery_prize_name'])) {
                update_post_meta($post_id, '_lottery_prize_name', $_POST['_lottery_prize_name']);
            }

            if (isset($_POST['_lottery_prize'])) {
                update_post_meta($post_id, '_lottery_prize', $_POST['_lottery_prize']);
            }

            if (isset($_POST['_lottery_prize_gami_points'])) {
                update_post_meta($post_id, '_lottery_prize_gami_points', $_POST['_lottery_prize_gami_points']);
            }

            if (isset($_POST['_lottery_match_alphabet'])) {
                update_post_meta($post_id, '_lottery_match_alphabet', $_POST['_lottery_match_alphabet']);
            }

            if (isset($_POST['_lottery_bonus_number'])) {
                update_post_meta($post_id, '_lottery_bonus_number', $_POST['_lottery_bonus_number']);
            }

            $lmb = "";
            if (isset($_POST['_lottery_match_bonus'])) {
                $lmb = $_POST['_lottery_match_bonus'];
            }

            if(!isset($_POST['_lottery_bonus_enabled']) || $_POST['_lottery_bonus_enabled'] == "0")
            {
                $lmb = 0;
            }

            update_post_meta($post_id, '_lottery_match_bonus', $lmb);

            if(isset($_POST['lottery_prize']) && isset($_POST['lottery_prize_point']) && isset($_POST['lottery_prize_gp_point_type']) && isset($_POST['lottery_prize_match_alphabet']) && isset($_POST['lottery_prize_match_bonus'])){
                for($pi=0; $pi < count($_POST['lottery_prize']); $pi ++){
                    $lmb = "";
                    if (isset($_POST['lottery_prize_match_bonus'][$pi])) {
                        $lmb = $_POST['lottery_prize_match_bonus'][$pi];
                    }
                    if(!isset($_POST['_lottery_bonus_enabled']) || $_POST['_lottery_bonus_enabled'] == "0")
                    {
                        $lmb = 0;
                    }
                    $this->lottery_prize_update_data($post_id, $pi+1, $_POST['lottery_prize'][$pi], $_POST['lottery_prize_point'][$pi], $_POST['lottery_prize_gp_point_type'][$pi], $_POST['lottery_prize_match_alphabet'][$pi], $lmb, $_POST['_lottery_pick_number_alphabet']);
                }
            }

            if(isset($_POST['lottery_prize_id'])) {
                for($i=0;$i<count($_POST['lottery_prize_id']);$i++) {
                    $this->lottery_remove_winner_prize($_POST['lottery_prize_id'][$i]);
                }
                for($i=0;$i<count($_POST['lottery_prize_id']);$i++) {
                    if(($_POST['lottery_digit_number_1'][$i] == "" || $_POST['lottery_digit_number_1'][$i] == "-" || $_POST['lottery_digit_number_1'][$i] == "#") && ($_POST['lottery_digit_number_2'][$i] == "" || $_POST['lottery_digit_number_2'][$i] == "-" || $_POST['lottery_digit_number_2'][$i] == "#") && ($_POST['lottery_digit_number_3'][$i] == "" || $_POST['lottery_digit_number_3'][$i] == "-" || $_POST['lottery_digit_number_3'][$i] == "#") && ($_POST['lottery_digit_number_4'][$i] == "" || $_POST['lottery_digit_number_4'][$i] == "-" || $_POST['lottery_digit_number_4'][$i] == "#") && ($_POST['lottery_digit_number_5'][$i] == "" || $_POST['lottery_digit_number_5'][$i] == "-" || $_POST['lottery_digit_number_5'][$i] == "#") && ($_POST['lottery_digit_number_6'][$i] == "" || $_POST['lottery_digit_number_6'][$i] == "-" || $_POST['lottery_digit_number_6'][$i] == "#") && ($_POST['lottery_digit_number_7'][$i] == "" || $_POST['lottery_digit_number_7'][$i] == "-" || $_POST['lottery_digit_number_7'][$i] == "#") && ($_POST['lottery_bonus_digit_number_1'][$i] == "" || $_POST['lottery_bonus_digit_number_1'][$i] == "-" || $_POST['lottery_bonus_digit_number_1'][$i] == "#") && ($_POST['lottery_bonus_digit_number_2'][$i] == "" || $_POST['lottery_bonus_digit_number_2'][$i] == "-" || $_POST['lottery_bonus_digit_number_2'][$i] == "#"))
                        continue;
                    $lmb1 = "";
                    $lmb2 = "";
                    if (isset($_POST['lottery_bonus_digit_number_1'][$i])) {
                        $lmb1 = $_POST['lottery_bonus_digit_number_1'][$i];
                    }

                    if (isset($_POST['lottery_bonus_digit_number_2'][$i])) {
                        $lmb2 = $_POST['lottery_bonus_digit_number_2'][$i];
                    }
                        
                    if(isset($_POST['_lottery_bonus_enabled']) && $_POST['_lottery_bonus_enabled'] == "1")
                    {
                        if(isset($_POST['_lottery_number_allow_pick']) && $_POST['_lottery_number_allow_pick'] == "1")
                            $lmb2 = "#";
                    }else {
                        $lmb1 = "#";
                        $lmb2 = "#";
                    }
                    //sort
                    if($_POST['_lottery_pick_number_alphabet'] == 1)
                    {
                        for($j=1;$j<=7;$j++)
                        {
                            if($_POST['lottery_digit_number_' . $j][$i] == '#' || $_POST['lottery_digit_number_' . $j][$i] == '-')
                                break;
                            for($k=(intval($j)+1);$k<=7;$k++)
                            {
                                if($_POST['lottery_digit_number_' . $k][$i] == '#' || $_POST['lottery_digit_number_' . $k][$i] == '-')
                                    continue;
                                if($_POST['lottery_digit_number_' . $j][$i] > $_POST['lottery_digit_number_' . $k][$i])
                                {
                                    $temp = $_POST['lottery_digit_number_' . $j][$i];
                                    $_POST['lottery_digit_number_' . $j][$i] = $_POST['lottery_digit_number_' . $k][$i];
                                    $_POST['lottery_digit_number_' . $k][$i] = $temp;
                                }
                            }
                        }
                    }
                    if($lmb1 != "#" && $lmb2 != "#" && $lmb1 != "-" && $lmb2 != "-")
                        if($lmb1 > $lmb2)
                        {
                            $temp = $lmb1;
                            $lmb1 = $lmb2;
                            $lmb2 = $temp;
                        }
                    $this->lottery_winner_prize_save_data($_POST['lottery_prize_id'][$i], 
                        $_POST['lottery_digit_number_1'][$i], 
                        $_POST['lottery_digit_number_2'][$i], 
                        $_POST['lottery_digit_number_3'][$i], 
                        $_POST['lottery_digit_number_4'][$i], 
                        $_POST['lottery_digit_number_5'][$i], 
                        $_POST['lottery_digit_number_6'][$i], 
                        $_POST['lottery_digit_number_7'][$i], 
                        $lmb1, 
                        $lmb2);
                }
            }


//            if (isset($_POST['_max_tickets']) && !empty($_POST['_max_tickets'])) {
//
//                update_post_meta($post_id, '_manage_stock', 'yes');
//
//                if (get_post_meta($post_id, '_lottery_participants_count', true)) {
//                    update_post_meta($post_id, '_stock', intval(wc_clean($_POST['_max_tickets'])) - intval(get_post_meta($post_id, '_lottery_participants_count', true)));
//                } else {
//                    update_post_meta($post_id, '_stock', wc_clean($_POST['_max_tickets']));
//                }
//
//                update_post_meta($post_id, '_backorders', 'no');
//            } else {
//
//                update_post_meta($post_id, '_manage_stock', 'no');
//                update_post_meta($post_id, '_backorders', 'no');
//                update_post_meta($post_id, '_stock_status', 'instock');
//
//            }

            if (isset($_POST['_lottery_price']) && '' !== $_POST['_lottery_price']) {

                $lottey_price = wc_format_decimal(wc_clean($_POST['_lottery_price']));

                update_post_meta($post_id, '_lottery_price', $lottey_price);
                update_post_meta($post_id, '_regular_price', $lottey_price);
                update_post_meta($post_id, '_price', $lottey_price);

            } else {
                delete_post_meta($post_id, '_lottery_price');
                delete_post_meta($post_id, '_regular_price');
                delete_post_meta($post_id, '_price');

            }

            if (isset($_POST['_lottery_sale_price']) && '' !== $_POST['_lottery_sale_price']) {
                $lottey_sale_price = wc_format_decimal(wc_clean($_POST['_lottery_sale_price']));
                update_post_meta($post_id, '_lottery_sale_price', $lottey_sale_price);
                update_post_meta($post_id, '_sale_price', $lottey_sale_price);
                update_post_meta($post_id, '_price', $lottey_sale_price);
            } else {
                delete_post_meta($post_id, '_lottery_sale_price');
                delete_post_meta($post_id, '_sale_price');
            }
            if (($_POST['_lottery_price'] == 0 || !isset($_POST['_lottery_price'])) && (!isset($_POST['_max_tickets_per_user']) or empty($_POST['_max_tickets_per_user']))) {
                update_post_meta($post_id, '_sold_individually', 'yes');
            }
//            if (isset($_POST['_max_tickets_per_user']) && !empty($_POST['_max_tickets_per_user'])) {
//                update_post_meta($post_id, '_max_tickets_per_user', wc_clean($_POST['_max_tickets_per_user']));
//                if ($_POST['_max_tickets_per_user'] <= 1) {
//                    update_post_meta($post_id, '_sold_individually', 'yes');
//                } else {
//                    update_post_meta($post_id, '_sold_individually', 'no');
//                }
//            } else {
//                delete_post_meta($post_id, '_max_tickets_per_user');
//                update_post_meta($post_id, '_sold_individually', 'no');
//            }
//
//            if (isset($_POST['_lottery_num_winners']) && !empty($_POST['_lottery_num_winners'])) {
//                update_post_meta($post_id, '_lottery_num_winners', wc_clean($_POST['_lottery_num_winners']));
//                if ($_POST['_lottery_num_winners'] <= 1) {
//                    update_post_meta($post_id, '_lottery_multiple_winner_per_user', 'no');
//                } else {
//                    if (isset($_POST['_lottery_multiple_winner_per_user']) && !empty($_POST['_lottery_multiple_winner_per_user'])) {
//                        update_post_meta($post_id, '_lottery_multiple_winner_per_user', 'yes');
//                    } else {
//                        update_post_meta($post_id, '_lottery_multiple_winner_per_user', 'no');
//                    }
//                }
//            }
//
//            if (isset($_POST['_min_tickets'])) {
//                update_post_meta($post_id, '_min_tickets', wc_clean($_POST['_min_tickets']));
//            } else {
//                delete_post_meta($post_id, '_min_tickets');
//            }
//            if (isset($_POST['_max_tickets'])) {
//                update_post_meta($post_id, '_max_tickets', wc_clean($_POST['_max_tickets']));
//            } else {
//                delete_post_meta($post_id, '_max_tickets');
//            }
           if (isset($_POST['_lottery_dates_from'])) {
               update_post_meta($post_id, '_lottery_dates_from', wc_clean($_POST['_lottery_dates_from']));
           }
           if (isset($_POST['_lottery_dates_to'])) {
               update_post_meta($post_id, '_lottery_dates_to', wc_clean($_POST['_lottery_dates_to']));
           }

            do_action('lottery_product_save_data', $post_id, $post);

//            if (isset($_POST['_relist_lottery_dates_from']) && isset($_POST['_relist_lottery_dates_to']) && !empty($_POST['_relist_lottery_dates_from']) && !empty($_POST['_relist_lottery_dates_to'])) {
//                $this->do_relist($post_id, $_POST['_relist_lottery_dates_from'], $_POST['_relist_lottery_dates_to']);
//            }
//            if (isset($_POST['_extend_lottery_dates_to']) && !empty($_POST['_extend_lottery_dates_to'])) {
//                $this->do_extend($post_id, $_POST['_extend_lottery_dates_to']);
//            }
//
//            if (isset($_POST['clear_on_hold_orders'])) {
//                delete_post_meta($post_id, '_order_hold_on');
//            }


            $product->lottery_update_lookup_table();
        }
    }

    /**
     * Relist  lottery
     *
     * @access public
     * @param int, string, string
     * @return void
     *
     */
    function do_relist($post_id, $relist_from, $relist_to)
    {
        global $wpdb;

        update_post_meta($post_id, '_lottery_dates_from', stripslashes($relist_from));
        update_post_meta($post_id, '_lottery_dates_to', stripslashes($relist_to));
        update_post_meta($post_id, '_lottery_relisted', current_time('mysql'));
        delete_post_meta($post_id, '_lottery_closed');
        delete_post_meta($post_id, '_lottery_started');
        delete_post_meta($post_id, '_lottery_has_started');
        delete_post_meta($post_id, '_lottery_fail_reason');
        delete_post_meta($post_id, '_lottery_participant_id');
        delete_post_meta($post_id, '_lottery_participants_count');
        delete_post_meta($post_id, '_lottery_winners');
        delete_post_meta($post_id, '_participant_id');
        update_post_meta($post_id, '_lottery_relisted', current_time('mysql'));
        delete_post_meta($post_id, '_order_hold_on');

        $lottery_max_tickets = get_post_meta($post_id, '_max_tickets', true);
        update_post_meta($post_id, '_stock', $lottery_max_tickets);
        update_post_meta($post_id, '_stock_status', 'instock');

        $order_id = get_post_meta($post_id, '_order_id', true);
        // check if the custom field has a value
        if (!empty($order_id)) {
            delete_post_meta($post_id, '_order_id');
        }

        $wpdb->delete(
            $wpdb->usermeta, array(
            'meta_key' => 'my_lotteries',
            'meta_value' => $post_id,
        ), array('%s', '%s')
        );

        if (!empty($_POST['_lottery_delete_log_on_relist'])) {

            if (!isset($_POST['save_lottery_data']) || !wp_verify_nonce(sanitize_text_field($_POST['save_lottery_data']), 'save_lottery_data_' . $post_id)) {
                wp_die(esc_html__('Action failed. Please refresh the page and retry.', 'woocommerce'));
            } else {
                if ('yes' === sanitize_text_field($_POST['_lottery_delete_log_on_relist'])) {
                    $this->del_lottery_logs($post_id);
                }
            }
        }

        do_action('woocommerce_lottery_do_relist', $post_id, $relist_from, $relist_to);
    }

    /**
     * Delete logs when lottery is deleted
     *
     * @access public
     * @param string
     * @return void
     *
     */
    function del_lottery_logs($post_id)
    {
        global $wpdb;

        if ($wpdb->get_var($wpdb->prepare('SELECT lottery_id FROM ' . $wpdb->prefix . 'wc_lottery_log WHERE lottery_id = %d', $post_id))) {
            $wpdb->query($wpdb->prepare('DELETE FROM ' . $wpdb->prefix . 'wc_lottery_log WHERE lottery_id = %d', $post_id));
        }

        return true;
    }

    /**
     * Extend  lottery
     *
     * @access public
     * @param int, string, string
     * @return void
     *
     */
    function do_extend($post_id, $extend_to)
    {
        update_post_meta($post_id, '_lottery_dates_to', stripslashes($extend_to));
        update_post_meta($post_id, '_lottery_extended', current_time('mysql'));
        delete_post_meta($post_id, '_lottery_closed');
        delete_post_meta($post_id, '_lottery_fail_reason');

        do_action('woocommerce_lottery_do_extend', $post_id, $extend_to);
    }

    /**
     * Add lottery column in product list in wp-admin
     *
     * @access public
     * @param array
     * @return array
     *
     */
    function woocommerce_simple_lottery_order_column_lottery($defaults)
    {

        $defaults['lottery'] = "<img src='" . plugin_dir_url(__FILE__) . 'images/lottery.png' . "' alt='" . __('Lottery', 'wc_lottery') . "' />";

        return $defaults;
    }

    /**
     * Add lottery icons in product list in wp-admin
     *
     * @access public
     * @param string, string
     * @return void
     *
     */
    function woocommerce_simple_lottery_order_column_lottery_content($column_name, $post_ID)
    {

        if ($column_name == 'lottery') {
            $class = '';

            $product_data = wc_get_product($post_ID);
            if ($product_data) {
                $product_data_type = method_exists($product_data, 'get_type') ? $product_data->get_type() : $product_data->product_type;
                if (is_object($product_data) && $product_data_type == 'lottery') {
                    if ($product_data->is_closed()) {
                        $class .= ' finished ';
                    }

                    echo "<img src='" . plugin_dir_url(__FILE__) . 'images/lottery.png' . "' alt='" . __('Lottery', 'wc_lottery') . "' class='$class' />";
                }
                if (get_post_meta($post_ID, '_lottery', true)) {
                    echo "<img src='" . plugin_dir_url(__FILE__) . 'images/lottery.png' . "' alt='" . __('Lottery', 'wc_lottery') . "' class='order' />";
                }
            }
        }
    }

    /**
     * Add dropdown to filter lottery
     *
     * @param  (wp_query object) $query
     *
     * @return Void
     */
    function admin_posts_filter_restrict_manage_posts()
    {

        //only add filter to post type you want
        if (isset($_GET['post_type']) && $_GET['post_type'] == 'product') {
            $values = array(
                'Active' => 'active',
                'Finished' => 'finished',
                'Fail' => 'fail',

            );
            ?>
            <select name="wc_lottery_filter">
                <option value=""><?php _e('Lottery filter By ', 'wc_lottery'); ?></option>
                <?php
                $current_v = isset($_GET['wcl_filter']) ? $_GET['wcl_filter'] : '';
                foreach ($values as $label => $value) {
                    printf(
                        '<option value="%s"%s>%s</option>',
                        $value,
                        $value == $current_v ? ' selected="selected"' : '',
                        $label
                    );
                }
                ?>
            </select>
            <?php
        }
    }

    /**
     * If submitted filter by post meta
     *
     * make sure to change META_KEY to the actual meta key
     * and POST_TYPE to the name of your custom post type
     * @param  (wp_query object) $query
     *
     * @return Void
     */
    function admin_posts_filter($query)
    {
        global $pagenow;

        if (isset($_GET['post_type']) && $_GET['post_type'] == 'product' && is_admin() && $pagenow == 'edit.php' && isset($_GET['wc_lottery_filter']) && $_GET['wc_lottery_filter'] != '') {

            switch ($_GET['wc_lottery_filter']) {
                case 'active':
                    $query->query_vars['meta_query'] = array(

                        array(
                            'key' => '_lottery_closed',
                            'compare' => 'NOT EXISTS',
                        ),
                    );

                    $taxquery = $query->get('tax_query');
                    if (!is_array($taxquery)) {
                        $taxquery = array();
                    }
                    $taxquery [] =
                        array(
                            'taxonomy' => 'product_type',
                            'field' => 'slug',
                            'terms' => 'lottery',

                        );

                    $query->set('tax_query', $taxquery);
                    break;
                case 'finished':
                    $query->query_vars['meta_query'] = array(

                        array(
                            'key' => '_lottery_closed',
                            'compare' => 'EXISTS',
                        ),
                    );

                    break;
                case 'fail':
                    $query->query_vars['meta_key'] = '_lottery_closed';
                    $query->query_vars['meta_value'] = '1';

                    break;

            }
        }
    }

    /**
     *  Add lottery setings tab to woocommerce setings page
     *
     * @access public
     *
     */
    function lottery_settings_class($settings)
    {

        $settings[] = include plugin_dir_path(dirname(__FILE__)) . 'admin/class-wc-settings-lottery.php';
        return $settings;
    }

    /**
     *  Add meta box to the product editing screen
     *
     * @access public
     *
     */
    function woocommerce_simple_lottery_meta()
    {

        global $post;

        $product_data = wc_get_product($post->ID);
        if ($product_data) {
            $product_data_type = method_exists($product_data, 'get_type') ? $product_data->get_type() : $product_data->product_type;
            if ($product_data_type == 'lottery') {
                add_meta_box('Lottery Winners', __('Lottery Winners', 'wc_lottery'), array($this, 'woocommerce_lottery_winner_meta_callback'), 'product', 'normal', 'default');
                add_meta_box('Lottery', __('Lottery', 'wc_lottery'), array($this, 'woocommerce_simple_lottery_meta_callback'), 'product', 'normal', 'default');
            }
        }

    }
    /** 
     * get all participants
    */
    function lottery_get_all_participants(){
        global $wpdb;
        global $post;
        $prize_content = $this->get_lottery_prizes($post->ID);
        foreach($prize_content as &$prize){
            $prize['winner_prizes'] = $this->get_lottery_winner_prizes($prize['id']);
        }
        return $prize_content;
    }

    /** 
     * get all winners
    */
    function lottery_get_all_winners(){
        global $wpdb;
        global $post;
        $product_data = wc_get_product($post->ID);

        $prize_content = $this->get_lottery_prizes($post->ID);
        $prizes = [];

        $lottery_history = apply_filters('woocommerce__lottery_history_data', $product_data->lottery_history());

        //(0->prefix,1->suffix,2->individual match)
        $pref_suff = [
            1 => [
                6 => 2,
                5 => 2,
                4 => 2,
                3 => 2,
                2 => 2,
                1 => 2,
            ],
            3 => [
                1 => 0,
            ],
            4 => [
                1 => 0,
            ],
            5 => [
                1 => 0,
                2 => 0,
                3 => 1,
                4 => 0,
                5 => 1,
                6 => 1,
            ],
            6 => [
                1 => 1,
                2 => 0,
                3 => 1,
                4 => 0,
                5 => 1,
                6 => 0,
            ]
        ];

        // get all history with ticket_numbers
        $selected_winner_ticket = [];

        // get all winners from winner_prizes and history
        foreach($prize_content as $prize){
            $content = unserialize($prize['content']);
            $winner_prizes = $this->get_lottery_winner_prizes($prize['id']);
            $winner_prize_tickets = [];
            foreach($winner_prizes as $winner_prize){
                $winner_content = unserialize($winner_prize['content']);
                $winner_ticket = "";
                if($winner_content["d1"] != "-" && $winner_content["d1"] != "#" && $winner_content["d1"] != "")
                    $winner_ticket = $winner_ticket . "-" . $winner_content["d1"];
                if($winner_content["d2"] != "-" && $winner_content["d2"] != "#" && $winner_content["d2"] != "")
                    $winner_ticket = $winner_ticket ."-" . $winner_content["d2"];
                if($winner_content["d3"] != "-" && $winner_content["d3"] != "#" && $winner_content["d3"] != "")
                    $winner_ticket = $winner_ticket ."-" . $winner_content["d3"];
                if($winner_content["d4"] != "-" && $winner_content["d4"] != "#" && $winner_content["d4"] != "")
                    $winner_ticket = $winner_ticket ."-" . $winner_content["d4"];
                if($winner_content["d5"] != "-" && $winner_content["d5"] != "#" && $winner_content["d5"] != "")
                    $winner_ticket = $winner_ticket ."-" . $winner_content["d5"];
                if($winner_content["d6"] != "-" && $winner_content["d6"] != "#" && $winner_content["d6"] != "")
                    $winner_ticket = $winner_ticket ."-" . $winner_content["d6"];
                if($winner_content["d7"] != "-" && $winner_content["d7"] != "#" && $winner_content["d7"] != "")
                    $winner_ticket = $winner_ticket ."-" . $winner_content["d7"];
                if($winner_content["db1"] != "-" && $winner_content["db1"] != "#" && $winner_content["db1"] != "")
                    $winner_ticket = $winner_ticket ."+" . $winner_content["db1"];
                if($winner_content["db2"] != "-" && $winner_content["db2"] != "#" && $winner_content["db2"] != "")
                    $winner_ticket = $winner_ticket ."+" . $winner_content["db2"];
                $winner_ticket = substr($winner_ticket, 1);
                if($content['pa'] != 1)
                    $winner_ticket = str_replace("-", "", $winner_ticket);
                $ticket_winners = [];

                foreach($lottery_history as $history){
                    if(in_array($history->id, $selected_winner_ticket))
                    {
                        continue;
                    }
                    $history_ticket_number = $history->ticket_numbers;
                    $is_winner = false;
                    $history_ticket_number_main = explode("+", $history_ticket_number)[0];
                    $history_ticket_number_bonus = explode("+", $history_ticket_number)[1];
                    if(isset(explode("+", $history_ticket_number)[2]))
                        $history_ticket_number_bonus .= "+" . explode("+", $history_ticket_number)[2];

                    $winner_ticket_number_main = explode("+", $winner_ticket)[0];
                    $winner_ticket_number_bonus = explode("+", $winner_ticket)[1];
                    if(isset(explode("+", $winner_ticket)[2]))
                        $winner_ticket_number_bonus .= "+" . explode("+", $winner_ticket)[2];

                    if($pref_suff[$content['pa']][$content['pm']] == 0)
                    {
                        //prefix
                        if(substr($history_ticket_number_main, 0, strlen($winner_ticket_number_main)) == $winner_ticket_number_main)
                            if($winner_ticket_number_bonus != "")
                            {
                                if($history_ticket_number_bonus == $winner_ticket_number_bonus)
                                    $is_winner = true;
                            } else {
                                $is_winner = true;
                            }
                    }else if($pref_suff[$content['pa']][$content['pm']] == 1)
                    {
                        //suffix
                        if(substr($history_ticket_number_main, -strlen($winner_ticket_number_main)) == $winner_ticket_number_main)
                            if($winner_ticket_number_bonus != "")
                            {
                                if($history_ticket_number_bonus == $winner_ticket_number_bonus)
                                    $is_winner = true;
                            } else {
                                $is_winner = true;
                            }
                    }else {
                        $winner_ticket_number_arr = explode("-", $winner_ticket_number_main);
                        $history_ticket_number_arr = explode("-", $history_ticket_number_main);
                        $is_match = true;
                        foreach($winner_ticket_number_arr as $winner_ticket_number_ind)
                        {
                            if(!in_array($winner_ticket_number_ind, $history_ticket_number_arr))
                                $is_match = false;
                        }
                        if($is_match == true)
                            if($winner_ticket_number_bonus != "")
                            {
                                if($history_ticket_number_bonus == $winner_ticket_number_bonus)
                                    $is_winner = true;
                            } else {
                                $is_winner = true;
                            }
                    }

                    if($is_winner == true)
                    {
                        $ticket_winners[] = ['user'=> $history->userid, 'order' => $history->orderid, 'history_id'=>$history->id, 'history_pay'=>$history->payout, 'history_ticket_number'=>$history->ticket_numbers];
                        $selected_winner_ticket[] = $history->id;
                    }
                }
                $winner_prize_tickets[] = [$winner_ticket, $ticket_winners];
            }

            $prizes[] = ['id' => $prize['id'], 'prize_name'=>$content['pn'], 'tickets'=>$winner_prize_tickets, 'pt'=>$content['pt'], 'pp'=>$content['pp'], 'pm'=>$content['pm'], 'pa'=>$content['pa'], 'pt_plural'=>gamipress_get_points_type_plural($content['pt'])];
        }
        
        return $prizes;

    }

    function woocommerce_lottery_winner_meta_callback()
    {
    ?>
    <div class="winner_panel">
        <p class="winner_line winner_line_header">
            <span class="winner_category">Category</span>
            <span class="winner_ticket_number">Ticket Number</span>
            <span class="winner_user">User</span>
            <span class="winner_order">Order#</span>
            <span class="winner_points">Points</span>
            <span class="winner_action">Action</span>
        </p>
        <?php
            // echo '<pre>';
            // print_r($this->lottery_get_all_winners());
            // echo '</pre>';
            $winners = $this->lottery_get_all_winners();
            foreach($winners as $winner)
                foreach($winner['tickets'] as $winner_ticket) {
                    if(empty($winner_ticket[1])) {
        ?>
                        <p class="winner_line winner_line_prize">
                        <span class="winner_category"><?php echo $winner['prize_name']; ?></span>
                        <span class="winner_ticket_number"><?php echo $winner_ticket[0] ?></span>
                        <span class="winner_user"></span>
                        <span class="winner_order"></span>
                        <span class="winner_points"></span>
                        <span class="winner_action">No Winner</span>
                    </p>
        <?php
                    }else
                    foreach($winner_ticket[1] as $winner_history_ticket) {
        ?>
        <p class="winner_line winner_line_prize">
            <span class="winner_category"><?php echo $winner['prize_name']; ?></span>
            <span class="winner_ticket_number"><?php echo $winner_ticket[0] ?></span>
            <span class="winner_user">
                <a href='<?php echo get_edit_user_link($winner_history_ticket["user"]); ?>'><?php echo get_userdata($winner_history_ticket["user"])->display_name; ?></a>
            </span>
            <span class="winner_order">
                <?php $order_url = admin_url('post.php?post=' . $winner_history_ticket["order"] . '&action=edit'); ?>
                <a href='<?php echo $order_url ?>'><?php echo $winner_history_ticket["order"];?></a>
            </span>
            <span class="winner_points"><?php echo $winner['pp']; echo ' '; echo ( gamipress_get_points_type_plural($winner['pt'])); ?> </span>
            <span class="winner_action">
                <?php
                    if($winner_history_ticket['history_pay'] == 0)
                    {
                        echo '<button class="button button-primary button-large payout" onclick="onPayout(' . $winner_history_ticket["history_id"]  . ',' . $winner_history_ticket["user"] . ',' . $winner['pt'] . ',' . $winner['pp'] . ')" id="payout-' . $winner_history_ticket["history_id"] . '">Payout</button>';
                        echo '<button class="button button-primary button-large paid" disabled style="display: none;" id="paid-' . $winner_history_ticket["history_id"] . '">Paid</button>';
                        echo '<button class="button button-primary button-large return-payout" style="background-color: #ffa500; border-color: #ffa500; display: none;" id="return-' . $winner_history_ticket["history_id"] . '" onclick="onReturnPayout(' . $winner_history_ticket["history_id"]  . ',' . $winner_history_ticket["user"] . ',' . $winner['pt'] . ',' . $winner['pp'] . ')">Return</button>';
                    }
                    else
                    {
                        echo '<button class="button button-primary button-large paid" disabled id="paid-' . $winner_history_ticket["history_id"] . '">Paid</button>';
                        echo '<button class="button button-primary button-large return-payout" style="background-color: #ffa500; border-color: #ffa500;" id="return-' . $winner_history_ticket["history_id"] . '" onclick="onReturnPayout(' . $winner_history_ticket["history_id"]  . ',' . $winner_history_ticket["user"] . ',' . $winner['pt'] . ',' . $winner['pp'] . ')">Return</button>';
                        echo '<button class="button button-primary button-large payout" onclick="onPayout(' . $winner_history_ticket["history_id"]  . ',' . $winner_history_ticket["user"] . ',' . $winner['pt'] . ',' . $winner['pp'] . ')" style="display: none;" id="payout-' . $winner_history_ticket["history_id"] . '">Payout</button>';
                    }
                ?>
            </span>
        </p>
        
        <?php
            }
        }
        ?>
    </div>
    <?php
    }

    /**
     *  Callback for adding a meta box to the product editing screen used in woocommerce_simple_lottery_meta
     *
     * @access public
     *
     */
    function woocommerce_simple_lottery_meta_callback()
    {

    ?>
    <?php
        global $post;
        $product_data = wc_get_product($post->ID);
        if (!$product_data && $product_data->get_type() !== 'lottery') {
            return;
        }

        $lottery_winers = get_post_meta($post->ID, '_lottery_winners');
        $order_hold_on = get_post_meta($post->ID, '_order_hold_on');

        ?>
        <?php
        if ($order_hold_on) {
            $orders_links_on_hold = '';
            echo '<p>';
            _e('Some on hold orders are preventing this lottery to end. Can you please check it! ', 'wc_lottery');
            foreach (array_unique($order_hold_on) as $key => $order_hold_on_id) {
                $orders_links_on_hold .= "<a href='" . admin_url('post.php?post=' . $order_hold_on_id . '&action=edit') . "'>$order_hold_on_id</a>, ";
            }
            echo rtrim($orders_links_on_hold, ', ');
            echo "<form><input type='hidden' name='clear_on_hold_orders' value='1' >";
            echo " <br><button class='button button-primary clear_orders_on_hold'  data-product_id='" . $product_data->get_id() . "'>" . __('Clear all on hold orders! ', 'wc_lottery') . '</button>';
            echo '</form>';
            echo '</p>';

        }

        $lottery_relisted = $product_data->get_lottery_relisted();
        if (!empty($lottery_relisted)) {
            ?>
            <p><?php esc_html_e('Lottery has been relisted on:', 'wc_lottery'); ?><?php echo date_i18n(get_option('date_format'), strtotime($lottery_relisted)) . ' ' . date_i18n(get_option('time_format'), strtotime($lottery_relisted)); ?></p>
            <?php
        }

        ?>
        <?php if (($product_data->is_closed() === true) and ($product_data->is_started() === true)) : ?>
        <p><?php _e('Lottery has finished', 'wc_lottery'); ?></p>
        <?php
        if ($product_data->get_lottery_fail_reason() == '1') {
            echo '<p>';
            _e('Lottery failed because there were no participants', 'wc_lottery');
            echo '</p>';
        } elseif ($product_data->get_lottery_fail_reason() == '2') {
            echo '<p>';
            _e('Lottery failed because there was not enough participants', 'wc_lottery');
            echo " <button class='button button-primary do-api-refund' href='#' id='lottery-refund' data-product_id='" . $product_data->get_id() . "'>";
            _e('Refund ', 'wc_lottery');
            echo '</button>';
            echo '<div id="refund-status"></div>';
            echo '<//p>';
        }
        if ($lottery_winers) {

            if (count($lottery_winers) === 1) {

                $winnerid = reset($lottery_winers);
                if (!empty($winnerid)) {
                    ?>
                    <p><?php _e('Lottery winner is', 'wc_lottery'); ?>: <span><a
                                    href='<?php echo get_edit_user_link($winnerid); ?>'><?php echo get_userdata($winnerid)->display_name; ?></a></span></p>
                <?php } ?>
            <?php } else { ?>

                <p><?php _e('Lottery winners are', 'wc_lottery'); ?>:
                <ul>
                    <?php
                    foreach ($lottery_winers as $key => $winnerid) {
                        if ($winnerid > 0) {
                            ?>
                            <li><a href='<?php get_edit_user_link($winnerid); ?>'><?php echo get_userdata($winnerid)->display_name; ?></a></li>
                            <?php
                        }
                    }
                    ?>
                </ul>

                </p>

            <?php } ?>

        <?php } ?>

    <?php endif; ?>
        <?php
        if (get_option('simple_lottery_history_admin', 'yes') == 'yes') :
            $lottery_history = apply_filters('woocommerce__lottery_history_data', $product_data->lottery_history());
            $heading = esc_html(apply_filters('woocommerce_lottery_history_heading', __('Lottery History', 'wc_lottery')));
            ?>
            <h2><?php echo $heading; ?></h2>
            <table class="lottery-table">
                <thead>
                <tr>
                    <th><?php _e('Date', 'wc_lottery'); ?></th>
                    <th><?php _e('Ticket Number', 'wc_lottery'); ?></th>
                    <th><?php _e('User', 'wc_lottery'); ?></th>
                    <th><?php _e('Order', 'wc_lottery'); ?></th>
                    <th class="actions"><?php _e('Actions', 'wc_lottery'); ?></th>
                </tr>
                </thead>

                <?php
                if ($lottery_history) :
                    foreach ($lottery_history as $history_value) {

                        if ($history_value->date < $product_data->get_lottery_relisted() && !isset($displayed_relist)) {
                            echo '<tr>';
                            echo '<td class="date">' . date_i18n(get_option('date_format'), strtotime($product_data->get_lottery_dates_from())) . ' ' . date_i18n(get_option('time_format'), strtotime($product_data->get_lottery_dates_from())) . '</td>';
                            echo '<td colspan="5"  class="relist">';
                            echo __('Lottery relisted', 'wc_lottery');
                            echo '</td>';
                            echo '</tr>';
                            $displayed_relist = true;
                        }
                        echo '<tr>';
                        echo '<td class="date">' . date_i18n(get_option('date_format'), strtotime($history_value->date)) . ' ' . date_i18n(get_option('time_format'), strtotime($history_value->date)) . '</td>';
                        echo '<td class="date">' . $history_value->ticket_numbers . '</td>';
                        echo "<td class='username'><a href='" . get_edit_user_link($history_value->userid) . "'>" . get_userdata($history_value->userid)->display_name . '</a></td>';
                        echo "<td class='username'><a href='" . admin_url('post.php?post=' . $history_value->orderid . '&action=edit') . "'>" . $history_value->orderid . '</a></td>';
                        echo "<td class='action'> <a href='#' data-id='" . $history_value->id . "' data-postid='" . $post->ID . "'    >" . __('Delete', 'wc_lottery') . '</a></td>';
                        echo '</tr>';
                    }
                endif;
                ?>
                <tr class="start">
                    <?php
                    if ($product_data->is_started() === true) {
                        echo '<td class="date">' . date_i18n(get_option('date_format'), strtotime($product_data->get_lottery_dates_from())) . ' ' . date_i18n(get_option('time_format'), strtotime($product_data->get_lottery_dates_from())) . '</td>';
                        echo '<td colspan="3"  class="started">';
                        echo apply_filters('lottery_history_started_text', __('Lottery started', 'wc_lottery'), $product_data);
                        echo '</td>';

                    } else {
                        echo '<td  class="date">' . date_i18n(get_option('date_format'), strtotime($product_data->get_lottery_dates_from())) . ' ' . date_i18n(get_option('time_format'), strtotime($product_data->get_lottery_dates_from())) . '</td>';
                        echo '<td colspan="3"  class="starting">';
                        echo apply_filters('lottery_history_starting_text', __('Lottery starting', 'wc_lottery'), $product_data);
                        echo '</td>';
                    }
                    ?>
                </tr>


            </table>
        <?php endif; ?>
        </ul>
        <?php
    }

    /**
     * Lottery order hold on
     *
     * Checks for lottery product in order when order is created on checkout before payment
     * @access public
     * @param int, array
     * @return void
     */
    function lottery_order_hold_on($order_id)
    {

        $order = new WC_Order($order_id);
        if ($order) {
            if ($order_items = $order->get_items()) {
                foreach ($order_items as $item_id => $item) {
                    if (function_exists('wc_get_order_item_meta')) {
                        $item_meta = wc_get_order_item_meta($item_id, '');
                    } else {
                        $item_meta = method_exists($order, 'wc_get_order_item_meta') ? $order->wc_get_order_item_meta($item_id) : $order->get_item_meta($item_id);
                    }
                    $product_id = $this->get_main_wpml_product_id($item_meta['_product_id'][0]);
                    $product_data = wc_get_product($product_id);
                    if ($product_data && $product_data->get_type() == 'lottery') {
                        update_post_meta($order_id, '_lottery', '1');
                        add_post_meta($product_id, '_order_hold_on', $order_id);
                    }
                }
            }
        }
    }

    /**
     * Get main product id for multilanguage purpose
     *
     * @access public
     * @return int
     *
     */
    function get_main_wpml_product_id($id)
    {

        return intval(apply_filters('wpml_object_id', $id, 'product', false, apply_filters('wpml_default_language', null)));

    }

    /**
     * Lottery order
     *
     * Checks for lottery product in order and assign order id to lottery product
     *
     * @access public
     * @param int, array
     * @return void
     */
    function lottery_order($order_id)
    {
        global $wpdb;
        $log = $wpdb->get_row($wpdb->prepare('SELECT 1 FROM ' . $wpdb->prefix . 'wc_lottery_log WHERE orderid=%d', $order_id));

        if (!is_null($log)) {
            return;
        }

        $order = new WC_Order($order_id);

        if ($order) {
            if ($order->get_meta('woocommerce_lottery_order_proccesed')) {
                return;
            };
            $order->update_meta_data('woocommerce_lottery_order_proccesed', time());
            $order->save();
            if ($order_items = $order->get_items()) {
                foreach ($order_items as $item_id => $item) {
                    if (function_exists('wc_get_order_item_meta')) {
                        $item_meta = wc_get_order_item_meta($item_id, '');
                    } else {
                        $item_meta = method_exists($order, 'wc_get_order_item_meta') ? $order->wc_get_order_item_meta($item_id) : $order->get_item_meta($item_id);
                    }
                    $product_id = $this->get_main_wpml_product_id($item_meta['_product_id'][0]);
                    $product_data = wc_get_product($product_id);
                    if ($product_data && $product_data->get_type() == 'lottery') {
                        $lottery_relisted = $product_data->get_lottery_relisted();
                        if ($lottery_relisted && $lottery_relisted > $order->get_date_created()->date('Y-m-d H:i:s')) {
                            continue;
                        }
                        update_post_meta($order_id, '_lottery', '1');
                        add_post_meta($product_id, '_order_id', $order_id);
                        delete_post_meta($product_id, '_order_hold_on', $order_id);
                        $log_ids = array();

                        if (apply_filters('lotery_add_participants_from_order', true, $item, $order_id, $product_id)) {
                            for ($i = 0; $i < $item_meta['_qty'][0]; $i++) {
                                add_post_meta($product_id, '_participant_id', $order->get_user_id());
                                $participants = get_post_meta($product_id, '_lottery_participants_count', true) ? get_post_meta($product_id, '_lottery_participants_count', true) : 0;
                                update_post_meta($product_id, '_lottery_participants_count', intval($participants) + 1);
                                $this->add_lottery_to_user_metafield($product_id, $order->get_user_id());
                                $log_ids[] = $this->log_participant($product_id, $order->get_user_id(), $order_id, $item);
                                do_action('wc_lottery_participate_added', $product_id, $order->get_user_id(), $order_id, $log_ids, $item, $item_id);
                            }
                            $max_tickets = intval($product_data->get_max_tickets());
                            $lottery_participants_count = intval($product_data->get_lottery_participants_count());
                            $stock_qty = $max_tickets - $lottery_participants_count;
                            wc_update_product_stock($product_data, $stock_qty, 'set');
                        } else {
                            $max_tickets = intval($product_data->get_max_tickets());
                            $lottery_participants_count = intval($product_data->get_lottery_participants_count());
                            $stock_qty = $max_tickets - $lottery_participants_count;
                            wc_update_product_stock($product_data, $stock_qty, 'set');
                            do_action('wc_lottery_participate_not_added', $product_id, $order->get_user_id(), $order_id, $log_ids, $item, $item_id);
                        }
                        do_action('wc_lottery_participate', $product_id, $order->get_user_id(), $order_id, $log_ids, $item, $item_id);
                    }
                }
            }
        }
    }

    /**
     * Add lottery to user custom field
     *
     * @access public
     * @return void
     *
     */
    function add_lottery_to_user_metafield($product_id, $user_id)
    {

        $my_lotteries = get_user_meta($user_id, 'my_lotteries', false);
        if (is_array($my_lotteries) && !in_array($product_id, $my_lotteries)) {
            add_user_meta($user_id, 'my_lotteries', $product_id, false);
        }
    }

    /**
     * Log participant
     *
     * @param int, int
     * @return void
     *
     */
    public function log_participant($product_id, $current_user_id, $order_id, $item)
    {

        global $wpdb;

        $wpdb->insert(
            $wpdb->prefix . 'wc_lottery_log', array(
            'userid' => $current_user_id,
            'lottery_id' => $product_id,
            'orderid' => $order_id,
            'ticket_numbers'=>$item->get_meta('Ticket number'),
            'date' => current_time('mysql'),
        ), array('%d', '%d', '%d', '%s', '%s')
        );

        return $wpdb->insert_id;
    }

    /**
     * Lottery order canceled
     *
     * Checks for lottery product in order and assign order id to lottery product
     *
     * @access public
     * @param int, array
     * @return void
     */
    function lottery_order_canceled($order_id)
    {
        global $wpdb;
        $log = $wpdb->get_row($wpdb->prepare('SELECT 1 FROM ' . $wpdb->prefix . 'wc_lottery_log WHERE orderid=%d', $order_id));

        if (is_null($log)) {
            return;
        }

        $order = new WC_Order($order_id);

        if ($order) {

            if ($order_items = $order->get_items()) {

                foreach ($order_items as $item_id => $item) {
                    if (function_exists('wc_get_order_item_meta')) {
                        $item_meta = wc_get_order_item_meta($item_id, '');
                    } else {
                        $item_meta = method_exists($order, 'wc_get_order_item_meta') ? $order->wc_get_order_item_meta($item_id) : $order->get_item_meta($item_id);
                    }
                    $product_id = $this->get_main_wpml_product_id($item_meta['_product_id'][0]);
                    $product_data = wc_get_product($product_id);
                    if ($product_data) {
                        $product_data_type = method_exists($product_data, 'get_type') ? $product_data->get_type() : $product_data->product_type;
                        if ($product_data_type == 'lottery') {

                            update_post_meta($order_id, '_lottery', '1');
                            add_post_meta($product_id, '_order_id', $order_id);
                            delete_post_meta($product_id, '_order_hold_on', $order_id);
                            $log_ids = array();
                            delete_post_meta($product_id, '_participant_id', $order->get_user_id());
                            if (apply_filters('lotery_remove_participants_from_order', true, $item, $order_id, $product_id)) {
                                for ($i = 0; $i < $item_meta['_qty'][0]; $i++) {
                                    $participants = get_post_meta($product_id, '_lottery_participants_count', true) ? get_post_meta($product_id, '_lottery_participants_count', true) : 0;
                                    if ($participants > 0) {
                                        update_post_meta($product_id, '_lottery_participants_count', intval($participants) - 1);
                                    }
                                    $this->remove_lottery_from_user_metafield($product_id, $order->get_user_id());
                                    $log_ids[] = $this->delete_log_participant($product_id, $order->get_user_id(), $order_id);
                                }
                                $count_from_lottery_logs = $this->get_count_from_lottery_logs($product_id, $order->get_user_id());
                                if (!empty($count_from_lottery_logs)) {
                                    $i = 0;
                                    while ($i < intval($count_from_lottery_logs)) {
                                        add_post_meta($product_id, '_participant_id', $order->get_user_id());
                                        $i++;
                                    }
                                }
                                do_action('wc_lottery_cancel_participation', $product_id, $order->get_user_id(), $order_id, $log_ids, $item, $item_id);
                            }
                            $max_tickets = intval($product_data->get_max_tickets());
                            $lottery_participants_count = intval($product_data->get_lottery_participants_count());
                            $stock_qty = $max_tickets - $lottery_participants_count;
                            wc_update_product_stock($product_data, $stock_qty, 'set');

                        }
                    }
                }
            }
        }
    }

    /**
     * Delete lottery from user custom field
     *
     * @access public
     * @return void
     *
     */
    function remove_lottery_from_user_metafield($product_id, $user_id)
    {
        $my_lotteries = get_user_meta($user_id, 'my_lotteries', false);
        if (in_array($product_id, $my_lotteries)) {
            delete_user_meta($user_id, 'my_lotteries', $product_id);
        }

    }

    /**
     * Log Lottery  participant
     *
     * @param int, int
     * @return void
     *
     */
    public function delete_log_participant($product_id, $current_user_id, $order_id)
    {

        global $wpdb;

        $log_id = $wpdb->get_var($wpdb->prepare('SELECT id FROM ' . $wpdb->prefix . 'wc_lottery_log  WHERE userid= %d AND lottery_id=%d AND orderid=%d', $current_user_id, $product_id, $order_id));
        if ($log_id) {
            $wpdb->query($wpdb->prepare('DELETE FROM ' . $wpdb->prefix . 'wc_lottery_log WHERE userid= %d AND lottery_id=%d AND orderid=%d', $current_user_id, $product_id, $order_id));
        }
        return $log_id;
    }

    /**
     * Delete logs when lottery is deleted
     *
     * @access public
     * @param string
     * @return void
     *
     */
    function get_count_from_lottery_logs($post_id, $user_id)
    {
        global $wpdb;
        $wheredatefrom = '';

        $relisteddate = get_post_meta($post_id, '_lottery_relisted', true);
        if (!empty($relisteddate)) {
            $datefrom = $relisteddate;
        }

        if ($datefrom) {
            $wheredatefrom = " AND CAST(date AS DATETIME) > '$datefrom' ";
        }


        if ($result = $wpdb->get_var($wpdb->prepare('SELECT COUNT(*)  FROM ' . $wpdb->prefix . 'wc_lottery_log WHERE lottery_id = %d AND userid = %d ' . $wheredatefrom, $post_id, $user_id))) {
            return $result;
        }

        return 0;
    }

    /**
     * Lottery order failed
     *
     * Checks for lottery product in failed order
     *
     * @access public
     * @param int, array
     * @return void
     */
    function lottery_order_failed($order_id)
    {
        global $wpdb;

        $order = new WC_Order($order_id);

        if ($order) {

            if ($order_items = $order->get_items()) {

                foreach ($order_items as $item_id => $item) {
                    if (function_exists('wc_get_order_item_meta')) {
                        $item_meta = wc_get_order_item_meta($item_id, '');
                    } else {
                        $item_meta = method_exists($order, 'wc_get_order_item_meta') ? $order->wc_get_order_item_meta($item_id) : $order->get_item_meta($item_id);
                    }
                    $product_id = $this->get_main_wpml_product_id($item_meta['_product_id'][0]);
                    $product_data = wc_get_product($product_id);
                    if ($product_data) {
                        $product_data_type = method_exists($product_data, 'get_type') ? $product_data->get_type() : $product_data->product_type;
                        if ($product_data_type == 'lottery') {
                            delete_post_meta($product_id, '_order_hold_on', $order_id);
                        }
                    }
                    do_action('wc_lottery_cancel_participation_failed', $product_id, $order->get_user_id(), $order_id, $log_ids = null, $item, $item_id);
                }
            }
        }
    }

    /**
     * Duplicate post
     *
     * Clear metadata when copy lottery
     *
     * @access public
     * @param array
     * @return string
     *
     */
    function woocommerce_duplicate_product($postid)
    {

        $product = wc_get_product($postid);

        if (!$product) {
            return false;
        }
        if ($product->get_type() != 'lottery') {
            return false;
        }

        delete_post_meta($postid, '_lottery_participants_count');
        delete_post_meta($postid, '_lottery_closed');
        delete_post_meta($postid, '_lottery_fail_reason');
        delete_post_meta($postid, '_lottery_dates_to');
        delete_post_meta($postid, '_lottery_dates_from');
        delete_post_meta($postid, '_order_id');
        delete_post_meta($postid, '_lottery_winners');
        delete_post_meta($postid, '_participant_id');
        delete_post_meta($postid, '_lottery_started');
        delete_post_meta($postid, '_lottery_has_started');
        delete_post_meta($postid, '_lottery_relisted');

        return true;

    }

    /**
     * Ajax delete participate entry
     *
     * Function for deleting participate entry in wp admin
     *
     * @access public
     * @param array
     * @return string
     *
     */
    function wp_ajax_delete_participate_entry()
    {

        global $wpdb;

        if (!current_user_can('edit_product', $_POST['postid'])) {
            die();
        }
        $log_id = $_POST['logid'] ? intval($_POST['logid']) : false;
        $post_id = $_POST['postid'] ? intval($_POST['postid']) : false;

        if ($post_id && $log_id) {
            $product = wc_get_product($post_id);
            $log = $wpdb->get_row($wpdb->prepare('SELECT 1 FROM ' . $wpdb->prefix . 'wc_lottery_log WHERE id=%d', $log_id));
            $participants = get_post_meta($post_id, '_participant_id', false);
            if (!is_null($log)) {
                $wpdb->query($wpdb->prepare('DELETE FROM ' . $wpdb->prefix . 'wc_lottery_log WHERE id= %d', $log_id));
                $pos = array_search($log->userid, $participants);
                unset($participants[$pos]);
                delete_post_meta($post_id, '_participant_id');
                delete_post_meta($post_id, '_order_id', $log->orderid);
                if (!$product->is_user_participating()) {
                    $this->remove_lottery_from_user_metafield($post_id, $log->userid);
                }
                $count = get_post_meta($post_id, '_lottery_participants_count', true) ? get_post_meta($post_id, '_lottery_participants_count', true) : 0;

                if ($count > 0) {
                    update_post_meta($post_id, '_lottery_participants_count', intval($count) - 1);
                }

                foreach ($participants as $key => $value) {
                    add_post_meta($post_id, '_participant_id', $value);
                }
                do_action('wc_lottery_delete_participate_entry', $post_id, $log_id);
                wp_send_json('deleted');
                exit;
            }
            wp_send_json('failed');
            exit;
        }
        wp_send_json('failed');
        exit;
    }

    /**
     * Payout to winner
     *
     *
     * @access public
     * @param array
     * @return string
     *
     */
    function wp_ajax_payout_lottery_winner()
    {
        global $wpdb;
        // get gamipress point type slug
        $gami_pts = gamipress_get_points_types();
        $pt_slug = '';
        $pt_name = '';
        foreach($gami_pts as $slug=>$point_type){
            if($point_type['ID'] == $_POST['pt']){
                $pt_slug = $slug;
                $pt_name = $point_type['plural_name'];
                break;
            }
        }
        gamipress_award_points_to_user( $_POST['user'], $_POST['pp'], $pt_slug, ['reason'=>"Lottery win(".$_POST['pp'].' '.$pt_name.")"] );
        $table = $wpdb->prefix . 'wc_lottery_log';
        $res = $wpdb->update($table, ['payout' => 1], ['id' => $_POST['id']]);
        wp_send_json($res);
        exit;
    }

    /**
     * Return Payout to winner
     *
     *
     * @access public
     * @param array
     * @return string
     *
     */
    function wp_ajax_return_payout_lottery_winner()
    {
        global $wpdb;
        // get gamipress point type slug
        $gami_pts = gamipress_get_points_types();
        $pt_slug = '';
        $pt_name = '';

        foreach($gami_pts as $slug=>$point_type){
            if($point_type['ID'] == $_POST['pt']){
                $pt_slug = $slug;
                $pt_name = $point_type['plural_name'];
                break;
            }
        }
        gamipress_deduct_points_to_user( $_POST['user'], $_POST['pp'], $pt_slug, ['reason'=>"Lottery win prize refund(".$_POST['pp'].' '.$pt_name.")"] );
        $table = $wpdb->prefix . 'wc_lottery_log';
        $res = $wpdb->update($table, ['payout' => 0], ['id' => $_POST['id']]);
        wp_send_json($res);
        exit;
    }

    /**
     * Sync meta with wpml
     *
     * Sync meta trough translated post
     *
     * @access public
     * @param bool $url (default: false)
     * @return void
     *
     */
    function sync_metadata_wpml($data)
    {

        global $sitepress;

        if (is_object($sitepress)) {

            $deflanguage = $sitepress->get_default_language();

            if (is_array($data)) {
                $product_id = $data['product_id'];
            } else {
                $product_id = $data;
            }

            $meta_values = get_post_meta($product_id);
            $orginalid = $sitepress->get_original_element_id($product_id, 'post_product');
            $trid = $sitepress->get_element_trid($product_id, 'post_product');
            $all_posts = $sitepress->get_element_translations($trid, 'post_product');

            unset($all_posts[$deflanguage]);

            if (!empty($all_posts)) {

                foreach ($all_posts as $key => $translatedpost) {

                    if (isset($meta_values['_max_tickets'][0])) {
                        update_post_meta($translatedpost->element_id, '_max_tickets', $meta_values['_max_tickets'][0]);
                    }
                    if (isset($meta_values['_min_tickets'][0])) {
                        update_post_meta($translatedpost->element_id, '_min_tickets', $meta_values['_min_tickets'][0]);
                    }
                    if (isset($meta_values['_lottery_num_winners'][0])) {
                        update_post_meta($translatedpost->element_id, '_lottery_num_winners', $meta_values['_lottery_num_winners'][0]);
                    }
                    if (isset($meta_values['_lottery_dates_from'][0])) {
                        update_post_meta($translatedpost->element_id, '_lottery_dates_from', $meta_values['_lottery_dates_from'][0]);
                    }
                    if (isset($meta_values['_lottery_dates_to'][0])) {
                        update_post_meta($translatedpost->element_id, '_lottery_dates_to', $meta_values['_lottery_dates_to'][0]);
                    }
                    if (isset($meta_values['_lottery_closed'][0])) {
                        update_post_meta($translatedpost->element_id, '_lottery_closed', $meta_values['_lottery_closed'][0]);
                    }
                    if (isset($meta_values['_lottery_fail_reason'][0])) {
                        update_post_meta($translatedpost->element_id, '_lottery_fail_reason', $meta_values['_lottery_fail_reason'][0]);
                    }
                    if (isset($meta_values['_order_id'][0])) {
                        update_post_meta($translatedpost->element_id, '_order_id', $meta_values['_order_id'][0]);
                    }

                    if (isset($meta_values['_lottery_participants_count'][0])) {
                        update_post_meta($translatedpost->element_id, '_lottery_participants_count', $meta_values['_lottery_participants_count'][0]);
                    }
                    if (isset($meta_values['_lottery_winners'][0])) {
                        update_post_meta($translatedpost->element_id, '_lottery_winners', $meta_values['_lottery_winners'][0]);
                    }
                    if (isset($meta_values['_participant_id'][0])) {
                        delete_post_meta($translatedpost->element_id, '_participant_id');
                        foreach ($meta_values['_lottery_winners'] as $key => $value) {
                            add_post_meta($translatedpost->element_id, '_participant_id', $value);
                        }
                    }

                    if (isset($meta_values['_regular_price'][0])) {
                        update_post_meta($translatedpost->element_id, '_regular_price', $meta_values['_regular_price'][0]);
                    }
                    if (isset($meta_values['_lottery_wpml_language'][0])) {
                        update_post_meta($translatedpost->element_id, '_lottery_wpml_language', $meta_values['_lottery_wpml_language'][0]);
                    }
                }
            }
        }
    }

    /**
     *
     * Add last language in use to custom meta of lottery
     *
     * @access public
     * @param int
     * @return void
     *
     */
    function add_language_wpml_meta($cart_item_key, $product_id, $quantity, $variation_id, $variation, $cart_item_data)
    {

        $language = isset($_SESSION['wpml_globalcart_language']) ? $_SESSION['wpml_globalcart_language'] : ICL_LANGUAGE_CODE;
        update_post_meta($product_id, '_lottery_wpml_language', $language);
    }

    function change_email_language($product_id)
    {

        global $sitepress;
        if (is_object($sitepress)) {

            $lang = get_post_meta($product_id, '_lottery_wpml_language', true);

            if ($lang) {

                $sitepress->switch_lang($lang, true);
                unload_textdomain('woocommerce');
                unload_textdomain('default');
                wc()->load_plugin_textdomain();
                load_default_textdomain();
                global $wp_locale;
                $wp_locale = new WP_Locale();
            }
        }
    }

    /**
     * Ouput custom columns for products.
     *
     * @param string $column
     *
     */
    public function render_product_columns($column)
    {

        global $post, $the_product;

        if (empty($the_product) || $the_product->get_id() != $post->ID) {
            $the_product = wc_get_product($post);
        }

        if ($column == 'product_type') {
            $the_product_type = method_exists($the_product, 'get_type') ? $the_product->get_type() : $the_product->product_type;
            if ('lottery' == $the_product_type) {
                $class = '';
                $closed = $the_product->get_lottery_closed();
                if ($closed == '2') {
                    $class .= ' finished ';
                }

                if ($closed == '1') {
                    $class .= ' fail ';
                }

                echo '<span class="lottery-status ' . $class . '"></span>';
            }
        }

    }

    /**
     * Search for [vendor] tag in recipients and replace it with author email
     *
     */
    public function add_vendor_to_email_recipients($recipient, $object)
    {
        $key = false;
        $author_info = false;
        $arrayrec = explode(',', $recipient);
        if (!$object) {
            return $recipient;
        }

        $post_id = method_exists($object, 'get_id') ? $object->get_id() : $object->id;
        $post_author = get_post_field('post_author', $post_id);
        if (!empty($post_author)) {
            $author_info = get_userdata($post_author);
            $key = array_search($author_info->user_email, $arrayrec);
        }

        if (!$key && $author_info) {
            $recipient = str_replace('[vendor]', $author_info->user_email, $recipient);

        } else {
            $recipient = str_replace('[vendor]', '', $recipient);
        }

        return $recipient;
    }

    /**
     * Run plugin update
     * WooCommerce is known to be active and initialized
     *
     */
    public function update()
    {
        global $wpdb;
        if (version_compare(get_site_option('wc_lottery_version'), '1.1.15', '<')) {
            $users = $wpdb->get_results('SELECT DISTINCT userid FROM ' . $wpdb->prefix . 'wc_lottery_log ', ARRAY_N);

            if (is_array($users)) {
                foreach ($users as $user_id) {
                    $user_lotteries = $wpdb->get_results('SELECT DISTINCT lottery_id FROM ' . $wpdb->prefix . "wc_lottery_log WHERE userid = $user_id[0] ", ARRAY_N);

                    if (isset($user_lotteries) && !empty($user_lotteries)) {
                        foreach ($user_lotteries as $lottery) {
                            add_user_meta($user_id[0], 'my_lotteries', $lottery[0], false);
                        }
                    }
                }
            }
            update_option('wc_lottery_version', $this->version);
        }
    }

    public function add_ticket_numbers_to_order_items( $item, $cart_item_key, $values, $order ) {
        global $wpdb;
        $product                    = $values['data'];
        if ( empty( $values['lottery_tickets_number'] ) ) {
                return;
        }
        
        $ticket_number = $values['lottery_tickets_number'];
        $ticket_number_arr = explode(",", $ticket_number);
        $ticket_number_sort = [];
        $ticket_bonus_number_comp = [];
        $alphabet_line = substr_count($values['lottery_tickets_number'], "A");
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
        $item->add_meta_data( __( 'Ticket number', 'wc-lottery' ), $ticket_rearranged );
        
        // add ticket numbers to db log
        $userid = get_current_user_id();
        if($userid != 0)
        {
            $content = $ticket_number;
            $table = $wpdb->prefix . 'wc_lottery_pick_number';
            $url     = wp_get_referer();
            $post_id = $product->get_id();
            $res = $wpdb->insert($table, array('userid' => $userid, 'productid' => $post_id, 'content' => $content));
        }
    }

    function adjust_ticket_number($ticket_number, $alphabet_line)
    {
        if($alphabet_line == 1)
            return $ticket_number;
        else
            return str_replace("-", "", $ticket_number);
    }

    
}
