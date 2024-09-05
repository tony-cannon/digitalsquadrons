<?php

function printr ( $object , $name = '' ) {

    print ( '\'' . $name . '\' : ' ) ;

    if ( is_array ( $object ) ) {
        print ( '<pre>' )  ;
        print_r ( $object ) ;
        print ( '</pre>' ) ;
    } else {
        var_dump ( $object ) ;
    }

}

// add_filter( 'mec_skin_query_args', function( $args ) {
//     printr( $args );

//     return $args;
// } );

/**
 * Add a custom post status for events so to know when they have been deleted by the user on the front end.
 */
function ds_mec_groups_custom_post_status(){
    register_post_status( 'userdeleted', array(
        'label'                     => __( 'User Deleted ', 'mec' ),
        'public'                    => true,
        'label_count'               => _n_noop( 'User Deleted <span class="count">(%s)</span>', 'User Deleted <span class="count">(%s)</span>', 'mec' ),
        'post_type'                 => array( 'mec-events' ), // Define one or more post types the status can be applied to.
        'show_in_admin_all_list'    => true,
        'show_in_admin_status_list' => true,
        'show_in_metabox_dropdown'  => true,
        'show_in_inline_dropdown'   => true,
        'dashicon'                  => 'dashicons-businessman' ) );
}
add_action( 'init', 'ds_mec_groups_custom_post_status' );

add_action('admin_footer-post.php',function(){

    global $post;
    $complete = '';
    $label = '';

    if($post->post_type == 'mec-events') {

        if ( $post->post_status == 'userdeleted' ) {
            $complete = ' selected=\"selected\"';
            $label    = 'User Deleted';
        }

        $script = <<<SD

 
       jQuery(document).ready(function($){
           $("select#post_status").append("<option value=\"userdeleted\" '.$complete.'>User Deleted</option>");
           
           if( "{$post->post_status}" == "userdeleted" ){
                $("span#post-status-display").html("$label");
                $("input#save-post").val("Save User Deleted");
           }
           var jSelect = $("select#post_status");
                
           $("a.save-post-status").on("click", function(){
                
                if( jSelect.val() == "userdeleted" ){
                    
                    $("input#save-post").val("Save User Deleted");
                }
           });
      });
     

SD;

        echo '<script type="text/javascript">' . $script . '</script>';
    }

});

add_action('admin_footer-edit.php',function() {
    global $post;
    if( $post->post_status == 'userdeleted' ) {
        echo "<script>
    jQuery(document).ready( function() {
        jQuery( 'select[name=\"_status\"]' ).append( '<option value=\"userdeleted\">User Deleted</option>' );
    });
    </script>";
    }
});

add_filter( 'display_post_states', function( $statuses ) {
    global $post;

    if( $post->post_type == 'mec-events') {
        if ( get_query_var( 'post_status' ) != 'userdeleted' ) { // not for pages with all posts of this status
            if ( $post->post_status == 'userdeleted' ) {
                return array( 'User Deleted' );
            }
        }
    }
    return $statuses;
});


// function ds_mec_groups_custom_post_status_dropdown( $states ) {
//     global $post;

//     $arg = get_query_var( 'post_status' );
    
//     if ( $arg != 'uDeleted' ) {
//         if ( $post->post_status == 'uDeleted' ) {
    
//             echo "<script>jQuery(document).ready( function() {jQuery( '#post-status-display' ).text( 'uDeleted' );});</script>";
            
//             return array('uDeleted');
//         }
//     }
//     return $states;
// }
// add_filter( 'display_post_states', 'ds_mec_groups_custom_post_status_dropdown' );

//remove_action( 'mec_fes_metabox_details', array( MEC::getInstance('app.features.events', 'MEC_feature_events'), 'meta_box_booking_options' ), 999 );


/**
 * We need to remove the booking option from the FES so that we can edit it from front-end perspective whilst still maintaining the backend state.
 */
ds_remove_class_action( 'mec_fes_metabox_details', 'MEC_feature_events', 'meta_box_booking_options' );