//
// create closure
//
(function($) {
	//
	// plugin definition
	// 

	var methods = {
		displayMessage: function(msg, target)
		{
			$('#'+target).focus().html(msg);
		},
		resetMessage: function(target)
		{
			$('#'+target).html('');
		},
		updateContent: function(response)
		{
			if(response !== null){			
				// update the content
				if(response.content !== undefined && response.content !== null){
					jQuery.each(response.content, function(i, val) {
						if(val !== '') 
							$("#" + i).html(val);
					});			
				}
			}
		},
		loadCssJs: function (response, opts){
			// load css
			//$.requireConfig.routeCss = '<?php echo DIR_WS_TEMPLATE;?>css/';
			if(opts.loadCss && response.load !== undefined){
				jQuery.each(response.load['css'], function(i, val) {
					if(val !== undefined) 
						$.include(val);
				});		
			}
			
			if(opts.loadJs){			
				// load external js files
				if(response.load !== undefined){
					jQuery.each(response.load['jscript'], function(i, val) {
						if(val !== undefined) 
							$.include(val);
		  		});	
		  		
		  		// inline js
		  		$('#'+opts.messageTarget).append(response.load['jscript_inline']);
		  		
		  		// onload js
		  		if(response.load['jscript_onload'].length > 0)
		  			eval(response.load['jscript_onload']);	
				}
			}
		},
		redirect: function (response, opts)
		{
			var url = urldecode(response.url);
			if($.fn.rbajax.redirect[response.redirect_type] !== undefined)
				$.fn.rbajax.redirect[response.redirect_type](url);
			else
				$.fn.rbajax.redirect.defaultRedirect(url,opts);
		}
	}
	

	function destroy(){
		return this.each(function(){
	        var $this = $(this),
	            data = $this.data('rbajax');
	
			// Namespacing FTW
			$(window).unbind('.rbajax');
			data.rbajax.remove();
			$this.removeData('rbajax');
		})
	}
	
	function bindLink(e2, opts) 
	{
		$.extend(opts.ajax, {
			url : opts.ajax.url != '' ? opts.ajax.url : e2.attr('src')
		});
		
		e2.bind('click', function(e){
			if( (!$.browser.msie && e.button == 0) || ($.browser.msie && e.button == 0)) {
				$.ajax(opts.ajax);
				e.preventDefault();
			}
		});
	}
	
	function bindForm (e2, opts)
	{
		// binding to forms			
	 	e2.bind("submit", function(){
	 		var element = this;
			e2.ajaxSubmit(opts.ajax);
			e.preventDefault();
	 	});
	}
	
	function ajax(opts)
	{
		$.ajax(opts.ajax);
	}
	
	$.fn.rbajax = function(options) { 
		var settings = {
			   	type: '',
			   	iframe: false,
			   	loadCss: false,
			   	loadJs: false,
				messageTarget: 'output',
				ajax: {
					data: {isajaxrequest: '1'},
					dataType: 'json'
				},
			    validateOptions: {rules: {}, messages: {}}
			  	};

		if ( options ) { 
			$.extend(true, settings, options);		
		}

		// set the default success callback
		if(settings.ajax.success === undefined)
			settings.ajax.success = function(data, textStatus, jqXHR){
				if(data.message !== undefined)
				methods.displayMessage.apply(this, [data.message, settings.messageTarget]);
				else methods.resetMessage.apply(this, [settings.messageTarget]);
				
				methods.updateContent.apply(this, [data]);
				
				if(data.status == 'redirect') methods.redirect.apply(this, [data, settings]);
			}
		
		// set the default error callback
		if(settings.ajax.error === undefined)
			settings.ajax.error = function(jqXHR, textStatus, errorThrown) {
			methods.displayMessage.apply(this, ['Error: ' + jqXHR.status + ' ' + jqXHR.statusText + ' ' + jqXHR.errorThrown, settings.messageTarget]);
			}
		
		$('#'+settings.messageTarget).attr('tabindex', -1);
		
		//
		if(!settings.loadCss && !settings.loadJs) $.extend(settings.ajax.data, {'ajax_skip_cj' : 1});
		
		if(this.length == 0)
		{
			ajax(settings);
		}
		else
		{
			return this.each(function(){
				$this = $(this);
				var elementType = settings.type == '' ? $this.attr('tagName') : settings.type;
				switch(elementType)
				{
				case 'A':
					bindLink($this, settings);
					break;
				case 'FORM':
					bindForm($this, settings);
					break;
				}
				return $this;	
			});
		}
	};  
	
	$.fn.rbajax.public = function (method, $args)
	{	
		if ( methods[method] ) {
	      return methods[method].apply( this, Array.prototype.slice.call( arguments, 1 ));
	    } 
	      $.error( 'Method ' +  method + ' does not exist on jQuery.rbajax' );   
	}
	
	/*`
	 * End of function and var definition
	 * Begin binding
	 */		
	
  $.fn.rbajax.redirect = [];
  
  //
  // private function for debugging
  //
  function debug($obj) {
	if (window.console && window.console.log)
	  window.console.log('rbajax selection count: ' + $obj.size());
  };
	
	function urldecode( str ) {
    // http://kevin.vanzonneveld.net
    // +   original by: Philip Peterson
    // +   improved by: Kevin van Zonneveld (http://kevin.vanzonneveld.net)
    // +      input by: AJ
    // +   improved by: Kevin van Zonneveld (http://kevin.vanzonneveld.net)
    // +   improved by: Brett Zamir
    // %          note: info on what encoding functions to use from: http://xkr.us/articles/javascript/encode-compare/
    // *     example 1: urldecode('Kevin+van+Zonneveld%21');
    // *     returns 1: 'Kevin van Zonneveld!'
    // *     example 2: urldecode('http%3A%2F%2Fkevin.vanzonneveld.net%2F');
    // *     returns 2: 'http://kevin.vanzonneveld.net/'
    // *     example 3: urldecode('http%3A%2F%2Fwww.google.nl%2Fsearch%3Fq%3Dphp.js%26ie%3Dutf-8%26oe%3Dutf-8%26aq%3Dt%26rls%3Dcom.ubuntu%3Aen-US%3Aunofficial%26client%3Dfirefox-a');
    // *     returns 3: 'http://www.google.nl/search?q=php.js&ie=utf-8&oe=utf-8&aq=t&rls=com.ubuntu:en-US:unofficial&client=firefox-a'
    
    var histogram = {};
    var ret = str.toString();
    
    var replacer = function(search, replace, str) {
        var tmp_arr = [];
        tmp_arr = str.split(search);
        return tmp_arr.join(replace);
    };
    
    // The histogram is identical to the one in urlencode.
    histogram["'"]   = '%27';
    histogram['(']   = '%28';
    histogram[')']   = '%29';
    histogram['*']   = '%2A';
    histogram['~']   = '%7E';
    histogram['!']   = '%21';
    histogram['%20'] = '+';
    histogram['&'] = '&amp;';
 
    for (replace in histogram) {
        search = histogram[replace]; // Switch order when decoding
        ret = replacer(search, replace, ret); // Custom replace. No regexing   
    }
    
    // End with decodeURIComponent, which most resembles PHP's encoding functions
    ret = decodeURIComponent(ret);
 
    return ret;
}

  //
  // the default redirection method
  //
  $.fn.rbajax.redirect.defaultRedirect = function(url, opts) {
	opts.ajax.url = url;
  	ajax(opts);
  };
  
  $.fn.rbajax.redirect.forceRedirect = function(redirect_url, updateContent, opts) {
  	window.location=redirect_url;
  	return;
  };
  
  $.fn.rbajax.redirect.noRedirect = function(redirect_url, updateContent, opts) {
		return;
  };
})(jQuery);