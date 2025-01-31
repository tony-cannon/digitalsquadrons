<?php
$show_search = buddyboss_theme_get_option( 'mobile_header_search' );
$show_messages = buddyboss_theme_get_option( 'mobile_messages' ) && is_user_logged_in();
$show_notifications = buddyboss_theme_get_option( 'mobile_notifications' ) && is_user_logged_in();
$show_shopping_cart = buddyboss_theme_get_option( 'mobile_shopping_cart' );

$logo_align = count( array_filter( array($show_search, $show_messages, $show_notifications, $show_shopping_cart) ) );
$logo_class = ( $logo_align <= 1 && ( !buddyboss_is_learndash_inner() && !buddyboss_is_lifterlms_inner() ) ) ? 'bb-single-icon' : '';
?>

<div class="bb-mobile-header-wrapper <?php echo $logo_class; ?>">
	<div class="bb-mobile-header flex align-items-center">
		<div class="bb-left-panel-icon-wrap">
			<a href="#" class="push-left bb-left-panel-mobile"><i class="bb-icon-menu-left"></i></a>
		</div>

		<div class="flex-1 mobile-logo-wrapper">
			<?php
			$show		     = buddyboss_theme_get_option( 'mobile_logo_switch' );
			$logo_id	     = buddyboss_theme_get_option( 'mobile_logo', 'id' );
			$show_dark    = buddyboss_theme_get_option( 'mobile_logo_dark_switch' );
			$logo_dark_id	 = buddyboss_theme_get_option( 'mobile_logo_dark', 'id' );
			$logo		     = ( $show && $logo_id ) ? wp_get_attachment_image( $logo_id, 'full', '', array( 'class' => 'bb-mobile-logo' ) ) : get_bloginfo( 'name' );
			$logo_dark		 = ( $show && $show_dark && $logo_dark_id ) ? wp_get_attachment_image( $logo_dark_id, 'full', '', array( 'class' => 'bb-mobile-logo bb-mobile-logo-dark' ) ) : '';

			// This is for better SEO
			$elem = ( is_front_page() && is_home() ) ? 'h1' : 'h2';
			?>

			<<?php echo $elem; ?> class="site-title">

				<a href="<?php echo esc_url( home_url( '/' ) ); ?>" rel="home">
					<?php if ( buddyboss_is_learndash_inner() && buddyboss_theme_ld_focus_mode() ) {
						if ( buddyboss_is_learndash_brand_logo() ) { ?>
							<img src="<?php echo esc_url(wp_get_attachment_url(buddyboss_is_learndash_brand_logo())); ?>" alt="<?php echo esc_attr(get_post_meta(buddyboss_is_learndash_brand_logo() , '_wp_attachment_image_alt', true)); ?>" class="bb-mobile-logo">
						<?php } else {
							echo $logo; echo $logo_dark;
						}
					} else {
						echo $logo; echo $logo_dark;
					} ?>
				</a>

			</<?php echo $elem; ?>>
		</div>
		<div class="header-aside">
				<?php if( ( class_exists( 'SFWD_LMS' ) && buddyboss_is_learndash_inner() ) || ( class_exists( 'LifterLMS' ) && buddyboss_is_lifterlms_inner() ) ){ ?>
					<?php if ( is_user_logged_in()) { ?>
						<a href="#" id="bb-toggle-theme">
		                    <span class="sfwd-dark-mode" data-balloon-pos="down" data-balloon="<?php _e( 'Dark Mode', 'buddyboss-theme' ); ?>"><i class="bb-icon-moon-circle"></i></span>
		                    <span class="sfwd-light-mode" data-balloon-pos="down" data-balloon="<?php _e( 'Light Mode', 'buddyboss-theme' ); ?>"><i class="bb-icon-sun"></i></span>
		                </a>
						<a href="#" class="header-maximize-link course-toggle-view" data-balloon-pos="left" data-balloon="<?php _e( 'Hide Sidepanel', 'buddyboss-theme' ); ?>"><i class="bb-icon-maximize"></i></a>
						<a href="#" class="header-minimize-link course-toggle-view" data-balloon-pos="left" data-balloon="<?php _e( 'Show Sidepanel', 'buddyboss-theme' ); ?>"><i class="bb-icon-minimize"></i></a>
					<?php }else{ ?>

						<?php if( $show_search ) : ?>
							<a data-balloon-pos="left" data-balloon="<?php _e( 'Search', 'buddyboss-theme' ); ?>" href="#" class="push-right header-search-link"><i class="bb-icon-search"></i></a>
						<?php endif; ?>

						<?php if( $show_shopping_cart && class_exists( 'WooCommerce' ) ) : ?>
							<?php get_template_part( 'template-parts/cart-dropdown' ); ?>
						<?php endif; ?>

						<a href="#" class="header-maximize-link course-toggle-view" data-balloon-pos="left" data-balloon="<?php _e( 'Hide Sidepanel', 'buddyboss-theme' ); ?>"><i class="bb-icon-maximize"></i></a>
						<a href="#" class="header-minimize-link course-toggle-view" data-balloon-pos="left" data-balloon="<?php _e( 'Show Sidepanel', 'buddyboss-theme' ); ?>"><i class="bb-icon-minimize"></i></a>
				<?php } ?>
			<?php }else{ ?>
				<?php if( $show_search ) : ?>
				<a data-balloon-pos="left" data-balloon="<?php _e( 'Search', 'buddyboss-theme' ); ?>" href="#" class="push-right header-search-link"><i class="bb-icon-search"></i></a>
				<?php endif; ?>

				<?php if( $show_messages && function_exists( 'bp_is_active' ) && bp_is_active( 'messages' )  ) : ?>
					<?php get_template_part( 'template-parts/messages-dropdown' ); ?>
				<?php endif; ?>

				<?php if( $show_notifications && function_exists( 'bp_is_active' ) && bp_is_active( 'notifications' ) ) : ?>
					<?php get_template_part( 'template-parts/notification-dropdown' ); ?>
				<?php endif; ?>

				<?php if( $show_shopping_cart && class_exists( 'WooCommerce' ) ) : ?>
					<?php get_template_part( 'template-parts/cart-dropdown' ); ?>
				<?php endif;
			} ?>
		</div>
	</div>

	<div class="header-search-wrap">
        <div class="container">
			<?php get_search_form(); ?>
            <a data-balloon-pos="left" data-balloon="<?php _e( 'Close', 'buddyboss-theme' ); ?>" href="#" class="close-search"><i class="bb-icon-close-circle"></i></a>
        </div>
    </div>
</div>

<div class="bb-mobile-panel-wrapper left light closed">
	<a href="#" class="bb-close-panel"><i class="bb-icon-close"></i></a>
	<div class="bb-mobile-panel-inner">
		<div class="bb-mobile-panel-header">
			<?php if ( is_user_logged_in() ) { ?>
				<?php
				$user_link		 = function_exists( 'bp_core_get_user_domain' ) ? bp_core_get_user_domain( get_current_user_id() ) : get_author_posts_url( get_current_user_id() );
				$current_user	 = wp_get_current_user();
				?>
				<div class="user-wrap">
					<a href="<?php echo $user_link; ?>"><?php echo get_avatar( get_current_user_id(), 100 ); ?></a>
					<div>
						<a href="<?php echo $user_link; ?>"><span class="user-name"><?php echo $current_user->display_name; ?></span></a>
						<?php
						if ( function_exists( 'bp_is_active' ) && bp_is_active( 'settings' ) ) {
							$settings_link = trailingslashit( bp_loggedin_user_domain() . bp_get_settings_slug() ); ?>
							<div class="my-account-link"><a class="ab-item" aria-haspopup="true" href="<?php echo $settings_link; ?>"><?php _e( 'My Account', 'buddyboss-theme' ); ?></a></div><?php
						} ?>
					</div>
				</div>
			<?php } else { ?>
				<div class="logo-wrap">
					<a href="<?php echo esc_url( home_url( '/' ) ); ?>" rel="home">
						<?php echo $logo; ?>
					</a>
				</div>
			<?php } ?>
		</div>

		<hr />

		<nav class="main-navigation" data-menu-space="120">
			<?php
			wp_nav_menu( array(
				'theme_location' => 'header-menu',
				'menu_id'		 => '',
				'container'		 => false,
				'fallback_cb'	 => false,
				'menu_class'	 => 'bb-primary-menu mobile-menu buddypanel-menu', )
			);
			?>
		</nav>

		<?php
		$menu = is_user_logged_in() ? 'buddypanel-loggedin' : 'buddypanel-loggedout';

		if ( has_nav_menu( $menu ) ) {
			 echo '<hr />';
		}

		wp_nav_menu( array(
			'theme_location' => $menu,
			'menu_id'		 => '',
			'container'		 => false,
			'fallback_cb'	 => false,
            'walker'         => new BuddyBoss_BuddyPanel_Menu_Walker(),
			'menu_class'	 => 'buddypanel-menu side-panel-menu', )
		);
		?>

		<div class="bb-login-section">
			<?php if ( !is_user_logged_in() ) { ?>
				<?php do_action( 'ds_discord_login_button' ) ?>
			<?php } else { ?>
				<a href="<?php echo wp_logout_url(); ?>" class="button small full sign-out"><?php _e( 'Logout', 'buddyboss-theme' ); ?></a>
			<?php } ?>
		</div>

	</div>
</div>