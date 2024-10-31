(function($) {
	
	'use strict';
	
	var plugin_prefix = 'sarvarov';
	
	window.lazySizesConfig = window.lazySizesConfig || {}; 
	window.lazySizesConfig.lazyClass = plugin_prefix + '-not-lazyloaded';
	window.lazySizesConfig.loadingClass  = plugin_prefix + '-lazyloading';
	window.lazySizesConfig.loadedClass = plugin_prefix + '-lazyloaded';
	window.lazySizesConfig.preloadClass = plugin_prefix + '-lazypreload';
	window.lazySizesConfig.srcAttr = 'data-' + plugin_prefix + '-src';
	window.lazySizesConfig.srcsetAttr = 'data-' + plugin_prefix + '-srcset';
	window.lazySizesConfig.sizesAttr = 'data-' + plugin_prefix + '-sizes';
	window.lazySizesConfig.loadMode = 2;

	var transitionEvent = whichTransitionEvent();
	
	$(document).on('lazybeforeunveil', function(e) {
		
		var tg = $(e.target), 
			tagName = tg.prop('tagName').toLowerCase();
			
		tg.trigger('sarvarov_lazy_load_before_load', [tagName, e.target]);
		
		tg.on('load', function() {
			tg.siblings('.sarvarov-lazylqip').addClass('is-anim');
		
			if(transitionEvent) {
				tg.one(transitionEvent, function(){ 
					animation_complete(tg);
				});
			}
			
			remove_data(tg);

			tg.trigger('sarvarov_lazy_load_after_load', [tagName, e.target]);
		});
		
	});
	
	function remove_data(e) {
		var data_to_remove = [
			'data-' + plugin_prefix + '-src', 
			'data-' + plugin_prefix + '-srcset', 
			'data-' + plugin_prefix + '-sizes'	
		];
		
		data_to_remove.forEach(function(i) {
			$(e).removeAttr(i);
		});
	}
	
	function animation_complete(e) {
		e.parent().addClass('animation-complete');	
		e.siblings('.sarvarov-lazylqip').remove();
	}
	
	function whichTransitionEvent(){
	  var t,
		  el = document.createElement('fakeelement');

	  var transitions = {
		"transition"      : "transitionend",
		"OTransition"     : "oTransitionEnd",
		"MozTransition"   : "transitionend",
		"WebkitTransition": "webkitTransitionEnd"
	  }

	  for (t in transitions){
		if (el.style[t] !== undefined){
		  return transitions[t];
		}
	  }
	}

})( jQuery );