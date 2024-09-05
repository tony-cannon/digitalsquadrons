jQuery( document ).ready( function() {

        //var attr_state = $( "select#dsState" ).attr('ds-attr-id');
        //var attr_state = $( "select#dsState" ).attr('ds-attr-id');

        $(function() { 
            $( "select#dsCountry" ).change(function(){

                if ( $( "select#dsCountry" ).length>0 ) {

                    var cnt = $( "select#dsCountry" ).children("option:selected").attr('data-id');

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

        $(function() { 
            $( "select#dsState" ).change(function(){

                if ( $( "select#dsState" ).length>0 ) {

                    var sid = $( "select#dsState" ).children("option:selected").attr('data-id');

                    $( "select#dsCity" ).empty().append('<option value="0">Select a City/Town</option>');

                    jQuery.ajax({
                        url : ds_ajax.ajax_url,
                        type : 'post',
                        dataType : "json",
                        data : { action: "ds_groups_get_city", nonce_ajax : ds_ajax.nonce, sid:sid },
                        success : function( response ) {
                        //console.log(response);
                        for(i=0;i<response.length;i++) {
                            var st_id=response[i]['id'];
                            var st_name=response[i]['name'];
                            var st_selected = ( st_id == $( "select#dsCity" ).attr('ds-attr-id') ? 'selected' : '');
                            var opt="<option data-id='"+st_id+"' value='"+st_id+"' "+ st_selected +">"+st_name+"</option>";				
                            $( "select#dsCity" ).append(opt);		
                            }
                    }
                    });
                }
            }).change(); // Add .change() here
        });

        /**
         * Select a platform so that we can populate the related aircraft fields on signup.
         */

        $( function() {
            $( '#ds-squadron-software-platform-select' ).change( function () {
                var hideables = $('#ds-aircraft-select-options');
                $(this).find("option:selected").each(function(){
                    hideables.empty();
                    var val = $(this).attr("value");
                    if ( val ) {
                        console.log(val);
                        $.ajax({
                            url: ds_ajax.ajax_url,
                            type: 'post',
                            dataType: 'json',
                            data: { action: 'ds_groups_get_types_from_platform', nonce: ds_ajax.nonce, platform: val },
                            beforeSend: function () { // Before we send the request, remove the .hidden class from the spinner and default to inline-block.
                                $('#loader').removeClass('hidden')
                            },
                            success: function( response ) {
                                hideables.append(response.data);
                                //initializeSecondarySelect2( '#ds-squadron-secondary-aircraft' );
                                $('#ds-squadron-secondary-aircraft-select').select2({
                                    multiple: true,
                                    maximumSelectionLength: 3,
                                    placeholder: 'Select additional aircraft...'
                                });
                                //$('#ds-squadron-secondary-aircraft').val(null).trigger("change");
                                hideables.show();
                            },
                            complete: function () { // Set our complete callback, adding the .hidden class and hiding the spinner.
                                $('#loader').addClass('hidden')
                            },
                        })    
                    } else{
                        hideables.hide();
                    }
                });

            }).change();
        });

        if ( $(window).width() < 782 ) {

            $('.bp-subnavs ul').each(function() {

                var $currentURL = $(location).attr('href');

                var $select = $('<select />').attr('id', 'ds-nav-select' );
        
                $(this).find('a').each(function() {
                    var $option = $('<option />');
                    $(this).find('span').remove();
                    $option.attr('value', $(this).attr('href')).html($(this).html());
                    if ( $(this).attr('href') == $currentURL ) {
                        $option.attr('selected','selected');
                    }
                    $option.find('span').css('display', 'none');
                    $select.append($option);
                });

                
        
                $(this).replaceWith($select);
                
            });

            
            $('#ds-nav-select').on("change", function (e) {
                document.location = this.value;
            });
        }

        /**
         * Maintain Group Name formatting.
         */
        $('input#mepr_squadron_name').bind('keydown', function (event) {
            switch (event.keyCode) {
                case 8:  // Backspace
                case 9:  // Tab
                case 13: // Enter
                case 37: // Left
                case 38: // Up
                case 39: // Right
                case 40: // Down
                break;
                default:
                var regex = new RegExp("^[a-zA-Z0-9.,/ ()]+$");
                var key = event.key;
                if (!regex.test(key)) {
                    event.preventDefault();
                    return false;
                }
                break;
            }
    });
});