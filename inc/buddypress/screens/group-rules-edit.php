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
    print_r('boom1');
	if ( 'group-rules-edit' != bp_get_group_current_admin_tab() ) {
		return false;
	}

	if ( bp_is_item_admin() ) {
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