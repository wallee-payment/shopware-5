<?php

/**
 * wallee Shopware
 *
 * This Shopware extension enables to process payments with wallee (https://www.wallee.com/).
 *
 * @package Wallee_Payment
 * @author customweb GmbH (http://www.customweb.com/)
 * @license http://www.apache.org/licenses/LICENSE-2.0  Apache Software License (ASL 2.0)
 */

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
