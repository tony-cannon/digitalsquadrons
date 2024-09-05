<?php 
$userID = bp_displayed_user_id();
$userNetworks = get_user_meta( $userID, '_ds_user_networks', true );
$userNetworksVis = get_user_meta( $userID, '_ds_user_network_vis', true );
?>

<h2 class="screen-heading social-media-screen">Social Media Links</h2>

<form name="bp-profile-social-media-settings" method="post" class="standard-form">

    <label for="ds-profile-social-media-discord">Discord Server</label>
    <input type="text" name="ds-profile-social-media-discord" id="ds-profile-social-media-discord" placeholder="https://www.discord.com..." value="<?php echo esc_url( $userNetworks['discord']); ?>">

    <label for="ds-profile-social-media-instagram">Instagram</label>
    <input type="text" name="ds-profile-social-media-instagram" id="ds-profile-social-media-instagram" placeholder="https://www.instagram.com..." value="<?php echo esc_url( $userNetworks['instagram']); ?>">

    <label for="ds-profile-social-media-youtube">YouTube Channel</label>
    <input type="text" name="ds-profile-social-media-youtube" id="ds-profile-social-media-youtube" placeholder="https://www.youtube.com..." value="<?php echo esc_url( $userNetworks['youtube']); ?>">

    <label for="ds-profile-social-media-twitter">Twitter</label>
    <input type="text" name="ds-profile-social-media-twitter" id="ds-profile-social-media-twitter" placeholder="https://www.twitter.com..." value="<?php echo esc_url( $userNetworks['twitter']); ?>">

    <label for="ds-profile-social-media-twitch">Twitch</label>
    <input type="text" name="ds-profile-social-media-twitch" id="ds-profile-social-media-twitch" placeholder="https://www.twitch.com..." value="<?php echo esc_url( $userNetworks['twitch']); ?>">

    <label for="ds-profile-social-media-facebook">Facebook</label>
    <input type="text" name="ds-profile-social-media-facebook" id="ds-profile-social-media-facebook" placeholder="https://www.facebook.com..." value="<?php echo esc_url( $userNetworks['facebook']); ?>">

    <hr style="height:1px;border-width:0;color:gray;background-color:#ccc;margin-top:20px;">

    <label for="ds-profile-social-media-visibility">Visibility</label>
    <p style="font-size: 13px;">Please select who you would like to view your social network profile information...</p>
    <label for="ds-profile-social-media-visibility-public"><input type="radio" name="ds-profile-social-media-visibility" id="ds-profile-social-media-visibility-public" value="public" <?php echo $userNetworksVis === 'public' || $userNetworksVis === '' ? 'checked' : '' ?>><?php _e( 'Public View', 'buddyboss' ); ?></label>
    <label for="ds-profile-social-media-visibility-loggedin"><input type="radio" name="ds-profile-social-media-visibility" id="ds-profile-social-media-visibility-loggedin" value="loggedin" <?php echo $userNetworksVis === 'loggedin' ? 'checked' : '' ?>><?php _e( 'Logged In Members', 'buddyboss' ); ?></label>
    <label for="ds-profile-social-media-visibility-friends"><input type="radio" name="ds-profile-social-media-visibility" id="ds-profile-social-media-visibility-friends" value="friends" <?php echo $userNetworksVis === 'friends' ? 'checked' : '' ?>><?php _e( 'Friends Only', 'buddyboss' ); ?></label>

    <?php wp_nonce_field( 'ds-profile-social-media' ); ?>
    <p class="submit">
        <input type="submit" id="ds-profile-social-media-submit" name="ds-profile-social-media-submit" class="button" value="<?php _e( 'Save', 'buddyboss' ); ?>"/>
    </p>

    <?php echo ds_get_user_social_networks_urls(); ?>
</form>