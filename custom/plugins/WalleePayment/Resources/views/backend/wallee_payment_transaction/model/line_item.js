/**
 * wallee Shopware 5
 *
 * This Shopware 5 extension enables to process payments with wallee (https://www.wallee.com/).
 *
 * @package Wallee_Payment
 * @author wallee AG (http://www.wallee.com/)
 * @license http://www.apache.org/licenses/LICENSE-2.0  Apache Software License (ASL 2.0)
 */

//{block name="backend/wallee_payment_transaction/model/line_item"}
Ext.define('Shopware.apps.WalleePaymentTransaction.model.LineItem', {
    
    extend: 'Ext.data.Model',
    
    updatedQuantity: null,
    updatedAmount: null,
 
    fields: [
        //{block name="backend/wallee_payment_transaction/model/line_item/fields"}{/block}
        { name: 'id', type: 'string' },
        { name: 'uniqueId', type: 'string' },
        { name: 'sku', type: 'string' },
        { name: 'name', type: 'string' },
        { name: 'amountIncludingTax', type: 'float' },
        { name: 'unitPriceIncludingTax', type: 'float' },
        { name: 'taxes', type: 'object' },
        { name: 'taxRate', type: 'float' },
        { name: 'type', type: 'string' },
        { name: 'quantity', type: 'float' },
        { name: 'shippingRequired', type: 'boolean' },
        { name: 'originalAmountIncludingTax', type: 'float' },
        { name: 'originalUnitPriceIncludingTax', type: 'float' },
        { name: 'originalQuantity', type: 'float' }
    ]

});
//{/block}