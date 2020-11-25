/**
 * wallee Shopware 5
 *
 * This Shopware 5 extension enables to process payments with wallee (https://www.wallee.com/).
 *
 * @package Wallee_Payment
 * @author wallee AG (http://www.wallee.com/)
 * @license http://www.apache.org/licenses/LICENSE-2.0  Apache Software License (ASL 2.0)
 */

//{block name="backend/wallee_payment_transaction/view/transaction/transaction"}
//{namespace name=backend/wallee_payment/main}
Ext.define('Shopware.apps.WalleePaymentTransaction.view.transaction.Transaction', {
    
    extend: 'Ext.tab.Panel',
    
    alias: 'widget.wallee-payment-transaction-transaction',

    autoScroll: true,
    
    border: 0,
    
    bodyBorder: false,
    
    activeTab: 0,
    
    snippets: {
        tab: {
            details: '{s name=transaction/tab/details}Details{/s}',
            lineItems: '{s name=transaction/tab/line_items}Line Items{/s}',
            refunds: '{s name=transaction/tab/refunds}Refunds{/s}'
        }
    },
            
    initComponent: function() {
        var me = this;
          
        me.callParent(arguments);
        
        if (me.record) {
        	me.createItems();
        }
    },
    
    setRecord: function(record) {
    	var me = this;
    	
    	me.removeAll();
		if (record) {
			me.record = record;
			me.createItems();
		}
    },
    
    updateRecord: function(record, callback) {
    	var me = this,
			activeIndex = me.items.findIndex('id', me.getActiveTab().getId());
	
    	me.setRecord(record);
		if (record) {
			me.setActiveTab(activeIndex);
		}
    },
    
    createItems: function(){
    	var me = this;

    	var infoTab = Ext.create('Shopware.apps.WalleePaymentTransaction.view.transaction.Details', {
    		title: me.snippets.tab.details,
    		record: me.record
    	});
    	me.add(infoTab);

    	if (me.record.getLineItems().count() > 0) {
        	var lineItemTab = Ext.create('Shopware.apps.WalleePaymentTransaction.view.transaction.LineItems', {
                title: me.snippets.tab.lineItems,
                record: me.record
            });
            me.add(lineItemTab);
    	}
    	
    	if (me.record.getRefunds().count() > 0 || me.record.get('canRefund')) {
            var refundTab = Ext.create('Shopware.apps.WalleePaymentTransaction.view.transaction.Refunds', {
                title: me.snippets.tab.refunds,
                record: me.record
            });
            me.add(refundTab);
        }
    	
    	me.doLayout();
    	me.setActiveTab(0);
    }
    
});
//{/block}