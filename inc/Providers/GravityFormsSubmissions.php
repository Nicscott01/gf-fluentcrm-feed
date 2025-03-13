<?php

namespace GFFluentFeed\Providers\GravityForms;
use FluentCrm\App\Models\Subscriber;
use FluentCrm\App\Models\SubscriberMeta;
use GFAPI;
use function GFFluentFeed\Helpers\insert_after_key;



class Submissions extends GenericProvider {

    /**
     *  Instance
     * 
     *  @var object null
     */
    public static $instance = null;


    public static $provider = 'gfff_subscriber_form_submissions';
    public static $providerTitle = 'Gravity Forms Entries';
    public static $entryType = 'subscriber_form_submissions';

    /**
     *  Construct
     * 
     * 
     */
    public function __construct() {


        //Maybe setup the subscriber profile section
        add_filter( 'fluentcrm_profile_sections', [ $this, 'setup_form_submissions_section' ], 10, 1 ); 

        //Register provider
        add_filter( 'fluent_crm/form_submission_providers', [ $this, 'register_provider' ], 20, 1 );

        //Display Data
        add_filter( 'fluentcrm_get_form_submissions_' . self::$provider, [ $this, 'display_form_submissions'], 20, 2 );

        //??Do I need this??
        add_filter( 'fluentcrm_commerce_provider', function() {
        
            return self::$provider;
        
        });
        

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



    public function get_columns() {

        //Columns
        $columns = get_field('entry_groups', 'options' );


        foreach( $columns as $column ) {

            if ( $column['contact_tab']['value'] == self::$entryType ) {
                return $column['columns'];
            }
        }

    }


    /**
     *  Display form submissions for Subscriber
     * 
     * 
     */
    public function display_form_submissions( $submission_data, $subscriber ) {

        //error_log( 'subscriber: ' . print_r( $subscriber, 1 ) );

        //$email = isset( $subscriber->email ) ? $subscriber->email : false;

        //dlog( $email, 'subscriber email' );

        $meta_key_base = self::$provider; //'gfff_subscriber_form_submissions';

        $subscriber_submissions = SubscriberMeta::where('subscriber_id', $subscriber->id)
        ->where('key', 'LIKE', $meta_key_base .'_%' )
        ->get()
        ->map(function ($item) {
            return maybe_unserialize($item->value);
        })
        ->toArray();

        //error_log( 'subscriber_submissions: ' . print_r( $subscriber_submissions, 1));


        $columns = $this->get_columns();
        $formattedSubmissions = [];

        $subscriber_submissions = array_reverse( $subscriber_submissions );

        if ( count( $subscriber_submissions ) ) {

                foreach( $subscriber_submissions as $entry ) {

                    $this_entry = [];

                    foreach( $columns as $column ) {

                        $column_key = $column['slug']; //str_replace( ' ', '_', strtolower( $column['column_title'] ) ); 

                        $this_entry[$column_key] = $entry[$column_key] ?? '';

                    }

                    //Get the Gravity Entry link
                    $gf_entry = GFAPI::get_entry( $entry['id'] );

                    if (!is_wp_error($gf_entry) && isset($gf_entry['id'])) {

                        $admin_url = admin_url( sprintf( "admin.php?page=gf_entries&view=entry&id=%s&lid=%s", $gf_entry['form_id'], $gf_entry['id']  ) );
                        //error_log( 'admin_url: ' . print_r( $admin_url, 1) );

                        
                        $this_entry['action'] = sprintf( '<a href="%s" target="_blank">View Submission</a>', $admin_url );

                    } else {
                        $this_entry['action'] = '';
                    }


                    $formattedSubmissions[] = $this_entry;

                    /*$formattedSubmissions[] = [
                        'id' => $entry['id'] ?? '',
                        'title' => $entry['form_title'] ?? '',
                        'submitted_at' => $entry['submitted_at'] ?? '',
                        'action' => sprintf( '<a href="%s" target="_blank">View Submission</a>', '#' ) 
                    ];*/


                }

                //error_log( 'formattedSubmissions: ' . print_r( $formattedSubmissions, 1));

                
        }


        $columns_config = [];
        //Prepare the columns
        foreach( $columns as $column ) {

            $columns_config[$column['slug']] = [
                'label' => $column['column_title'],  
            ];

            if( !empty( $column['width'] ) ) {
                $columns_config[$column['slug']]['width'] = sprintf( '%spx', $column['width'] );
            }

        }


        //Add the action column
        $columns_config['action'] = [
            'label' => 'Action'
        ];
        
        //error_log( 'columns_config: ' . print_r( $columns_config, 1));
        

        //dlog( $submissions, 'submissions' );

        $submission_data = [
            'total' => count($formattedSubmissions),
            'data' => $formattedSubmissions,
            'columns_config' => $columns_config
        ];
        
    


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


Submissions::get_instance();