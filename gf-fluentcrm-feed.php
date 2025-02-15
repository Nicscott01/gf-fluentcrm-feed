<?php
/**
 * Plugin Name: GravityForms FluentCRM Feed
 * PLugin URI: https://github.com/Nicscott01/gf-fluentcrm-feed
 * Author: Creare Web Solutions
 * Author URI: https://www.crearewebsolutions.com/
 * Description: Add subscribers into Fluent CRM from Gravity Forms and save their form entry data.
 * Requires Plugins: fluent-crm, gravityforms
 * Version: 0.1.0
 * 
 */

 namespace GFFluentFeed;
 use \GFAddOn;

 define( 'GF_FLUENT_FEED_ADDON_VERSION', '0.1.0' );

 add_action( 'gform_loaded', array( '\GFFluentFeed\GF_Fluent_Feed_AddOn_Bootstrap', 'load' ), 5 );

require_once( __DIR__ . '/inc/helper.php' );
//require_once( __DIR__ . '/inc/Controllers/Donation.php' );
require_once( __DIR__ . '/inc/Providers/GenericProvider.php' );
require_once( __DIR__ . '/inc/Providers/GravityFormsSubmissions.php' );
require_once( __DIR__ . '/inc/Providers/GravityFormsDonations.php' );
//require_once( __DIR__ . '/inc/fluent/Subscriber.php' );

class GF_Fluent_Feed_AddOn_Bootstrap {

	public static function load() {

		if ( ! method_exists( 'GFForms', 'include_feed_addon_framework' ) ) {
			return;
		}

		require_once( __DIR__ . '/GravityFormsFluentCrmFeedAddon.php' );

		GFAddOn::register( '\GFFluentFeed\GravityFormsFluentCrmFeedAddon' );
	}

}

function gf_fluent_feed_addon() {
	return GravityFormsFluentCrmFeedAddon::get_instance();
}

