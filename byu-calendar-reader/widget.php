<?php

$widget_last_date ='';

function build_widget($atts) {

//Options for plugin
	$options = get_option('YCal_Options');

// shortcode attributes
	$a = shortcode_atts( array(
		'events' => $options['widget_howMany'],
		'id1' => $options['widget_categories1'],
		'id2' => $options['widget_categories2']
	), $atts );

	$data = widget_get_data($a);
	return build_container($data);

}

// see if any events are pending (mainly used to show for admin)
function check_if_pending() {
	global $wpdb;
	$options = get_option('YCal_Options');
	$wpdb->get_results('SELECT EventID FROM ' . $options['table_name_events'] . ' WHERE pending=1', OBJECT);
	return $wpdb->num_rows;
}

// build widget
function build_container($results) {
	$options = get_option('YCal_Options');
	global $widget_last_date;
// build the actual HTML for widget
	$builder = "<div class='Ycal-widget'>
					<div class='Ycal-widget-col";
						   if ($options['widget_2column']) {
						   	$builder .= "-1";
						   }
	$builder .=		"'>";
	
	$last_date = "0";
// for each event
	for($x = 0; $x < sizeof($results); $x++) {
		// if two column and half the amount
		if ($options['widget_2column'] && ($x == ceil(sizeof($results)/2))) {
			$builder .= "</div><div class='Ycal-widget-col-2'>";
		}
		// build the individual event
		$builder .= build_event($results[$x],$x);

		$last_date = $results[$x]['StartDateTime'];		
	}

	// The Full Calendar Link
	$builder .= "<div class='Ycal-widget-event'><div class='Ycal-widget-event-date'></div><a style='color:#002b5c;' class='more-link' href='" . $options['calendar_url'] . "?category=" . $options["widget_categories1"] . "'><i class='fa fa-chevron-circle-right'></i><span> Full Calendar</span></a>";				
	
	global $userdata;
	wp_get_current_user();
	// If user is admin
	if(is_user_logged_in() && $userdata->user_level > 9)
	{ 
		// show settings field
		$builder .= " | <a style='color: #002b5c;' href='". get_site_url() . "/wp-admin/admin.php?page=YCal-Settings'><span><i class='fa fa-cog'></i> Settings </span></a>";
		
		// if pending, show pending button
		$pending = check_if_pending();
		if ($pending) {
			$builder .= " | <a href='". get_site_url() . "/wp-admin/admin.php?page=YCal'><span><i class='fa fa-exclamation'></i> Pending (".$pending.") </span></a>";
		} else {
			$builder .= " | <a href='". get_site_url() . "/wp-admin/admin.php?page=YCal'><span><i class='fa fa-tasks'></i> Manage </span></a>";
		}
	}
	$builder .= "</div>
				</div>
				</div>";

	return $builder;
}

// builds the individual event
function build_event($event,$x) {
	global $widget_last_date;
	$options = get_option('YCal_Options');
	$start = new DateTime($event['StartDateTime']);
	$end = new DateTime($event['EndDateTime']);

	if($event['Description']=="" && $event['MoreInformationUrl'] != "") {
		$calendarURL = $event['MoreInformationUrl'];
	} else {
		$calendarURL = $options['calendar_url'] . "?event=" . $event['OccurrenceId'];
	}
	// Event Div
// Date Div
	$builder = "								 
				<div class='Ycal-widget-event' id='Ycal-widget-event-".$x."'>
					<div class='Ycal-widget-event-date'>";
						if ($widget_last_date != $start->format('M d')) {
							$widget_last_date = $start->format('M d');
							$builder .= "<span class='Ycal-widget-month'>".$start->format('M')."</span>";
							$builder .= "<span class='Ycal-widget-day'>".$start->format('d')."</span>";
						}						
	$builder .=		"</div>";
// Image Div
					if($options['widget_showImage']) {
						if ($event['ImgUrl'] !="") {
	$builder .=				"<div class='Ycal-widget-imageDiv'><a href='".$calendarURL."'><img src='".$event['ImgUrl']."' alt='".$event['ImgAlt']."' class='Ycal-widget-image' style='width:".$options['widget_imageSize'].";'></a></div>";						
						} else {
	$builder .=				"<div class='Ycal-widget-imageDiv'><a href='".$calendarURL."'><img src='".get_default_image($event['CategoryId'])."' alt='".$event['ImgAlt']."' class='Ycal-widget-image' style='width:".$options['widget_imageSize'].";'></a></div>";						
						}
	

					}
// Text Div
// Title
// Time
	$builder .=		"<div class='Ycal-widget-event-text'>
						<h4><a href='". $calendarURL ."' class='Ycal-widget-title'>".$event['Title']."</a></h4>
						<p class='Ycal-widget-event-timestamp'>";	
							if ($event['AllDay']) {
								$builder .= $options['all_day_text'];
							} else {
								$builder .= $start->format('g:i A');

								if ($event['EndDateTime'] != 0 && $options['widget_showEndTime']) {
									$builder .= " - " . $end->format('g:i A');
								}

								if($options['widget_showTimeZone'] || $event['TimeZone'] != $options['widget_homeTimeZone']) {
									$builder .= " " . $event['TimeZone'];
								}
								

							}
// Location
	$builder .= 			"</p>
						<p class='Ycal-widget-event-location'>";
						if($options['widget_showLocation']) {
							if ($event['Latitude'] != 0) {
								$builder .= " <a href='http://maps.google.com?q=" . $event['Latitude'] .",". $event['Longitude'] ."&amp;z=18' target='_blank'>".$event['LocationName']."</a>";
							} else if ($event['LocationName'] != '') {
								$builder .= $event['LocationName'];
							}						
						}

							

	$builder .= 			"</p>
					</div>
				</div>";						
			  

	return $builder;
}





// Add shortcodes here!
// ex: add_shortcode( 'SHORTCODE NAME', 'FUNCTION' );
// Function must return a string.
add_shortcode( 'YCal_widget', 'build_widget' );


 ?>