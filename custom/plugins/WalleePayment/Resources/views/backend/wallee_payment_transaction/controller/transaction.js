/**
 * wallee Shopware
 *
 * This Shopware extension enables to process payments with wallee (https://www.wallee.com/).
 *
 * @package Wallee_Payment
 * @author customweb GmbH (http://www.customweb.com/)
 * @license http://www.apache.org/licenses/LICENSE-2.0  Apache Software License (ASL 2.0)
 */

//{namespace name=backend/wallee_payment/main}
//{block name="backend/wallee_payment_transaction/controller/transaction"}
Ext.define('Shopware.apps.WalleePaymentTransaction.controller.Transaction', {
    
    extend:'Ext.app.Controller',
    
    refs: [
        { ref: 'orderList', selector: 'order-list' }
    ],
    
    snippets: {
        message: {
            success: '{s name=line_item/message/success}The line items have been successfully updated.{/s}',
            failure: '{s name=line_item/message/failure}An error has occurred while saving the line items.{/s}',
            voided: '{s name=transaction/message/void_success}The transaction has been voided.{/s}',
            completed: '{s name=transaction/message/complete_success}The transaction has been completed.{/s}',
            denied: '{s name=transaction/message/delivery_indication_deny}The payment has been denied.{/s}',
            accepted: '{s name=transaction/message/delivery_indication_accept}The payment has been accepted.{/s}',
            exception: '{s name=message/error}There has been an exception.{/s}',
            updated: '{s name=transaction/message/update_success}The transaction has been updated.{/s}',
        },
        growlTitle: '{s name=growl_title}wallee Payment{/s}'
    },

    init:function () {
        var me = this;

        me.control({
            'wallee-payment-transaction-details': {
                downloadInvoice: me.onDownloadInvoice,
                downloadPackingSlip: me.onDownloadPackingSlip,
                voidTransaction: me.onVoidTransaction,
                denyDeliveryIndication: me.onDenyDeliveryIndication,
                acceptDeliveryIndication: me.onAcceptDeliveryIndication,
                completeTransaction: me.onCompleteTransaction,
                updateTransaction: me.onUpdateTransaction
            },
            'wallee-payment-transaction-line-items': {
                updateLineItems: me.onUpdateLineItems
            }
        });

        me.callParent(arguments);
    },
    
    onUpdateLineItems: function(record, grid) {
        var me = this,
            store = grid.getStore(),
            window = grid.up('window');
            
        var params = {
            spaceId: record.get('spaceId'),
            transactionId: record.get('transactionId')
        };
        store.each(function(lineItem){
            params['lineItems[' + lineItem.get('uniqueId') + '][quantity]'] = lineItem.updatedQuantity;
            params['lineItems[' + lineItem.get('uniqueId') + '][amount]'] = lineItem.updatedAmount;
        });
        
        window.setLoading(true);
        Ext.Ajax.request({
            url: '{url controller="WalleePaymentTransaction" action="saveLineItem"}',
            params: params,
            success: function(response) {
                var data = Ext.decode(response.responseText);
                if (data.success) {
                    Shopware.Notification.createGrowlMessage(me.snippets.growlTitle, me.snippets.message.success);
                    me.subApplication.getStore('Shopware.apps.WalleePaymentTransaction.store.Transaction').load({
                        params: {
                            transactionId: record.get('id')
                        },
                        callback:function (records) {
                            window.updateRecord(records[0]);
                            window.setLoading(false);
                        }
                    });
                } else {
                    window.updateRecord(record);
                    window.setLoading(false);
                    Shopware.Notification.createGrowlMessage(me.snippets.growlTitle, data.message || me.snippets.message.failure);
                }
            }
        });
    },
    
    onDownloadInvoice: function(record, view) {
        window.open('{url controller="WalleePaymentTransaction" action=downloadInvoice}?id=' + record.get('id'), 'download');
    },
    
    onDownloadPackingSlip: function(record, view) {
        window.open('{url controller="WalleePaymentTransaction" action=downloadPackingSlip}?id=' + record.get('id'), 'download');
    },
    
    onVoidTransaction: function(record, view) {
        var me = this;
    
        if (record.get('canVoid')) {
            me.process(record, view, '{url controller="WalleePaymentTransaction" action="void"}',
                    me.snippets.message.voided, me.snippets.message.exception);
        }
    },
    
    onCompleteTransaction: function(record, view) {
        var me = this;
        
        if (record.get('canComplete')) {
            me.process(record, view, '{url controller="WalleePaymentTransaction" action="complete"}',
                    me.snippets.message.completed, me.snippets.message.exception);
        }
    },
    
    onDenyDeliveryIndication: function(record, view) {
        var me = this;
    
        if (record.get('canReview')) {
            me.process(record, view, '{url controller="WalleePaymentTransaction" action="deny"}',
                    me.snippets.message.denied, me.snippets.message.exception);
        }
    },
    
    onAcceptDeliveryIndication: function(record, view) {
        var me = this;
        
        if (record.get('canReview')) {
            me.process(record, view, '{url controller="WalleePaymentTransaction" action="accept"}',
                    me.snippets.message.accepted, me.snippets.message.exception);
        }
    },
    
    onUpdateTransaction: function(record, view) {
    		var me = this;
    		
    		me.process(record, view, '{url controller="WalleePaymentTransaction" action="update"}',
                    me.snippets.message.updated, me.snippets.message.exception);
    },
    
    process: function(record, view, url, successText, failureText){
        var me = this,
            store = me.subApplication.getStore('Shopware.apps.WalleePaymentTransaction.store.Transaction'),
            window = view.up('window');
        
        window.setLoading(true);
        Ext.Ajax.request({
            url: url,
            params: {
                id: record.get('id')
            },
            success: function(response) {
                var data = Ext.decode(response.responseText),
                    text;
                if (data.success) {
                    store.load({
                        params: {
                            transactionId: record.get('id')
                        },
                        callback:function (records) {
                            window.updateRecord(records[0]);
                            window.setLoading(false);
                        }
                    });
                    text = successText;
                } else {
                    window.updateRecord(record);
                    window.setLoading(false);
                    text = failureText;
                }
                Shopware.Notification.createGrowlMessage(me.snippets.growlTitle, text);
                me.getOrderList().store.load();
            }
        });
    }
    
});
//{/block}