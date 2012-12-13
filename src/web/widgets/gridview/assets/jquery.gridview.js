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
			this.tableId = '#'+$element.attr('id');
			this.table = $element;
			this.config = config;
		},
		
		update: function(options) {
			options = $.extend({
				url: this.config.url
			}, options);
			var that = this;
			$.get(options.url, {ajaxId: this.config.id}, function(result) {
				var $data = $('<div>' + result + '</div>');
				that.table.replaceWith($(that.tableId, $data));
			})
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