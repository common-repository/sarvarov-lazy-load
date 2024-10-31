(function( $ ) {
	
	'use strict';

	$(document).ready(function() {
		
		// wpColorPicker init
		$('.color-picker').wpColorPicker();
		
		// hide loader
		loader_stop();
		
		function loader_start() {
			$('#loader').removeClass('loader-inactive').addClass('loader-inprogress');
			
			loader_stop();
		}
		
		function loader_stop() {
			setTimeout( function() { 
				$('#loader').removeClass('loader-inprogress').addClass('loader-inactive');
			}, 700)
		}

		// admin sections update
		admin_sections_update();
		
		$('.wrap.sarvarov-wrap form :input').change(function() {
			admin_sections_update();
		});
		
		function admin_sections_update() {
			if( !$('input[name="sarvarov_lazy_load[enable_on_images]"]').prop('checked') && !$('input[name="sarvarov_lazy_load[enable_on_iframes]"]').prop('checked')) {
				$('.wrap.sarvarov-wrap form tr:not(:first), .wrap.sarvarov-wrap form .section:not(:first)').hide();
				loader_start();
			} else {
				$('.wrap.sarvarov-wrap form tr, .wrap.sarvarov-wrap form .section').show();
				if($('input[name="sarvarov_lazy_load[enable_on_images]"]').prop('checked')) {
					$('#images_settings').show();
				} else {
					$('#images_settings').hide();
				}
				
				if($('input[name="sarvarov_lazy_load[enable_on_iframes]"]').prop('checked')) {
					$('#iframes_settings').show();
				} else {
					$('#iframes_settings').hide();
				}
				
				if($('input[name="sarvarov_lazy_load[lqip_enable]"]').prop('checked')) {
					$('.lqip-child').parents('tr').show();
				} else {
					$('.lqip-child').parents('tr').hide();
				}
			}
		}
		
		// sticky sidebar
		var $sticky = $('.sidebar-inner');

		$sticky.hcSticky({
			top: 25 + ( $('#wpadminbar').length ? $('#wpadminbar').outerHeight() : 0 ), 
			bottom: 25
		});
	});

})(jQuery);
