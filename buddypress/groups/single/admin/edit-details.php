<?php
/**
 * BP Nouveau Group's edit details template.
 *
 * @since BuddyPress 3.0.0
 * @version 3.1.0
 */
?>

<?php if ( bp_is_group_create() ) : ?>

	<h3 class="bp-screen-title creation-step-name">
		<?php esc_html_e( 'Enter Group Name &amp; Description', 'buddyboss' ); ?>
	</h3>

<?php else : ?>

	<h2 class="bp-screen-title">
		<?php esc_html_e( 'Edit Group Name &amp; Description', 'buddyboss' ); ?>
	</h2>

<?php endif; ?>

<label for="group-name"><?php esc_html_e( 'Group Name (required)', 'buddyboss' ); ?></label>
<input type="text" name="group-name" id="group-name" value="<?php bp_is_group_create() ? bp_new_group_name() : bp_group_name(); ?>" aria-required="true" disabled />

<?php 
	$groupID = bp_get_current_group_id();

	$platforms = get_categories( array(
					'taxonomy'	=> 'ds-software-platform',
					'hide_empty'	=> false
	) );

	$groupPlatform = groups_get_groupmeta( $groupID, '_ds_software_platform' );

	$types = get_posts( array (
		'numberposts' => -1,   // -1 returns all posts
		'post_type' => 'bp-group-type',
		'orderby' => 'title',
		'order' => 'ASC',
		'tax_query' => array(
			array(
				'taxonomy' => 'ds-software-platform',
				'field'	=> 'slug',
				'terms' => $groupPlatform,
				'include_children' => false // Remove if you need posts from term 7 child terms
			),
		),
	));

	$primaryAircraft = groups_get_groupmeta( $groupID, '_ds_primary_aircraft' );

	$typesArray = bp_groups_get_group_type( $groupID, false );

	error_log( print_r( $typesArray, true ) );

?>

<?php if ( current_user_can('administrator') ) : ?>
	<label for="group-software-platform">Software Platform (ADMIN ONLY)</label>
	<?php if ( ! empty( $platforms ) && ! is_wp_error( $platforms ) ) : ?>
		<select id="group-software-platform" name="group-software-platform" class="ds-platform-select" data-bp-group-platform-filter="groups">
			<option value>Select Platform...</option>
			<?php foreach ( $platforms as $platform ) : ?>
			<option value="<?php echo esc_attr( $platform->slug ); ?>"><?php echo esc_html( $platform->name ); ?></option>
			<?php endforeach; ?>
		</select>
	<?php endif; ?>

	<label for="group-primary-aircraft">Primary Aircraft (ADMIN ONLY)</label>
	<select id="group-primary-aircraft" name="group-primary-aircraft" class="group-primary-aircraft">
		<option value>Select Primary Aircraft...</option>
	<?php foreach ( $types as $type ) : ?>
		<option value="<?php echo $type->post_name; ?>"><?php echo $type->post_title; ?></option>
	<?php endforeach; ?>
	</select>
	<script>
		jQuery(function ($) {
			$('#group-software-platform option[value="<?php echo $groupPlatform ?>"]').attr("selected", "selected");
			$('#group-primary-aircraft option[value="<?php echo $primaryAircraft ?>"]').attr("selected", "selected");
		});
	</script>

<?php endif; ?>

	<label for="group-secondary-aircraft">Supplementary Aircraft</label>
	<select id="group-secondary-aircraft" name="group-secondary-aircraft[]" class="group-secondary-aircraft">
	<?php foreach ( $types as $type ) : ?>
		<?php if ( $type->post_name !== $primaryAircraft ) : ?>
		<option value="<?php echo $type->post_name; ?>"><?php echo $type->post_title; ?></option>
		<?php endif; ?>
	<?php endforeach; ?>
	</select>
	<p>Please select up to 5 additional aircraft. If you wish to change the Squadrons primary aircraft then please contact Support.</p>
	<script>
		jQuery(function ($) {
			$('#group-secondary-aircraft').select2({
				multiple: true,
				maximumSelectionLength: 5
			});
			$('#group-secondary-aircraft').val(<?php echo json_encode( $typesArray ); ?>).trigger("change");
		});
	</script>

<label class="group-desc-label" for="group-desc"><?php esc_html_e( 'Group Description', 'buddyboss' ); ?></label>
<textarea name="group-desc" id="group-desc" aria-required="true"><?php bp_is_group_create() ? bp_new_group_description() : bp_group_description_editable(); ?></textarea>

<label for="group-website"><?php esc_html_e( 'Squadron Website URL', 'buddyboss' ); ?></label>
<input type="text" name="group-website" id="group-website" value="<?php echo groups_get_groupmeta( $groupID, '_ds_group_website' ); ?>" aria-required="false" />

<label for="group-discord"><?php esc_html_e( 'Discord Server', 'buddyboss' ); ?></label>
<input type="text" name="group-discord" id="group-discord" value="<?php echo groups_get_groupmeta( $groupID, '_ds_group_discord' ); ?>" aria-required="false" />

<label for="group-instagram"><?php esc_html_e( 'Instagram', 'buddyboss' ); ?></label>
<input type="text" name="group-instagram" id="group-instagram" value="<?php echo groups_get_groupmeta( $groupID, '_ds_group_instagram' ); ?>" aria-required="false" />

<label for="group-twitter"><?php esc_html_e( 'Twitter', 'buddyboss' ); ?></label>
<input type="text" name="group-twitter" id="group-twitter" value="<?php echo groups_get_groupmeta( $groupID, '_ds_group_twitter' ); ?>" aria-required="false" />

<label for="group-facebook"><?php esc_html_e( 'Facebook', 'buddyboss' ); ?></label>
<input type="text" name="group-facebook" id="group-facebook" value="<?php echo groups_get_groupmeta( $groupID, '_ds_group_facebook' ); ?>" aria-required="false" />

<label for="group-twitch"><?php esc_html_e( 'Twitch', 'buddyboss' ); ?></label>
<input type="text" name="group-twitch" id="group-twitch" value="<?php echo groups_get_groupmeta( $groupID, '_ds_group_twitch' ); ?>" aria-required="false" />