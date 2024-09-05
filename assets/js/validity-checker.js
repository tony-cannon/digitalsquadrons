jQuery( document ).ready( function() {

    var jq = jQuery;
    
    if ( !jq( _dsValidityChecker.selectors ).val() ) {
        jq(':input[type=submit]').prop('disabled', true);
    }
	
    jq( document ).on( 'blur', _dsValidityChecker.selectors, function() {
		var $wrapper = jq( this ).parent('.validityChecker');

        if( ! $wrapper.get(0) ) {
			$wrapper = createWrapper( this );
        }
        
        jq( '.name-info', $wrapper ).empty();//hhide the message
		//show loading icon
		jq( '.loading', $wrapper ).css( {display:'block'} );
		
        var checkValue = jq( this ).val();
        var checkType = jq( '#checkType' ).val();

        jq.post( ajaxurl, {
			action: 'check_validity',
			cookie: encodeURIComponent(document.cookie),
            check_value: checkValue,
            check_type: checkType
			},
			function( resp ) {
				
				if( resp && resp.code != undefined && resp.code == 'success' ) {
						showMessage( $wrapper, resp.message, 0 );
                        jq( _dsValidityChecker.selectors ).removeClass('invalid');
						jq(':input[type=submit]').prop('disabled', false);
				} else {
					showMessage( $wrapper, resp.message, 1 );
                    jq( _dsValidityChecker.selectors ).addClass('invalid');
					//add error to form to not submit
					jq(':input[type=submit]').prop('disabled', true);
				}
				},
			'json'	
		);

    })//end on blur

    function showMessage( $wrapper, msg, is_error ) {
		jq( '.name-info', $wrapper ).removeClass('available error');
		
		jq( '.loading', $wrapper ).css( {display:'none'} );
		
		jq( '.name-info', $wrapper ).html( msg );
      
		if( is_error ) {
			jq( '.name-info', $wrapper ).addClass( 'error' );
		} else {
			jq( '.name-info', $wrapper ).addClass( 'available' );
		}
    }

    function createWrapper( element ) {

        var $wrapper = jq( element ).parent('.validityChecker');
		
		if( ! $wrapper.get(0) ) {
			
			jq( element ).wrap( "<div id='validityChecker' class='validityChecker'></div>" );
			
			$wrapper = jq( element ).parent('.validityChecker');
			$wrapper.append( "<span class='loading' style='display:none'></span>" );
			$wrapper.append( "<span class='name-info'></span>" );
		}
		
		return $wrapper;
    }

});//end dom ready