<?php
/**
 * Isolated regression checks. All IDs and choice labels below are fixtures.
 * Run from the repository root: php tests/checkbox-choice-tags.php
 * No WordPress connection, database, or real contact is used.
 */

namespace FluentCrm\App\Models {
	class SubscriberMeta {
		public static function create( $data ) {
			return (object) $data;
		}
	}
}

namespace GFFluentFeed\Helpers {
	function get_fluent_subscriber_fields() {
		return array();
	}
}

namespace {
	define( 'GF_FLUENT_FEED_ADDON_VERSION', '0.1.6' );
	set_error_handler(
		function ( $severity, $message, $file, $line ) {
			throw new \ErrorException( $message, 0, $severity, $file, $line );
		}
	);

	class GFForms {
		public static function include_feed_addon_framework() {}
	}

	class GFFeedAddOn {
		public $form     = array();
		public $posted   = array();
		public $errors   = array();
		public $logs     = array();

		public function get_current_form() {
			return $this->form;
		}
		public function get_posted_settings() {
			return $this->posted;
		}
		public function set_field_error( $field, $message ) {
			$this->errors[] = $message;
		}
		public function log_debug( $message ) {
			$this->logs[] = $message;
		}
		public function get_field_map_fields( $feed, $name ) {
			return 'entryContact' === $name ? array( 'email' => '1' ) : array();
		}
		public function get_setting( $name ) {
			return '';
		}
	}

	function rgar( $array, $key, $default = null ) {
		return is_array( $array ) && isset( $array[ $key ] ) ? $array[ $key ] : $default;
	}
	function rgars( $array, $path, $default = null ) {
		foreach ( explode( '/', $path ) as $key ) {
			if ( ! is_array( $array ) || ! isset( $array[ $key ] ) ) {
				return $default;
			}
			$array = $array[ $key ];
		}
		return $array;
	}
	function wp_json_encode( $value ) {
		return json_encode( $value );
	}
	function esc_html__( $value, $domain ) {
		return $value;
	}
	function apply_filters( $name, $value ) {
		return $value;
	}
	function fluentcrm_subscriber_statuses( $details ) {
		return array();
	}

	class FakeContacts {
		public $data = array();
		public $tags = array( 999 );
		public function getContact( $email ) {
			return (object) array( 'id' => 1, 'status' => 'subscribed' );
		}
		public function createOrUpdate( $data ) {
			$this->data = $data;
			$this->tags = array_values( array_unique( array_merge( $this->tags, $data['tags'] ) ) );
			return (object) array( 'id' => 1, 'status' => 'subscribed' );
		}
	}

	$fixture_tag_ids = array( 101, 102, 103 );
	$contacts        = new FakeContacts();
	function FluentCrmApi( $name ) {
		global $fixture_tag_ids, $contacts;
		if ( 'contacts' === $name ) {
			return $contacts;
		}
		return new class( $fixture_tag_ids ) {
			private $ids;
			public function __construct( $ids ) {
				$this->ids = $ids;
			}
			public function all() {
				return array_map(
					function ( $id ) {
						return (object) array( 'id' => $id, 'title' => 'Fixture tag ' . $id );
					},
					$this->ids
				);
			}
		};
	}

	require dirname( __DIR__ ) . '/GravityFormsFluentCrmFeedAddon.php';

	$checks = 0;
	function expect_same( $expected, $actual, $name ) {
		global $checks;
		++$checks;
		if ( $expected !== $actual ) {
			throw new \RuntimeException( $name . ': ' . json_encode( $actual ) );
		}
	}
	function fixture_field( $id, $values ) {
		$inputs  = array();
		$choices = array();
		foreach ( $values as $index => $value ) {
			// Include a real GF-style gap: .9 -> .11, never .10.
			$input_id = 0 === $index ? 1 : ( 1 === $index ? 2 : 11 );
			$inputs[] = array( 'id' => $id . '.' . $input_id, 'label' => 'Label ' . $index );
			$choices[] = array( 'text' => 'Label ' . $index, 'value' => $value );
		}
		return (object) array(
			'id'         => $id,
			'type'       => 'checkbox',
			'adminLabel' => '',
			'label'      => 'Fixture interests',
			'inputs'     => $inputs,
			'choices'    => $choices,
		);
	}

	$addon = new \GFFluentFeed\GravityFormsFluentCrmFeedAddon();
	$form  = array( 'id' => 77, 'fields' => array( fixture_field( 7, array( 'alpha', 'beta', '0' ) ) ) );
	$addon->form = $form;
	$sources    = $addon->get_checkbox_choice_sources( $form );
	$keys       = array_keys( $sources );
	$rows       = array(
		array( 'key' => $keys[0], 'value' => '101' ),
		array( 'key' => $keys[1], 'value' => '102' ),
		array( 'key' => $keys[2], 'value' => '103' ),
	);
	$feed = array( 'meta' => array( 'entryChoiceTags' => $rows ) );

	expect_same( 3, count( $sources ), 'three configurable choices' );
	expect_same( array(), $addon->get_checkbox_choice_tag_ids( array(), array(), $form ), 'old feed' );
	expect_same( array(), $addon->get_checkbox_choice_tag_ids( $feed, array(), $form ), 'no checked boxes' );
	expect_same( array( 101 ), $addon->get_checkbox_choice_tag_ids( $feed, array( '7.1' => 'alpha' ), $form ), 'one choice' );
	expect_same( array( 101, 102 ), $addon->get_checkbox_choice_tag_ids( $feed, array( '7.1' => 'alpha', '7.2' => 'beta' ), $form ), 'multiple choices' );
	expect_same( array( 103 ), $addon->get_checkbox_choice_tag_ids( $feed, array( '7.11' => '0' ), $form ), 'zero string is selected' );
	expect_same( array(), $addon->get_checkbox_choice_tag_ids( $feed, array( '7' => 'alpha,beta' ), $form ), 'parent export is not read' );
	expect_same( array(), $addon->get_checkbox_choice_tag_ids( $feed, array( '8.1' => 'alpha' ), $form ), 'different field cannot match' );
	expect_same( array(), $addon->get_checkbox_choice_tag_ids( $feed, array( '7.1' => array( 'alpha' ) ), $form ), 'malformed entry' );
	expect_same( array(), $addon->get_checkbox_choice_tag_ids( $feed, array( '7.1' => 'Label 0' ), $form ), 'labels are not values' );

	$many = $feed;
	$many['meta']['entryChoiceTags'][] = array( 'key' => $keys[0], 'value' => '102' );
	$many['meta']['entryChoiceTags'][] = $rows[0];
	expect_same( array( 101, 102 ), $addon->get_checkbox_choice_tag_ids( $many, array( '7.1' => 'alpha' ), $form ), 'one choice to many tags and deduplication' );

	$fixture_tag_ids = array( 101, 103 );
	expect_same( array(), $addon->get_checkbox_choice_tag_ids( $feed, array( '7.2' => 'beta' ), $form ), 'deleted tag skipped' );
	$fixture_tag_ids = array( 101, 102, 103 );
	$bad = array( 'meta' => array( 'entryChoiceTags' => array( array( 'key' => $keys[0], 'value' => 'New Tag Name' ) ) ) );
	expect_same( array(), $addon->get_checkbox_choice_tag_ids( $bad, array( '7.1' => 'alpha' ), $form ), 'no auto-created tag' );
	$bad['meta']['entryChoiceTags'][0]['value'] = array( 101 );
	expect_same( array(), $addon->get_checkbox_choice_tag_ids( $bad, array( '7.1' => 'alpha' ), $form ), 'invalid tag array' );
	expect_same( array(), $addon->get_checkbox_choice_tag_ids( array( 'meta' => array( 'entryChoiceTags' => 'bad' ) ), array(), $form ), 'invalid mappings container' );

	$edited = unserialize( serialize( $form ) );
	$edited['fields'][0]->choices[0]['text'] = 'Renamed label';
	expect_same( array( 101 ), $addon->get_checkbox_choice_tag_ids( $feed, array( '7.1' => 'alpha' ), $edited ), 'label edit preserves mapping' );
	$edited['fields'][0]->choices = array_reverse( $edited['fields'][0]->choices );
	expect_same( array( 101 ), $addon->get_checkbox_choice_tag_ids( $feed, array( '7.11' => 'alpha' ), $edited ), 'reordered input preserves exact value' );
	$edited['fields'][0]->choices[2]['value'] = 'new-alpha';
	expect_same( array(), $addon->get_checkbox_choice_tag_ids( $feed, array( '7.11' => 'new-alpha' ), $edited ), 'changed value invalidates old mapping' );
	$edited['fields'] = array();
	expect_same( array(), $addon->get_checkbox_choice_tag_ids( $feed, array( '7.1' => 'alpha' ), $edited ), 'deleted field' );
	$edited = $form;
	$edited['id'] = 78;
	expect_same( array(), $addon->get_checkbox_choice_tag_ids( $feed, array( '7.1' => 'alpha' ), $edited ), 'different form cannot match' );
	$ambiguous = array( 'id' => 77, 'fields' => array( fixture_field( 7, array( 'same', 'same', '' ) ) ) );
	expect_same( array(), $addon->get_checkbox_choice_sources( $ambiguous ), 'empty and duplicate values excluded' );
	$non_checkbox = array( 'id' => 77, 'fields' => array( (object) array( 'type' => 'select' ) ) );
	expect_same( array(), $addon->get_checkbox_choice_sources( $non_checkbox ), 'other field types excluded' );

	$setting = array( 'name' => 'entryChoiceTags' );
	$addon->posted = array( 'entryChoiceTags' => $rows );
	$addon->validate_checkbox_choice_tags( $setting );
	expect_same( array(), $addon->errors, 'valid configuration' );
	$addon->posted = array( 'entryChoiceTags' => array( array( 'key' => '', 'value' => '' ) ) );
	$addon->validate_checkbox_choice_tags( $setting );
	expect_same( array(), $addon->errors, 'empty editor row' );
	$addon->posted = array( 'entryChoiceTags' => array( array( 'key' => $keys[0], 'value' => '' ) ) );
	$addon->validate_checkbox_choice_tags( $setting );
	expect_same( 1, count( $addon->errors ), 'incomplete row rejected' );
	$addon->errors = array();
	$addon->posted = array( 'entryChoiceTags' => array( array( 'key' => 'stale', 'value' => '101' ) ) );
	$addon->validate_checkbox_choice_tags( $setting );
	expect_same( 1, count( $addon->errors ), 'stale row rejected' );

	$settings = $addon->feed_settings_fields();
	$map = null;
	foreach ( $settings as $section ) {
		foreach ( $section['fields'] as $field ) {
			if ( 'entryChoiceTags' === $field['name'] ) {
				$map = $field;
			}
		}
	}
	expect_same( 'generic_map', $map['type'], 'native mapping setting' );
	expect_same( true, $map['key_field']['allow_duplicates'], 'one-to-many UI' );
	expect_same( false, $map['value_field']['allow_custom'], 'no custom tag names' );
	expect_same( 3, count( $map['key_field']['choices'] ), 'choice picker options' );

	$feed['meta']['entryTags'] = array( 101 => '1' );
	$feed['meta']['entryLists'] = array( 201 => '1' );
	$feed['meta']['entryType'] = 'subscriber_form_submissions';
	$feed['meta']['setSubscriberStatusEnable'] = '0';
	$entry = array( 'id' => 500, '1' => 'fixture@example.test', '7.1' => 'alpha', '7.2' => 'beta' );
	$addon->process_feed( $feed, $entry, $form );
	expect_same( array( 101, 102 ), $contacts->data['tags'], 'feed unions static and dynamic tags' );
	expect_same( array( 201 ), $contacts->data['lists'], 'static lists unchanged' );
	expect_same( array( 999, 101, 102 ), $contacts->tags, 'existing tag retained by API stub' );
	expect_same( false, isset( $contacts->data['status'] ), 'mapping adds no status override' );
	$addon->process_feed( $feed, array( 'id' => 501, '1' => 'fixture@example.test' ), $form );
	expect_same( array( 999, 101, 102 ), $contacts->tags, 'later unchecked submission does not detach tags' );
	expect_same( array(), $addon->get_choices_values( null ), 'missing static settings' );

	echo 'PASS: ' . $checks . " isolated checks; live WordPress integration not exercised.\n";
}
