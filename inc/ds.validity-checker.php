<?php 

class DS_Validity_Checker {

    /**
	 * Singleton instance.
	 *
	 * @var DS_Validity_Checker
	 */
	private static $instance = null;
	/**
	 * Absolute path to this plugin directory.
	 *
	 * @var string
	 */
	private $path;

	/**
	 * Absolute url to this plugin directory.
	 *
	 * @var string
	 */
	private $url;

	/**
	 * Constructor.
	 */
	private function __construct() {

		$this->path = plugin_dir_path( __FILE__ );
		$this->url  = plugin_dir_url( __FILE__ );

		$this->setup();
	}

	/**
	 * Get the singleton instance.
	 *
	 * @return DS_Validity_Checker
	 */
	public static function get_instance() {

		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}

		return self::$instance;
    }
    
    /**
	 * Setup hooks.
	 */
	private function setup() {

		// load css/js on front end.
		add_action( 'wp_enqueue_scripts', array( $this, 'load_js' ) );
		// on wp-login.php for action=register.
		add_action( 'login_enqueue_scripts', array( $this, 'load_js' ) );
		// load assets on admin Add new user screen.
		add_action( 'admin_enqueue_scripts', array( $this, 'load_js' ) );
		// ajax check.
		// hook to ajax action.
		add_action( 'wp_ajax_check_validity', array( $this, 'ajax_check' ) );
		// hook to ajax action.
		add_action( 'wp_ajax_nopriv_check_validity', array( $this, 'ajax_check' ) );

    }
    
    /**
	 * Load required js
	 */
	public function load_js() {

		if ( $this->should_load_asset() ) {
			wp_enqueue_script( 'validity-checker-js', get_stylesheet_directory_uri() . '/assets/js/validity-checker.js', array( 'jquery' ) );

			$data = array(
                'selectors' => apply_filters( 'ds_validity_checker_selectors', 'input#signup_username, form#createuser input#user_login, #registerform input#user_login, .lwa-register input#user_login, #signup-form input#field_3, #mepr_squadron_name' ),
                'ajaxurl'   => admin_url( 'admin-ajax.php' )
			);

			wp_localize_script( 'validity-checker-js', '_dsValidityChecker', $data );
		}
    }
    
    /**
	 * Check whether to load assets or not?
	 *
	 * @return boolean whether to load assets or not
	 */
	public function should_load_asset() {
		global $pagenow;

		$load = false;

		if ( function_exists( 'bp_is_register_page' ) && bp_is_register_page() ) {
			$load = true;
		} elseif ( is_admin() && function_exists( 'get_current_screen' ) && get_current_screen()->id == 'user' && get_current_screen()->action == 'add' ) {
			$load = true;
		} elseif ( $pagenow == 'wp-login.php' && isset( $_GET['action'] ) && $_GET['action'] == 'register' ) {
			$load = true;
		} elseif ( class_exists( 'LoginWithAjax' ) && ! is_user_logged_in() ) {
			$load = true;
		} elseif ( is_singular( 'memberpressproduct' ) ) {
			$load = true;
		} elseif ( get_post_field( 'post_name' ) === 'create-a-squadron') {
			$load = true;
		}

		return apply_filters( 'ds_validity_checker_load_assets', $load );

    }
    
    public function ajax_check() {

        //post vars - check_type, check_value
        $checkType = empty( $_POST['check_type'] ) ? 'username' : $_POST['check_type'];

        if ( empty( $_POST['check_value'] ) ) {

			// if the value to check against is empty then what do we validate against - the execution will stop here.
			wp_send_json( array(
				'code'    => 'error',
				'message' => __( 'This can not be left empty!', 'digitalSquadrons' ),
			) );
		} 

        if ($checkType == 'username') {

            $username = sanitize_user( $_POST['check_value'] );

            if ( username_exists( $username ) ) {

                $message = array(
                    'code'    => 'taken',
                    'message' => __( 'The usename is taken, please choose another one.', 'digitalSquadrons' ),
                );
    
            }
    
            if ( empty( $message ) ) {
                // so all is well, but now let us validate.
                $check = $this->validate_username( $username );
    
                if ( empty( $check ) ) {
                    $message = array(
                        'code'    => 'success',
                        'message' => __( 'Congrats! The username is available.', 'digitalSquadrons' ),
                    );
                } else {
    
                    $message = array(
                        'code'    => 'error',
                        'message' => $check,
                    );
                }
            }


        } elseif( $checkType == 'groupname' ) {

			// @TODO: check username to make sure it is clean from excluded words, etc...
			//$groupname = sanitize_group_name( $_POST['check_value']);

			$groupname = sanitize_text_field( $_POST['check_value'] );

			if ( $this->groupname_exists( $groupname ) ) {
				$message = array(
                    'code'    => 'taken',
                    'message' => __( 'The name ' . $groupname . ' is taken, please choose another one.', 'digitalSquadrons' ),
                );
			}

			if ( empty( $message ) ) {

				//check name to see if it contains prohibited language and notify...
				$profanityValue =  bp_profanity_filter_get_parsed_content( $groupname );

				if ( $groupname !== $profanityValue ) {

					$message = array(
						'code'		=> 'error',
						'message'	=> __('Your name contains prohibited language, please remove', 'digitalSquadrons')
					);
				} else {

					$message = array(
						'code'		=> 'success',
						'message'	=> __('Congratulations, your squadron name is available', 'digitalSquadrons')
					);
				}
			}

		}
		
		wp_send_json( $message );
    }

    /**
	 * Helper function to check the username is valid or not,
	 * Thanks to @apeatling, taken from bp-core/bp-core-signup.php and modified for checking only the username
	 * original: bp_core_validate_user_signup()
	 *
	 * @return string nothing if validated else error string
	 * */
	private function validate_username( $username ) {
		$errors = new WP_Error();

		$username = sanitize_user( $username, true );

		if ( empty( $username ) ) {
			// must not be empty.
			$errors->add( 'username', __( 'Please enter a valid username.', 'digitalSquadrons' ) );
		}

		if ( function_exists( 'buddypress' ) ) {
			$username = preg_replace( '/\s+/', '', $username );

		}

		// check blacklist.
		$illegal_names = get_site_option( 'illegal_names' );
		if ( in_array( $username, (array) $illegal_names ) ) {
			$errors->add( 'username', __( 'That username is not allowed.', 'digitalSquadrons' ) );
		}

		// see if passed validity check.
		if ( ! validate_username( $username ) ) {
			$errors->add( 'username', __( 'Usernames can contain only letters, numbers, ., -, and @', 'digitalSquadrons' ) );
		}

		if ( strlen( $username ) < 4 ) {
			$errors->add( 'username', __( 'Username must be at least 4 characters', 'digitalSquadrons' ) );
		} elseif ( mb_strlen( $username ) > 60 ) {
			$errors->add( 'user_login_too_long', __( 'Username may not be longer than 60 characters.', 'digitalSquadrons' ) );
		}

		if ( strpos( ' ' . $username, '_' ) != false ) {
			$errors->add( 'username', __( 'Sorry, usernames may not contain the character "_"!', 'digitalSquadrons' ) );
		}

		/* Is the username all numeric? */
		$match = array();
		preg_match( '/[0-9]*/', $username, $match );

		if ( $match[0] == $username ) {
			$errors->add( 'username', __( 'Sorry, usernames must have letters too!', 'digitalSquadrons' ) );
		}

		/**
		 * Filters the list of blacklisted usernames.
		 *
		 * @param array $usernames Array of blacklisted usernames.
		 */
		$illegal_logins = (array) apply_filters( 'illegal_user_logins', array() );

		if ( in_array( strtolower( $username ), array_map( 'strtolower', $illegal_logins ) ) ) {
			$errors->add( 'invalid_username', __( 'Sorry, that username is not allowed.', 'digitalSquadrons' ) );
		}

		// Let others dictate us
		// the divine message to show the users in case of failure
		// success is empty, never forget that.
		return apply_filters( 'ds_validity_checker_username_error', $errors->has_errors() ? $errors->get_error_message() : '', $username );
	}

	/**
	 * Check if the given BuddyPress group name is duplicate. A group with same name already exists.
 	 *
 	 * @param string $groupname name to be checked.
 	 * @param int $groupID group id(exclude this group from test).
 	 *
 	 * @return bool
	 */
	public function groupname_exists( $groupname, $groupID = 0 ) {
		global $wpdb;

    	$bp    = buddypress();
    	$table = $bp->groups->table_name;
 
    	$sql = $wpdb->prepare( "SELECT id FROM {$table} WHERE name = %s", $groupname );
		// except this group, used for updating.
		if ( $groupID ) {
			$sql .= $wpdb->prepare( " AND id != %d", $groupID );
		}
 
    	return $wpdb->get_var( $sql );
	}
}

// instantiate.
DS_Validity_Checker::get_instance();
