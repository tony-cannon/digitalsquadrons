<?php
/**
 * Handles the display of the profile location page by loading the correct template file.
 * Also checks to make sure this can only be accessed for the logged in users profile.
 *
 */
function ds_member_profile_social_media_screen() {

	if ( ! empty( $_POST['ds-profile-social-media-submit'] ) ) {

		if ( ! wp_verify_nonce( $_POST['_wpnonce'], 'ds-profile-social-media' ) ) {
			die( __( 'Security check failed', 'buddyboss' ) );
		}

		$userID = bp_displayed_user_id();
		$permittedNetworks = array( 'discord', 'instagram', 'twitter', 'twitch', 'facebook', 'youtube' );
		$postArray = array();

		foreach ( $permittedNetworks as $network ) {
			$postVal = $_POST["ds-profile-social-media-{$network}"];

			if ( isset( $postVal ) && strlen( $postVal ) > 0 ) {
				$postArray[$network] = $postVal;
			}
		}

		if ( isset( $postArray ) ) {
			update_user_meta( $userID, '_ds_user_networks', $postArray );

			if ( in_array( $_POST['ds-profile-social-media-visibility'], array( 'public', 'loggedin', 'friends') ) ){
				update_user_meta( $userID, '_ds_user_network_vis', $_POST['ds-profile-social-media-visibility'] );
			}
			
			bp_core_add_message( __( 'Social Media Networks Updated!', 'buddyboss' ) );
		}
	}

    /**
	 * Fires right before the loading of the XProfile edit screen template file.
	 *
	 * @since BuddyPress 1.0.0
	 */
	do_action( 'ds_screen_profile_social_media_edit' );

	/**
	 * Filters the template to load for the XProfile edit screen.
	 *
	 * @since BuddyPress 1.0.0
	 *
	 * @param string $template Path to the XProfile edit template to load.
	 */
	bp_core_load_template( apply_filters( 'ds_template_profile_social_media_edit', 'members/single/home' ) );
}