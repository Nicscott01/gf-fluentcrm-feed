<?php

namespace GFFF\Controllers;

class DonationController
{
    public function donationHistory($request)
    {
        /*
         | $request is typically an instance of \FluentCrm\Framework\Request\Request
         | which has the subscriber model in:
         |    $request->get('subscriber')
         | and possibly other data like pagination, search, etc.
         */
        $subscriber = $request->get('subscriber');
        
        if (!$subscriber) {
            // If there's no subscriber, return an empty structure so we don't break
            return [
                'rows'       => [],
                'columns'    => [],
                'has_more'   => false,
                'pagination' => [
                    'total'        => 0,
                    'per_page'     => 0,
                    'current_page' => 1
                ]
            ];
        }

        $email = $subscriber->email;
        // or $subscriber->id if you store donations by contact ID

        // 1) Retrieve donation data from wherever you store it
        // For example:
        $donations = my_get_donations_by_email($email); // pseudo function

        // 2) Build rows
        $rows = [];
        foreach ($donations as $donation) {
            $rows[] = [
                'id'       => $donation->id,
                'date'     => $donation->date,
                'amount'   => $donation->amount,
                'campaign' => $donation->campaign_name
            ];
        }

        // 3) Define columns for the table
        $columns = [
            [ 'key' => 'id',       'title' => __('Donation ID', 'fluent-crm') ],
            [ 'key' => 'date',     'title' => __('Date', 'fluent-crm') ],
            [ 'key' => 'amount',   'title' => __('Amount', 'fluent-crm') ],
            [ 'key' => 'campaign', 'title' => __('Campaign', 'fluent-crm') ],
        ];

        // 4) Return what FluentCRM expects
        return [
            'rows'       => $rows,
            'columns'    => $columns,
            'has_more'   => false, // if you want to implement real pagination, set true and handle it
            'pagination' => [
                'total'        => count($rows),
                'per_page'     => 99999,
                'current_page' => 1
            ]
        ];
    }
}
