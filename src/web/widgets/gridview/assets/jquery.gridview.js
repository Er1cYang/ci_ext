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
			ajaxUpdater = '.pager a, thead a';
			this.element.find(ajaxUpdater).live('click', function() {
				that.update({url: this.href})
				return false;
			});
		},
		
		getSelectedValue: function() {
			var values = this.getSelectedValues();
			return values.length>0?values[0]:null;
		},
		
		getSelectedValues: function() {
			var values = new Array();
			var $checkeds = this.element.find('table tbody :checked').each(function(i, e) {
				values.push(e.value);
			});
			return values;
		},
		
		getRow: function(index) {
			var $rows = this.getRows();
			return $rows.eq(index);
		},
		
		getRows: function() {
			return this.element.find('tbody tr');
		},
		
		update: function(options) {
			var that = this;
			options = $.extend({
				url: this.config.url,
				type: 'GET',
				success: function(data) {
					var $data = $('<div>' + data + '</div>');
					that.element.html($(that.id, $data).html());
				},
				error: function (XHR, textStatus, errorThrown) {
					var err;
					switch (textStatus) {
						case 'timeout':
							err = 'The request timed out!';
							break;
						case 'parsererror':
							err = 'Parser error!';
							break;
						case 'error':
							if (XHR.status && !/^\s*$/.test(XHR.status)) {
								err = 'Error ' + XHR.status;
							} else {
								err = 'Error';
							}
							if (XHR.responseText && !/^\s*$/.test(XHR.responseText)) {
								err = err + ': ' + XHR.responseText;
							}
							break;
					}
					alert(err);
				}
			}, options);
			$.ajax(options, options || {});
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