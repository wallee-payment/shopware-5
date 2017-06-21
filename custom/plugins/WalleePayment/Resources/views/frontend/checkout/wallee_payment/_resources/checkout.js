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
            this.handler.create(container, $.proxy(function(validationResult) {
                if (validationResult.success) {
                    $.ajax({
                        url: saveOrderUrl,
                        data: $('#confirm--form').serializeArray(),
                        success: $.proxy(function(){
                            this.handler.submit();
                        }, this)
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