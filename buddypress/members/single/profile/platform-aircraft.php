<h2 class="screen-heading platform-aircraft-screen">Platforms and Aircraft</h2>

<p>By selecting your platforms and aircraft below will enable us to bespoke your experience and channel lists on the <a href="https://discord.com/channels/799626076455305267" target="_blank">Digital Squadron's Discord Server</a>. If fine tuning wasn't an option you would end up with a lot of channels which have no real relevance to your current flight sim setup or experience; by fine tuning the options it will make the content more relevant and immersive. </p>

<?php 

$userID = bp_displayed_user_id();
$userPlatforms = get_user_meta( $userID, '_ds_member_platforms', true );
$userTypes = bp_get_user_meta( $userID, '_ds_member_group_types', true );

$groupTypeArgs = array(
                'numberposts' => -1,   // -1 returns all posts
                'post_type' => 'bp-group-type',
                'orderby' => 'title',
                'order' => 'ASC',
);

?>
<form name="bp-profile-platform-aircraft-settings" method="post" class="standard-form">
    <!-- Platform Select -->
    <div id="ds-member-platform-select-container" class="">
        <label for="ds-member-platform-select"><?php echo esc_html('Select Simulator Platforms'); ?></label>
        <select name="ds-member-platform-select[]" id="ds-member-platform-select" data-bp-member-platform-filter="members">
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
    <div id="ds-member-dcs-world-select-container" class="">
        <label for="ds-member-dcs-world-select"><?php echo esc_html('DCS World Aircraft'); ?></label>
        <select name="ds-member-dcs-world-select[]" id="ds-member-dcs-world-select" data-bp-member-dcs-world-filter="members">
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
                $('#ds-member-dcs-world-select').select2({
                    multiple: true,
                    maximumSelectionLength: 10
                });
                $('#ds-member-dcs-world-select').val(<?php echo json_encode( $userTypes ); ?>).trigger("change");
            });
        </script>
        <p>Select up to 10 aircraft.</p>
    </div>
    <?php endif; ?>

    <?php if ( in_array( 'msfs', $userPlatforms ) ) : ?>
    <!-- MSFS Select -->
    <div id="ds-member-msfs-select-container" class="">
        <label for="ds-member-msfs-select"><?php echo esc_html('MSFS Aircraft'); ?></label>
        <select name="ds-member-msfs-select[]" id="ds-member-msfs-select" data-bp-member-msfs-filter="members">
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
    <div id="ds-member-prepar3d-select-container" class="">
        <label for="ds-member-prepar3d-select"><?php echo esc_html('Prepar3d Aircraft'); ?></label>
        <select name="ds-member-prepar3d-select[]" id="ds-member-prepar3d-select" data-bp-member-prepar3d-filter="members">
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
                $('#ds-member-prepar3d-select').select2({
                    multiple: true,
                    maximumSelectionLength: 10
                });
                $('#ds-member-prepar3d-select').val(<?php echo json_encode( $userTypes ); ?>).trigger("change");
            });
        </script>
        <p>Select up to 10 aircraft.</p>
    </div>
    <?php endif; ?>

    <?php if ( in_array( 'xplane', $userPlatforms ) ) : ?>
    <!-- X-Plane Select -->
    <div id="ds-member-xplane-select-container" class="">
        <label for="ds-member-xplane-select"><?php echo esc_html('X-Plane Aircraft'); ?></label>
        <select name="ds-member-xplane-select[]" id="ds-member-xplane-select" data-bp-member-xplane-filter="members">
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

    <?php if ( in_array( 'vtol-vr', $userPlatforms ) ) : ?>
    <!-- VTOL-VR Select -->
    <div id="ds-member-vtol-vr-select-container" class="">
        <label for="ds-member-vtol-vr-select"><?php echo esc_html('VTOL VR Aircraft'); ?></label>
        <select name="ds-member-vtol-vr-select[]" id="ds-member-vtol-vr-select" data-bp-member-vtol-vr-filter="members">
            <?php 
            /**
             * Output bp-group-types for VTOL-VR.
             */
            $groupTypeArgs['tax_query'] = array(
                array(
                    'taxonomy'          => 'ds-software-platform',
                    'field'             => 'slug',
                    'terms'             => 'vtol-vr',
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
                $('#ds-member-vtol-vr-select').select2({
                    multiple: true,
                    maximumSelectionLength: 10
                });
                $('#ds-member-vtol-vr-select').val(<?php echo json_encode( $userTypes ); ?>).trigger("change");
            });
        </script>
        <p>Select up to 10 aircraft.</p>
    </div>
    <?php endif; ?>

    <?php wp_nonce_field( 'ds-profile-platform-aircraft' ); ?>

    <p class="submit">
        <input type="submit" id="ds-profile-platform-aircraft-submit" name="ds-profile-platform-aircraft-submit" class="button" value="<?php _e( 'Save', 'buddyboss' ); ?>"/>
    </p>
</form>