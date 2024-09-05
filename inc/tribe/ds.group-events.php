<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

//Tribe__Settings_Manager::set_option( 'tribeEventsTemplate', 'views/template-events.blade.php' );


class DS_Group_Events {

    /**
     * Possible Event Types
     * @var array
     */
    public $eventTypes = array( 'squadron', 'community' );

    /**
     * Current Action
     * @var string
     */
    public $currentAction = '';

    /**
     * Curent Group ID
     * @var string
     */
    public $groupID = null;

    /**
     * Is current user the Creator of the Group?
     * @var boolean
     */
    public $isGroupCreator = false;

    /**
     * Events Object 
     * @var object WP_Query
     */
    public $events;

    /**
     * 
     */
    function __construct() {
        global $bp;

        $this->currentAction = $bp->current_action;
        $this->groupID = bp_get_current_group_id();

        $currentUser = get_current_user_id();

        if ( $currentUser == $bp->groups->current_group->creator_id ) {
            $this->isGroupCreator = true;
        }

        $this->bootstrap();

        $form = new Tribe__Events__Community__Event_Form( null );

        //print_r($form);
    }

    public function bootstrap() {
        global $bp;

        //print_r($this->currentAction);
        
        add_action( 'ds_group_event_screen_output_submission_form', array( $this, 'ds_group_event_output_submission_form' ), 10 );
    }

    public function ds_group_event_form_footer() {

       
    }

    function ds_group_event_output_submission_form() {
        //set_query_var( 'events', $events );
        set_query_var( 'groupEvents', $this );
        // parse to template and format the table...
        $template = 'groups/single/admin/event-edit';

        bp_get_template_part( $template ); 

        //print_r($this->currentAction);
    }

    public function doEventList() {

        // need to advance and return an error - not that we should have one! 
        if ( $this->groupID === null || $this->currentAction !== 'group-events' ) {
            return;
        }
        //print_r( $_SERVER['REQUEST_URI']);	
        $eventArgs = array(
            'author'        => get_current_user_id()
        );

        // Get an object or an array of events related to this particular group?
        $this->events = tribe_get_events( $eventArgs, true );
        $test = 'her I am';

        //set_query_var( 'events', $events );
        set_query_var( 'groupEvents', $this );
        // parse to template and format the table...
        $template = 'groups/single/admin/' . $this->currentAction;

		bp_get_template_part( $template );

        ?>
        <div>We are here!</div>
        <?php
    }

    public function doEditEvent() {
        // Create a page containing a form for either entering new event or edit an existing event...
    }


}

function ds_group_events_boot() {
    global $bp;

    if ( $bp->current_action == 'group-events' ) {
        $dsGroupEvent = new DS_Group_Events;
    }
}
add_action( 'bp_init', 'ds_group_events_boot', 10 );


















function ds_group_event_edit_form_shortcode() {
    
    if ( !is_user_logged_in() ) {
        return;
        //Need to 404
    }

    $dsGroupEvent = new DS_Group_Events;

    bp_get_template_part( 'groups/single/admin/event-edit' );
    
    
}
//add_shortcode( 'ds_display_group_event_edit_form', 'ds_group_event_edit_form_shortcode' );

function ds_change_event_edit_page_template( $template ) {
    print_r($template);
    if ( $_SERVER['REQUEST_URI'] == '/events/squadrons/add/?group_id=34' ) {        
        $template = 'page_cleanpage.php';
    }
    print_r($template);
    return $template;
}
//add_filter( 'tribe_events_community_template', 'ds_change_event_edit_page_template', 10 );


function change_event_slug_on_save( $post_id, $event_data, $event ) {
    //$event_data holds the changed event data, $event is the actual event (post)
    if ( ! wp_is_post_revision( $post_id ) && tribe_is_event($post_id) ) {
        // verify post is not a revision, and an event
        $slug = sanitize_title($event->post_title);
        $newslug = $slug . '-' . tribe_format_date($event_data['EventStartDate'], false, 'j-F-Y');
        if ($event->post_name !== $newslug) {
            // unhook this function to prevent infinite looping
            remove_action( 'tribe_events_update_meta', 'change_event_slug_on_save' );
            // update the post slug
            wp_update_post( array(
                'ID' => $post_id,
                'post_name' => $newslug
            ));
            // re-hook this function
            add_action( 'tribe_events_update_meta', 'change_event_slug_on_save', 10, 3 );
        }

        /**
         * $data = array(
         *  'ID' => $post_id,
         *  'post_content' => $content,
         *  'meta_input' => array(
         *      '_ds_event_group_id' => bp_get_group_id(),
         *      'another_meta_key' => $another_meta_value
         *  )
         * );
         * wp_update_post( $data );
         */
    }
}
//add_action( 'tribe_events_update_meta', 'change_event_slug_on_save', 10, 3);

function ds_filter_group_events_args( $args ) {
    global $bp;

    // Check if we are actually on the 'group-events' page and current user privileges... {}
    if ( bp_is_current_action( 'group-events' ) ) {

        // Check for a page number


        // Get groupID
        $groupID = bp_get_group_id();
        //$args['_ds_event_group_id'] = bp_get_group_id();
        $args['author'] = '';
        //print_r($args);
    }
    return $args;
}
//add_filter( 'tribe_events_community_my_events_query', 'ds_filter_group_events_args', 10 );

/**
 * Removed Columns on Event Creation Page...
 */
function ds_remove_group_event_columns( $columns ) {
    unset( $columns['venue'] );

    return $columns;
}
//add_filter( 'tribe_community_events_list_columns', 'ds_remove_group_event_columns', 10 );