<?php
/**
 * BP Nouveau Component's groups filters template.
 *
 * @since BuddyBoss 1.0.0
 */
?>

<?php

if ( true ) {
	?>
	<div id="group-proximity-filters" class="component-filters clearfix">
		<div id="group-proximity-select" class="last filter">
			<label class="bp-screen-reader-text" for="group-proximity-order-by">
				<span><?php bp_nouveau_filter_label(); ?></span>
			</label>
			<div class="select-wrap">
				<?php //echo ds_get_region_dropdown('dsRegionFilter', bp_current_component() ) ?>
				<?php echo ds_get_filter_country_dropdown( 'dsRegionFilter', bp_current_component() ); ?>
				<span class="select-arrow" aria-hidden="true"></span>
			</div>
		</div>
	</div>
	<?php
}
