<?php

// don't load directly.
if (!defined('ABSPATH')) {
    header('Status: 403 Forbidden');
    header('HTTP/1.1 403 Forbidden');
    exit;
}

/**
 * Database Helper for MEC Event Discord Channels and Users.
 * 
 * 
 */

class DS_MEC_Discord_Event {

    /**
     * Hold the class instance
     */
    private static $instance = null;

    /**
     * The WP database object.
     *
     * @var wpdb
     */
    protected $_wpdb;

    /**
     * Our custom table name.
     *
     * @var string
     */
    protected $_event_table_name = 'ds_mec_discord_event';

    /**
     * User event table name.
     * 
     * @var string
     */
    protected $_user_table_name = 'ds_mec_discord_users';

    /**
     * Discord Servers table for Events
     * 
     * @var string
     */
    protected $_discord_server_table_name = 'ds_mec_discord_event_servers';

    /**
     * Table containing event timestamp in event creators times
     * 
     * @var string
     */
    protected $_discord_timzeone_times_table = 'ds_mec_timezone_times';

    /**
     * The event row from the DB.
     */
    public $eventObj = null;

    /**
     * Discord Options
     * 
     * @var array
     */
    protected $_discord_options = array();

    /**
     * Yikes_Inc_Easy_MailChimp_Customizer_Extender_DB constructor.
     *
     * @param integer $postID
     */
    public function __construct()
    {
        global $wpdb;

        $this->_wpdb = $wpdb;
        $this->_discord_options = get_option( 'discord-credentials', array() );

        /**
         * Fetch Event data from DB...
         */
        //$this->eventObj = $this->get_discord_event( $postID );
    }

     /**
     * Registers our plugin with WordPress.
     */
    public static function register()
    {
        $plugin = new self();

        // Actions
        add_action( 'save_post', array( $plugin, 'ds_mec_fes_save_event' ), 9 );
        add_action( 'mec_save_event_data', array( $plugin, 'fes_correct_timezones_for_discord' ), 999, 2 );
        if ( is_user_logged_in() ) {
            add_action( 'ds_mec_discord_join_server_script', array( $plugin, 'event_join_script' ) );
        }
        // Shortcode to add discord join/leave buttons on an event page...
        add_shortcode('ds_event_controls', array( $plugin, 'ds_mec_event_controls'));
        
        // Ajax Actions
        add_action( 'wp_ajax_event_user_action', array( $plugin, 'ds_mec_discord_event_user_action') );
    }

    public function get_discord_event( $postID ) {
        $result = $this->_wpdb->get_row( $this->_wpdb->prepare( "SELECT * FROM {$this->_wpdb->prefix}{$this->_event_table_name} WHERE post_id = %d", $postID ) );
        
        if ( null === $result ) {
            return false;
        }

        return $result;
    }

    public function create_event_entry( $postID ) {

        $authorID = get_post_field( 'post_author', $postID );
        $authorDiscordID = ds_member_get_discord_value( 'discord_id', $authorID );
        
        /**
         * 1. Check make sure entry doesnt exist for post, if it does exit;
         * 2. If No, select server with least channels and grab ID.
         * 3. Create Category, Text Channel, Comms Channel and Role on selected server ID.
         * 4. Give $creatorID role and additional Event Admin Role so to be able to moderate event.
         * 5. Add to count of selected discord_event_server table.
         * 6. Add creator to the discord_users table
         * 
         */
    
        $discordEvent = $this->_wpdb->get_row( $this->_wpdb->prepare( "SELECT * FROM {$this->_wpdb->prefix}{$this->_event_table_name} WHERE post_id = %d", $postID ) );
    
        if ( $discordEvent === null ) {
            // Continue creating entry.
    
            // Select comms server with least events...
            $discordServer = $this->_wpdb->get_row( "SELECT MIN( tally ) as tally, server_id, role_admin_id, role_everyone_id FROM {$this->_wpdb->prefix}{$this->_discord_server_table_name}", ARRAY_A );
            $serverID = $discordServer['server_id'];
    
            $client = new RestCord\DiscordClient(['token' => $this->_discord_options['ds_discord_primary_bot_token'] ]);
    
            // If the user is not a member of the server then lets add with the basic role of pilot...
            try {
                $client->guild->addGuildMember(
                    [
                        'guild.id'      =>  (int) $serverID,
                        'user.id'       =>  (int) $authorDiscordID,
                        'access_token'  =>  (string) get_user_meta( (int) $authorID, '_ds_user_access_token', true ),
                        'roles'         =>  [ (int) $discordServer['role_admin_id'] ] // Give event creator some admin priveleges
                    ]
                );
            } catch (\Exception $e ) {
                dsel('Error in adding event organiser to guild...');
                dsel($e, __FILE__);
            }

            try {

                // Create an event specific role...
                $eventRole = $client->guild->createGuildRole(
                    [
                        'guild.id'      =>  (int) $serverID,
                        'name'          =>  (string) '(#' . $postID  . ') Event Participant',
                        'permissions'   =>  (int) $this->_discord_options["ds_discord_default_pilot_channel_permission_id"],
                        'hoise'         =>  false,
                        'mentionable'   =>  false
                    ]
                );
    
                // Give creator this role in addition to the admin bits above...
                $client->guild->addGuildMemberRole(
                    [
                        'guild.id'      =>  (int) $serverID,
                        'user.id'       =>  (int) $authorDiscordID,
                        'role.id'       =>  (string) $eventRole->id
                    ]
                );

            } catch (\Exception $e ) {
                dsel('Error creating Event Participant Role and adding participant');
                dsel($e, __FILE__);
            }

            try {

                // Add Event Category...
                $category = $client->guild->createGuildChannel(
                    [
                        'guild.id'      =>  (int) $serverID,
                        'name'          =>  '✈  ' . 'Event',
                        'type'          =>  4,
                        'permission_overwrites' => [
                            [   
                                'id'    => (int) $discordServer['role_everyone_id'], 
                                'type'  => 'role', 
                                'deny'  => '1099511627775' // Deny everything.
                            ],
                            [
                                'id'    => (int) $eventRole->id,
                                'type'  => 'role',
                                'allow' => (string) $this->_discord_options['ds_discord_default_pilot_channel_permission_id'],
                                'deny'  => (string) $this->_discord_options['ds_discord_default_channel_role_deny_permission_id']
                            ]
                        ],
                        'nsfw'                  => false
                    ]
                );
        
                // Add Event Text Channel...
                $textChannel = $client->guild->createGuildChannel(
                    [
                        'guild.id'                  =>  (int) $serverID,
                        'name'                      =>  '│event-chat│',
                        'type'                      =>  0,
                        'topic'                     =>  'Event Text Chat...',
                        'user_limit'                =>  0, // We need to get the limit set be the event submission form.
                        'rate_limit_per_user'       =>  10,
                        'persmission_overwrites'    =>  [
                            [   
                                'id'    => (int) $discordServer['role_everyone_id'], 
                                'type'  => 'role', 
                                'deny'  => '1099511627775' // Deny everything.
                            ],
                            [
                                'id'    => (int) $eventRole->id,
                                'type'  => 'role',
                                'allow' => (string) $this->_discord_options['ds_discord_default_pilot_channel_permission_id'],
                                'deny'  => (string) $this->_discord_options['ds_discord_default_channel_role_deny_permission_id']
                            ]
                        ],
                        'parent_id'                 =>  (int) $category->id,
                        'nsfw'                      =>  false
                    ]
                );
        
                // Add Event Comms...
                $commsChannel = $client->guild->createGuildChannel(
                    [
                        'guild.id'                  =>  (int) $serverID,
                        'name'                      =>  '│event-comms│',
                        'type'                      =>  2,
                        'topic'                     =>  'Event Communication Channel...',
                        'user_limit'                =>  0, // We need to get the limit set be the event submission form.
                        'persmission_overwrites'    =>  [
                            [   
                                'id'    => (int) $discordServer['role_everyone_id'], 
                                'type'  => 'role', 
                                'deny'  => '1099511627775' // Deny everything.
                            ],
                            [
                                'id'    => (int) $eventRole->id,
                                'type'  => 'role',
                                'allow' => (string) $this->_discord_options['ds_discord_default_pilot_channel_permission_id'],
                                'deny'  => (string) $this->_discord_options['ds_discord_default_channel_role_deny_permission_id']
                            ]
                        ],
                        'parent_id'                 =>  (int) $category->id,
                        'nsfw'                      =>  false
                    ]
                );

            } catch (\Exception $e ) {
                dsel('Error creating Event Channels...');
                dsel($e, __FILE__);
            }
    
            // Add an embedded message to the text channel...
            $client->channel->createMessage(
                [
                    'channel.id'            =>  (int) $textChannel->id,
                    //'content'    => '',
                    'embed'      => [
                        'title'       => get_post_field( 'post_title', $postID ),
                        'description' => get_post_field( 'post_content', $postID ),
                        'url'         => 'https://discordapp.com',
                        'color'       => 14290439,
                        'timestamp'   => '2017-02-20T18:05:58.512Z',
                        'footer'      => [
                            'icon_url' => 'https://cdn.discordapp.com/embed/avatars/0.png',
                            'text'     => 'footer text',
                        ],
                        'thumbnail'   => [
                            'url' => 'https://cdn.discordapp.com/embed/avatars/0.png',
                        ],
                        'image'       => [
                            'url' => 'https://cdn.discordapp.com/embed/avatars/0.png',
                        ],
                        'author'      => [
                            'name'     => 'author name',
                            'url'      => 'https://discordapp.com',
                            'icon_url' => 'https://cdn.discordapp.com/embed/avatars/0.png',
                        ],
                        'fields'      => [
                            [
                                'name'  => 'Foo',
                                'value' => 'some of these properties have certain limits...',
                            ],
                            [
                                'name'  => 'Bar',
                                'value' => 'try exceeding some of them!',
                            ],
                            [
                                'name'  => ' 😃',
                                'value' => 'an informative error should show up, and this view will remain as-is until all issues are fixed',
                            ],
                            [
                                'name'  => '<:thonkang:219069250692841473>',
                                'value' => '???',
                            ],
                        ],
                    ],
                ]
            );
    
            $this->_wpdb->insert(
                $this->_wpdb->prefix . $this->_event_table_name,
                array(
                    'post_id'       =>  (int) $postID,
                    'host_id'       =>  (int) $authorID,
                    'server_id'     =>  (int) $serverID,
                    'cat_id'        =>  (int) $category->id,
                    'text_id'       =>  (int) $textChannel->id,
                    'comms_id'      =>  (int) $commsChannel->id,
                    'role_id'       =>  (int) $eventRole->id,
                    'tally'         =>  1
                ),
                array(
                    '%d',
                    '%d',
                    '%d',
                    '%d',
                    '%d',
                    '%d',
                    '%d',
                    '%d'
                )
            );
    
            $eventID = $this->_wpdb->insert_id;
    
            // 5
            $countUpdate = $this->_wpdb->query( $this->_wpdb->prepare( "UPDATE {$this->_wpdb->prefix}{$this->_discord_server_table_name} SET tally = tally + 1 WHERE server_id= %d", $serverID));
            // 6
            // First we need to delete user from other events prior to launching this one...
            if ( $userAttachedEventID = $this->get_user_attached_event( $authorID ) ) {
                $this->remove_user_from_event( $authorID, $userAttachedEventID );
            }
    
            $this->_wpdb->insert( 
                $this->_wpdb->prefix . $this->_user_table_name, 
                array(
                    'event_id'  => (int) $eventID,
                    'post_id'   => (int) $postID,
                    'user_id'   => (int) $authorID
                ),
                array(
                    '%d',
                    '%d',
                    '%d'
                )
            );
    
    
        }
    
        return false;
    }

    public function delete_event_entry( $postID ) {
        global $wpdb;
    
        /**
         * 1. Get Params for events details from database.
         * 2. Remove users for event from server and user table.
         * 3. Delete Category, Text Channel, Comms Channel and Role from server.
         * 4. Delete the event entry from the discord_event table
         * 6. minus to count of selected discord_event_server table.
         *
         */
    
        // 1 //
        $discordEvent = $this->_wpdb->get_row( $this->_wpdb->prepare( "SELECT * FROM {$this->_wpdb->prefix}{$this->_event_table_name} WHERE post_id = %d", $postID ), ARRAY_A );
    
        if ( $discordEvent !== null ) {
    
            $client = new RestCord\DiscordClient(['token' => $this->_discord_options['ds_discord_primary_bot_token'] ]);
            $serverID = (int) $discordEvent['server_id'];
    
            // 2 //
            $users = $this->_wpdb->get_results( $this->_wpdb->prepare("SELECT user_id FROM {$this->_wpdb->prefix}{$this->_user_table_name} WHERE post_id=%d", $postID ), ARRAY_A );
            foreach ( $users as $user ) {
                $client->guild->removeGuildMember(
                    [
                        'guild.id'      =>  $serverID,
                        'user.id'       =>  (int) ds_member_get_discord_value( 'discord_id', $user['user_id'] )
                    ]
                );
                $this->_wpdb->delete(
                    $this->_wpdb->prefix . $this->_user_table_name,
                    array(
                        'user_id'   => (int) $user['user_id']
                    ),
                    array(
                        '%d'
                    )
                );
            }
    
            // 3 //
            try {
                // Delete Channels...
                $client->channel->deleteOrcloseChannel(
                    [
                        'channel.id'    => (int) $discordEvent['cat_id']
                    ]
                );
                $client->channel->deleteOrcloseChannel(
                    [
                        'channel.id'    => (int) $discordEvent['text_id']
                    ]
                );
                $client->channel->deleteOrcloseChannel(
                    [
                        'channel.id'    => (int) $discordEvent['comms_id']
                    ]
                );
                $client->guild->deleteGuildRole(
                    [
                        'guild.id'      => (int) $serverID,
                        'role.id'       => (string) $discordEvent['role_id']
                    ]
                );
            } catch (\Exception $e) {
                error_log( print_r( $e, true ) );
            }
    
            // 4 //
            $this->_wpdb->delete(
                $this->_wpdb->prefix . $this->_event_table_name,
                array(
                    'post_id'   =>  $postID
                ),
                array(
                    '%d'
                )
            );
    
            // 5 //
            $countUpdate = $this->_wpdb->query( $this->_wpdb->prepare( "UPDATE {$this->_wpdb->prefix}{$this->_discord_server_table_name} SET tally = tally - 1 WHERE server_id= %d", $serverID ) );

            // 6 // Let's tidy the table up here...
        
        }
    }

    /**
     * Remove user role from an event.
     * 
     * 
     */
    public function remove_user_from_event( $userID, $eventID ) {

        // 1. Remove the role on Discord...
        $client = new RestCord\DiscordClient(['token' => $this->_discord_options['ds_discord_primary_bot_token'] ]);

        $userDiscordID = ds_member_get_discord_value( 'discord_id', $userID );

        $eventDiscordDetails = $this->_wpdb->get_row( $this->_wpdb->prepare( "SELECT * FROM {$this->_wpdb->prefix}{$this->_event_table_name} WHERE post_id=%d", $eventID ) );

        $client->guild->removeGuildMemberRole(
            [
                'guild.id'      =>  (int) $eventDiscordDetails->server_id,
                'user.id'       =>  (int) $userDiscordID,
                'role.id'       =>  (string) $eventDiscordDetails->role_id
            ]
        );

        // 2. Minus one on event tally...

        // 3. Delete user entry from the table...
        $result = $this->_wpdb->delete( 
            $this->_wpdb->prefix . $this->_user_table_name,
            array(
                'user_id'   => (int) $userID
            ),
            array(
                '%d'
            )
        );

        if ( $result ) {
            $this->_wpdb->query( $this->_wpdb->prepare( "UPDATE {$this->_wpdb->prefix}{$this->_event_table_name} SET tally = tally - 1 WHERE server_id= %d", $eventDiscordDetails->server_id ) );
        }

        return $result;
    }  

    /**
     * Add a user to an event, including role
     * 
     * 
     */
    public function add_user_to_event( $userID, $eventID ) {

        $client = new RestCord\DiscordClient(['token' => $this->_discord_options['ds_discord_primary_bot_token'] ]);

        $userDiscordID = ds_member_get_discord_value( 'discord_id', $userID );

        $eventDiscordDetails = $this->_wpdb->get_row( $this->_wpdb->prepare( "SELECT * FROM {$this->_wpdb->prefix}{$this->_event_table_name} WHERE post_id=%d", $eventID ) );

        dsel($eventDiscordDetails, __FILE__);

        // Add the user to the server and give them the adequate role for this server.
        $guildResult = $client->guild->addGuildMember(
            [
                'guild.id'      =>  (int) $eventDiscordDetails->server_id,
                'user.id'       =>  (int) $userDiscordID,
                'access_token'  =>  (string) get_user_meta( (int) $userID, '_ds_user_access_token', true ),
                'roles'         =>  [ (int) $eventDiscordDetails->role_id ]
            ]
        );

        dsel($eventDiscordDetails->role_id);

        // Add entry to table for user to event.
        $result = $this->_wpdb->insert(
            $this->_wpdb->prefix . $this->_user_table_name,
            array(
                'event_id'      => $eventDiscordDetails->id,
                'post_id'       => $eventID,
                'user_id'       => $userID
            )
        );

        if ( $result ) {
            // Add to tally for the event...
            $this->_wpdb->query( $this->_wpdb->prepare( "UPDATE {$this->_wpdb->prefix}{$this->_event_table_name} SET tally = tally + 1 WHERE server_id= %d", $eventDiscordDetails->server_id ) );
        }

        return $result;

    }

    /**
     * Get an event ID that the user is attached to. Return either event ID or false is currently not attached.
     * 
     * @var integer $userID
     */
    public function get_user_attached_event( $userID ) {

        $userEvent = $this->_wpdb->get_row( $this->_wpdb->prepare( "SELECT * FROM {$this->_wpdb->prefix}ds_mec_discord_users WHERE user_id=%d", $userID ) );

        if ( $userEvent !== null ) {
            return $userEvent->post_id;
        }

        return false;

    }

    /**
     * 
     */
    public function get_occurences( $db, $afterBefore = 'before', $minutes = 30 ) {

        $now = current_time('Y-m-d H:i');

        $nowStart = strtotime($now);
        $nowEnd = $nowStart + 60; 

        $plusMinus = $minutes * 60;

        if ( $afterBefore === 'after' ) $plusMinus *= -1;

        $occStart = $nowStart + $plusMinus;
        $occEnd = $nowEnd + $plusMinus;

        /**
         * Taken for MEC cron files...
         */
        // if ( $afterBefore === 'before' ) {
        //     $query = "SELECT `post_id`, `tstart`, `tend` FROM `#__mec_dates` WHERE `tstart`>=".$occStart." AND `tstart`<".$occEnd;
        // } else {
        //     $query = "SELECT `post_id`, `tstart`, `tend` FROM `#__mec_dates` WHERE `tend`>=".$occStart." AND `tend`<".$occEnd;
        // }

        /**
         * The following changed due to the workaround to take into account timezones.
         */
        if ( $afterBefore === 'before' ) {
            $query = "SELECT `post_id`, `tstart`, `tend` FROM {$this->_wpdb->prefix}{$this->_discord_timzeone_times_table} WHERE `tstart`>=".$occStart." AND `tstart`<".$occEnd;
        } else {
            $query = "SELECT `post_id`, `tstart`, `tend` FROM {$this->_wpdb->prefix}{$this->_discord_timzeone_times_table} WHERE `tend`>=".$occStart." AND `tend`<".$occEnd;
        }

        // Fetch Event Occurrences
        $occurrences = $this->_wpdb->get_results( $query );
        dsel( $occurrences, __FILE__ );

        if ( $afterBefore === 'before' ) {
            //Delete old events from the timezone table...
            $lastCleanUp = get_option( 'ds_last_event_cleanup' );
 
            $difference = time() - $lastCleanUp;


            if ( (int) $difference > 3600 ) {

                // It's been longer than an hour lets have a tidy up...
                $query = $this->_wpdb->query( "DELETE FROM {$this->_wpdb->prefix}{$this->_discord_timzeone_times_table} WHERE tend<=".$lastCleanUp."" );

                update_option( 'ds_last_event_cleanup', time() );
            }
        }

        return $occurrences;
    }

    public function getEventButton( $eventID ) {
        $html = '';

        $eventObj = $this->get_discord_event( $eventID );
        dsel($eventObj, __FILE__);
        $eventMeta = get_post_meta( (int) $eventObj->post_id, 'mec_booking', true );

            //var_dump( $this->eventObj );
        /**
         * 1. Check DB - has the event been created yet?
         * 1. a. if no, send a message outlining channels don't open till 30 minutes before the event starts.
         * 1. b. if yes, then...
         *      (i)     has the event limit been reached?
         *      (ii)    is the member already attached to the event?
         *      (iii)   is the member attached to another event?
         */

        // If the user is not logged in, then they will be unable to join events - all event users must be registered!
        if ( ! is_user_logged_in() ) {
            $html .= '<div>Only <a href="javascript:void(0)" onclick="dsOAuthLoginNew(\'discord\');" data-balloon="Click to Connect with Discord..." data-balloon-pos="up">connected Discord members</a> can access the Event Servers</div>';

            return $html;
        }
        /**
         * Has the event started and been created on Discord?
         */
        if ( $eventObj === false ) {
            // Event hasn't been created by the cron so we're assuming its not time...
            $html .= '<div>Event is yet to start. Discord Server will open 30 minutes before the event starts</div>';
        } else {
            /**
             * is the member already attaced to an event, and if so, is it this one?
             */
            $user = $this->_wpdb->get_row( $this->_wpdb->prepare( "SELECT * FROM {$this->_wpdb->prefix}{$this->_user_table_name} WHERE user_id = %d", get_current_user_id() ) );
            dsel($user);
            $eventMeta = get_post_meta( $eventObj->post_id, 'mec_booking' );

            $limitReached = $eventMeta['bookings_limit'] <= $eventObj->tally ? false : true;

            if ( $limitReached ) {
                // enough members attached to event
                $html .= '<a href="#" type="button" id="event-full" class="event-action-button" disabled><span class="button__text">Event Capacity Full</span></a>';
                
            } else {
                if ( $user === null ) {
                    // User is not attached to an event, display join button...
                    $html .= '<a href="#" type="button" id="event-join" class="event-action-button" scope="join"><span class="button__text">Join Event</span></a>';
                } else {
                    if ( $user->post_id === $eventObj->post_id ) {
                        // User already connected to the event
                        $html .= '<a href="#" type="button" id="event-browser-link" class="event-action-button-noajax"><span class="button__text">Open in Browser</span></a><a href="#" type="button" id="event-link" class="event-action-button-noajax"><span class="button__text">Open Discord App</span></a><a href="#" type="button" id="event-leave" class="event-action-button" scope="leave"><span class="button__text">Leave Server</span></a>';
                    } else {
                        // User is attached to an event - can't be two places at once!
                        // Let's get the other event details and link so user has the option to visit other event and leave to join this one..
                        $html .= '<div>What the?</div>';
                    }
                    
                }
            }
        }

        return $html;
    }

    public function event_join_script( $eventID ) {

        $discordLink = $this->get_discord_event_link( $eventID );

        ?>
        <script type="text/javascript">
            jQuery(document).ready(function ($) {
                $('#ds-mec-discord-user-buttons').on( 'click', 'a.event-action-button', function(e) {
                    e.preventDefault();
                    $(this).addClass('button--loading');
                    $.ajax({
                        type: "POST",
                        url: '<?php echo esc_url( admin_url( 'admin-ajax.php' ) ); ?>' ,
                        data: { 
                            action: 'event_user_action',
                            scope: $(this).attr( 'scope' ),
                            event_id: <?php echo $eventID; ?>,
                            _nonce: '<?php echo wp_create_nonce( 'ds_mec_discord_event_user_action' ); ?>'
                        },
                        //dataType: 'json',
                        success: function( response ) {
                            console.log(response.data.html);
                            $('#ds-mec-discord-user-buttons').html(response.data.html);
                        },
                        error: function( result ) {
                            alert( 'There was an error...' );
                        }
                    });
                });

                <?php if ( $discordLink ) : ?>
                    $('#ds-mec-discord-user-buttons').on( 'click', 'a#event-browser-link', function() {
                        window.open('<?php echo $this->get_discord_event_link( $eventID ); ?>', '_blank');
                    });
                    $('#ds-mec-discord-user-buttons').on( 'click', 'a#event-link', function() {
                        window.open('<?php echo $this->get_discord_event_link( $eventID, 'discord' ); ?>', '_blank');
                    });
                <?php endif; ?>
            });
        
        </script>
        <?php
    }

    public function get_discord_event_link( $eventID, $type = 'https' ) {
        $event = $this->get_discord_event( $eventID );

        if ( $event === null ) return false;

        $link = $type . '://discord.com/channels/' . $event->server_id . '/';

        return $link;
    }

    /**
     * Ajax controller for joining and leaving events...
     * 
     */
    public function ds_mec_discord_event_user_action() {

        // Verify that the nonce is valid.
        if ( ! wp_verify_nonce( sanitize_text_field($_POST['_nonce']), 'ds_mec_discord_event_user_action') ) {
            wp_die();
        }

        // Verify scope is within the bounds.
        if ( ! in_array( $_POST['scope'], array( 'join', 'leave' ) ) ) {
            wp_die();
            // Return error.
        }

        /**
         * Outcome possibilities:
         *  - joined
         *  - left
         *  - full
         * Create a switch statement for the Ajax return.
         */
        if ( $_POST['scope'] === 'join' ) {
            $outcome = $this->joinEvent( $_POST['event_id'] );
        } else {
            $outcome = $this->leaveEvent( $_POST['event_id'] );
        }

        $return = array();

        switch ( $outcome ) {
            case 'joined':
                $return['html'] .= '<a href="#" type="button" id="event-browser-link" class="event-action-button-noajax"><span class="button__text">Open in Browser</span></a><a href="#" type="button" id="event-link" class="event-action-button-noajax"><span class="button__text">Open Discord App</span></a><a href="#" type="button" id="event-leave" class="event-action-button" scope="leave"><span class="button__text">Leave Server</span></a>'; 
                break;
            case 'left':
                $return['html'] .= '<a href="#" type="button" id="event-join" class="event-action-button" scope="join"><span class="button__text">Join Server</span></a>'; 
                break;
            case 'full':
                $return['html'] .= '<a href="#" type="button" id="event-join" class="event-action-button" disabled><span class="button__text">Event Server Full</span></a>'; 
                break;
            case 'closed':
                $return['html'] .= '<div>Event Closed.</div>';
                break;
            case false:
                $return['html'] .= ''; 
                break;
        }

        if ( ! $outcome ) {
            wp_send_json_error( $return );
        }

        wp_send_json_success( $return );
    }

    /**
     * 
     */
    public function joinEvent( $postID ) {

        $event = $this->get_discord_event( $postID );

        dsel( $event );

        $eventMeta = get_post_meta( $postID, 'mec_booking' );

        dsel( $eventMeta );

        if ( is_object( $event ) ) {
            $limitReached = $eventMeta['bookings_limit'] <= $event->tally ? false : true;

            if ( ! $limitReached ) {

                $userID = get_current_user_id();

                if ( $this->get_user_attached_event( $userID ) ) {
                    // User connected to another event lets remove from that event first.
                    $this->remove_user_from_event( $userID, $postID );
                }

                // Add user to the new event
                $userToNewEvent = $this->add_user_to_event( $userID, $postID );

                return $userToNewEvent ? 'joined' : false;


            } else {
                return 'full';
            }

        } else {
            return 'closed';
        }
    }

    /**
     * Start the process of removing a user from an event.
     */
    public function leaveEvent( $postID ) {
        $result = $this->remove_user_from_event( get_current_user_id(), $postID );

        return $result ? 'left' : 'closed';
    }

    /**
     * On the front-end we need to hook into the save_post for events to align the data before MEC does it's bits.
     * 
     * @param int $postID
     * @return void 
     */
    public function fes_save_event( $postID ) {

        // Check if our nonce is set.
        if(!isset($_POST['mec_event_nonce'])) return;
        //error_log( print_r( 'nonce is set', true ) );

        // It's from FES
        if(isset($_POST['action']) and $_POST['action'] === 'mec_fes_form') return;
        //error_log( print_r( 'action is set', true ) );

        // Verify that the nonce is valid.
        if(!wp_verify_nonce($_POST['mec_event_nonce'], 'mec_event_data')) return;
        //error_log( print_r( 'nonce is correct', true ) );

        // If this is an autosave, our form has not been submitted, so we don't want to do anything.
        if(defined('DOING_AUTOSAVE') and DOING_AUTOSAVE) return;
        //error_log( print_r( 'not doing an autosave...', true ) );

        //error_log( print_r( $_POST['mec'], true ) );

    }

    /**
     * Hook fired after MEC has done it's bits and returns the event data.
     * 
     * @param int $eventID
     * @param array $eventData
     */
    public function fes_correct_timezones_for_discord( $eventID, $eventData ) {

        $mecDatesTable = 'mec_dates';
        $dsTimesTables = 'ds_mec_timezone_times';

        // 1. grab timestamps from the db. wp_mec_dates
        $entries = $this->_wpdb->get_results( $this->_wpdb->prepare("SELECT * FROM {$this->_wpdb->prefix}{$mecDatesTable} WHERE post_id=%d", $eventID ) );
        
        if ( is_array( $entries ) && count( $entries ) > 0 ) {
            // 2. if entries, delete existing from wp_ds_mec_discord_times
            $this->_wpdb->delete(
                $this->_wpdb->prefix . $this->_discord_timzeone_times_table,
                array(
                    'post_id'       => $eventID
                )
            );

            // Check if a timezone has been set to know if we need to adjust the timestamp accordingly...
            if ( isset( $eventData['timezone'] ) && strlen( $eventData['timezone'] ) > 0 ) {

                $timezone = $eventData['timezone'] === 'global' ? 'UTC' : $eventData['timezone'];

                /**
                 * TODO: Check if valid timzone has been set...
                 */

                // 3. adjust timestamps to correct timezones.
                foreach ( $entries as $entry ) {

                    /**
                     * MEC doesn't store event times against the relevant timezone so it means that the event date/time is stored against the system time.
                     * 
                     * This work around, changes the timestamp back to a string and recreates the stamp based upto the correct timezone for the event.
                     */
                    $startDateTime = new DateTime();
                    $startDateTime->setTimestamp( $entry->tstart );
                    $startDateTimeString = $startDateTime->format( 'Y-m-d H:i:s' );
                    
                    $startDateTime = new DateTime( $startDateTimeString, new DateTimeZone( $timezone ) );
                    $tStart = $startDateTime->format('U');


                    $endDateTime = new DateTime();
                    $endDateTime->setTimestamp( $entry->tend );
                    $endDateTimeString = $endDateTime->format( 'Y-m-d H:i:s' );
                    
                    $endDateTime = new DateTime( $endDateTimeString, new DateTimeZone( $timezone ) );
                    $tend = $endDateTime->format('U');

                    // $tStart = ds_convert_time( $entry->tstart, date_default_timezone_get(), $eventData['timezone'] );
                    // $tend = ds_convert_time( $entry->tend, date_default_timezone_get(), $eventData['timezone'] );

                    // 4. add timestamps to wp_ds_mec_discord_times
                    $this->_wpdb->insert(
                        $this->_wpdb->prefix . $this->_discord_timzeone_times_table,
                        array(
                            'post_id'   => $eventID,
                            'tstart'    => $tStart,
                            'tend'      => $tend
                        )
                    );
                }
            }
        }

    }

    /**
     * We need to add or deduct one from the event tally as people enter and leave...
     * 
     * @var int $eventID
     * @var int $tally
     */
    public function ds_mec_discord_event_tally( $eventID, $tally = 1 ) {
    
        //$this->_wpdb->query( $this->_wpdb->prepare( "UPDATE {$this->_wpdb->prefix}{$this->_event_table_name} SET tally = tally + 1 WHERE server_id= %d", $serverID));
    
    
    }

    /**
     * Shortcode to output Discord join/leave buttons on the event page...
     * 
     * 
     */
    public function ds_mec_event_controls() {

        $currentEventID = get_the_ID();

        ?>

        <div id="ds-mec-discord-user-buttons">
            <?php echo $this->getEventButton( $currentEventID ); ?>
        </div>
        <!-- Event Styles. -->
        <style>
                        .event-action-button, .event-action-button-noajax {
                            position: relative;
                            padding: 5px 10px;
                            background: #1099f0;
                            border: none;
                            outline: none;
                            border-radius: 5px;
                            cursor: pointer;
                            margin: 5px;
                        }

                        .event-action-button:active {
                            background: #007a63;
                        }

                        .button__text {
                            font: bold 16px "Quicksand", san-serif;
                            color: #ffffff;
                            transition: all 0.2s;
                        }

                        .button--loading .button__text {
                            visibility: hidden;
                            opacity: 0;
                        }

                        .button--loading::after {
                            content: "";
                            position: absolute;
                            width: 16px;
                            height: 16px;
                            top: 0;
                            left: 0;
                            right: 0;
                            bottom: 0;
                            margin: auto;
                            border: 4px solid transparent;
                            border-top-color: #ffffff;
                            border-radius: 50%;
                            animation: button-loading-spinner 1s ease infinite;
                        }

                        @keyframes button-loading-spinner {
                            from {
                                transform: rotate(0turn);
                            }

                            to {
                                transform: rotate(1turn);
                            }
                        }
                    </style>
                <!-- Add our script -->
                <?php do_action( 'ds_mec_discord_join_server_script', $currentEventID ); ?>
        <?php
    }

}

/**
 * We need to instantiate the class here as we have hooks that need running...
 */
DS_MEC_Discord_Event::register();

// /**
//  * 
//  */
// function ds_mec_discord_event_access_point() {

//     // check and validate post data.

//     // setup DS_MEC_Discord_Event::getInstance( $postID );

//     // possible options ... join : leave ?


// }
// add_action( 'wp_ajax_ds_mec_discord_event_access', 'ds_mec_discord_event_access_point' );

// function ds_mec_add_user_to_event( $userID, $eventID ) {

// }



// /**
//  * On the front-end we need to hook into the save_post for events to align the data before MEC does it's bits.
//  * 
//  * @param int $postID
//  * @return void 
//  */
// function ds_mec_fes_save_event( $postID ) {

//     // Check if our nonce is set.
//     if(!isset($_POST['mec_event_nonce'])) return;
//     //error_log( print_r( 'nonce is set', true ) );

//     // It's from FES
//     if(isset($_POST['action']) and $_POST['action'] === 'mec_fes_form') return;
//     //error_log( print_r( 'action is set', true ) );

//     // Verify that the nonce is valid.
//     if(!wp_verify_nonce($_POST['mec_event_nonce'], 'mec_event_data')) return;
//     //error_log( print_r( 'nonce is correct', true ) );

//     // If this is an autosave, our form has not been submitted, so we don't want to do anything.
//     if(defined('DOING_AUTOSAVE') and DOING_AUTOSAVE) return;
//     //error_log( print_r( 'not doing an autosave...', true ) );

//     //error_log( print_r( $_POST['mec'], true ) );

// }
// add_action( 'save_post', 'ds_mec_fes_save_event', 9 );

// /**
//  * Hook fired after MEC has done it's bits and returns the event data.
//  * 
//  * @param int $eventID
//  * @param array $eventData
//  */
// function ds_mec_fes_correct_timezones_for_discord( $eventID, $eventData ) {
//     global $wpdb;

//     $mecDatesTable = 'mec_dates';
//     $dsTimesTables = 'ds_mec_timezone_times';

//     // 1. grab timestamps from the db. wp_mec_dates
//     $entries = $wpdb->get_results( $wpdb->prepare("SELECT * FROM {$wpdb->prefix}{$mecDatesTable} WHERE post_id=%d", $eventID ) );
    
//     if ( is_array( $entries ) && count( $entries ) > 0 ) {
//         // 2. if entries, delete existing from wp_ds_mec_discord_times
//         $wpdb->delete(
//             $wpdb->prefix . $dsTimesTables,
//             array(
//                 'post_id'       => $eventID
//             )
//         );

//         // Check if a timezone has been set to know if we need to adjust the timestamp accordingly...
//         if ( isset( $eventData['timezone'] ) && strlen( $eventData['timezone'] ) > 0 ) {

//             $timezone = $eventData['timezone'] === 'global' ? 'UTC' : $eventData['timezone'];

//             /**
//              * TODO: Check if valid timzone has been set...
//              */

//             // 3. adjust timestamps to correct timezones.
//             foreach ( $entries as $entry ) {

//                 /**
//                  * MEC doesn't store event times against the relevant timezone so it means that the event date/time is stored against the system time.
//                  * 
//                  * This work around, changes the timestamp back to a string and recreates the stamp based upton the correct timezone for the event.
//                  */
//                 error_log( print_r( 'timezone...', true ));
//                 error_log( print_r( $eventData['timezone'], true ));
//                 $startDateTime = new DateTime();
//                 $startDateTime->setTimestamp( $entry->tstart );
//                 $startDateTimeString = $startDateTime->format( 'Y-m-d H:i:s' );
                
//                 $startDateTime = new DateTime( $startDateTimeString, new DateTimeZone( $timezone ) );
//                 $tStart = $startDateTime->format('U');


//                 $endDateTime = new DateTime();
//                 $endDateTime->setTimestamp( $entry->tend );
//                 $endDateTimeString = $endDateTime->format( 'Y-m-d H:i:s' );
                
//                 $endDateTime = new DateTime( $endDateTimeString, new DateTimeZone( $timezone ) );
//                 $tend = $endDateTime->format('U');

//                 // $tStart = ds_convert_time( $entry->tstart, date_default_timezone_get(), $eventData['timezone'] );
//                 // $tend = ds_convert_time( $entry->tend, date_default_timezone_get(), $eventData['timezone'] );

//                 // 4. add timestamps to wp_ds_mec_discord_times
//                 $wpdb->insert(
//                     $wpdb->prefix . $dsTimesTables,
//                     array(
//                         'post_id'   => $eventID,
//                         'tstart'    => $tStart,
//                         'tend'      => $tend
//                     )
//                 );
//             }
//         }
//     }

// }
// add_action( 'mec_save_event_data', 'ds_mec_fes_correct_timezones_for_discord', 999, 2 );

// /**
//  * Not currently used - possible use in future...
//  */
// function ds_convert_time( $time_to_convert = null, $start_timezone_string = "UTC", $end_timezone_string = "UTC", $date_format = null ) {
	
// 	// We require a start time
// 	if( empty( $time_to_convert ) ){
// 		return false;
// 	}
	
// 	// If the two timezones are different, find the offset
// 	if( $start_timezone_string != $end_timezone_string ) {
// 		// Create two timezone objects, one for the start and one for
// 		// the end
// 		$dateTimeZoneStart = new DateTimeZone( $start_timezone_string );
// 		$dateTimeZoneEnd = new DateTimeZone( $end_timezone_string );
		
// 		// Create two DateTime objects that will contain the same Unix timestamp, but
// 		// have different timezones attached to them.
// 		$dateTimeStart = new DateTime( "now", $dateTimeZoneStart );
// 		$dateTimeEnd = new DateTime( "now", $dateTimeZoneEnd );
		
// 		// Calculate the GMT offset for the date/time contained in the $dateTimeStart
// 		// object, but using the timezone rules as defined for the end timezone ($dateTimeEnd)
// 		$timeOffset = $dateTimeZoneEnd->getOffset($dateTimeStart);
		
// 	} else {
// 		// If the timezones are the same, there is no offset
// 		$timeOffset = 0;
// 	}

//     error_log( print_r( $timeOffset, true ) );
	
// 	// Convert the time by the offset
// 	$converted_time = $time_to_convert + $timeOffset;
	
// 	// If we have no given format, just return the time
// 	if( empty( $date_format ) ) {
// 		return $converted_time;
// 	}
	
// 	// Convert to the given date format
// 	return date( $date_format, $converted_time );
// }