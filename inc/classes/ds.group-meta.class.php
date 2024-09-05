<?php 

// Buddypress Group MetaBox
if( class_exists( 'BP_Group_Extension' ) ) {

    class DS_Group_Setup {

        public function __construct() {
            $this->setup_hooks();
        }

        private function setup_hooks() {
            add_action( 'bp_groups_admin_meta_boxes', array( $this, 'ds_group_active' ) );
            add_action( 'bp_setup_nav', array( $this, 'ds_groups_setup_nav' ), 100 );
            add_action( 'bp_group_admin_edit_after',  array( $this, 'ds_group_admin_ui_additional_options_save'), 10, 1 );
        }

        public function ds_group_active() {
            add_meta_box( 'ds_group_active', __( 'Group Lock' ), array( &$this, 'ds_group_active_page_metabox'), get_current_screen()->id, 'side', 'core');
            add_meta_box( 'ds_group_options', __( 'Group Options' ), array( &$this, 'ds_group_admin_ui_metabox_options'), get_current_screen()->id, 'side', 'high' );
        }

        public function ds_group_active_page_metabox( $item = false ) {
        ?>
            <fieldset>
                <div class="field-group">
                    <p><input type="checkbox" id="ds_group_active" name="ds_group_active" > <?php _e( 'Group Locked?' );?></p>
                </div>
                <p class="bb-section-info">If a group payment is missed the group will auto-lock until payment is rectified.</p>
                <p class="bb-section-info">A Group can be locked and removed from public view by checking this box.</p>
            </fieldset>
            
            
        <?php

            wp_nonce_field(basename(__FILE__), "ds_group_active_nonce");
        }

        public function bp_fan_club_option() {
        ?>
            <option value="featured"><?php _e( 'Featured' ); ?></option>
        <?php
        }

        public function ds_groups_setup_nav() {
            global $bp, $current_user;
            
            /**
             * Setup the create group link within user profile group page...
             */
            $newGroupArgs = array(
                'name'                  => __( 'Create a Squadron', 'digitalSquadrons'),
                'slug'                  => 'create-squadron',
                'parent_slug'           => $bp->groups->slug,
                'parent_url'            => $bp->loggedin_user->domain,
                //'position'              => 400,
                'user_has_access'   	=> is_user_logged_in(),
                'link'                  => home_url( '/create-a-squadron/' ),
                'screen_function'       => 'something-to-do',
                'item_css_id'           => $bp->profile->id
            );
        
            bp_core_new_subnav_item( $newGroupArgs );
        }

        /**
         * Displays the meta box
         */
        public function ds_group_admin_ui_metabox_options( $item = false ) {
            if( empty( $item ) )
                return;
    
            // Using groups_get_groupmeta to check if the group is featured
            $includeInGroupLoop = groups_get_groupmeta( $item->id, '_ds_groups_excludeFromGroupLoop' );
            ?>
                <p>
                    <input type="checkbox" id="ds-admin-ui-options-group-loop" name="ds-admin-ui-options-group-loop" value="1" <?php checked( 1, $includeInGroupLoop );?>> <?php _e( 'Exclude Group from the Loop?' );?>
                </p>
                <p>i.e. If this is a competition, such as a league/cup, then we want to exclude from squadron results.</p>
            <?php
            wp_nonce_field( 'ds_groups_options_save_' . $item->id, '_admin' );
        }
    
        function ds_group_admin_ui_additional_options_save( $group_id = 0 ) {
            if ( 'POST' !== strtoupper( $_SERVER['REQUEST_METHOD'] ) || empty( $group_id ) )
                return false;
    
            check_admin_referer( 'ds_groups_options_save_' . $group_id, '_admin' );
            
            // You need to check if the group was featured so that you can eventually delete the group meta
            $was_featured = groups_get_groupmeta( $group_id, '_ds_groups_excludeFromGroupLoop' );
            $to_feature = !empty( $_POST['ds-admin-ui-options-group-loop'] ) ? true : false;
    
            if( !empty( $to_feature ) && empty( $was_featured ) )
                groups_update_groupmeta( $group_id, '_ds_groups_excludeFromGroupLoop', 1 );
            if( empty( $to_feature ) && !empty( $was_featured ) )
                groups_delete_groupmeta( $group_id, '_ds_groups_excludeFromGroupLoop' );
        }
    }
}

if ( class_exists( 'DS_Group_Setup' ) ) {
    if ( bp_is_active( 'groups' ) ) {
        $dsGroupTemplate = new DS_Group_Setup();
    }  
}
    

