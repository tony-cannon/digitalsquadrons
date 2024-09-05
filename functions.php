<?php
/*This file is part of buddyboss-theme-child, buddyboss-theme child theme.

All functions of this file will be loaded before of parent theme functions.
Learn more at https://codex.wordpress.org/Child_Themes.

Note: this function loads the parent stylesheet before, then child theme stylesheet
(leave it in place unless you know what you are doing.)
*/

/**
 * Write an entry to a log file in the uploads directory.
 * 
 * @since x.x.x
 * 
 * @param mixed $entry String or array of the information to write to the log.
 * @param string $file Optional. The file basename for the .log file.
 * @param string $mode Optional. The type of write. See 'mode' at https://www.php.net/manual/en/function.fopen.php.
 * @return boolean|int Number of bytes written to the lof file, false otherwise.
 */

// function ds_app_log( $entry, $mode = 'a', $file = 'ds-system' ) { 
// 	// Get WordPress uploads directory.
// 	$upload_dir = wp_upload_dir();
// 	$upload_dir = $upload_dir['basedir'];
// 	// If the entry is array, json_encode.
// 	if ( is_array( $entry ) ) { 
// 	$entry = json_encode( $entry ); 
// 	} 
// 	// Write the log file.
// 	$file  = $upload_dir . '/' . $file . '.log';
// 	$file  = fopen( $file, $mode );
// 	$bytes = fwrite( $file, current_time( 'mysql' ) . "::" . $entry . "\n" ); 
// 	fclose( $file ); 
// 	return $bytes;
// }

function dsel( $entry, $filename = 'UNKNOWN' ) {

	if ( is_object( $entry ) ) {
		$entry->file = $filename;
		$entry = print_r( $entry, true );
	} elseif ( is_array( $entry ) ) {
		$entry['file'] = $filename;
	} else {
		$entry = 'File: ' . $filename . ', ' . $entry;
	}
	error_log( $entry, 3, '/opt/bitnami/apps/wordpress/htdocs/wp-content/uploads/ds-system.log' );
}

function buddyboss_theme_child_enqueue_child_styles() {
	$parent_style = 'parent-style'; 
		wp_enqueue_style($parent_style, get_template_directory_uri() . '/style.css' );
		wp_enqueue_style( 
			'child-style', 
			get_stylesheet_directory_uri() . '/style.css',
			array( $parent_style ),
			wp_get_theme()->get('Version') );
	}	
add_action( 'wp_enqueue_scripts', 'buddyboss_theme_child_enqueue_child_styles' );

function ds_scripts() {
	wp_enqueue_script(
        'ds-nouveau',
        get_stylesheet_directory_uri() . '/assets/js/ds-nouveau.js',
        array( 'jquery', 'bp-nouveau' ),
		false,
		true
    );

	wp_localize_script( 'ds-nouveau', 'ds_ajax', array( 'ajax_url' => admin_url( 'admin-ajax.php' ),'nonce'=> wp_create_nonce( 'ds_ajax_nonce' ) ) );

	// PICO SCRIPT FOR FORM WORK
	if ( bp_current_action() == 'group-events' ) {
        wp_enqueue_style( 'pico-style', get_stylesheet_directory_uri() . '/assets/css/pico.css', array(), '1.0.0' );
    }

}
add_action( 'wp_enqueue_scripts', 'ds_scripts', 999 );

function ds_cleanpage_add_scripts_styles() {
	?>
	<link rel="stylesheet" href="<?php echo get_stylesheet_directory_uri() . '/assets/css/pico.min.css' ?>">
	<?php
}
add_action( 'ds_cleanpage_scripts_styles', 'ds_cleanpage_add_scripts_styles' );

/*Write here your own functions */
require_once( 'inc/classes/ds.group-custom-filters.class.php' );

function ds_bootstrap()
{
	// This is for RestCord API...
	//include __DIR__.'/vendor/autoload.php';
	require_once( 'vendor/autoload.php' );

    // Here we load from our includes directory
	// This considers parent and child themes as well  
	require_once( 'inc/classes/ds.group-meta.class.php' );
	require_once( 'inc/ds.exclude-fields.class.php' );
	require_once( 'inc/ds.validity-checker.php' ); 
	//require_once( 'inc/classes/ds.group-events.class.php' ); 

	// SCREENS
	require_once( 'inc/buddypress/screens/group-location.php' );
	require_once( 'inc/buddypress/screens/edit-rules.php' );
	//require_once( 'inc/buddypress/screens/member-location.php' );
	//require_once( 'inc/classes/ds.group-custom-filters.class.php' );

	/**
	 * MEMBERPRESS FILES
	 */
	require_once( 'inc/ds.memberpress.class.php' );

	/**
	 * BUDDYPRESS FILES
	 */
	require_once( 'inc/buddypress/ds.groups-functions.php');
	require_once( 'inc/buddypress/ds.members-functions.php');
	require_once( 'inc/buddypress/ds.members-profile.class.php');

	/**
	 * DISCORD FILES
	 */
	require_once( 'inc/discord/functions.php' );
	require_once( 'inc/discord/classes/hooks.php' );
	require_once( 'inc/discord/classes/oauth.php' );

	/**
	 * MEC FILES
	 */
	require_once( 'inc/mec/functions.php' );
	require_once( 'inc/mec/custom-post-status.php' );
	require_once( 'inc/mec/classes/fes.php' );
	require_once( 'inc/mec/classes/discord-coordinator.php' );

	/**
	 * Email
	 */
	require_once( 'inc/classes/ds.email.assign.class.php' );
	require_once( 'inc/classes/ds.email.templates.table.class.php' );

}
add_action( 'after_setup_theme', 'ds_bootstrap' );

/**
 * Remove actions from inaccessible classes
 * 
 * url: https://wordpress.stackexchange.com/questions/36013/remove-action-or-remove-filter-with-external-classes
 * 
 * @var string $action
 * @var string $class
 * @var string $method
 */
function ds_remove_class_action ( $action, $class, $method ) {
    global $wp_filter;

    if ( isset( $wp_filter[$action] ) ) {
        $len = strlen( $method );
        foreach ( $wp_filter[$action] as $pri => $actions ) {
            foreach ( $actions as $name => $def ) {
                if ( substr($name,-$len) == $method ) {
                    if ( is_array( $def['function'] ) ) {
                        if ( get_class($def['function'][0]) == $class ) {
                            if ( is_object( $wp_filter[$action] ) && isset( $wp_filter[$action]->callbacks ) ) {
                                unset( $wp_filter[$action]->callbacks[$pri][$name] ) ;
                            } else {
                                unset( $wp_filter[$action][$pri][$name] ) ;
                            }
                        }
                    }
                }
            }
        }
    }
}

function ds_bp_ajax_querystring_debug( $querystring, $object ) {
	if ( $object == 'groups' ) {
		error_log( print_r(wp_parse_args( $querystring), true ) );	
	}
	
	error_log( print_r( $object, true ) );

	return $querystring;
}
//add_filter('bp_ajax_querystring', 'ds_bp_ajax_querystring_debug', 30, 2 );

add_filter('Yoast\WP\SEO\post_redirect_slug_change', '__return_true' );
add_filter('Yoast\WP\SEO\term_redirect_slug_change', '__return_true' );