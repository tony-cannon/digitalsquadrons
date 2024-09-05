<?php
/**
 * Handles the display of the profile location page by loading the correct template file.
 * Also checks to make sure this can only be accessed for the logged in users profile.
 *
 */
function ds_member_profile_location_screen() {

	$selectName = 'ds-member-location';

	if ( ! empty( $_POST['ds-profile-location-submit'] ) ) {

		if ( ! wp_verify_nonce( $_POST['_wpnonce'], 'ds-profile-location' ) ) {
			die( __( 'Security check failed', 'buddyboss' ) );
		}

		$userID = bp_displayed_user_id();

		if ( isset( $_POST['ds-member-location-country-select'] ) ) {
			// Update the user platforms...
			update_user_meta( $userID, '_ds_member_country', $_POST['ds-member-location-country-select'] );

			if ( isset ( $_POST['ds-member-location-state-select'] ) ) {
				update_user_meta( $userID, '_ds_member_state', $_POST['ds-member-location-state-select'] );
			}

		} else {
			update_user_meta( $userID, '_ds_member_country', $_POST['ds-member-location-state-select'] );
			update_user_meta( $userID, '_ds_member_state', $_POST['ds-member-location-state-select'] );
		}

		bp_core_add_message( __( 'Location Details Updated!', 'buddyboss' ) );
	}
	

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
