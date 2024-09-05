<?php
/*
 * Template name: Competition Loop
 *
 * @package DIGITALSQUADRONS_THEME
 */

get_header();
?>

<div id="primary" class="content-area bb-grid-cell">
	<main id="main" class="site-main">

    <?php 
		
		get_template_part( 'template-parts/content', 'competition-loop' );

    ?>

	</main><!-- #main -->
</div><!-- #primary -->

<?php
get_footer();
