<?php

// Exit if accessed directly

use iThemesSecurity\User_Groups\All_Users;

if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Get a specific Discordian meta value...
 * 
 * @var meta_key string
 * @var userID int
 */
function ds_member_get_discord_value( $meta_key, $userID = null ) {
    global $wpdb;

    $userID = $userID === null ? get_current_user_id() : $userID;
    $discordTable = $wpdb->prefix . 'ds_discord_oauth';
    $userDiscordData = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $discordTable WHERE user_id = %d", (int) $userID ) );

    if ( is_object( $userDiscordData ) && isset( $userDiscordData->{$meta_key} ) ) {
        return $userDiscordData->{$meta_key};
    }

    return false;

}

/**
 * On loading members search page, the filters resort to last profile, these need to be reset to allow fresh search.
 * 
 * Adds JS to implement reset function...
 */
function ds_members_search_reset_on_page_load() {
    ?>
    <script type="text/javascript" id="ds_reset_search_page_load">
        jQuery( document ).ready( function() {
            bp_ps_clear_form_elements();
        });
    </script>
    <?php
}
add_action( 'bp_before_directory_members_content', 'ds_members_search_reset_on_page_load' );

/**
 * 
 */
function ds_members_remove_create_events_page() {
    global $bp;
    bp_core_remove_subnav_item( 'mec-main', 'mec-main-created');
    bp_core_remove_nav_item( 'mec-events');


}
add_action( 'bp_setup_nav', 'ds_members_remove_create_events_page', 100 );

/**
 * Add an option to members filter to including seeking option.
 */
function ds_members_recruiting_option(){
	?>
    <option value="seeking"><?php _e( 'Squadron Seeking' ); ?></option>
    <?php
}
add_action( 'bp_members_directory_order_options', 'ds_members_recruiting_option' );

function ds_members_my_groups_filter( $querystring, $object ) {
    global $bp;

	$action = $bp->current_action;

    if ( $action == 'my-groups' && $object == 'groups' ) {

        $ds_ajax_querystring             = wp_parse_args( $querystring );
        //$querystring['scope']    = 'all';
        //$querystring['page']     = 1;
        //$querystring['per_page'] = '1';
        //$querystring['user_id']  = 0;
        //$querystring['group_type__not_in'] = 'leagues'; 

        /* this is your meta_query */
        $ds_ajax_querystring['meta_query'] = array(
            'relation' => 'OR',
            array(
                'key'     => '_ds_groups_excludeFromGroupLoop',
                'value'   => 0,
                //'type'    => 'numeric',
                'compare' => '='
            ),
            array(
                'key' => '_ds_groups_excludeFromGroupLoop',
                'compare' => 'NOT EXISTS', // works!
                'value' => '' // This is ignored, but is necessary...
            )
        );

        error_log( print_r( 'my array: ' . $ds_ajax_querystring, true ) );

        // using a filter will help other plugins to eventually extend this feature
    return apply_filters( 'ds_ajax_querystring', build_query($ds_ajax_querystring), $querystring );

    }

    return $querystring;

}
//add_filter('bp_ajax_querystring', 'ds_members_my_groups_filter', 30, 2 );

/**
 * 
 */
function ds_member_filter_ajax_querystring( $querystring = '', $object = '' ) {
 
    /* bp_ajax_querystring is also used by other components, so you need
    to check the object is groups, else simply return the querystring and stop the process */
    if( $object != 'members' )
        return $querystring;

	$ds_ajax_querystring = wp_parse_args( $querystring );

    error_log( print_r( $ds_ajax_querystring, true ) );

	// $metaQuery = array(
	// 	'relation'	=> 'AND'
	// );

    if ( isset( $_POST['location']) && $_POST['location'] != false ) {
		$location = sanitize_text_field( $_POST['location'] );

		$metaQuery[] = array(
			'key'		=> '_ds_member_country',
			'value'		=> $location,
			'compare'	=> 'LIKE'
		);
	}

	if ( isset( $_POST['platform']) && $_POST['platform'] != false ) {
		$platform = sanitize_text_field( $_POST['platform'] );

		$metaQuery[] = array(
			'key'		=> '_ds_member_platforms',
			'value'		=> $platform,
			'compare'	=> 'LIKE'
		);
	}

    if ( isset( $_POST['aircraft']) && $_POST['aircraft'] != false ) {
		$aircraft = sanitize_text_field( $_POST['aircraft'] );

		$metaQuery[] = array(
			'key'		=> '_ds_member_group_types',
			'value'		=> $aircraft,
			'compare'	=> 'LIKE'
		);
	}
 
    /* if your featured option has not been requested 
    simply return the querystring to stop the process
    */
    if ( $ds_ajax_querystring['type'] == 'seeking' ) {
		$metaQuery[] = array(
            'key'     => '_ds_member_group_seeking',
            'value'   => 'yes',
            'type'    => 'string',
            'compare' => '='
        );
	}

    $users = get_users( array( 'meta_query' => array( $metaQuery ), 'fields' => 'ids' ) );

    if ($users) {
        $ds_ajax_querystring['include'] = $users;
    } else {
        $ds_ajax_querystring['include'] = array( '-1');
    }
     
    // using a filter will help other plugins to eventually extend this feature
    return apply_filters( 'ds_member_ajax_querystring', build_query( $ds_ajax_querystring ), $querystring );
}
/* The groups loop uses bp_ajax_querystring( 'groups' ) to filter the groups depending on the selected option */
add_filter( 'bp_ajax_querystring', 'ds_member_filter_ajax_querystring', 999, 2 );

/**
 * START: Remove Login details page from the members menu and profile
 */
add_action( 'bp_actions', function() {
	if ( bp_is_active( 'xprofile' ) ) {
        $access        = bp_core_can_edit_settings();
        $slug          = bp_get_settings_slug();

        $args = array(
            'parent_slug'     => $slug,
            'subnav_slug'	  => 'notifications',
            'screen_function' => 'bp_settings_screen_notification',
            'user_has_access' => $access
            );
    
        bp_core_new_nav_default( $args );
	}
} );

add_action( 'bp_setup_admin_bar', function () {
    global $wp_admin_bar;
        if ( bp_use_wp_admin_bar() ) {
            $wp_admin_bar->remove_menu( 'my-account-settings-general' );
        }
}, 301 );

add_action( 'bp_actions', function() {
	if( bp_is_active( 'xprofile' ) ) {	
		bp_core_remove_subnav_item( 'settings', 'general' );
    }
} );

add_action( 'bp_late_include', function() {
	// Include Email screen code on Settings component and for default nav action (which is empty).
	if ( is_user_logged_in() && bp_is_settings_component() && ! bp_current_action() ) {
		require_once buddypress()->settings->path . 'bp-settings/screens/notifications.php';
	}
}, 11 );
/**
 * END: Remove Login Page
 */

function ds_members_get_previous_aliases_output( $userID = null ) {
    $output = bp_core_get_user_displayname( bp_loggedin_user_id() );

    if ( is_null( $userID ) ) {
        return $output;
    }

    global $wpdb;

    $previousCredentials = $wpdb->get_var( $wpdb->prepare( " SELECT prev_usernames FROM {$wpdb->prefix}ds_discord_oauth WHERE user_id = %d ", $userID ) );

    if ( $previousCredentials ) {
        $credentialsArray = explode( ',', $previousCredentials );

        $output = 'Previous Usernames...&#013;';

        foreach ($credentialsArray as $credential ) {
            $output .= $credential . '&#013;';
        }
    }

    return $output;

}

 function ds_span_displayname( $displayName ) {
    
    $nameArray = explode( '#', $displayName );
				
    $displayName = $nameArray[0] . '<span>#' . $nameArray[1] . '</span>';

    return $displayName;
 }

 function ds_members_my_aircraft_screen () {  
	add_action( 'bp_template_title', 'original_action_title' );  
	add_action( 'bp_template_content', 'original_action_content' );
	bp_core_load_template( apply_filters( 'bp_core_template_plugin', 'members/single/plugins' ) );
}  
function original_action_title() {  
	//echo 'original';  
}  
function original_action_content() { 
    if ( bp_action_variables() ) {
		bp_do_404();
		return;
	} 
	include_once ( get_stylesheet_directory() . "/buddypress/members/single/settings/my-aircraft.php" ); 
}

function ds_is_user_locations() {
    return (bool) ( bp_is_profile_component() && bp_is_current_action( 'member-location' ) );
}

function ds_is_user_platform_aircraft() {
    return (bool) ( bp_is_profile_component() && bp_is_current_action( 'platform-aircraft' ) );
}

function ds_is_user_social_media() {
    return (bool) ( bp_is_profile_component() && bp_is_current_action( 'social-media' ) );
}

/**
 * Add social networks button to the member header area.
 *
 * @return string
 */
function ds_get_user_social_networks_urls( $userID = null ) {

	global $wpdb;
	global $bp;

	$socialNetworks = array( 'discord', 'instagram', 'twitter', 'twitch', 'facebook', 'youtube' );
	$html = '';

	$userID = ( $userID !== null && $userID > 0 ) ? $userID : bp_displayed_user_id();

	// User Meta...
	$userNetworks = get_user_meta( $userID, '_ds_user_networks', true ); // Array containing info for user networks.
	$userNetworksVis = get_user_meta( $userID, '_ds_user_network_vis', true ); // Visibility level - public, loggedin or friends

	if ( isset( $userNetworks ) && is_array( $userNetworks ) ) {
		foreach( $userNetworks as $network => $networkURL ) {
			if ( in_array( $network, $socialNetworks ) ) {
				$html .= '<span class="' . $network . '"><a target="_blank" data-balloon-pos="up" data-balloon="' . ucfirst( $network ) . '" href="' . esc_url( $networkURL ) . '"></a></span>';
			}
		}
	}

	if ( $html !== '' ) {
		if ( bp_displayed_user_id() === bp_loggedin_user_id() ) {
			$html = '<div class="social-networks-wrap">' . $html . '</div>';
		} elseif ( 'public' === $userNetworksVis ) {
			$html = '<div class="social-networks-wrap">' . $html . '</div>';
		} elseif ( 'loggedin' === $userNetworksVis && is_user_logged_in() ) {
			$html = '<div class="social-networks-wrap">' . $html . '</div>';
		} elseif ( 'friends' === $userNetworksVis && is_user_logged_in() ) {
			$memberFriendStatus = friends_check_friendship_status( bp_loggedin_user_id(), bp_displayed_user_id() );

			if ( 'is_friend' === $memberFriendStatus ) {
				$html = '<div class="social-networks-wrap">' . $html . '</div>';
			} else {
				$html = '';
			}
		}
	}

	return apply_filters( 'ds_get_user_social_networks_urls', $html, $socialNetworks, $userNetworks );
}