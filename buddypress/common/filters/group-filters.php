<?php
/**
 * BP Nouveau Component's groups filters template.
 *
 * @since BuddyBoss 1.0.0
 */
?>

<?php

// Check group type enable?
if ( false === bp_disable_group_type_creation() ) {
	return '';
}

$currentComponentSingular = bp_current_component() == 'groups' ? 'group' : 'member';

	?>
	<div id="ds-<?php echo $currentComponentSingular ?>-filters" class="bp-<?php echo $currentComponentSingular ?>-filter-wrap subnav-filters filters no-ajax" style="display: none;">
		<div id="<?php echo $currentComponentSingular ?>-type-filters" class="component-filters clearfix">
			<div id="<?php echo $currentComponentSingular ?>-type-select" class="last filter">
				<label class="bp-screen-reader-text" for="<?php echo $currentComponentSingular ?>-type-order-by">
					<span><?php bp_nouveau_filter_label(); ?></span>
				</label>
				<div class="select-wrap">
					<select id="<?php echo $currentComponentSingular ?>-type-order-by" data-bp-<?php echo $currentComponentSingular ?>-type-filter="<?php bp_nouveau_search_object_data_attr() ?>">
						
					</select>
					<span class="select-arrow" aria-hidden="true"></span>
				</div>
			</div>
		</div>
	</div>
