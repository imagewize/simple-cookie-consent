/* global warderAdmin */
jQuery( document ).ready( function( $ ) {

	$( '.show-add-cookie-form' ).on( 'click', function() {
		var categoryId  = $( this ).data( 'category' );
		var $container  = $( '#warder-add-cookie-container-' + categoryId );
		// Move the container to appear immediately after the main settings form
		// so it's not nested inside it, then scroll into view.
		$( '#warder-main-settings-form' ).after( $container );
		$container.show();
		$( 'html, body' ).animate( { scrollTop: $container.offset().top - 50 }, 300 );
	} );

	$( '.cancel-add-cookie' ).on( 'click', function( e ) {
		e.preventDefault();
		$( this ).closest( '.warder-add-cookie-form-container' ).hide();
	} );

	$( '#warder-main-settings-form input:not([form]), #warder-main-settings-form textarea:not([form]), #warder-main-settings-form select:not([form])' ).on( 'change', function() {
		$( this ).css( 'background-color', '#ffffdd' );
	} );

	$( '#warder-main-settings-form' ).on( 'submit', function( e ) {
		e.preventDefault();

		var form      = $( this );
		var submitBtn = form.find( 'input[type="submit"]:not([form])' );

		submitBtn.prop( 'disabled', true ).val( warderAdmin.saving );

		$.post(
			warderAdmin.ajaxurl,
			form.serialize() + '&action=warder_save_settings',
			function( response ) {
				$( '.warder-ajax-notice' ).remove();
				var cls     = response.success ? 'notice-success' : 'notice-error';
				var $notice = $( '<div class="notice ' + cls + ' is-dismissible warder-ajax-notice"><p><strong>' + response.data.message + '</strong></p></div>' );
				form.before( $notice );
				$( 'html, body' ).animate( { scrollTop: $notice.offset().top - 50 }, 300 );
				if ( response.success ) {
					form.find( 'input, textarea, select' ).css( 'background-color', '' );
				}
			}
		).always( function() {
			submitBtn.prop( 'disabled', false ).val( warderAdmin.save );
		} );
	} );

} );
