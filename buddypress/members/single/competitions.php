<h2 class="screen-heading group-invites-screen"><?php esc_html_e( 'My Competitions', 'digitalSquadrons' ); ?></h2>

<?php bp_nouveau_group_hook( 'before', 'competitions_content' ); ?>

<?php

$args = array(
    'user_id'    => bp_displayed_user_id(),
    'type'       => 'alphabetical',
    'group_type' => array( 'leagues', 'cup' ),
    'show_hidden'=> true
);

?>

<?php if ( bp_has_groups( $args ) ) : ?>

	<ul id="group-list" class="item-list groups-list bp-list grid bb-cover-enabled" data-bp-list="groups_invites">

		<?php
		while ( bp_groups() ) :
            bp_the_group();
		?>

<li <?php bp_group_class( array( 'item-entry' ) ); ?> data-bp-item-id="<?php bp_group_id(); ?>" data-bp-item-component="groups">
			<div class="list-wrap">

				<?php if( !bp_disable_group_cover_image_uploads() ) { ?>
					<?php
					$group_cover_image_url = bp_attachments_get_attachment( 'url', array(
						'object_dir' => 'groups',
						'item_id'    => bp_get_group_id(),
					) );
					$default_group_cover   = buddyboss_theme_get_option( 'buddyboss_group_cover_default', 'url' );
					$group_cover_image_url = $group_cover_image_url ?: $default_group_cover;
					?>
					<div class="bs-group-cover only-grid-view"><a href="<?php bp_group_permalink(); ?>"><img src="<?php echo $group_cover_image_url; ?>"></a></div>
				<?php } ?>

				<?php if ( ! bp_disable_group_avatar_uploads() ) : ?>
					<div class="item-avatar">
						<a href="<?php bp_group_permalink(); ?>" class="group-avatar-wrap"><?php bp_group_avatar( bp_nouveau_avatar_args() ); ?></a>

						<div class="groups-loop-buttons only-grid-view">
							<?php bp_nouveau_groups_loop_buttons(); ?>
						</div>
					</div>
				<?php endif; ?>

				<div class="item">
					<div class="item-block">

						<h2 class="list-title groups-title"><?php bp_group_link(); ?></h2>

						<?php if ( bp_nouveau_group_has_meta() ) : ?>

							<p class="item-meta group-details only-list-view"><?php bp_nouveau_group_meta(); ?></p>
							<p class="item-meta group-details only-grid-view"><?php
								$meta = bp_nouveau_get_group_meta();
								echo $meta['status']; ?>
							</p>
						<?php endif; ?>

						<p class="last-activity item-meta">
							<?php
							printf(
								/* translators: %s = last activity timestamp (e.g. "active 1 hour ago") */
								__( 'active %s', 'buddyboss-theme' ),
								bp_get_group_last_active()
							);
							?>
						</p>

					</div>

					<div class="item-desc group-item-desc only-list-view"><?php bp_group_description_excerpt( false , 150 ) ?></div>

					<?php bp_nouveau_groups_loop_item(); ?>

					<div class="groups-loop-buttons footer-button-wrap"><?php bp_nouveau_groups_loop_buttons(); ?></div>

					<div class="group-members-wrap only-grid-view">
						<?php echo buddyboss_theme()->buddypress_helper()->group_members( bp_get_group_id(), array( 'member', 'mod', 'admin' ) ); ?>
					</div>
				</div>
			</div>
		</li>

		<?php endwhile; ?>
	</ul>

<?php else : ?>

	<?php bp_nouveau_user_feedback( 'member-invites-none' ); ?>

<?php endif; ?>

<?php
bp_nouveau_group_hook( 'after', 'invites_content' );