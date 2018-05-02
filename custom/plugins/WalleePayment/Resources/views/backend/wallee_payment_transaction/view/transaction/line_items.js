/**
 * wallee Shopware
 *
 * This Shopware extension enables to process payments with wallee (https://www.wallee.com/).
 *
 * @package Wallee_Payment
 * @author customweb GmbH (http://www.customweb.com/)
 * @license http://www.apache.org/licenses/LICENSE-2.0  Apache Software License (ASL 2.0)
 */

//{block name="backend/wallee_payment_transaction/view/transaction/line_items"}
//{namespace name=backend/wallee_payment/main}
Ext.define('Shopware.apps.WalleePaymentTransaction.view.transaction.LineItems', {
    
    extend: 'Ext.panel.Panel',
    
    alias: 'widget.wallee-payment-transaction-line-items',
    
    layout: 'fit',
    
    autoScroll: true,

    border: false,
    
    snippets: {
        field: {
            taxes: '{s name=line_item/field/total_tax_amount}Taxes{/s}',
            total: '{s name=line_item/field/total_amount}Total{/s}'
        },
        button: {
            update: '{s name=line_item/button/update}Update{/s}'
        }
    },
    
    initComponent: function() {
        var me = this;
        me.registerEvents();
        me.items = me.getItems();
        me.dockedItems = me.createToolbar();
        me.callParent(arguments);
    },

    registerEvents: function() {
        this.addEvents(
            'updateLineItems'
        );
    },
    
    getItems: function() {
        var me = this;
        
        var items = [
            Ext.create('Ext.panel.Panel', {
                layout: 'border',
                items: [ me.createLineItemGrid(), me.createTotals() ]
            })
        ];

        return items;
    },
    
    createLineItemGrid: function(){
        var me = this;
        
        me.grid = Ext.create('Shopware.apps.WalleePaymentTransaction.view.transaction.LineItems.Grid', {
            region: 'center',
            record: me.record,
            store: me.record.getLineItems(),
            style: {
                borderTop: '1px solid #A4B5C0'
            }
        });

        return me.grid;
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
                    fieldLabel: me.snippets.field.taxes,
                    value: 0,
                    record: me.record,
                    renderer: me.amountRenderer,
                    initComponent: function(){
                        var me = this;
                        me.callParent(arguments);
                        me.record.on('updateLineItem', function(){
                            me.setValue(parent.calculateTaxAmount());
                        });
                        me.setValue(parent.calculateTaxAmount());
                    }
                }, {
                    fieldLabel: me.snippets.field.total,
                    value: 0,
                    record: me.record,
                    renderer: me.amountRenderer,
                    initComponent: function(){
                        var me = this;
                        me.callParent(arguments);
                        me.record.on('updateLineItem', function(){
                            me.setValue(me.record.get('lineItemTotalAmount'));
                        });
                        me.setValue(me.record.get('lineItemTotalAmount'));
                    }
                }]
            }]
        });
        
        return totals;
    },
    
    calculateTaxAmount: function(){
        var me = this;
        
        var totalAmount = 0;
        me.record.getLineItems().each(function(lineItem) {
            totalAmount += (lineItem.updatedAmount - (lineItem.updatedAmount / (1 + lineItem.get('taxRate') / 100)));
        });
        
        return totalAmount;
    },
    
    amountRenderer: function(value) {
        var me = this;

        if (value === Ext.undefined) {
            return value;
        }
        return Ext.util.Format.currency(value, null, me.record.get('currencyDecimals'));
    },
    
    createToolbar: function() {
        var me = this,
            toolbar = [];
        
        var buttons = me.createButtons();
        if (buttons.length > 1) {
            toolbar.push({
                xtype: 'toolbar',
                dock: 'bottom',
                ui: 'footer',
                border: 0,
                defaults: {
                    xtype: 'button'
                },
                items: buttons
            });
        }
        
        return toolbar;
    },
    
    createButtons: function() {
        var me = this;
        
        var buttons = [{ xtype: 'component', flex: 1 }];
        
        if (me.record.get('canUpdateLineItems')) {
            buttons.push({
                text: me.snippets.button.update,
                action: 'updateLineItems',
                cls: 'primary',
                handler: function() {
                    me.fireEvent('updateLineItems', me.record, me.grid);
                }
            });
        }
        
        return buttons;
    }
    
});
//{/block}