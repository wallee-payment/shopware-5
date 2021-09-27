/**
 * wallee Shopware 5
 *
 * This Shopware 5 extension enables to process payments with wallee (https://www.wallee.com/).
 *
 * @package Wallee_Payment
 * @author wallee AG (http://www.wallee.com/)
 * @license http://www.apache.org/licenses/LICENSE-2.0  Apache Software License (ASL 2.0)
 */

//{namespace name=backend/wallee_payment/main}
Ext.define('Shopware.apps.WalleePaymentManualTasksWidget.view.Main', {

    extend: 'Shopware.apps.Index.view.widgets.Base',

    alias: 'widget.wallee-payment-manual-tasks',

    title: '{s name="manual_task/window/title"}wallee Payment - Manual Tasks{/s}',

    minHeight: 0,

    snippets: {
        button: '{s name=manual_task/info_button}View in wallee{/s}',
        dataLabel: '{s name="manual_task/text"}Number of manual tasks that need your attention{/s}'
    },

    initComponent: function() {
        var me = this;

        me.items = [];

        me.tools = [{
            type: 'refresh',
            scope: me,
            handler: me.refreshView
        }];
        
        me.load(function(result){
            me.add(me.createContainer(result));
            
            me.createTaskRunner();
        });

        me.callParent(arguments);
    },
    
    createTaskRunner: function () {
        var me = this;

        me.refreshTask = Ext.TaskManager.start({
            scope: me,
            run: me.refreshView,
            interval: 300000
        });
    },
    
    refreshView: function(){
        var me = this;
        
        me.load(function(result){
            me.dataView.update([{
                numberOfManualTasks: result.data.number
            }]);
            
            if (result.data.number == 0) {
                me.detailBtn.hide();
            } else {
                me.detailBtn.show();
            }
            me.detailBtn.setHandler(function(){
                window.open(result.data.detailUrl);
            });
        });
    },
    
    createContainer: function(result){
        var me = this;
        
        return Ext.create('Ext.container.Container', {
            layout: 'hbox',
            items: [
                me.createDataView(result.data.number),
                {
                    xtype: 'container',
                    cls: 'button-container',
                    items: [ me.createButton(result.data.detailUrl) ]
                }
            ]
        });
    },

    createDataView: function(numberOfManualTasks) {
        var me = this;
        
        me.dataView = Ext.create('Ext.view.View', {
            flex: 1,
            tpl: me.createManualTaskDataTemplate(),
            data: [
                {
                    numberOfManualTasks: numberOfManualTasks
                }
            ]
        });

        return me.dataView;
    },
    
    createManualTaskDataTemplate: function() {
        var me = this;

        return new Ext.XTemplate(
            '{literal}',
            '<tpl for=".">',
                '<div class="manual-tasks">',
                    '<strong class="title">' + me.snippets.dataLabel + ':</strong>',
                    '<span class="number">{numberOfManualTasks}</span>',
                '</div>',
            '</tpl>',
            '{/literal}'
        );
    },
    
    createButton: function(detailUrl) {
        var me = this;

        me.detailBtn = Ext.create('Ext.button.Button', {
            cls: 'small primary',
            text: me.snippets.button,
            handler: function() {
                window.open(detailUrl);
            }
        });
        
        return me.detailBtn;
    },
    
    load: function(callback){
        var me = this;
        
        Ext.Ajax.request({
            url: '{url controller=WalleePaymentManualTasksWidget action=info}',
            async: true,
            success: function(response) {
                if (!response || !response.responseText) {
                    return;
                }

                var result = Ext.decode(response.responseText);
                if (!result.success) {
                    return;
                }
                
                if (typeof callback == 'function') {
                    callback(result);
                }
            }
        });
    }
    
});