<?php

// don't load directly.
if (!defined('ABSPATH')) {
    header('Status: 403 Forbidden');
    header('HTTP/1.1 403 Forbidden');
    exit;
}

/**
 * DS integration with MEC FES through the groups page and setting up events...
 *
 * @author      author
 * @package     package
 * @since       1.0.0
 */
class DS_MEC_Integration {

    /**
     * The Args
     *
     * @access  public
     * @var     array
     */
    public static $args;

    /**
     * 
     */
    public function __construct()
    {
        $this->settingUp();
        $this->setHooks();
        //self::init();
    }

    /**
     * 
     */
    public function settingUp() {}

    /**
     * 
     */
    public function setHooks() {

        add_action('bp_setup_nav', array($this, 'ds_mec_groups_setup_nav'));
        /**
         * Hook 'ds_mec_fes_metabox_details' creted in MEC plugin file - app/fes/forms.php to allow for better positioning 
         * of the event type field.
         */
        add_action( 'ds_mec_fes_metabox_event_type', array( $this, 'ds_mec_groups_fes_meta_box_event_type' ) );
        add_action( 'ds_mec_fes_metabox_server_details', array( $this, 'ds_mec_groups_fes_meta_box_server_details' ), 200 );
        add_action( 'mec_fes_metabox_details', array( $this, 'ds_mec_fes_booking_options' ), 35 );

        add_action( 'mec_save_event_data', array( $this, 'ds_mec_groups_fes_submission_handler' ), 999, 2 );

        add_action( 'mec_shortcode_filters_tab_links', array( $this, 'ds_mec_groups_shortcode_filters_event_type_tab' ) );
        add_action( 'mec_shortcode_filters_content', array( $this, 'ds_mec_groups_shortcode_filters_event_type_content' ) );
        add_action( 'mec_shortcode_filters_save', array( $this, 'ds_mec_groups_shortcode_filters_event_type_save' ), 10, 2 );

        /**
         * Filters
         */
        add_filter( 'mec_calendar_atts', array( $this, 'ds_mec_groups_shortcode_filters_event_type_apply'), 200 );
        add_filter( 'mec_include_frontend_assets', array( $this, 'ds_mec_groups_enable_frontend_assets' ), 999, 1 );
        add_filter( 'mec_map_meta_query', array( $this, 'ds_my_squadrons_filter_events_meta_query' ), 10, 2 );

        /**
         * AJAX Requests
         */
        add_action('wp_ajax_mec_bp_delete_event', array($this, 'ds_mec_groups_delete_event'));
        add_action('wp_ajax_nopriv_mec_bp_delete_event', array($this, 'ds_mec_groups_delete_event'));

        /**
         * Admin UI
         */
        add_action( 'mec_monthly_search_form', array( $this, 'ds_mec_grid_search_form_platform_type') ); 

    }

    public function ds_mec_groups_delete_event() {
        if ( ! wp_verify_nonce( $_REQUEST['nonce'], 'ds-mec-ajax-nonce' ) ) {
            return wp_send_json_error(array('status' => false, 'error' => 'incorrect nonce...'));
        }

        $eventID = isset($_REQUEST['id']) ? $_REQUEST['id'] : null;
        $groupID = isset($_REQUEST['group']) ? $_REQUEST['group'] : null;

		if (!$eventID || !$groupID) {
			return wp_send_json_error(array('status' => false, 'error' => 'problem with id'));
		}

        // can the user edit this item?
        $permitted = bp_mec_is_user_can_event_change( $groupID );

        // check eventid belongs to groupid
        $eventGroupID = get_post_meta( $eventID, 'ds_mec_bp_group_id', true );

        if ($eventGroupID !== $groupID ) {
            return wp_send_json_error(array('status' => false, 'error' => 'groupID sent does not match groupID stored'));
        }

        if ( $permitted ) {
  
            $result = $this->ds_mec_groups_change_post_status( (int) $eventID, 'userdeleted' );

            if ( ! $result ) {
                update_post_meta( $eventID, 'ds_mec_bp_group_user_deleted_event', get_current_user_id() );

                return wp_send_json_success( array( 'status' => true, 'details' => $result, 'eventID' => (int) $eventID, 'groupID' => $groupID ) );
            }

        } else {
            return wp_send_json_error(array('status' => false, 'error' => 'current user does not have priveles - contact admin.'));
        }

    }

    /**
     * $post_id - The ID of the post you'd like to change.
     * $status -  The post status publish|pending|draft|private|static|object|attachment|inherit|future|trash.
     */
    public function ds_mec_groups_change_post_status( $post_id, $status ){
        // $current_post = get_post( $post_id, 'ARRAY_A' );
        // $current_post['post_status'] = $status;
        // $current_post['edit_date'] = true;
        global $wpdb;

        return $wpdb->query( $wpdb->prepare( "UPDATE $wpdb->posts SET post_status = '{$status}' WHERE ID = %d", $post_id ) );   

    }

    public function ds_mec_groups_setup_nav() {

        bp_core_new_subnav_item(array(
			'name' => __('Edit Event', 'mec-buddyboss'),
			'slug' => 'edit-event',
			'parent_url' => bp_loggedin_user_domain() . '/events/',
			'parent_slug' => 'events',
			'position' => 60,
			'item_css_id' => 'mec_edit_event',
			'screen_function' => array( $this, 'ds_mec_groups_edit_event_add_content' ),
		)
		);

    }

    public function ds_mec_groups_edit_event_add_content() {
        add_action( 'bp_template_content', array( $this, 'ds_mec_groups_edit_event_content' ) );
		bp_core_load_template( apply_filters( 'bp_core_template_plugin', 'members/single/plugins' ) );
    }

    public function ds_mec_groups_edit_event_content() {

    }

    /**
     * Display a meta group to collect event type.
     * @var array $post
     */
    public function ds_mec_groups_fes_meta_box_event_type( $post ) {
        /**
         * Taxonomy - event-type 
         * Only 3 options available to select - Squadron, Community and Recruitment.
         * Must validate the numbers on submit!
         * 
         */

        $groupID = bp_get_group_id() ?: '';

        $eventType = array_pop( wp_get_post_terms( $post->ID, 'mec_event_type' ) );
        $eventType = $eventType->term_id === 100 ? 100 : $eventType->term_id; 
        $discordRequired = get_post_meta( $post->ID, '_ds_group_mec_event_create_discord_channel', true );
        $discordURL = get_post_meta( $post->ID, '_ds_group_mec_event_discord_url', true );
        var_dump( $discordRequired );

        ?>
        <div class="mec-meta-box-fields" id="mec-event-type">
            <input type="hidden" name="mec[group]" value="<?php echo $groupID; ?>">
            <h4>Event Type</h4>
            <div>
                <input type="radio" id="ds_mec_fes_type_squadron" name="mec[type]" value="100" <?php echo $eventType === 100 ? 'checked' : ''; ?>>
                <label for="ds_mec_fes_type_squadron">Squadron<a data-balloon-pos="right" data-balloon="Event for Squadron members only">
                    <i class="fas fa-question-circle"></i></i></a>
                </label>
            </div>
            <div>
                <input type="radio" id="ds_mec_fes_type_community" name="mec[type]" value="101" <?php echo $eventType === 101 ? 'checked' : ''; ?>>
                <label for="ds_mec_fes_type_community">Community<a data-balloon-pos="right" data-balloon="Event for all community members">
                    <i class="fas fa-question-circle"></i></i></a>
                </label>
            </div>
            <div>
                <input type="radio" id="ds_mec_fes_type_recruitment" name="mec[type]" value="134" <?php echo $eventType === 134 ? 'checked' : ''; ?>>
                <label for="ds_mec_fes_type_recruitment">Recruitment<a data-balloon-pos="right" data-balloon="An event specifically aimed at recruiting new pilots">
                    <i class="fas fa-question-circle"></i></i></a>
                </label>
            </div>
        </div>

        <div class="mec-meta-box-fields" id="mec-event-discord">
            <h4>Discord Options</h4>
            <div style="margin-bottom: 15px;">
                <input type="checkbox" name="mec[discord_required]" id="ds_mec_fes_discord_required"<?php echo $discordRequired == true ? ' checked' : ''; ?>>
                <label for="ds_mec_fes_discord_required">Discord Comms Channel?<a data-balloon-pos="right" data-balloon="Check this box if you wish to have a secure comms channel for this event">
                    <i class="fas fa-question-circle"></i></i></a>
                </label>
            </div>
            <div class="mec-form-row">
                <div class="mec-title">
                    <span class="mec-dashicons dashicons dashicons-admin-links"></span>
                    <label for="ds_mec_fes_discord_url">Discord URL<a data-balloon-pos="right" data-balloon="If you have your own Discord Server with chat and comms, please enter the URL here">
                        <i class="fas fa-question-circle"></i></i></a>
                    </label>
                </div>
                
                <input type="text" name="mec[discord_url]" id="ds_mec_fes_discord_url" value="<?php echo $discordURL; ?>">

            </div>
            
        </div>

        <?php
    }

    /**
     * Display booking options in the FES.
     * 
     * @var object $post
     */
    function ds_mec_fes_booking_options( $post ) {

        $booking_options = get_post_meta($post->ID, 'mec_booking', true);
        $bookings_limit = isset($booking_options['bookings_limit']) ? $booking_options['bookings_limit'] : '';
        $bookings_limit_unlimited = isset($booking_options['bookings_limit_unlimited']) ? $booking_options['bookings_limit_unlimited'] : 0;

        ?>
        <div id="mec-booking">
            <div class="mec-meta-box-fields mec-booking-tab-content mec-tab-active" id="mec_meta_box_booking_options_form_1">
                <h4 class="mec-title"><label for="mec_bookings_limit"><?php _e('Total booking limit', 'mec'); ?></label></h4>
                <div class="mec-form-row">
                <label class="mec-col-3" for="mec_bookings_limit">Capacity
                    <a data-balloon-pos="right" data-balloon="Maximum number of pilots who can participate?"><i class="fas fa-question-circle"></i></a>
                </label>
                    <input class="mec-col-4 <?php echo ($bookings_limit_unlimited == 1) ? 'mec-util-hidden' : ''; ?>" type="text" name="mec[booking][bookings_limit]" id="mec_bookings_limit" value="<?php echo esc_attr($bookings_limit); ?>" placeholder="<?php _e('100', 'mec'); ?>"/>
                </div>
            </div>       
        </div>
        <?php
    }

    /**
     * Display a meta group for server information.
     * @var array $post
     */
    public function ds_mec_groups_fes_meta_box_server_details( $post ) {

        $settingsParameters = get_post_meta( $post->ID, '_ds_group_mec_event_settings_parameters', true );
        $serverDetails = get_post_meta( $post->ID, '_ds_group_mec_event_server_details', true );
        ?>
        <div class="mec-meta-box-fields" id="mec-settings-details">
            <h4>Event Settings and Parameters</h4>
            <textarea name="mec[settings_parameters]" id="ds_mec_fes_settings_parameters" style="max-width: 100%;"><?php echo isset( $settingsParameters ) ? $settingsParameters : ''; ?></textarea>
        </div>

        <div class="mec-meta-box-fields" id="mec-server-details">
            <h4>Server Information</h4>
            <textarea name="mec[server_details]" id="ds_mec_fes_server_details" style="max-width: 100%;"><?php echo isset( $serverDetails ) ? $serverDetails : ''; ?></textarea>
        </div>
        <?php
    }

    /**
     * Hook into event save to make necessary ammendments.
     * @var int $post_id
     * @var array $mec
     */
    public function ds_mec_groups_fes_submission_handler( $post_id, $mec ) {

        /**
         * Save event type and group_id
         */
        $postValid = false;

        // Check current_user is creator of the group_id
        if ( isset( $mec['group']) && $this->ds_mec_groups_is_group_owner( $mec['group']) && isset( $mec['type'] ) && isset( $post_id ) ) {

            $groupID = $mec['group'];
            $group      = groups_get_group( $groupID );
            update_post_meta( $post_id, "mec_bp_group_{$groupID}", $groupID );
            update_post_meta( $post_id, "ds_mec_bp_group_id", $groupID );
            update_post_meta( $post_id, 'ds_mec_bp_group_name', bp_get_group_name( $group ) );
    

            /**
             * Squadron:100
             * Recruitment:134
             */

            $allowedTypes = array( '100', '101', '134');

            if ( in_array( $mec['type'], $allowedTypes ) ) {
                error_log( 'this is the event type:' . $mec['type'] );
                $eventType = (int) $mec['type'];
                $taxResult = wp_set_post_terms( $post_id, $eventType, 'mec_event_type' );
                if ( !is_wp_error( $taxResult ) ) {
                    $result = true;
                } else {
                    error_log( 'there was an error with the tax' );
                }

            }
        }
        // Check event_type int is valid against available options
        // Store both event type and group_id as meta for the post(event)

         // 1. Save Discord Channel requirements...
        $discordRequired = isset( $mec['discord_required'] ) ? true : false; 
        update_post_meta( (int) $post_id, '_ds_group_mec_event_create_discord_channel', $discordRequired );

        // 2. Save Discord URL
        $discordURL = sanitize_text_field( $mec['discord_url'] );
        update_post_meta( (int) $post_id, '_ds_group_mec_event_discord_url', $discordURL );
        
        // 3. Save Event Settings and Parameters...
        $settingsParameters = sanitize_text_field( $mec['settings_parameters'] );
        update_post_meta( (int) $post_id, '_ds_group_mec_event_settings_parameters', $settingsParameters );

        // 4. Save Event Server Details...
        $serverDetails = sanitize_text_field( $mec['server_details'] );
        update_post_meta( (int) $post_id, '_ds_group_mec_event_server_details', $serverDetails );


    }

    /**
     * Check if current user submitting event is the creator of the group?
     */
    public function ds_mec_groups_is_group_owner( $currentGroupID ) {
        global $wpdb;

        $result = false;
        $groups = $wpdb->get_results( $wpdb->prepare( "SELECT id FROM {$wpdb->prefix}bp_groups WHERE creator_id = %d", get_current_user_id() ) );

        foreach ( $groups as $group ) {
            if ( $group->id == $currentGroupID ) {
                return true;
            }
        }

        return $result;

    }

    public function ds_mec_groups_shortcode_filters_event_type_tab( $post ) {
        ?>
        <a class="mec-create-shortcode-tabs-link" data-href="mec_select_event_type" href="#"><?php echo esc_html__('Event Types' ,'mec'); ?></a>
        <?php
    }

    public function ds_mec_groups_shortcode_filters_event_type_content( $post ) {

        $MEC_tax_walker = new MEC_tax_walker();

        ?>
        <div class="mec-form-row mec-create-shortcode-tab-content" id="mec_select_event_type">
            <h4><?php _e('Event Types', 'mec'); ?></h4>
            <p class="description"><?php _e('Choose your desired event types for filtering the events.', 'mec'); ?></p>
            <p class="description" style="color: red;"><?php _e('You will see only those types that are associated to at-least one event.', 'mec'); ?></p>
            <select name="mec_tax_input[mec_type][]" multiple="multiple">
            <?php
                $selected_categories = explode(',', get_post_meta($post->ID, 'event_type', true));
                wp_terms_checklist(0, array(
                    'descendants_and_self'=>0,
                    'taxonomy'=>'mec_event_type',
                    'selected_cats'=>$selected_categories,
                    'popular_cats'=>false,
                    'checked_ontop'=>false,
                    'walker'=>$MEC_tax_walker
                ));
            ?>
            </select>
        </div>

        <?php
    }

    /**
     * To prevent MEC frontend assets being called on every page, we only want to declare them where necessary. 
     * 
     * @var bool $status
     */
    public function ds_mec_groups_enable_frontend_assets( $status ) {
        // check component and action = return $status
        if ( bp_current_component() !== 'groups' && bp_current_action() !== 'events' ) {
            return $status;
        }

        return true;
    }

    /**
     * In calendar view for 'My Squadrons' we want to filter the results so user only see's events relating to squadrons they are members of.
     */
    public function ds_my_squadrons_filter_events_meta_query( $metaQuery, $atts ) {
        /**
         * So if the shortcode is for 'My Squadrons' ($atts['id']) and we are looking at events of 'Squadron' type.
         * 
         * Let's add a meta value for the squadrons that the user is member of...
         */
        if ( $atts['id'] == 2293 && $atts['ds_event_type'] == 100 ) {
            // 1. Get an ID list of all squadrons a member belongs to...
            $usersGroups = groups_get_groups( array(
                'user_id'           => (int) get_current_user_id()
            ));
            $usersGroups = wp_list_pluck( $usersGroups['groups'], 'id' );
            // 2. Filter all events based upon their type matching the relevant squadrons...
            $metaQuery[] = array(
                'key'     => 'ds_mec_bp_group_id',
                'value'   => $usersGroups,
                'compare' => 'IN',
            );
        }
    
        return $metaQuery;
    }

    public function ds_mec_groups_shortcode_filters_event_type_save( $post_id, $terms ) {
        $types = (isset($terms['mec_type']) and is_array($terms['mec_type'])) ? implode(',', $terms['mec_type']) : '';
        
        update_post_meta($post_id, 'event_type', $types);
    }

    public function ds_mec_groups_shortcode_filters_event_type_apply( $atts ) {
        //printr( $atts );

        $atts['ds_event_type'] = '100';
        
        return $atts;

        
    } 

    public function ds_mec_grid_search_form_platform_type( $sf_options_list ) {
        ?>
        <div class="mec-form-row">
            <label class="mec-col-12" for="mec_sf_list_platform_type"><?php echo __('Platform Type'); ?></label>
            <select class="mec-col-12" name="mec[sf-options][list][platform][type]" id="mec_sf_list_platform_type">
                <option value="0" <?php if(isset($sf_options_list['platform']) and isset($sf_options_list['platform']['type']) and $sf_options_list['platform']['type'] == '0') echo 'selected="selected"'; ?>><?php _e('Disabled', 'mec'); ?></option>
                <option value="dropdown" <?php if(isset($sf_options_list['platform']) and isset($sf_options_list['platform']['type']) and $sf_options_list['platform']['type'] == 'dropdown') echo 'selected="selected"'; ?>><?php _e('Dropdown', 'mec'); ?></option>
            </select>
        </div>
        <?php 
    }


}

add_action( 'bp_init', function() {
    $ds_fes_event_type = new DS_MEC_Integration;
});

