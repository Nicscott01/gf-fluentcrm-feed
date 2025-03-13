<?php

namespace GFFluentFeed\Routing;


/**
 * Get Routing Fields
 * 
 * This is a static re-creation of the fields that were intially created with ACF options.
 * 
 * @return array $fields 
 */
function get_routing_fields() {


    return [
        [
            'contact_tab' => [
                'value' => 'subscriber_form_submissions',
                'label' => 'Form Submissions'
            ],
            'columns' => [
                [
                    'column_title' => 'ID',
                    'slug' => 'id',
                    'width' => 100
                ],
                [
                    'column_title' => 'Form Title',
                    'slug' => 'form_title',
                    'width' => ''
                ],
                [
                    'column_title' => 'Important',
                    'slug' => 'important',
                    'width' => ''
                ],
                [
                    'column_title' => 'Submitted At',
                    'slug' => 'submitted_at',
                    'width' => ''
                ]
            ]
        ],
        [
            'contact_tab' => [
                'value' => 'donation_history',
                'label' => 'Donation History'
            ],
            'columns' => [
                [
                    'column_title' => 'ID',
                    'slug' => 'id',
                    'width' => 100
                ],
                [
                    'column_title' => 'Form Title',
                    'slug' => 'form_title',
                    'width' => ''
                ],
                [
                    'column_title' => 'Amount',
                    'slug' => 'amount',
                    'width' => ''
                ],
                [
                    'column_title' => 'Type',
                    'slug' => 'type',
                    'width' => ''
                ],
                [
                    'column_title' => 'Payment Status',
                    'slug' => 'payment_status',
                    'width' => ''
                ],
                [
                    'column_title' => 'Submitted At',
                    'slug' => 'submitted_at',
                    'width' => ''
                ]
            ]
        ],
        [
            'contact_tab' => [
                'value' => 'purchase_history',
                'label' => 'Purchase History'
            ],
            'columns' => [
                [
                    'column_title' => 'ID',
                    'slug' => 'id',
                    'width' => ''
                ]
            ]
        ]
    ];
}
