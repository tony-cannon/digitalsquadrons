<?php
/**
 * BP Nouveau Component's directory nav template.
 *
 * @since 3.0.0
 * @version 3.0.0
 */
?>

<nav class="<?php bp_nouveau_directory_type_navs_class(); ?> bp-subnavs" role="navigation" aria-label="<?php esc_attr_e( 'Directory menu', 'buddyboss-theme' ); ?>">

	<?php if ( bp_nouveau_has_nav( array( 'object' => 'directory' ) ) ) : ?>

		<ul class="component-navigation <?php bp_nouveau_directory_list_class(); ?>">

			<?php
			while ( bp_nouveau_nav_items() ) :
				bp_nouveau_nav_item();
                ?>

				<li id="<?php bp_nouveau_nav_id(); ?>" class="<?php bp_nouveau_nav_classes(); ?>" <?php bp_nouveau_nav_scope(); ?> data-bp-object="<?php bp_nouveau_directory_nav_object(); ?>">
					<a href="<?php bp_nouveau_nav_link(); ?>">
						<?php bp_nouveau_nav_link_text(); ?>

						<?php if ( bp_nouveau_nav_has_count() ) : ?>
							<span class="count"><?php bp_nouveau_nav_count(); ?></span>
						<?php endif; ?>
					</a>
				</li>
				
			<?php endwhile; ?>

			<?php if ( bp_is_groups_component() ) : ?>

				<li>
					<a href="<?php echo get_page_link(949); ?>">
							<?php echo __( 'Create a Squadron', 'digitalSquadrons');  ?>
					</a>
				</li>

			<?php endif; ?>

		</ul><!-- .component-navigation -->

	<?php endif; ?>

</nav><!-- .bp-navs -->
