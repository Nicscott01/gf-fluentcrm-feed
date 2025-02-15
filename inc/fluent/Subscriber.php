<?php

namespace GFFluentFeed;

use function GFFluentFeed\Helpers\insert_after_key;


/**
 * Register Providers
 * 
 */


add_filter( 'fluentcrm_commerce_provider', function() {


    return 'gfff_purchase_history';

});


add_filter( 'fluent_crm/purchase_history_providers', function( $providers ) {


    $providers['gfff_purchase_history'] = [
        'title' => 'Gravity Forms Purchase History',
        'name' => 'gfff_purchase_history'
    ];

    $providers['gfff_donation_history'] = [
        'title' => 'Gravity Forms Donation History',
        'name' => 'gfff_donation_history'
    ];

    return $providers;

});



add_filter( 'fluent_crm/form_submission_providers', function( $providers ) {


    $providers['gfff_form_submissions'] = [
        'title' => 'Gravity Forms Submissions',
        'name' => 'gfff_form_submissions'
    ];

    return $providers;

});




//Maybe setup the subscriber profile section
add_filter( 'fluentcrm_profile_sections', function( $sections ) {

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

}, 15, 1 );




















































/**
 * This sets up a separate donation tab
 * however we don't have a controller that
 * is in the app so, we'll forgo using
 * this until maybe fluent exposes an api
 * for this (or we write our own vue.js app to tie in)
 * 
 */
add_filter( 'fluentcrm_profile_sections_d', function( $sections ) {

    if ( !isset( $sections['donation_history'] ) ) {

        $donation_history = [
            'name'    => 'donation_history',
            'title'   => __('Donation History', 'fluent-crm'),
            //'handler' => 'route',
            'handler' => 'GFFF\\Controllers\\DonationController@donationHistory'
 
        ];

        //Put the form submissions after the emails
        $sections = insert_after_key( $sections, 'subscriber_purchases', 'donation_history', $donation_history );

    } 

    //error_log( 'sections' . print_r( $sections, 1) );

    return $sections;

}, 10, 1 );





function my_fluentcrm_donation_history_handler( $subscriber )
{
    // $subscriber is the Subscriber Model object, so you can get ID, email, etc.
    $subscriber_id    = $subscriber->id;
    $subscriber_email = $subscriber->email;

    /*
     * Retrieve your donation data however you store it. 
     * E.g., maybe from a custom DB table, a GiveWP table, etc.
     * Below is pseudo-code to illustrate:
     */
    //$donations = my_get_donations_by_email( $subscriber_email );
    $donations = [];
    
    // Format rows in the same structure that FluentCRM expects
    $rows = [];
    foreach ( $donations as $donation ) {
        $rows[] = [
            'id'         => $donation->id,
            'date'       => $donation->donation_date,
            'amount'     => $donation->amount,
            'campaign'   => $donation->campaign_name,
            // etc...
        ];
    }

    // Setup the columns (you can label them however you want)
    $columns = [
        [
            'key'   => 'id',
            'title' => __( 'Donation ID', 'fluent-crm' ),
        ],
        [
            'key'   => 'date',
            'title' => __( 'Date', 'fluent-crm' ),
        ],
        [
            'key'   => 'amount',
            'title' => __( 'Amount', 'fluent-crm' ),
        ],
        [
            'key'   => 'campaign',
            'title' => __( 'Campaign', 'fluent-crm' ),
        ],
        // etc...
    ];

    // Construct the response array
    return [
        'rows'       => $rows,
        'columns'    => $columns,
        // If you want to handle pagination:
        'has_more'   => false,
        'pagination' => [
            'total'        => count( $rows ),
            'per_page'     => 999999,
            'current_page' => 1,
        ],
    ];
}





/**
 *  This code pulls the data for display under our custom
 *  purchase area
 * 
 * 
 */

 add_filter( 'fluent_crm/gfff_donation_history_d', function( $data, $subscriber ) {

    $data['data'] = [
         [
             'order' => 'Test ORder',
             'date' => 'Today',
             'status' => 'completed',
             'total' => '$999',
             'actions' => '<a href="#">Click</a>'
         ]
     ];
 
     $data['total'] = count( $data['data']);
     $data['has_recount'] = 1;
 
     $data['columns_config'] = [
         'order' => [
             'label' => 'Order Label',
             'width' => '100px',
             'sortable' => 1,
             'key' => 'id'
         ],
         'data' => [
             'label' => 'Date Label',
             'sortable' => 1,
             'key' => 'date_created_gmt'
         ],
         'status' => [
             'label' => 'Status Label',
             'width' => '100px'
         ],
         'total' => [
             'label' => 'Total Label',
             'width' => '200px',
             'sortable' => 1,
             'key' => 'total_amount'
         ],
         'actions' => [
             'label' => 'Actions label',
         ]
     ];
 
     return $data;
 
 }, 20, 2);




