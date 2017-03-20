<?php
// Action to add admin menus
add_action( 'admin_menu', 'y_cal_menu_function' );

// Function to add the menu (called in the action above)
function y_cal_menu_function() {
	//Add YCalendar to main admin menu
	add_menu_page( "BYU Calendar Reader", "YCalendar", 'edit_calendar', 'YCal', 'y_cal_manage_events', "dashicons-calendar-alt");
	//Add Settings  submenu to YCalendar admin menu
	add_submenu_page( 'YCal','YCal', 'Manage Events', 'edit_calendar', 'YCal', 'y_cal_manage_events', "dashicons-admin-generic" );
	add_submenu_page( 'YCal','YCal Settings', 'Settings', 'manage_options', 'YCal-Settings', 'y_cal_settings', "dashicons-admin-generic" );

}

//This is the YCalendar menu
//Admin can manage the events (show/hide from calendar and widget)
//Settings for auto pending/showing options
function y_cal_manage_events() {
	//Check for editing permissions
	if (!current_user_can( 'edit_calendar')) {
		wp_die(__( 'You do not have permission to manage calendar events'));
	}

	$hidden_field = "hide_calendar";

	//Retrieve 'YCal_options' options from database
	$options = get_option('YCal_Options');

	global $wpdb;

	//See if the user has posted any information
	//If they did, this hidden field will be set to 'Y'
	if( isset($_POST[ $hidden_field]) && $_POST[ $hidden_field == 'Y']) {
		//Read posted values and update options
		foreach ($_POST as $key => $value){
			//This part goes through the post array and grabs the settings for the individual events.
        	if (strpos($key, 'showCalendarChk_') !== FALSE){
        		//showOnCalendar
    			$event = substr($key,16);
    			$wpdb->query("UPDATE " . $options['table_name_events'] . " SET showOnCalendar=".$value." WHERE EventId=" . $event);
        	} else if (strpos($key, 'showWidgetChk_') !== FALSE){
        		//showOnWidget
    			$event = substr($key,14);
    			$wpdb->query("UPDATE " . $options['table_name_events'] . " SET showOnWidget=".$value." WHERE EventId=" . $event);
        	} else if (strpos($key, 'pendingChk_') !== FALSE){
        		//pending
    			$event = substr($key,11);
    			$wpdb->query("UPDATE " . $options['table_name_events'] . " SET pending=".$value." WHERE EventId=" . $event);
        	} else if (strpos($key, 'forcePromote_') !== FALSE){
        		//forcePromote
        		$event = substr($key,13);
        		$wpdb->query("UPDATE " . $options['table_name_events'] . " SET forcePromote='".$value."' WHERE EventId=" . $event);
        	}
        }
    //Settings for auto/pending stuff
    $options['auto_not_showCalendar'] = $_POST["auto_not_showCalendar"];
    $options['auto_not_showWidget'] = $_POST["auto_not_showWidget"];
    $options['auto_not_approve'] = $_POST["auto_not_approve"];
    $options['pending_email'] = $_POST["pending_email"];
    $options['forcePromote'] = $_POST["forcePromate"];

    update_option( "YCal_Options", $options );

    ?>
    	<div class="updated"><p><strong><?php _e('Settings Saved.', 'menu-test' ); ?></strong></p></div>
    <?php
    
    	}


    ?>
    <h1> YCalendar </h1>
    <div>
    		<link rel="stylesheet" href="http://maxcdn.bootstrapcdn.com/font-awesome/4.3.0/css/font-awesome.min.css">
			<!-- DataTables CSS -->
			<link rel="stylesheet" type="text/css" href="http://cdn.datatables.net/1.10.5/css/jquery.dataTables.css">
			  
			<!-- jQuery -->
			<script type="text/javascript" charset="utf8" src="http://code.jquery.com/jquery-1.10.2.min.js"></script>
			  
			<!-- DataTables -->
			<script type="text/javascript" charset="utf8" src="http://cdn.datatables.net/1.10.5/js/jquery.dataTables.js"></script>
		<hr />

	<form method="post" action="">	
	<input type="hidden" name="<?php echo $hidden_field; ?>" value="Y">
<?php
	//Get pending events
	$options = get_option('YCal_Options');

	$a = array(
		'events' => $options['widget_howMany'],
		'id1' => $options['widget_categories1'],
		'id2' => $options['widget_categories2']
		);

	//get list of titles currently showing on widget
	$widget_data = widget_get_data($a);
	$showing_on_widget = array();
	foreach ($widget_data as $value) {		
		array_push($showing_on_widget,$value['Title']);
	}

	$results = $wpdb->get_results('SELECT * FROM '.$options['table_name_events'].'
						LEFT JOIN '.$options['table_name'].' ON '.$options['table_name_events'].'.EventId = '.$options['table_name'].'.EventId 
						WHERE pending=1', ARRAY_A);

	//If pending events, show on table
		if(count($results) > 0) {
?>
		<h3> Pending </h3>
			
				<table id="pending" class="order-column" cellspacing="0" width="100%">
			        <thead>
			            <tr>
			                <th>Event</th>
			                <th>Category</th>
			                <th>Date</th>
			                <th>Calendar</th>
			                <th>Homepage</th>
			                <th>Pending</th>
			            </tr>
			        </thead>
			 
			        <tfoot>
			            <tr>
			                <th>Event</th>
			                <th>Category</th>
			                <th>Date</th>
			                <th>Calendar</th>
			                <th>Homepage</th>
			                <th>Pending</th>
			            </tr>
			        </tfoot>
			 
			        <tbody>

			        	<?php 
			        		global $wpdb;
			        		$last_eventId = 0;
			        		$options = get_option('YCal_Options');
			        		foreach ($results as $row) { 
								if ($row['EventId'] == $last_eventId) continue;
								$last_eventId = $row['EventId'];
							?>
								<tr>
					                <td><a href='<?php echo $options['calendar_url'] . "?event=" .$row['OccurrenceId'] ?>' target="_blank"><?php echo $row['Title'] ?></a></td>
					                <td><?php echo $row['CategoryName'] ?></td>
					                <td><?php echo $row['StartDateTime'] ?></td>
					               	<td><?php echo "<input type='radio' name='showCalendarChk_".$row['EventId']."' value=1 onClick='radio_change(\"".$row['EventId']."\")' ". checked( $row['showOnCalendar'], 1, false ) .">Show | 
					                				<input type='radio' name='showCalendarChk_".$row['EventId']."' value=0 onClick='radio_change(\"".$row['EventId']."\")' ". checked( $row['showOnCalendar'], 0, false ) .">Hide" ?></td>
					                <td><?php echo "<input type='radio' name='showWidgetChk_".$row['EventId']."' value=1 onClick='radio_change(\"".$row['EventId']."\")' ". checked( $row['showOnWidget'], 1, false ) .">Show | 
					                				<input type='radio' name='showWidgetChk_".$row['EventId']."' value=0 onClick='radio_change(\"".$row['EventId']."\")' ". checked( $row['showOnWidget'], 0, false ) .">Hide" ?></td>
					                <td><?php echo "<input type='radio' name='pendingChk_".$row['EventId']."' value=1 ". checked( $row['pending'], 1, false ) .">Pending | 
					                				<input type='radio' name='pendingChk_".$row['EventId']."' value=0 ". checked( $row['pending'], 0, false ) .">Done" ?></td>
					            </tr>
							<?php }
			        	?>

			        </tbody>
			    </table>
			    <p class="submit">
					<input type="submit" name="Submit" class="button-primary" value="<?php esc_attr_e('Save Changes') ?>" />
				</p>
					<h4>Instructions/Tips:</h4>
					<p class="description">
						Calendar: This column enables you to either show or hide the event on the calendar page - <a href='http://cpms.byu.edu/calendar/'>http://cpms.byu.edu/calendar</a>
					</p>
					<p class="description">
						Homepage: This column enables you to either show or hide the event on the homepage widget. The eye icon identifies the events currently showing on the homepage widget. - <a href='http://cpms.byu.edu/'>http://cpms.byu.edu</a>
					</p>
					<p class="description">
						Pending: This column is used to manage pending events. After you have confirmed the proper settings for an event, you can set this column to done. Those events marked as done will no longer show on the Pending list (after Saving Changes).
					</p>
				
			
<?php } else { //end of if statement for if there are pending things ?>
		<h3> Pending </h3><p>No Pending Events...</p>
<?php } 
	//Now show all events not pending
?>
			<hr />

			<h3> All Events </h3>
				<table id="table-events" class="order-column" cellspacing="0" width="100%">
			        <thead>
			            <tr>
			                <th>Event</th>
			                <th>Category</th>
			                <th>Date</th>
			                <th>Calendar</th>
			                <th>Homepage</th>
			                <th>Force Promote</th>
			            </tr>
			        </thead>
			 
			        <tfoot>
			            <tr>
			                <th>Event</th>
			                <th>Category</th>
			                <th>Date</th>
			                <th>Calendar</th>
			                <th>Homepage</th>
			                <th>Force Promote</th>
			            </tr>
			        </tfoot>
			 
			        <tbody>

			        	<?php 
			        		//Get all events not pending
			        		$last_eventId = 0;
			        			$results = $wpdb->get_results('SELECT * FROM '.$options['table_name_events'].'
							LEFT JOIN '.$options['table_name'].' ON '.$options['table_name_events'].'.EventId = '.$options['table_name'].'.EventId 
							WHERE pending=0', ARRAY_A);
			        		//Show on table
							foreach ($results as $row) { 
								//if eventID is already on table, dont show it (removes duplicates)
								if ($row['EventId'] == $last_eventId) continue;
								$last_eventId = $row['EventId'];							
							?>
								<tr>
					                <td><a href='<?php echo $options['calendar_url'] . "?event=" .$row['OccurrenceId'] ?>' target="_blank"><?php echo $row['Title'] ?></a></td>
					                <td><?php echo $row['CategoryName'] ?></td>
					                <td><?php echo $row['StartDateTime'] ?></td>
					                <td><?php echo "<input type='radio' name='showCalendarChk_".$row['EventId']."' value=1 ". checked( $row['showOnCalendar'], 1, false ) .">Show | 
					                				<input type='radio' name='showCalendarChk_".$row['EventId']."' value=0 ". checked( $row['showOnCalendar'], 0, false ) .">Hide" ?></td>
					                <td><?php echo "<input type='radio' name='showWidgetChk_".$row['EventId']."' value=1 ". checked( $row['showOnWidget'], 1, false ) .">Show | 
					                				<input type='radio' name='showWidgetChk_".$row['EventId']."' value=0 ". checked( $row['showOnWidget'], 0, false ) .">Hide" ?> <?php echo (in_array($row['Title'], $showing_on_widget)) ? "<i class='fa fa-eye fa-fw'></i>" : "" ?></td>
					            	<td><?php echo "<input type='date' name='forcePromote_".$row['EventId']."' value='".$row['forcePromote']."'>"?></td>
					            </tr>
							<?php }
			        	?>

			        </tbody>
			    </table>
			    <p class="submit">
					<input type="submit" name="Submit" class="button-primary" value="<?php esc_attr_e('Save Changes') ?>" />
				</p>
		    	<h4>Instructions/Tips:</h4>
					<p class="description">
						Calendar: This column enables you to either show or hide the event on your calendar page
					</p>
					<p class="description">
						Homepage: This column enables you to either show or hide the event on the homepage widget. The eye icon identifies the events currently showing on the homepage widget.
					</p>
					<p class="description">
						Force Promote: This column enables you to forcefully show an event on the homepage widget. Set the field to the date you want the event to first show up on the homepage widget. If more forced promoted events are selected (for a given time period) than spots available on the widget, then it will prioritize according to date of event (not forced promote date). Events will not show if homepage widget show/hide setting is set to 'Hide'. 
					</p>


			<hr />
			<h3>Default Pending Settings</h3>
				<p>Does Not Show on Calendar by Default:
						<input type="text" name="auto_not_showCalendar" value="<?php echo $options["auto_not_showCalendar"]; ?>" size="40"><br />
					<p class="description">
						If by default you do not what a specific category to show up on the calendar (not talking about the homepage widget) then enter the category(s) here in comma dilimited format.
					</p>
					Does Not Show on Homepage Widget by Default:
						<input type="text" name="auto_not_showWidget" value="<?php echo $options["auto_not_showWidget"]; ?>" size="40"><br />
					<p class="description">
						If by default you do not what a specific category to show up on the homepage widget then enter the category(s) here in comma dilimited format.
					</p>
					Set to Pending by Default:
						<input type="text" name="auto_not_approve" value="<?php echo $options["auto_not_approve"]; ?>" size="40"><br />
					<p class="description">
						If you want to mark certin category events for review as they are loaded into the system enter the category(s) here in a comma dilimited format.
					</p>
					
				</p>
				<p class="submit">
					<input type="submit" name="Submit" class="button-primary" value="<?php esc_attr_e('Save Changes') ?>" />
				</p>

			    <script>    
			    // Loads tables (with sort order)
			    	$(document).ready(function() {
					    $('#pending').dataTable( {
					    	"order": [[2, "asc"]]
					    });
					    $('#table-events').dataTable( {
					    	"order": [[4,"dec"],[2, "asc"]]
					    });
					} );

					function radio_change(id) {
						$('[name="pendingChk_' + id + '"]').prop("checked", true);
						//document.getElementByName("pendingChk_" + id).checked = true;
					}
			    </script>
			</div>

		</form>
		
<?php
}

// function for main admin settings for YCalendar
function y_cal_settings() {
	//check for correct user
	if ( !current_user_can( 'manage_options' ) )  {
		wp_die( __( 'You do not have sufficient permissions to access this page.' ) );
	}
	    
	$hidden_field_name = "hide_me";

    // Read in existing option value from database
    $options = get_option('YCal_Options');

    // See if the user has posted us some information
    // If they did, this hidden field will be set to 'Y'
    if( isset($_POST[ $hidden_field_name ]) && $_POST[ $hidden_field_name ] == 'Y' ) {
        
        // Read their posted values and update options
        $options["widget_categories1"] = $_POST["widget_categories1"];
        $options["widget_categories2"] = $_POST["widget_categories2"];
        $options["widget_priority1_days"] = filter_var($_POST["widget_priority1_days"], FILTER_VALIDATE_INT);
        $options["widget_priority2_days"] = filter_var($_POST["widget_priority2_days"], FILTER_VALIDATE_INT);
        $options["widget_howMany"] = filter_var($_POST["widget_howMany"], FILTER_VALIDATE_INT);
		$options["widget_showTimeZone"] = isset($_POST["widget_showTimeZone"]);
		$options["widget_showEndTime"] = isset($_POST["widget_showEndTime"]);
		$options["widget_showLocation"] = isset($_POST["widget_showLocation"]);
		$options["widget_showUnfeatured"] = isset($_POST["widget_showUnfeatured"]);
		$options["widget_showUnMainCalendar"] = isset($_POST["widget_showUnMainCalendar"]);
		$options["widget_priority3"] = isset($_POST["widget_priority3"]);
		$options["widget_showImage"] = isset($_POST["widget_showImage"]);
		$options["widget_imageSize"] = $_POST["widget_imageSize"];
		$options["widget_homeTimeZone"] = $_POST["widget_homeTimeZone"];
		$options["byu_url"] = $_POST["byu_url"];
        $options["getYears"] = filter_var($_POST["getYears"], FILTER_VALIDATE_INT);
        $options["getMonths"] = filter_var($_POST["getMonths"], FILTER_VALIDATE_INT);
        $options["image_prefix"] = $_POST["image_prefix"];
        $options["all_day_text"] = $_POST["all_day_text"];
        $options["calendar_url"] = $_POST["calendar_url"];

		if ($_POST["frequency"] != $options["refresh_frequency"]) {
        	$options["refresh_frequency"] = $_POST["frequency"];
        	update_option( "YCal_Options", $options );
        	refreshCRON();
		}

        // Save the posted value in the database
        update_option( "YCal_Options", $options );

        // If needing to refresh everything
		if (isset($_POST['ForceRefresh'])) {
        	y_cal_loadXML();
        	y_cal_loadCategories();
        }
        

// Put an settings updated message on the screen
	?>
	<div class="updated"><p><strong><?php _e('Settings Saved.', 'menu-test' ); ?></strong></p></div>
	<?php

	    }

	    // Now display the settings editing screen

	    echo '<div class="wrap">';

	    // header

	    echo "<h2>" . __( 'BYU Calendar Reader Settings', 'menu-test' ) . "</h2>";

	    // settings form
	    
	    ?>

			<form name="form1" method="post" action="">
			<input type="hidden" name="<?php echo $hidden_field_name; ?>" value="Y">

			
				<h3>Importing Settings</h3>

				<p>Last Updated: <?php echo date(DATE_RFC2822, $options["last_updated"]); ?>
					| <input type="submit" name="ForceRefresh" class="button" value="<?php esc_attr_e('Force Refresh') ?>" />
				</p>
				<p class="description">
						Force Refresh allows you to re-retrieve all events from BYU Calendar.
				</p>
				<p><b>Frequency</b><br>
				<input type="radio" name="frequency" value="hourly" <?php checked( $options['refresh_frequency'], "hourly" ); ?>>Hourly
				<br>
				<input type="radio" name="frequency" value="twicedaily" <?php checked( $options['refresh_frequency'], "twicedaily" ); ?>>Twice a day
				<br>
				<input type="radio" name="frequency" value="daily" <?php checked( $options['refresh_frequency'], "daily" ); ?>>Daily
				</p>
				<p class="description">
						How often we automaticly pull from BYU Calendar 
				</p>
			
			<hr />
				<h3>General Settings</h3>
				<p>All Day Text:
					<input type="text" name="all_day_text" value="<?php echo $options["all_day_text"]; ?>" size="20">
					
					<p class="description">This is the text that will show instead of the time if the event is marked as an all day event</p>
				</p>
			<hr />
				<h3>Homepage Widget Settings</h3>
				<p>How Many Events To Show:
					<input type="text" name="widget_howMany" value="<?php echo $options["widget_howMany"]; ?>" size="5">
				</p>
				<p>Show Image
						<input type="checkbox" name="widget_showImage" value="1" <?php checked( $options['widget_showImage'], 1 ); ?> />
				    | Image Width: 
				     	<input type="text" name="widget_imageSize" value="<?php echo $options["widget_imageSize"]; ?>" size="5">
				    <p class="description">
						For Image Width be sure to include px, %, or some other form of measurement for size.
					</p>
				</p>
				<p>Show End Time
						<input type="checkbox" name="widget_showEndTime" value="1" <?php checked( $options['widget_showEndTime'], 1 ); ?> /> 
					 | Show Location
						<input type="checkbox" name="widget_showLocation" value="1" <?php checked( $options['widget_showLocation'], 1 ); ?> />
					 | Show Time Zone Always
						<input type="checkbox" name="widget_showTimeZone" value="1" <?php checked( $options['widget_showTimeZone'], 1 ); ?> /> 
					 | Home Timezone: 
					 	<input type="text" name="widget_homeTimeZone" value="<?php echo $options["widget_homeTimeZone"]; ?>" size="5"> 				 
				</p>
				<p class="description">
						Show Time Zone Always: Select this if you always want to show the timezone on the homepage widget. Else, it will only show the timezone if it is not the timezone specified as 'Home Timezone'.
				</p>
				
				<p>
					<p>Priority I - Categories:
						<input type="text" name="widget_categories1" value="<?php echo $options["widget_categories1"]; ?>" size="40">
						 | Show events within <input type="text" name="widget_priority1_days" value="<?php echo $options["widget_priority1_days"]; ?>" size="5"> days
					</p>

					<p>Priority II - Categories:
						<input type="text" name="widget_categories2" value="<?php echo $options["widget_categories2"]; ?>" size="40">
						 | Show events within <input type="text" name="widget_priority2_days" value="<?php echo $options["widget_priority2_days"]; ?>" size="5"> days
					</p>

					<p>Priority III:
						Pull From General Calendar
							<input type="checkbox" name="widget_priority3" value="1" <?php checked( $options['widget_priority3'], 1 ); ?> /> 
						| Show Unfeatured Events
							<input type="checkbox" name="widget_showUnfeatured" value="1" <?php checked( $options['widget_showUnfeatured'], 1 ); ?> /> 
						| Show Events Not On Main Calander
							<input type="checkbox" name="widget_showUnMainCalendar" value="1" <?php checked( $options['widget_showUnMainCalendar'], 1 ); ?> /> 
					</p>

					<p class="description">
							Priority I will show all events from the specified categories. Then, if the number of events in Priority I is less than the number of events to show on the widget Priority II categories are inserted.<br/>
						Categories must be separated by commas (ex: "72,74,75,76,77,70,78,79", excluding quotations). <br/>
						You must use specific category ids. <br/>
						A list of categorie ids are found in <a href="https://calendar-test.byu.edu/introduction">BYU's API Documentation</a>.</p>
					</p>
				</p>
				

			
			<hr />
				<h3>Calendar Settings</h3>
				<p>URL:
					<input type="text" name="calendar_url" value="<?php echo $options["calendar_url"]; ?>" size="40">
				</p>
				<p class="description">
					Enter the URL for the calendar page. It is best to not use absolute (http://cpms.byu.edu/calendar) but rather relative (/calendar).
				</p>
			<hr />
				<h3>Advance Settings</h3>
				<p class="description">
					Normaly you would not need to edit these settings. Additional settings are found in the source code as an array of options. Please check there if a setting is not found on the settings pages.
				</p>
				<p>Import URL:
					<input type="text" name="byu_url" value="<?php echo $options["byu_url"]; ?>" size="40">
				</p>
				<p>Image Prefix:
					<input type="text" name="image_prefix" value="<?php echo $options["image_prefix"]; ?>" size="40">
				</p>
				<p>Import Range: 
				Years
					<input type="text" name="getYears" value="<?php echo $options["getYears"]; ?>" size="5">
					Months
					<input type="text" name="getMonths" value="<?php echo $options["getMonths"]; ?>" size="5">
				</p>

			<p class="submit">
			<input type="submit" name="Submit" class="button-primary" value="<?php esc_attr_e('Save Changes') ?>" />
			</p>

			</form>
			<div>
				<p>This plugin is provided to sync the BYU calander with WordPress. The API documentation for BYU's Calander can be found at <a href="https://calendar-test.byu.edu/introduction">https://calendar-test.byu.edu/introduction</a>.
			</div>



			
			</div>

			<script type="text/javascript">
			//Add functionality to force refesh button on settings page
				document.getElementById("ForceRefresh").onclick = doFunction
			</script>
			

	<?php
}
	}
}