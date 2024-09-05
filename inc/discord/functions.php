<?php

/**
 * Formulate the Avatar URL with discord...
 * 
 * @param   $avatarHash (string)
 * @param   $userID (int)
 * @param   $discordID (int)
 * @param   $size (string)
 * 
 * @return  $url (string)
 */
function dsGetAvatarURL( $avatarHash, $userID, $discordID = false, $size = false ) {
    global $wpdb;

    $url = '';

    // If we don't have the discord ID then we need fetch it...
    if ( $discordID === false ) {
        $discordTable = $wpdb->prefix . 'ds_discord_oauth';
        $discordUser = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $discordTable WHERE user_id = %d", $userID ) );
        $discordID = $discordUser->discord_id;
    }

    if ( isset( $discordID ) && $discordID !== false ) {
        
        if ( strlen($avatarHash) !== 0 ) {
            $url = 'https://cdn.discordapp.com/avatars/' . $discordID . '/' . $avatarHash . '.png';

            if (isset($size) && $size !== false ) {
                $url = $url . '?size="' . $size . '"';
            }

            //error_log($url);
        }
    }

    return $url;
}

/**
 * Get the row of data from Discord User Table...
 * 
 * @param   $userID (int)
 * 
 * @return  $discordUser (array)
 */
function dsGetDiscordUser( $userID ) {
    global $wpdb;

    $discordTable = $wpdb->prefix . 'ds_discord_oauth';
    $discordUser = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $discordTable WHERE user_id = %d", intval( $userID ) ) );
    //error_log( print_r($wpdb->get_row($wpdb->prepare( "SELECT * FROM $discordTable WHERE discord_id = %d", intval( $userID ) ) ), true) );

    return $discordUser;

}

/**
 * Create user roles for a Wing's Server.
 * 
 * @var postID integer
 * @var botToken string
 * @var serverID integer
 * @var permissions integer
 */
function dsCreateDefaultWingRoles( $postID, $botToken, $serverID, $permissions = 17179935744 ) {
    $client = new RestCord\DiscordClient(['token' => $botToken ]);
    $result = array();

    // Let's get the exisitng roles for the server and check consistency.
    $existingRoles = $client->guild->getGuildRoles([
        'guild.id'  =>  (int) $serverID
    ]);

    error_log( print_r( $existingRoles, true ) );

    foreach ( $existingRoles as $existingRole ) {
        $includeList[ $existingRole->name ] = $existingRole->id;
    }

    error_log( print_r( $includeList, true ) );

    $newRoles = array(
        'Squadron Commander'   => 2274343,
        'Pilot'             => 3911117
    );

    error_log( print_r( $newRoles, true ) );

    foreach ( $newRoles as $role => $roleColor ) {

        if ( ! array_key_exists( $role, $includeList ) ) {

            $roleObj = $client->guild->createGuildRole(
                [
                    'guild.id'      =>  (int) $serverID,
                    'name'          =>  (string) $role,
                    'permissions'   =>  (int) $permissions,
                    'color'         =>  (int) $roleColor,
                    'hoist'         =>  true,
                    'mentionable'   =>  true
                ]
            );

            error_log( print_r( $roleObj, true ) );
    
            if ( is_object( $roleObj ) ) {
                update_post_meta( $postID, "_ds_discord_group_{$role}_role_id", $roleObj->id );
                $result[ $role ] = $roleObj->id;
                error_log( print_r( $result, true ) );
            }

        }
    }

    return array_merge( $includeList, $result );
}

function ds_discord_is_member_still_attached_to_server( $userID, $groupID, $platform, $serverOnly = true, $admin = false ) {
    $chosenDiscordServerID = groups_get_groupmeta( $groupID, '_ds_discord_group_server_id' );
    // 1. Get all group types for a particular platform
    $groupTypes = get_posts( array(
        'post_type' => 'bp-group-type',
        'numberposts' => -1,
        'tax_query' => array(
            array(
                'taxonomy' => 'ds-software-platform',
                'field' => 'term_id',
                'terms' => $platform,
                'operator' => 'IN',
            )
        )
    ));

    foreach ( $groupTypes as $typeObj ) {
        $groupTypeSlugs[] = $typeObj->post_name;
    }

    // 2. Get all the groups for this platform that the user is member
    $usersGroups = groups_get_groups( array(
        'user_id'           => (int) $userID,
        'group_type'        => $groupTypeSlugs
    ));

    $usersGroups = wp_list_pluck( $usersGroups['groups'], 'id' );

    // We're just looking at other groups, so lets remove the current group if present...
    if ( ( $key = array_search( $groupID, $usersGroups ) ) !== false ) {
        unset( $usersGroups[$key] );
    }

    $nMatches = 0;

    $userAdminGroups = BP_Groups_Member::get_is_admin_of( (int) $userID );
    $userAdminGroups = wp_list_pluck( $userAdminGroups['groups'], 'id' );

    // 3. If Admin (bool), get all the groups where the user is admin and intersect with (2) so no duplicates.
    if ( $admin ) {
        $usersGroups = array_intersect( $usersGroups, $userAdminGroups ); // Only returns groups where user is admin.
    } else {
        $usersGroups = array_diff( $usersGroups, $userAdminGroups );
    }

    if ( $serverOnly ) {
        // 4. Get all the Discord Server IDs for remaining groups.
        foreach ( $usersGroups as $group ) {
            $discordServerID = groups_get_groupmeta( (int) $group, '_ds_discord_group_server_id' );
            // 5. foreach id, if they match then nMatches++ 
            if ( $discordServerID === $chosenDiscordServerID ) {
                $nMatches++;
            }
        }
    }

    $nMatches = $serverOnly ? $nMatches : count( $usersGroups );

    error_log( print_r( 'nMatches...', true ) );
    error_log( print_r( $nMatches, true ) );

    // 6. if nMatches === 1, then we can delete user from the server - return true, else false.
    if ( $nMatches > 0 ) {
        return true;
    } else {
        return false;
    }
}

function dsSendPostToDiscord($id, $post) {
    /**
     * get post category and webhook url
     */
    $categories = get_the_category( $id );

    if ( ! empty( $categories ) ) {
        //We are just going to post to first category webhook. WP_Term is an array containing objects...
        //TODO: if multiple categories, grab webhook for each category and post inidividually...
        $category = $categories[0];

        if ( !$category instanceof WP_Term ) {
            error_log( print_r('Discord Webhook failure for ' . $category, true ) );
            exit;
        }
    }

    $webhookURL = get_term_meta( $category->term_id, 'ds_discord_post_category_webhook_url', true );
    
    if ( $webhookURL !== null ) {
        error_log( $webhookURL );
        $ch = curl_init( $webhookURL );
        $timestamp = date("c", strtotime("now"));

        // Lets get some Wordpressy bits...
        $author_id      = $post->post_author;
        $name           = get_the_author_meta('display_name', $author_id);
        $title          = $post->post_title;
        $permalink      = get_permalink($id);

        // Lets get some Discordy stuff...
        $discordUser = dsGetDiscordUser( $author_id );
        error_log( print_r( $discordUser, true ));
        $avatar = dsGetAvatarURL( $discordUser->avatar_hash, $author_id, $discordUser->discord_id );
        $postCover = wp_get_attachment_image_url($id);
        $postThumb = get_the_post_thumbnail_url($id);

        $profileDomain = bp_core_get_user_domain( intval($author_id) );


        $message = $name . " has just posted " . $title . "! Go check it out: " . $permalink;

        $jsonData = json_encode([
                            // Message
                            //"content" => "Hello World! This is message line ;) And here is the mention, use userID <@12341234123412341>",
                            "content" => $message,
                            
                            // Username
                            "username" => $name,

                            // Avatar URL.
                            // Uncoment to replace image set in webhook
                            "avatar_url" => $avatar . '?size=512',

                            // Text-to-speech
                            "tts" => false

                            // File upload
                            // "file" => "",

                            // TODO: This embeds section is causing errors?...
                            
                            // Embeds Array
                            // "embeds" => [
                            //     [
                            //         // Embed Title
                            //         "title" => $title,

                            //         // Embed Type
                            //         "type" => "rich",

                            //         // Embed Description
                            //         //"description" => "Description will be here, someday, you can mention users here also by calling userID <@12341234123412341>",

                            //         // URL of title link
                            //         "url" => $permalink,

                            //         // Timestamp of embed must be formatted as ISO8601
                            //         "timestamp" => $timestamp,

                            //         // Embed left border color in HEX
                            //         "color" => hexdec( "3366ff" ),

                            //         // Footer
                            //         "footer" => [
                            //             "text" => $profileDomain,
                            //             "icon_url" => $avatar . '?size=100',
                            //         ],

                            //         // Image to send
                            //         "image" => [
                            //             "url" => $postCover
                            //         ],

                            //         // Thumbnail
                            //         "thumbnail" => [
                            //             "url" => $postThumb
                            //         ],

                            //         // Author
                            //         "author" => [
                            //             "name" => $name,
                            //             "url" => $profileDomain
                            //         ],

                            //         // Additional Fields array
                            //         "fields" => [
                            //             // Field 1
                            //             [
                            //                 "name" => "Field #1 Name",
                            //                 "value" => "Field #1 Value",
                            //                 "inline" => false
                            //             ],
                            //             // Field 2
                            //             [
                            //                 "name" => "Field #2 Name",
                            //                 "value" => "Field #2 Value",
                            //                 "inline" => true
                            //             ]
                            //             // Etc..
                            //         ]
                            //     ]
                            // ]
                            , JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE ]);

        // $author     = $post->post_author;
        // $name       = get_the_author_meta('display_name', $author);
        // $title      = $post->post_title;
        // $permalink  = get_permalink($id);

        // $message = $name . " has just posted " . $title . "! Go check it out: " . $permalink;
        error_log(print_r($jsonData,true));
        curl_setopt( $ch, CURLOPT_HTTPHEADER, array('Content-type: application/json'));
        curl_setopt( $ch, CURLOPT_POST, 1);
        curl_setopt( $ch, CURLOPT_POSTFIELDS, $jsonData);
        curl_setopt( $ch, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt( $ch, CURLOPT_HEADER, 0);
        curl_setopt( $ch, CURLOPT_RETURNTRANSFER, 1);

        $response = curl_exec( $ch );
        // If you need to debug, or find out why you can't send message uncomment line below, and execute script.
        //echo $response;
        if (curl_errno($ch)) {
            $error_msg = curl_error($ch);
        }
        curl_close($ch);
        
        if (isset($error_msg)) {
            error_log( print_r( $error_msg ) );
        }
        
    }
}
//add_action('publish_post', 'dsSendPostToDiscord', 10, 2);