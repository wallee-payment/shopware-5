/**
 * Wallee Shopware
 *
 * This Shopware extension enables to process payments with Wallee (https://wallee.com/).
 *
 * @package Wallee_Payment
 * @author customweb GmbH (http://www.customweb.com/)
 * @license http://www.apache.org/licenses/LICENSE-2.0  Apache Software License (ASL 2.0)
 * @link https://github.com/wallee-payment/shopware
 */

if (typeof ShopwareWallee == 'undefined') {
    var ShopwareWallee = {};
}

ShopwareWallee.Checkout = {
    handler : null,
    checkoutButtonBackup: null,

    init : function(container, configurationId, saveOrderUrl) {
        this.checkoutButtonBackup = $('button[form="confirm--form"]').html();
        
        this.attachListeners();
        this.createHandler(container, configurationId, saveOrderUrl);
    },
    
    attachListeners: function(){
        $('#confirm--form').on('submit.wallee_payment', $.proxy(function(event){
            this.onSubmit();
            event.preventDefault();
            return false;
        }, this));
    },
    
    onSubmit: function(){
        this.handler.validate();
    },

    createHandler : function(container, configurationId, saveOrderUrl) {
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
            this.handler.create(container, $.proxy(function(validationResult) {
                if (validationResult.success) {
                    $.ajax({
                        url: saveOrderUrl,
                        data: $('#confirm--form').serializeArray(),
                        dataType: 'json',
                        success: $.proxy(function(response){
                        		if (response.result == 'success') {
                        			this.handler.submit();
                        		} else {
                        			window.location.reload();
                        		}
                        }, this),
                        error: function(){
                        		window.location.reload();
                        }
                    })
                } else {
                    this.unblockCheckoutButton();
                }
            }, this), $.proxy(function() {
                this.unblockCheckoutButton();
            }, this));
        }
    },
    
    wrap: function(object, functionName, wrapper){
        var originalFunction = $.proxy(object[functionName], object);
        return function(){
            var args = arguments;
            args.unshift(originalFunction);
            return wrapper.apply(object, args);
        };
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