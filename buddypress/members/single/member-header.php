<?php
/**
 * BuddyPress - Users Header
 *
 * @since 3.0.0
 * @version 3.0.0
 */

remove_filter( 'bp_get_add_follow_button', 'buddyboss_theme_bp_get_add_follow_button' );
?>
<?php if ( ! bp_is_user_messages() && ! bp_is_user_settings() && ! bp_is_user_notifications() && ! bp_is_user_profile_edit() && ! bp_is_user_change_avatar() && ! bp_is_user_change_cover_image() ) : ?>
	<div id="cover-image-container" class="item-header-wrap">

		<?php $class = bp_disable_cover_image_uploads() ? 'bb-disable-cover-img' : 'bb-enable-cover-img'; ?>

		<div id="item-header-cover-image" class="<?php echo $class; ?>">

			<div id="item-header-avatar">
				<?php if ( bp_is_my_profile() && ! bp_disable_avatar_uploads() ) { ?>
					<a href="<?php bp_members_component_link( 'profile', 'change-avatar' ); ?>" class="link-change-profile-image" data-balloon-pos="down" data-balloon="<?php _e('Change Profile Photo', 'buddyboss-theme'); ?>">
						<i class="bb-icon-edit-thin"></i>
					</a>
				<?php } ?>
				<?php bp_displayed_user_avatar( 'type=full' ); ?>
			</div><!-- #item-header-avatar -->

			<div id="item-header-content">

				<div class="flex">
					<div class="bb-user-content-wrap">
						<div class="flex align-items-center member-title-wrap">
							
						</div>

						<?php bp_nouveau_member_hook( 'before', 'header_meta' ); ?>

						<?php if ( ( bp_is_active( 'activity' ) && bp_activity_do_mentions() ) || bp_nouveau_member_has_meta() ) : ?>
							<div class="item-meta">
								<?php if ( bp_is_active( 'activity' ) && bp_activity_do_mentions() ) : ?>
									<span class="mention-name">@<?php bp_displayed_user_mentionname(); ?></span>
								<?php endif; ?>

								<?php if ( bp_is_active( 'activity' ) && bp_activity_do_mentions() && bp_nouveau_member_has_meta() ) : ?>
									<span class="separator">&bull;</span>
								<?php endif; ?>

								<?php bp_nouveau_member_hook( 'before', 'header_meta' ); ?>

								<?php if ( bp_nouveau_member_has_meta() ) : ?>
									<?php bp_nouveau_member_meta(); ?>
								<?php endif; ?>
							</div>	
						<?php endif; ?>

						<?php if( function_exists( 'bp_is_activity_follow_active' ) && bp_is_active('activity') && bp_is_activity_follow_active() ) { ?>
							<div class="flex align-items-top member-social">
                                <div class="flex align-items-center">
								    <?php buddyboss_theme_followers_count(); ?>
								    <?php buddyboss_theme_following_count(); ?>
                                </div>
								<?php
		                        if( function_exists('bp_get_user_social_networks_urls') ) {
			                        echo bp_get_user_social_networks_urls();
		                        } ?>
							</div>
						<?php } else { ?>
                            <div class="flex align-items-center">
	                            <?php
		                        if( function_exists('bp_get_user_social_networks_urls') ) {
			                        echo bp_get_user_social_networks_urls();
		                        }
		                        ?>
                            </div>
                        <?php } ?>
					</div>

					<?php remove_filter( 'bp_get_add_friend_button', 'buddyboss_theme_bp_get_add_friend_button' ); ?>
					<?php bp_nouveau_member_header_buttons( array( 'container_classes' => array( 'member-header-actions' ) ) ); ?>
					<?php
					if ( function_exists( 'bp_nouveau_member_header_bubble_buttons' ) ) {
						bp_nouveau_member_header_bubble_buttons( array( 'container_classes' => array( 'bb_more_options' ), ) );
					}
					?>
					<?php add_filter( 'bp_get_add_friend_button', 'buddyboss_theme_bp_get_add_friend_button' ); ?>

				</div>

			</div><!-- #item-header-content -->
		</div>
	</div>
<?php
add_filter( 'bp_get_add_follow_button', 'buddyboss_theme_bp_get_add_follow_button' );

endif;