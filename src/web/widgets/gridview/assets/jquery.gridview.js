;(function($){
	
	var cieGridview = function($element, config) {
		config = config || {};
		defaults = cieGridview.defaultConfig;
		if(!config.id) {
			config.id = $element.attr('id');
		}
		// 合并默认配置
	    for (var i in defaults) {
	        if (config[i] === undefined) {
	            config[i] = defaults[i];
	        };
	    };
		
		return cieGridview.list[config.id] = new cieGridview.fn.constructor($element, config);
	}
	
	cieGridview.list = {};
	$.cieGridview = cieGridview;
	
	cieGridview.fn = cieGridview.prototype = {
		constructor: function($element, config) {
			this.id = '#'+$element.attr('id');
			this.element = $element;
			this.config = config;
			this.init();
		},
		
		init: function() {
			var that = this;
			this.element.find('.pager a').live('click', function() {
				that.update({url: this.href})
				return false;
			});
		},
		
		update: function(options) {
			if(options) {
				this.config = $.extend(this.config, options);
			} else {
				options = this.config;
			}
			var that = this;
			$.get(options.url, function(result) {
				var $data = $('<div>' + result + '</div>');
				that.element.html($(that.id, $data).html());
			});
		}
	}
	
	cieGridview.fn.constructor.prototype = cieGridview.fn;
	$.fn.cieGridview = function(config) {
		return cieGridview(this, config);
	}
	
	$.cieGridview.get = function(id) {
		if(id == null) {
			return cieGridview.list;
		} else {
			return cieGridview.list[id] ? cieGridview.list[id] : null;
		}
	}
	
	cieGridview.defaultConfig = {
		url: ''
	}
	
})(jQuery);