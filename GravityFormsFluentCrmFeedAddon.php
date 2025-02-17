<?php
namespace GFFluentFeed;
use \GFForms;
use \GFFeedAddOn;
use \GFAddOn;
use FluentCrm\App\Models\Subscriber;
use FluentCrm\App\Models\SubscriberMeta;
use GFFluentFeed\Helpers;

use function \BricBreakdance\dlog;


GFForms::include_feed_addon_framework();

class GravityFormsFluentCrmFeedAddon extends GFFeedAddOn {

	protected $_version                  = GF_FLUENT_FEED_ADDON_VERSION;
	protected $_min_gravityforms_version = '2.9';
	protected $_slug                     = 'fluentcrmfeedaddon';
	protected $_path                     = 'gf-fluentcrm-feed/GravityFormsFluentCrmFeedAddon.php';
	protected $_full_path                = __FILE__;
	protected $_title                    = 'Gravity Forms FluentCRM Feed Add-On';
	protected $_short_title              = 'FluentCRM';

    /**
     * Entry Types
     * is it a subscriber_form_submissions, purchase_history, donation_history
     * @var array
     */
    protected $entry_types = [];

	private static $_instance = null;

	/**
	 * Get an instance of this class.
	 *
	 * @return GravityFormsFluentCrmFeedAddon
	 */
	public static function get_instance() {
		if ( self::$_instance == null ) {
			self::$_instance = new self;
		}

		return self::$_instance;
	}

	/**
	 * Plugin starting point.
	 */
	public function init() {

		parent::init();

        //Get our columns settings
        $columns = \GFFluentFeed\Helpers\get_entry_types_columns( '', true );

        $this->entry_types['default'] = [
            'label' => 'Select an Entry Type',
            'value' => ''
        ];

        foreach( $columns as $column ) {
        
            $this->entry_types[$column['contact_tab']['value']] = [
                'label' => $column['contact_tab']['label'],
                'value' => $column['contact_tab']['value']
            ];
        
        }		
    
	}




	// # FEED PROCESSING -----------------------------------------------------------------------------------------------

	/**
	 * Process the feed e.g. subscribe the user to a list.
	 *
	 * @param array $feed The feed object to be processed.
	 * @param array $entry The entry object currently being processed.
	 * @param array $form The form object currently being processed.
	 *
	 * @return bool|void
	 */
	public function process_feed( $feed, $entry, $form ) {
		
        //$feedName  = $feed['meta']['feedName'];

        error_log( 'Feed:' . print_r( $feed, 1 ) );
        error_log( 'Entry:' . print_r( $entry, 1 ) );

        $contact_data_map = $this->get_field_map_fields( $feed, 'entryContact' );

//        error_log( 'contact_data_map:' . print_r( $contact_data_map, 1 ) );

        if ( is_array( $contact_data_map ) ) {

            $contact_data = [];

            foreach( $contact_data_map as $name => $field_id ) {
                if ( isset( $entry[$field_id ] ) ) {
                    if ( strpos( $name, 'custom_field_' ) === 0 ) {

                        $slug = str_replace( 'custom_field_', '', $name );

                        $contact_data['custom_values'][$slug] = $entry[$field_id];

                    } else {

                        $contact_data[$name] = $entry[$field_id];

                    }
                }


            }

        }


        //Add Tags & Lists to $contact_data; looks like $contact_data['tags'] = [1,2,3,'Dynamic Tag'];
        $tags = $this->get_choices_values( $feed['meta']['entryTags'] );
        $contact_data['tags'] = $tags;

        $lists = $this->get_choices_values( $feed['meta']['entryLists'] );
        $contact_data['lists'] = $lists;

        if ( $feed['meta']['setSubscriberStatusEnable'] == '1' ) {

            $contact_data['status'] = $feed['meta']['setSubscriberStatusValue'];
        }


		// Retrieve the name => value pairs for all fields mapped in the fluentCrmColumn_subscriber_form_submissions' field map.
		$field_map = $this->get_field_map_fields( $feed, 'fluentCrmColumn_' . $feed['meta']['entryType'] );



//        error_log( 'Field Map:' . print_r( $field_map, 1 ) );
        
		// Loop through the fields from the field map setting building an array of values to be passed to the third-party service.
		$merge_vars = array();
		foreach ( $field_map as $name => $field_id ) {

			// Get the field value for the specified field id
			$merge_vars[ $name ] = $this->get_field_value( $form, $entry, $field_id );

		}

		// Send the values to the third-party service.
        error_log( 'merge_vars:' . print_r( $merge_vars, 1 ) );





        /**
         * Maybe find the contact and set status
         */
        $contactApi = \FluentCrmApi('contacts');
     


        $disable_double_opt_in = apply_filters( 'gfff_disable_double_opt_in', false );
          
        //Set status to unsubscribed if not set in form
        if ( ( !isset( $contact_data['status'] ) || $contact_data['status'] == '' ) && !$disable_double_opt_in  ) {

            //See if the contact exists
            $existing_contact = $contactApi->getContact( $contact_data['email'] );

            if( empty( $existing_contact ) ) {
                $contact_data['status'] = 'transactional';
            }
        } elseif ( $disable_double_opt_in ) {

            $contact_data['status'] = 'subscribed';

        }


        /**
         * Right before we add the contact
         */
        error_log( 'Contact Data: ' . print_r( $contact_data, 1 ) );

        $subscriber = $contactApi->createOrUpdate( $contact_data );

        if ( $subscriber ) {

            if( $subscriber->status == 'pending' ) {
                $subscriber->sendDoubleOptinEmail();
            }


            // We'll store everything in this meta key:
            $meta_key = 'gfff_' . $feed['meta']['entryType']  . '_'  . $entry['id']; //. time() . '_' . uniqid();

            // Grab the existing meta row if it exists, otherwise create it.
            /*$subscriber_meta = SubscriberMeta::where('subscriber_id', $subscriber->id)
                ->where('key', $meta_key)
                ->first();
*/
            // If it doesn't exist, create it with an empty array (serialized).
            //if ( ! $subscriber_meta ) {
                $subscriber_meta = SubscriberMeta::create([
                    'subscriber_id' => $subscriber->id,
                    'key'      => $meta_key,
                    'value'         => $merge_vars,
                ]);
            //}

            // Unserialize existing values into an array.
          /*  $submissions = maybe_unserialize( $subscriber_meta->value );

            // Make sure it’s an array in case the meta was previously something else.
            if ( ! is_array( $submissions ) ) {
                $submissions = [];
            }

            // Append the new submission data.
            $submissions[] = $merge_vars;

            // Serialize and save it back.
            $subscriber_meta->value = maybe_serialize( $submissions );
            $subscriber_meta->save();
*/

        }

	}



    /**
     * Helper to get tag/lists settings
     * 
     * @param array $choice_array
     * 
     * @return array $items - tags, lists
     */
    public function get_choices_values( $choice_array ) {

        $items = [];

        if ( !empty( $choice_array ) ) {

            foreach(  $choice_array as $item_id => $selection ) {

                if ( $selection == '1' ) {
                    $items[] = (int) $item_id;
                }

            }

        }

        return $items;

    }




	// # SCRIPTS & STYLES -----------------------------------------------------------------------------------------------

	/**
	 * Return the scripts which should be enqueued.
	 *
	 * @return array
	 */
	public function scripts_disable() {
		$scripts = array(
			
		);

		return array_merge( parent::scripts(), $scripts );
	}

	/**
	 * Return the stylesheets which should be enqueued.
	 *
	 * @return array
	 */
	public function styles() {

		$styles = array(
			/*array(
				'handle'  => 'my_styles_css',
				'src'     => $this->get_base_url() . '/css/my_styles.css',
				'version' => $this->_version,
				'enqueue' => array(
					array( 'field_types' => array( 'poll' ) ),
				),
			),*/
		);

		return array_merge( parent::styles(), $styles );
	}

	// # ADMIN FUNCTIONS -----------------------------------------------------------------------------------------------

	/**
	 * Creates a custom page for this add-on.
	 */
	/*public function plugin_page() {
		echo 'This page appears in the Forms menu';
	}*/


    /**
	 * Return the plugin's icon for the plugin/form settings menu.
	 *
	 * 
	 *
	 * @return string
	 */
	public function get_menu_icon(): string {

        $icon = file_get_contents( __DIR__ . '/assets/icons/fluent-icon.svg' );
        //$icon = '<svg style="height: 24px; width: 37px;" viewBox="0 0 300 235" xmlns="http://www.w3.org/2000/svg" fill="currentColor"><path d="M300,0c0,0 -211.047,56.55 -279.113,74.788c-12.32,3.301 -20.887,14.466 -20.887,27.221l0,38.719c0,0 169.388,-45.387 253.602,-67.952c27.368,-7.333 46.398,-32.134 46.398,-60.467c0,-7.221 0,-12.309 0,-12.309Z"/><path d="M184.856,124.521c0,-0 -115.6,30.975 -163.969,43.935c-12.32,3.302 -20.887,14.466 -20.887,27.221l0,38.719c0,0 83.701,-22.427 138.458,-37.099c27.368,-7.334 46.398,-32.134 46.398,-60.467c0,-7.221 0,-12.309 0,-12.309Z"/></svg>';

		return $icon;

	}


	/**
	 * @inheritdoc
	 * @since 2.0.0
	 */
	public function plugin_settings_icon(): string {
		return $this->get_menu_icon();
	}



	/**
	 * Configures the settings which should be rendered on the add-on settings tab.
	 *
	 * @return array
	 */
	public function plugin_settings_fields() {
		return array(
			array(
				'title'  => esc_html__( 'FluentCRM Feed Settings', 'fluentcrmfeedaddon' ),
				'fields' => array(
					array(
						'name'    => 'textbox',
						'tooltip' => esc_html__( 'This is the tooltip', 'fluentcrmfeedaddon' ),
						'label'   => esc_html__( 'This is the label', 'fluentcrmfeedaddon' ),
						'type'    => 'text',
						'class'   => 'small',
					)
				),
			),
		);
	}

	/**
	 * Configures the settings which should be rendered on the feed edit page in the Form Settings > Simple Feed Add-On area.
	 *
	 * @return array
	 */
	public function feed_settings_fields() {


        //Get the Fluent Fields
        $fields = Helpers\get_fluent_subscriber_fields();



		$feed_settings_fields = array(
			array(
				'title'  => esc_html__( 'FluentCRM Feed Settings', 'fluentcrmfeedaddon' ),
				'fields' => [
                    [
						'label'   => esc_html__( 'Feed name', 'fluentcrmfeedaddon' ),
						'type'    => 'text',
						'name'    => 'feedName',
						'tooltip' => esc_html__( 'Name your feed', 'fluentcrmfeedaddon' ),
						'class'   => 'small',
                    ],[
                        'name' => 'entryType',
                        'label' => 'Entry Type',
                        'type' => 'select',
                        'onchange' => "jQuery(this).parents('form').submit();",
                        'class' => '',
                        'tooltip' => 'This is where the submissions will show up in FluentCRM.',
                        'choices' => $this->entry_types

                    ]
                ]
            ), 
            array(
                'title' => 'Subscriber Fields',
                'tooltip' => 'Map the FluentCRM subscriber fields to the form entry data.',
                'fields' =>  [
                    [
                        'label' => 'Contact',
                        'name' => 'entryContact',
                        'type' => 'field_map',
                        'field_map' => (function() use ( $fields ) {

                            if (!is_array($fields)) {
                                error_log('Warning: $fields is not an array.');
                                return [];
                            }
                        
                            $map = array_map(function($field) {
                                
                                if ( strpos( $field['value'], 'custom_field_' ) === 0 ) {
                                    $label = 'Custom Field: ' . $field['text'];
                                } else {
                                    $label = $field['text'];
                                }
                                
                                
                                $return = [
                                    'label'      => $label ?? '',
                                    'name'       => $field['value'] ?? '',
                                    'field_type' => $field['field_type'] ?? ''
                                ];
                                return $return;

                            }, $fields);
                        
                        
                            return $map;
                
                        })(),
                        'required' => 1
                    ],[
                        'label' => 'Tags',
                        'type' => 'checkbox',
                        'name' => 'entryTags[]',
                        'class' => '',
                        'tooltip' => 'Select the tag(s) to assign to the contact',
                        'choices' => $this->get_choices_for_crm_items( 'tags', 'entryTags')
                    ],[
                        'label' => 'Lists',
                        'type' => 'checkbox',
                        'name' => 'entryLists[]',
                        'class' => '',
                        'tooltip' => 'Select the list(s) to assign to the contact',
                        'choices' => $this->get_choices_for_crm_items( 'lists', 'entryLists')
                    ], [
                
                        'name' => 'setSubscriberStatus',
                        'label' => 'Set Subscriber Status',
                        'type'  => 'checkbox_and_select',
                        'tooltip' => 'Be careful! You typically want to set this to pending. Status will be transactional by default.',
                        'checkbox' => [
                            'name' => 'setSubscriberStatusEnable',
                            'label' => 'Manually set subscriber status for all entries',
                            'default_value' => 0
                        ], 
                        'select' => [
                            'name' => 'setSubscriberStatusValue',
                            'choices' => $this->get_subscriber_statuses()
                        ]
                    
                        
                    ] 
                ]
            ),           
            
            
            
		);

       
        

        foreach( $this->entry_types as $entry_type ) {

            //var_dump( $entry_type );

            if ( $entry_type['value'] !== '' && ( $this->get_setting( 'entryType' ) == $entry_type['value'] ) ) {

                $feed_settings_fields[ ] = [
                    'title' => $entry_type['label'],
                    'tooltip' => 'Map the FluentCRM quick-view data to the form entry.',
                    'fields' => [
                        [                
                        'name' => 'fluentCrmColumn_' . $entry_type['value'],
                        'type' => 'field_map',
                        'dependency' => [
                            'field' => 'entryType',
                            'values' => [ $entry_type['value'] ]
                        ],
                        'field_map' => $this->get_field_map_values( $entry_type )
                        ]
                    ]
                ];
            }
            
        }
    
        //error_log( 'feed_settings_fields: ' . print_r( $feed_settings_fields , 1 ));


        return $feed_settings_fields;
	}





    /**
     * Get Field Map Values
     * 
     * Loop through the field colums and add to the map
     * 
     * @param array $entry_type
     * @return array $field_map_vals
     */

    public function get_field_map_values( $entry_type ) {
     
            //Get our entry field settings
            $gf_fluentcrm_routing = get_field( 'entry_groups', 'option' );

            foreach( $gf_fluentcrm_routing as $route ) {
                
                if ( $route['contact_tab']['value'] == $entry_type['value'] ) {

                    $field_map_vals = [];

                    foreach( $route['columns'] as $column ) {

                        $field_map_vals[] = [
                            'label' => $column['column_title'],
                            'name' => str_replace('-', '_', sanitize_title($column['column_title'])),
                            'field_type' => $column['field_type'] ?? []
                        ];
                    }


                    if ( !empty( $field_map_vals ) ) {
                        return $field_map_vals;
                    }
                }
            }

            return [];

    }



    /**
     * Get Items (Lists/Tags)
     * 
     * @param string $item - lists, tags
     * @param string $field_name - name of overall choice field that will put the choices in an array
     * 
     * @return array $items_array - the formatted items for the choices
     */
    public function get_choices_for_crm_items( $item , $field_name ) {
                            
        $itemApi = FluentCrmApi( $item );

        $allItems = $itemApi->all();


        $items_array = [];

        if ( $allItems ) {

            foreach ( $allItems as $item ) {

                $items_array[] = [
                    'name' => $field_name . "[" . (string) $item->id . "]",
                    'label' => $item->title,
                    'value' => (string) $item->id
                ];
                
                
            }
        }




        return $items_array;

    }





    /**
     * Get Subscriber Statuses
     * 
     * @return array $subscriber_statuses
     */
    public function get_subscriber_statuses() {

        //Get them from fluent
        $statuses = fluentcrm_subscriber_statuses(true);

        $subscriber_statuses = [];
        //Map them
        foreach( $statuses as $status ) {

            $subscriber_statuses[] = [
                'label' => $status['title'],
                'value' => $status['slug']
            ];
        }

        error_log( 'subscriber_statuses: ' . print_r( $subscriber_statuses, 1) );

        return $subscriber_statuses;
    }

    /**
     *  Validate the mapped fields
     * 
     * #not sure this is needed?
     * 
     * 
     */
    public function validate_custom_meta( $field ) {


    }

	/**
	 * Configures which columns should be displayed on the feed list page.
	 *
	 * @return array
	 */
	public function feed_list_columns() {
		return array(
			'feedName'  => esc_html__( 'Name', 'fluentcrmfeedaddon' ),
			'mytextbox' => esc_html__( 'My Textbox', 'fluentcrmfeedaddon' ),
		);
	}

	/**
	 * Format the value to be displayed in the mytextbox column.
	 *
	 * @param array $feed The feed being included in the feed list.
	 *
	 * @return string
	 */
	public function get_column_value_mytextbox( $feed ) {
		return '<b>' . rgars( $feed, 'meta/mytextbox' ) . '</b>';
	}

	/**
	 * Prevent feeds being listed or created if an api key isn't valid.
	 *
	 * @return bool
	 */
	public function can_create_feed() {

		// Get the plugin settings.
		$settings = $this->get_plugin_settings();

		// Access a specific setting e.g. an api key
		$key = rgar( $settings, 'apiKey' );

		return true;
	}

}