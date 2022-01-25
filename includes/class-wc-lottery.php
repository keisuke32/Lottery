<?php

/**
 * The file that defines the core plugin class
 *
 * A class definition that includes attributes and functions used across both the
 * public-facing side of the site and the admin area.
 *
 * @link       http://wpgenie.org
 * @since      1.0.0
 *
 * @package    wc_lottery
 * @subpackage wc_lottery/includes
 */

/**
 * The core plugin class.
 *
 * This is used to define internationalization, admin-specific hooks, and
 * public-facing site hooks.
 *
 * Also maintains the unique identifier of this plugin as well as the current
 * version of the plugin.
 *
 * @since      1.0.0
 * @package    wc_lottery
 * @subpackage wc_lottery/includes
 * @author     wpgenie <info@wpgenie.org>
 */
class wc_lottery
{

    /**
     * The current path of the plugin.
     *
     * @since    1.0.0
     * @access   protected
     * @var      string $version The current version of the plugin.
     */
    public $path;
    public $plugin_public;
    public $plugin_admin;
    /**
     * The loader that's responsible for maintaining and registering all hooks that power
     * the plugin.
     *
     * @since    1.0.0
     * @access   protected
     * @var      wc_lottery_Loader $loader Maintains and registers all hooks for the plugin.
     */
    protected $loader;
    /**
     * The unique identifier of this plugin.
     *
     * @since    1.0.0
     * @access   protected
     * @var      string $wc_lottery The string used to uniquely identify this plugin.
     */
    protected $wc_lottery;
    /**
     * The current version of the plugin.
     *
     * @since    1.0.0
     * @access   protected
     * @var      string $version The current version of the plugin.
     */
    protected $version;

    /**
     * Define the core functionality of the plugin.
     *
     * Set the plugin name and the plugin version that can be used throughout the plugin.
     * Load the dependencies, define the locale, and set the hooks for the admin area and
     * the public-facing side of the site.
     *
     * @since    1.0.0
     */
    public function __construct()
    {

        $this->wc_lottery = 'wc-lottery';
        $this->version = '1.1.22 ';
        $this->path = plugin_dir_path(dirname(__FILE__));
        $this->load_dependencies();
        $this->set_locale();
        $this->define_admin_hooks();
        $this->define_public_hooks();

    }

    /**
     * Load the required dependencies for this plugin.
     *
     * Include the following files that make up the plugin:
     *
     * - wc_lottery_Loader. Orchestrates the hooks of the plugin.
     * - wc_lottery_i18n. Defines internationalization functionality.
     * - wc_lottery_Admin. Defines all hooks for the admin area.
     * - wc_lottery_Public. Defines all hooks for the public side of the site.
     *
     * Create an instance of the loader which will be used to register the hooks
     * with WordPress.
     *
     * @since    1.0.0
     * @access   private
     */
    private function load_dependencies()
    {

        /**
         * The class responsible for orchestrating the actions and filters of the
         * core plugin.
         */
        require_once plugin_dir_path(dirname(__FILE__)) . 'includes/class-wc-lottery-loader.php';
        /**
         * The class responsible for defining all actions that occur in the admin area.
         */
        require_once plugin_dir_path(dirname(__FILE__)) . 'admin/class-wc-lottery-admin.php';
        /**
         * The class responsible for defining all actions that occur in the public-facing
         * side of the site.
         */
        require_once plugin_dir_path(dirname(__FILE__)) . 'public/class-wc-lottery-public.php';

        require_once plugin_dir_path(dirname(__FILE__)) . 'public/class-wc-lottery-shortcodes.php';
        require_once plugin_dir_path(dirname(__FILE__)) . 'public/class-wc-shortcode-lotteries.php';

        require_once plugin_dir_path(dirname(__FILE__)) . 'includes/class-wc-product-lottery.php';

        require_once plugin_dir_path(dirname(__FILE__)) . 'includes/wc-lottery-template-functions.php';

        require_once plugin_dir_path(dirname(__FILE__)) . 'admin/class-wpgenie-dashboard.php';

        $this->loader = new wc_lottery_Loader();

        $this->shortcodes = new WC_Shortcode_Lottery();


    }

    /**
     * Define the locale for this plugin for internationalization.
     *
     * Uses the wc_lottery_i18n class in order to set the domain and to register the hook
     * with WordPress.
     *
     * @since    1.0.0
     * @access   private
     */
    private function set_locale()
    {

        $plugin_i18n = new wc_lottery_i18n();

        $this->loader->add_action('wp_loaded', $plugin_i18n, 'load_plugin_textdomain');
    }

    /**
     * Register all of the hooks related to the admin area functionality of the plugin.
     *
     * @since    1.0.0
     * @access   private
     */
    private function define_admin_hooks()
    {

        $this->plugin_admin = new wc_lottery_Admin($this->get_wc_lottery(), $this->get_version(), $this->get_path());

        $this->loader->add_action('admin_enqueue_scripts', $this->plugin_admin, 'enqueue_styles');
        $this->loader->add_action('admin_enqueue_scripts', $this->plugin_admin, 'enqueue_scripts');
        $this->loader->add_action('admin_enqueue_scripts', $this->plugin_admin, 'enqueue_scripts');

        $this->loader->add_action('manage_shop_order_posts_custom_column', $this->plugin_admin, 'woocommerce_simple_lottery_order_column_lottery_content', 10, 2);
        $this->loader->add_action('add_meta_boxes', $this->plugin_admin, 'woocommerce_simple_lottery_meta');
        $this->loader->add_action('admin_notices', $this->plugin_admin, 'woocommerce_simple_lottery_admin_notice');
        $this->loader->add_action('admin_init', $this->plugin_admin, 'woocommerce_simple_lottery_ignore_notices');
        $this->loader->add_action('admin_init', $this->plugin_admin, 'update');
        $this->loader->add_action('restrict_manage_posts', $this->plugin_admin, 'admin_posts_filter_restrict_manage_posts');
        $this->loader->add_action('delete_post', $this->plugin_admin, 'del_lottery_logs');

        $this->loader->add_filter('woocommerce_product_data_tabs', $this->plugin_admin, 'product_write_panel_tab', 1);
        if (version_compare(WC_VERSION, '2.7', '<')) {
            $this->loader->add_action('woocommerce_product_write_panels', $this->plugin_admin, 'product_write_panel');
            /**
             * @Author igor
             * --------------------START-----------------------
             */
            $this->loader->add_action('woocommerce_product_write_panels', $this->plugin_admin, 'product_write_panel');
            /**
             * ---------------------END-------------------------
             */
            $this->loader->add_action('manage_product_posts_custom_column', $this->plugin_admin, 'woocommerce_simple_lottery_order_column_lottery_content', 10, 2);
            $this->loader->add_filter('manage_product_posts_columns', $this->plugin_admin, 'woocommerce_simple_lottery_order_column_lottery');
        } else {
            $this->loader->add_action('woocommerce_product_data_panels', $this->plugin_admin, 'product_write_panel');
            $this->loader->add_action('manage_product_posts_custom_column', $this->plugin_admin, 'render_product_columns');
        }

        $this->loader->add_action('woocommerce_process_product_meta', $this->plugin_admin, 'product_save_data', 80, 2);
        $this->loader->add_action('woocommerce_email', $this->plugin_admin, 'add_to_mail_class');
        $this->loader->add_action('woocommerce_checkout_update_order_meta', $this->plugin_admin, 'lottery_order_hold_on', 10);
        $this->loader->add_action('woocommerce_order_status_processing', $this->plugin_admin, 'lottery_order', 10, 1);
        $this->loader->add_action('woocommerce_order_status_completed', $this->plugin_admin, 'lottery_order', 10, 1);
        $this->loader->add_action('woocommerce_order_status_cancelled', $this->plugin_admin, 'lottery_order_canceled', 10, 1);
        $this->loader->add_action('woocommerce_order_status_refunded', $this->plugin_admin, 'lottery_order_canceled', 10, 1);
        $this->loader->add_action('woocommerce_order_status_cancelled', $this->plugin_admin, 'lottery_order_failed', 10, 1);
        $this->loader->add_action('woocommerce_order_status_failed', $this->plugin_admin, 'lottery_order_failed', 10, 1);
        $this->loader->add_action('wp_ajax_delete_lottery_participate_entry', $this->plugin_admin, 'wp_ajax_delete_participate_entry');
        $this->loader->add_action('wp_ajax_payout_lottery_winner', $this->plugin_admin, 'wp_ajax_payout_lottery_winner');
        $this->loader->add_action('wp_ajax_return_payout_lottery_winner', $this->plugin_admin, 'wp_ajax_return_payout_lottery_winner');
        $this->loader->add_action('wp_ajax_lottery_refund', $this->plugin_admin, 'lottery_refund');
        $this->loader->add_action('woocommerce_duplicate_product', $this->plugin_admin, 'woocommerce_duplicate_product');
        $this->loader->add_action('widgets_init', $this->plugin_admin, 'register_widgets');
        /**
         * @Author Igor
         * add hook for ajax add prize
         * ------------start-------------
         *
         */
        $this->loader->add_action('wp_ajax_lottery_add_prize', $this->plugin_admin, 'lottery_prize_save_data');
        $this->loader->add_action('wp_ajax_lottery_remove_prize', $this->plugin_admin, 'lottery_remove_prize');
        $this->loader->add_action('wp_ajax_lottery_sort_prize', $this->plugin_admin, 'lottery_sort_prize');
        /**
         * ------------end--------------
         */

        /**
         * @Author Igor
         * add hook for ajax add prize
         * ------------start-------------
         *
         */
        $this->loader->add_action('wp_ajax_participate_lottery', $this->plugin_admin, 'participate_lottery');
        $this->loader->add_action('wp_ajax_nopriv_participate_lottery', $this->plugin_admin, 'participate_lottery');
        /**
         * ------------end--------------
         */

        $this->loader->add_filter('woocommerce_get_settings_pages', $this->plugin_admin, 'lottery_settings_class', 20);
        $this->loader->add_filter('parse_query', $this->plugin_admin, 'admin_posts_filter', 20);
        $this->loader->add_filter('plugin_row_meta', $this->plugin_admin, 'add_support_link', 10, 2);
        $this->loader->add_filter('product_type_selector', $this->plugin_admin, 'add_product_type', 10, 2);

        /* emails hooks */
        $email_actions = array('wc_lottery_won', 'wc_lottery_fail', 'wc_lottery_close', 'woocommerce_lottery_do_extend');
        foreach ($email_actions as $action) {
            $this->loader->add_action($action, 'WC_Emails', 'send_transactional_email');
        }


        /* wpml support */
        global $sitepress;
        if (function_exists('icl_object_id') && method_exists($sitepress, 'get_default_language')) {
            $this->loader->add_action('wc_lottery_participate', $this->plugin_admin, 'sync_metadata_wpml', 1);
            $this->loader->add_action('wc_lottery_close', $this->plugin_admin, 'sync_metadata_wpml', 1);
            $this->loader->add_action('woocommerce_process_product_meta', $this->plugin_admin, 'sync_metadata_wpml', 85);
            $this->loader->add_action('woocommerce_add_to_cart', $this->plugin_admin, 'add_language_wpml_meta', 99, 6);
            $this->loader->add_action('woocommerce_add_to_cart', $this->plugin_admin, 'change_email_language', 1);
            $this->loader->add_action('woocommerce_simple_lottery_won', $this->plugin_admin, 'change_email_language', 1);
        }

        // wc-vendors email integration
        $this->loader->add_filter('woocommerce_email_recipient_lottery_fail', $this->plugin_admin, 'add_vendor_to_email_recipients', 10, 2);
        $this->loader->add_filter('woocommerce_email_recipient_lottery_finished', $this->plugin_admin, 'add_vendor_to_email_recipients', 10, 2);


        add_filter('woocommerce_email_recipient_lottery_fail', array($this, 'add_vendor_to_email_recipients'), 10, 2);
        add_filter('woocommerce_email_recipient_lottery_finished', array($this, 'add_vendor_to_email_recipients'), 10, 2);

        $this->loader->add_action( 'woocommerce_checkout_create_order_line_item', $this->plugin_admin, 'add_ticket_numbers_to_order_items', 10, 4 );
    }

    /**
     * The name of the plugin used to uniquely identify it within the context of
     * WordPress and to define internationalization functionality.
     *
     * @return    string    The name of the plugin.
     * @since     1.0.0
     */
    public function get_wc_lottery()
    {
        return $this->wc_lottery;
    }

    /**
     * Retrieve the version number of the plugin.
     *
     * @return    string    The version number of the plugin.
     * @since     1.0.0
     */
    public function get_version()
    {
        return $this->version;
    }

    /**
     * Retrieve the path of the plugin.
     *
     * @return    string    The path  of the plugin.
     * @since     1.0.0
     */
    public function get_path()
    {
        return $this->path;
    }

    /**
     * Register all of the hooks related to the public-facing functionality
     * of the plugin.
     *
     * @since    1.0.0
     * @access   private
     */
    private function define_public_hooks()
    {

        $this->plugin_public = new wc_lottery_Public($this->get_wc_lottery(), $this->get_version());

        $this->loader->add_action('wp_enqueue_scripts', $this->plugin_public, 'enqueue_styles');
        $this->loader->add_action('wp_enqueue_scripts', $this->plugin_public, 'enqueue_scripts');
        $this->loader->add_action('wp_ajax_finish_lottery', $this->plugin_public, 'ajax_finish_lottery');
        $this->loader->add_action('woocommerce_product_tabs', $this->plugin_public, 'lottery_tab');
        $this->loader->add_action('woocommerce_product_tab_panels', $this->plugin_public, 'lottery_tab_panel');
        //$this->loader->add_action( 'woocommerce_product_is_visible', $this->plugin_public, 'filter_lottery', 10, 2 );
        $this->loader->add_action('woocommerce_before_shop_loop_item_title', $this->plugin_public, 'add_lottery_bage', 60);
        $this->loader->add_action('woocommerce_product_query', $this->plugin_public, 'remove_lottery_from_woocommerce_product_query', 2);
        $this->loader->add_action('woocommerce_check_cart_items', $this->plugin_public, 'check_cart_items');
        $this->loader->add_action('woocommerce_product_query', $this->plugin_public, 'remove_lottery_from_woocommerce_product_query', 2);
        $this->loader->add_action('widgets_init', $this->plugin_public, 'register_widgets');
        $this->loader->add_action('init', $this->plugin_public, 'simple_lottery_cron', PHP_INT_MAX);
        $this->loader->add_action('woocommerce_before_single_product', $this->plugin_public, 'participating_message', 1);
        $this->loader->add_action('template_redirect', $this->plugin_public, 'track_lotteries_view', 1);
        $this->loader->add_action('woocommerce_login_form_end', $this->plugin_public, 'add_redirect_previous_page');

        $this->loader->add_filter('woocommerce_locate_template', $this->plugin_public, 'woocommerce_locate_template', 10, 3);
        $this->loader->add_filter('woocommerce_add_to_cart_validation', $this->plugin_public, 'add_to_cart_validation', 10, 4);
        $this->loader->add_filter('template_include', $this->plugin_public, 'lottery_page_template', 99);
        $this->loader->add_filter('body_class', $this->plugin_public, 'output_body_class');
        $this->loader->add_filter('pre_get_posts', $this->plugin_public, 'lottery_archive_pre_get_posts');
        $this->loader->add_action('pre_get_posts', $this->plugin_public, 'query_is_lottery_archive', 1);
        $this->loader->add_filter('woocommerce_product_query', $this->plugin_public, 'pre_get_posts', 99, 2);
        $this->loader->add_filter('woocommerce_is_purchasable', $this->plugin_public, 'is_purchasable', 99, 2);
        $this->loader->add_filter('post_class', $this->plugin_public, 'add_post_class');
        $this->loader->add_filter('icl_ls_languages', $this->plugin_public, 'translate_ls_lottery_url', 99);
        $this->loader->add_filter('woocommerce_get_lottery_page_id', $this->plugin_public, 'lottery_page_wpml', 99);
        $this->loader->add_filter('woocommerce_get_breadcrumb', $this->plugin_public, 'lottery_get_breadcrumb', 1, 2);
        $this->loader->add_filter('pre_get_document_title', $this->plugin_public, 'lottery_filter_wp_title', 10);
        $this->loader->add_filter('woocommerce_page_title', $this->plugin_public, 'lottery_page_title', 10);
        $this->loader->add_filter('woocommerce_product_related_posts_query', $this->plugin_public, 'remove_finished_lotteries_from_related_products', 10);

        $this->loader->add_filter('lottery_get_all_winners', $this->plugin_admin, 'lottery_get_all_winners');
        $this->loader->add_filter('adjust_ticket_number', $this->plugin_admin, 'adjust_ticket_number', 10, 2);

        $this->loader->add_filter('wp_nav_menu_objects', $this->plugin_public, 'lottery_nav_menu_item_classes');

        add_shortcode('woocommerce_simple_lottery_my_lottery', array($this, 'shortcode_my_lottery'));
        add_action('woocommerce_single_product_summary', 'woocommerce_lottery_participate_template', 25);
        add_action('woocommerce_single_product_summary', 'woocommerce_lottery_winners_template', 25);
        add_action('woocommerce_lottery_add_to_cart', 'woocommerce_lottery_add_to_cart_template');
        add_action('woocommerce_lottery_before_participate', 'woocommerce_lottery_countdown_template');
        add_action('woocommerce_lottery_ajax_change_participate', 'woocommerce_lottery_info_template', 10);
        add_action('woocommerce_lottery_ajax_change_participate', 'woocommerce_lottery_progressbar_template', 15);
        add_action('woocommerce_lottery_participate_future', 'woocommerce_lottery_countdown_template');
        add_action('woocommerce_lottery_participate_future', 'woocommerce_lottery_info_future_template');

        add_action('init', array('WC_Shortcode_Lottery', 'init'));


        $this->loader->add_filter( 'woocommerce_add_cart_item_data', $this->plugin_public, 'add_ticket_number_to_cart_item', 10, 3 );       
        $this->loader->add_filter( 'woocommerce_get_item_data', $this->plugin_public, 'display_ticket_numbers_cart', 10, 2 );
        $this->loader->add_filter( 'woocommerce_get_cart_item_from_session', $this->plugin_public , 'check_cart_ticket_numbers', 10, 4 );
        $this->loader->add_filter( 'woocommerce_cart_loaded_from_session', $this->plugin_public , 'check_cart_for_duplicate_ticket_numbers', 10 );
        $this->loader->add_filter( 'woocommerce_quantity_input_max', $this->plugin_public , 'woocommerce_quantity_input_max_callback', 10, 2 );
    }

    /**
     * Run the loader to execute all of the hooks with WordPress.
     *
     * @since    1.0.0
     */
    public function run()
    {
        $this->loader->run();
    }

    /**
     * The reference to the class that orchestrates the hooks with the plugin.
     *
     * @return    wc_lottery_Loader    Orchestrates the hooks of the plugin.
     * @since     1.0.0
     */
    public function get_loader()
    {
        return $this->loader;
    }

    /**
     * Search for [vendor] tag in recipients and replace it with author email
     *
     */
    public function add_vendor_to_email_recipients($recipient, $object)
    {

        if (!is_object($object)) {
            return $recipient;
        }

        $key = false;
        $author_info = false;
        $arrayrec = explode(',', $recipient);

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


}
