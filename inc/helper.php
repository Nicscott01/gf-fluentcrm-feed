<?php

namespace GFFluentFeed\Helpers;


function insert_after_key( $array, $target_key, $new_key, $new_value ) {
	$result = array();

	foreach ( $array as $key => $value ) {
		$result[ $key ] = $value;
		if ( $key === $target_key ) {
			$result[ $new_key ] = $new_value;
		}
	}

	return $result;
}



function get_fluent_subscriber_fields() {

    $fields = [
        [
            'value' => 'user_id',
            'text' => 'User ID',
        ], [
            'value' => 'company_id',
            'text' => 'Company ID'
        ], [
            'value' => 'prefix',
            'text' => 'Prefix'
        ], [
            'value' => 'first_name',
            'text' => 'First Name',
            'field_type' => ['name', 'hidden']
        ], [
            'value' => 'last_name',
            'text' => 'Last Name',
            'field_type' => ['name', 'hidden']
        ], [
            'value' => 'email',
            'text' => 'Email',
            'field_type' => ['email', 'hidden']
        ], [
            'value' => 'timezone',
            'text' => 'Timezone'
        ], [
            'value' => 'address_line_1',
            'text' => 'Address Line 1'
        ], [
            'value' => 'address_line_2',
            'text' => 'Address Line 2'
        ], [
            'value' => 'postal_code',
            'text' => 'Postal Code'
        ], [
            'value' => 'city',
            'text' => 'City'
        ], [
            'value' => 'state',
            'text' => 'State'
        ], [
            'value' => 'country',
            'text' => 'Country'
        ], [
            'value' => 'ip',
            'text' => 'IP Address'
        ], [
            'value' => 'latitude',
            'text' => 'Latitude'
        ], [
            'value' => 'longitude',
            'text' => 'Longitude'
        ], [
            'value' => 'phone',
            'text' => 'Phone'
        ], [
            'value' => 'status',
            'text' => 'Status (pending/subscribed/bounced/unsubscribed)'
        ], [
            'value' => 'contact_tye',
            'text' => 'lead/customer'
        ], [
            'value' => 'source',
            'text' => 'Source'
        ], [
            'value' => 'avatar',
            'text' => 'Custom Contact Photo URL'
        ], [
            'value' => 'date_of_birth',
            'text' => 'Date of Birth in Y-m-d format'
        ], [
            'value' => 'last_activity',
            'text' => 'Last Activity'
        ], [
            'value' => 'updated_at',
            'text' => 'Updated At'
        ]
    ];


    //Get custom fields
    $custom_contact_fields = fluentcrm_get_custom_contact_fields();  


    if ( !empty( $custom_contact_fields ) ) {
        
        foreach( $custom_contact_fields as $custom_field ) {
            $fields[] = [
                'value' => 'custom_field_' . $custom_field['slug'],
                'text' => $custom_field['label'],
            ];
        }


    }



    return $fields;

}







/**
 * Get the columns / entry types
 * 
 * @param bool $return_all 
 * 
 * @return array $column
 */

function get_entry_types_columns( $entry_type = '', $return_all = false ) {

    //Columns
    //$columns = get_field('entry_groups', 'options' );

    $columns = [
        [
            'contact_tab' => [
                'value' => 'subscriber_form_submissions',
                'label' => 'Form Submissions'
            ],
            'columns' => [
                [
                    'column_title' => 'ID',
                    'slug' => 'id',
                    'field_type' => ['id', 'name'],
                    'width' => 100
                ],[
                    'column_title' => 'Form Title',
                    'slug' => 'form_title',
                    'field_type' => ['form_title'],
                    'width' => ''
                ],[
                    'column_title' => 'Important Data',
                    'slug' => 'important',
                    'width' => ''
                ],[
                    'column_title' => 'Submitted At',
                    'slug' => 'submitted_at',
                    'field_type' => ['date_created'],
                    'width' => ''
                ],
            ]
        ], [
            'contact_tab' => [
                'value' => 'donation_history',
                'label' => 'Donation History'
            ],
            'columns' => [
                [
                    'column_title' => 'ID',
                    'slug' => 'id',
                    'width' => 100
                ],[
                    'column_title' => 'Form Title',
                    'slug' => 'form_title',
                    'width' => ''
                ],[
                    'column_title' => 'Amount',
                    'slug' => 'amount',
                    'width' => ''
                ],[
                    'column_title' => 'Type',
                    'slug' => 'type',
                    'width' => ''
                ],[
                    'column_title' => 'Payment Status',
                    'slug' => 'payment_status',
                    'width' => ''
                ],[
                    'column_title' => 'Submitted At',
                    'slug' => 'submitted_at',
                    'width' => ''
                ],
            ]
        ]
    ];


    if ( $return_all ) {
        return $columns;
    }


    if ( !empty( $entry_type ) ) {
        
        foreach( $columns as $column ) {

            if ( $column['contact_tab']['value'] == $entry_type ) {
                return $column['columns'];
            }
        }
    }


}
