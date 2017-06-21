//{block name="backend/wallee_payment_transaction/model/refund"}
Ext.define('Shopware.apps.WalleePaymentTransaction.model.Refund', {
    
    extend: 'Ext.data.Model',
 
    fields: [
        //{block name="backend/wallee_payment_transaction/model/refund/fields"}{/block}
        { name: 'id', type: 'int' },
        { name: 'state', type: 'string' },
        { name: 'createdOn', type: 'date' },
        { name: 'amount', type: 'float' },
        { name: 'externalId', type: 'string' },
        { name: 'failureReason', type: 'string' },
        { name: 'labels', type: 'object' }
    ],
    
    associations:[
        {
            type: 'hasMany',
            model: 'Shopware.apps.WalleePaymentTransaction.model.LineItem',
            name: 'getLineItems',
            associationKey: 'lineItems'
        }
    ]

});
//{/block}