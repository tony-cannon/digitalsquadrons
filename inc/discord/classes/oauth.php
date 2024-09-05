<?php

/**
 * +--------+                               +---------------+
 *    |        |--(A)- Authorization Request ->|   Resource    |
 *    |        |                               |     Owner     |
 *    |        |<-(B)-- Authorization Grant ---|               |
 *    |        |                               +---------------+
 *    |        |
 *    |        |                               +---------------+
 *    |        |--(C)-- Authorization Grant -->| Authorization |
 *    | Client |                               |     Server    |
 *    |        |<-(D)----- Access Token -------|               |
 *    |        |                               +---------------+
 *    |        |
 *    |        |                               +---------------+
 *    |        |--(E)----- Access Token ------>|    Resource   |
 *    |        |                               |     Server    |
 *    |        |<-(F)--- Protected Resource ---|               |
 *    +--------+                               +---------------+
 * 
 * 
 * 
 * 
 */

class DS_Discord_OAuth {

    private $_discordAuthorizeEndpoint;
    private $_discordAccessTokenEndpoint;
    private $_discordGetUserInfoEndpoint;
    private $_discordOptions = array();

    public function __construct()
    {

		$this->_discordOptions = get_option( 'discord-credentials', array() );

        add_action( 'init', array( $this, 'dsLogin' ) );
        add_action( 'init', array( $this, 'dsOAuthStartSession' ) );
        add_action( 'init', array( $this, 'dsOAuthLoginValidate' ) );
		//add_action( 'init', array( $this, 'dsNewOAuthLoginValidate') );
        add_action( 'wp_logout', array( $this, 'dsOAuthEndSession' ) );
		add_action( 'ds_discord_login_button', array( $this, 'dsOAuthLoginButton' ) );
		
		add_action( 'delete_user', array( $this, 'dsDeleteUser' ) );
		add_filter ('bp_core_fetch_avatar_url', array( $this, 'dsDiscordAvatarURL' ), 1000, 2 );
		add_filter ('bp_core_fetch_avatar', array( $this, 'dsDiscordAvatar' ), 1000, 2 );
		
        
        
    }

    public function dsLogin(){
        global $pagenow;
        
        if( 'wp-login.php' == $pagenow && !is_user_logged_in() && !$this->dsIsStagingSite() ) {
            wp_redirect('https://www.digitalsquadrons.com/connect/');
            exit();
        }
    }

	public function dsIsStagingSite() {
		if ( get_site_url() !== 'https://www.digitalsquadrons.com' ) {
			return true;
		}
		return false;
	}

    public function dsOAuthStartSession() {
        if( ! session_id() && ! $this->dsOAuthClientIsAjaxRequest() && ! $this->dsOAuthClientIsRestAPICall() ) {
			session_start();
		}

		// if(isset($_REQUEST['option']) and $_REQUEST['option'] == 'testattrmappingconfig'){
		// 	$mo_oauth_app_name = $_REQUEST['app'];
		// 	wp_redirect(site_url().'?option=oauthredirect&app_name='. urlencode($mo_oauth_app_name)."&test=true");
		// 	exit();
		// }
    }

    public function dsOAuthEndSession() {
        if( ! session_id() ) { 	
            session_start();
        }
		session_destroy();
    }

    public function dsOAuthLoginButton() {
        $this->dsOAuthLoadLoginScripts();
        $button = '<img src="'. wp_get_attachment_url('915') .'" alt="Login with Discord" style="height:30px;opacity:0.7;margin-right:5px;" />';

        ?>
        <a href="javascript:void(0)" onClick="dsOAuthLoginNew('discord');" class="button small outline signin-button link"><?php echo $button; ?>Discord Connect</a>
        <?php
    }

    public function dsOAuthLoadLoginScripts() {
        ?>
        <script type="text/javascript">

            function HandlePopupResult(result) {
                window.location.href = result;
            }

            function dsOAuthLogin(app_name) {
                window.location.href = '<?php echo site_url() ?>' + '/?option=generateDynmicUrl&app_name=' + app_name;
            }
            function dsOAuthLoginNew(app_name) {
                window.location.href = '<?php echo site_url() ?>' + '/?option=oauthredirect&app_name=' + app_name;
            }
        </script>
	    <?php
    }

	public function dsDoingLogin() {
		if ( ( $_REQUEST['option'] && strpos( $_REQUEST['option'], 'oauthdirect' ) ) || strpos( $_SERVER['REQUEST_URI'], "/oauthcallback" ) !== false || isset( $_REQUEST['code'] ) ) {
			return true;
		}

		return false;
	}

    public function dsOAuthLoginValidate() {
		global $wpdb;
		/**
		 * 1st Step: Authorization Request
		 */
        if( isset( $_REQUEST['option'] ) and strpos( $_REQUEST['option'], 'oauthredirect' ) !== false ) {
            $appname = $_REQUEST['app_name'];

            if ($appname === 'discord' ) {

                $randomString = $this->dsGenerateToken();
                $authorizationUrl = $this->_discordOptions['ds_discord_authorize_endpoint'];

                if ( strpos( $authorizationUrl, '?' ) !== false ) {
					$authorizationUrl = $authorizationUrl."&client_id=".$this->_discordOptions['ds_discord_client_id']."&scope=".$this->_discordOptions['ds_discord_scope']."&redirect_uri=".$this->_discordOptions['ds_discord_redirect_callback_url']."&response_type=code&state=".$randomString;
                } else {
					$authorizationUrl = $authorizationUrl."?client_id=".$this->_discordOptions['ds_discord_client_id']."&scope=".$this->_discordOptions['ds_discord_scope']."&redirect_uri=".$this->_discordOptions['ds_discord_redirect_callback_url']."&response_type=code&state=".$randomString;
                }

                if ( strpos( $authorizationUrl, 'apple' ) !== false ) {
                    $authorizationUrl = str_replace( "response_type=code", "response_type=code+id_token", $authorizationUrl );
                    $authorizationUrl = $authorizationUrl . "&response_mode=form_post";
                }

                if(session_id() == '' || !isset($_SESSION)) {
                    session_start();
                }
						
                $_SESSION['oauth2randomstring'] = $randomString;
                $_SESSION['appname'] = $appname;

                header('Location: ' . $authorizationUrl);
                exit;
			}
		/**
		 * 2nd Step: Receive Authorization Grant and Request Access Token
		 * 3rd Step: Receive Access Token
		 */
        } else if ( ( strpos( $_SERVER['REQUEST_URI'], "/oauthcallback" ) !== false || isset( $_REQUEST['code'] ) ) && !wp_doing_ajax() ) {
            
            if(session_id() == '' || !isset($_SESSION)) {
                session_start();
            }

            $this->_discordOptions = get_option( 'discord-credentials', array() );

            if (!isset($_REQUEST['code'])){

				if(isset($_REQUEST['error_description'])){
					$this->dsOAuthLog($_REQUEST['error_description']);
					exit($_REQUEST['error_description']);
				}
				else if(isset($_REQUEST['error']))
				{
					$this->dsOAuthLog($_REQUEST['error']);
					exit($_REQUEST['error']);
				}
				$this->dsOAuthLog('Invalid response');
                exit('Invalid response');
                
			} else {

                try {

                    $currentappname = '';

					if ( isset( $_SESSION['appname'] ) && !empty( $_SESSION['appname'] ) )
						$currentappname = $_SESSION['appname'];
					else if ( isset($_REQUEST['state'] ) && !empty( $_REQUEST['state'] ) ){
						$currentappname = base64_decode( $_REQUEST['state'] );
					}

					if ( empty( $currentappname ) ) {
						$this->dsOAuthLog('No request found for this application.');
						exit('No request found for this application.');
                    }
						
					$sendHeaders = true;
                    $sendBody = false;

					
					$accessToken = $this->dsGetAccessToken( $this->_discordOptions['ds_discord_access_token_endpoint'], 'authorization_code', $this->_discordOptions['ds_discord_client_id'], $this->_discordOptions['ds_discord_client_secret'], $_GET['code'], $this->_discordOptions['ds_discord_redirect_callback_url'], $sendHeaders, $sendBody);
					error_log( print_r( $accessToken, true ) );
					if(!$accessToken){
						$this->dsOAuthLog('Invalid token received.');
						exit('Invalid token received.');
					}

					$resourceownerdetailsurl = $this->_discordOptions['ds_discord_get_user_info_endpoint'];

					if ( substr( $resourceownerdetailsurl, -1 ) == '=' ) {
						$resourceownerdetailsurl .= $accessToken;
					}

					/**
					 * @dsGetResourceOwner should return an array containing all resource values, i.e. username, email, etc...
					 */
					$resourceOwner = $this->dsGetResourceOwner( $resourceownerdetailsurl, $accessToken );

					$username_attr = $this->_discordOptions['ds_discrod_username_attribute'];

					if (isset( $resourceOwner[$username_attr] ) ) {

						/**
						 * We Need to add member to the guild/Server.
						 */
						$client = new RestCord\DiscordClient(['token' => $this->_discordOptions['ds_discord_primary_bot_token'] ]); // Bot Token

						$result = $client->guild->addGuildMember( 	[ 
																	'guild.id'		=> (int) $this->_discordOptions['ds_discord_server_id'],
																	'user.id'		=> (int) $resourceOwner['id'],
																	'access_token' 	=> (string) $accessToken,
																	'roles'			=> [ (int) $this->_discordOptions['ds_discord_pilot_role_id'] ] // Give the new user a pilot role.
																	] 
																);
						
						/**
						 * Lets see if this flyguy is an existing user...
						 */
						$discordTable = $wpdb->prefix . 'ds_discord_oauth';
						$userOAuthData = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $discordTable WHERE discord_id = %d", $resourceOwner['id'] ) );
						$userUpdate = array();

						if ( $userOAuthData ) {
							/**
							 * Update User info
							 */
							$user = get_userdata( $userOAuthData->user_id );
							$userUpdate['ID'] = $userOAuthData->user_id;

							// Clear up any issues if a user is missing their own user channel! 
							if ( ! get_user_meta( $userOAuthData->user_id, '_ds_discord_user_my_channel_id', true ) ) {
								//$userChannelID = $this->dsCreateUserChannel( $resourceOwner['username'], $user->ID );
							}

							if ( $userOAuthData->username !== $resourceOwner['username'] || $userOAuthData->discriminator !== $resourceOwner['discriminator']) {

								// $oldCredentials = array(
								// 	'old_user_login'	=> $userOAuthData['username'] . '-' . $userOAuthData['discriminator'],
								// 	'old_display_name'	=> $userOAuthData['username'] . '#' . $userOAuthData['discriminator']
								// );

								$oldUserLogin = $userOAuthData->username . '#' . $userOAuthData->discriminator;

								$this->dsStoreOldCredentials( $userOAuthData->user_id, $oldUserLogin );
						
								/**
								 * WP_Users Format...
								 * 
								 * user_login		:	discord.username . '-' . discord.discriminator
								 * user_nicename	:	discord.username
								 * display_name		: 	discord.username . '#' . discord.discriminator
								 * 
								 */

								//'user_login' cannot be updated via the wp_update_user function.
								$newUserLogin = $resourceOwner['username'] . '-' . $resourceOwner['discriminator'];
								$wpdb->update( $wpdb->users, ['user_login' => $newUserLogin], ['ID' => $userUpdate['ID']] );
								
								$userUpdate['display_name'] = $resourceOwner['username'] . '#' . $resourceOwner['discriminator'];
								$userUpdate['user_nicename'] = $resourceOwner['username'] . $resourceOwner['discriminator'];
								//$userUpdate['nickname'] = $userUpdate['display_name'];
								$userUpdate['nickname'] = $resourceOwner['username'] . '#' . $resourceOwner['discriminator'];

								/**
								 * We also need to update the user's Discord channel...
								 */
								$userChannelID = get_user_meta( $userOAuthData->user_id, '_ds_discord_user_my_channel_id', true );	
								if ( $userChannelID ) {
									$client->channel->modifyChannel(
										[
											'channel.id'	=> (int) $userChannelID,
											'name'          => strtolower( preg_replace("/[^\w]+/", "-", $resourceOwner['username'] ) ),
											'topic'			=> $resourceOwner['username'] . ', this is your very own personal channel and only your connections on Digital Squadron will be able to view this and communicate with you.',
										]
									);
								}
								

							}

							if ( $user->user_email !== $resourceOwner['email'] ) {
								$userUpdate['user_email'] = $resourceOwner['email'];
							}

							$userUpdate = wp_update_user( $userUpdate );
							//Update the OAUTH Discord table...
							$discordTableUpdated = $this->dsUpdateDiscordTable( $resourceOwner, $userOAuthData->user_id, true );

						} else {
							/**
							 * Create a User...
							 */
							//formulate the username from the current resource, use to create new account or check against existing to see if update required...
							$newUserLogin = $resourceOwner['username'] . '-' . $resourceOwner['discriminator'];
							$newDisplayName = $resourceOwner['username'] . '#' . $resourceOwner['discriminator'];

							$user = $this->dsCreateUser( $newUserLogin, $resourceOwner['email'] );

							if ( $user ) {
								/**
							 	 * Create link entry...
							 	 */
								$discordTableUpdated = $this->dsUpdateDiscordTable( $resourceOwner, $user->ID );

								$userUpdate = array(
									'ID'			=> $user->ID,
									//'nickname'		=> $resourceOwner['username'],
									'nickname'		=> $newDisplayName,
									'display_name'	=> $newDisplayName
								);

								$userUpdate = wp_update_user( $userUpdate );

								/**
								 * Create a channel for user and store in their 'My Channel' Category
								 */
								$userResult = $this->dsCreateUserChannel( $resourceOwner['username'], $user->ID );

							} else {
								//Error...
							}
						}

						if ( is_wp_error( $userUpdate ) ) {
							//ERRROR:Update didn't happen! 
						} else {
							// if ( $resourceOwner['avatar'] !== '' && $resourceOwner['avatar'] !== $userOAuthData['avatar_hash'] ) {
							// 	/**
							// 	 * Update the avatar image...
							// 	 */
							// }
						}

					} else {
						$this->dsOAuthLog('ERROR: No login credentials from Discord....');
					}

					if ( $user ) {
						wp_set_current_user($user->ID);
						wp_set_auth_cookie($user->ID);
						delete_user_meta( $user->ID, '_ds_user_access_token' );
						update_user_meta( $user->ID, '_ds_user_access_token', $accessToken );

						$user  = get_user_by( 'ID', $user->ID );
						do_action( 'wp_login', $user->user_login, $user );
						$redirect_to = $this->_discordOptions['ds_discord_redirect_after_login'];
						
						if ( $redirect_to == '' ) {
							$redirect_to = home_url();
						}

						wp_redirect($redirect_to);						
						exit;
					}
					

                } catch ( Exception $e ) {

					// Failed to get the access token or user details.
					//print_r($e);
					$this->dsOAuthLog( $e->getMessage() );
					exit( $e->getMessage() );

				}

            }
				
        }
    }

	public function dsStoreOldCredentials( $userID, $oldCredentials = null ) {
		if ( is_null( $oldCredentials ) ) {
			return false;
		}

		global $wpdb;

		$storeCredentials = array();
		
		$previousCredentials = $wpdb->get_var( $wpdb->prepare( " SELECT prev_usernames FROM {$wpdb->prefix}ds_discord_oauth WHERE user_id = %d ", $userID ) );
		if ( is_string( $previousCredentials ) ) {
			$credentialsArray = explode( ',', $previousCredentials );

			if ( !in_array( $oldCredentials, $credentialsArray ) ) {
				$credentialsArray[] = $oldCredentials . ',';
			}

			if ( count( $credentialsArray ) > 5 ) {
				array_shift( $credentialsArray );
			}

		} else {
			$storeCredentials[] = $oldCredentials;
		}

		$storeCredentials = implode( ',', $credentialsArray );

		$result = $wpdb->update( $wpdb->prefix . 'ds_discord_oauth', ['prev_usernames' => $storeCredentials], ['user_id' => $userID], '%s', '%d' );

		return $result;
	}

    public function dsGetAccessTokenCurl($tokenendpoint, $grant_type, $clientid, $clientsecret, $code, $redirect_url, $send_headers, $send_body){

		$ch = curl_init($tokenendpoint);
		curl_setopt( $ch, CURLOPT_FOLLOWLOCATION, true );
		curl_setopt( $ch, CURLOPT_ENCODING, "" );
		curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
		curl_setopt( $ch, CURLOPT_AUTOREFERER, true );
		curl_setopt( $ch, CURLOPT_SSL_VERIFYPEER, false );
		curl_setopt( $ch, CURLOPT_MAXREDIRS, 10 );
		curl_setopt( $ch, CURLOPT_POST, true);
		curl_setopt($ch, CURLOPT_HTTPHEADER, array(
			'Authorization: Basic '.base64_encode($clientid.":".$clientsecret),
			'Accept: application/json'
		));

		curl_setopt( $ch, CURLOPT_POSTFIELDS, 'redirect_uri='.urlencode($redirect_url).'&grant_type='.$grant_type.'&client_id='.$clientid.'&client_secret='.$clientsecret.'&code='.$code);
		$content = curl_exec($ch);

		if(curl_error($ch)){
			echo "<b>Response : </b><br>";print_r($content);echo "<br><br>";
			$this->dsOAuthLog(curl_error($ch));
			exit( curl_error($ch) );
		}

		if(!is_array(json_decode($content, true))){
			echo "<b>Response : </b><br>";print_r($content);echo "<br><br>";
			$this->dsOAuthLog("Invalid response received.");
			exit("Invalid response received.");
		}

		$content = json_decode($content,true);
		if(isset($content["error_description"])){
			$this->dsOAuthLog($content["error_description"]);
			exit($content["error_description"]);
		} else if(isset($content["error"])){
			$this->dsOAuthLog($content["error"]);
			exit($content["error"]);
		} else if(isset($content["access_token"])) {
			$access_token = $content["access_token"];
		} else {
			echo "<b>Response : </b><br>";print_r($content);echo "<br><br>";
			$this->dsOAuthLog('Invalid response received from OAuth Provider. Contact your administrator for more details.');
			exit('Invalid response received from OAuth Provider. Contact your administrator for more details.');
		}

		return $access_token;
	}


	public function dsGetAccessToken($tokenendpoint, $grant_type, $clientid, $clientsecret, $code, $redirect_url, $send_headers, $send_body){
		$response = $this->dsGetToken ($tokenendpoint, $grant_type, $clientid, $clientsecret, $code, $redirect_url, $send_headers, $send_body);
		$content = json_decode($response,true);

		if(isset($content["access_token"])) {
			return $content["access_token"];
			exit;
		} else {
			echo 'Invalid response received from OAuth Provider. Contact your administrator for more details.<br><br><b>Response : </b><br>'.$response;
			exit;
		}
	}

	/**
	 * Send request to Discord for Access Token
	 * 
	 * 
	 */
	public function dsGetToken($tokenendpoint, $grant_type, $clientid, $clientsecret, $code, $redirect_url, $send_headers, $send_body){

		$clientsecret = html_entity_decode( $clientsecret );
		$body = array(
				'grant_type'    => $grant_type,
				'code'          => $code,
				'client_id'     => $clientid,
				'client_secret' => $clientsecret,
				'redirect_uri'  => $redirect_url,
			);
		$headers = array(
				'Accept'  => 'application/json',
				'charset'       => 'UTF - 8',
				'Authorization' => 'Basic ' . base64_encode( $clientid . ':' . $clientsecret ),
				'Content-Type' => 'application/x-www-form-urlencoded',
		);
		if($send_headers && !$send_body){
				unset( $body['client_id'] );
				unset( $body['client_secret'] );
		}else if(!$send_headers && $send_body){
				unset( $headers['Authorization'] );
		}
		
		$response   = wp_remote_post( $tokenendpoint, array(
			'method'      => 'POST',
			'timeout'     => 45,
			'redirection' => 5,
			'httpversion' => '1.0',
			'blocking'    => true,
			'headers'     => $headers,
			'body'        => $body,
			'cookies'     => array(),
			'sslverify'   => false
		) );
		if ( is_wp_error( $response ) ) {
			$this->dsOAuthLog('Invalid response recieved while fetching token');
			wp_die( $response );
		}
		$response =  $response['body'] ;

		if(!is_array(json_decode($response, true))){
			echo "<b>Response : </b><br>";print_r($response);echo "<br><br>";
			$this->dsOAuthLog('Invalid response received.');
			exit("Invalid response received.");
		}
		
		$content = json_decode($response,true);
		if(isset($content["error_description"])){
			$this->dsOAuthLog($content["error_description"]);
			exit($content["error_description"]);
		} else if(isset($content["error"])){
			$this->dsOAuthLog($content["error"]);
			exit($content["error"]);
		}
		
		return $response;
	}
	
	/**
	 * Get the Access Token from via dsGetToken function then decode json and return an array 
	 */
	public function dsGetIDToken($tokenendpoint, $grant_type, $clientid, $clientsecret, $code, $redirect_url, $send_headers, $send_body){
		$response = $this->dsGetToken ($tokenendpoint, $grant_type, $clientid, $clientsecret, $code, $redirect_url, $send_headers, $send_body);
		$content = json_decode($response,true);
		if(isset($content["id_token"]) || isset($content["access_token"])) {
			return $content;
			exit;
		} else {
			$this->dsOAuthLog('Invalid response received from OpenId Provider. Contact your administrator for more details.Response : '.$response);
			echo 'Invalid response received from OpenId Provider. Contact your administrator for more details.<br><br><b>Response : </b><br>'.$response;
			exit;
		}
	}

	public function dsGetResourceOwnerFromIDToken($id_token){
		$id_array = explode(".", $id_token);
		if(isset($id_array[1])) {
			$id_body = base64_decode($id_array[1]);
			if(is_array(json_decode($id_body, true))){
				return json_decode($id_body,true);
			}
		}
		$this->dsOAuthLog('Invalid response received.Id_token : '.$id_token);
		echo 'Invalid response received.<br><b>Id_token : </b>'.$id_token;
		exit;
	}
	
	/**
	 * Request user info...
	 * 
	 * @return array $content
	 */
	public function dsGetResourceOwner($resourceownerdetailsurl, $access_token){
		$headers = array();
		$headers['Authorization'] = 'Bearer '.$access_token;

		$response   = wp_remote_post( $resourceownerdetailsurl, array(
			'method'      => 'GET',
			'timeout'     => 45,
			'redirection' => 5,
			'httpversion' => '1.0',
			'blocking'    => true,
			'headers'     => $headers,
			'cookies'     => array(),
			'sslverify'   => false
		) );

		if ( is_wp_error( $response ) ) {
			$this->dsOAuthLog('Invalid response recieved while fetching resource owner details');
			wp_die( $response );
		}

		$response =  $response['body'] ;

		if(!is_array(json_decode($response, true))){
			$response = addcslashes($response, '\\');
			if(!is_array(json_decode($response, true))){
			echo "<b>Response : </b><br>";print_r($response);echo "<br><br>";
			$this->dsOAuthLog("Invalid response received.");
			exit("Invalid response received.");
			}
		}
		
		$content = json_decode($response,true);
		if(isset($content["error_description"])){
			$this->dsOAuthLog($content["error_description"]);
			exit($content["error_description"]);
		} else if(isset($content["error"])){
			$this->dsOAuthLog($content["error"]);
			exit($content["error"]);
		}
		//error_log( print_r( $content, true) );
		return $content;
	}
	
	public function dsGetResponse($url){
		$response = wp_remote_get($url, array(
			'method' => 'GET',
			'timeout' => 45,
			'redirection' => 5,
			'httpversion' => 1.0,
			'blocking' => true,
			'headers' => array(),
			'cookies' => array(),
			'sslverify' => false,
		));

		$content = json_decode($response,true);
		if(isset($content["error_description"])){
			$this->dsOAuthLog($content["error_description"]);
			exit($content["error_description"]);
		} else if(isset($content["error"])){
			$this->dsOAuthLog($content["error"]);
			exit($content["error"]);
		}
		
		return $content;
	}

    public function dsOAuthLog($dsMessage)
	{
		$dsOAuthLog = get_stylesheet_directory() . 'logs/OAuth-error.log';
		$dsTime = time();
		$dsLog = '['.date("Y-m-d H:i:s", $dsTime ).' UTC] : '.$dsMessage.PHP_EOL;
		//error_log( $dsLog, 3 );
	}

    public function dsOAuthClientIsAjaxRequest() {
		return defined('DOING_AJAX') && DOING_AJAX;
	}

	public function dsOAuthClientIsRestAPICall() {
		return strpos( $_SERVER['REQUEST_URI'], '/wp-json' ) == false;
	}

	/**
	 * New user, create an account
	 * 
	 * @todo: are we checking this correctly, can users change username in Discord, do we need to compare against discord ID?
	 */
	public function dsOAuthCreateUser( $username ) {

		$random_password = wp_generate_password( 10, false );
		// if(is_email($email))
		// 	$user_id = wp_create_user( $email, $random_password, $email );
		// else
		// 	$user_id = wp_create_user( $email, $random_password);	
		$user_id = 	wp_create_user( $username, $random_password);
		$user = get_user_by( 'login', $username);			
		wp_update_user( array( 'ID' => $user_id ) );
		return $user;
	}

	private function dsGenerateToken() {
        $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $charactersLen = strlen($characters);
        $randomString = "";
        for ($i = 0; $i < 20; $i++) {
            $randomString .= $characters[rand(0, $charactersLen - 1)];
        }
        return $randomString;
	}
	
	private function dsCreateUser( $username, $email ) {
		
		$randomPassword = wp_generate_password( 10, false );
		//$userMeta = array();
		// if(is_email($email))
		// 	$user_id = wp_create_user( $email, $random_password, $email );
		// else
		// 	$user_id = wp_create_user( $email, $random_password);	
		$user_id = 	wp_create_user( $username, $randomPassword, $email );
		$user = get_user_by( 'login', $username );			
		wp_update_user( array( 'ID' => $user_id ) );

		/**
		 * We need to run this hook so that memberpress will create default membership 'pilot' for this user...
		 */
		do_action( 'bp_core_signup_user', $user_id, $username, $randomPassword, $email, $usermeta = array() );

		//$user = bp_core_signup_user( $username, $randomPassword, $email, $userMeta );
		return $user;
	}
	
	public function dsDiscordAvatarURL ( $url, $args) {
		global $wpdb;
		if ( $args['object'] != 'user' ) {
			return $url;
		}
		//$discordTable = $wpdb->prefix . 'ds_discord_oauth';
		//$discordUser = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $discordTable WHERE user_id = %d", $args['item_id'] ) );
		//var_dump($args);
		//var_dump($discordUser->avatar_hash);
		$discordUser = dsGetDiscordUser( $args['item_id']);
		
		if ( is_object( $discordUser ) ) {
			if ( strlen($discordUser->avatar_hash) !== 0 ) {
				$discordURL = 'https://cdn.discordapp.com/avatars/' . $discordUser->discord_id . '/' . $discordUser->avatar_hash . '.png';

				if ( @getimagesize( $discordURL ) ) {
					return $discordURL;
				}
			}
		}
		
    	// $avatar = '<hr><img alt="' . $alt . '" src="' . $args['url'] . '" class="avatar avatar - ' . $size . ' photo" height="' . $size . '" width="' . $size . '" />';
    	return $url;
	}

	public function dsDiscordAvatar ( $imgTag, $args) {
		global $wpdb;
		if ( $args['object'] != 'user' ) {
			return $imgTag;
		}
		//$discordTable = $wpdb->prefix . 'ds_discord_oauth';
		//$discordUser = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $discordTable WHERE user_id = %d", $args['item_id'] ) );
		//var_dump($args['item_id']);
		//var_dump($args);

		$discordUser = dsGetDiscordUser( $args['item_id']);

		if ( is_object( $discordUser ) ) {
			if ( strlen($discordUser->avatar_hash) !== 0 && $args['class'] === 'avatar' ) {
				$discordURL = 'https://cdn.discordapp.com/avatars/' . $discordUser->discord_id . '/' . $discordUser->avatar_hash . '.png';

				if ( @getimagesize($discordURL ) ) {
					$imgTag = preg_replace( '/src=".+?"/', 'src="' . $discordURL . '"', $imgTag );
				}
			}
		}
		
    	// $avatar = '<hr><img alt="' . $alt . '" src="' . $args['url'] . '" class="avatar avatar - ' . $size . ' photo" height="' . $size . '" width="' . $size . '" />';
    	return $imgTag;
	}

	public function dsDeleteUser( $userID ) {
		global $wpdb;

		$discordTable = $wpdb->prefix . 'ds_discord_oauth';
		$wpdb->delete( $discordTable, array( 'user_id' => $userID ), array( '%d' ) );


	}

	private function dsUpdateDiscordTable( $resourceOwner, $userID, $update = false ) {
		global $wpdb;

		$discordTable = $wpdb->prefix . 'ds_discord_oauth';

		if ( $update ) {
			//$userOAuthData = $wpdb->query( $wpdb->prepare( "UPDATE INTO $discordTable last_login='$newUOAuthData['last_login']' WHERE discord_id='&d'", $newUOAuthData['id'] ) );
			$userOAuthData = $wpdb->update( $discordTable, 
											array( 
												'last_login' 	=> current_time( 'timestamp' ), 
												'avatar_hash' 	=> $resourceOwner['avatar'],
												'username'		=> $resourceOwner['username'],
												'email'			=> $resourceOwner['email'],
												'discriminator'	=> $resourceOwner['discriminator']	
											), 
											 array( 
												 'discord_id' => $resourceOwner['id'] 
											),
											 array( '%d', '%s', '%s', '%s', '%s' ),
											 array( '%d' ) 
											);
		} else {

			$newUOAuthData = array(
				'discord_id'	=>	$resourceOwner['id'],
				'user_id'		=>	$userID,
				'last_login'	=>	current_time( 'timestamp' ),
				'avatar_hash'	=>	$resourceOwner['avatar'],
				'username'		=>	$resourceOwner['username'],
				'email'			=>	$resourceOwner['email'],
				'discriminator'	=>	$resourceOwner['discriminator']
			);

			$userOAuthData = $wpdb->query( $wpdb->prepare( "INSERT INTO $discordTable (discord_id, user_id, last_login, avatar_hash, username, email, discriminator) VALUES (%d, %d, %d, %s, %s, %s, %s)", $newUOAuthData ) );
		}

		return $userOAuthData;

	}

	/**
	 * Process a username change
	 *
	 * @since       3.0.0
	 * @param       string $oldUsername The old (current) username.
	 * @param       string $newUsername The new username.
	 * @param       string $discriminator The discord discriminator.
	 * @return      bool $return Whether or not we completed successfully
	 */
	public function dsChangeUsername( $oldUserLogin, $oldDisplayName, $newUsername, $newDiscrimainator ) {
		global $wpdb;

		$return = false;
		$credentials = array(
			'old_user_login'	=> $oldUserLogin,
			'old_display_name'	=> $oldDisplayName
		);

		/**
		 * WP_Users Format...
		 * 
		 * user_login		:	discord.username . '-' . discord.discriminator
		 * user_nicename	:	discord.username
		 * display_name		: 	discord.username . '#' . discord.discriminator
		 * 
		 */

		//used for login purposes on standard wordpress installation
		$credentials['new_user_login'] = $newUsername . '-' . $newDiscrimainator;
		//user for url purposes to link profiles
		$credentials['new_user_nicename'] = $newUsername . $newDiscrimainator;
		//shown to all other community members as an identification.
		$credentials['new_display_name'] = $newUsername . '#' . $newDiscrimainator;
		
		// One last sanity check to ensure the user exists.
		$userID = username_exists( $oldUserLogin );

		if ( $userID ) {
			// Let devs hook into the process.
			do_action( 'ds_change_username_before', $credentials  );
			error_log( print_r( $credentials, true) );

			// Update user_login and user_nicename!
			$qun = $wpdb->prepare( "UPDATE $wpdb->users SET user_login = %s AND user_nicename = %s WHERE user_login = %s", array( $credentials['new_user_login'], $credentials['new_user_nicename'], $oldUserLogin ) ); // phpcs:ignore WordPressVIPMinimum.Variables.RestrictedVariables

			if ( false !== $wpdb->query( $qun ) ) { // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared,WordPress.DB.DirectDatabaseQuery
				// // Update user_nicename.
				error_log( print_r( 'boom im here again!', true) );
				// $qnn = $wpdb->prepare( "UPDATE $wpdb->users SET user_nicename = %s WHERE user_login = %s AND user_nicename = %s", $newUsername, $newUsername, $newHashName ); // phpcs:ignore WordPressVIPMinimum.Variables.RestrictedVariables
				// $wpdb->query( $qnn ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared,WordPress.DB.DirectDatabaseQuery
				
				// Update display_name.
				$qdn = $wpdb->prepare( "UPDATE $wpdb->users SET display_name = %s WHERE user_login = %s AND display_name = %s", array( $credentials['new_display_name'], $credentials['new_user_login'], $oldDisplayName ) ); // phpcs:ignore WordPressVIPMinimum.Variables.RestrictedVariables
				$wpdb->query( $qdn ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared,WordPress.DB.DirectDatabaseQuery

				// Update nickname.
				// $nickname = get_user_meta( $userID, 'nickname', true );
				// if ( $nickname === $oldHashName ) {
				// 	update_user_meta( $userID, 'nickname', $newHashName );
				// }

				$return = true;
			}

			return $return;
		}
	}

	public function dsCreateUserChannel( $username, $userID ) {

		$client = new RestCord\DiscordClient(['token' => $this->_discordOptions['ds_discord_primary_bot_token'] ]); // Bot Token

		$userResult = $client->guild->createGuildChannel(   
			[
				'guild.id'              => (int) $this->_discordOptions['ds_discord_server_id'],
				'name'                  => strtolower( preg_replace("/[^\w]+/", "-", $username ) ),
				'type'                  => 0,
				'topic'                 => $username . ', this is your very own personal channel and only your connections on Digital Squadron will be able to view this and communicate with you.',
				'user_limit'            => 0, //0 is unlimited
				'rate_limit_per_user'   => 10,
				'permission_overwrites' => [
						[   
							'id'    => $this->_discordOptions['ds_discord_everyone_role_id'], 
							'type'  => 'role', 
							'deny'  => (string) $this->_discordOptions['ds_discord_default_channel_permission_id'], // Deny them all privileges.
						],
						[
							'id'    => (int) ds_member_get_discord_value('discord_id', $userID ), // Discord User ID
							'type'  => 'member', // User '1' or Role '0'
							'allow' => (string) $this->_discordOptions['ds_discord_default_channel_permission_id'], // Manage permissions calculator... https://dpermcalc.neocities.org/#1571904
						]
				],
				'parent_id'             => (int) $this->_discordOptions['ds_discord_my_channel_parent_id'],
				'nsfw'                  => false
			]
		);

		if ( is_object( $userResult ) ) {
			update_user_meta( $userID, '_ds_discord_user_my_channel_id', $userResult->id );

			return $userResult;
		}

		return false;

	}

}

function initDiscordOAuth() {
    if ( class_exists( 'DS_Discord_OAuth' )) {
        $dsOAuth = new DS_Discord_OAuth();
    }
}
add_action( 'init', 'initDiscordOAuth', 1 );