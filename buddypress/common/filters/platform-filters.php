<?php
/**
 * DS Filter groups based upon software platform - 'ds-software-platform'
 *
 * @since 1.0.0
 */
?>

<?php

$platforms = get_terms(array(
	'taxonomy'		=> 'ds-software-platform',
	'hide_empty'	=> false
));

$currentComponentSingular = bp_current_component() == 'groups' ? 'group' : 'member';

if ( ! empty( $platforms ) && ! is_wp_error( $platforms ) ) {
    ?>
    <div id="<?php echo $currentComponentSingular ?>-platform-filters" class="component-filters clearfix">
		<div id="<?php echo $currentComponentSingular ?>-platform-select" class="last filter">
			<label class="bp-screen-reader-text" for="<?php echo $currentComponentSingular ?>-platform-order-by">
				<span><?php __( 'Filter by:' ); ?></span>
			</label>
			<div class="select-wrap">
				<select id="ds-platform-select" name="ds_platform_select" class="ds-platform-select" data-bp-<?php echo $currentComponentSingular ?>-platform-filter="<?php bp_nouveau_filter_component(); ?>">
					<option value><?php echo __('All Platforms') ?></option>
				<?php foreach ( $platforms as $platform ) : ?>
					<option value="<?php echo esc_attr( $platform->slug ) ?>"><?php echo esc_html( $platform->name ) ?></option>
				<?php endforeach; ?>
				</select>
				<span class="select-arrow" aria-hidden="true"></span>
			</div>
		</div>
	</div>
    <?php
}