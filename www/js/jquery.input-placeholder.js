(function (jQuery) {

    jQuery.fn.inputDefault = function(options) {

		var defaults = {
		/**
		 * default text on input
		 */	
    		defaultText: '',
    		/**
    		 * if default text text is not specified in defaultText option, it is found in the defined input attribute
    		 */
    		defaultTextAttr: "placeholder",
    		
    		/**
    		 * prefix for all classNames
    		 */
    		classPrefix: "jquery-input-default-"
    	};

		defaults = jQuery.extend(defaults, options);		
		
		jQuery(this).each(function() {

			var field = jQuery(this);
			var label = null;
			var wrapper = null;
			
			init();
			
			function init() {
	
				var defaultText = '';
				
				if (defaults.defaultText === ''){
					var placeholder = field.attr(defaults.defaultTextAttr);
					if (placeholder !== ''){
						defaultText = placeholder;
					} else {
						return false;
					}		
				} else {
					defaultText = defaults.defaultText;
				}
	
				field.removeAttr('placeholder');
	
				wrapper = jQuery('<div>');
				wrapper.addClass(createClassName('wrapper'))
						.addClass(field.is('textarea') ? createClassName('textarea') : createClassName('input'));	
				
				label = jQuery('<div>');
				label.addClass(createClassName('placeholder'));
				label.html(defaultText);
				
				field.before(wrapper);
				wrapper.append(label).append(field);
				
				field.bind('focus.inputDefault blur.inputDefault input.inputDefault propertychange.inputDefault', eventHandler)
					.trigger('input.inputDefault');
				
				return true;
			}
	
			function eventHandler() {
				if (field.val() === ''){
					wrapper.removeClass(createClassName('filled'));
				} else {
					wrapper.addClass(createClassName('filled'));	
				}	
	
				if (field.get(0) === document.activeElement){
					wrapper.removeClass(createClassName('blured')).addClass(createClassName('focused'));
				} else {
					wrapper.removeClass(createClassName('focused')).addClass(createClassName('blured'));
				}			
				
				//for ie7
				label.width(field.width());
				label.css('width', 'auto');				
				
				return true;
			}
			
			function createClassName($className){
				return defaults.classPrefix + $className;
			}
			
		});	

		return this;
	}
})(jQuery);
