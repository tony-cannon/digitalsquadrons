<?php
/**
 * Handles the display of the profile location page by loading the correct template file.
 * Also checks to make sure this can only be accessed for the logged in users profile.
 *
 */
function ds_member_profile_location_screen() {

	

    /**
	 * Fires right before the loading of the XProfile edit screen template file.
	 *
	 * @since BuddyPress 1.0.0
	 */
	do_action( 'ds_screen_profile_location_edit' );

	/**
	 * Filters the template to load for the XProfile edit screen.
	 *
	 * @since BuddyPress 1.0.0
	 *
	 * @param string $template Path to the XProfile edit template to load.
	 */
	bp_core_load_template( apply_filters( 'ds_template_profile_location_edit', 'members/single/home' ) );
}
