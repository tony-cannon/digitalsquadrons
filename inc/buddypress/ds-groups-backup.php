<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Do members of a specific Group Type (bp-group-type) have the option to remove themselves for the relative group?
 * 
 * @return bool
 */
function ds_group_member_opt_out() {
	global $groups_template;

	$optOut = false;

	$group =& $groups_template->group;
	$groupType = bp_groups_get_group_type( $group->id );

	$groupType = get_page_by_path( $groupType, OBJECT, 'bp-group-type' );
	if ( ! empty($groupType ) ) {
		$optOut = get_post_meta( $groupType->ID, '_ds_groups_users_opt_to_leave', true );
		$optOut = array_pop( $optOut );
		return empty( $optOut ) || $optOut !== 'true' ? false : true;
	}
}

/**
 * Enable Group Types to be publicly addressable from the frontend...
 */
function group_type_organizer() {
	//New arguments
    $group_type_organizer_args = get_post_type_object('bp-group-type'); // get the post type to modify
    $group_type_organizer_args->public = true;
    $group_type_organizer_args->publicly_queryable = true;
	$group_type_organizer_args->show_in_nav_menus = true;
	$group_type_organizer_args->hierarchical = true;
    //$group_type_organizer_args->exclude_from_search = false; // show in search result
 	//re-register the same post type includeing the new args
    register_post_type( 'bp-group-type', $group_type_organizer_args );
}
add_action( 'init', 'group_type_organizer', 100 );

/**
 * Check user_id and see if they own the current group?
 * 
 * @return bool
 */
function ds_group_is_owner() {
	global $groups_template;
	$owner = false;

	$group =& $groups_template->group;
	$userID = get_current_user_id();
	error_log( print_r( $userID, true ) );
	error_log( print_r( $group->creator_id, true ) );

	return $group->creator_id == get_current_user_id() ? true : false;
}

/**
 * Disable group deletion by non site admins.
 */
function ds_group_disable_group_delete_by_non_site_admin() {
 
    if ( ! bp_is_group() || is_super_admin() ) {
        return;
    }
 
    $parent = groups_get_current_group()->slug . '_manage';
    bp_core_remove_subnav_item( $parent, 'delete-group', 'groups' );
 
 
    // BuddyPress seems to have a bug, the same screen function is used for all the sub nav in group manage
    // so above code removes the callback, let us reattach it
    // if we don't , the admin redirect will not work.
    if ( function_exists( 'groups_screen_group_admin' ) ) {
        add_action( 'bp_screens', 'groups_screen_group_admin', 2 );
    }
 
}
add_action( 'groups_setup_nav', 'ds_group_disable_group_delete_by_non_site_admin' );

/**
 * We do not want users to be able to update/change the group name, they must remain the same. This hooks into save
 * loop for 'edit-details'.
 * 
 * @return null
 */
function ds_groups_prevent_post_name_update() {

	if ( 'edit-details' != bp_get_group_current_admin_tab() ) {
		return false;
	}

	if ( bp_is_item_admin() ) {

		$bp = buddypress();

		if ( isset( $_POST['save'] ) ) {

			// Check that the specified group_id exists, and that the current user can access it.
			$the_group = groups_get_group( array( 'group_id' => absint( $_POST['group-id'] ) ) );
			if ( $the_group->id === 0 || !$the_group->user_has_access ) {
				return false;
			}

			var_dump($the_group);

			$_POST['group-name'] = $the_group->name;

		}
	}

}
add_action('bp_screens', 'ds_groups_prevent_post_name_update', 9 );

/**
 * Save edit-details custom post data.
 */
function ds_groups_save_custom_edit_details() {

	$groupID = isset( $_POST['group-id'] ) ? $_POST['group-id'] : false;

	if ( $groupID) {

		/**
		 * Save software platform type.
		 */
		if ( isset( $_POST['group-software-platform']) && current_user_can( 'administrator' ) ) {
			// Update platform type...
			groups_update_groupmeta( $groupID, '_ds_software_platform', $_POST['group-software-platform'] );
		}

		/**
		 * Save Aircraft Types
		 */
		if ( isset( $_POST['group-primary-aircraft'] ) && current_user_can( 'administrator' ) ) {
			// Update the group type...
			groups_update_groupmeta( $groupID, '_ds_primary_aircraft', $_POST['group-primary-aircraft'] );
		}
		if ( isset( $_POST['group-secondary-aircraft'] ) ) {
			$primary = array( groups_get_groupmeta( $groupID, '_ds_primary_aircraft' ) );

			if ( $primary && is_array( $_POST['group-secondary-aircraft']) ) {
				
				$allTypes = array_reverse ( array_merge( $primary, $_POST['group-secondary-aircraft'] ) );

				$result = bp_groups_set_group_type( $groupID, $allTypes, false );
			}
			
		}

		/**
		 * Save Social Media.
		 */
		if ( isset( $_POST['group-website'] ) ) {
			groups_update_groupmeta( $groupID, '_ds_group_website', $_POST['group-website'] );
		}
		if ( isset( $_POST['group-discord'] ) ) {
			groups_update_groupmeta( $groupID, '_ds_group_discord', $_POST['group-discord'] );
		}
		if ( isset( $_POST['group-instagram'] ) ) {
			groups_update_groupmeta( $groupID, '_ds_group_instagram', $_POST['group-instagram'] );
		}
		if ( isset( $_POST['group-twitter'] ) ) {
			groups_update_groupmeta( $groupID, '_ds_group_twitter', $_POST['group-twitter'] );
		}
		if ( isset( $_POST['group-facebook'] ) ) {
			groups_update_groupmeta( $groupID, '_ds_group_facebook', $_POST['group-facebook'] );
		}
		if ( isset( $_POST['group-twitch'] ) ) {
			groups_update_groupmeta( $groupID, '_ds_group_twitch', $_POST['group-twitch'] );
		}
	}

	
}
add_action( 'groups_group_details_edited', 'ds_groups_save_custom_edit_details' );

/**
 * Change the group status icons for visibility, type, etc...
 */
function ds_group_change_status_meta_output( $type, $group ) {
	global $groups_template;

	//print_r($type);

	if ( empty( $group ) ) {
		$group =& $groups_template->group;
	}
	$groupPlatform = groups_get_groupmeta( $group->id, '_ds_software_platform' );
	$term = get_term('id', 'ds-software-platform' );
	$primaryType = groups_get_groupmeta( $group->id, '_ds_primary_aircraft' );
	$groupTypes = bp_groups_get_group_type( $group->id, false );
	$statusOutput = '';
	
	if ( !is_array( $groupTypes ) ) {
		// Well it should be! 
		error_log( print_r( 'Group Status is not an array for Group ID: ' . $group->id, true ) );
	} else {
		//$statusOutput = '<span class="group-visibility ' . $group->status . '">' . ucfirst( $group->status ) . '</span><span class="type-separator"> ~ </span>';

		$statusOutput .= '<span class="group-visibility platform">' . $term->name . '</span><span class="type-separator"> ~ </span>';

		//$statusOutput .= '<span style="width:100%;height:0;"></span>';

		if ( $primaryType ) {
			$primaryObj = bp_groups_get_group_type_object( $primaryType );

			$primaryLabel = isset( $primaryObj->labels['singular_name'] ) ? $primaryObj->labels['singular_name'] : '';

			$statusOutput .= '<span class="group-type" data-balloon-pos="up" data-balloon="Primary Aircraft">' . $primaryLabel . '</span><span class="type-separator"> ~ </span>';
		}

		foreach( $groupTypes as $k => $type ) {

			if ( $type !== $primaryType ) { // Don't repeat the primary...
				$post = get_page_by_path( $type, OBJECT, 'bp-group-type' );
				if ( $post ) {
					$statusOutput .= '<span class="group-type secondary">' . get_the_title( $post->ID ) . '</span><span class="type-separator"> ~ </span>';
				}
			}	
		}
	}

	return $statusOutput;
}
//add_filter( 'bp_get_group_type', 'ds_group_change_status_meta_output', 99, 2 );

/**
 * Change the subnav for subgroups in the group menus...
 */
function ds_groups_change_subgroup_subnav() {
	global $bp;
	//var_dump(bp_current_component());
	if ( bp_current_component() !== 'groups' ) {
		return;
	}

	$groupType = bp_groups_get_group_type($bp->groups->current_group->id);
	if ( $groupType !== null ) {
		$groupTypeObject = get_page_by_path( $groupType, OBJECT, 'bp-group-type' );
		$groupLabel = get_post_meta( $groupTypeObject->ID, 'ds_subgroups_label', true );

		if (isset($bp->groups->current_group->slug) && $bp->groups->current_group->slug == $bp->current_item) {
		$bp->bp_options_nav[$bp->groups->current_group->slug]['subgroups']['name'] = 'All ' . $groupLabel;
		}
	}
	
  }
  //add_action( 'bp_init', 'ds_groups_change_subgroup_subnav' );

/**
 * Display a custom taxonomy dropdown in admin
 * @author Mike Hemberger
 * @link http://thestizmedia.com/custom-post-type-filter-admin-custom-taxonomy/
 */
add_action('restrict_manage_posts', 'ds_filter_post_type_by_taxonomy');
function ds_filter_post_type_by_taxonomy() {
	global $typenow;
	$post_type = 'bp-group-type'; // change to your post type
	$taxonomy  = 'ds-software-platform'; // change to your taxonomy
	if ($typenow == $post_type) {
		$selected      = isset($_GET[$taxonomy]) ? $_GET[$taxonomy] : '';
		$info_taxonomy = get_taxonomy($taxonomy);
		wp_dropdown_categories(array(
			'show_option_all' => sprintf( __( 'Show all %s', 'textdomain' ), $info_taxonomy->label ),
			'taxonomy'        => $taxonomy,
			'name'            => $taxonomy,
			'orderby'         => 'name',
			'selected'        => $selected,
			'show_count'      => true,
			'hide_empty'      => true,
		));
	};
}

/**
 * 
 */
function ds_group_filter_ajax_querystring( $querystring = '', $object = '' ) {
 
    /* bp_ajax_querystring is also used by other components, so you need
    to check the object is groups, else simply return the querystring and stop the process */
    if( $object != 'groups' )
        return $querystring;
 
    // Let's rebuild the querystring as an array to ease the job
    // $defaults = array(
    //     'type'            => 'active',
    //     'action'          => 'active',
    //     'scope'           => 'all',
    //     'page'            => 1,
    //     'user_id'         => 0,
    //     'search_terms'    => '',
    //     'exclude'         => false,
    // );
 
    //$ds_ajax_querystring = wp_parse_args( $querystring, $defaults );

	$ds_ajax_querystring = wp_parse_args( $querystring );

	$metaQuery = array(
		'relation'	=> 'AND'
	);

	if ( isset( $_POST['location'] ) && $_POST['location'] != false ) {
		$location = sanitize_text_field( $_POST['location']);

		$metaQuery[] = array(
			'key'		=> '_ds_group_country',
			'value'		=> $location,
			'compare'	=> '='
		);
	}

	if ( isset( $_POST['primary'] ) && $_POST['primary'] != false ) {
		$type = sanitize_text_field( $_POST['group_type'] );

		$metaQuery[] = array(
			'key'		=> '_ds_primary_aircraft',
			'value'		=> $type,
			'compare'	=> 'LIKE'
		);
	}

	if ( isset( $_POST['platform']) && $_POST['platform'] != false ) {
		$platform = sanitize_text_field( $_POST['platform'] );

		$metaQuery[] = array(
			'key'		=> '_ds_software_platform',
			'value'		=> $platform,
			'compare'	=> '='
		);
	}
 
    /* if your featured option has not been requested 
    simply return the querystring to stop the process
    */
    if ( $ds_ajax_querystring['type'] == 'recruiting' ) {
		$metaQuery[] = array(
            'key'     => '_ds_group_recruitment_option',
            'value'   => 'yes',
            'type'    => 'string',
            'compare' => '='
        );
	}
 
    /* this is your meta_query */
    $ds_ajax_querystring['meta_query'][] = $metaQuery;
     
    // using a filter will help other plugins to eventually extend this feature
    return apply_filters( 'ds_ajax_querystring', build_query( $ds_ajax_querystring ), $querystring );
}
/* The groups loop uses bp_ajax_querystring( 'groups' ) to filter the groups depending on the selected option */
add_filter( 'bp_ajax_querystring', 'ds_group_filter_ajax_querystring', 200, 2 );

function ds_parse_args_test( $values ) {
	error_log( print_r($values, true ) );
	return $values;
}
//add_filter( 'bp_after_has_groups_parse_args', 'ds_parse_args_test' );

/**
 * 
 */
function ds_group_show_recruitment_status_setting( $setting, $group = false ) {
	
	if ( ! $group && is_object( $group ) ) {
		$group_id = isset( $group->id ) ? $group->id : false;
	}

	$recruitment_status = ds_group_get_recruitment_status( $group_id );

	if ( $setting == $recruitment_status ) {
		echo ' checked="checked"';
	}
}

/**
 * 
 */
function ds_group_get_recruitment_status( $group_id = false ) {
	global $groups_template;

	if ( ! $group_id ) {
		$bp = buddypress();

		if ( isset( $bp->groups->current_group->id ) ) {
			// Default to the current group first.
			$group_id = $bp->groups->current_group->id;
		} elseif ( isset( $groups_template->group->id ) ) {
			// Then see if we're in the loop.
			$group_id = $groups_template->group->id;
		} else {
			return false;
		}
	}

	$recruitment_status = groups_get_groupmeta( $group_id, '_ds_group_recruitment_option' );

	// Backward compatibility. When 'recruitment_status' is not set, fall back to a default value.
	if ( ! $recruitment_status ) {
		$recruitment_status = apply_filters( 'ds_group_recruitment_status_fallback', 'no' );
	}

	// If privacy is set to hidden - group cannot be shown publicly as recruiting.
	if ( bp_get_new_group_status() == 'hidden' || $_POST['group-status'] == 'hidden' ) {
		$recruitment_status = apply_filters( 'ds_group_recruitment_status_hidden', 'no' );
	}

	/**
	 * Filters the recruitment status of a group.
	 *
	 * Recruitment status in this case means wherther the group are looking to recruit new members.
	 *
	 * @since BuddyPress 1.5.0
	 *
	 * @param string $recruitment_status YES/NO.
	 * @param int    $group_id      ID of the group whose status is being checked.
	 */
	return apply_filters( 'bp_group_get_recruitment_status', $recruitment_status, $group_id );
}

/**
 * 
 */
function ds_group_save_recruitment_status( $group_id ) {
	if ( isset ($_POST['group-recruitment-status'] ) ) {
		if ( !in_array( $_POST['group-recruitment-status'], array( 'yes', 'no' ) ) ) {
			//post message.
			bp_core_add_message( __( 'There was an error updating group settings. Please try again.', 'buddyboss' ), 'error' );
		} else {
			if ( ($_POST['group-recruitment-status'] == 'yes' && bp_get_new_group_status() == 'hidden' ) || ( $_POST['group-recruitment-status'] == 'yes' && !in_array( $_POST['group-status'], array( 'public', 'private' ) ) ) ) {
				bp_core_add_message( __( 'To enable the recruitment option your squadron cannot be hidden - please check privacy settings.', 'buddyboss' ), 'error' );
			} else {
				groups_update_groupmeta( $group_id, '_ds_group_recruitment_option', $_POST['group-recruitment-status'] );
			}
			
		}
	}
}
add_action( 'groups_group_settings_edited', 'ds_group_save_recruitment_status', 999 );

/**
 * Add a recruiting filter option to the Groups search page...
 */
function ds_group_recruiting_option() {
    ?>
    <option value="recruiting"><?php _e( 'Currently Recruiting' ); ?></option>
    <?php
}
/* finally you create your options in the different select boxes */
// you need to do it for the Groups directory
add_action( 'bp_groups_directory_order_options', 'ds_group_recruiting_option' );
add_action( 'bp_member_group_order_options', 'ds_group_recruiting_option' );

/**
 * Get Group Meta based upon name...
 */
function ds_get_group_meta( $metaName = false ) {
	if ( $metaName ) {
		global $bp;

		$groupID = $bp->groups->current_group->id;

		$metaValue = groups_get_groupmeta( $groupID, $metaName );

		return $metaValue;	
	}
}

/**
 * 
 */
function ds_set_group_location() {
	global $bp;

	$groupID = $bp->groups->current_group->id;

	$groupCountry = groups_get_groupmeta( $groupID, '_ds_group_country') ?: false;
	$groupState = groups_get_groupmeta( $groupID, '_ds_group_state') ?: false;
	//$groupCity = groups_get_groupmeta( $groupID, '_ds_group_city') ?: false;

	?>

	<label for="group-region">Squadron Location</label>
	
	<div class="dsSquadronLocationInfo" style="margin-bottom: 15px;">
		<label for="group-country">Country</label>
		<?php echo ds_get_region_dropdown('dsCountry', false, $groupCountry ) ?> 
		<p>required.</p>
	</div>
	<div class="dsSquadronLocationInfo" style="margin-bottom: 15px;">
		<label for="group-state">Region</label>
		<select id="dsState" ds-attr-id="<?php echo $groupState ?>" name="ds_squadron_state">
			<option value="0">Select Region</option>
		</select>
		
	</div>
	<?php
}
//add_action('groups_custom_group_fields_editable', 'ds_set_group_location', 100);

/**
 * Get states based upon country.
 */
function ds_get_states() {
	check_ajax_referer( 'ds_ajax_nonce', 'nonce_ajax' );	
	
	global $wpdb;
	
	if(isset($_POST["cnt"])) {
		$cid = sanitize_text_field($_POST["cnt"]);
	}
	
	$states = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM ".$wpdb->base_prefix."state where country_id=%1s order by name asc", $cid) );
	
	echo json_encode($states);
	wp_die();
}
add_action('wp_ajax_ds_groups_get_states','ds_get_states');
add_action("wp_ajax_nopriv_ds_groups_get_states", "ds_get_states");

function ds_get_cities() {
	check_ajax_referer( 'ds_ajax_nonce', 'nonce_ajax' );
	global $wpdb;
	
	if ( isset( $_POST["sid"] ) ) {
		$sid = sanitize_text_field( $_POST["sid"] );
	}

	$cities = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM ".$wpdb->base_prefix."city where state_id=%1s order by name asc", $sid));

	echo json_encode($cities);
	wp_die();
}
add_action('wp_ajax_ds_groups_get_city','ds_get_cities');
add_action("wp_ajax_nopriv_ds_groups_get_city", "ds_get_cities");

/**
 * 
 */
function ds_get_region_dropdown( $id, $filter = false, $selected = null, $placeHolder = 'All Countries' ) {
	global $wpdb;

	$priorityCountries = array(14,39,45,75,107,182,204,207,232,233);
	$filter = $filter ? 'data-bp-group-location-filter="groups"' : '';

	if ( is_array( $selected ) ) {
		$selected = array_pop( $selected );
	}

	$html = '<select class="ds-country-selector dsCountry' . $id . '" id="' . $id . '" name="' . $id . '" ' . $filter . '>';
	$html .= '<option value="0">' . $placeHolder .'</option>';

	$tbl = 'countries';	
	
	$countries = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM ".$wpdb->base_prefix."%1s", $tbl) );
	foreach( $countries as $cnt ) {
		if (in_array( $cnt->id, $priorityCountries ) ) {
			if ( $selected !== null && $cnt->id == $selected ) {
				//selected option...
				$html.="<option value='".esc_html($cnt->id)."' data-id='".$cnt->id."' selected>".esc_html($cnt->name)."</option>";
			} else {
				$html.="<option value='".esc_html($cnt->id)."' data-id='".$cnt->id."'>".esc_html($cnt->name)."</option>";
			}
		}
	}

	$html .= '<option disabled>──────────</option>';

	foreach( $countries as $cnt ) {
		if (!in_array( $cnt->id, $priorityCountries ) ) {
			if ( $selected !== null && $cnt->id == $selected ) {
				//selected option...
				$html.="<option value='".esc_html($cnt->id)."' data-id='".$cnt->id."' selected>".esc_html($cnt->name)."</option>";
			} else {
				$html.="<option value='".esc_html($cnt->id)."' data-id='".$cnt->id."'>".esc_html($cnt->name)."</option>";
			}
		}
	}


	
	$html.='</select>';
	
	return $html;
}

function ds_get_filter_country_dropdown( $id, $filterType = 'groups' ) {
	$html = '';
	$html = '<select class="ds-country-selector dsCountry' . $id . '" id="' . $id . '" name="' . $id . '" data-bp-' . rtrim( $filterType, 's' ) . '-location-filter="' . $filterType . '">';
	$html .= '<option value>All Countries</option>';

	$html .= ds_get_country_options();
	$html.='</select>';

	return $html;

}

function ds_get_country_options( $selected = null ) {
	global $wpdb;

	$html = '';
	$priorityCountries = array(14,39,45,75,107,182,204,207,232,233);
	$tbl = 'countries';	
	
	$countries = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM ".$wpdb->base_prefix."%1s", $tbl) );
	foreach( $countries as $cnt ) {
		if (in_array( $cnt->id, $priorityCountries ) ) {
			if ( $selected !== null && $cnt->id == $selected ) {
				//selected option...
				$html.="<option value='".esc_html($cnt->id)."' data-id='".$cnt->id."' selected>".esc_html($cnt->name)."</option>";
			} else {
				$html.="<option value='".esc_html($cnt->id)."' data-id='".$cnt->id."'>".esc_html($cnt->name)."</option>";
			}
		}
	}

	$html .= '<option disabled>──────────</option>';

	foreach( $countries as $cnt ) {
		if (!in_array( $cnt->id, $priorityCountries ) ) {
			if ( $selected !== null && $cnt->id == $selected ) {
				//selected option...
				$html.="<option value='".esc_html($cnt->id)."' data-id='".$cnt->id."' selected>".esc_html($cnt->name)."</option>";
			} else {
				$html.="<option value='".esc_html($cnt->id)."' data-id='".$cnt->id."'>".esc_html($cnt->name)."</option>";
			}
		}
	}

	return $html;
}

/**
 * Saving location fields from Group Admin->Manage->location page.
 * @global type $bp
 * @param array $locationDetails
 */

function ds_group_update_location( $locationDetails ) {
    
	if ( !is_array( $locationDetails) ) {
		return false;
	}

    if ( empty( $locationDetails['group_id'] ) ) {
        global $bp;
        $groupID = $bp->groups->current_group->id;
    } else {
		$groupID = $locationDetails['group_id'];
	}

    if ( $locationDetails['country'] ) {
        $result = groups_update_groupmeta( $groupID, '_ds_group_country', $locationDetails['country'] );
    }
    if ( $locationDetails['state'] !== 0 ) {
        $result = groups_update_groupmeta( $groupID, '_ds_group_state', $locationDetails['state'] );
    }

	if ( $result ) {
		return true;
	}
}

function ds_groups_filter_get_types_from_platform() {
	$output = '';

	if ( isset( $_POST['platform'] ) ) {

		$output .= '<option value>'. __( 'All Types', 'buddyboss' ) . '</option>';
		
		$platformSlug = sanitize_text_field( $_POST['platform'] );

		$term = get_term_by( 'slug', $platformSlug, 'ds-software-platform' ); 

		$types = get_posts( array (
					'numberposts' => -1,   // -1 returns all posts
					'post_type' => 'bp-group-type',
					'orderby' => 'title',
					'order' => 'ASC',
					'tax_query' => array(
						array(
							'taxonomy' => 'ds-software-platform',
							'terms' => $term->term_id,
							'include_children' => false // Remove if you need posts from term 7 child terms
						),
					),
				));
		
		foreach ($types as $type) {
			$output .= '<option value="' . $type->post_name . '">' . $type->post_title . '</option>';
		}

		//$output['types'] = $types;

		wp_send_json_success( $output );
            
	}

	$error = new WP_Error( '001', 'No user information was retrieved.', 'Some information' );
 
	wp_send_json_error( $error );
}
add_action( 'wp_ajax_ds_groups_filter_get_types_from_platform', 'ds_groups_filter_get_types_from_platform' );
add_action( 'wp_ajax_nopriv_ds_groups_filter_get_types_from_platform', 'ds_groups_filter_get_types_from_platform' );

/**
 * 
 */
function ds_group_update_rules( $rules = null ) {
	global $bp;

	$groupID = $bp->groups->current_group->id;

	if ( !is_null( $rules) ) {
		$result = groups_update_groupmeta( $groupID, '_ds_group_rules', $rules  );
	}

	return $result;
}

/**
 * 
 */
function get_location_from_id( $id, $type ) {
	global $wpdb;

	$table = $wpdb->base_prefix . $type;
	$location = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM ".$table." where id=%1s", $id) );

	return $location->name;
}

function ds_group_description_read_more_button( $desc, $group ) {
	
	if ( 'groups' != bp_current_component() ) {
        return apply_filters( 'bplp_display_group_address_info', $desc, $group );
    }

	$desc .= '<p class="read-more"><a href="#" class="button small">Read More</a></p>';

	return apply_filters( 'ds_group_description_read_more_button_filter', $desc, $group );
}
//add_filter( 'bp_get_group_description', 'ds_group_description_read_more_button', 99, 2);

function ds_groups_get_social_media_meta( $groupID ) {

	$mediaOptions = array( 'website', 'discord', 'instagram', 'twitter', 'facebook', 'twitch' );

	$linksArray = array();

	foreach( $mediaOptions as $option ) {
		$meta = groups_get_groupmeta( $groupID, '_ds_group_' . $option );

		$linksArray[$option] = $meta;
	}

	return $linksArray;

}

/**
 * 
 */
function ds_testeroony() {
	
    $group_id = bp_get_current_group_id();

	?>
	<div class="group-location-links">
	<?php 

	/**
	 * Start with the physical location...
	 */

	//$city = groups_get_groupmeta($group_id,'_ds_group_city',true) ? groups_get_groupmeta($group_id,'_ds_group_city',true) : '';
	$state = groups_get_groupmeta($group_id,'_ds_group_state',true) ? groups_get_groupmeta($group_id,'_ds_group_state',true) : '';
	$country = groups_get_groupmeta($group_id,'_ds_group_country',true) ? groups_get_groupmeta($group_id,'_ds_group_country',true) : '';

	//$city = ( $city !== '' ) ? get_location_from_id( $city, 'city' ). ', ' : '';
	$state = ( $state !== '' ) ? get_location_from_id( $state, 'state') . ', ' : '';
	$country = ( $country !== '' ) ? get_location_from_id( $country, 'countries' ) : '';

        $address = $state.$country;
        $address = rtrim($address);
        $address = rtrim($address,',');

	if ( $address ) {
		?>
		<div class="group-location"><span class="ds-location-flag"><i class="fa fa-map-marker" aria-hidden="true"></i><?php echo $address; ?></span></div>
		<?php
	}

	/**
	 * Create Social media links...
	 * 
	 */
	$linksArray = ds_groups_get_social_media_meta( $group_id );
	$linksOutput = '';

	if ( !empty( $linksArray ) ) {

		?>

		<div class="group-socials">
				<?php foreach ($linksArray as $key => $url ) : ?>
				<?php $cssKey = $key !== 'website' ? $key : 'connectdevelop'; ?>
				<?php if ( $url !== '' ) : ?>
					<span class="social <?php echo $key; ?>"><a href="<?php echo $url; ?>" target="_blank" data-bp-tooltip-pos="up" data-bp-tooltip="<?php echo ucfirst( $key ); ?>"><i class="fab fa-<?php echo $cssKey; ?>"></i></a></span>
				<?php endif; ?>
				<?php endforeach; ?>
		</div>
		</div>
		<?php
	}

}
add_action( 'ds_group_header_location_and_links', 'ds_testeroony');

/**
 * Add additional menu items to group Nav, including...
 * 
 * 1. Location.
 * 2. Rules and Rules Edit Page (Manage Tab).
 */
function ds_setup_group_nav() {
    global $bp; 

    /* Add some group subnav items */
    $user_access = false;
    $group_link = '';
    $sub_nav = array();

    if ( bp_is_active( 'groups' ) && !empty( $bp->groups->current_group ) ) {

        $group_link = bp_get_group_permalink( $bp->groups->current_group );
        //$group_link = $bp->root_domain . '/' . bp_get_groups_root_slug() . '/' . $bp->groups->current_group->slug . '/';
        $admin_link = trailingslashit( $group_link . 'admin' );
        $user_access = $bp->groups->current_group->user_has_access;

        // Common params to all nav items.
        $default_params = array(
            'parent_url'        => $admin_link,
            'parent_slug'       => $bp->groups->current_group->slug . '_manage',
            'screen_function'   => 'groups_screen_group_admin',
            'user_has_access'   => $user_access,
            'show_in_admin_bar' => true,
        );

        $sub_nav[] = array_merge(
            array(
                'name'     => __( 'Location', 'buddyboss' ),
                'slug'     => 'group-location',
                'position' => 6,
            ),
            $default_params
        );

        $sub_nav[] = array_merge(
            array(
                'name'     => __( 'Rules', 'buddyboss' ),
                'slug'     => 'edit-rules',
                'position' => 32,
            ),
            $default_params
        );

        foreach ( $sub_nav as $nav ) {
            bp_core_new_subnav_item( $nav, 'groups' );
        }
        
        bp_core_new_subnav_item( array( 
            'name' => __( 'Rules'),
            'slug' => 'group-rules', 
            'parent_url' => $group_link, 
            'parent_slug' => $bp->groups->current_group->slug,
            'screen_function' => 'ds_group_screen_rules', 
            'position' => 60, 
            'user_has_access' => $user_access, 
            'item_css_id' => 'ds-group-screen-rules' 
        ));

		// bp_core_new_subnav_item( array( 
        //     'name' => __( 'Events'),
        //     'slug' => 'group-events', 
        //     'parent_url' => $group_link, 
        //     'parent_slug' => $bp->groups->current_group->slug,
        //     'screen_function' => 'ds_group_screen_events', 
        //     'position' => 25, 
        //     'user_has_access' => $user_access, 
        //     'item_css_id' => 'ds-group-screen-events' 
        // ));

		bp_core_remove_subnav_item( $bp->groups->current_group->slug, 'activity' );
    }

	//print_r( $bp->groups );
}
add_action( 'bp_init', 'ds_setup_group_nav', 99 );

function ds_group_screen_rules() {
    //add_action('bp_template_title', 'ds_group_rules_show_screen_title');
    add_action('bp_template_content', 'ds_group_rules_show_screen_content');

    $templates = array('groups/single/plugins.php', 'plugin-template.php');
    if (strstr(locate_template($templates), 'groups/single/plugins.php')) {
        bp_core_load_template(apply_filters('bp_core_template_plugin', 'groups/single/plugins'));
    } else {
        bp_core_load_template(apply_filters('bp_core_template_plugin', 'plugin-template'));
    }

}

function ds_group_screen_events() {
    //add_action('bp_template_title', 'ds_group_rules_show_screen_title');
    add_action('bp_template_content', 'ds_group_events_show_screen_content');

    $templates = array('groups/single/plugins.php', 'plugin-template.php');
    if (strstr(locate_template($templates), 'groups/single/plugins.php')) {
        bp_core_load_template(apply_filters('bp_core_template_plugin', 'groups/single/plugins'));
    } else {
        bp_core_load_template(apply_filters('bp_core_template_plugin', 'plugin-template'));
    }

}

function ds_group_events_show_screen_content() {
    //check the current action...
	if ( bp_current_action() !== 'group-events' ) {
		return;
	}

	$template = 'groups/single/admin/' . bp_current_action();

	bp_get_template_part( $template );

}

function ds_group_rules_show_screen_content() {

	$rules = ds_get_group_meta( '_ds_group_rules' );
	
	if ( ! $rules ) {
		$rules = "It’s your Squadron: make up your own rules.";
	}
    
	?>
	<div class="bb-profile-grid bb-grid">
		<div id="item-body" class="item-body">
			<div class="bb-media-container group-media">
				<h2 class="bb-title">Squadron Rules</h2>
				<p><?php echo $rules; ?></p>
			</div>
		</div>
	</div>
	<?php

}

/**
 * We need to setup the template for any custom pages within Group Admin. 
 * 
 * wp-content/plugins/buddyboss-platform/bp-templates/bp-nouveau/includes/groups/template-tags.php : bp_nouveau_group_manage_screen()
 */
function ds_groups_admin_manage_location() {
	//get the current action...
	$action = bp_action_variable( 0 );

	if ( $action === 'group-location' ) {

		$template = 'groups/single/admin/' . $action;

		bp_get_template_part( $template );

		wp_nonce_field( 'groups_edit_group_location' );

		//printf( '<input type="hidden" name="group-id" id="group-id" value="%s" />', esc_attr( bp_get_group_id() ) );

		$output = sprintf( '<p><input type="submit" value="%s" id="save" name="save" /></p>', esc_attr__( 'Save Changes', 'buddyboss' ) );

		echo $output;

	}
}
add_action( 'groups_custom_edit_steps', 'ds_groups_admin_manage_location');

/**
 * We need to setup the template for any custom pages within Group Admin. 
 * 
 * wp-content/plugins/buddyboss-platform/bp-templates/bp-nouveau/includes/groups/template-tags.php : bp_nouveau_group_manage_screen()
 */
function ds_groups_admin_manage_rules() {
	//get the current action...
	$action = bp_action_variable( 0 );

	if ( $action === 'edit-rules' ) {

		$template = 'groups/single/admin/' . $action;

		bp_get_template_part( $template );

		wp_nonce_field( 'groups_edit_group_edit_rules' );

		//printf( '<input type="hidden" name="group-id" id="group-id" value="%s" />', esc_attr( bp_get_group_id() ) );

		$output = sprintf( '<p><input type="submit" value="%s" id="save" name="save" /></p>', esc_attr__( 'Save Changes', 'buddyboss' ) );

		echo $output;

	}
}
add_action( 'groups_custom_edit_steps', 'ds_groups_admin_manage_rules');

/**
 * Remove 'Leave Group' button from Group Header if user is the group creator!
 * 
 * @var array $buttons
 */
add_filter( 'bp_get_group_join_button', function( $buttons = array() ) {
	if ( ds_group_is_owner() && is_array( $buttons ) ) {
		unset($buttons);

		return;
	}
	return $buttons;
} );

/**
 * We want to create a forum automatically when creating a aircraft type to streamline working processes.
 * 
 * @var integer $postID - ID for the bp-group-type
 * @var object $post - for the above
 * @var bool $update - as far as aware, does not work! 
 */
function ds_group_types_on_save( $postID, $post, $update ) {
    $postType = get_post_type($postID);

        if ( $postType === 'bp-group-type' ){

            //this to preventtwice insert by save_post action :)
            if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE ) {
                return;
            } else {

                //check if new post so insert
                if( strpos( wp_get_raw_referer(), 'post-new' ) > 0 ) {

					$presetForumID = get_post_meta( $postID, '_ds_squadron_type_forum', true );

					if ( $presetForumID === '' ) {

						/**
						 * 
						 */
						$forumID = wp_insert_post( array(
							'post_title'		=>	$post->post_title,
							'post_content'		=>	$post->post_content,
							'post_status'		=> 	'publish',
							'post_type'			=> 	'forum',
							'post_parent'		=>	get_post_meta( $postID, 'ds_groups_type_forum_parent_id', true )
						));

						update_post_meta( $postID, '_ds_squadron_type_forum', $forumID );

					}

                }else{

                    // This is an update so ignore...
                }

				// This will be done, regardless of new or update...

				/**
				 * TODO: update forum title, etc...
				 */

				$forumID = (int) reset( get_post_meta( $postID, '_ds_squadron_type_forum', true ) );

				if ( $forumID !== '' && is_int( $forumID ) ) {
					// Copy Group Type terms over to the forum, we want to keep them the same...
					$postDeveloperTerm = wp_get_post_terms( $postID, 'ds-software-developer', array( 'fields' => 'ids' ) );
					if ( ! is_wp_error( $postDeveloperTerm ) ) {
						wp_set_object_terms( $forumID, $postDeveloperTerm, 'ds-software-developer' );
					}

					$postPlatformTerm = wp_get_post_terms( $postID, 'ds-software-platform', array( 'fields' => 'ids' ) );
					if ( ! is_wp_error( $postPlatformTerm ) ) {
						wp_set_object_terms( $forumID, $postPlatformTerm, 'ds-software-platform' );
					}

					set_post_thumbnail( $forumID, get_post_meta( $postID, 'ds_groups_type_forum_featured_image', true ) );
				}
            }

        }
}
add_action( 'save_post', 'ds_group_types_on_save', 999, 3 );