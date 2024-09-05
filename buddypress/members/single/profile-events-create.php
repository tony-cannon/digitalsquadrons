<?php

/**
 * Template for creating or editing Events on a member profile page
 * You can copy this file to your-theme/buddypress/members/single
 * and then edit the layout.
 */

$pp_ec = PP_Simple_Events_Create::get_instance()->pp_events_get_edit_object();

$required_fields = get_option( 'pp_events_required' );	//var_dump( $required_fields );

pp_events_load_create_scripts();

?>

<form id="profile-event-form" name="profile-event-form" method="post" action="" class="standard-form" enctype="multipart/form-data">

	<p>
		<label for="event-title"><?php _e( 'Title', 'bp-simple-events' ); ?>: *</label>
		<input type="text" id="event-title" name="event-title" value="<?php echo $pp_ec->title; ?>" />
	</p>

	<p>
		<label for="event-description"><?php _e( 'Description', 'bp-simple-events' ); ?>: *</label>
		<textarea id="event-description" name="event-description" ><?php echo $pp_ec->description; ?></textarea>
	</p>

	<p>
		<label for="event-date"><?php _e( 'Start', 'bp-simple-events' ); ?>: <?php if ( in_array('date', $required_fields) ) _e( '*', 'bp-simple-events' ); ?></label>
		<input type="text" id="event-date" name="event-date" placeholder="<?php _e( 'Click to add Start Date...', 'bp-simple-events' ); ?>" value="<?php echo $pp_ec->date; ?>" />
	</p>

	<p>
		<label for="event-date-end"><?php _e( 'End', 'bp-simple-events' ); ?>: <?php if ( in_array('date-end', $required_fields) ) _e( '*', 'bp-simple-events' ); ?></label>
		<input type="text" id="event-date-end" name="event-date-end" placeholder="<?php _e( 'Click to add End Date...', 'bp-simple-events' ); ?>" value="<?php echo $pp_ec->date_end; ?>" />
	</p>

	<p>
		<label for="event-location"><?php _e( 'Location', 'bp-simple-events' ); ?>: <?php if ( in_array('location', $required_fields) ) _e( '*', 'bp-simple-events' ); ?></label>
		<input type="text" id="event-location" name="event-location" placeholder="<?php _e( 'Start typing location name...', 'bp-simple-events' ); ?>" value="<?php echo $pp_ec->address; ?>" />
	</p>

	<p>
		<label for="event-url"><?php _e( 'Url', 'bp-simple-events' ); ?>: <?php if ( in_array('url', $required_fields) ) _e( '*', 'bp-simple-events' ); ?></label>
		<input type="text" size="80" id="event-url" name="event-url" placeholder="<?php _e( 'Add an Event-related Url...', 'bp-simple-events' ); ?>" value="<?php echo $pp_ec->url; ?>" />
	</p>

	<?php
		$args = array(
			'type'                     => 'post',
			'child_of'                 => 0, //get_cat_ID( 'Events' ),
			'parent'                   => '',
			'orderby'                  => 'name',
			'order'                    => 'ASC',
			'hide_empty'               => 0,
			'hierarchical'             => 1,
			'exclude'                  => '',
			'include'                  => '',
			'number'                   => '',
			'taxonomy'                 => 'category',
			'pad_counts'               => false
		);

		$event_cat_args = apply_filters( 'event_cat_args_filter', $args );
		$categories = get_categories( $event_cat_args );
	?>

	<?php if ( ! empty( $categories ) ) : ?>

		<p>
			<label for="event-cats"><?php _e( 'Categories', 'bp-simple-events' ); ?>: <?php if ( in_array('categories', $required_fields) ) _e( '*', 'bp-simple-events' ); ?></label>
			<?php
				foreach( $categories as $category ) {

					$checked = '';
					if ( in_array( $category->term_id, $pp_ec->cats_checked ) ) {
						$checked = ' checked';
					}

					echo '&nbsp;&nbsp;<input type="checkbox" name="event-cats[]" value="' . $category->term_id . '"' . $checked . '/> ' . $category->name . '<br>';
				}
			?>
		</p>

	<?php endif; ?>

	<p>

		<label for="event-attend"><?php _e( 'Attending Options', 'bp-simple-events' ); ?>:</label>
		&nbsp;&nbsp;<input type="checkbox" id="event-attend-button" name="event-attend-button" value="1" <?php checked( $pp_ec->attend_button, 1 ); ?> /> <?php _e( 'Add an "I want to attend" button on single event pages.', 'bp-simple-events' ); ?>

		<div id="event-attend-options" name="event-attend-options" style="margin-left: 10px;">

			<input type="checkbox" name="event-attend-notify" value="1" <?php checked( $pp_ec->attend_notify, 1 ); ?> /> <?php _e( 'Receive a Notification when a member decides to attend or not attend your event.', 'bp-simple-events' ); ?>
			<br>

			<input type="checkbox" id="event-attendees-list" name="event-attendees-list" value="1" <?php checked( $pp_ec->attendees_list, 1 ); ?> /> <?php _e( 'Show a list of attendees on single event pages.', 'bp-simple-events' ); ?>

			<div id="event-attendees-display-options" name="event-attendees-display-options" style="margin-left: 10px;">

				<input type="checkbox" id="event-attendees-list-public" name="event-attendees-list-public" value="1" <?php checked( $pp_ec->attendees_list_public, 1 ); ?> /> <?php _e( 'Make the attendees list public. Otherwise it will only be visible to you.', 'bp-simple-events' ); ?>
				<br>

				<input type="checkbox" id="event-attendees-list-avatars" name="event-attendees-list-avatars" value="1" <?php checked( $pp_ec->attendees_list_avatars, 1 ); ?> /> <?php _e( 'Show attendees as Avatars. Otherwise their display names with be shown.', 'bp-simple-events' ); ?>

			</div>

		</div>

	</p>


	<?php
	 /**
	  * If site admin has enabled Group support...
	  * And if this member belongs to a Group that has selected to allow Events to be assigned to a Group
	  * then those Groups will appear here as checkboxes
	  * If this member selects a Group, this Event will appear under the Events tab for that Group
	  * See see function get_groups() in bp-simple-events\inc\pp-events-create-class.php
	  */
	?>
	<?php $groups = $pp_ec->groups; ?>
	<?php if ( ! empty( $groups ) ) : ?>

		<p>
			<label for="event-groups"><?php _e( 'Groups', 'bp-simple-events' ); ?>: <?php if ( in_array('groups', $required_fields) ) _e( '*', 'bp-simple-events' ); ?></label>
			<?php
				foreach( $groups as $key => $value ) {

					$checked = '';
					if ( in_array( $key, $pp_ec->groups_checked ) ) {
						$checked = ' checked';
					}

					echo '&nbsp;&nbsp;<input type="checkbox" name="event-groups[]" value="' . $key . '"' . $checked . '/> ' . $value . '<br>';
				}
			?>
		</p>

	<?php endif; ?>


	<?php if ( ! $pp_ec->editor ) : ?>

		<p>
			<label for="event-img"><?php _e( 'Image: ( jpg only  )', 'bp-simple-events' ); ?>: <?php if ( in_array('image', $required_fields) ) _e( '*', 'bp-simple-events' ); ?></label>
			<input type="file" id="event-img" name="event-img" value="">
			&nbsp;&nbsp;<input onclick="pp_events_clearFileInput('event-img')" type="button" value="<?php _e('Remove', 'bp-simple-events'); ?>" />
		</p>

	<?php else : ?>

		<p>
			<label for="event-img"><?php _e( 'Image: ( jpg only  )', 'bp-simple-events' ); ?> <?php if ( in_array('image', $required_fields) ) _e( '*', 'bp-simple-events' ); ?></label>

			<?php if ( has_post_thumbnail( $pp_ec->post_id ) ) : ?>

				<?php _e( 'Current Image:', 'bp-simple-events' ); ?>
				<br>
				<?php echo get_the_post_thumbnail( $pp_ec->post_id, 'thumbnail' ); ?>
				<br>
				<?php _e( 'Delete the Image?', 'bp-simple-events' ); ?>
				&nbsp;&nbsp;<input type="checkbox" name="event-img-delete" id="event-img-delete" value="1">
				<br>&nbsp;<br>

			<?php endif; ?>

			<input type="file" id="event-img" name="event-img" value="">
			&nbsp;&nbsp;<input onclick="pp_events_clearFileInput('event-img')" type="button" value="Remove" />
		</p>

	<?php endif; ?>

	<input type="hidden" id="event-address" name="event-address" value="<?php echo $pp_ec->address; ?>" />
	<input type="hidden" id="event-latlng" name="event-latlng"  value="<?php echo $pp_ec->latlng; ?>" />
	<input type="hidden" name="action" value="event-action" />
	<input type="hidden" name="eid" value="<?php echo $pp_ec->post_id; ?>" />
	<?php wp_nonce_field( 'event-nonce' ); ?>

	<input type="submit" name="submit" class="button button-primary" value="<?php _e(' SAVE ', 'bp-simple-events'); ?>"/>

</form>
