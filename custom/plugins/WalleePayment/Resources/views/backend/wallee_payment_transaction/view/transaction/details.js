/**
 * wallee Shopware
 *
 * This Shopware extension enables to process payments with wallee (https://www.wallee.com/).
 *
 * @package Wallee_Payment
 * @author customweb GmbH (http://www.customweb.com/)
 * @license http://www.apache.org/licenses/LICENSE-2.0  Apache Software License (ASL 2.0)
 */

//{block name="backend/wallee_payment_transaction/view/transaction/details"}
//{namespace name=backend/wallee_payment/main}
Ext.define('Shopware.apps.WalleePaymentTransaction.view.transaction.Details', {
    
    extend: 'Ext.panel.Panel',
    
    alias: 'widget.wallee-payment-transaction-details',
    
    cls: 'shopware-form',
    
    autoScroll: true,

    bodyPadding: 10,
    
    snippets: {
        field: {
            paymentMethod: '{s name=transaction/field/payment_method}Payment Method{/s}',
            state: '{s name=transaction/field/state}Transaction State{/s}',
            failureReason: '{s name=transaction/field/failure_reason}Failure Reason{/s}',
            currency: '{s name=transaction/field/currency}Currency{/s}',
            amount: '{s name=transaction/field/authorization_amount}Authorization Amount{/s}',
            transaction: '{s name=transaction/field/transaction}Transaction{/s}',
            customer: '{s name=transaction/field/customer}Customer{/s}',
            invoice: '{s name=transaction/field/invoice}Invoice{/s}',
            packingSlip: '{s name=transaction/field/packing_slip}Packing Slip{/s}'
        },
        state: {
            authorized: '{s name=transaction/state/authorized}Authorized{/s}',
            completed: '{s name=transaction/state/completed}Completed{/s}',
            confirmed: '{s name=transaction/state/confirmed}Confirmed{/s}',
            decline: '{s name=transaction/state/decline}Decline{/s}',
            failed: '{s name=transaction/state/failed}Failed{/s}',
            fulfill: '{s name=transaction/state/fulfill}Fulfill{/s}',
            pending: '{s name=transaction/state/pending}Pending{/s}',
            processing: '{s name=transaction/state/processing}Processing{/s}',
            voided: '{s name=transaction/state/voided}Voided{/s}',
            unknown: '{s name=transaction/state/unknown}Unknown State{/s}'
        },
        button: {
            downloadInvoice: '{s name=transaction/button/download_invoice}Download Invoice{/s}',
            downloadPackingSlip: '{s name=transaction/button/download_packing_slip}Download Packing Slip{/s}',
            void: '{s name=transaction/button/void}Void{/s}',
            complete: '{s name=transaction/button/complete}Complete{/s}',
            deny: '{s name=transaction/button/delivery_indication_deny}Deny{/s}',
            accept: '{s name=transaction/button/delivery_indication_accept}Accept{/s}',
            transaction: '{s name=transaction/button/transaction_link}View in wallee{/s}',
            customer: '{s name=transaction/button/customer_link}View in wallee{/s}',
            update: '{s name=transaction/button/update}Update{/s}',
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
            'downloadInvoice',
            'downloadPackingSlip',
            'voidTransaction',
            'acceptDeliveryIndication',
            'denyDeliveryIndication',
            'completeTransaction',
            'updateTransaction'
        );
    },
    
    getItems: function() {
        var me = this;
        
        var items = [
            this.createInfoPanel()
        ];
        
        var labels = me.record.get('labels');
        for (var index in labels) {
        	if (labels.hasOwnProperty(index)) {
        		items.push(this.createLabelPanel(labels[index].group, labels[index].labels));
        	}
        }
        
        return items;
    },
    
    createInfoPanel: function(){
        var me = this;

        var fields = [];
        fields.push({
            xtype: 'fieldcontainer',
            items: {
                'xtype': 'image',
                'src': me.record.get('image'),
                height: 50,
                width: 100,
                style: {
                    'object-fit': 'contain'
                }
            }
        });
        if (me.record.get('paymentMethod')) {
            fields.push({
                value: me.record.get('paymentMethod').name,
                fieldLabel: me.snippets.field.paymentMethod,
                helpText: me.record.get('paymentMethod').description
            });
        }
        fields.push({
            value: this.getTransactionStateLabel(me.record.get('state')),
            fieldLabel: me.snippets.field.state
        });
        if (me.record.get('failureReason')) {
            fields.push({
                value: me.record.get('failureReason'),
                fieldLabel: me.snippets.field.failureReason
            });
        }
        fields.push({
            value: me.record.get('currency'),
            fieldLabel: me.snippets.field.currency
        });
        fields.push({
            value: Ext.util.Format.currency(me.record.get('authorizationAmount')),
            fieldLabel: me.snippets.field.amount
        });
        fields.push({
            xtype: 'fieldcontainer',
            fieldLabel: me.snippets.field.transaction,
            items: {
                xtype: 'container',
                html: '<a href="' + me.record.get('transactionUrl') + '" target="_blank">' + me.snippets.button.transaction + '</a>'
            }
        });
        fields.push({
            xtype: 'fieldcontainer',
            fieldLabel: me.snippets.field.customer,
            items: {
                xtype: 'container',
                html: '<a href="' + me.record.get('customerUrl') + '" target="_blank">' + me.snippets.button.customer + '</a>'
            }
        });
        
        var buttons = [];
        if (me.record.get('canDownloadInvoice')) {
            buttons.push({
                xtype: 'fieldcontainer',
                fieldLabel: me.snippets.field.invoice,
                items: {
                    xtype: 'button',
                    text: me.snippets.button.downloadInvoice,
                    handler: function() {
                        me.fireEvent('downloadInvoice', me.record, me);
                    }
                }
            });
        }
        if (me.record.get('canDownloadPackingSlip')) {
            buttons.push({
                xtype: 'fieldcontainer',
                fieldLabel: me.snippets.field.packingSlip,
                items: {
                    xtype: 'button',
                    text: me.snippets.button.downloadPackingSlip,
                    handler: function() {
                        me.fireEvent('downloadPackingSlip', me.record, me);
                    }
                }
            });
        }
        
        var panel = Ext.create('Ext.container.Container',  {
            layout: 'column',
            margin: '0 0 10',
            items: [
                Ext.create('Ext.container.Container', {
                    columnWidth:    0.5,
                    layout:         'anchor',
                    padding:        '10px 20px',
                    defaults: {
                        anchor:     '100%',
                        xtype:      'displayfield',
                        labelWidth: 155,
                        fieldStyle: 'margin-top: 5px; overflow-wrap: break-word; word-wrap: break-word;'
                    },
                    items:          fields
                }),
                Ext.create('Ext.container.Container', {
                    columnWidth:    0.5,
                    layout:         'anchor',
                    padding:        '10px 20px',
                    items:          buttons
                })
            ]
        });
        
        return panel;
    },
    
    getTransactionStateLabel: function(state) {
        var me = this;
        switch(state) {
            case 'AUTHORIZED':
                return me.snippets.state.authorized;
            case 'COMPLETED':
                return me.snippets.state.completed;
            case 'CONFIRMED':
                return me.snippets.state.confirmed;
            case 'DECLINE':
                return me.snippets.state.decline;
            case 'FAILED':
                return me.snippets.state.failed;
            case 'FULFILL':
                return me.snippets.state.fulfill;
            case 'PENDING':
                return me.snippets.state.pending;
            case 'PROCESSING':
                return me.snippets.state.processing;
            case 'VOIDED':
                return me.snippets.state.voided;
            default:
                return me.snippets.state.unknown;
        }
    },
    
    createLabelPanel: function(group, labels) {
        var me = this;
        
        var fields = me.createLabelFields(labels);
        var panel = Ext.create('Ext.panel.Panel',  {
            layout: 'column',
            title: group.name,
            margin: '0 0 10',
            items: [
                me.createLabelColumn(fields.left),
                me.createLabelColumn(fields.right)
            ]
        });
        
        return panel;
    },
    
    createLabelColumn: function(fields) {
        var me = this;
        
        var column = Ext.create('Ext.container.Container', {
            columnWidth:    0.5,
            layout:         'anchor',
            padding:        '10px 20px',
            defaults: {
                anchor:     '100%',
                xtype:      'displayfield',
                labelWidth: 155,
                fieldStyle: 'margin-top: 5px; overflow-wrap: break-word; word-wrap: break-word;'
            },
            items:          fields
        });
        
        return column;
    },
    
    createLabelFields: function(labels) {
        var me = this,
            field,
            fields = { left: [], right: [] },
            count = Math.ceil(Object.keys(labels).length / 2),
            i = 1;
        for (var key in labels) {
        	if (labels.hasOwnProperty(key)) {
	            field = { value: labels[key].value, fieldLabel: labels[key].descriptor.name, helpText: labels[key].descriptor.description };
	            if (i <= count) {
	                fields.left.push(field);
	            } else {
	                fields.right.push(field);
	            }
	            i++;
        	}
        }
        return fields;
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
        
        buttons.push({
            text: me.snippets.button.update,
            action: 'updateTransaction',
            cls: 'secondary',
            handler: function() {
                me.fireEvent('updateTransaction', me.record, me);
            }
        });
        
        if (me.record.get('canVoid')) {
            buttons.push({
                text: me.snippets.button.void,
                action: 'voidTransaction',
                cls: 'secondary',
                handler: function() {
                    me.fireEvent('voidTransaction', me.record, me);
                }
            });
        }
        
        if (me.record.get('canComplete')) {
            buttons.push({
                text: me.snippets.button.complete,
                action: 'completeTransaction',
                cls: 'primary',
                handler: function() {
                    me.fireEvent('completeTransaction', me.record, me);
                }
            });
        }
        
        if (me.record.get('canReview')) {
            buttons.push({
                text: me.snippets.button.deny,
                action: 'deny-delivery-indication',
                cls: 'secondary',
                handler: function() {
                    me.fireEvent('denyDeliveryIndication', me.record, me);
                }
            });
            
            buttons.push({
                text: me.snippets.button.accept,
                action: 'accept-delivery-indication',
                cls: 'primary',
                handler: function() {
                    me.fireEvent('acceptDeliveryIndication', me.record, me);
                }
            });
        }
        
        return buttons;
    }
    
});
//{/block}