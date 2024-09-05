<?php
$userID = bp_displayed_user_id();
//$selectName = 'ds-member-location';
?>
<h2 class="screen-heading platform-aircraft-screen">Member Location</h2>

<form name="bp-profile-location-settings" method="post" class="standard-form">

    <?php 
        
        get_template_part( 
            'template-parts/country-state-select', 
            'member-location', 
            array(
                'selectName'                => 'ds-member-location',
                'placeholderCountry'        => 'Select Country',
                'placeholderState'          => 'Select State',
                'state'                     => true
            ) 
        ); 
        ?> 

    <?php wp_nonce_field( 'ds-profile-location' ); ?>
    <p class="submit">
        <input type="submit" id="ds-profile-location-submit" name="ds-profile-location-submit" class="button" value="<?php _e( 'Save', 'buddyboss' ); ?>"/>
    </p>


</form>