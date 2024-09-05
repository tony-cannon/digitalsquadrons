<?php
/**
 * Handles the display of the profile location page by loading the correct template file.
 * Also checks to make sure this can only be accessed for the logged in users profile.
 *
 */
function ds_member_profile_platform_aircraft_screen() {
	/**
	 * We need to check for any posts and make relevant saves.
	 */
	if ( ! empty( $_POST['ds-profile-platform-aircraft-submit'] ) ) {

		if ( ! wp_verify_nonce( $_POST['_wpnonce'], 'ds-profile-platform-aircraft' ) ) {
			die( __( 'Security check failed', 'buddyboss' ) );
		}

		$userID = bp_displayed_user_id();
		$platforms = $_POST['ds-member-platform-select'];
		$previousPlatforms = get_user_meta( $userID, '_ds_member_platforms', true ) ?: array();
		$previousAircraft = get_user_meta( $userID, '_ds_member_group_types', true ) ?: array();

		if ( isset( $platforms ) && is_array( $platforms ) ) {
			// Update the user platforms...
			update_user_meta( $userID, '_ds_member_platforms', $platforms );

			do_action( 'ds_member_update_platforms', $platforms, $previousPlatforms, $userID );
			// Update the aircraft from all platforms...
			$allAircraft = array();

			foreach ( $platforms as $platform ) {
				// Check for aircraft select fields and update aircraft...
				$platformAircraft = (array) $_POST["ds-member-{$platform}-select"];
				
				foreach ( $platformAircraft as $aircraft ) {
					$allAircraft[] = $aircraft;
				}
			}

			update_user_meta( $userID, '_ds_member_group_types', $allAircraft );

			do_action( 'ds_member_update_aicraft', $allAircraft, $previousAircraft, $userID );

		} else {
			update_user_meta( $userID, '_ds_member_platforms', $platforms );

			do_action( 'ds_member_update_platforms', $platforms, $previousPlatforms, $userID );
		}

		bp_core_add_message( __( 'Platforms and Aircraft Updated!', 'buddyboss' ) );
	}

    /**
	 * Fires right before the loading of the XProfile edit screen template file.
	 *
	 * @since BuddyPress 1.0.0
	 */
	do_action( 'ds_screen_profile_platform_aircraft_edit' );

	/**
	 * Filters the template to load for the XProfile edit screen.
	 *
	 * @since BuddyPress 1.0.0
	 *
	 * @param string $template Path to the XProfile edit template to load.
	 */
	bp_core_load_template( apply_filters( 'ds_template_profile_platform_aircraft_edit', 'members/single/home' ) );
}