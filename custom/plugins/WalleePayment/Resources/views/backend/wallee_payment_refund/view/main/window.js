/**
 * wallee Shopware 5
 *
 * This Shopware 5 extension enables to process payments with wallee (https://www.wallee.com/).
 *
 * @package Wallee_Payment
 * @author wallee AG (http://www.wallee.com/)
 * @license http://www.apache.org/licenses/LICENSE-2.0  Apache Software License (ASL 2.0)
 */

//{block name="backend/wallee_payment_refund/view/main/window"}
//{namespace name=backend/wallee_payment/main}
Ext.define('Shopware.apps.WalleePaymentRefund.view.main.Window', {
    
    extend: 'Enlight.app.Window',
    
    title: '{s name=refund/window/title}Create Refund{/s}',
    
    alias: 'widget.wallee-payment-refund-main-window',
    
    border: false,
    
    autoShow: true,
    
    layout: 'fit',
    
    height: 400,
    
    width: 800,
    
    snippets: {
        field: {
            totalTaxAmount: '{s name=refund/total_tax_amount}Refund Taxes{/s}',
            totalRefundAmount: '{s name=refund/total_amount}Total Refund Amount{/s}',
        },
        button: {
            cancel: '{s name=button/cancel}Cancel{/s}',
            create: '{s name=button/create}Create{/s}'
        }
    },
    
    initComponent: function() {
        var me = this;

        me.items = me.createItems();
        me.buttons = me.createActionButtons();

        me.callParent(arguments);
    },
    
    createItems: function() {
        var me = this;
        
        var items = [
            Ext.create('Ext.panel.Panel', {
                layout: 'border',
                items: [ me.createForm(), me.createTotals() ]
            })
        ];
        
        return items;
    },
    
    createForm: function() {
        var me = this;
        
        var form = Ext.create('Shopware.apps.WalleePaymentRefund.view.main.Form', {
            region: 'center',
            record: me.record,
            store: me.record.getRefundBaseLineItems(),
            style: {
                borderTop: '1px solid #A4B5C0'
            }
        });
        
        return form;
    },
    
    createTotals: function() {
        var me = parent = this;
        
        var totals = Ext.create('Ext.panel.Panel', {
            region: 'south',
            layout: 'hbox',
            bodyPadding: 10,
            border: false,
            cls: 'shopware-form',
            items: [{
                xtype: 'component',
                flex: 1
            }, {
                xtype: 'container',
                layout: 'anchor',
                defaults: {
                    anchor:     '100%',
                    xtype:      'displayfield',
                    labelWidth: 155,
                    labelAlign: 'right',
                    width:      250,
                    fieldStyle: 'margin-top: 5px; overflow-wrap: break-word; word-wrap: break-word; text-align: right;'
                },
                items: [{
                    fieldLabel: me.snippets.field.totalTaxAmount,
                    value: 0,
                    record: me.record,
                    renderer: me.amountRenderer,
                    initComponent: function(){
                        var me = this;
                        me.callParent(arguments);
                        me.record.on('updateReductions', function(){
                            me.setValue(parent.calculateTaxAmount());
                        });
                        me.setValue(0);
                    }
                }, {
                    fieldLabel: me.snippets.field.totalRefundAmount,
                    value: 0,
                    record: me.record,
                    renderer: me.amountRenderer,
                    initComponent: function(){
                        var me = this;
                        me.callParent(arguments);
                        me.record.on('updateReductions', function(){
                            me.setValue(parent.calculateTotalAmount());
                        });
                        me.setValue(0);
                    }
                }]
            }]
        });
        
        return totals;
    },
    
    calculateTaxAmount: function(){
        var me = this;
        
        var totalAmount = 0;
        me.record.getRefundBaseLineItems().each(function(lineItem) {
            totalAmount += lineItem.get('amountIncludingTax') * lineItem.get('taxRate') / 100;
        });
        
        var reducedTotalAmount = 0; 
        me.record.getRefundBaseLineItems().each(function(lineItem) {
            var quantity = lineItem.get('quantity') - lineItem.quantityReduction,
                unitPrice = lineItem.get('unitPriceIncludingTax') - lineItem.unitPriceReduction;
            reducedTotalAmount += quantity * unitPrice * lineItem.get('taxRate') / 100;
        });
        
        return totalAmount - reducedTotalAmount;
    },
    
    calculateTotalAmount: function(){
        var me = this;
        
        var totalAmount = 0;
        me.record.getRefundBaseLineItems().each(function(lineItem) {
            totalAmount += lineItem.get('amountIncludingTax');
        });
        
        var reducedTotalAmount = 0; 
        me.record.getRefundBaseLineItems().each(function(lineItem) {
            var quantity = lineItem.get('quantity') - lineItem.quantityReduction,
                unitPrice = lineItem.get('unitPriceIncludingTax') - lineItem.unitPriceReduction;
            reducedTotalAmount += quantity * unitPrice;
        });
        
        return totalAmount - reducedTotalAmount;
    },
    
    amountRenderer: function(value) {
        var me = this;

        if (value === Ext.undefined) {
            return value;
        }
        return Ext.util.Format.currency(value, null, me.record.get('currencyDecimals'));
    },
    
    createActionButtons: function() {
        var me = this;
        return [{
            text: me.snippets.button.cancel,
            cls: 'secondary',
            action: 'wallee-payment-refund-main-window-cancel'
        }, {
            text: me.snippets.button.create,
            cls: 'primary',
            action: 'wallee-payment-refund-main-window-create'
        }];
    }
    
});
//{/block}