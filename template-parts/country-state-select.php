<div id="<?php echo $args['selectName']; ?>-country-select-container" class="">
    <label for="<?php echo $args['selectName']; ?>-country-select"><?php echo esc_html('Country'); ?></label>
    <select class="<?php echo $args['selectName']; ?>-country-select" id="<?php echo $args['selectName']; ?>-country-select" name="<?php echo $args['selectName']; ?>-country-select">
        <option value="0"><?php echo $args['placeholderCountry']; ?></option>
        <?php echo ds_get_country_options( get_user_meta( bp_displayed_user_id(), '_ds_member_country', true ) ); ?>
    </select>
</div>

<?php if ( $args['state'] ) : ?>

<div id="<?php echo $args['selectName']; ?>-state-select-container" class="" style="margin-bottom: 15px;">
    <label for="<?php echo $args['selectName']; ?>-state-select">State/Region</label>
    <select class="<?php echo $args['selectName']; ?>-state-select" id="<?php echo $args['selectName']; ?>-state-select" ds-attr-id="<?php echo get_user_meta( bp_displayed_user_id(), '_ds_member_state', true ) ?>" name="<?php echo $args['selectName']; ?>-state-select">
        <option value="0">Select Region</option>
    </select>
</div>


<script>
    jQuery( document ).ready( function() {
        $(function() { 
            $( "select#<?php echo $args['selectName']; ?>-country-select" ).change(function(){

                if ( $( this ).length>0 ) {

                    var cnt = $( this ).children("option:selected").attr('data-id');

                    $( "select#<?php echo $args['selectName']; ?>-state-select" ).empty().append('<option value="0">Select a State/County</option>');

                    jQuery.ajax({
                        url : ds_ajax.ajax_url,
                        type : 'post',
                        dataType : "json",
                        data : { action: "ds_groups_get_states", nonce_ajax : ds_ajax.nonce, cnt:cnt },
                        success : function( response ) {
                        //console.log(response);
                        for(i=0;i<response.length;i++) {
                            var st_id=response[i]['id'];
                            var st_name=response[i]['name'];
                            var st_selected = ( st_id == $( "select#<?php echo $args['selectName']; ?>-state-select" ).attr('ds-attr-id') ? 'selected' : '');
                            var opt="<option data-id='"+st_id+"' value='"+st_id+"' "+ st_selected +">"+st_name+"</option>";				
                            $( "select#<?php echo $args['selectName']; ?>-state-select" ).append(opt);		
                            }
                    }
                    });
                }
            }).change(); // Add .change() here
        });
    });
</script>

<?php endif; ?>