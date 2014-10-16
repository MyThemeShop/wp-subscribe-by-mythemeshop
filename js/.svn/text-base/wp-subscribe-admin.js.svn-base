/*
Plugin Name: WP Subscribe
Plugin URI: http://mythemeshop.com/plugins/wp-subscribe/
Author: MyThemeShop
Author URI: http://mythemeshop.com
*/
( function( $ ){

	// color picker
	function initColorPicker( widget ) {
		// and services dropdown
		widget.find('.wp-subscribe-service-field select').change(function(event) {
	        var $this = $(this);
	        widget.find('.wp_subscribe_account_details_'+$this.val()).show().siblings('div').hide();
	        widget.find('.wp_subscribe_account_details').slideDown();
	    }).trigger('change');
	}

	function onFormUpdate( event, widget ) {
		initColorPicker( widget );
	}
	
	$( document ).on( 'widget-added widget-updated', onFormUpdate );

	$( document ).ready( function() {
		$( '#widgets-right .widget:has(.wp-subscribe-service-field select)' ).each( function () {
			initColorPicker( $( this ) );
		});
	} );

	// slideToggle
	$(document).on('click', function(e) {
	    var $this = jQuery(e.target);
	    var $widget = $this.closest('.wp_subscribe_options_form');
	    if ($widget.length) {
	        if ($this.is('.wp-subscribe-toggle')) {
	            e.preventDefault();
	            var $related = $widget.find('.'+$this.attr('rel'));
	            $related.slideToggle();
	        }
	    }
	});
}( jQuery ) );