//{block name="backend/wallee_payment_transaction/store/transaction"}
Ext.define('Shopware.apps.WalleePaymentTransaction.store.Transaction', {

    extend: 'Ext.data.Store',
 
    autoLoad: false,
    
    sorters: [{
		property : 'transactionId',
		direction: 'DESC'
	}],
 
    model: 'Shopware.apps.WalleePaymentTransaction.model.Transaction'
        
});
//{/block}