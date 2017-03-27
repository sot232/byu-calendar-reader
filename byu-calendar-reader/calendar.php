<?php

$calendar_last_date = '';

function y_cal_build_calendar( $atts ) {

// Options for plugin
	$options = get_option('YCal_Options');

// shortcode attributes
	$a = shortcode_atts( array(
		'limit' => $options['calendar_howMany'],
		'id1' => $options['calendar_categories1'],
		'id2' => $options['calendar_categories2']
	), $atts );

// sanitize input (BE SURE TO GRAB HERE, NOT FROM GET ARRAY)
	$occurrenceID = sanitize_text_field( $_GET["event"] );
	$categoryID = sanitize_text_field( $_GET["category"] );
	$showByuID = sanitize_text_field( $_GET["show_byu"] );
	if ($showByuID != "true") {
		unset($_GET["show_byu"]);
		unset($showByuID);
	}

// build according to filter settings
	if (isset($_GET['event'])) {
		return y_cal_build_event($occurrenceID);
	} else if (isset($_GET["category"]) && isset($_GET["show_byu"])) {
		return y_cal_build_cal($categoryID, true);
	} else if (isset($_GET["category"])) {
		return y_cal_build_cal($categoryID);
	} else if (isset($_GET["show_byu"])) {
		return y_cal_build_cal("", true);
	} else {
		return y_cal_build_cal();
	}
}

// Get Event(s) Data (filter by occurrence ID)
function getEventData($occurrence_id) {
	global $wpdb;
	$options = get_option('YCal_Options');

	$strSQL = "SELECT * FROM " . $options['table_name'];
	$strSQL .= " WHERE OccurrenceId IN (" . $occurrence_id . ")";
	$strSQL .= " ORDER BY StartDateTime ASC ";

	$results = $wpdb->get_results($strSQL,'ARRAY_A');

	return $results;
}

// Get Calendar Data (filter by category ID)
function getCalendarData($category_filter = "", $show_byu = false) {
	global $wpdb;
	$options = get_option('YCal_Options');

	$strSQL = "SELECT * FROM " . $options['table_name'];
	$strSQL .= " LEFT JOIN " . $options['table_name_events'];
	$strSQL .= " ON " . $options['table_name'] . ".EventId=" . $options['table_name_events'] . ".EventId";
	$strSQL .= " WHERE " . $options['table_name_events'] . ".showOnCalendar=1";

	if ($category_filter != "") {
		$strSQL .= " AND " . $options['table_name'] . ".DeptIds IN (" . $category_filter . ")";
		if ($show_byu) {
			$strSQL .= " OR " . $options['table_name'] . ".DeptIds<>('288,275,276,277,278,279,280,281')";
		}
	} else if ($show_byu) {
		$strSQL .= " AND " . $options['table_name'] . ".DeptIds<>('288,275,276,277,278,279,280,281')";
	}
   
	$strSQL .= " ORDER BY " . $options['table_name'] . ".StartDateTime ASC ";

	$results = $wpdb->get_results($strSQL,'ARRAY_A');


	//echo($strSQL);
	return $results;
}

// build calendar
function y_cal_build_cal($category_filter = "", $show_byu = false) {
	$page_link = get_page_link();
	$data = getCalendarData($category_filter, $show_byu);
	$builder = "";
	$last_date = 0;

	$options = get_option('YCal_Options');	

	$builder .= '<link href="http://' . $_SERVER['HTTP_HOST'] . '/wp-content/plugins/byu-calendar-reader/byu-cal-ui.css" rel="stylesheet" />';
	$builder .= '<style>
		.ui-datepicker-inline {
			width: 100%;
		}
		.ui-datepicker-calendar {
			line-height: 1;
			border: 1px solid #ccc !important;
		}
		.ui-datepicker th {
			padding: .7em .3em !important;
		}
		.ui-datepicker-calendar td {
			border: 0 solid #eee !important;
			padding: 1px !important;
		}
		.ui-datepicker-calendar tbody a {
			font-size: 1.1em;
			padding-top: 5px !important;
			padding-bottom: 5px !important;
		}
		#dayControls {
			margin-bottom: 25px;
			width: 100%;
			text-align: center;
		}
		#dayControls a {
			padding: 3%;
			font-weight: bold;
			text-transform: uppercase;
		}
		.activeSpan {
			color: #000;
		}
		#today {
			color: #fff;
			display: block;
			background: #628cb6;
			background-image: -webkit-gradient(linear, 50% 0%, 50% 100%, color-stop(0%, #628cb6), color-stop(100%, #336699));
			background-image: -webkit-linear-gradient(top, #628cb6 0%, #336699 100%);
			background-image: -moz-linear-gradient(top, #628cb6 0%, #336699 100%);
			background-image: -o-linear-gradient(top, #628cb6 0%, #336699 100%);
			background-image: -ms-linear-gradient(top, #628cb6 0%, #336699 100%);
			background-image: linear-gradient(top, #628cb6 0%, #336699 100%);
			border-radius: 16px;
			box-shadow:
				0 1px 0 rgba(255, 255, 255, 0.25) inset,
				0 1px 8px rgba(0, 0, 0, 0.2);
			padding: 5px 28px;
			margin: -10px auto 30px auto;
			text-decoration: none;
			font-weight: bold;
			text-transform: uppercase;
			width: 10em;
		}
	</style>';
	$builder .= '<script src="http://' . $_SERVER['HTTP_HOST'] . '/wp-content/plugins/byu-calendar-reader/jquery-ui.min.js"></script>';
	$builder .= '<script>
		var defaultType = 3; // index of span types
		var spanArray = [1, 3, 7, 31, 365]; // day, 3-day, week, month, year
		var currentSpan = spanArray[defaultType];
		var displayMonth = (new Date()).getMonth();
		var selectedOtherMonth = false; // Whether or not the last date pressed was outside the current month


		/* Changes the events that are visible based on the selected date range from the calendar. */
		function filter_dates() {
			var start_day = jQuery("#datepicker .ui-datepicker-current-day:first");
			var start_date = new Date(
				(parseInt(start_day.attr("data-month")) + 1).toString() + "-" +
				start_day.children().text() + "-" +
				start_day.attr("data-year")
			);
			var end_date = new Date(start_date);
			end_date.setDate(end_date.getDate() + currentSpan - 1);

			jQuery(".Ycal-calendar-event").show();
			jQuery(".Ycal-calendar-event").each(function() {
				var date_text = jQuery(this).children(".Ycal-calendar-event-date").attr("data-date");
				var date = new Date(date_text);
				if (date < start_date || date > end_date) {
					jQuery(this).hide();
				}
			});
		}


		/*
			Selects the correct range of dates starting from the currently selected date. The date
			range is specified in currentSpan.
		*/
		function correct_range() {
			var allDates = jQuery("#datepicker").find("table.ui-datepicker-calendar td"); // get all date elements
			
			// td element of current date
			var selectedDate = jQuery(".ui-state-active:first").parent();
			jQuery(".ui-datepicker-current-day")
						.removeClass("ui-datepicker-current-day").children().removeClass("ui-state-active");

			// index of current date within all of the dates
			var index = allDates.index(selectedDate);
			allDates.slice(index, index + currentSpan)
				.addClass("ui-datepicker-current-day")
				.find("a").addClass("ui-state-active");
			filter_dates();
		}


		jQuery(document).ready(function() {
			// Setup the datepicker
			var $date = jQuery("#datepicker").datepicker({
				altField: "#date-value",
				changeMonth: true,
				changeYear: true,
				dayNamesMin: [ "S", "M", "T", "W", "Th", "F", "Sa" ],
				inline: true,
				minDate: 0,
				monthNamesShort: jQuery.datepicker.regional["en"].monthNames,
				onSelect: function(dateText, inst) {
					var picker = inst.dpDiv;
					var allDates = picker.find("table.ui-datepicker-calendar td"); // get all date elements

					//console.log(inst,current_date);
					if (displayMonth != jQuery("#datepicker").datepicker("getDate").getMonth()) {
						// Don\'t change the display month here
						console.log("clicking different month!");
						selectedOtherMonth = true;
					} else {
						displayMonth = jQuery("#datepicker").datepicker("getDate").getMonth();
						selectedOtherMonth = false;
					}

					// This is the important line.
					// Setting this to false prevents the redraw.
					inst.inline = false;

					// The remainder of the function simply preserves the 
					// highlighting functionality without completely redrawing.

					// This removes any existing selection styling.
					picker.find(".ui-datepicker-current-day")
						.removeClass("ui-datepicker-current-day").children().removeClass("ui-state-active");

					// This finds the selected link and styles it accordingly.
					// You can probably change the selectors, depending on your layout.
					picker.find("a").each(function() {
						var correctMonth = !jQuery(this).hasClass("ui-priority-secondary");
						if (selectedOtherMonth) {
							correctMonth = jQuery(this).hasClass("ui-priority-secondary");
						}

						if (jQuery(this).text() == inst.selectedDay && correctMonth) {
							// Remove current selected date styles
							picker.find(".ui-datepicker-current-day")
								.removeClass("ui-datepicker-current-day")
								.children()
								.removeClass("ui-state-active");

							// td element of current date
							var selectedDate = jQuery(this).parent();

							// index of current date within all of the dates
							var index = allDates.index(selectedDate);
							allDates.slice(index, index + currentSpan)
								.addClass("ui-datepicker-current-day")
								.find("a").addClass("ui-state-active");
						}
					});

					filter_dates();
				},
				onChangeMonthYear: function(year, month, inst) {
					jQuery("#datepicker").datepicker("setDate", month + "/1/" + year);
					displayMonth = jQuery("#datepicker").datepicker("getDate").getMonth();
					setTimeout(correct_range, 100); // We have to wait until it switches months
				},
				showOtherMonths: true,
				selectOtherMonths: true
			});


			jQuery("#dayControls a").click(function() {
				jQuery(".activeSpan").removeClass("activeSpan");
				jQuery(this).addClass("activeSpan");
				currentSpan = spanArray[ jQuery(this).index() ];
				correct_range();
			});


			jQuery("#today").click(function() {
				var today = new Date();
				jQuery("#datepicker").datepicker("setDate", today);
				correct_range();
			});

			
			// Set the default date range
			jQuery("#dayControls").children()[defaultType].className = "activeSpan";
			correct_range();
		});
	</script>';

	$builder .= "<div class='Ycal-calendar'>";
	
	$builder .= "<div class='Ycal-calendar-col'>";
	for ($x = 0; $x < sizeof($data); $x++) {

		$event = $data[$x];
		$start = new DateTime($event['StartDateTime']);
		$end = new DateTime($event['EndDateTime']);

		if($event['Description']=="" && $event['MoreInformationUrl'] != "") {
			$calendarURL = $event['MoreInformationUrl'];
		} else {
			$calendarURL = $options['calendar_url'] . "?event=" . $event['OccurrenceId'];
		}

// Event Div
// Date Div
	$builder .= "<div class='Ycal-calendar-event' id='Ycal-calendar-event-" . $x . "'>
					<div class='Ycal-calendar-event-date sticky hidden' id='the-sticky-div' data-date='" . $start->format('m-d-y') . "'>";
						if ($last_date !== $start->format('M d')) {
							$last_date = $start->format('M d');
							$builder .= "<span class='Ycal-calendar-date'>" . $start->format('l, j F') . "</span>";
						}	
	$builder .=		"</div>
					<div>";
// Image Div
						if ($event['ImgUrl'] !="") {
	$builder .=				"<div class='Ycal-calendar-imageDiv'>
							<a href='" . $calendarURL . "' class='Ycal-calendar-title'>
							<img src='" . $event['ImgUrl'] . "' alt='" . $event['ImgAlt'] . "' class='Ycal-calendar-image' style='width:" . $options['calendar_imageSize'] . ";'></a>
							</div>";						
						} else {
	$builder .=				"<div class='Ycal-calendar-imageDiv'>
							<a href='" . $calendarURL . "' class='Ycal-calendar-title'><img src='" .get_default_image($event['CategoryId']) . "' alt='" . $event['ImgAlt'] . "' class='Ycal-calendar-image' style='width:" . $options['calendar_imageSize'] . ";'></a>
							</div>";						
						}
// Text Div
// Title
// Category
// Time
	$builder .=		"<div class='Ycal-calendar-event-text'>
						<h3><a href='" . $calendarURL . "' class='Ycal-calendar-title'>" . $event['Title'] . "</a></h3>
						<strong class='Ycal-calendar-event-timestamp'>";	
							if ($event['AllDay']) {
								$builder .= $options['all_day_text'];
							} else {
								$builder .= $start->format('g:i A');

								if ($event['EndDateTime'] != 0) {
									$builder .= " - " . $end->format('g:i A');
								}
									$builder .= " " . $event['TimeZone'];
							}
// Location
	$builder .= 			"</strong>
						<p class='Ycal-calendar-event-location'>";
							if ($event['Latitude'] != 0) {
								$builder .= " <a href='http://maps.google.com?q=" . $event['Latitude'] . "," . $event['Longitude'] . "&amp;z=18' target='_blank'>" . $event['LocationName'] . "</a>";
							} else if ($event['LocationName'] != '') {
								$builder .= $event['LocationName'];
							}						
// Info
	// $builder .=			"<p class='Ycal-calendar-event-details'>";
	// 						$str = "";
	// 						if (strlen($event['Description']) > $options['calendar_MaxDescriptionLength']) {
	// 							$str = substr($event['Description'], 0, $options['calendar_MaxDescriptionLength']) . '...';
	// 						} else {
	// 							$str = $event['Description'];
	// 						}
	// 						$str .= " <a href='" . $page_link . "?event=" . $event["OccurrenceId"] . "'>[Read More]</a>"; 
 //   	$builder .= 			$str . "</p>";
// // More Info
// 							if ($event['MoreInformationUrl'] != "") {
// 								$builder .=  "<p><a class='Ycal-calendar-event-MoreURL' href='" . $event['MoreInformationUrl'] . "'>More Information</a></p>";
// 							}


							

	$builder .= 		"</p></div>			   
					</div>
				</div>";

	}
									 
	$builder .= "</div></div>";




	return $builder;
}








// build event content
function y_cal_build_event($occurrence_id) {
	$page_link = get_page_link();
	$data = getEventData($occurrence_id);
	$builder = "";

	$options = get_option('YCal_Options');	

	$builder .=   "<div class='Ycal-calendar'>";
	
	$builder .= 		"<div class='Ycal-calendar-col'>";
	for ($x = 0; $x < sizeof($data); $x++) {

		$event = $data[$x];
		$start = new DateTime($event['StartDateTime']);
		$end = new DateTime($event['EndDateTime']);
// Event Div
// Date Div
	$builder .= "<div class='Ycal-calendar-event' id='Ycal-calendar-event-" . $x . "'>";
	// 				<div class='Ycal-calendar-event-date'>";
	// 						$builder .= "<span class='Ycal-calendar-month'>" . $start->format('M') . "</span>";
	// 						$builder .= "<span class='Ycal-calendar-day'>" . $start->format('d') . "</span>";
	// $builder .=		"</div>";
// Image Div
						if ($event['ImgUrl'] !="") {
	$builder .=				"<div class='Ycal-calendar-imageDiv'><img src='" . $event['ImgUrl'] . "' alt='" . $event['ImgAlt'] . "' class='Ycal-calendar-image'></div>";						
						} else {
	$builder .=				"<div class='Ycal-calendar-imageDiv'><img src='" . get_default_image($event['CategoryId']) . "' alt='" . $event['ImgAlt'] . "' class='Ycal-calendar-image'></div>";						
						}
// Image
// Text Div
// Title
// Category
// Date
// Time
	$builder .=		"<div class='Ycal-calendar-event-text'>";
						if ($event['ImgUrl'] !="") {
	$builder .=				"<img src='"  . $event['ImgUrl'] . "' alt='" . $event['ImgAlt'] . "' class='Ycal-calendar-image'>";						
						} else {
	$builder .=				"<img src='" . get_default_image($event['CategoryId']) . "' alt='" . $event['ImgAlt'] . "' class='Ycal-calendar-image'>";						
						}

	$builder .=		"<h3>" . $event['Title'] . "</h3>
						<h6><a href='" . $page_link . "?category=" . $event['CategoryId'] . "'>" . $event['CategoryName'] . "</a></h6>
						<p class='Ycal-calendar-event-timestamp'><i class='fa fa-calendar fa-fw'></i>";
							$builder .=	"<span> " . $start->format('l, j F Y');
							if ($start->format('j f Y') != $end->format('j f Y') && $end->format('Y') != "-0001") {
								$builder .= " - " . $end->format('l, j F Y');
							}
	$builder .=		"</span>
						</p>
						<p class='Ycal-calendar-event-timestamp'><i class='fa fa-clock-o fa-fw'></i> ";	
							if ($event['AllDay']) {
								$builder .= $options['all_day_text'];
							} else {
								$builder .= $start->format('g:i A');

								if ($event['EndDateTime'] != 0) {
									$builder .= " - " . $end->format('g:i A');
								}
									$builder .= " " . $event['TimeZone'];
							}
// Location
	$builder .= 			"</p>
						<p class='Ycal-calendar-event-location'> ";
							if ($event['Latitude'] != 0) {
								$builder .= "<a href='http://maps.google.com?q=" . $event['Latitude'] . "," . $event['Longitude'] . "&amp;z=18' target='_blank'><i class='fa fa-map-marker fa-fw'></i> " . $event['LocationName'] . "</a>";
							} else if ($event['LocationName'] != '') {
								$builder .= "<i class='fa fa-map-marker fa-fw'></i> " . $event['LocationName'];
							}						
// Info
	$builder .=			"<p class='Ycal-calendar-event-details'>" . $event['Description'] . "</p>";
// More Info
							if ($event['MoreInformationUrl'] != "") {
								$builder .=  "<p><a class='Ycal-calendar-event-MoreURL' href='" . $event['MoreInformationUrl'] . "'>More Information</a></p>";
							}


							

	$builder .= 			"</p><br>
<a class='more-link' href='" . $options['calendar_url'] . "?category=" . $options["widget_categories1"] . "'> <i class='fa fa-chevron-circle-left'></i> <span>Full Calendar</span></a>				
					</div>
				</div>";

	}
									 
	$builder .= "</div></div>";
	return $builder;
}

function y_cal_build_category_filter() {
	if (!isset($_GET["event"])) {
		$options = get_option('YCal_Options');
		$getCat = $_GET["category"];
		$builder = "<div id='Ycal-calendar-filter'>
					<div id='datepicker'></div>
					<div id='dayControls'>
						<a href='#'>Day</a>
						<a href='#'>3 Days</a>
						<a href='#'>Week</a>
						<a href='#'>Month</a>
						<a href='#'>Year</a>
					</div>
					<input id='date-value' type='hidden' />
					<a href='#' id='today'>Today</a>
					<h2>Categories Filter</h2>
					<hr />";

					// Filter checkbox to show all departments
					/*$all_departments = "72,74,75,76,77,78,79";
					if ($getCat == $all_departments) {
						$builder .= " checked";
					}
					$builder .= ">The College<br />";*/

					/*unset($options["table_name_events"]);
					$options["table_name_events"] = $table_name . "_events";
					update_option('YCal_Options', $options);*/

					foreach ($options['calendar_filterCategories'] as $key => $value) {
						if (is_array($value)) {
							$builder .= "<input type='checkbox' class='categoryFilter filterParent' value='#'";

							// Check if all subcategories are in the url
							$all_checked = true;

							foreach ($value as $subkey => $subvalue) {
								if (strpos($getCat, $subvalue) === false) $all_checked = false;
							}

							if ($all_checked) {
					  			$builder .= " checked ";
					  		}
							$builder .= ">" . $key . "<br />";

							$builder .= "<div class='subitems' style='margin-left: 20px;'>";

							// Get subcategories
							foreach ($value as $subkey => $subvalue) {
								$builder .= "<input type='checkbox' class='categoryFilter filter'"
											. " value='" . $subvalue . "' onclick='filter()'";

								// If the value is found in the url
								if (strpos($getCat, $subvalue) !== false) {
						  			$builder .= " checked ";
						  		}

								$builder .= ">" . $subkey . "<br />";
							}

							$builder .= "</div>";
						} else {
							/*$newQ = "";
							if (isset($getCat)) {
								$newQ = $getCat . "," . $value;
							} else {
								$newQ = $value;
							}*/

							$builder .= "<input type='checkbox' class='categoryFilter filter'"
										. " value='" . $value . "' onclick='filter()'";

							// If the value is found in the url
							if (strpos($getCat, $value) !== false) {
					  			$builder .= " checked ";
					  		}

							$builder .= ">" . $key . "<br />";
						}
					}

					// Show everything on BYU's calendar filter checkbox
					$builder .= "<input type='checkbox' class='categoryFilter byu' value='#' onclick='filter()'";
						// if it's empty
						if (strpos($_GET["show_byu"], "true") !== false) {
					  		$builder .= " checked ";
					  	}
					$builder .= ">" . $options['calendar_filter_unfiltered_title'] . "<br />";
	  			 	
		$builder .= "</div>
					<script>
						/*
							Finds all the filters that are activated and refreshes the page with the proper
							events.
						*/
						function filter() {
							var newAddress = '';
							var inputElements = document.getElementsByClassName('filter');

							for (var i = 0; i < inputElements.length; i++) {
								if (inputElements[i].checked) newAddress += inputElements[i].value + ',';
							}
							newAddress = newAddress.substring(0, (newAddress.length - 1)); // remove the trailing comma

							if (jQuery('.byu')[0].checked) {
								if (newAddress == '') window.location = '" . get_page_link() . "?show_byu=true';
								else window.location = '" . get_page_link() . "?category=' + newAddress + '&show_byu=true';
							} else {
								if (newAddress == '') window.location = '" . get_page_link() . "';
								else window.location = '" . get_page_link() . "?category=' + newAddress;
							}
						}


						/*
							Toggles whether or not every suboption checkbox is checked.
						*/
						jQuery('.filterParent').click(function(e) {
							suboptions = jQuery(e.target).siblings('.subitems').children('input');
							all_checked = true;

							for (var i = 0; i < suboptions.length; i++) {
								if (!suboptions[i].checked) all_checked = false;
							}

							if (!all_checked) {
								// Check all
								for (var i = 0; i < suboptions.length; i++) {
									suboptions[i].checked = true;
								}
							}
							else {
								// Uncheck all
								for (var i = 0; i < suboptions.length; i++) {
									suboptions[i].checked = false;
								}
							}

							filter();
						});
					</script>";

		return $builder;
	} else return "";
}


// Add shortcodes here!
// ex: add_shortcode( 'SHORTCODE NAME', 'FUNCTION' );
// Function must return a string.
add_shortcode( 'YCal_calendar', 'y_cal_build_calendar' );
add_shortcode( 'YCal_calendar_filter', 'y_cal_build_category_filter' );

 ?>
