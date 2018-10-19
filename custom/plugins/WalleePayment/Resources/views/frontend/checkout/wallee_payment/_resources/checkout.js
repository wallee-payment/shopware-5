/**
 * wallee Shopware
 *
 * This Shopware extension enables to process payments with wallee (https://www.wallee.com/).
 *
 * @package Wallee_Payment
 * @author customweb GmbH (http://www.customweb.com/)
 * @license http://www.apache.org/licenses/LICENSE-2.0  Apache Software License (ASL 2.0)
 */

if (typeof ShopwareWallee == 'undefined') {
    var ShopwareWallee = {};
}

ShopwareWallee.Checkout = {
    handler : null,
    checkoutButtonBackup: null,
    paymentPageUrl: null,
    saveOrderUrl: null,
    blockSubmit: false,

    init : function(container, configurationId, saveOrderUrl, paymentPageUrl) {
    	this.paymentPageUrl = paymentPageUrl + "&paymentMethodConfigurationId=" + configurationId;
    	this.saveOrderUrl = saveOrderUrl;
        this.checkoutButtonBackup = $('button[form="confirm--form"]').html();
        
        this.attachListeners();
        if (typeof window.IframeCheckoutHandler != "undefined") {
        	this.createHandler(container, configurationId);
        }
    },
    
    attachListeners: function(){
        $('#confirm--form').on('submit.wallee_payment', $.proxy(function(event){
            this.onSubmit();
            event.preventDefault();
            return false;
        }, this));
    },
    
    onSubmit: function(){
    	if (this.handler) {
	        if (!this.blockSubmit) {
	        	this.blockSubmit = true;
	        	this.handler.validate();
	    	}
    	} else {
    		this.createOrder($.proxy(function(){
    			window.location.replace(this.paymentPageUrl);
    		}, this), function(){});
    	}
    },

    createHandler : function(container, configurationId) {
        this.blockCheckoutButton();
        if (!this.handler) {
            this.handler = window.IframeCheckoutHandler(configurationId);
            this.handler.setHeightChangeCallback(function(height){
            		if (height > 0) {
            			$('#wallee_payment_method_form_container').css({
            				position: 'static',
            				left: 'auto'
            			});
                }
            });
            this.handler.setEnableSubmitCallback($.proxy(function(){
            	this.enableCheckoutButton();
            }, this));
            this.handler.setDisableSubmitCallback($.proxy(function(){
            	this.disableCheckoutButton();
            }, this));
            this.handler.create(container, $.proxy(function(validationResult) {
            		this.hideErrors();
                if (validationResult.success) {
                	this.createOrder($.proxy(function(){
                		this.handler.submit();
                    }, this), $.proxy(function(){
                    	this.unblockCheckoutButton();
                        this.blockSubmit = false;
                    }, this));
                } else {
                    $(window).scrollTop($('#' + container).offset().top);
            		if (validationResult.errors) {
            			this.showErrors(validationResult.errors);
            		}
                    this.unblockCheckoutButton();
                    this.blockSubmit = false;
                }
            }, this), $.proxy(function() {
                this.unblockCheckoutButton();
            }, this));
        }
    },
    
    createOrder: function(onSuccess, onError){
    	$.ajax({
            url: this.saveOrderUrl,
            data: $('#confirm--form').serializeArray(),
            dataType: 'json',
            method: 'POST',
            success: $.proxy(function(response){
            	if (response.result == 'success') {
            		onSuccess();
            	} else if (response.result == 'error') {
        			if (response.error == 'agbError') {
        				$('label[for="sAGB"]').addClass('has--error');
        				$(window).scrollTop($('label[for="sAGB"]').offset().top);
        				onError();
        			} else {
            			$(window).scrollTop($('#' + container).offset().top);
            			this.showErrors([response.error]);
            			onError();
        			}
        		} else {
        			window.location.reload();
        		}
            }, this),
            error: function(){
        		window.location.reload();
            }
        });
    },
    
    wrap: function(object, functionName, wrapper){
        var originalFunction = $.proxy(object[functionName], object);
        return function(){
            var args = Array.prototype.slice.call(arguments);
            args.unshift(originalFunction);
            return wrapper.apply(object, args);
        };
    },
    
    showErrors: function(errors){
    		var element = $('.wallee-payment-validation-failure-message');
        element.find('.alert--list').html('');
        $.each(errors, function(index, error){
            element.find('.alert--list').append('<li class="list--entry">' + error + '</li>');
        });
        element.show();
        $(window).scrollTop(0);
    },
    
    hideErrors: function(){
    		var element = $('.wallee-payment-validation-failure-message');
    		element.hide();
    },
    
    enableCheckoutButton: function(){
    	var element = $('button[form="confirm--form"]');
    	element.removeAttr('disabled');
    },
    
    disableCheckoutButton: function(){
    	var element = $('button[form="confirm--form"]');
    	element.prop('disabled', true);
    },
    
    blockCheckoutButton: function(){
        var element = $('button[form="confirm--form"]'),
            instance = element.data('plugin_swPreloaderButton'),
            checkFormIsValidBackup = instance.opts.checkFormIsValid;
        
        instance.opts.checkFormIsValid = false;
        instance.onShowPreloader();
        instance.opts.checkFormIsValid = checkFormIsValidBackup;
    },
    
    unblockCheckoutButton: function(){
        var element = $('button[form="confirm--form"]'),
            instance = element.data('plugin_swPreloaderButton');
        window.setTimeout($.proxy(function() {
            element.removeAttr('disabled').find('.' + instance.opts.loaderCls).remove();
            element.html(this.checkoutButtonBackup);
        }, this), 25);
    }
};