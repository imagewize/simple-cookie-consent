/* global warderAdmin */
jQuery( document ).ready( function( $ ) {

	$( '.show-add-cookie-form' ).on( 'click', function() {
		var categoryId = $( this ).data( 'category' );
		$( '#warder-add-cookie-container-' + categoryId ).show();
	} );

	$( '.cancel-add-cookie' ).on( 'click', function( e ) {
		e.preventDefault();
		$( this ).closest( '.warder-add-cookie-form-container' ).hide();
	} );

	$( '#warder-main-settings-form input, #warder-main-settings-form textarea, #warder-main-settings-form select' ).on( 'change', function() {
		$( this ).css( 'background-color', '#ffffdd' );
	} );

	$( '#warder-main-settings-form' ).on( 'submit', function( e ) {
		if ( e.originalEvent && e.originalEvent.submitter && e.originalEvent.submitter.name === 'warder_add_cookie' ) {
			return;
		}
		e.preventDefault();

		var form      = $( this );
		var submitBtn = form.find( 'input[type="submit"]' );

		submitBtn.prop( 'disabled', true ).val( warderAdmin.saving );

		$.post(
			warderAdmin.ajaxurl,
			form.serialize() + '&action=warder_save_settings',
			function( response ) {
				$( '.warder-ajax-notice' ).remove();
				var cls = response.success ? 'notice-success' : 'notice-error';
				form.before(
					'<div class="notice ' + cls + ' is-dismissible warder-ajax-notice">' +
					'<p><strong>' + response.data.message + '</strong></p></div>'
				);
				if ( response.success ) {
					form.find( 'input, textarea, select' ).css( 'background-color', '' );
				}
			}
		).always( function() {
			submitBtn.prop( 'disabled', false ).val( warderAdmin.save );
		} );
	} );

} );
