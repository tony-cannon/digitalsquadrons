<div>some select fields...</div>


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