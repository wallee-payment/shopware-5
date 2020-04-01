/**
 * wallee Shopware 5
 *
 * This Shopware 5 extension enables to process payments with wallee (https://www.wallee.com/).
 *
 * @package Wallee_Payment
 * @author customweb GmbH (http://www.customweb.com/)
 * @license http://www.apache.org/licenses/LICENSE-2.0  Apache Software License (ASL 2.0)
 */

//{block name="backend/wallee_payment_refund/view/main/form"}
//{namespace name=backend/wallee_payment/main}
Ext.define('Shopware.apps.WalleePaymentRefund.view.main.Form', {
    
    extend: 'Ext.grid.Panel',
    
    alias: 'widget.wallee-payment-refund-main-form',
    
    minHeight: 90,
    
    autoScroll: true,
    
    disableSelection: true,
    
    sortableColumns: false,
    
    snippets: {
        field: {
            uniqueId: '{s name=line_item/field/uniqueid}Unique ID{/s}',
            sku: '{s name=line_item/field/sku}SKU{/s}',
            name: '{s name=line_item/field/name}Name{/s}',
            unitPriceIncludingTax: '{s name=line_item/field/unit_price_including_tax}Unit Price{/s}',
            amountIncludingTax: '{s name=refund/field/refund_amount}Refund Amount{/s}',
            taxRate: '{s name=line_item/field/tax_rate}Taxes{/s}',
            quantity: '{s name=line_item/field/quantity}Quantity{/s}',
            type: '{s name=line_item/field/type}Type{/s}'
        },
        type: {
            product: '{s name=line_item/type/product}Product{/s}',
            discount: '{s name=line_item/type/discount}Discount{/s}',
            fee: '{s name=line_item/type/fee}Fee{/s}',
            shipping: '{s name=line_item/type/shipping}Shipping{/s}'
        }
    },
    
    viewConfig: {
        enableTextSelection: false
    },
    
    initComponent:function () {
        var me = this;
        me.columns = me.getColumns();
        me.callParent(arguments);
    },
    
    getColumns:function () {
        var me = parent = this;

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
                    if (value == 0) {
                        return 0;
                    }
                    
                    return {
                        xtype: 'container',
                        layout: 'fit',
                        items: [{
                            xtype: 'displayfield',
                            cls: 'wallee-payment-refund-reduction-value',
                            initComponent: function(){
                                var me = this;
                                me.callParent(arguments);
                                record.on('updateReductions', function(){
                                    me.setValue(value - record.quantityReduction);
                                });
                                me.setValue(value);
                            }
                        }, {
                            xtype: 'numberfield',
                            value: 0,
                            minValue: 0,
                            maxValue: value,
                            allowBlank: false,
                            listeners: {
                                change: function(field, value){
                                    record.quantityReduction = value;
                                    record.fireEvent('updateReductions');
                                    me.record.fireEvent('updateReductions');
                                }
                            }
                        }]
                    };
                }
            },
            {
                header: me.snippets.field.unitPriceIncludingTax,
                dataIndex: 'unitPriceIncludingTax',
                flex: 1,
                xtype: 'componentcolumn',
                renderer: function(value, meta, record){
                    if (value == 0) {
                        return me.amountRenderer(0);
                    }
                    
                    return {
                        xtype: 'container',
                        layout: 'fit',
                        items: [{
                            xtype: 'displayfield',
                            cls: 'wallee-payment-refund-reduction-value',
                            initComponent: function(){
                                var me = this;
                                me.callParent(arguments);
                                record.on('updateReductions', function(){
                                    me.setValue(parent.amountRenderer(value - record.unitPriceReduction));
                                });
                                me.setValue(parent.amountRenderer(value));
                            }
                        }, {
                            xtype: 'numberfield',
                            value: 0,
                            minValue: value < 0 ? value : 0,
                            maxValue: value < 0 ? 0 : value,
                            allowBlank: false,
                            decimalPrecision: me.record.get('currencyDecimals'),
                            listeners: {
                                change: function(field, value){
                                    record.unitPriceReduction = value;
                                    record.fireEvent('updateReductions');
                                    me.record.fireEvent('updateReductions');
                                }
                            }
                        }]
                    };
                }
            },
            {
                header: me.snippets.field.amountIncludingTax,
                dataIndex: 'amountIncludingTax',
                flex: 1,
                xtype: 'componentcolumn',
                renderer: function(value, meta, record){
                    return {
                        xtype: 'displayfield',
                        cls: 'wallee-payment-refund-reduction-value',
                        initComponent: function(){
                            var me = this;
                            me.callParent(arguments);
                            record.on('updateReductions', function(){
                                var quantity = record.get('quantity') - record.quantityReduction,
                                    unitPrice = record.get('unitPriceIncludingTax') - record.unitPriceReduction;
                                me.setValue(parent.amountRenderer(quantity * unitPrice));
                            });
                            me.setValue(parent.amountRenderer(record.get('amountIncludingTax')));
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