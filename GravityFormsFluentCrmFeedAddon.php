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

        $this->entry_types = [
            'subscriber_form_submissions' => [
                'label' =>'Form Submissions',
                'value' => 'subscriber_form_submissions'
            ],
            'purchase_history' => [
                'label' => 'Purchase History',
                'value' => 'purchase_history'
            ], 
            'donation_history' => [
                'label' => 'Donation History',
                'value' => 'donation_history'
            ]
        ]; 
        
		
        

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

        error_log( 'contact_data_map:' . print_r( $contact_data_map, 1 ) );


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


        

		// Retrieve the name => value pairs for all fields mapped in the fluentCrmColumn_subscriber_form_submissions' field map.
		$field_map = $this->get_field_map_fields( $feed, 'fluentCrmColumn_' . $feed['meta']['entryType'] );

        error_log( 'Field Map:' . print_r( $field_map, 1 ) );
        
		// Loop through the fields from the field map setting building an array of values to be passed to the third-party service.
		$merge_vars = array();
		foreach ( $field_map as $name => $field_id ) {

			// Get the field value for the specified field id
			$merge_vars[ $name ] = $this->get_field_value( $form, $entry, $field_id );

		}

		// Send the values to the third-party service.
        error_log( 'merge_vars:' . print_r( $merge_vars, 1 ) );

        //Find contact and add this form entry to them

        //Find Contact
        $contactApi = \FluentCrmApi('contacts');
     
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



	// # SCRIPTS & STYLES -----------------------------------------------------------------------------------------------

	/**
	 * Return the scripts which should be enqueued.
	 *
	 * @return array
	 */
	public function scripts() {
		$scripts = array(
			array(
				'handle'  => 'fluentcrm-feed-config',
				'src'     => $this->get_base_url() . '/assets/js/fluentcrm-feed-config.js',
				'version' => $this->_version,
				'deps'    => array( 'jquery' ),
				'enqueue' => array(
					array(
						'admin_page' => array( 'form_settings' ),
						'tab'        =>  $this->_slug, //'fluentcrmfeedaddon',
					),
				),
			),
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
					),
                    [
                        'name'  => 'test1',
                        'type'  => 'dynamic_field_map'
                    ]
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
                        'label' => 'Entry Type',
                        'type' => 'select',
                        'name' => 'entryType',
                        'class' => 'small',
                        'choices' => $this->entry_types

                    ],[
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
                    ]
                ]
            ),           
            
            
            
		);

       
        

        foreach( $this->entry_types as $entry_type ) {

            //var_dump( $entry_type );

            $feed_settings_fields[] = [
                'title' => $entry_type['label'],

                'fields' => [
                    [                
                    'name' => 'fluentCrmColumn_' . $entry_type['value'],
                    'type' => 'field_map',
                    'field_map' => $this->get_field_map_values( $entry_type )
                    ]
                ]
            ];
            
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
                            'name' => str_replace('-', '_', sanitize_title($column['column_title']))
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