<?php
/**
 *  WordPress initializing
 */
function mec_find_wordpress_base_path_ef()
{
    $dir = dirname(__FILE__);
    
    do
    {
        if(file_exists($dir.'/wp-load.php') and file_exists($dir.'/wp-config.php')) return $dir;
    }
    while($dir = realpath($dir.'/..'));
    
    return NULL;
}

define('BASE_PATH', mec_find_wordpress_base_path_ef().'/');
if(!defined('WP_USE_THEMES')) define('WP_USE_THEMES', false);

global $wp, $wp_query, $wp_the_query, $wp_rewrite, $wp_did_header;
require BASE_PATH.'wp-load.php';

/** @var $main MEC_main **/

// Clear Cache...
// $lastCacheClean = get_option( 'ds_last_cache_clean' );

// $difference = time() - $lastCacheClean;

// // If cache older than 15 minutes then lets have a freshen up!
// if ( (int) $difference > 900 ) {
//     wp_cache_flush();
//     update_option( 'ds_last_cache_clean', time() );
// }

// MEC libraries
$main = MEC::getInstance('app.libraries.main');

// Blogs
$blogs = array(1);

// Current Blog ID
$multisite = (function_exists('is_multisite') and is_multisite());
$current_blog_id = get_current_blog_id();

// Database
$db = $main->getDB();

// Multisite
if($multisite) $blogs = $db->select("SELECT `blog_id` FROM `#__blogs`", 'loadColumn');

foreach($blogs as $blog)
{
    // Switch to Blog
    if($multisite) switch_to_blog($blog);

    // MEC Settings
    $settings = $main->get_settings();

    // Check Last Run Date & Time
    // $latest_run = get_option('_ds_mec_discord_channels_created_last_run_datetime', NULL);
    // if ($latest_run and strtotime( $latest_run ) > strtotime( '-5 minutes', strtotime( $now ) ) ) {
    //     error_log( print_r( 'problem with latest_run...', true ) );
    //     continue;
    // }

    /**
     * Notification Sender Library
     * @var $notif MEC_notifications
     */
    $notif = $main->getNotifications();

    $discordEvent = new DS_MEC_Discord_Event();
    $endOccurrences = $discordEvent->get_occurences( $db, 'after' );

    foreach ( $endOccurrences as $occurrenceToEnd ) {
        $discordEvent->delete_event_entry( $occurrenceToEnd->post_id );
    }

    $startOccurrences = $discordEvent->get_occurences( $db );

    foreach ( $startOccurrences as $occurrenceToStart ) {
        $discordEvent->create_event_entry( $occurrenceToStart->post_id );
    }

}

// Switch to Current Blog
if($multisite) switch_to_blog($current_blog_id);

//echo sprintf(__('%s notification(s) sent.', 'mec'), $sent_notifications);
exit;