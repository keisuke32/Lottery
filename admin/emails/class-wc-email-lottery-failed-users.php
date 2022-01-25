<?php

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/**
 * Lottery fail users emails 
 *
 * Lottery fail users emails are sent to al participants when  lotery faild.
 * @class 		WC_Email_SA_Outbid
 * @extends 	WC_Email
 */

class WC_Email_Lottery_Fail_Users extends WC_Email {
		
    /** @var string */
    var $title;

    /** @var string */
    var $lottery_id;


    /**
     * Constructor
     *
     * @access public
     * @return void
     */
    function __construct() {

        global $wc_lottery;

        $this->id             = 'lottery_fail_users1';
        $this->title          = __( 'Lottery Fail For Customers', 'wc_lottery' );
        $this->description    = __( 'Lottery Fail emails are sent when lottery fails to users that participated.', 'wc_lottery' );
        $this->customer_email = true;

        $this->template_html  = 'emails/lottery_fail_users.php';
        $this->template_plain = 'emails/plain/lottery_fail_users.php';
        $this->template_base  = $wc_lottery->get_path() .  'templates/';
        
        $this->subject        = __( 'Lottery Failed on {blogname}', 'wc_lottery');
        $this->heading        = __( 'Better luck next time!', 'wc_lottery');

        add_action( 'wc_lottery_fail', array( $this, 'trigger' ) );

        // Call parent constructor
        parent::__construct();
    }

    /**
     * trigger function.
     *
     * @access public
     * @return void
     */
    function trigger( $args ) {		

        if ( !$this->is_enabled() ) return;


        if ( $args ) {

            $product_id = $args[ 'lottery_id' ];
            if( get_post_status( $product_id ) != 'publish' ){
                return;
            }

            $product_data  = wc_get_product(  $product_id );
            $participants  =  get_post_meta( $product_id, '_participant_id');
            $uniquep_participants= array_unique($participants);


            if ( $product_data && !empty($uniquep_participants)) {

                foreach ($uniquep_participants as $user) {
                    $this->object     = new WP_User( $user );
                    $this->recipient  = $this->object->user_email;
                    $this->lottery_id = $product_id;
                    $this->send( $this->get_recipient(), $this->get_subject(), $this->get_content(), $this->get_headers(), $this->get_attachments() );						
                }
            }

        }
    }
    /**
     * get_content_html function.
     *
     * @access public
     * @return string
     */
    function get_content_html() {
        ob_start();
        wc_get_template( 	
            $this->template_html, array(
                    'email_heading' => $this->get_heading(),
                    'blogname'      => $this->get_blogname(),
                    'product_id'    => $this->lottery_id				
            ) );
        return ob_get_clean();
    }
    /**
     * get_content_plain function.
     *
     * @access public
     * @return string
     */
    function get_content_plain() {
        ob_start();
        wc_get_template( 
            $this->template_plain, array(
                'email_heading' => $this->get_heading(),
                'blogname'      => $this->get_blogname(),
                'product_id'    => $this->lottery_id				
            ) );
        return ob_get_clean();
    }
}