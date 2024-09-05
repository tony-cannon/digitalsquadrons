<?php 

// Buddypress Group MetaBox
if( class_exists( 'BP_Group_Extension' ) ) :

class bp_fan_club {
    public function __construct() {
        $this->setup_hooks();
    }

    private function setup_hooks() {
        add_action( 'bp_groups_admin_meta_boxes', array( $this, 'bp_fan_club_page' ) );
    }

    public function bp_fan_club_page() {
        add_meta_box( 'bp_fan_club', __( 'List on the Fan Club page' ), array( &$this, 'bp_fan_club_page_metabox'), get_current_screen()->id, 'side', 'core');
    }

    public function bp_fan_club_page_metabox( $item = false ) {
    ?>
        <p>
        <input type="checkbox" id="bp_fan_club" name="bp_fan_club" > <?php _e( 'Mark this to List on the Fan Club Page' );?>
        </p>
    <?php

        wp_nonce_field(basename(__FILE__), "bpgroupmeta-box-nonce");
    }

    public function bp_fan_club_option() {
    ?>
        <option value="featured"><?php _e( 'Featured' ); ?></option>
    <?php
    }
}

function bp_fan_club_group() {
    if( bp_is_active( 'groups') )
    return new bp_fan_club();
}
add_action( 'bp_init', 'bp_fan_club_group' );

endif;