/**
 * wallee Shopware 5
 *
 * This Shopware 5 extension enables to process payments with wallee (https://www.wallee.com/).
 *
 * @package Wallee_Payment
 * @author wallee AG (http://www.wallee.com/)
 * @license http://www.apache.org/licenses/LICENSE-2.0  Apache Software License (ASL 2.0)
 */

//{block name="backend/wallee_payment_transaction/view/transaction/refunds/details"}
//{namespace name=backend/wallee_payment/main}
Ext.define('Shopware.apps.WalleePaymentTransaction.view.transaction.Refunds.Details', {
    
    extend: 'Ext.panel.Panel',
    
    alias: 'widget.wallee-payment-transaction-refunds-details',
    
    cls: 'shopware-form',
    
    autoScroll: true,

    bodyPadding: 10,
    
    snippets: {
        title: '{s name=refund/title}Refund details:{/s}',
        field: {
            state: '{s name=refund/field/state}Refund State{/s}',
            amount: '{s name=refund/field/amount}Amount{/s}',
            externalId: '{s name=refund/field/external_id}External ID{/s}',
            failureReason: '{s name=refund/fild/failure_reason}Failure Reason{/s}',
            document: '{s name=refund/field/document}Document{/s}'
        },
        state: {
            successful: '{s name=refund/state/successful}Successful{/s}',
            failed: '{s name=refund/state/failed}Failed{/s}',
            pending: '{s name=refund/state/pending}Pending{/s}',
            manualCheck: '{s name=refund/state/manual_check}Manual Check{/s}',
            unknown: '{s name=refund/state/unknown}Unknown State{/s}'
        },
        button: {
            downloadDocument: '{s name=refund/button/download_document}Download Document{/s}',
            failed: '{s name=refund/button/mark_as_failed}Mark as Failed{/s}',
            successful: '{s name=refund/button/mark_as_successful}Mark as Successful{/s}'
        }
    },
    
    initComponent:function () {
        var me = this;
        
        me.registerEvents();
        me.items = [];
        me.callParent(arguments);
    },
    
    registerEvents: function() {
        this.addEvents(
            'markAsFailed',
            'markAsSuccessful',
            'downloadRefund'
        );
    },
    
    updateRecord: function(record) {
        var me = this;
        
        me.removeAll();
        me.getDockedItems().forEach(function(component){
            me.removeDocked(component);
        });
        if (record) {
            me.record = record;
            me.add(me.getItems());
            me.addDocked(me.createToolbar());
        }
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
        
        items.push(this.createLineItemPanel());
        
        return items;
    },
    
    createInfoPanel: function(){
        var me = this;

        var fields = [];
        fields.push({
            value: this.getRefundStateLabel(me.record.get('state')),
            fieldLabel: me.snippets.field.state
        });
        fields.push({
            value: Ext.util.Format.currency(me.record.get('amount')),
            fieldLabel: me.snippets.field.amount
        });
        fields.push({
            value: me.record.get('externalId'),
            fieldLabel: me.snippets.field.externalId
        });
        if (me.record.get('failureReason')) {
            fields.push({
                value: me.record.get('failureReason'),
                fieldLabel: me.snippets.field.failureReason
            });
        }
        fields.push({
            xtype: 'fieldcontainer',
            fieldLabel: me.snippets.field.document,
            items: {
                xtype: 'button',
                text: me.snippets.button.downloadDocument,
                handler: function() {
                    me.fireEvent('downloadRefund', me.record, me.transaction, me);
                }
            }
        });
        
        var panel = Ext.create('Ext.container.Container', {
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
        
        return panel;
    },
    
    getRefundStateLabel: function(state) {
        var me = this;
        
        switch(state) {
            case 'SUCCESSFUL':
                return me.snippets.state.successful;
            case 'FAILED':
                return me.snippets.state.failed;
            case 'PENDING':
                return me.snippets.state.pending;
            case 'MANUAL_CHECK':
                return me.snippets.state.manualCheck;
            default:
                return me.snippets.state.unknown;
        }
    },
    
    createLabelPanel: function(group, labels) {
        var me = this;
        
        var panel = Ext.create('Ext.panel.Panel',  {
            layout: 'fit',
            title: group.name,
            margin: '0 0 10',
            items: [
                Ext.create('Ext.container.Container', {
                    layout:         'anchor',
                    padding:        '10px 20px',
                    defaults: {
                        anchor:     '100%',
                        xtype:      'displayfield',
                        labelWidth: 155,
                        fieldStyle: 'margin-top: 5px; overflow-wrap: break-word; word-wrap: break-word;'
                    },
                    items:          me.createLabelFields(labels)
                })
            ]
        });
        
        return panel;
    },
    
    createLabelFields: function(labels) {
        var me = this,
            fields = [];
        for (var key in labels) {
        	if (labels.hasOwnProperty(key)) {
        		fields.push({ value: labels[key].value, fieldLabel: labels[key].descriptor.name, helpText: labels[key].descriptor.description });
        	}
        }
        return fields;
    },
    
    createLineItemPanel: function(){
        var me = this;
        
        var panel = Ext.create('Shopware.apps.WalleePaymentTransaction.view.transaction.LineItems.Grid', {
            name: 'wallee-payment-transaction-line-item-grid',
            record: me.record,
            store: me.record.getLineItems(),
            viewConfig: {
                enableTextSelection: false
            }
        });
        
        return panel;
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
        
        if (me.record.get('state') == 'MANUAL_CHECK') {
            buttons.push({
                text: me.snippets.button.failed,
                action: 'markAsFailed',
                cls: 'secondary',
                handler: function() {
                    me.fireEvent('markAsFailed', me.record, me.transaction, me);
                }
            });
            
            buttons.push({
                text: me.snippets.button.successful,
                action: 'markAsSuccessful',
                cls: 'primary',
                handler: function() {
                    me.fireEvent('markAsSuccessful', me.record, me.transaction, me);
                }
            });
        }
        
        return buttons;
    }
    
});
//{/block}