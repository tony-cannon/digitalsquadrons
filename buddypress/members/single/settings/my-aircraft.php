<?php 

$userID = bp_get_member_user_id();
$userPlatforms = get_user_meta( $userID, '_ds_member_platforms' );
$userTypes = get_user_meta( $userID, '_ds_member_group_types' );

$groupTypeArgs = array(
                'numberposts' => -1,   // -1 returns all posts
                'post_type' => 'bp-group-type',
                'orderby' => 'title',
                'order' => 'ASC',
);

?>
<!-- Platform Select -->
<div id="ds-member-platform-select-container" class="">
    <label for="ds-member-platform-select"><?php echo esc_html('Select Simulator Platforms'); ?></label>
    <select name="ds-member-platform-select" id="ds-member-platform-select" data-bp-member-platform-filter="members">
        <option value><?php echo esc_html(''); ?></option>
        <?php 
        /**
         * Output Software Platform Terms.
         */
        $platforms = get_categories( array(
                    'taxonomy'	=> 'ds-software-platform',
                    'hide_empty'	=> false
        ) );

        //print_r($platforms);

        ?>
        <?php foreach ( $platforms as $platform ) : ?>
            <option value="<?php echo esc_attr( $platform->slug ); ?>"><?php echo esc_html( $platform->name ); ?></option>
        <?php endforeach; ?>
    </select>
    <script>
        jQuery(function ($) {
			$('#ds-member-platform-select').select2({
				multiple: true,
				// maximumSelectionLength: 5
			});
			$('#ds-member-platform-select').val(<?php echo json_encode( $userPlatforms ); ?>).trigger("change");
		});
    </script>
    <p>Please select platforms applicable to you and press 'Save', you will then be able to select aircraft.</p>
</div>

<?php if ( in_array( 'dcs-world', $userPlatforms ) ) : ?>
<!-- DCS Select -->
<div id="ds-member-dcs-select-container" class="">
    <label for="ds-member-dcs-select"><?php echo esc_html(''); ?></label>
    <select name="ds-member-dcs-select" id="ds-member-dcs-select" data-bp-member-dcs-filter="members">
        <option value><?php echo esc_html(''); ?></option>
        <?php 
        /**
         * Output bp-group-types for DCS.
         */
        $groupTypeArgs['tax_query'] = array(
                array(
                    'taxonomy'          => 'ds-software-platform',
                    'field'             => 'slug',
                    'terms'             => 'dcs-world',
                    'include_children'  => false // Remove if you need posts from term 7 child terms
                )
            );
        $types = get_posts( $groupTypeArgs );
        ?>
        <?php foreach ($types as $type) : ?>
        <option value="<?php echo $type->post_name ?>"><?php echo $type->post_title; ?></option>';
        <?php endforeach; ?>
    </select>
    <script>
        jQuery(function ($) {
			$('#ds-member-dcs-select').select2({
				multiple: true,
				maximumSelectionLength: 10
			});
			$('#ds-member-dcs-select').val(<?php echo json_encode( $userTypes ); ?>).trigger("change");
		});
    </script>
    <p>Select up to 10 aircraft.</p>
</div>
<?php endif; ?>

<?php if ( in_array( 'msfs', $userPlatforms ) ) : ?>
<!-- MSFS Select -->
<div id="ds-member-msfs-select-container" class="">
    <label for="ds-member-msfs-select"><?php echo esc_html(''); ?></label>
    <select name="ds-member-msfs-select" id="ds-member-msfs-select" data-bp-member-msfs-filter="members">
        <option value><?php echo esc_html(''); ?></option>
        <?php 
        /**
         * Output bp-group-types for MSFS.
         */
        $groupTypeArgs['tax_query'] = array(
            array(
                'taxonomy'          => 'ds-software-platform',
                'field'             => 'slug',
                'terms'             => 'msfs',
                'include_children'  => false // Remove if you need posts from term 7 child terms
            )
        );
        $types = get_posts( $groupTypeArgs );
        ?>
        <?php foreach ($types as $type) : ?>
        <option value="<?php echo $type->post_name ?>"><?php echo $type->post_title; ?></option>';
        <?php endforeach; ?>
    </select>
    <script>
        jQuery(function ($) {
			$('#ds-member-msfs-select').select2({
				multiple: true,
				maximumSelectionLength: 10
			});
			$('#ds-member-msfs-select').val(<?php echo json_encode( $userTypes ); ?>).trigger("change");
		});
    </script>
    <p>Select up to 10 aircraft.</p>
</div>
<?php endif; ?>

<?php if ( in_array( 'prepar3d', $userPlatforms ) ) : ?>
<!-- Prepar3d Select -->
<div id="ds-member-p3d-select-container" class="">
    <label for="ds-member-p3d-select"><?php echo esc_html(''); ?></label>
    <select name="ds-member-p3d-select" id="ds-member-p3d-select" data-bp-member-p3d-filter="members">
        <option value><?php echo esc_html(''); ?></option>
        <?php 
        /**
         * Output bp-group-types for P3D.
         */
        $groupTypeArgs['tax_query'] = array(
            array(
                'taxonomy'          => 'ds-software-platform',
                'field'             => 'slug',
                'terms'             => 'prepar3d',
                'include_children'  => false // Remove if you need posts from term 7 child terms
            )
        );
        $types = get_posts( $groupTypeArgs );
        ?>
        <?php foreach ($types as $type) : ?>
        <option value="<?php echo $type->post_name ?>"><?php echo $type->post_title; ?></option>';
        <?php endforeach; ?>
    </select>
    <script>
        jQuery(function ($) {
			$('#ds-member-p3d-select').select2({
				multiple: true,
				maximumSelectionLength: 10
			});
			$('#ds-member-p3d-select').val(<?php echo json_encode( $userTypes ); ?>).trigger("change");
		});
    </script>
    <p>Select up to 10 aircraft.</p>
</div>
<?php endif; ?>

<?php if ( in_array( 'xplane', $userPlatforms ) ) : ?>
<!-- X-Plane Select -->
<div id="ds-member-xplane-select-container" class="">
    <label for="ds-member-xplane-select"><?php echo esc_html(''); ?></label>
    <select name="ds-member-xplane-select" id="ds-member-xplane-select" data-bp-member-xplane-filter="members">
        <option value><?php echo esc_html(''); ?></option>
        <?php 
        /**
         * Output bp-group-types for X-Plane.
         */
        $groupTypeArgs['tax_query'] = array(
            array(
                'taxonomy'          => 'ds-software-platform',
                'field'             => 'slug',
                'terms'             => 'xplane',
                'include_children'  => false // Remove if you need posts from term 7 child terms
            )
        );
        $types = get_posts( $groupTypeArgs );
        ?>
        <?php foreach ($types as $type) : ?>
        <option value="<?php echo $type->post_name ?>"><?php echo $type->post_title; ?></option>';
        <?php endforeach; ?>
    </select>
    <script>
        jQuery(function ($) {
			$('#ds-member-xplane-select').select2({
				multiple: true,
				maximumSelectionLength: 10
			});
			$('#ds-member-xplane-select').val(<?php echo json_encode( $userTypes ); ?>).trigger("change");
		});
    </script>
    <p>Select up to 10 aircraft.</p>
</div>
<?php endif; ?>