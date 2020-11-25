/**
 * wallee Shopware 5
 *
 * This Shopware 5 extension enables to process payments with wallee (https://www.wallee.com/).
 *
 * @package Wallee_Payment
 * @author wallee AG (http://www.wallee.com/)
 * @license http://www.apache.org/licenses/LICENSE-2.0  Apache Software License (ASL 2.0)
 */

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