<?php

class DS_Non_Editable_Field_Helper {
	
	/**
	 *
	 * @var DS_Non_Editable_Field_Helper 
	 */
	private static $instance;
	
	private function __construct () {
		
		//generate admin meta box
		add_action( 'xprofile_field_after_sidebarbox',  array( $this, 'admin_metabox' ) );
		
		add_action( 'xprofile_fields_saved_field', array( $this, 'update_setting' ) );
		
        //add_filter( 'bp_before_has_profile_parse_args', array( $this, 'exclude_fields_from_editing' ) );
        add_action( 'xprofile_data_before_save', array( $this, 'ds_member_on_profile_save' ) );
        add_action( 'bp_custom_profile_edit_fields_pre_visibility', array( $this, 'set_fields_to_readonly' ) );
	}
	
	/**
	 * 
	 * @return DS_Non_Editable_Field_Helper
	 */
	public static function get_instance() {
		
		if ( ! isset( self::$instance ) ) {
			self::$instance = new self();
		}

		return self::$instance;
		
    }

    /**
     * Prevent a user altering certain fields within their account, including <nickname>
     * 
     * @param object BP_XProfile_ProfileData
     */
    public function ds_member_on_profile_save( $data ) {

        //do not exclude the fields if the profile is being edited by super admin
		if ( is_super_admin() ) {
			return;//it does not matter whose profile he/she is editing
        }
        
        if (  ! bp_is_user_profile_edit() && ! $this->is_admin_edit_profile()  ) {
			return;
        }

        $user_id = false;
		
		if ( bp_is_user_profile_edit() ) {
			$user_id = bp_displayed_user_id ();
		} elseif ( $this->is_admin_edit_profile() ) {
			$user_id = $this->get_user_id();
        }

        //get non editable fields for this user
        $noneditable_fields = (array) bpne_field_helper()->get_non_editable_fields_for_user( $user_id );

        if ( in_array( $data->field_id, $noneditable_fields ) ) {
            $data->field_id = false;
        }

        return $data;
    }
    
    public function set_fields_to_readonly() {
        $field_id = bp_get_the_profile_field_id();
        $field_input_name = bp_get_the_profile_field_input_name();

        //do not exclude the fields if the profile is being edited by super admin
		if ( is_super_admin() ) {
			return;//it does not matter whose profile he/she is editing
        }
        
        if (  ! bp_is_user_profile_edit() && ! $this->is_admin_edit_profile()  ) {
			return;
        }
        
        $user_id = false;
		
		if ( bp_is_user_profile_edit() ) {
			$user_id = bp_displayed_user_id ();
		} elseif ( $this->is_admin_edit_profile() ) {
			$user_id = $this->get_user_id();
        }
        
        //get non editable fields for this user
        $noneditable_fields = (array) bpne_field_helper()->get_non_editable_fields_for_user( $user_id );

        if ( in_array( $field_id, $noneditable_fields ) ) {
            ?>
            <script type="text/javascript">
            jQuery(document).ready(function() {
                $('[id*="<?php echo $field_input_name; ?>"]').prop('disabled', true);
            })   
            </script>
            <?php
        }
    }
	
	/**
	 * Filter 'bp_before_has_profile_parse_args' and exclude the non editable fields
	 * @param array $r
	 * @return mixed
	 */
	public function exclude_fields_from_editing( $r ) {
		//error_log( print_r( $r, true ) );
		//do not exclude the fields if the profile is being edited by super admin
		
		if ( is_super_admin() ) {
			return $r;//it does not matter whose profile he/she is editing
		}

		//if we are not on edit profile, no need to restrict
		if (  ! bp_is_user_profile_edit() && ! $this->is_admin_edit_profile()  ) {
			return $r;
		}

		$user_id = false;
		
		if ( bp_is_user_profile_edit() ) {
			$user_id = bp_displayed_user_id ();
		} elseif ( $this->is_admin_edit_profile() ) {
			$user_id = $this->get_user_id();
		}
		//get non editable fields for this user
		$noneditable_fields = bpne_field_helper()->get_non_editable_fields_for_user( $user_id );
        //error_log( print_r( $noneditable_fields, true ) );
		$fields = isset( $r['exclude_fields'] )? $r['exclude_fields'] : array();

		if ( ! empty( $fields ) && ! is_array( $fields ) ) {
			$fields = explode ( ',', $fields );
		}

		$excluded_fields = array_merge( $fields, $noneditable_fields );

		$r['exclude_fields'] = $excluded_fields;

		return $r;
	}

	
	/**
	 * Show a metabox in the sitebar of single field edit/create screen in admin
	 * 
	 * @param type $field
	 */
	public function admin_metabox( $field ) {

		$can_edit = $this->get_field_editing_preference( $field->id );	
		?>	
			<div class="postbox">
				<h3><label for="member-can-edit"><?php _e( 'Is This Field Editable?', 'bp-non-editable-profile-fields' ); ?></label></h3>
				<div class="inside">
					<ul>
						<li>
							<input type="radio" id="member-can-edit-allowed" name="member-can-edit" value="yes" <?php checked( $can_edit, 'yes' ); ?> />
							<label for="member-can-edit-allowed"><?php _e( "Let Users change this field", 'bp-non-editable-profile-fields' ); ?></label>
						</li>
						<li>
							<input type="radio" id="member-can-edit-disabled" name="member-can-edit" value="no" <?php checked( $can_edit, 'no' ); ?> />
							<label for="member-can-edit-disabled"><?php _e( 'Do not allow a user to change this field .', 'bp-non-editable-profile-fields' ); ?></label>
						</li>
					</ul>
					<p><?php _e( "If you mark this field non editable, A user can only update it once.", 'bp-non-editable-profile-fields' );?></p>
				</div>
			</div>	
		<?php 
	
	}
	/**
	 * Update the editing preference when field is saved
	 * 
	 * @param type $field
	 */
	public function update_setting( $field ) {

		$member_can = $_POST['member-can-edit'];

		$this->update_field_editing_preference( $field->id, $member_can );
	}
	
	/**
	 *  Get all fields which can not be modified 
	 * 
	 * 
	 * @global type $wpdb
	 * @return type
	 */
	public function get_non_editable_fields() {
		global $wpdb;

		$table = buddypress()->profile->table_name_meta;

		$query = $wpdb->prepare( "SELECT object_id FROM {$table} WHERE object_type = %s AND meta_key= %s and meta_value = %s", 'field', 'member_can_edit', 'no'); 

		return $wpdb->get_col( $query );
	}
	
	/**
	 * Get all fields ids which can not be modified by the given user id
	 * 
	 *  We actualy query the fields which are marked as non editable and has data set by the user
	 * @global type $wpdb
	 * @param type $user_id
	 * @return type
	 */
	public function get_non_editable_fields_for_user( $user_id ) {
		
		global $wpdb;
		
		$non_editable_fields = $this->get_non_editable_fields();
		
		if ( empty( $non_editable_fields ) ) {
			return array();//no field
		}

		$id_list = join( ',', $non_editable_fields );
		//if we are here, there may be non editable fields
		
		//we will mark the subset uneditable which is presenbt in non editable list and also the user has saved
		
		$table = buddypress()->profile->table_name_data;
		$user_non_editable = $wpdb->get_col( $wpdb->prepare( "SELECT field_id FROM {$table} WHERE user_id = %d AND field_id IN ({$id_list})", $user_id ) );
		
		return $user_non_editable;
		
	}
	/**
	 * Can this field be modified
	 * 
	 * @param type $field_id
	 * @return boolean
	 */
	public function is_editable_field( $field_id ) {
		
		return ! $this->is_non_editable_field( $field_id );
	}
	
	/**
	 * Is this field non editable?
	 * 
	 * @param type $field_id
	 * @return boolean
	 */
	public function is_non_editable_field( $field_id ) {
		
		if ( $this->get_field_editing_preference( $field_id ) == 'no' ) {
			return true;
		}

		return false;
	}

	
	/**
	 * Get default Field editing Preference
	 * 
	 * @return string
	 */
	public function get_default_field_editing_preference() {
	
		return 'yes';//allow members to edit the fields by default
	}
	
	/**
	 * Get current preference for this field
	 * 
	 * @param type $field_id
	 * @return string( yes|no )
	 */
	public function get_field_editing_preference( $field_id ) {

		if ( ! $field_id ) {
			return $this->get_default_field_editing_preference();
		}

		$pref = bp_xprofile_get_meta( $field_id, 'field', 'member_can_edit', true );

		if ( ! $pref ) {
			$pref = $this->get_default_field_editing_preference();
		}

		return $pref;
	}
	
	/**
	 * Save preference to xprofile meta
	 * 
	 * @param type $field_id
	 * @param string $pref
	 * @return boolean
	 */
	public function update_field_editing_preference( $field_id, $pref ) {
	
		if ( ! $field_id ) {
			return false;
		}

		if ( ! $pref || ! in_array( $pref, array( 'yes', 'no' ) ) ) {
			$pref = 'yes';//if not given or not valid, set yes editing
		}

		return bp_xprofile_update_meta( $field_id, 'field', 'member_can_edit', $pref );

	}

	/**
	 * Is this admin edit profile page?
	 * 
	 * @global type $pagenow
	 * @return boolean
	 */
	public function is_admin_edit_profile() {
		
		global $pagenow;
		
		if ( is_admin() && $pagenow =='users.php' && isset( $_GET['page'] ) && $_GET['page'] == 'bp-profile-edit' ) {
			return true;
		}

		return false;
	}
	
	/**
	 * Get the user id fro the admin screen
	 * 
	 * @return type
	 */
	private function get_user_id() {
		
		$user_id = get_current_user_id();

		// We'll need a user ID when not on the user admin
		if ( ! empty( $_GET['user_id'] ) ) {
			$user_id = $_GET['user_id'];
		}

		return intval( $user_id );
	}
}
/**
 * 
 * @return DS_Non_Editable_Field_Helper
 */
function bpne_field_helper() {
	
	return DS_Non_Editable_Field_Helper::get_instance();
}
//initialize
bpne_field_helper();
