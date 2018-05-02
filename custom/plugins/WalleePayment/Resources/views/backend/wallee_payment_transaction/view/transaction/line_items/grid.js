/**
 * wallee Shopware
 *
 * This Shopware extension enables to process payments with wallee (https://www.wallee.com/).
 *
 * @package Wallee_Payment
 * @author customweb GmbH (http://www.customweb.com/)
 * @license http://www.apache.org/licenses/LICENSE-2.0  Apache Software License (ASL 2.0)
 */

//{block name="backend/wallee_payment_transaction/view/transaction/line_items/grid"}
//{namespace name=backend/wallee_payment/main}
Ext.define('Shopware.apps.WalleePaymentTransaction.view.transaction.LineItems.Grid', {
    
    extend: 'Ext.grid.Panel',
    
    alias: 'widget.wallee-payment-transaction-line-items-grid',
    
    minHeight: 90,
    
    autoScroll: true,
    
    disableSelection: true,
    
    sortableColumns: false,
    
    snippets: {
        field: {
            uniqueId: '{s name=line_item/field/uniqueid}Unique ID{/s}',
            sku: '{s name=line_item/field/sku}SKU{/s}',
            name: '{s name=line_item/field/name}Name{/s}',
            amountIncludingTax: '{s name=line_item/field/amount_including_tax}Total{/s}',
            taxRate: '{s name=line_item/field/tax_rate}Taxes{/s}',
            quantity: '{s name=line_item/field/quantity}Quantity{/s}',
            type: '{s name=line_item/field/type}Type{/s}',
        },
        type: {
            product: '{s name=line_item/type/product}Product{/s}',
            discount: '{s name=line_item/type/discount}Discount{/s}',
            fee: '{s name=line_item/type/fee}Fee{/s}',
            shipping: '{s name=line_item/type/shipping}Shipping{/s}',
        }
    },
    
    viewConfig: {
        enableTextSelection: false
    },
    
    initComponent:function () {
        var me = this;
        me.store.each(function(record){
            record.updatedQuantity = record.get('quantity');
            record.updatedAmount = record.get('amountIncludingTax');
        });
        me.columns = me.getColumns();
        me.callParent(arguments);
    },
    
    getColumns:function () {
        var me = this;

        var columns = [
            {
                header: me.snippets.field.uniqueId,
                dataIndex: 'uniqueId',
                flex: 1
            },
            {
                header: me.snippets.field.sku,
                dataIndex: 'sku',
                flex: 1
            },
            {
                header: me.snippets.field.name,
                dataIndex: 'name',
                flex: 2
            },
            {
                header: me.snippets.field.quantity,
                dataIndex: 'quantity',
                xtype: 'componentcolumn',
                flex: 1,
                renderer: function(value, meta, record){
                    if (!me.record.get('canUpdateLineItems')) {
                        return value;
                    }
                    
                    return {
                        xtype: 'numberfield',
                        value: value,
                        record: me.record,
                        minValue: 0,
                        maxValue: record.get('originalQuantity'),
                        allowBlank: false,
                        listeners: {
                            change: function(field, value){
                                var totalDiff = me.record.get('authorizationAmount') - me.record.get('lineItemTotalAmount');
                                record.updatedQuantity = value;
                                record.updatedAmount = Ext.util.Format.round(Math.min(value * record.get('originalUnitPriceIncludingTax'), totalDiff), me.record.get('currencyDecimals'));
                                me.record.set('lineItemTotalAmount', me.calculateTotalAmount());
                                record.fireEvent('updateLineItem');
                                me.record.fireEvent('updateLineItem');
                            }
                        },
                        initComponent: function(){
                            var me = this,
                                updateField = function(){
                                    var totalDiff = me.record.get('authorizationAmount') - me.record.get('lineItemTotalAmount');
                                    if (record.get('originalQuantity') <= 0 || (record.get('originalAmountIncludingTax') < 0 && totalDiff + record.updatedAmount < 0)) {
                                        me.disable();
                                    } else {
                                        me.enable();
                                    }
                                };
                            me.callParent(arguments);
                            record.on('updateLineItem', function(){
                                me.setValue(record.updatedQuantity);
                            });
                            me.record.on('updateLineItem', function(){
                                updateField();
                            });
                            me.setValue(value);
                            updateField();
                        }
                    };
                }
            },
            {
                header: me.snippets.field.amountIncludingTax,
                dataIndex: 'amountIncludingTax',
                xtype: 'componentcolumn',
                flex: 1,
                renderer: function(value, meta, record){
                    if (!me.record.get('canUpdateLineItems')) {
                        return value;
                    }
                    
                    return {
                        xtype: 'numberfield',
                        value: value,
                        record: me.record,
                        allowBlank: false,
                        decimalPrecision: me.record.get('currencyDecimals'),
                        listeners: {
                            change: function(field, value){
                                record.updatedAmount = Ext.util.Format.round(value, me.record.get('currencyDecimals'));
                                me.record.set('lineItemTotalAmount', me.calculateTotalAmount());
                                record.fireEvent('updateLineItem');
                                me.record.fireEvent('updateLineItem');
                            }
                        },
                        initComponent: function(){
                            var me = this,
                                updateField = function(){
                                    var totalDiff = me.record.get('authorizationAmount') - me.record.get('lineItemTotalAmount'),
                                        minValue,
                                        maxValue;
                                    if (record.get('originalAmountIncludingTax') < 0) {
                                        minValue = record.get('originalAmountIncludingTax');
                                        maxValue = Math.min(0, record.updatedAmount + totalDiff);
                                    } else {
                                        minValue = 0;
                                        maxValue = Math.min(record.updatedQuantity * record.get('originalUnitPriceIncludingTax'), record.updatedAmount + totalDiff);
                                    }
                                    me.setMinValue(minValue);
                                    me.setMaxValue(maxValue);
                                    if (record.updatedQuantity == 0 || minValue >= maxValue) {
                                        me.disable();
                                    } else {
                                        me.enable();
                                    }
                                };
                            me.callParent(arguments);
                            record.on('updateLineItem', function(){
                                me.setValue(record.updatedAmount);
                            });
                            me.record.on('updateLineItem', function(){
                                updateField();
                            });
                            me.setValue(value);
                            updateField();
                        }
                    };
                }
            },
            {
                header: me.snippets.field.type,
                dataIndex: 'type',
                flex: 1,
                renderer: me.typeRenderer
            },
            {
                header: me.snippets.field.taxRate,
                dataIndex: 'taxRate',
                flex: 1,
                renderer: me.taxRenderer
            }
        ];
        
        return columns;
    },
    
    calculateTotalAmount: function(){
        var me = this;
        
        var totalAmount = 0;
        me.record.getLineItems().each(function(lineItem) {
            totalAmount += lineItem.updatedAmount;
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
    
    taxRenderer: function(value){
        return value.toString().replace(/[.,]/, Ext.util.Format.decimalSeparator)+'%';
    },
    
    typeRenderer: function(value) {
        var me = this;
        
        switch (value) {
        case 'PRODUCT':
            return me.snippets.type.product;
        case 'DISCOUNT':
            return me.snippets.type.discount;
        case 'FEE':
            return me.snippets.type.fee;
        case 'SHIPPING':
            return me.snippets.type.shipping;
        }
    }
    
});
//{/block}