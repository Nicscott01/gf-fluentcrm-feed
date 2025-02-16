<?php

namespace GFFluentFeed\Providers\GravityForms;
use FluentCrm\App\Models\Subscriber;
use FluentCrm\App\Models\SubscriberMeta;
use FluentCrm\App\Services;
use GFAPI;



abstract class GenericProvider {

    /**
     *  Instance
     * 
     *  @var object null
     */
    public static $instance = null;


    public static $provider = '';
    public static $providerTitle = '';
    public static $entryType = '';

    /**
     *  Construct
     * 
     * 
     */
    public function __construct() {



    }





    /**
     *  Register this provider
     * 
     * 
     */
    public function register_provider( $providers ) {

        $providers[self::$provider] = [
            'title' => self::$providerTitle,
            'name' => self::$provider
        ];


        return $providers;

    }






    /**
     *  Maybe setup the Section
     *  
     *  If it's already setup, we don't need to
     *  
     *  @return array
     */
    public function setup_form_submissions_section( $sections ) {

 
        if ( !isset( $sections['subscriber_form_submissions'] ) ) {

            $subscriber_form_submissions = [
                'name'    => 'subscriber_form_submissions',
                'title'   => __('Form Submissions', 'fluent-crm'),
                'handler' => 'route'
            ];

            //Put the form submissions after the emails
            $sections = insert_after_key( $sections, 'subscriber_emails', 'subscriber_form_submissions', $subscriber_form_submissions );

        }

        return $sections;

    }


    /**
     * Get the columns / entry types
     * 
     * @param bool $return_all 
     * 
     * @return array $column
     */

    public function get_columns() {

       return  \GFFluentFeed\Helpers\get_entry_types_columns( static::$entryType );

    }


    /**
     *  Display form submissions for Subscriber
     * 
     * 
     */
    public function display_form_submissions( $submission_data, $subscriber ) {

            


        return $submission_data; 

    }








    /**
     *  Get instance
     * 
     *  @return object
     * 
     */
    public static function get_instance() {

        if ( self::$instance == null ) {

            self::$instance = new self;

        }

        return self::$instance;

    }
    
}