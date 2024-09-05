<?php

// Exit if accessed directly
if ( !defined( 'ABSPATH' ) ) exit;

class DS_Email_Assign {

    public function __construct() {
        
        add_action( 'admin_menu', array( $this, 'dsEmailAssignTemplatePages' ), 99 );
        add_action( 'manage_' . bp_get_email_post_type() . '_posts_custom_column', array( $this, 'pp_etemplates_add_custom_column_data' ), 10, 2 );
        add_filter( 'manage_' . bp_get_email_post_type() . '_posts_columns', array( $this, 'pp_etemplates_add_custom_column' ) );
        add_action( 'admin_head', array( $this, 'pp_etemplates_admin_styles' ) );
        add_action( 'admin_head', array( $this, 'pp_etemplates_custom_column_css' ) );

        // Admin Meta Box
        add_action( 'add_meta_boxes_' . bp_get_email_post_type(), array( $this, 'dsEmailAssignCustomMetabox' ) );
        add_action( 'save_post_' . bp_get_email_post_type(), array( $this, 'dsEmailAssignMetaboxSave' ), 21, 3 );
        add_filter( 'is_protected_meta', array( $this, 'dsEmailAssignMetaboxProtect' ), 21, 3 );

        add_filter( 'bp_core_get_emails_admin_tabs', array( $this, 'dsEmailAssignCreateTab'), 10, 1 );
    }

    public function dsEmailAssignCustomMetabox(){
        add_meta_box( 'bp-dsEmailAssignTemplate', __( 'Email Template', 'digitalSquadrons' ), array( $this, 'dsEmailAssignTemplateMetabox' ), null, 'side', 'low' );
    }
    
    public function dsEmailAssignTemplateMetabox( $obj ) {
        global $wpdb;
    
        $query = "SELECT * FROM {$wpdb->prefix}options WHERE option_name LIKE 'bp-email-template-%' ORDER BY option_id ASC ";
    
        $items = $wpdb->get_results( $query );
    
        if ( !empty( $items ) ) {
    
            wp_nonce_field(basename(__FILE__), "bp-dsEmailAssignTemplate-meta-box-nonce");
    
            $assigned_template = get_post_meta( $obj->ID, 'bp-etemplate', true );
    
            echo '<div class="categorydiv">';
            echo	'<div class="tabs-panel"><ul>';
    
            foreach( $items as $item ) {
    
                $item_data = maybe_unserialize( $item->option_value );
    
                $template_exists = locate_template( $item_data['fname'], false );
    
                $checked = '';
    
                if ( $item->option_name == 'bp-email-template-0' && empty( $assigned_template ) )
                    $checked = ' checked';
                elseif ( $item->option_name == $assigned_template )
                    $checked = ' checked';
    
                if ( ! empty ( $template_exists ) ) {
                    echo '<br><li class="popular-category"><label class="selectit"><input value="' . $item->option_name . '" type="radio" name="bp-etemplates" ' . $checked .'>' .  $item_data['oname'] . '</label></li>';
                }
                elseif ( $item->option_name == 'bp-email-template-0' ) {
                    echo '<br><li class="popular-category"><label class="selectit"><input value="bp-email-template-0" type="radio" name="bp-etemplates" checked>Default Template</label></li>';
                }
            }
    
            echo '</ul><br></div></div>';
        } else {

            $this->dsEmailAssignSetDefaults();

        }
    }
    
    
    public function dsEmailAssignMetaboxSave( $post_id, $post, $update ) {
    
        if ( !isset( $_POST['bp-dsEmailAssignTemplate-meta-box-nonce'] ) || !wp_verify_nonce( $_POST['bp-dsEmailAssignTemplate-meta-box-nonce'], basename(__FILE__) ) )
            return $post_id;
    
        if ( !current_user_can('manage_options') )
            return $post_id;
    
        if ( defined('DOING_AUTOSAVE') && DOING_AUTOSAVE )
            return $post_id;
    
        if ( isset( $_POST['bp-etemplates'] ) ) {
    
            $value = $_POST['bp-etemplates'];
    
            update_post_meta( $post_id, 'bp-etemplate', $value );
        }
    
    }
    
    
    //  protect the custom meta box so that it does not appear in custom-fields support
    public function dsEmailAssignMetaboxProtect ( $protected, $meta_key, $meta_type ){
    
        if ( $meta_key == 'bp-etemplate' )
            $protected = true;
    
        return $protected;
    }

    /**
     * Set default email templates if not already set...
     */
    public function dsEmailAssignSetDefaults() {

        add_option( 'bp_email_templates_count', 0, NULL, false );
    
        $option_name = 'bp-email-template-0';
    
        $option_value = array(
                'oname' => 'Default Template',
                'fname' => 'single-bp-email.php',
            );
    
        add_option( 'bp-email-template-0', $option_value, NULL, false );
    
    }

    public function dsEmailAssignCreateTab( $tabs ) {

        // Firstly lets remove the customizer...
        foreach ( $tabs as $key => $values ) {
            if ( $values['class'] === 'bp-emails-customizer' ) {
                unset($tabs[$key]);
            }
        }

        $tabs[] = array(
            //'href'  => bp_get_admin_url( add_query_arg( array( 'page' => 'bp-emails-customizer-redirect' ), 'themes.php' ) ),
            'href'  => bp_get_admin_url( add_query_arg( array( 'page' => 'ds-email-templates' ), 'admin.php' ) ),
            'name'  => __( 'Email Templates', 'digitalSquadrons' ),
            'class' => 'ds-email-templates',
        );

        return $tabs;

    }

    public function dsEmailAssignTemplatePages() {

        $capability = bp_core_do_network_admin() ? 'manage_network_options' : 'manage_options';

        // add_menu_page( 
        //     __( 'Email Templates', 'digitalSquadrons' ),
        //     __( 'Email Templates', 'digitalSquadrons' ),
        //     $capability,
        //     'ds-email-templates',
        //     array( $this, 'dsEmailAssignAdminScreen' )
        // );

        add_submenu_page(
            'buddyboss-platform',   // or 'options.php'
            __( 'Email Templates', 'digitalSquadrons' ),
            __( 'Email Templates', 'digitalSquadrons' ),
            $capability,
            'ds-email-templates',
            array( $this, 'dsEmailAssignAdminScreen' )
        );

	    // $capability = bp_core_do_network_admin() ? 'manage_network_options' : 'manage_options';

        // var_dump(bp_get_email_post_type());
        // $dsEmailAssignSubmenuPage = add_submenu_page(
        //     'edit.php?post_type=' . bp_get_email_post_type(),
        //     //'wp-mail-smtp',
        //     'Templates',
        //     'Templates',
        //     $capability,
        //     'ds-emails-templates',
        //     'dsEmailAssignAdminScreen'
        // );

        //add_action( 'load-' . $dsEmailAssignSubmenuPage, array( $this, 'dsEmailAssignHelpTab' ) );
    }

    public function dsEmailAssignHelpTab () {

        $screen = get_current_screen();

        $content =
            '<p>' . __( 'On this screen, you can create, edit or delete an email template option.', 'bp-email-templates' ) . '</p>' .
            '<p>' . __( 'The Option Name can be anything but should be unique.', 'bp-email-templates' ) . '</p>';
        $screen->add_help_tab( array(
            'id'	    => 'name_tab',
            'title'	    => __('Option Name', 'bp-email-templates'),
            'content'	=> $content,
        ) );

        $content =
            '<p>' . __( 'The file name needs to end with ".php" and must be a file that exists in your active theme.', 'bp-email-templates' ) . '</p>' .
            '<p>' . __( 'If you want email templates to live in a separate directory in your theme, then include the path as part of the file name. For example "/bp-email-templates/bp-email-for-friends.php"', 'bp-email-templates' ) . '</p>';
        $screen->add_help_tab( array(
            'id'	    => 'file_tab',
            'title'	    => __('Option File', 'bp-email-templates'),
            'content'	=> $content,
        ) );

        $content =
            '<p>' . __( 'BuddyPress includes a <em>single</em> default email template.', 'bp-email-templates' ) .
            ' <a href="https://codex.buddypress.org/emails/#customize-email-template" target="_blank">'. __('More Info', 'bp-email-templates'). '</a>' . '</p>' .
            '<p>' . __( 'But what if you want multiple and assignable templates? Or have a template that does not use the customizer settings?', 'bp-email-templates' ) . '</p>' .
            '<p>' . __( 'If so, create a copy of the default email template, rename it and adjust as necessary. You can change the layout, include images, remove the customizer setting calls, etc.', 'bp-email-templates' ) . '</p>' .
            '<p>' . __( '[ The customizer settings <em>will apply to all templates</em> that call the customizer settings. ]', 'bp-email-templates' ) . '</p>' .
            '<p>' . __( 'Then upload the new template to your active theme directory and create an option here.', 'bp-email-templates' ) . '</p>' .
            '<p>' . __( 'Your new option will be available for selection on the Email create and edit screens in the lower right area.', 'bp-email-templates' ) . '</p>';
        $screen->add_help_tab( array(
            'id'	    => 'template_tab',
            'title'	    => __('Create a Template', 'bp-email-templates'),
            'content'	=> $content,
        ) );
    }

    function dsEmailAssignEditForm( $id ) {
	    global $wpdb;

	    if ( ! isset( $_POST['eto-id'] ) ) {

		    $this->dsEmailAssignTemplateScripts();

		    echo '<div class="wrap"><h3>Edit Template Option</h3>';

			$query = " SELECT * FROM {$wpdb->prefix}options WHERE option_id = $id ";
            
            $templateOptions = $wpdb->get_row($query);

			if ( $templateOptions != NULL ) {

				$templateOptionsValue = maybe_unserialize( $templateOptions->option_value );

				echo '<form action="' . site_url() . '/wp-admin/edit.php?post_type=bp-email&page=bp-emails-templates&action=edit-ds-template-options" name="ds-template-options-form" id="ds-template-options-form" method="post" class="standard-form">';

				wp_nonce_field('ds-template-options-edit-action', 'ds-template-options-edit-field');

			?>

				<table border="0" cellspacing="10">

					<tr>
						<td>Option Name: <div id='ds-template-options-name-error' class='error_div'></div></td>
						<td><input type="text" name="ds-template-options-name" id="ds-template-options-name" maxlength="50" size="50" value="<?php echo stripslashes( $templateOptionsValue['oname'] ); ?>" /></td>
					</tr>

					<tr>
						<td>Option File: <div id='ds-template-options-file-name-error' class='error_div'></div></td>
						<td><input type="text" name="ds-template-options-file-name" id="ds-template-options-file-name" maxlength="50" size="50" value="<?php echo stripslashes( $templateOptionsValue['fname'] ); ?>" /></td>
					</tr>

				</table>

				<input type="hidden" id="ds-template-options-editor" name="ds-template-options-editor" value="1" />
				<input type="hidden" name="ds-template-options-option-name" id="ds-template-options-option-name" value="<?php echo $templateOptions->option_name; ?>"/>
				<input id="ds-template-options-submit" name="ds-template-options-submit" type="button" class="button button-primary" onclick="validateEtoForm()" value="<?php _e('Update Template Option', 'bp-email-templates'); ?>"  />
				</form>
			<?php
			}
			else
				echo '<br/><div class="error_div"><strong>' . __('The Template Option was not found.', 'bp-email-templates' ) . '</strong></div>';

		        echo '</div>';

	    }
    }

    // print scripts and styles for create / edit forms
    public function dsEmailAssignTemplateScripts() {
        ?>
        <style> .error_div { color: red; } </style>
    
        <script type="text/javascript">
    
            function dsEmailAssignValidateForm() {
                dsEmailAssignRemoveValidationErrors();
    
                if ( dsEmailAssignValidateRequiredField( document.getElementById('ds-template-options-name').value ) == false ) {
                    document.getElementById('ds-template-options-name-error').innerHTML = "Please add a Name.";
                    document.getElementById('ds-template-options-name').style.background= "#eee";
                    document.getElementById('ds-template-options-name').focus();
                    return false;
                }
    
                if ( dsEmailAssignValidateRequiredField( document.getElementById('ds-template-options-file-name').value ) == false ) {
                    document.getElementById('ds-template-options-file-name-error').innerHTML = "Please add a File Name.";
                    document.getElementById('ds-template-options-file-name').style.background= "#eee";
                    document.getElementById('ds-template-options-file-name').focus();
                    return false;
                }
    
                document.forms["ds-template-options-form"].submit();
                return false;
            }
    
    
            function dsEmailAssignRemoveValidationErrors() {
                document.getElementById('ds-template-options-name-error').innerHTML = "";
                document.getElementById('ds-template-options-file-name-error').innerHTML = "";
            }
    
            function dsEmailAssignValidateRequiredField(value) {
                if ( value == null || value == "" ) {
                        return false;
                }
            }
    
        </script>
    <?php
    }

    public function dsEmailAssignUpdateOption() {

	    if ( isset( $_POST['ds-template-options-option-name'] ) ) {

		    if ( !wp_verify_nonce($_POST['ds-template-options-edit-field'],'ds-template-options-edit-action') ) {
                die('Security Check - Failed');
            }
			
            if ( ! current_user_can('manage_options') ) {
                return false;
            }	

		    if ( ! empty( $_POST['ds-template-options-name'] ) && ! empty( $_POST['ds-template-options-file-name'] ) ) {

			    $validateFilename = $this->dsEmailAssignTemplatesValidateFilename( $_POST['ds-template-options-file-name'] );

			    if ( $validateFilename ) {

				    $optionName = $_POST['ds-template-options-option-name'];

                    $optionValue = array(
                            'oname' => $_POST['ds-template-options-name'],
                            'fname' => $_POST['ds-template-options-file-name'],
                        );

				    $updateOption = update_option( $optionName, $optionValue, false );

                    if ( $updateOption ) {
                        echo '<br/><div class="entry-content"><strong>' . __('Template Option was Updated', 'bp-email-templates' ) . '</strong></div><br/>';
                    } else {
                        echo '<br/><div class="error_div"><strong>' . __('There was a problem Updating that Template Option.', 'bp-email-templates' ) . '</strong></div><br/>';
                    }

                } else {
                    echo $validateFilename;
                }
				
		    } else {
            echo '<br/><div class="error_div"><strong>' . __('Please fill out both fields.', 'bp-email-templates' ) . '</strong></div><br/>';
            }	
        }
    }

    public function dsEmailAssignCreateForm() {

	    $this->dsEmailAssignTemplateScripts();

	    echo '<div class="wrap"><h3>Create a Template Option</h3>';

	    echo '<form action="' . site_url() . '/wp-admin/edit.php?post_type=bp-email&page=bp-emails-templates&action=create-ds-template-options" name="ds-template-options-form" id="ds-template-options-form"  method="post" class="standard-form">';

	    wp_nonce_field('ds-template-options-create-action', 'ds-template-options-create-field');
        ?>

                    <table border="0" cellspacing="10">

                        <tr>
                            <td>Option Name: <div id='ds-template-options-name-error' class='error_div'></div></td>
                            <td><input type="text" name="ds-template-options-name" id="ds-template-options-name" maxlength="50" size="50" value="" /></td>
                        </tr>

                        <tr>
                            <td>Option File: <div id='ds-template-options-file-name-error' class='error_div'></div></td>
                            <td><input type="text" name="ds-template-options-file-name" id="ds-template-options-file-name" maxlength="50" size="50" value="" /><br/><em><?php _e('The file must already exist in your active theme directory.', 'bp-email-templates'); ?></em></td>
                        </tr>

                    </table>
                    <input type="hidden" id="ds-template-options-creator" name="ds-template-options-creator" value="1" />
                    <input id="ds-template-options-submit" name="ds-template-options-submit" type="button" class="button button-primary" onclick="dsEmailAssignValidateForm()" value="<?php _e('Create Template Option', 'bp-email-templates'); ?>"  />

                </form>
            </div>
        <?php
    }

    public function dsEmailAssignCreateOption() {

        if ( isset( $_POST['ds-template-options-creator'] ) && $_POST['ds-template-options-creator'] == '1' ) {

            if ( ! empty( $_POST['ds-template-options-name'] ) && ! empty( $_POST['ds-template-options-file-name'] ) ) {

                if ( !wp_verify_nonce($_POST['ds-template-options-create-field'],'ds-template-options-create-action') )
                    die('Security Check - Failed');

                if ( ! current_user_can('manage_options') )
                    return false;


                $validateFilename = $this->dsEmailAssignTemplatesValidateFilename( $_POST['ds-template-options-file-name'] );

                if ( $validateFilename ) {

                    $count = intval( get_option( 'bp_email_templates_count' ) ) + 1;
                    $optionName = 'bp-email-template-' . $count;

                    $optionValue = array(
                            'oname' => $_POST['eto-name'],
                            'fname' => $_POST['eto-file-name'],
                        );

                    $newOption = add_option( $optionName, $optionValue, '', false );

                    write_log( $newOption );

                    if ( $newOption ) {
                        update_option( 'bp_email_templates_count', $count, false );
                        echo '<br/><div class="entry-content"><strong>' . __('Template Option was created.', 'bp-email-templates' ) . '</strong></div><br/>';
                    }
                    else
                        echo '<br/><div class="error_div"><strong>' . __('There was a problem creating that Template Option.', 'bp-email-templates' ) . '</strong></div><br/>';

                } else
                    echo $validateFilename;
            }
            else
                echo '<br/><div class="error_div"><strong>' . __('Please fill out both fields.', 'bp-email-templates' ) . '</strong></div>';
            }
    }

    public function dsEmailAssignTemplatesValidateFilename( $filename ) {

        // check for file name ending in .php
        $fileNameEnd = substr( $filename, -4 );

        if ( '.php' != $fileNameEnd ) {

            echo '<br/><div class="error_div"><strong>' . __('The file name must end with .php', 'bp-email-templates' ) . '</strong></div>';

            return false;
        }

        // check if file exists
        $templateExists = locate_template( $filename, false );

        if ( '' == $templateExists ) {

            echo '<br/><div class="error_div"><strong>' . sprintf( __('The file %s was not found in your active theme.', 'bp-email-templates' ), $filename ) . '</strong></div>';

            return false;
        }

        return true;
    }

    public function dsEmailAssignAdminScreen() {
    ?>
        <div class="wrap">
            <div id="icon-tools" class="icon32"><br /></div>
            <h2><?php _e( 'BuddyPress Email Assign Templates', 'bp-email-templates' )?></h2>

        <?php
                $this->dsEmailAssignUpdateOption();

                if ( isset( $_GET['action'] ) ) {

                    if ( $_GET['action'] == 'create-ds-template-options' )

                        $this->dsEmailAssignCreateOption();

                }


                $listTable = new BP_Email_Templates_Table();

                $listTable->prepare_items();


                if ( isset( $_GET['action'] ) ) {
                    if ( $_GET['action'] == 'edit-single' )
                        $this->dsEmailAssignEditForm( $_GET['gid'] );
                    else
                        $this->dsEmailAssignCreateForm();
                }
                else
                    $this->dsEmailAssignCreateForm();

                ?>

                <br />

                <form id="ds-template-options-filter" method="post">
                    <input type="hidden" name="page" value="<?php echo $_REQUEST['page'] ?>" />
                    <?php $listTable->display();  ?>
                </form>

            </div>
        <?php
    }


    public function pp_etemplates_admin_styles() {

        $page = ( isset($_GET['page'] ) ) ? esc_attr( $_GET['page'] ) : false;

        if ( 'bp-emails-templates' != $page )
            return;

        $style_str = '<style type="text/css">';
        $style_str .= '.column-name { width: 30%; }';
        $style_str .= '.column-file-name { width: 60%; }';
        $style_str .= '</style>';
        echo $style_str;
    }



    public function pp_etemplates_admin_list() {
        if (!class_exists('WP_List_Table')) {
            require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
        }
    }
    
    // add data to Template Name column
    public function pp_etemplates_add_custom_column_data( $column, $post_id ){

        if ( $column == 'etemplate' ) {

            $value = get_post_meta( $post_id, 'bp-etemplate', true );

            if ( ! empty( $value ) ) {

                $option = get_option( $value );

                if ( ! $option ) {

                    // option does not exist, so get rid of post_meta and use default template
                    delete_post_meta( $post_id, 'bp-etemplate' );

                    echo 'Default Template';

                }
                else
                    echo $option['oname'];
            }
            else
                echo 'Default Template';
        }
    }

    public function pp_etemplates_custom_column_css() {
        echo '<style> .column-etemplate {width: 20%} </style>';
    }

    // add Template Name column
    public function pp_etemplates_add_custom_column( $columns ){

        unset($columns['title']);
        unset($columns['date']);

        $columns['title']       = __( 'Title', 'buddypress' );
        $columns['etemplate']   = __( 'Template', 'bp-email-templates' );
        $columns['date']        = __( 'Date', 'bp-email-templates' );

        return $columns;
    }
}

function initEmailAssign() {
    if ( class_exists( 'DS_Email_Assign' )) {
        $dsOAuth = new DS_Email_Assign();
    }
}
// add_action( 'init', 'initEmailAssign' );
$dsEmailAssign = new DS_Email_Assign();