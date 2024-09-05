<div class="groups-admin-manage">
    <p>You can set rules for your Squadron. These rules will be displayed to all pilots on the 'Rules' page via the corresponding tab in the Squadrons main menu. </p>
</div>
<?php 

// default settings - Kv_front_editor.php
$content = ds_get_group_meta( '_ds_group_rules' );
$content = $content ?: 'Please enter your Squadron rules here...';
$editorID = 'ds-group-rules';
$settings =   array(
    'wpautop' => true, // use wpautop?
    'media_buttons' => true, // show insert/upload button(s)
    'textarea_name' => $editorID, // set the textarea name to something different, square brackets [] can be used here
    'textarea_rows' => get_option('default_post_edit_rows', 10), // rows="..."
    'tabindex' => '',
    'editor_css' => '', //  extra styles for both visual and HTML editors buttons, 
    'editor_class' => '', // add extra class(es) to the editor textarea
    'teeny' => false, // output the minimal editor config used in Press This
    'dfw' => false, // replace the default fullscreen with DFW (supported on the front-end in WordPress 3.4)
    'tinymce' => true, // load TinyMCE, can be used to pass settings directly to TinyMCE using an array()
    'quicktags' => true // load Quicktags, can be used to pass settings directly to Quicktags using an array()
);


?>
<div style="margin: 10px 0;">
    <?php wp_editor( $content, $editorID, $settings ); ?>    
</div>
