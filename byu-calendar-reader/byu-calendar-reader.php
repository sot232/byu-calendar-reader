<?php

defined('ABSPATH') or die("No script kiddies please!");

require_once dirname( __FILE__ ) . '/widget.php';
require_once dirname( __FILE__ ) . '/calendar.php';

/**
 * Plugin Name: BYU Calendar Reader (Ycal)
 * Plugin URI: http://URI_Of_Page_Describing_Plugin_and_Updates
 * Description: Specificly made to import and show BYU calendar.
 * Version: 1.0.0
 * Author: BYU CPMS
 * Author URI: http://cpms.byu.edu
 * License: GPL2
 */

 /*  Copyright 2015  BYU College of Physical and Mathematical Sciences

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License, version 2, as
    published by the Free Software Foundation.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

global $YCal_db_version;
global $table_name;
$table_name = "byu_calendar";

// database version number
$YCal_db_version = '1.0';

function y_cal_install() {
	global $wpdb;
	global $table_name;
	global $YCal_db_version;

	$table_name = $wpdb->prefix . $table_name;

// DEFAULT SETTINGS
	$new_options = array(

	// General Settings
		'table_name' => $table_name, // table name (including prefix) of calendar events imported
		'table_name_categories' => $table_name . "_categories", // table name (including prefix) of category info
		'table_name_events' => $table_name . "_events", // table name (including prefix) of custom information about event
		'image_prefix' => 'http://calendar.byu.edu', // the url prefix for images (image url stored in database w/o prefix)
		'all_day_text' =>'All Day', // text to show if event is all day
		'img_defaults' => array( // image defaults when image url is not given (unber refers to category id)
			"byu" => plugins_url( "images/default/byu.jpg", __FILE__ ),
			"288" => plugins_url( "images/default/cpms.jpg", __FILE__ ),
			"275" => plugins_url( "images/default/chem.jpg", __FILE__ ),
			"276" => plugins_url( "images/default/cs.jpg", __FILE__ ),
			"277" => plugins_url( "images/default/geology.jpg", __FILE__ ),
			"278" => plugins_url( "images/default/math.jpg", __FILE__ ),
			"279" => plugins_url( "images/default/mathEd.jpg", __FILE__ ),
			"280" => plugins_url( "images/default/physics.jpg", __FILE__ ),
			"281" => plugins_url( "images/default/stats.jpg", __FILE__ ),
		),


	// Widget Settings
		'widget_2column' => false, // show widget in two columns (not showing in admin settings page)
		'widget_howMany' => 5, // how many events to show on widget
		'widget_categories1' => '288,275,276,277,278,279,280,281', // These categories will be the first to populate the widget
		'widget_categories2' => '38', // This is second priority for events to show on widget
		'widget_priority1_days' => 14, // Limits the amount of days to pull from priority 1 categories
		'widget_priority2_days' => 14, // Limits the amount of days to pull from priority 2 categories
		'widget_showTimeZone' => False, // Show timezone ALWAYS. if false, will only show timezone if different than home timezone
		'widget_homeTimeZone' => 'MT', // Current timezone (as imported into database)
		'widget_showEndTime' => False, // show end time on widget
		'widget_showLocation' => True, // show location on widget
		'widget_priority3' => True, // show
		'widget_showUnfeatured' => False, // show unfeatured items after the first two priorities (does not apply to the 2 priorities)
		'widget_showUnMainCalendar' => False, // show not on main calendar events (same idea as above)
		'widget_showImage' => True, // show image on widget
		'widget_imageSize' => '75px', // max image width on widget

	// Calendar Settings
		'calendar_url' => '/calendar', // Wordpress url for calendar page
		'calendar_imageSize' => '150px', // max image width on calendar
		'calendar_MaxDescriptionLength' => '300', // max description length on calendar page
		'calendar_filterCategories' => array( // array for filter settings. Add things here to add filter ability (Title => CategoryID)
			//"The College" => '70,72,74,75,76,77,78,79',
			"Physical & Mathmatical Sciences" => array(
				"The College" => '288',
				"Chemistry & Biochemistry" => '275',
				"Computer Science" => '276',
				"Geological Sciences" => '277',
				"Mathematics" => '278',
				"Math Education" => '279',
				"Physics & Astronomy" => '280',
				"Statistics" => '281'
			)
		),
		'calendar_filter_unfiltered_title' => "BYU", // filter name for unfilter

	// Import Settings
		'first_query' => "274", //This is the parent category to all categories used by CPMS 
		'byu_url' =>'http://calendar.byu.edu/api/Events?', // url for uploading XML stuff
		'getYears' => 1, // number of years to grab at import
		'getMonths' => 0, // number of months to brab at import
		'refresh_frequency' => 'hourly', // how often to refresh calendar info (re-import)
		'last_updated' => time(), // time last updated

	// Pending/Auto settings
		'auto_not_showCalendar' => "",
		'auto_not_showWidget' => "288,275,276,277,278,279,280,281",
		'auto_not_approve' => "288,275,276,277,278,279,280,281",
		'pending_email' => ""
	);

	// Adds options to options database
	add_option('YCal_Options', $new_options);

	// Get the options for the options database
	$options = get_option('YCal_Options');

// SET TABLES
	$charset_collate = $wpdb->get_charset_collate();
// Create table for importings events (Column name correspondes with associative array names)
	$sql = "CREATE TABLE " . $options['table_name'] . " (
	  AllDay tinyint(1),
	  CategoryId int,
	  CategoryName varchar(1000),
	  DeptIds int,
	  DeptNames varchar (1000),
	  Description varchar(10000),
	  EndDateTime datetime,
	  EventId int NOT NULL,
	  FullUrl varchar (1000),
	  HighPrice numeric(15,2),
	  ImgAlt varchar(1000),
	  ImgUrl varchar(1000),
	  IsFree tinyint(1),
	  IsFeatured tinyint(1),
	  IsPublishedNotMainCalendar tinyint(1),
	  Latitude decimal(9,6),
	  LocationName varchar(1000),
	  Longitude decimal(9,6),
	  LowPrice numeric(15,2),
	  MoreInformationUrl varchar(1000),
	  OccurrenceId int,
	  PriceDescription varchar(1000),
	  ShortDescription varchar(1000),
	  StartDateTime datetime,
	  TagsIds int,
	  TagsNames varchar(1000),
	  Title varchar(1000),
	  PRIMARY KEY  (EventId)
	) $charset_collate;";

// Create table for importing categories info (Column name correspodes with associative array names) [check info ]
	$sql2 = "CREATE TABLE " . $options['table_name_categories'] . " (
		CategoryId int,
		CategoryTypeId int,
		CategoryType varchar (1000),
		FullUrl varchar(1000),
		Name varchar(1000),
		ParentCategoryId int,
		doNotDisplayOnHomePage tinyint(1),
		PRIMARY KEY (CategoryId)
	) $charset_collate;";

// Create table for custom events info
// pending - shows up for needing to be approved
// showOnCalendar - show on calendar
// showOnWidget - show on widget
	$sql3 = "CREATE TABLE " . $options['table_name_events'] . " (
		EventId int,
		DeptIds int,
		dateAdded datetime,
		pending tinyint(1),
		showOnCalendar tinyint(1),
		showOnWidget tinyint(1),
		forcePromote tinyint(1),
		PRIMARY KEY  (EventId)
	) $charset_collate;";

// update databases as needed
	require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
	dbDelta( $sql );
	dbDelta( $sql2 );
	dbDelta( $sql3 );

// add/edit database version stuff
	add_option( 'YCal_db_version', $YCal_db_version );
	update_option( "YCal_db_version", $YCal_db_version );

// Create Scheduled Event to parse calendar
	y_cal_refreshCRON();

}

// Refreshes the CROM job which updates the database
function y_cal_refreshCRON() {

	$options = get_option('YCal_Options');

// schedule to get events
	if ( wp_next_scheduled( 'parse_schedule' ) ) {
		wp_clear_scheduled_hook( 'parse_schedule' );
	}
	wp_schedule_event( time(), $options['refresh_frequency'], 'parse_schedule' );

// schedule to get categories
	if ( wp_next_scheduled( 'parse_cat_schedule' ) ) {
		wp_clear_scheduled_hook( 'parse_cat_schedule' );
	}
	wp_schedule_event( time(), "daily", 'parse_cat_schedule');

}

// Get categories from BYU
function y_cal_loadCategories(){
	global $wpdb;

	$options = get_option('YCal_Options');
	$table = $options["table_name_categories"];

	$context  = stream_context_create(array('http' => array('header' => 'Accept: application/xml')));
	// URL to import from
	$url = "http://calendar.byu.edu/api/AllCategories";

	$xml = file_get_contents($url, false, $context);
	$xml = simplexml_load_string($xml);

	// Remove all current info
	$wpdb->query ('DELETE FROM ' . $table);

	// load everything into array to upload to wordpress
	foreach($xml->item as $category)
	{
		$current = array(
			'CategoryId' => $category->CategoryId,
			'CategoryType' => $category->CategoryType,
			'CategoryTypeId' => $category->CategoryTypeId,
			'FullUrl' => $category->FullUrl,
			'Name' => $category->Name,
			'ParentCategoryId' => $category->ParentCategoryId,
			'doNotDisplayOnHomePage' => (int) filter_var($category->doNotDisplayOnHomePage, FILTER_VALIDATE_BOOLEAN)
		);

		$wpdb->insert($table,$current);
	}


}

// This loads the all the events
function y_cal_loadXML() {
	global $wpdb;
	global $table_name;

	$table_name = $wpdb->prefix . $table_name;

	$options = get_option('YCal_Options');

		$context  = stream_context_create(array('http' => array('header' => 'Accept: application/xml')));

		// have to do two queries so we get the right category for the child events. (ex: Geology shows up under CPMS otherwise)
		$url1 = $options["byu_url"] . 'categories=274' . '&event[min][date]=' . date('Y-m-d') . '&event[max][date]=' . date("Y-m-d", mktime(0, 0, 0, date("m")+$options["getMonths"],   date("d"),   date("Y")+$options["getYears"]));
		$url2 = $options["byu_url"] . 'categories=all'. '&event[min][date]=' . date('Y-m-d') . '&event[max][date]=' . date("Y-m-d", mktime(0, 0, 0, date("m")+$options["getMonths"],   date("d"),   date("Y")+$options["getYears"]));



		$xml1 = file_get_contents($url1, false, $context);
		$xml1 = simplexml_load_string($xml1);

		$xml2 = file_get_contents($url2, false, $context);
		$xml2 = simplexml_load_string($xml2);

		$events = array();

		$wpdb->query ('DELETE FROM ' . $table_name);

		for ($i = 0; $i < 2; $i++) {
			$xml = "";
			// first use url1
			// then use url2
			// do this once each (twice throught the for loop)
			switch ($i) {
				case 0:
					$xml = $xml1;
					break;
				case 1:
					$xml = $xml2;
					break;
				default:
					$xml = $xml1;
			}
			// load each event into an array and add to database
			foreach($xml->item as $event)
			{
				$current = array(
					'AllDay' => (int) filter_var($event->AllDay, FILTER_VALIDATE_BOOLEAN),
					'CategoryId' => $event->CategoryId,
					'CategoryName' => $event->CategoryName,
					'Description' => $event->Description,
					'DeptIds' => $event->DeptIds,
					'DeptNames' => $event->DeptNames,
					'EndDateTime' => $event->EndDateTime,
					'EventId' => $event->EventId,
					'FullUrl' => $event->FullUrl,
					'HighPrice' => $event->HighPrice,
					'ImgAlt' => $event->ImgAlt,
					'ImgUrl' => $event->ImgUrl,
					'IsFeatured' => (int) filter_var($event->IsFeatured, FILTER_VALIDATE_BOOLEAN),
					'IsFree' => (int) filter_var($event->IsFree, FILTER_VALIDATE_BOOLEAN),
					'IsPublishedNotMainCalendar' => (int) filter_var($event->IsPublishedNotMainCalendar, FILTER_VALIDATE_BOOLEAN),
					'Latitude' => $event->Latitude,
					'LocationName' => $event->LocationName,
					'Longitude' => $event->Longitude,
					'LowPrice' => $event->LowPrice,
					'MoreInformationUrl' => $event->MoreInformationUrl,
					'OccurrenceId' => $event->OccurrenceId,
					'PriceDescription' => $event->PriceDescription,
					'ShortDescription' => $event->ShortDescription,
					'StartDateTime' => $event->StartDateTime,
					'TagsIds' => $event->TagsIds,
					'TagsNames' => $event->TagsNames,
					'Title' => $event->Title
				);
        
			// Get category name and add it to database
				if ($current['CategoryName']=="") {
					$current['CategoryName'] = $wpdb->get_var('SELECT name FROM '.$options['table_name_categories']. ' WHERE CategoryId='. $current['CategoryId']);
				}

			// Check if already in databases
				if (is_null($wpdb->get_var("SELECT OccurrenceId FROM ".$options['table_name']." WHERE OccurrenceId = ".$current['OccurrenceId']))){
					// insert into database
					$wpdb->insert($table_name,$current);
				}

			}
		}

		// update time last updated
		$options["last_updated"] = time();

		update_option("YCal_Options", $options);

		y_cal_manage_event_table();
}

function y_cal_manage_event_table(){
	global $wpdb;

	$options = get_option('YCal_Options');

// Remove past events
	$strSQL =  "DELETE FROM " . $options["table_name_events"] . "
				WHERE EventId NOT IN (SELECT EventId FROM " . $options["table_name"] . ")";
		$wpdb->query($strSQL);
// Add new events to custom table
	$strSQL =  "INSERT IGNORE INTO " . $options["table_name_events"] . " (EventId, CategoryId, DeptIds, dateAdded, pending, showOnWidget, showOnCalendar)
				SELECT EventId, CategoryId, DeptIds, NOW(), 0 AS pending , 1 AS showOnWidget , 1 AS showOnCalendar FROM " . $options["table_name"];
		$wpdb->query($strSQL);
// Auto not approved for widget
	if ($options["auto_not_showWidget"] != "") {
		$strSQL =  "UPDATE " . $options["table_name_events"] . " SET showOnWidget=0
			   WHERE DeptIds IN (" . $options["auto_not_showWidget"] . ")
			   	AND dateAdded > NOW() - INTERVAL 2 MINUTE";
		$wpdb->query($strSQL);
	}
// Auto not approved for calendar
	if ($options["auto_not_showCalendar"] != "") {
		$strSQL =  "UPDATE " . $options["table_name_events"] . " SET showOnCalendar=0
				   WHERE DeptIds IN (" . $options["auto_not_showCalendar"] . ")
			   		AND dateAdded > NOW() - INTERVAL 2 MINUTE";
		$wpdb->query($strSQL);
	}
// Auto not approved
	if ($options["auto_not_approve"] != "") {
		$strSQL =  "UPDATE " . $options["table_name_events"] . " SET pending=1
				   WHERE DeptIds IN (" . $options["auto_not_approve"] . ")
			   		AND dateAdded > NOW() - INTERVAL 2 MINUTE";
		$wpdb->query($strSQL);
	}

// Check for new pending
	$strSQL =  "SELECT EventId  FROM " . $options["table_name_events"] . "
				WHERE pending = 1
			   	AND dateAdded > NOW() - INTERVAL 2 MINUTE";
	$results = $wpdb->get_results($strSQL,'ARRAY_A');
	$new_pending = $wpdb->num_rows;
// Email if pending
	if($new_pending) {
		$to = $options['pending_email'];
		$subject = "YCal: Pending Events";
		$message = "There " . ($new_pending == 1 ? 'is' : 'are') . " " . $new_pending . " new pending " . ($new_pending == 1 ? 'event' : 'events') . " to approve on the CPMS website:<br><br>";

		// foreach ($results as $pending) {
		// 	$message .= "<li>" . $results["Title"] . " | " . $results["CategoryName"] . " | " .$results["StartDateTime"] . "</li>";
		// }

		$headers = array('Content-Type: text/html; charset=UTF-8');
		wp_mail( $to, $subject, $message, $headers);
	}

}

// function to update database.
function y_cal_update_db_check() {
    global $YCal_db_version;
    if ( get_site_option( 'YCal_db_version' ) != $YCal_db_version ) {
        y_cal_install();
    }
}

// With plugin is disabled
function y_cal_pluginUninstall() {

     global $wpdb;
     $options = get_option('YCal_Options');
// Drop tables
	 $wpdb->query("DROP TABLE IF EXISTS " . $options['table_name']);
	 $wpdb->query("DROP TABLE IF EXISTS " . $options["table_name_categories"]);
//	 $wpdb->query("DROP TABLE IF EXISTS " . $options["table_name_events"]);
// Last line is commented out to hold events and reduce cost of reactivation.

// Clear Scheduled Event to parse calendar & categories
	 wp_clear_scheduled_hook( 'parse_schedule' );
	 wp_clear_scheduled_hook( 'parse_cat_schedule' );
// Delete all options stuff
	delete_option('YCal_Options');
}

// function to add stylesheet (style.css)
function y_cal_prefix_add_my_stylesheet() {
    // Respects SSL, Style.css is relative to the current file
    wp_register_style( 'prefix-style', plugins_url('style.css', __FILE__) );
    wp_enqueue_style( 'prefix-style' );

}


function y_cal_install_options($option_name, $should_be){
	if( !get_option( $option_name ) ) {
		add_option($option_name, $should_be);
	} else {
		$current = get_option($option_name);

		foreach ($should_be as $key => $value) {
			if (array_key_exists($key,$current)) {
				$should_be[$key] = $current[$key];
			}
		}

		update_option($option_name, $should_be);
	}
}

// gets the default image if no image exists
// returns byu's default image if nothing is specified
function get_default_image($id) {
	$options = get_option('YCal_Options');
	if(isset($options["img_defaults"]["$id"])) {
		return $options["img_defaults"]["$id"];
	}
	return $options["img_defaults"]["byu"];
}


function getOccurances($array) {
	$string = "";
	for ($i = 0; $i < count($array); ++$i) {
        $string .= $array[$i]['OccurrenceId'];
        if($i < count($array)-1) {
        	$string .= ",";
        }
    }

    if($string=="")
    	$string="0";

	return $string;
}

// build result array for event widget
function widget_get_data($a) {

	global $wpdb;
	global $widget_last_date;
	$options = get_option('YCal_Options');

	// keeps track of the day. This way we only show the date once per set of events a day
	$widget_last_date = '';

	$results = array();

// GET FORCED PRIOITY

		$strSQL = "SELECT * FROM " . $options['table_name'] ;
		$strSQL .= " LEFT JOIN " . $options['table_name_events'] . " ON " . $options['table_name'] .".EventID = " . $options['table_name_events'] . ".EventId ";
		$strSQL .= " WHERE forcePromote <= NOW() AND forcePromote != CONVERT(0,DATETIME)";
		$strSQL .= " AND DATE(StartDateTime) >= CURDATE()";
		$strSQL .= " AND showOnWidget = 1 ";
		$strSQL .= " ORDER BY  StartDateTime ASC ";
		$strSQL .= " LIMIT " . ($a['events']);

		$results = $wpdb->get_results($strSQL,'ARRAY_A');



// GET FIRST PRIORITY
	if($a['id1'] !== '') {

		$strSQL = "SELECT * FROM " . $options['table_name'] ;
		$strSQL .= " LEFT JOIN " . $options['table_name_events'] . " ON " . $options['table_name'] .".EventID = " . $options['table_name_events'] . ".EventId ";
		$strSQL .= " WHERE " . $options['table_name'] . ".DeptIds IN (". $a['id1'] .")";
		$strSQL .= " AND DATE(StartDateTime) >= CURDATE()";
		$strSQL .= " AND StartDateTime < NOW() + INTERVAL ". $options['widget_priority1_days'] ." DAY";
		$strSQL .= " AND showOnWidget = 1 ";
		$strSQL .= " AND OccurrenceId NOT IN (".getOccurances($results).")";
		$strSQL .= " ORDER BY  StartDateTime ASC ";
		$strSQL .= " LIMIT " . ($a['events'] - sizeof($results));

		$results1 = $wpdb->get_results($strSQL,'ARRAY_A');


		$results = array_merge($results, $results1);


	}

// GET SECOND PRIORITY
	if ($a['id2'] !== '' && ($a['events'] - sizeof($results)) > 0) {

		$strSQL = "SELECT * FROM " . $options['table_name'] ;
		$strSQL .= " LEFT JOIN " . $options['table_name_events'] . " ON " . $options['table_name'] . ".EventID = " . $options['table_name_events'] . ".EventId ";
		$strSQL .= " WHERE " . $options['table_name'] . ".DeptIds IN (" . $a['id2'] . ")";
		$strSQL .= " AND DATE(StartDateTime) >= CURDATE()";
		$strSQL .= " AND StartDateTime < NOW() + INTERVAL ". $options['widget_priority2_days'] ." DAY";
		$strSQL .= " AND showOnWidget = 1 ";
		$strSQL .= " AND OccurrenceId NOT IN (".getOccurances($results).")";
		$strSQL .= " ORDER BY  StartDateTime ASC ";
		$strSQL .= " LIMIT " . ($a['events'] - sizeof($results));

		$results2 = $wpdb->get_results($strSQL, 'ARRAY_A');

		$results = array_merge($results, $results2);
	}

// GET THIRD PRIORITY (BYU GENERAL)
	if (($a['events'] - sizeof($results)) > 0) {
		$strSQL = "SELECT * FROM " . $options['table_name'] ;
		$strSQL .= " LEFT JOIN " . $options['table_name_events'] . " ON " . $options['table_name'] .".EventID = " . $options['table_name_events'] . ".EventId ";
		$strSQL .= " WHERE showOnWidget = 1 ";

			if ($a['id1'] !== '') {
				$strSQL .= " AND " . $options['table_name'] . ".DeptIds NOT IN (". $a['id1'];
				if($a['id2'] !== '') {
					$strSQL .= "," . $a['id2'];
				}
				$strSQL .= ") ";
			} else if ($a['id1'] == '' && $a['id2'] !== ''){
				$strSQL .= " AND " . $options['table_name'] . ".DeptIds NOT IN (". $a['id2'] .") ";
			}

			if ($a['id1'] !== '' || $a['id2'] !== '') {
				$strSQL .= " AND ";
				if (!$options["widget_showUnfeatured"]) {
					$strSQL .= " IsFeatured = 1 ";
				}
				if (!$options["widget_showUnMainCalendar"]) {
					if (!$options["widget_showUnfeatured"]) {
						$strSQL .= " AND ";
					}
					$strSQL .= " IsPublishedNotMainCalendar = 1 ";
				}
			} else if (!$options["widget_showUnfeatured"] || !$options["widget_showUnMainCalendar"]) {
				$strSQL .= " AND ";
				if (!$options["widget_showUnfeatured"]) {
					$strSQL .= " IsFeatured = 1 ";
				}
				if (!$options["widget_showUnMainCalendar"]) {
					if (!$options["widget_showUnfeatured"]) {
						$strSQL .= " AND ";
					}
					$strSQL .= " IsPublishedNotMainCalendar = 1 ";
				}
			}
		$strSQL .= " AND OccurrenceId NOT IN (".getOccurances($results).")";
		$strSQL .= " ORDER BY  StartDateTime ASC ";
		$strSQL .= " LIMIT " . ($a['events'] - sizeof($results));
		$results3 = $wpdb->get_results($strSQL,'ARRAY_A');

		$results = array_merge($results,$results3);
	}

// sort result this way...
	function result_sort ($a, $b ) {
		if($a['StartDateTime'] == $b['StartDateTime']) {
			return 0;
		}
		return ($a['StartDateTime'] < $b['StartDateTime']) ? -1 : 1;
	}

// sort the results
	usort($results,"result_sort");

// return results
	return $results;

}

function add_plugin_caps() {
    // gets the author role
    $role = get_role( 'administrator' );

    $role->add_cap( 'edit_calendar' );
}
add_action( 'admin_init', 'add_plugin_caps');

// add stylesheet
add_action( 'wp_enqueue_scripts', 'y_cal_prefix_add_my_stylesheet' );
// add CRON events download schedule
add_action( 'parse_schedule', 'y_cal_loadXML' );
// add CRON categories download schedule
add_action( 'parse_cat_schedule', 'y_cal_loadCategories' );
// add db check if needs to update
add_action( 'plugins_loaded', 'y_cal_update_db_check' );
// install plugin stuff
register_activation_hook( __FILE__, 'y_cal_install' );
// uninstall plugin stuff
register_deactivation_hook( __FILE__, 'y_cal_pluginUninstall' );

// get admin menu stuff (admin menu can grab above functions)
require_once dirname( __FILE__ ) .'/admin-menu.php';
