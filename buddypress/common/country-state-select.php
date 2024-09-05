<div id="<?php echo $selectName; ?>-country-select-container" class="">
    <label for="<?php echo $selectName; ?>-country-select"><?php echo esc_html('Country'); ?></label>
    <?php //echo ds_get_region_dropdown('ds-member-location-country-select', false, ds_get_group_meta( '_ds_member_country' ) ) ?> 
</div>

<div id="<?php echo $selectName; ?>-state-select-container" class="" style="margin-bottom: 15px;">
    <label for="<?php echo $selectName; ?>-state-select">State/Region</label>
    <select class="<?php echo $selectName; ?>-state-select dsState" id="<?php echo $selectName; ?>-state-select" ds-attr-id="<?php echo get_user_meta( $userID, '_ds_member_state', true ) ?>" name="<?php echo $selectName; ?>-state-select">
        <option value="0">Select Region</option>
    </select>
</div>


<script>
    jQuery( document ).ready( function() {
        $(function() { 
            $( "select.dsCountry" ).change(function(){

                if ( $( this ).length>0 ) {

                    var cnt = $( this ).children("option:selected").attr('data-id');

                    $( "select#dsState" ).empty().append('<option value="0">Select a State/County</option>');

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
                            var st_selected = ( st_id == $( "select#dsState" ).attr('ds-attr-id') ? 'selected' : '');
                            var opt="<option data-id='"+st_id+"' value='"+st_id+"' "+ st_selected +">"+st_name+"</option>";				
                            $( "select#dsState" ).append(opt);		
                            }
                    }
                    });
                }
            }).change(); // Add .change() here
        });
    });
</script>