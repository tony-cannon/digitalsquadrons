<?php 

// Exit if accessed directly.

use RestCord\Model\Guild\GuildMember;

defined( 'ABSPATH' ) || exit;

class DS_Discord_Hooks {

    private static $instance = null;

    private function __construct() {
        $this->addHooks();
    }
    
    public static function getInstance() {

        if ( self::$instance == null ) {
            self::$instance = new DS_Discord_Hooks();
        }

        return self::$instance;
    }

    public function addHooks() {
        add_action( 'groups_membership_accepted', array( $this, 'ds_discord_groups_add_user_to_channels'), 20, 3 );
        add_action( 'groups_accept_invite', array( $this, 'ds_discord_groups_add_user_to_channels'), 20, 3 );
        add_action( 'groups_leave_group', array( $this, 'ds_discord_groups_member_leaves_from_channels' ), 99, 2 );
        add_action( 'groups_remove_member', array( $this, 'ds_discord_groups_member_leaves_from_channels'), 99, 2 );

        add_action( 'ds_member_update_aicraft', array( $this, 'ds_discord_member_update_aircraft' ), 99, 3 );
        add_action( 'ds_member_update_platforms', array( $this, 'ds_discord_member_update_platforms' ), 99, 3 );

        add_action( 'groups_before_delete_group', array( $this, 'ds_discord_group_deleted' ) );

        add_action( 'groups_membership_requested', array( $this, 'ds_discord_group_notify_join_request'), 99, 4 );
    }

    /**
     * 
     */
    public function ds_discord_groups_add_user_to_channels( $userID, $groupID, $extra ) {

        $userDiscordID = ds_member_get_discord_value( 'discord_id', $userID );
        $discordOptions = get_option( 'discord-credentials', array() );
        $client = new RestCord\DiscordClient(['token' => $discordOptions['ds_discord_primary_bot_token'] ]);

        //groups_update_groupmeta( $groupID, "_ds_discord_group_server_id", 940581012390445057 );
        $typeServerID = groups_get_groupmeta( $groupID, "_ds_discord_group_server_id" );
        $groupChannelID = groups_get_groupmeta( $groupID, '_ds_discord_group_text_channel_id', true );

        // Get the roleID for pilot from the server list.
        $existingRoles = $client->guild->getGuildRoles([
            'guild.id'  =>  (int) $typeServerID
        ]);

        error_log( print_r( 'exisiting roles...', true) );
        error_log( print_r( $existingRoles, true) );

        foreach ( $existingRoles as $role ) {
            if ( $role->name === 'Pilot' ) {
                $pilotRoleID = $role->id;
            }
        }

        // If the user is not a member of the server then lets add with the basic role of pilot...
        $client->guild->addGuildMember(
            [
                'guild.id'      =>  (int) $typeServerID,
                'user.id'       =>  (int) $userDiscordID,
                'access_token'  =>  (string) get_user_meta( (int) $userID, '_ds_user_access_token', true )
            ]
        );

        $userAdd = $client->guild->addGuildMemberRole(
            [
                'guild.id'      => (int) $typeServerID,
                'user.id'       => (int) $userDiscordID,
                'role.id'       => (string) groups_get_groupmeta( $groupID, '_ds_discord_group_pilot_role_id' )
            ]
        );

        $userAdd = $client->guild->addGuildMemberRole(
            [
                'guild.id'      => (int) $typeServerID,
                'user.id'       => (int) $userDiscordID,
                'role.id'       => (string) $pilotRoleID
            ]
        );
        
        // Send a message to group welcoming the new member...
        $joiningUser = $client->user->getUser(
            [
                'user.id'           => (int) $userDiscordID
            ]
        );

        $client->channel->createMessage(
            [
                'channel.id'            => (int) $groupChannelID,
                'content'               => '**Squadron Info:** Welcome ' . $joiningUser->username . ' to the Squadron!. Please can all pilots take the opportunity to say "Hello!"'
            ]
            );
    }

    /**
     * If a user leaves a group on their own accord or is removed by admin then this is fired.
     */
    public function ds_discord_groups_member_leaves_from_channels( $groupID, $userID ) {
        error_log( print_r( 'its been called', true ) );

        $userDiscordID = ds_member_get_discord_value( 'discord_id', $userID );
        $discordOptions = get_option( 'discord-credentials', array() );
        $client = new RestCord\DiscordClient(['token' => $discordOptions['ds_discord_primary_bot_token'] ]);
        $platform = groups_get_groupmeta( $groupID, '_ds_group_platform' );

        $typeServerID = groups_get_groupmeta( $groupID, "_ds_discord_group_server_id" );
        $groupChannelID = groups_get_groupmeta( $groupID, '_ds_discord_group_text_channel_id', true );

        // Get the roleID for pilot from the server list.
        $existingRoles = $client->guild->getGuildRoles([
            'guild.id'  =>  (int) $typeServerID
        ]);

        foreach ( $existingRoles as $role ) {
            if ( $role->name === 'Pilot' ) {
                $pilotRoleID = $role->id;
            }
        }
    
        // Remove specific Squadron role ID
        $client->guild->removeGuildMemberRole(
            [
                'guild.id'      => (int) $typeServerID,
                'user.id'       => (int) $userDiscordID,
                'role.id'       => (string) groups_get_groupmeta( $groupID, '_ds_discord_group_pilot_role_id' )
            ]
        );  
        
        /**
         * Now we need to check if Pilot is member of any other squadrons on this server? If they are, then the following roles need to remain.
         */
        if ( ! ds_discord_is_member_still_attached_to_server( $userID, $groupID, $platform ) ) {
            // Remove generic Pilot role ID
            $client->guild->removeGuildMemberRole(
                [
                    'guild.id'      => (int) $typeServerID,
                    'user.id'       => (int) $userDiscordID,
                    'role.id'       => (string) $pilotRoleID
                ]
            ); 
            // Remove user from server completely. 
            $client->guild->removeGuildMember(
                [
                    'guild.id'      =>  (int) $typeServerID,
                    'user.id'       =>  (int) $userDiscordID
                ]
            );   
        }

        // Send a message to group notifying of exit...
        $joiningUser = $client->user->getUser(
            [
                'user.id'           => (int) $userDiscordID
            ]
        );

        $client->channel->createMessage(
            [
                'channel.id'            => (int) $groupChannelID,
                'content'               => '**Squadron Info:** Good-Bye ' . $joiningUser->username . '!. Unfortunately they have decided to leave the Squadron - was it something I said!?'
            ]
            );
    }
    
    public function ds_discord_member_update_aircraft( $aircraftType, $previousAircraft, $userID ) {
        $userDiscordID = ds_member_get_discord_value( 'discord_id', $userID );
        $discordOptions = get_option( 'discord-credentials', array() );
        $client = new RestCord\DiscordClient(['token' => $discordOptions['ds_discord_primary_bot_token'] ]);
        // Remove previous aircraft type persmissions...
        // Add new aircraft type permissions...
        // Delete all previous aircraft roles
        
        $toKeep = array_merge( array_intersect( $aircraftType, $previousAircraft ), array_diff( $aircraftType, $previousAircraft ) ); // Shows what are in both

        if ( is_array( $previousAircraft ) ) {
            foreach ( $previousAircraft as $type ) {
                if ( ! in_array( $type, $toKeep ) && in_array( $type, $previousAircraft ) ) {
                    $typeObj = get_page_by_path( $type, OBJECT, 'bp-group-type' );
                    $roleID = get_post_meta( $typeObj->ID, '_ds_discord_aircraft_role_id', true );
                    // Remove role for aircraft.
                    $client->guild->removeGuildMemberRole(
                        [
                            'guild.id'          => (int) $discordOptions['ds_discord_server_id'],
                            'user.id'           => (int) $userDiscordID,
                            'role.id'           => (string) $roleID
                        ]
                    );
                }
                
            }
        }
        
        // Add new aircraft roles...
        foreach ( $aircraftType as $type ) {

            if ( in_array( $type, $toKeep ) && ! in_array( $type, $previousAircraft ) ) {
                // check of aircraftType has roleID
                $typeObj = get_page_by_path( $type, OBJECT, 'bp-group-type' );
                $platformObj = current( get_the_terms( $typeObj->ID, 'ds-software-platform' ) );
        
                $roleID = get_post_meta( $typeObj->ID, '_ds_discord_aircraft_role_id', true );

                if ( ! $roleID ) {
                    // Create new role for aicraft type.
                    $roleObj = $client->guild->createGuildRole(
                        [
                            'guild.id'          => (int) $discordOptions['ds_discord_server_id'],
                            'name'              => (string) $typeObj->post_title,
                            //'permissions'       => '',
                            //'color'             => '',
                            'hoist'             => false,
                            'mentionable'       => true
                        ]
                    );

                    if ( is_object( $roleObj ) ) {
                        update_post_meta( $typeObj->ID, '_ds_discord_aircraft_role_id', $roleObj->id );
                        $roleID = $roleObj->id;
                    }
                }

                $textChannelID = get_post_meta( $typeObj->ID, '_ds_discord_aircraft_text_channel_id', true );
                $voiceChannelID = get_post_meta( $typeObj->ID, '_ds_discord_aircraft_voice_channel_id', true );
                $parentCategoryID = get_term_meta( $platformObj->term_id, '_ds_discord_platform_aircraft_category_id', true );
                if ( ! $textChannelID ) {
                    // We need to create a channel for this type...
                    $textResult = $client->guild->createGuildChannel(   
                        [
                            'guild.id'              => (int) $discordOptions['ds_discord_server_id'],
                            'name'                  => strtolower( preg_replace("/[^\w]+/", "-", $typeObj->post_title ) ) . '-chat',
                            'type'                  => 0,
                            'topic'                 => $typeObj->post_title . ' for ' . $platformObj->name . ' Related Matters - Text Chat',
                            'user_limit'            => 0, //0 is unlimited
                            'rate_limit_per_user'   => 10,
                            'permission_overwrites' => [
                                    [   
                                        'id'    => (int) $discordOptions['ds_discord_everyone_role_id'], 
                                        'type'  => 'role', 
                                        'deny'  => (string) $discordOptions['ds_discord_channel_role_deny_everything_id'] 
                                    ],
                                    [
                                        'id'    => (int) $roleObj->id,
                                        'type'  => 'role',
                                        'allow' => (string) $discordOptions['ds_discord_default_pilot_channel_permission_id'],
                                        'deny'  => (string) $discordOptions['ds_discord_default_channel_role_deny_permission_id']
                                    ]
                            ],
                            'parent_id'             => (int) $parentCategoryID,
                            'nsfw'                  => false
                        ]
                    );

                    if ( is_object( $textResult ) ) {
                        update_post_meta( $typeObj->ID, '_ds_discord_aircraft_text_channel_id', $textResult->id );
                    }

                }

                if ( ! $voiceChannelID ) {

                    $voiceResult = $client->guild->createGuildChannel(   
                        [
                            'guild.id'              => (int) $discordOptions['ds_discord_server_id'],
                            'name'                  => strtolower( preg_replace("/[^\w]+/", "-", $typeObj->post_title ) ) . '-comms',
                            'type'                  => 2,
                            'topic'                 => $typeObj->post_title . ' for ' . $platformObj->name . ' Related Matters - Voice Chat',
                            'user_limit'            => 10, //0 is unlimited
                            'permission_overwrites' => [
                                [   
                                    'id'    => (int) $discordOptions['ds_discord_everyone_role_id'], 
                                    'type'  => 'role', 
                                    'deny'  => (string) $discordOptions['ds_discord_channel_role_deny_everything_id'] 
                                ],
                                [
                                    'id'    => (int) $roleObj->id,
                                    'type'  => 'role',
                                    'allow' => (string) $discordOptions['ds_discord_default_pilot_channel_permission_id'],
                                    'deny'  => (string) $discordOptions['ds_discord_default_channel_role_deny_permission_id']
                                ]
                            ],
                            'parent_id'             => (int) $discordOptions['ds_discord_squadron_voice_parent_id'],
                            'nsfw'                  => false
                        ]
                    );

                    if ( is_object( $voiceResult ) ) {
                        update_post_meta( $typeObj->ID, '_ds_discord_aircraft_voice_channel_id', $voiceResult->id );
                    }

                }

                // Give the user the role...
                $client->guild->addGuildMemberRole(
                    [
                        'guild.id'          => (int) $discordOptions['ds_discord_server_id'],
                        'user.id'           => (int) $userDiscordID,
                        'role.id'           => (string) $roleID
                    ]
                );

            } 
        }

    }

    public function ds_discord_member_update_platforms( $platforms, $previousPlatforms, $userID ) {
        $userDiscordID = ds_member_get_discord_value( 'discord_id', $userID );
        $discordOptions = get_option( 'discord-credentials', array() );
        $client = new RestCord\DiscordClient(['token' => $discordOptions['ds_discord_primary_bot_token'] ]);

        $toKeep = array_merge( array_intersect( $platforms, $previousPlatforms ), array_diff( $platforms, $previousPlatforms ) ); // Shows what are in both
         
        foreach( $previousPlatforms as $platform ) {
            if ( ! in_array( $platform, $toKeep ) && in_array( $platform, $previousPlatforms ) ) {
                $term = get_term_by( 'slug', $platform, 'ds-software-platform' );
                $termRoleID = get_term_meta( $term->term_id, '_ds_discord_platform_role_id', true );
                if ( $termRoleID ) {
                    // Remove from roles...
                    $client->guild->removeGuildMemberRole(
                        [
                            'guild.id'          => (int) $discordOptions['ds_discord_server_id'],
                            'user.id'           => (int) $userDiscordID,
                            'role.id'           => (string) $termRoleID
                        ]
                    );
                }
            }
        }

        foreach ( $platforms as $platform ) {
            if ( in_array( $platform, $toKeep ) && ! in_array( $platform, $previousPlatforms ) ) {
                $term = get_term_by( 'slug', $platform, 'ds-software-platform' );
                $termRoleID = get_term_meta( $term->term_id, '_ds_discord_platform_role_id', true );
                if ( $termRoleID ) {
                    // Add to roles...
                    $client->guild->addGuildMemberRole(
                        [
                            'guild.id'          => (int) $discordOptions['ds_discord_server_id'],
                            'user.id'           => (int) $userDiscordID,
                            'role.id'           => (string) $termRoleID
                        ]
                    );
                }   
            }
        }
    }

    /**
     * Housekeeping Discord side when deleting a squadron...
     * 
     * @var groupid integer
     */
    public function ds_discord_group_deleted( $groupID ) {
        $discordOptions = get_option( 'discord-credentials', array() );
        $client = new RestCord\DiscordClient(['token' => $discordOptions['ds_discord_primary_bot_token'] ]);
        /**
         * STAGE ONE: GROUP LEVEL - Remove Group Specific Roles and Channels
         */
        $typeServerID = groups_get_groupmeta( $groupID, '_ds_discord_group_server_id' );
        $allChannels[] = groups_get_groupmeta( $groupID, '_ds_discord_group_text_channel_id' );
        $allChannels[] = groups_get_groupmeta( $groupID, '_ds_discord_group_voice_channel_id' );
        $allChannels[] = groups_get_groupmeta( $groupID, '_ds_discord_group_category_id' );

        if ( strlen( $typeServerID ) > 0 ) {

            // Get the roleID for pilot from the server list.
            $existingRoles = $client->guild->getGuildRoles([
                'guild.id'  =>  (int) $typeServerID
            ]);

        }
        foreach ( $existingRoles as $role ) {
            if ( $role->name === 'Pilot' ) {
                $pilotRoleID = $role->id;
            }
        }

        foreach ( $allChannels as $key => $channelID ) {
            if ( $channelID ) {
                try {
                    // Delete Channels...
                    $client->channel->deleteOrcloseChannel(
                        [
                            'channel.id'    => (int) $channelID
                        ]
                    );
                } catch (\Exception $e) {
                    error_log( print_r( $e, true ) );
                }
            }  
        }

        $roleID = groups_get_groupmeta( $groupID, "_ds_discord_group_pilot_role_id" );

        if ( $roleID ) {
            try {
                $client->guild->deleteGuildRole(
                    [
                        'guild.id'  => (int) $typeServerID,
                        'role.id'   => (string) $roleID
                    ]
                );
            } catch (\Exception $e) {
                error_log( print_r( $e, true ) );
            }
        }

        // Get the Discord Servers Count No.
        $serverPostID = groups_get_groupmeta( $groupID, '_ds_discord_group_server_post_id' );
        $serverCount = get_post_meta( (int) $serverPostID, '_ds_server_squadron_count', true );
        // Deduct 1 from the server and update...
        $serverCount = (int) $serverCount - 1;
        update_post_meta( $serverPostID, '_ds_server_squadron_count', $serverCount );
        $platform = groups_get_groupmeta( $groupID, '_ds_group_platform' );

        // 1. get list of all members of the group and list of admins.
        $groupAdmins = groups_get_group_admins( $groupID );
        $allMembersIncAdmins = groups_get_group_members( array( 
              'group_id'            => $groupID,
              'exclude_admins_mods' => false
        ));

        /**
         * STAGE TWO: SERVER LEVEL - Remove user's roles at server level
         */

        // Check to see if SC of any other groups on the server, if not then remove user's SC role for server...
        foreach ( $groupAdmins as $admin ) {
            if ( ! ds_discord_is_member_still_attached_to_server( $admin->user_id, $groupID, $platform, true, true ) ) {
                // Remove SC role from the server for this user.
                try{
                    $client->guild->removeGuildMemberRole(
                        [
                            'guild.id'      => (int) $typeServerID,
                            'user.id'       => (int) ds_member_get_discord_value( 'discord_id', $admin->user_id ),
                            'role.id'       => (string) get_post_meta( $serverPostID, '_ds_discord_platform_squadron_commander_role_id', true )
                        ]
                    );
                } catch ( \Exception $e ) {
                    error_log( print_r( $e, true ) );
                }
                
            }
        }
        // Check to see if all members are pilots on other groups on the server, if not then remove user's pilot role and remove from server.
        foreach ( $allMembersIncAdmins['members'] as $member ) {
            $mDiscordID = ds_member_get_discord_value( 'discord_id', $member->ID );
            if ( ! ds_discord_is_member_still_attached_to_server( $member->ID, $groupID, $platform ) ) {
                // Remove Pilot role and remove from server.
                try {
                    $client->guild->removeGuildMemberRole(
                        [
                            'guild.id'      => (int) $typeServerID,
                            'user.id'       => (int) $mDiscordID,
                            'role.id'       => (string) $pilotRoleID
                        ]
                    );

                    // Remove user from server completely. 
                    $client->guild->removeGuildMember(
                        [
                            'guild.id'      =>  (int) $typeServerID,
                            'user.id'       =>  (int) $mDiscordID
                        ]
                    );   
                } catch ( \Exception $e ) {
                    error_log( print_r( $e, true ) );
                }
            }
        }

        /**
         * STAGE THREE: PLATFORM LEVEL - check to see if user is SC for this platform type on any other servers
         */
        foreach ( $groupAdmins as $admin ) {
            if ( ! ds_discord_is_member_still_attached_to_server( $admin->user_id, $groupID, $platform, false, true ) ) {
                // We need to remove from the platform SC-Only channel on the hanger server...
                try {
                    $client->guild->removeGuildMemberRole(
                        [
                            'guild.id'      => (int) $discordOptions['ds_discord_server_id'],
                            'user.id'       => (int) ds_member_get_discord_value( 'discord_id', $admin->user_id ),
                            'role.id'       => (string) get_term_meta( (int) $platform, '_ds_discord_platform_squadron_commander_role_id', true )
                        ]
                    );
                } catch ( \Exception $e ) {
                    error_log( print_r( $e, true ) );
                }
            }
        }   
    }

    public function ds_discord_group_notify_join_request( $userID, $admins, $groupID, $requestID ) {
        // Get the link for the group
        error_log( print_r( 'admins...', true ) );
        error_log( print_r( $admins, true ) );

        foreach ( $admins as $admin ) {
            $userGroupsOfType = groups_get_groups( array(
                'user_id'           => $userID,
                'group_type__in'    => array(  )
            ) );
        }
        $groupChannelID = groups_get_groupmeta( $groupID, '_ds_discord_group_text_channel_id', true );

        if ( $groupChannelID ) {
            $discordOptions = get_option( 'discord-credentials', array() );
            $client = new RestCord\DiscordClient(['token' => $discordOptions['ds_discord_primary_bot_token'] ]);

            foreach ( $admins as $admin ) {
                $adminDiscordIDs[ $admin->user_id ] = ds_member_get_discord_value( 'discord_id', $admin->user_id );
            }

            $furtherMessage = '';

            if ( isset( $_POST['group-request-membership-comments'] ) ) {
                $furtherMessage = '`**Further Message Reads...** ' . sanitize_textarea_field( $_POST['group-request-membership-comments'] ) . '`';
            }
    
            $requestingUserDiscordID = ds_member_get_discord_value( 'discord_id', $userID );

            $requestingUser = $client->user->getUser(
                [
                    'user.id'           => (int) $requestingUserDiscordID
                ]
            );

            $client->channel->createMessage(
                [
                    'channel.id'            => (int) $groupChannelID,
                    'content'               => '**Squadron Info:** You have received a membership request from __***' . $requestingUser->username . '***__. Squadron Commander, please check back to Digital Squadrons to respond. ' . $furtherMessage
                ]
                );
        }

    }
    
}
//<a href="' . bp_get_group_permalink( groups_get_group ( $groupID ) ) . '">
$dsDiscordHooks = DS_Discord_Hooks::getInstance();