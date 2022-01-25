<?php

/**
 * Define the internationalization functionality.
 *
 * Loads and defines the internationalization files for this plugin
 * so that it is ready for translation.
 *
 * @since      1.0.0
 * @package    wc_lottery
 * @subpackage wc_lottery/includes
 * @author     wpgenie <info@wpgenie.org>
 */
class wc_lottery_i18n {
    /**
     * Load the plugin text domain for translation.
     *
     * @since    1.0.0
     */
    public function load_plugin_textdomain() {

        $domain = 'wc_lottery';
        $langdir = dirname( dirname( plugin_basename( __FILE__ ) ) ) . '/languages';		
        load_plugin_textdomain( $domain, false, $langdir.'/');

    }

}