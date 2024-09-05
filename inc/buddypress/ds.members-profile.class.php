<?php 

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

class DS_Member_Profile {
    
    // Hold the class instance.
    private static $instance = null;
    
    // The constructor is private
    // to prevent initiation with outer code.
    private function __construct()
    {
        $this->addHooks();
    }
   
    // The object is created from within the class itself
    // only if the class has no instance.
    public static function getInstance()
    {
      if (self::$instance == null)
      {
        self::$instance = new DS_Member_Profile();
      }
   
      return self::$instance;
    }

    public function addHooks() {
        add_action( 'bp_setup_nav', array( $this, 'ds_setup_member_profile_nav' ), 100  );
        add_action( 'bp_setup_nav', array( $this, 'ds_filter_member_profile_subnav' ),999 );

        add_action( 'bp_late_include', array( $this, 'ds_late_includes' ) );
    }

    public function ds_setup_member_profile_nav() {
        global $bp;

        if ( bp_is_active( 'members' ) && bp_core_can_edit_settings() ) {

            // Determine user to use.
            if ( bp_displayed_user_domain() ) {
                $userDomain = bp_displayed_user_domain();
            } elseif ( bp_loggedin_user_domain() ) {
                $userDomain = bp_loggedin_user_domain();
            } else {
                return;
            }

            $access         = bp_core_can_edit_settings();

            $settings_slug  = bp_get_settings_slug();
            $settings_link  = trailingslashit( $userDomain . bp_get_settings_slug() );

            $profile_slug   = bp_get_profile_slug();
            $profile_link   = trailingslashit( $userDomain . bp_get_profile_slug() );

            bp_core_new_subnav_item(
                array(
                    'name'            => __( 'Platforms/Aircraft', 'buddyboss' ),
                    'slug'            => 'platform-aircraft',
                    'parent_url'      => $profile_link,
                    'parent_slug'     => $profile_slug,
                    'screen_function' => 'ds_member_profile_platform_aircraft_screen',
                    'position'        => 60,
                    'user_has_access' => $access,
                    ),
                'members'
            );

            bp_core_new_subnav_item( 
                array(
                    'name'            => __( 'Location', 'text-domain' ),
                    'slug'            => 'member-location',
                    'parent_url' 	  => $profile_link,
                    'parent_slug'     => $profile_slug,
                    'screen_function' => 'ds_member_profile_location_screen',
                    //'item_css_id'	  => 'settings-profile', // ID must be unique
                    'position'        => 55,
                    'user_has_access' => $access	
                    ), 
                'members' 
            );

            bp_core_new_subnav_item( 
                array(
                    'name'            => __( 'Social Media', 'text-domain' ),
                    'slug'            => 'social-media',
                    'parent_url' 	  => $profile_link,
                    'parent_slug'     => $profile_slug,
                    'screen_function' => 'ds_member_profile_social_media_screen',
                    //'item_css_id'	  => 'settings-profile', // ID must be unique
                    'position'        => 65,
                    'user_has_access' => $access	
                    ), 
                'members' 
            );


        }
    }

    public function ds_member_profile_platform_aircraft_screen() {
        
    }

    public function ds_late_includes() {

		// Bail if not on a user page.
		if ( ! bp_is_user() ) {
			return;
		}

        // User nav.
		if ( bp_is_profile_component() ) {
			//require $this->path . 'bp-xprofile/screens/public.php';

			// Sub-nav items.
			if ( is_user_logged_in() &&
				in_array( bp_current_action(), array( 'member-location', 'platform-aircraft', 'social-media' ), true )
			) {
				require_once get_stylesheet_directory() . '/inc/buddypress/screens/' . bp_current_action() . '.php';
			}
		}
    }

    function ds_filter_member_profile_subnav() {
        bp_core_remove_subnav_item( 'settings', 'profile' );
        bp_core_remove_subnav_item( 'profile', 'edit' );
        bp_core_remove_subnav_item( 'profile', 'change-avatar' );
    }


  }

  $dsMembersProfile = DS_Member_Profile::getInstance();