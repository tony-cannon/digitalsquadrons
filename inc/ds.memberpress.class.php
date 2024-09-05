<?php

if(!defined('ABSPATH')) {
    die('You are not allowed to call this page directly.');
}

class dsMemberpress {

    private $_meprSquadronCreateProdID = 79;

    public $defaultMembershipStr = '_ds_group_type_default_group_memberships';

    public $_discordOptions;

    public function init() {}

    public function __construct() {

        $this->_discordOptions = get_option( 'discord-credentials', array() );

        //add_action( 'mepr-above-checkout-form', array( $this, 'ds_group_form_add_logo' ) );
        add_action( 'mepr-checkout-before-coupon-field', array( $this, 'ds_group_form_details' ) );

        add_action( 'mepr-event-subscription-created', array( $this, 'ds_group_subscription_created') );
        add_action( 'mepr-event-transaction-completed', array( $this, 'ds_group_transaction_completed' ) );
        
        //add_action( 'mepr-event-transaction-completed', array( $this, 'ds_group_created_on_frontend' ) );
        //add_action( 'mepr-event-create', array( $this, 'ds_group_created_on_frontend' ) );
        add_action('mepr-signup', array( $this, 'ds_create_a_free_group' ) );

        add_action( 'add_meta_boxes', array( $this, 'ds_group_type_meta_boxes') );
        add_action( 'save_post',      array( $this, 'ds_group_type_save_metabox' ), 10, 2 );

        add_action( 'bp_setup_nav', array( $this, 'ds_group_edit_memberpress_menus' ), 9999 );

        add_filter( 'bp_get_group_join_button', array ( $this, 'ds_hide_joingroup_button' ), 999 );
        add_shortcode( 'ds_group_creation_form', array( $this, 'ds_groups_select_creation_form' ) );

        /**
         * Ajax requests...
         */
        add_action( 'wp_ajax_ds_groups_get_types_from_platform', array( $this, 'ds_groups_get_types_from_platform' ) );
        add_action( 'wp_ajax_nopriv_ds_groups_get_types_from_platform', array( $this,  'ds_groups_get_types_from_platform' ) );


        //add_action( 'mepr-event-create', array( $this, 'ds_membership_event' ) );
    }

    /**
     * Output the correct form for group creation, i.e. paid or free?
     * 
     */
    public function ds_groups_select_creation_form() {
        global $wpdb;

        $userID = get_current_user_id();

        $productID = 79;

        $userExistingGroupID = $wpdb->get_var( $wpdb->prepare( " SELECT id FROM {$wpdb->prefix}bp_groups WHERE creator_id = %d ", $userID ) );

        if ( $userExistingGroupID !== null ) {
            // User already has a free account so send them the paid account product id...
            $productID = 2228;
        }

        $shortcodeString = '[mepr-membership-registration-form id="' . $productID . '"]';

        echo do_shortcode( $shortcodeString );
    }

    /**
     * 
     */
    public function ds_groups_get_types_from_platform() {
        //check_ajax_referer( 'ds_ajax_nonce', 'nonce_ajax' );

        $output = '';
        $optionsOutput = '';

        if ( isset( $_POST['platform'] ) ) {
            $platformID = sanitize_text_field( $_POST['platform'] );

            $types = get_posts( array (
                        'numberposts' => -1,   // -1 returns all posts
                        'post_type' => 'bp-group-type',
                        'orderby' => 'title',
                        'order' => 'ASC',
                        'tax_query' => array(
                            array(
                                'taxonomy' => 'ds-software-platform',
                                'terms' => $platformID,
                                'include_children' => false // Remove if you need posts from term 7 child terms
                            ),
                        ),
                    ));
            
            foreach ($types as $type) {
                $optionsOutput .= '<option value="' . $type->post_name . '">' . $type->post_title . '</option>';
            }

            /**
             * Output the primary select...
             */
            $output .= '<div class="mp-form-row mepr_squadron_primary_type">
                            <div class="mp-form-label">
                                <label for="ds-squadron-primary-aircraft">Primary Aircraft</label>
                                <span class="cc-error">What aircraft will the Squadron primarily fly?</span>
                            </div>
                            <select id="ds-squadron-primary-aircraft-select" class="mepr-form-input" name="ds_squadron_primary_aircraft" required ><option value>' . __( 'Select Primary Aircraft...' ) . '</option>' . $optionsOutput . '</select>
                            <p style="font-size:13px;">Please select the primary aircraft for your squadron.</p>
                        </div>';


            
            /**
             * Output the Secondary Select... 
             */    
            $output .= '<div class="mp-form-row mepr_squadron_secondary_type">
                            <div class="mp-form-label">
                                <label for="ds-squadron-secondary-aircraft">Supplementary Aircraft</label>
                                <span class="cc-error">What aircraft will the Squadron primarily fly?</span>
                            </div>
                            <select id="ds-squadron-secondary-aircraft-select" name="ds_squadron_secondary_aircraft"><option value></option>' . $optionsOutput . '</select>
                            <p style="font-size:13px;">Please select up to 3 additional aircraft that your Squadron will fly.</p>
                        </div>';

            wp_send_json_success( $output );
            
        }

        $error = new WP_Error( '001', 'No user information was retrieved.', 'Some information' );
 
        wp_send_json_error( $error );

    }

    public function ds_group_form_add_logo() {
        
        $logo_id = buddyboss_theme_get_option( 'admin_logo_media', 'id' );
        $logo	 = ( $logo_id ) ? wp_get_attachment_image( $logo_id, 'full', '', array( 'class' => 'bb-logo' ) ) : get_bloginfo( 'name' );
        $enable_private_network = bp_get_option( 'bp-enable-private-network' );
        if ( '0' === $enable_private_network ) {
        ?>
            <div class="register-section-logo private-on-div">
                <?php echo $logo; ?>
            </div>
            <?php
        } else {
            ?>
            <div class="register-section-logo">
                <a href="<?php echo esc_url( home_url( '/' ) ); ?>" rel="home">
                    <?php echo $logo; ?>
                </a>
            </div>
            <?php
        }
    }

    public function ds_group_form_details() {

        /**
         * TODO: exclude needs to managed in admin and not hardcoded...
         */
        $primaryOptions = array(
            'name'  =>  'ds_squadron_primary_aircraft',
            'id'    =>  'ds-squadron-primary-aircraft',
            'show_option_none'  =>  __( 'Select Primary Aircraft...')            
        );

        $secondaryOptions = array(
            'name'  =>  'ds_squadron_secondary_aircraft[]',
            'id'    =>  'ds-squadron-secondary-aircraft'         
        );

        $defaultArgs = array(
            'post_type' => 'bp-group-type',
            'class'     => 'mepr-form-input',
            'required'  => '',
            'echo'      => 0,
            'value_field' => 'post_name',
            'exclude'   => array(380,901,906,908)
        );

        ?>
        <div class="mp-form-row mepr_squadron_name">
            <div class="mp-form-label">
			    <label>Squadron Name</label>
			    <span class="cc-error">Name of Squadron Required</span>
            </div>
            <input type="hidden" id="checkType" name="check_type" value="groupname">
            <input type="text" name="mepr_squadron_name" id="mepr_squadron_name" class="mepr-form-input" required />
        </div>

        <div class="mp-form-row mepr_squadron_software_platform">
            <div class="mp-form-label">
			    <label for="ds-squadron-software-platform">Software Platform</label>
			    <span class="cc-error">Which software platform will your Squadron be based on?</span>
            </div>
            <?php
                        $args = array( 
                            'taxonomy'  => 'ds-software-platform',
                            'post_type' => 'bp-group-type',
                            'hide_empty'    => false
                        );
                        $types = get_categories( $args );
                    ?>
                    <select id="ds-squadron-software-platform-select" class="ds-squadron-software-platform mepr-form-input" name="ds_squadron_software_platform" required>
                        <option value="">All Types</option>
                        <?php
                        foreach ($types as $key => $type) {
                            echo '<option value="'.$type->term_id.'">'.$type->name.'</option>';
                        }
                        ?>
                    </select>
                    <p style="font-size: 13px;">All squadrons are based upon a flight simulator platform.</p>
        </div>

        <div id="ds-aircraft-select-options">
            

        </div>
        <style>.select2-container {width: 100% !important;padding: 0;}</style>


        <div class="mp-form-row mepr_squadron_region">
            <div class="mp-form-label"> 
                <label for="squadron-region">Country</label>
                <span class="cc-error">Please select a country...</span>
            </div>

            <?php

                $countrySelect = ds_get_region_dropdown('dsNewSquadron', true, null, 'Select a Country...');

                echo $countrySelect;

            ?>
            <p style="font-size:13px;">A squadron location helps with squadron searching and for multiplayer efficiency.</p>
        </div>
          
        <?php 
    }

    /**
     * Prior to payment of subscription, we need to capture the data submitted in the initial form and temporarily attach it the subscription for later reference after payment has been made...
     * 
     * @param object $event
     */
    public function ds_group_subscription_created( $event ) {
        $subscription = $event->get_data();
        $user = $subscription->user();

        //error_log( print_r( $subscription, true ) );
        //error_log( print_r( $event, true ) );

        if ( wp_verify_nonce( $_REQUEST['mepr_checkout_nonce'], 'logged_in_purchase' ) ) {
            $groupName = sanitize_text_field( $_POST['mepr_squadron_name'] );
            $platform = sanitize_text_field( $_POST['ds_squadron_software_platform']);
            $primaryType = $_POST['ds_squadron_primary_aircraft'];
            $secondaryTypes = $_POST['ds_squadron_secondary_aircraft'];
            $countrySelect = $_POST['ds_squadron_country'];
        
            if ( !empty( $simPostType ) && !empty( $groupName ) ) {
                $subscription->add_meta( '_ds_group_name', $groupName );
                $subscription->add_meta( '_ds_group_platform', $platform );
                $subscription->add_meta( '_ds_primary_type', $primaryType );
                $subscription->add_meta( '_ds_secondary_type', $secondaryTypes );
                $subscription->add_meta( '_ds_group_country', $countrySelect );
            }
        }
    }

    /**
     * This event fires on every action, i.e. subscription, transaction, transaction-failed, user-deleted, etc
     * 
     * We will use this to create a squadron based upon a particular transaction occurring. 
     */
    function ds_group_created_on_frontend( $event ) {
        $obj = $event->get_data();
        //$obj might be a MeprTransaction object or a MeprSubscription object

        if ( $event->event === 'transaction-completed' ) {

            error_log( print_r( $event, true ) );
            error_log( print_r( $obj, true ) );
            error_log( print_r( $_POST, true ) );
        }

        if(!($obj instanceof MeprTransaction) && !($obj instanceof MeprSubscription)) {
            return; // nothing here to do if we're not dealing with a txn or sub
        }
      
        $member = $obj->user();
      
        if($member->is_active_on_membership($obj)) {
          //member is active on membership
          error_log( print_r( 'membership is active...', true ) );
        }
        else {
          //member is no longer active on this membership
          error_log( print_r( 'membership is not active...', true ) );
        }
    }

    /**
     * We need to check what the transaction is for?
     * If it is what we want (Product ID is 79), capture info...
     */
    public function ds_create_a_free_group( $txn ) {
            if ( is_object( $txn ) && $txn->product_id == '79' ) {

                // We first need to check if this group is actually free before we allow it to continue?
                if ( $txn->gateway !== 'free' ) {
                    return;
                }

                $user = wp_get_current_user();

                if ( wp_verify_nonce( $_REQUEST['mepr_checkout_nonce'], 'logged_in_purchase' ) ) {
                    $groupName = sanitize_text_field( $_POST['mepr_squadron_name'] );
                    $platform = sanitize_text_field( $_POST['ds_squadron_software_platform']);
                    $primaryType = $_POST['ds_squadron_primary_aircraft'];
                    $secondaryTypes = $_POST['ds_squadron_secondary_aircraft'];
                    $countrySelect = $_POST['ds_squadron_country'];
                
                    $groupArgs = array(
                        'creator_id'    => $user->ID,
                        'name'          => $groupName
                    );

                    $groupID = (int) $this->ds_create_group( $groupArgs );
                    
                    if ( $groupID ) {
        
                        //groups_update_groupmeta( $groupID, '_ds_subscription_id', $subscription->id );
                        groups_update_groupmeta( $groupID, '_ds_group_country', $countrySelect );
                        groups_update_groupmeta( $groupID, '_ds_primary_aircraft', $primaryType );
        
                        // Attached the group ID to the subscription for future reference...
                        //$subscription->add_meta( '_ds_group_id', $groupID );
        
                        // Attach new group to relevants type...
                        if ( isset( $primaryType ) ) { 
                            
                            groups_update_groupmeta( $groupID, '_ds_group_platform', $platform ) ;
        
                            $allTypes = array( $primaryType );
                            $result = bp_groups_set_group_type( $groupID, $allTypes, true );
        
                            $postType = get_page_by_path( $primaryType, OBJECT, 'bp-group-type' );
                            //error_log( print_r( $postType, true ) );
                            if ( $postType->ID > 0 ) {
                                // Get the default memberships for this group type
                                $defaultGroups = maybe_unserialize(get_post_meta($postType->ID, $this->defaultMembershipStr, true));
                                // add to user to those groups 
                                if ( is_array( $defaultGroups ) ) {
                                    foreach ( $defaultGroups as $g ) {
                                        $r = groups_join_group( $g, $user->ID );
                                    }
                                } 
                            }
                        }

                        $discordArgs = array(
                            'name'          => $groupName,
                            'platform'      => $platform,
                            'primary'       => $primaryType
                        );

                        $this->ds_discord_build_channels( $groupID, $discordArgs );

                    }
                }
            }   
    }

    /** 
     * Once the first transaction has been processed we need to manage link between the subscription and the Buddtpress Group... 
     * 
     * @todo: check when transaction is the first payment so we don't keep creating groups on subsequent payments!
     * @todo: check to make sure the subscription package is correct...
     */
    public function ds_group_transaction_completed( $event ) {
        //error_log( print_r( 'i am being run!!', true ) );
        $isFirstRealPayment = false;
        $transaction = $event->get_data();

        if(!($transaction instanceof MeprTransaction) && !($transaction instanceof MeprSubscription)) {
            return; // nothing here to do if we're not dealing with a txn or sub
        }
        
        $subscription = $transaction->subscription();
        
        // Identify if this is the first ever transaction - if so, we need to create the group...
        if($subscription !== false) {
            if ( $subscription->txn_count == 1 ) {
              $isFirstRealPayment = true;
            }
        }
        error_log( print_r( $isFirstRealPayment, true ) );
        if ( $isFirstRealPayment && $transaction->status == 'complete' ) {
            error_log( print_r( 'tran completed', true ) );
            $user = $transaction->user();

            // Primary Aircraft will be the select group type value to store...
            $primary = $subscription->get_meta( '_ds_primary_type', true);
            $secondaryTypes = $subscription->get_meta( '_ds_secondary_type' );
            error_log( print_r( $primary, true ) );
            $groupArgs = array(
                'creator_id'    => $user->ID,
                'name'          => $subscription->get_meta( '_ds_group_name', true )
            );
            error_log( print_r( $groupArgs, true ) );
            $groupID = (int) $this->ds_create_group( $groupArgs );
            error_log( print_r( 'The group id is .... ' . $groupID, true ) );
            if ( $groupID ) {

                groups_update_groupmeta( $groupID, '_ds_subscription_id', $subscription->id );
                groups_update_groupmeta( $groupID, '_ds_group_country', $subscription->get_meta( '_ds_group_country' ) );
                groups_update_groupmeta( $groupID, '_ds_primary_aircraft', $primary );

                error_log( print_r( 'created a group with ID - ' . $groupID, true ) );
                // Attached the group ID to the subscription for future reference...
                $subscription->add_meta( '_ds_group_id', $groupID );

                // Attach new group to relevants type...
                if ( isset( $primary ) ) { 
                    
                    groups_update_groupmeta( $groupID, '_ds_group_platform', $subscription->get_meta( '_ds_group_platform' ) );

                    $allTypes = array_reverse ( array_merge( $primary, $secondaryTypes ) );
                    $result = bp_groups_set_group_type( $groupID, $allTypes, true ); 
                    error_log( print_r( $result, true ) );

                    $postType = get_page_by_path( $primary, OBJECT, 'bp-group-type' );
                    //error_log( print_r( $postType, true ) );
                    if ( $postType->ID > 0 ) {
                        // Get the default memberships for this group type
                        $defaultGroups = maybe_unserialize(get_post_meta($postType->ID, $this->defaultMembershipStr, true));
                        error_log( print_r( $defaultGroups, true ) );
                        // add to user to those groups 
                        if ( is_array( $defaultGroups ) ) {
                            foreach ( $defaultGroups as $g ) {
                                $r = groups_join_group( $g, $user->ID );
                                error_log( print_r( $r, true ) );
                            }
                        } 
                    }
                    
                }

                $discordArgs = array(
                    'name'          => $groupArgs['name'],
                    'platform'      => $subscription->get_meta( '_ds_group_platform' ),
                    'primary'       => $primary
                );

                $this->ds_discord_build_channels( $groupID, $discordArgs );

                // Have a tidy and remove meta keys that we no longer need attached to subscription...
                $subscription->delete_meta( '_ds_primary_type' );
                $subscription->delete_meta( '_ds_secondary_type' );
                $subscription->delete_meta( '_ds_group_name' );
                $subscription->delete_meta( '_ds_group_country' );
            }

            
        }
    }

    /**
     * Build Roles, Category and Channels for this new Squadron on a Discord Server.
     * 
     */
    public function ds_discord_build_channels( $groupID, $discordArgs ) {
         // Lets get a list of Discord Servers available related to platform and decide on best suited for the task.
         $serverArgs = array(
            'post_type' => 'ds-discord-servers',
            'numberposts' => -1,
            'tax_query' => array(
                                array(
                                    'taxonomy' => 'ds-software-platform',
                                    'field' => 'term_id', 
                                    'terms' => (int) $discordArgs['platform'], // Where term_id of Term 1 is "1".
                                    'include_children' => false
                                )
                        )
        );
        $servers = get_posts( $serverArgs );

        foreach ( $servers as $server ) {
            $count = (int) get_post_meta( $server->ID, '_ds_server_squadron_count', true ) ?: 0;

            $serverTotals[ $server->ID ] = (int) $count;
        }

        asort( $serverTotals );
        $selectedServerPostID = key( $serverTotals );
        $selectedServerID = get_post_meta( (int) $selectedServerPostID, '_ds_discord_server_id', true );

        groups_update_groupmeta( $groupID, "_ds_discord_group_server_post_id", $selectedServerPostID);

        /**
        * Let's do some Discordian related stuff here...
        */
        $client = new RestCord\DiscordClient(['token' => $this->_discordOptions['ds_discord_primary_bot_token'] ]);

        // Check for a Server ID on the Group Type
        $post = get_page_by_path( $discordArgs['primary'], OBJECT, 'bp-group-type');

        if ( $selectedServerID ) {
            $userDiscordID = ds_member_get_discord_value( 'discord_id' );
            // Check if Roles exist, if not create...
            $roleIDs = dsCreateDefaultWingRoles( $post->ID, $this->_discordOptions['ds_discord_primary_bot_token'], $selectedServerID );

            $userAdd = $client->guild->addGuildMember(
                [
                    'guild.id'      =>  (int) $selectedServerID,
                    'user.id'       =>  (int) $userDiscordID,
                    'access_token'  =>  (string) get_user_meta( get_current_user_id(), '_ds_user_access_token', true ),
                    'roles'			=> [ (int) $roleIDs['Squadron Commander'] ] // Give the new user a pilot role.
                ]
            );

            // Create a squadron role for server and save to squadron (group_meta).
            $squadronRole = $client->guild->createGuildRole(
                                [
                                    'guild.id' => (int) $selectedServerID,
                                    'name'      => (string) $discordArgs['name'],
                                    'permissions'   => (int) $this->_discordOptions["ds_discord_default_pilot_channel_permission_id"],
                                    //'color'         => (int) $groupColor,
                                    'hoist'         => false,
                                    'mentionable'   => true
                                ]
                            );
                                    
            groups_update_groupmeta( $groupID, "_ds_discord_group_server_id", $selectedServerID);
            groups_update_groupmeta( $groupID, "_ds_discord_group_pilot_role_id", $squadronRole->id);
            // Add member to roles.
            $client->guild->addGuildMemberRole(
                [
                    'guild.id'      => (int) $selectedServerID,
                    'user.id'       => (int) $userDiscordID,
                    'role.id'       => (string) $squadronRole->id
                ]
            );

            // We need to add the user to the squadron commanders role for the server.
            $client->guild->addGuildMemberRole(
                [
                    'guild.id'      => (int) $selectedServerID,
                    'user.id'       => (int) $userDiscordID,
                    'role.id'       => (string) get_post_meta( (int) $selectedServerPostID, '_ds_discord_platform_squadron_commander_role_id', true )
                ]
            );

            // We need to make them a platform specific squadron leader for the Hanger Server.
            $client->guild->addGuildMemberRole(
                [
                    'guild.id'      => (int) $this->_discordOptions['ds_discord_server_id'],
                    'user.id'       => (int) $userDiscordID,
                    'role.id'       => (string) get_term_meta( (int) $discordArgs['platform'], '_ds_discord_platform_squadron_commander_role_id', true )
                ]
            );

            // Add Category.
            $category = $client->guild->createGuildChannel(
                [
                    'guild.id'      =>  (int) $selectedServerID,
                    'name'          =>  '✈  ' . esc_html( $discordArgs['name'] ),
                    'type'          =>  4,
                    'permission_overwrites' => [
                        [   
                            'id'    => (int) $roleIDs['@everyone'], 
                            'type'  => 'role', 
                            'deny'  => '1099511627775' // Deny everything.
                        ],
                        [
                            'id'    => (int) $squadronRole->id,
                            'type'  => 'role',
                            'allow' => (string) $this->_discordOptions['ds_discord_default_pilot_channel_permission_id'],
                            'deny'  => (string) $this->_discordOptions['ds_discord_default_channel_role_deny_permission_id']
                        ],
                        [
                            'id'    => (int) $roleIDs['Squadron Commander'],
                            'type'  => 'role',
                            'allow' => '17192460544'
                        ]
                    ],
                    'nsfw'                  => false
                ]
            );

            if ( is_object( $category ) ) {
                groups_update_groupmeta( $groupID, '_ds_discord_group_category_id', $category->id );
            }

            // Add Channels.
            $textResult = $client->guild->createGuildChannel(   
                                [
                                    'guild.id'              => (int) $selectedServerID,
                                    'name'                  => '│hanger-chat│',
                                    'type'                  => 0,
                                    'topic'                 => $discordArgs['name'] . ' Related Matters - Text Chat',
                                    'user_limit'            => 0, //0 is unlimited
                                    'rate_limit_per_user'   => 10,
                                    'permission_overwrites' => [
                                            [   
                                                'id'    => (int) $roleIDs['@everyone'], 
                                                'type'  => 'role', 
                                                'deny'  => '1099511627775' // Deny everything.
                                            ],
                                            [
                                                'id'    => (int) $squadronRole->id,
                                                'type'  => 'role',
                                                'allow' => (string) $this->_discordOptions['ds_discord_default_pilot_channel_permission_id'],
                                                'deny'  => (string) $this->_discordOptions['ds_discord_default_channel_role_deny_permission_id']
                                            ]
                                    ],
                                    'parent_id'             => (int) $category->id,
                                    'nsfw'                  => false
                                ]
                            );

            if ( is_object( $textResult ) ) {
                groups_update_groupmeta( $groupID, '_ds_discord_group_text_channel_id', $textResult->id );
            }

            $voiceResult = $client->guild->createGuildChannel(   
                                                [
                                                    'guild.id'              => (int) $selectedServerID,
                                                    'name'                  => 'squadron-comms',
                                                    'type'                  => 2,
                                                    'topic'                 => $discordArgs['name'] . ' Related Matters - Voice Chat',
                                                    'user_limit'            => 0, //0 is unlimited
                                                    'permission_overwrites' => [
                                                        [   
                                                            'id'    => (int) $roleIDs['@everyone'], 
                                                            'type'  => 'role', 
                                                            'deny'  => '1099511627775' // Deny everything.
                                                        ],
                                                        [
                                                            'id'    => (int) $squadronRole->id,
                                                            'type'  => 'role',
                                                            'allow' => (string) $this->_discordOptions['ds_discord_default_pilot_channel_permission_id'],
                                                            'deny'  => (string) $this->_discordOptions['ds_discord_default_channel_role_deny_permission_id']
                                                        ]
                                                    ],
                                                    'parent_id'             => (int) $category->id,
                                                    'nsfw'                  => false
                                                ]
                                            );

            if ( is_object( $voiceResult ) ) {
                groups_update_groupmeta( $groupID, '_ds_discord_group_voice_channel_id', $voiceResult->id );
            }
            $serverCount = (int) $serverTotals[ $selectedServerPostID ] + 1;
            update_post_meta( $selectedServerPostID, '_ds_server_squadron_count', $serverCount );
        }
        
    }

    public function ds_membership_event( $event ) {
        
    }

    /**
     * Create a buddypress group with information supplied by the user at the point of purchasing a group through MemberPress...
     * 
     * @param array $groupArgs
     * 
     * @return int|false
     */
    public function ds_create_group( $groupArgs = array() ) {
        
        $groupDefaultArgs = array(
            'group_id'      => 0,
            'creator_id'    => 0,
            'name'          => '',
            'description'   => '',
            'slug'          => '',
            'status'        => 'private',
            'enable_forum'  => 0
        );

        $groupArgs = array_merge( $groupDefaultArgs, $groupArgs );

        //$userInfo = get_userdata( $groupArgs['creator_id'] );

        $groupID = groups_create_group( $groupArgs );
        $groupObj = groups_get_group ( $groupID );
        $href = bp_get_group_permalink( $groupObj );
        //error_log( print_r( $groupArgs, true ) );

        // Set description link...
        groups_edit_base_group_details(
            array(
                'description'   => 'Please give your Squadron a description...<a href="' . $href . '" alt="Edit Squadron Description">click here</a>'
            )
        );

        return $groupID;

    }

    public function ds_change_group_status() {

    }

    public function ds_group_type_meta_boxes() {
        add_meta_box( 'ds-group-type-default-groups', __( 'Default Groups for Members', 'sitepoint' ), array( $this, 'ds_get_default_groups_callback'), 'bp-group-type', 'normal', 'high' );
    }

    public function ds_get_default_groups_callback( $post) {

        $groups = (bp_is_active('groups'))?BP_Groups_Group::get(array('show_hidden' => true, 'type'=>'alphabetical', 'per_page' => 9999, 'group_type' => array('squadron-commanders-only'))):false;
        $defaultMembershipGroups = maybe_unserialize(get_post_meta($post->ID, $this->defaultMembershipStr, true));
        ?>
        <div>
            <p>Select the default groups that Squadron Commanders will automatically join when creating a Squadron of this group type.</p>
        </div>
        <?php

    if( !$groups ) { return; }
    ?>

    <div id="mepr-buddypress" class="mepr-product-adv-item">

      <div id="mepr_buddypress_membership_groups_area" class="product-options-panel">
          <?php wp_nonce_field( 'group_type_meta_nonce_action', 'group_type_meta_nonce' ); ?>
        <label for="mepr_bp_membership_groups"><?php _e('Default Group(s) for ALL Members', 'memberpress-buddypress'); ?>:</label>
        <br/>
        <select id="ds_group_type_default_memberships" name="ds_group_type_default_memberships[]" multiple="multiple" style="width:98%;height:150px;">
          <?php foreach($groups['groups'] as $g): ?>
            <option value="<?php echo $g->id; ?>" <?php selected( in_array( $g->id, $defaultMembershipGroups, false ) ); ?>><?php echo $g->name; ?></option>
          <?php endforeach; ?>
        </select>
        <br/>
        <small><?php _e('Hold the Control Key (Command Key on the Mac) in order to select or deselect multiple groups.', 'memberpress-buddypress'); ?></small>
      </div>
    </div>
        <?php 
    }

    public function ds_group_type_save_metabox( $post_id, $post ) {
        // Add nonce for security and authentication.
        $nonce_name   = isset( $_POST['group_type_meta_nonce'] ) ? $_POST['group_type_meta_nonce'] : '';
        $nonce_action = 'group_type_meta_nonce_action';
 
        // Check if nonce is valid.
        if ( ! wp_verify_nonce( $nonce_name, $nonce_action ) ) {
            return;
        }

        // Check if user has permissions to save data.
        if ( ! current_user_can( 'edit_post', $post_id ) ) {
            return;
        }
 
        // Check if not an autosave.
        if ( wp_is_post_autosave( $post_id ) ) {
            return;
        }
 
        // Check if not a revision.
        if ( wp_is_post_revision( $post_id ) ) {
            return;
        }

        if( !bp_is_active('groups')) { return; }

        if( isset( $_POST['ds_group_type_default_memberships'] ) ) {
            update_post_meta($post_id, $this->defaultMembershipStr, ( !empty( $_POST['ds_group_type_default_memberships'] ) ) ? (array) $_POST['ds_group_type_default_memberships'] : array() );
        }
    }

    /**
     * Edit Memberpress configuration with Buddypress and the menu links
     * 
     */
    function ds_group_edit_memberpress_menus() {
        $nav = buddypress()->members->nav;
        //error_log( print_r( $nav, true ) );
        // Remove the info page from memberpress's buddypress pages...
        bp_core_remove_subnav_item( 'mp-membership', 'mp-info' );
    
        // Setup the subscription page to default for profile area...
        $nav->edit_nav( 
            array( 
            'default_subnav_slug' => 'mp-subscriptions',
            'css_id' => 'mepr-bp-subscriptions',
            'link' => home_url( '/pilots/' . bp_core_get_username( get_current_user_id() ) . '/mp-membership/mp-subscriptions/' ) ), 
            'mp-membership' );
    
        $mepr = $nav->get_secondary();
        //mp-info, mp-subscriptions, mp-payments
    }

    function ds_hide_joingroup_button( $btn) {

        if ( ! current_user_can('edit_posts' ) ) {
            unset( $btn['id'] );//unsetting id will fore BP_Button to not generate any content
        }
    
        return $btn;
    }
}

if ( class_exists( 'dsMemberpress' ) ) new dsMemberpress;