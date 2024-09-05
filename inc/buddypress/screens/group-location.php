<?php
/**
 * Groups: Single group "Manage > Location" screen handler
 *
 * @package BuddyBoss\Groups\Screens
 * @since BuddyPress 3.0.0
 */

/**
 * Handle the display of a group's admin/group-location page.
 *
 * @since BuddyPress 1.0.0
 */
function groups_screen_group_admin_location() {
	if ( 'group-location' != bp_get_group_current_admin_tab() ) {
		return false;
	}

	if ( bp_is_item_admin() ) {

        if ( isset( $_POST['save'] ) ) {
            // Check the nonce.
            if ( ! check_admin_referer( 'groups_edit_group_location' ) ) {
                return false;
            }
    
            if ( ! $_POST['ds_squadron_country'] ) {
                bp_core_add_message( __( 'Each squadron must have a country.', 'buddyboss' ), 'error' );
            } elseif ( ! ds_group_update_location( 
                array(
                    'group_id'      => $_POST['group-id'],
                    'country'       => $_POST['ds_squadron_country'],
                    'state'         => $_POST['ds_squadron_state']     
                )
            ) ) {
                bp_core_add_message( __( 'There was an error updating group location. Please try again.', 'buddyboss' ), 'error' );
            } else {
                bp_core_add_message( __( 'Group location details were successfully updated.', 'buddyboss' ) );
            }
    
            bp_core_redirect( bp_get_group_permalink( groups_get_current_group() ) . 'admin/group-location/' );
        }
        /**
         * Fires before the loading of the group location page template.
         *
         * @since BuddyPress 2.4.0
         *
         * @param int $id ID of the group that is being displayed.
         */
        do_action( 'groups_screen_group_admin_location', bp_get_current_group_id() );

        /**
         * Filters the template to load for a group's location page.
         *
         * @since BuddyPress 2.4.0
         *
         * @param string $value Path to a group's location edit template.
         */
        bp_core_load_template( apply_filters( 'groups_template_group_admin_location', 'groups/single/home' ) );
    }
}
add_action( 'bp_screens', 'groups_screen_group_admin_location' );