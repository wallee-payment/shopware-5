<?php

use WalleePayment\Components\Controller\Backend;

class Shopware_Controllers_Backend_WalleePaymentSynchronize extends Backend
{
    public function synchronizeAction()
    {
        $pluginConfig = $this->get('shopware.plugin.config_reader')->getByPluginName('WalleePayment');
        $userId = $pluginConfig['applicationUserId'];
        $applicationKey = $pluginConfig['applicationUserKey'];
        if ($userId && $applicationKey) {
            try {
                $this->get('events')->notify('Wallee_Payment_Config_Synchronize');

                $this->view->assign([
                    'success' => true
                ]);
            } catch (\Exception $e) {
                $this->view->assign([
                    'success' => false,
                    'message' => $e->getMessage()
                ]);
            }
        } else {
            $this->view->assign([
                'success' => false,
                'message' => $this->get('snippets')->getNamespace('backend/wallee_payment/main')->get('synchronize/message/config_incomplete', 'The configuration is incomplete.')
            ]);
        }
    }
}
