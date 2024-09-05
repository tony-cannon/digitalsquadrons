<?php 

// Exit if accessed directly
if ( !defined( 'ABSPATH' ) ) exit;
 
class BP_Loop_Filters {
 
    /**
     * Constructor
     */
    public function __construct() {
        $this->setup_actions();
    }
 
    /**
     * Actions
     *
     * @uses bp_is_active()
     * @uses is_multisite()
     */
    private function setup_actions() {
        /**
         * Adds the random order to the select boxes of the Members, Groups and Blogs directory pages
         */
        // Members component is core, so it will be available
        add_action( 'bp_members_directory_order_options', array( $this, 'random_order' ) );
 
        // You need to check Groups component is available
        if( bp_is_active( 'groups' ) )
            add_action( 'bp_groups_directory_order_options',  array( $this, 'random_order' ) );
 
        // You need to check WordPress config and that Blogs Component is available
        if( is_multisite() && bp_is_active( 'blogs' ) )
            add_action( 'bp_blogs_directory_order_options',   array( $this, 'random_order' ) );
    }
 
    /**
     * Displays a new option in the Members/Groups & Blogs directories
     *
     * <a class="bp-suggestions-mention" href="https://buddypress.org/members/return/" rel="nofollow">@return</a> string html output
     */
    public function random_order() {
        ?>
        <option value="recruiting"><?php _e( 'Recruiting', 'buddypress' ); ?></option>
        <?php
    }
 
}
 
// 1, 2, 3 go !
function bp_loop_filters() {
    return new BP_Loop_Filters();
}
 
add_action( 'bp_include', 'bp_loop_filters' );