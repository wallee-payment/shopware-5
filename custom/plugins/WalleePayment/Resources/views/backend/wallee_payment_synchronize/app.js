//{block name="backend/wallee_payment_synchronize/application"}
Ext.define('Shopware.apps.WalleePaymentSynchronize', {
    
    extend: 'Enlight.app.SubApplication',
    
    name: 'Shopware.apps.WalleePaymentSynchronize',
    
    loadPath: '{url action=load}',
    
    controllers: [
        'Synchronize'
    ],
    
    launch: function() {
        var me = this;
        me.getController('Synchronize').directSynchronize();
    }
    
});
//{/block}