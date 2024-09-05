<?php

// Exit if accessed directly
if ( !defined( 'ABSPATH' ) ) exit;

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