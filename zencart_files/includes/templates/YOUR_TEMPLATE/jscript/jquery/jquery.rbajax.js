//
// create closure
//
(function($) {
  //
  // plugin definition
  // 

  $.fn.rbajax = function(options) {  	
	var $selector = $(this);
   	// build main options before element iteration
   	var opts = jQuery.extend({
   	type: '',
   	url: '',	
   	iframe: false,
   	loadCss: false,
   	loadJs: false,
	messageTarget: 'output',
    data: {isajaxrequest: '1'},
    validateOptions: {rules: {}, messages: {}},
    beforeSubmit: '',
    beforeSuccess: '',
    afterSuccess: '',
    beforeError: '',
    afterError: '',
    complete: ''
  	}, options);		

	$('#'+opts.messageTarget).attr('tabindex', -1);
	$.fn.displayMessage = function (msg, opts){
		$('#'+opts.messageTarget).focus().html(msg);
  	};
	  
	var resetMessage = function (opts){
		$('#'+opts.messageTarget).html('');
	};

	var updateContent = function(response, status, opts){
		if(response !== null){
			
			if(status === undefined || status != 'success'){
				$.fn.displayMessage(response, opts);
				return;
			}
	
			if (response.message !== undefined) 
				$.fn.displayMessage(response.message, opts);
			else
				resetMessage(opts);
				
			switch(response.status){
				case 'success':
				case 'error':
					// load css
					//$.requireConfig.routeCss = '<?php echo DIR_WS_TEMPLATE;?>css/';
					if(opts.loadCss && response.load !== undefined){
						jQuery.each(response.load['css'], function(i, val) {
							if(val !== undefined) 
			      	$.include(val);
			  		});	
					}
		  			  		
					// update the content
					if(response.content !== undefined && response.content !== null){
						jQuery.each(response.content, function(i, val) {
							if(val != '') 
			      	$("#" + i).html(val);
			  		});			
					}
									
		  		if(response.action !== undefined){
	//					switch(response.action.type){
	//						case 'autosubmit':
	//							$(selector + ' .step[title='+currentIndex+'] ' + response.action.id).ajaxSubmit({ 
	//							  success: function(data, textStatus){
	//			         	updateContent(data, textStatus, opts);
	//			        	},
	//							  dataType:  'json',
	//								error: function(xhr) {
	//									$.fn.displayMessage('Error: ' + xhr.status + ' ' + xhr.statusText + ' ' + xhr.responseText, opts);
	//								}
	//							 });
	//							$(selector + ' .step[title='+currentIndex+'] .loader').fadeIn();
	//						break;
	//					}
					}
					else if(opts.loadJs){			
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
					break;
					
				case 'redirect':
					var url = urldecode(response.url);
					if($.fn.rbajax.redirect[response.redirect_type] !== undefined)
						$.fn.rbajax.redirect[response.redirect_type](url, updateContent, opts);
					else
						$.fn.rbajax.redirect.defaultRedirect(url, updateContent, opts); 
					break;
				default:
				break;
			}
		}
	};
	   
	/*`
	 * End of function and var definition
	 * Begin binding
	 */		
	var elementType = opts.type == '' ? $selector.attr('tagName') : opts.type;
	
	if(elementType=='A'){
		// binding to link
		var data = jQuery.extend(opts.data, {'ajax_skip_cj': (!opts.loadCss && !opts.loadJs) ? '1' : '0' });
		
		$selector.live('click', function(e){
			if( (!$.browser.msie && e.button == 0) || ($.browser.msie && e.button == 0)) {
				var element = this;
				if(opts.beforeSubmit != '') opts.beforeSubmit(element, opts);
				$.ajax({
	  		    type: "GET",
				data: opts.data,
				url: opts.url != '' ? opts.url : $(this).attr('href'),
				dataType: 'json',
				success: function(data, textStatus, jqXHR){
								if ($.isFunction(opts.beforeSuccess))
									opts.beforeSuccess(element,data, textStatus, jqXHR);
			         	
								updateContent(data, textStatus, opts);

		         	if ($.isFunction(opts.afterSuccess))
									opts.afterSuccess(element, data, textStatus, jqXHR);
			        	},
                error: function(jqXHR, textStatus, errorThrown) {
			  				if ($.isFunction(opts.beforeError))
	  							opts.beforeError(element, jqXHR, textStatus, errorThrown);
	  							
								$.fn.displayMessage('Error: ' + jqXHR.status + ' ' + jqXHR.statusText + ' ' + jqXHR.errorThrown, opts);
								
								if ($.isFunction(opts.afterError))
	  							opts.afterError(element, jqXHR, textStatus, errorThrown);
								},
			    complete: function(jqXHR, textStatus) {
			    	        if ($.isFunction(opts.complete))
			    	        	opts.complete(element, jqXHR, textStatus);
			    }
				});
				e.preventDefault();
			}
		}); 
	}
	
	else if(elementType == 'FORM'){	
		// binding to forms	
		var data = jQuery.extend(opts.data, {iframe: opts.iframe ? '1' : '0', 'ajax_skip_cj': (!opts.loadCss && !opts.loadJs) ? '1' : '0' });
		
	 	$selector.live("submit", function(){
	 		//if($(this).validate({rules: opts.validateOptions.rules,messages: opts.validateOptions.messages}).form())
	 		var element = this;
	 		if(opts.beforeSubmit != '') opts.beforeSubmit(element, opts);
			$(this).ajaxSubmit({ 
		    dataType:  'json',
			success: function(data, textStatus, jqXHR){
			  	if ($.isFunction(opts.beforeSuccess))
							opts.beforeSuccess(element, data, textStatus, jqXHR);
	         	
						updateContent(data, textStatus, opts);
	         	
	         	if ($.isFunction(opts.afterSuccess))
							opts.afterSuccess(element, data, textStatus, jqXHR);
    		  },
			error: function(jqXHR, textStatus, errorThrown) {
					if ($.isFunction(opts.beforeError))
							opts.beforeError(element, jqXHR, textStatus, errorThrown);
							
					$.fn.displayMessage('Error: ' + jqXHR.status + ' ' + jqXHR.statusText + ' ' + jqXHR.errorThrown, opts);
						
						if ($.isFunction(opts.afterError))
							opts.afterError(element, jqXHR, textStatus, errorThrown);
			},
			complete: function(jqXHR, textStatus) {
    	        if ($.isFunction(opts.complete))
    	        	opts.complete(element, jqXHR, textStatus);
            },
			data: data,
			iframe: opts.iframe,
			url: opts.url != '' ? opts.url : $(element).attr('action')
			});
			return false;
  	});
	}
  };
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
  $.fn.rbajax.redirect.defaultRedirect = function(redirect_url, updateContent, opts) {
  	$.ajax({
  		type: "GET",
			url: redirect_url,
			dataType: 'json',
			success: function(data, textStatus){
			         	updateContent(data, textStatus, opts);
			        },
			error: function(xhr) {
									$.fn.displayMessage('Error: ' + xhr.status + ' ' + xhr.statusText + ' ' + xhr.responseText, opts);
								}
  	});
		return;
  };
  
  $.fn.rbajax.redirect.forceRedirect = function(redirect_url, updateContent, opts) {
  	window.location=redirect_url;
  	return;
  };
  
  $.fn.rbajax.redirect.noRedirect = function(redirect_url, updateContent, opts) {
		return;
  };
})(jQuery);