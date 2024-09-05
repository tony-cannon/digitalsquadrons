<?php
/**
 * BP Nouveau Group's edit settings template.
 *
 * @since BuddyPress 3.0.0
 * @version 3.1.0
 */
?>

<?php if ( bp_is_group_create() ) : ?>

	<h3 class="bp-screen-title creation-step-name">
		<?php esc_html_e( 'Select Group Settings', 'buddyboss' ); ?>
	</h3>

<?php else : ?>

	<h2 class="bp-screen-title">
		<?php esc_html_e( 'Change Group Settings', 'buddyboss' ); ?>
	</h2>

<?php endif; ?>

<div class="group-settings-selections">

	<fieldset class="radio group-recruiting">
		<legend><?php esc_html_e( 'Squadron Recruitment', 'buddyboss' ); ?></legend>

		<p class="group-setting-label" tabindex="0"><?php esc_html_e( 'Is this Squadron looking to recruit new pilots? This will enable pilots within the community to easily search for new squadrons to join!', 'buddyboss' ); ?></p>

		<div class="bp-radio-wrap">
			<input type="radio" name="group-recruitment-status" id="group-recruitment-status-yes" class="bs-styled-radio" value="yes"<?php ds_group_show_recruitment_status_setting( 'yes' ); ?>  />
			<label for="group-recruitment-status-yes"><?php esc_html_e( 'Yes', 'buddyboss' ); ?></label>
		</div>

		<div class="bp-radio-wrap">
			<input type="radio" name="group-recruitment-status" id="group-recruitment-status-no" class="bs-styled-radio" value="no"<?php ds_group_show_recruitment_status_setting( 'no' ); ?> />
			<label for="group-recruitment-status-no"><?php esc_html_e( 'No', 'buddyboss' ); ?></label>
		</div>

		<p><b>Please note: </b>Your Squadron Privacy Setting must be set to either 'Public' or 'Private' for this option to be correctly enabled.</p>
	</fieldset>

	<fieldset class="radio group-invitations">
		<legend><?php esc_html_e( 'Group Invitations', 'buddyboss' ); ?></legend>

		<p class="group-setting-label" tabindex="0"><?php esc_html_e( 'Which members of this group are allowed to invite others?', 'buddyboss' ); ?></p>

		<div class="bp-radio-wrap">
			<input type="radio" name="group-invite-status" id="group-invite-status-members" class="bs-styled-radio" value="members"<?php bp_group_show_invite_status_setting( 'members' ); ?> />
			<label for="group-invite-status-members"><?php esc_html_e( 'All group members', 'buddyboss' ); ?></label>
		</div>

		<div class="bp-radio-wrap">
			<input type="radio" name="group-invite-status" id="group-invite-status-mods" class="bs-styled-radio" value="mods"<?php bp_group_show_invite_status_setting( 'mods' ); ?> />
			<label for="group-invite-status-mods"><?php esc_html_e( 'Organizers and Moderators only', 'buddyboss' ); ?></label>
		</div>

		<div class="bp-radio-wrap">
			<input type="radio" name="group-invite-status" id="group-invite-status-admins" class="bs-styled-radio" value="admins"<?php bp_group_show_invite_status_setting( 'admins' ); ?> />
			<label for="group-invite-status-admins"><?php esc_html_e( 'Organizers only', 'buddyboss' ); ?></label>
		</div>
	</fieldset>

	<?php if ( bp_is_active( 'media' ) && bp_is_group_media_support_enabled() ) : ?>

		<fieldset class="radio group-media">
			<legend><?php esc_html_e( 'Group Photos', 'buddyboss' ); ?></legend>

			<p class="group-setting-label" tabindex="0"><?php esc_html_e( 'Which members of this group are allowed to manage photos?', 'buddyboss' ); ?></p>

			<div class="bp-radio-wrap">
				<input type="radio" name="group-media-status" id="group-media-status-members" class="bs-styled-radio" value="members"<?php bp_group_show_media_status_setting( 'members' ); ?> />
				<label for="group-media-status-members"><?php esc_html_e( 'All group members', 'buddyboss' ); ?></label>
			</div>

			<div class="bp-radio-wrap">
				<input type="radio" name="group-media-status" id="group-media-status-mods" class="bs-styled-radio" value="mods"<?php bp_group_show_media_status_setting( 'mods' ); ?> />
				<label for="group-media-status-mods"><?php esc_html_e( 'Organizers and Moderators only', 'buddyboss' ); ?></label>
			</div>

			<div class="bp-radio-wrap">
				<input type="radio" name="group-media-status" id="group-media-status-admins" class="bs-styled-radio" value="admins"<?php bp_group_show_media_status_setting( 'admins' ); ?> />
				<label for="group-media-status-admins"><?php esc_html_e( 'Organizers only', 'buddyboss' ); ?></label>
			</div>
		</fieldset>

	<?php endif; ?>

	<?php if ( bp_is_active( 'media' ) && bp_is_group_albums_support_enabled() ) : ?>

		<fieldset class="radio group-albums">
			<legend><?php esc_html_e( 'Group Albums', 'buddyboss' ); ?></legend>

			<p class="group-setting-label" tabindex="0"><?php esc_html_e( 'Which members of this group are allowed to manage albums?', 'buddyboss' ); ?></p>

			<div class="bp-radio-wrap">
				<input type="radio" name="group-album-status" id="group-albums-status-members" class="bs-styled-radio" value="members"<?php bp_group_show_albums_status_setting( 'members' ); ?> />
				<label for="group-albums-status-members"><?php esc_html_e( 'All group members', 'buddyboss' ); ?></label>
			</div>

			<div class="bp-radio-wrap">
				<input type="radio" name="group-album-status" id="group-albums-status-mods" class="bs-styled-radio" value="mods"<?php bp_group_show_albums_status_setting( 'mods' ); ?> />
				<label for="group-albums-status-mods"><?php esc_html_e( 'Organizers and Moderators only', 'buddyboss' ); ?></label>
			</div>

			<div class="bp-radio-wrap">
				<input type="radio" name="group-album-status" id="group-albums-status-admins" class="bs-styled-radio" value="admins"<?php bp_group_show_albums_status_setting( 'admins' ); ?> />
				<label for="group-albums-status-admins"><?php esc_html_e( 'Organizers only', 'buddyboss' ); ?></label>
			</div>
		</fieldset>

	<?php endif; ?>

	<?php if ( bp_is_active( 'media' ) && bp_is_group_document_support_enabled() ) : ?>

		<fieldset class="radio group-document">
			<legend><?php esc_html_e( 'Group Documents', 'buddyboss' ); ?></legend>

			<p class="group-setting-label" tabindex="0"><?php esc_html_e( 'Which members of this group are allowed to manage documents?', 'buddyboss' ); ?></p>

			<div class="bp-radio-wrap">
				<input type="radio" name="group-document-status" id="group-document-status-members" class="bs-styled-radio" value="members"<?php bp_group_show_document_status_setting( 'members' ); ?> />
				<label for="group-document-status-members"><?php esc_html_e( 'All group members', 'buddyboss' ); ?></label>
			</div>

			<div class="bp-radio-wrap">
				<input type="radio" name="group-document-status" id="group-document-status-mods" class="bs-styled-radio" value="mods"<?php bp_group_show_document_status_setting( 'mods' ); ?> />
				<label for="group-document-status-mods"><?php esc_html_e( 'Organizers and Moderators only', 'buddyboss' ); ?></label>
			</div>

			<div class="bp-radio-wrap">
				<input type="radio" name="group-document-status" id="group-document-status-admins" class="bs-styled-radio" value="admins"<?php bp_group_show_document_status_setting( 'admins' ); ?> />
				<label for="group-document-status-admins"><?php esc_html_e( 'Organizers only', 'buddyboss' ); ?></label>
			</div>
		</fieldset>

	<?php endif; ?>

	<?php if ( bp_is_active( 'messages' ) && true === bp_disable_group_messages() ) : ?>

		<fieldset class="radio group-messages">
			<legend><?php esc_html_e( 'Group Messages', 'buddyboss' ); ?></legend>

			<p class="group-setting-label" tabindex="0"><?php esc_html_e( 'Which members of this group are allowed to send group messages?', 'buddyboss' ); ?></p>

			<div class="bp-radio-wrap">
				<input type="radio" name="group-message-status" id="group-messages-status-members" class="bs-styled-radio" value="members"<?php bp_group_show_messages_status_setting( 'members' ); ?> />
				<label for="group-messages-status-members"><?php esc_html_e( 'All group members', 'buddyboss' ); ?></label>
			</div>

			<div class="bp-radio-wrap">
				<input type="radio" name="group-message-status" id="group-messages-status-mods" class="bs-styled-radio" value="mods"<?php bp_group_show_messages_status_setting( 'mods' ); ?> />
				<label for="group-messages-status-mods"><?php esc_html_e( 'Organizers and Moderators only', 'buddyboss' ); ?></label>
			</div>

			<div class="bp-radio-wrap">
				<input type="radio" name="group-message-status" id="group-messages-status-admins" class="bs-styled-radio" value="admins"<?php bp_group_show_messages_status_setting( 'admins' ); ?> />
				<label for="group-messages-status-admins"><?php esc_html_e( 'Organizers only', 'buddyboss' ); ?></label>
			</div>
		</fieldset>

	<?php endif; ?>



	<?php
	$group_types = bp_groups_get_group_types( array( 'show_in_create_screen' => true ), 'objects' );

	// Hide Group Types if none is selected in Users > Profile Type > E.g. (Students) > Allowed Group Types meta box.
	if ( false === bp_restrict_group_creation() && true === bp_member_type_enable_disable() ) {
		$get_all_registered_member_types = bp_get_active_member_types();
		if ( isset( $get_all_registered_member_types ) && ! empty( $get_all_registered_member_types ) ) {

			$current_user_member_type = bp_get_member_type( bp_loggedin_user_id() );
			if ( '' !== $current_user_member_type ) {
				$member_type_post_id = bp_member_type_post_by_type( $current_user_member_type );
				$include_group_type  = get_post_meta( $member_type_post_id, '_bp_member_type_enabled_group_type_create', true );
				if ( isset( $include_group_type ) && ! empty( $include_group_type ) && 'none' === $include_group_type[0] ) {
					$group_types = '';
				}
			}
		}
	}

	if ( bp_enable_group_hierarchies() ) :
		$current_parent_group_id = bp_get_parent_group_id();
		$possible_parent_groups  = bp_get_possible_parent_groups();
		?>

		<fieldset class="select group-parent">
			<legend><?php esc_html_e( 'Group Parent', 'buddyboss' ); ?></legend>
			<p class="group-setting-label" tabindex="0"><?php esc_html_e( 'Which group should be the parent of this group? (optional)', 'buddyboss' ); ?></p>
			<select id="bp-groups-parent" name="bp-groups-parent" autocomplete="off">
				<option value="0" <?php selected( 0, $current_parent_group_id ); ?>><?php _e( 'Select Parent', 'buddyboss' ); ?></option>
				<?php
				if ( $possible_parent_groups ) {

					foreach ( $possible_parent_groups as $possible_parent_group ) {
						?>
						<option value="<?php echo $possible_parent_group->id; ?>" <?php selected( $current_parent_group_id, $possible_parent_group->id ); ?>><?php echo esc_html( $possible_parent_group->name ); ?></option>
						<?php
					}
				}
				?>
			</select>
		</fieldset>
	<?php endif; ?>

</div><!-- // .group-settings-selections -->
