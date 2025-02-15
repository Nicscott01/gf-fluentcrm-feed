<?php

namespace GFFluentFeed\Providers\GravityForms;
use FluentCrm\App\Models\Subscriber;
use FluentCrm\App\Models\SubscriberMeta;
use GFAPI;



class GFDonations extends GenericProvider {

    /**
     *  Instance
     * 
     *  @var object null
     */
    public static $instance = null;


    public static $provider = 'gfff_donation_history';
    public static $providerTitle = 'Gravity Forms Donations';
    public static $entryType = 'donation_history';

    /**
     *  Construct
     * 
     * 
     */
    public function __construct() {


        //Maybe setup the subscriber profile section
        add_filter( 'fluentcrm_profile_sections', [ $this, 'setup_form_submissions_section' ], 10, 1 ); 

        //Register provider
        add_filter( 'fluent_crm/purchase_history_providers', [ $this, 'register_provider' ], 20, 1 );

        //Display Data
        add_filter( 'fluent_crm/purchase_history_' . self::$provider, [ $this, 'display_form_submissions'], 20, 2 );

        //??Do I need this??
        add_filter( 'fluentcrm_commerce_provider', function() {
        
            return self::$provider;
        
        });

        add_action( 'init', function() {
            if ( isset( $_GET['nrs']) ) {

                $entry = GFAPI::get_entry( $_GET['nrs'] );
                $this->save_payment_status( $entry, [] );
            }
        });

        add_action( 'gform_post_payment_action', [$this, 'save_payment_status'], 10, 2 );
        //add_action( 'gform_post_payment_refunded', [$this, 'save_payment_status'], 10, 2 );
        add_action( 'gform_post_payment_transaction', [$this, 'save_transaction_status'], 10, 3 );
        
        //Handle all the other posibilities
       // add_action( 'gform_post_payment_refunded', [$this, 'update_subscription_status'], 10, 1 );
        //add_action( 'gform_subscription_canceled', [$this, 'update_subscription_status_cancel'], 10, 1 );
        add_action( 'gform_payment_details', [$this, 'refresh_cache_transaction_status'], 10, 2 );




        add_filter( 'fluent_crm/subscriber_top_widgets', [ $this, 'donation_summary_pane' ], 100, 2 ); 
                

        
        

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


        if ( isset( $sections['subscriber_purchases' ] ) ) {

            //Change the name to include "Donations"
            $sections['subscriber_purchases']['title'] = 'Purchases & Donations';
    
        }

        return $sections;

    }



    public function get_subscriber_submissions( $subscriber ) {

        $meta_key_base = self::$provider;

        $subscriber_submissions = SubscriberMeta::where('subscriber_id', $subscriber->id)
        ->where('key', 'LIKE', $meta_key_base .'_%' )
        ->get()
        ->map(function ($item) {
            return maybe_unserialize($item->value);
        })
        ->toArray();

        return $subscriber_submissions;

    }



    /**
     *  Display form submissions for Subscriber
     * 
     * 
     */
    public function display_form_submissions( $submission_data, $subscriber ) {


        //$email = isset( $subscriber->email ) ? $subscriber->email : false;

        //dlog( $email, 'subscriber email' );

        $subscriber_submissions = $this->get_subscriber_submissions( $subscriber );

        //error_log( 'subscriber_submissions: ' . print_r( $subscriber_submissions, 1));


        $columns = $this->get_columns();
        $formattedSubmissions = [];

        //error_log( 'columns ' . print_r( $columns, 1 ));

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
                    //error_log( 'gf_entry: ' . print_r( $gf_entry, 1) );

                    if (!is_wp_error($gf_entry) && isset($gf_entry['id'])) {

                        $admin_url = admin_url( sprintf( "admin.php?page=gf_entries&view=entry&id=%s&lid=%s", $gf_entry['form_id'], $gf_entry['id']  ) );
                        
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



    public function get_donor_history( $submissions ) {

        $oldest = $submissions[0];

        $submissions_desc = array_reverse( $submissions );
        $newest = $submissions_desc[0];

        $oldest_dt = new \DateTime( $oldest['submitted_at'] );
        $newest_dt = new \DateTime( $newest['submitted_at'] );

        $lifetime_value = 0;

        $donations_count = 0;

        foreach( $submissions as $sub ) {

            if ( in_array( $sub['payment_status'], ['Paid', 'Active'] ) ) {
              
                $amount = str_replace( '$', '', $sub['amount'] );

                $lifetime_value += (int) $amount;

                $donations_count++;
            }

        }


        return [
            'first_donation' => [
                'label' => 'Donor Since',
                'value' => $oldest_dt->format( 'F j, Y')
            ],
            'recent_donation' => [
                'label' => 'Last Donation',
                'value' => $newest_dt->format( 'F j, Y')
            ], 
            'donation_count' => [
                'label' => 'Total Donations',
                'value' => $donations_count 
            ],
            'lifetime_value' => [
                'label' => 'Lifetime Value',
                'value' => sprintf('$%.2f', $lifetime_value )
            ]
        ];
        

    }



    public function donation_summary_pane( $top_widgets, $subscriber ) {

        //error_log( 'top_widgets' . print_r( $top_widgets, 1 ));

        //Get the donations for this subscriber


        $subscriber_submissions = $this->get_subscriber_submissions( $subscriber );


        $donor_history = $this->get_donor_history( $subscriber_submissions );


        ob_start();

        ?>

        <ul class="fc_full_listed">
            <?php

            foreach( $donor_history as $data ) {
                printf( '<li>
                <span class="fc_list_sub">%s</span>
                <span class="fc_list_value">%s</span>', $data['label'], $data['value']);
            }
            ?>
           
        </ul>

        <?php

        $donation_summary_html = ob_get_clean();

            $top_widgets[] = [
                'title' => 'Donation Summary',
                'content' => $donation_summary_html
            ];

            
            return $top_widgets;

    }



    public function save_transaction_status( $txn_id, $entry_id, $transaction_type ) {
        
        $entry = \GFAPI::get_entry( $entry_id );
        
        $this->save_payment_status( $entry, $transaction_type );

    }


    public function update_subscription_status( $entry ) {

        error_log( 'update_subscription_status: ' . print_r( $entry, 1 ) );

        $this->save_payment_status( $entry );
    }


    /**
     * Manually change the payment_status to "Canceled"
     * for writing to Fluent
     * 
     */
    public function update_subscription_status_cancel( $entry ) {

        //We have to change this becuase it's not updated yet for whatever reason
        $entry['payment_status'] = 'Canceled';

        error_log( 'update_subscription_status_cancel: ' . print_r( $entry, 1 ) );

        $this->save_payment_status( $entry );
    }



    public function refresh_cache_transaction_status( $form_id, $entry ) {

        $this->save_payment_status( $entry );

    }

    /**
     * Save the Payment status to the Fluent Record
     * 
     * @param array $entry - the entry object
     * @param array $action - event details
     * 
     * 
     */

    public function save_payment_status( $entry, $action = [] ) {

        global $wpdb;


        error_log( 'save_payment_status: ' . print_r( $entry, 1 ) );
        error_log( 'action: ' . print_r( $action, 1 ) );


        
        //error_log( 'entry after gform_post_payment_completed: ' . print_r( $entry, 1 ));
        //error_log( 'txn_id after gform_post_payment_completed: ' . print_r( $txn_id, 1 ));
        //error_log( 'transaction_type after gform_post_payment_completed: ' . print_r( $transaction_type, 1 ));

  
        // Define the meta key and the unique value you're searching for
        $meta_key = self::$provider . '_' . $entry['id'];

        $results = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM {$wpdb->prefix}fc_subscriber_meta WHERE `key` = %s",
                $meta_key
            )
        );

        //error_log('wpdb results ' . print_r( $results , 1 ));


        if (!empty($wpdb->last_error)) {

            error_log('Database Error during save_payment_status: ' . $wpdb->last_error);

        } elseif (!empty($results)) {

            foreach ($results as $row) {

                //error_log('Found result: ' . print_r($row, true));
                //Update the payment_status
                $value = maybe_unserialize( $row->value );
                

                /**
                 * We need to do this for when we "update cache" 
                 * becuase there's no action being sent. So we just
                 * want to mirror the $entry object at this point.
                 * 
                 */
                if ( empty( $action ) ) {
                    $value['payment_status'] = $entry['payment_status'];
                } else {
                    $value['payment_status'] = $action['payment_status'];
                }

                SubscriberMeta::where('subscriber_id', $row->subscriber_id )
                ->where('key', $meta_key)
                ->update( [ 'value' => maybe_serialize( $value )] );


            }

        } else {

            error_log('No results found for meta key: ' . $meta_key);

        }

        
        
        
        




        

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


GFDonations::get_instance();