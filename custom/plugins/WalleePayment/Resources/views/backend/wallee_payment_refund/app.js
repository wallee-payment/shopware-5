//{block name="backend/wallee_payment_refund/application"}
    //{include file="backend/wallee_payment_index/components/CTemplate.js"}
    //{include file="backend/wallee_payment_index/components/ComponentColumn.js"}

    Ext.define('Shopware.apps.WalleePaymentRefund', {
        
        extend: 'Enlight.app.SubApplication',
        
        name: 'Shopware.apps.WalleePaymentRefund',
        
        loadPath: '{url controller="WalleePaymentRefund" action=load}',
        
        controllers: [
            'Main'
        ],
        
        launch: function() {
            var me = this,
                mainController = me.getController('Main');
            return mainController.mainWindow;
        }
        
    });
//{/block}