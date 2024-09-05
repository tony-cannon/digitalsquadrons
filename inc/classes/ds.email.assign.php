<?php

// Exit if accessed directly
if ( !defined( 'ABSPATH' ) ) exit;

class dsEmailAssign {

    public function __construct() {
        
        add_action('admin_menu', array( $this, 'dsEmailAssignTemplatePages', 11 ) );

        // Admin Meta Box
        add_action( 'add_meta_boxes_' . bp_get_email_post_type(), array( $this, 'dsEmailAssignCustomMetabox' ) );
        add_action( 'save_post_' . bp_get_email_post_type(), array( $this, 'dsEmailAssignMetaboxSave' ), 21, 3 );
        add_filter( 'is_protected_meta', array( $this, 'dsEmailAssignMetaboxProtect' ), 21, 3 );
    }

    public function init(  ) {

    }

    public function dsEmailAssignCustomMetabox(){
        add_meta_box( 'bp-dsEmailAssignTemplate', __( 'Email Template', 'bp-email-templates' ), 'dsEmailAssignTemplateMetabox', null, 'side', 'low' );
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


    public function dsEmailAssignTemplatePages() {

	    $capability = bp_core_do_network_admin() ? 'manage_network_options' : 'manage_options';

        $dsEmailAssignSubmenuPage = add_submenu_page(
            'edit.php?post_type=' . bp_get_email_post_type(),
            'Templates',
            'Templates',
            $capability,
            'ds-emails-templates',
            'dsEmailAssignAdminScreen'
        );

        add_action( 'load-' . $dsEmailAssignSubmenuPage, array( $this, 'dsEmailAssignHelpTab' ) );
    }


    function dsEmailAssignHelpTab () {

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

		if ( !wp_verify_nonce($_POST['ds-template-options-edit-field'],'ds-template-options-edit-action') )
			die('Security Check - Failed');

		if ( ! current_user_can('manage_options') )
			return false;

		if ( ! empty( $_POST['ds-template-options-name'] ) && ! empty( $_POST['ds-template-options-file-name'] ) ) {

			$validateFilename = $this->dsEmailAssignTemplatesValidateFilename( $_POST['ds-template-options-file-name'] );

			if ( $validateFilename ) {

				$optionName = $_POST['ds-template-options-option-name'];

				$optionValue = array(
						'oname' => $_POST['ds-template-options-name'],
						'fname' => $_POST['ds-template-options-file-name'],
					);

				$updateOption = update_option( $optionName, $optionValue, false );

                if ( $updateOption )
					echo '<br/><div class="entry-content"><strong>' . __('Template Option was Updated', 'bp-email-templates' ) . '</strong></div><br/>';
				else
					echo '<br/><div class="error_div"><strong>' . __('There was a problem Updating that Template Option.', 'bp-email-templates' ) . '</strong></div><br/>';

			}
			else
				echo $validateFilename;
		}
		else
			echo '<br/><div class="error_div"><strong>' . __('Please fill out both fields.', 'bp-email-templates' ) . '</strong></div><br/>';

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



    function dsEmailAssignCreateOption() {

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
                <?php $pp_etemplates_list_table->display();  ?>
            </form>

        </div>
    <?php
    }


function pp_etemplates_admin_styles() {

	$page = ( isset($_GET['page'] ) ) ? esc_attr( $_GET['page'] ) : false;

	if ( 'bp-emails-templates' != $page )
		return;

	$style_str = '<style type="text/css">';
	$style_str .= '.column-name { width: 30%; }';
	$style_str .= '.column-file-name { width: 60%; }';
	$style_str .= '</style>';
	echo $style_str;
}
add_action( 'admin_head', 'pp_etemplates_admin_styles'  );


function pp_etemplates_admin_list() {

	if (!class_exists('WP_List_Table')){
		require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
	}

	class BP_Email_Templates_Table extends WP_List_Table {

		 function __construct() {
			 parent::__construct( array(
			'singular'=> 'eto',
			'plural' => 'etos',
			'ajax'	=> false
			) );
		 }

		function get_columns() {
			return $columns= array(
				'cb'            => '<input type="checkbox" />',
				'name'	        => __('Name'),
				'file-name'     => __('File'),
			);
		}


		function get_bulk_actions() {
			$actions = array(
				'delete' => 'Delete'
			);
			return $actions;
		}


		function delete_eto( $id ) {
			global $wpdb;

			$wpdb->query( "DELETE FROM $wpdb->options WHERE option_id = $id" );

		}

		function process_bulk_action() {

			if ( 'delete'===$this->current_action() ) {
				foreach($_POST['bid'] as $id) {
					$this->delete_eto( $id );
				}
			}

			if ( 'delete-single'===$this->current_action() ) {
				$nonce = $_REQUEST['_wpnonce'];
				if (! wp_verify_nonce($nonce, 'eto-nonce') ) die('Security check');

				$this->delete_eto( $_GET['gid'] );
			}

		}


		function prepare_items( $search = NULL ) {
			global $wpdb, $_wp_column_headers;

			$screen = get_current_screen();

			$this->process_bulk_action();

			$query = "SELECT * FROM {$wpdb->prefix}options WHERE option_name LIKE 'bp-email-template-%' ORDER BY option_id DESC ";

			$columns = $this->get_columns();
			$hidden = array();
			$sortable = $this->get_sortable_columns();
			$this->_column_headers = array($columns, $hidden, $sortable);

			$this->items = $wpdb->get_results($query);

		}



		function display_rows() {
			global $wpdb;

			$records = $this->items;        //print_r( $records );

			list( $columns, $hidden ) = $this->get_column_info();

			if ( !empty($records) ) {
				foreach( $records as $rec ) {

					$rec_data = maybe_unserialize( $rec->option_value );

					if ( $rec_data['oname'] == 'Default Template' ) {
						echo '<tr id="record_0"><th scope="row" class="check-column"></th><td class="name column-name">Default Template<br><div class="row-actions"><span class="edit"><a href="https://codex.buddypress.org/emails/#customize-email-template" target="_blank">This is the default template. You cannot delete it, but you can overload it. More info...</a></div></td><td class="file-name column-file-name">single-bp-email.php</td>';
					}
					else {
						echo '<tr id="record_'.$rec->option_id.'">';
						foreach ( $columns as $column_name => $column_display_name ) {

							$class = "class='$column_name column-$column_name'";
							$style = "";
							if ( in_array( $column_name, $hidden ) ) $style = ' style="display:none;"';
							$attributes = $class . $style;

							switch ( $column_name ) {

								case "cb":
									echo '<th scope="row" class="check-column">';
									echo '<input type="checkbox" name="bid[]" value="' . $rec->option_id . '"/>';
									echo '</th>';
									break;

								case "name":
										echo '<td '. $attributes . '>' . stripslashes($rec_data['oname']);
										echo "<br /><div class='row-actions'><span class='edit'>";
										$edit_nonce= wp_create_nonce('eto-edit-nonce');
										echo sprintf('<a href="?post_type=bp-email&page=%s&action=%s&gid=%s&_wpnonce=%s" ">' . __('Edit', 'bp-email-templates') . '</a>',$_REQUEST['page'],'edit-single',$rec->option_id,$edit_nonce);
										echo "</span> | <span class='trash'>";
										$nonce= wp_create_nonce('eto-nonce');
										echo sprintf('<a href="?post_type=bp-email&page=%s&action=%s&gid=%s&_wpnonce=%s" onclick="return confirm(\'Are you sure you want to Delete this Template Option?\');">' . __('Delete', 'bp-email-templates') . '</a>',$_REQUEST['page'],'delete-single',$rec->option_id,$nonce);
										echo "</span></div></td>";
									break;

								case "file-name":
									echo '<td '. $attributes . '>'. $rec_data['fname'] . "</td>";
									break;
							}
						}
						echo'</tr>';
					}
				}
			}
		}
	}
}



// add Template Name column
function pp_etemplates_add_custom_column( $columns ){

	unset($columns['title']);
	unset($columns['date']);

    $columns['title']       = __( 'Title', 'buddypress' );
    $columns['etemplate']   = __( 'Template', 'bp-email-templates' );
    $columns['date']        = __( 'Date', 'bp-email-templates' );

    return $columns;
}
add_filter( 'manage_' . bp_get_email_post_type() . '_posts_columns', 'pp_etemplates_add_custom_column' );

// add data to Template Name column
function pp_etemplates_add_custom_column_data( $column, $post_id ){

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
add_action( 'manage_' . bp_get_email_post_type() . '_posts_custom_column', 'pp_etemplates_add_custom_column_data', 10, 2 );


function pp_etemplates_custom_column_css() {
  echo '<style> .column-etemplate {width: 20%} </style>';
}
add_action( 'admin_head', 'pp_etemplates_custom_column_css' );


/**
  * Making the column sortable is more confusing then helpful
  * because it displays the option value associated with the meta value
  * but will try to sort on the meta name
  * and template may not have a meta_value for that field

function pp_etemplates_custom_column_sortable( $columns ) {

    $columns['etemplate'] = 'etemplatee';

    return $columns;
}
add_filter( 'manage_edit-' . bp_get_email_post_type() . '_sortable_columns', 'pp_etemplates_custom_column_sortable' );


function pp_etemplates_custom_orderby( $query ) {

    if( ! is_admin() )
        return;

    $orderby = $query->get( 'orderby');

    if( 'etemplate' == $orderby ) {
        $query->set( 'meta_key', 'bp-etemplate' );
        $query->set( 'orderby', 'meta_value' );
    }
}
add_action( 'pre_get_posts', 'pp_etemplates_custom_orderby' );
*/

}

