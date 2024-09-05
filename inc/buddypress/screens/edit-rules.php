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
function groups_screen_group_admin_rules() {
	if ( 'edit-rules' != bp_get_group_current_admin_tab() ) {
		return false;
	}

	if ( bp_is_item_admin() ) {

        if ( isset( $_POST['save'] ) ) {
            // Check the nonce.
            if ( ! check_admin_referer( 'groups_edit_group_edit_rules' ) ) {
                return false;
            }

            if ( !empty( $_POST['ds-group-rules']) ) {
                if ( ! ds_group_update_rules( $_POST['ds-group-rules'] ) ) {
                    bp_core_add_message( __( 'There was an error updating group rules. Please try again.', 'buddyboss' ), 'error' );
                } else {
                    bp_core_add_message( __( 'Group rules were successfully updated.', 'buddyboss' ) );
                }
            }
     
            bp_core_redirect( bp_get_group_permalink( groups_get_current_group() ) . 'admin/edit-rules/' );
        }
        /**
         * Fires before the loading of the group location page template.
         *
         * @since BuddyPress 2.4.0
         *
         * @param int $id ID of the group that is being displayed.
         */
        do_action( 'groups_screen_group_admin_rules', bp_get_current_group_id() );

        /**
         * Filters the template to load for a group's location page.
         *
         * @since BuddyPress 2.4.0
         *
         * @param string $value Path to a group's location edit template.
         */
        bp_core_load_template( apply_filters( 'groups_template_group_admin_rules', 'groups/single/home' ) );
    }
}
add_action( 'bp_screens', 'groups_screen_group_admin_rules' );