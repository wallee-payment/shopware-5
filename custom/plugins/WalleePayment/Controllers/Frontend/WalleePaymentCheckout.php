<?php

/**
 * Wallee Shopware
 *
 * This Shopware extension enables to process payments with Wallee (https://wallee.com/).
 *
 * @package Wallee_Payment
 * @author customweb GmbH (http://www.customweb.com/)
 * @license http://www.apache.org/licenses/LICENSE-2.0  Apache Software License (ASL 2.0)
 * @link https://github.com/wallee-payment/shopware
 */

class Shopware_Controllers_Frontend_WalleePaymentCheckout extends Shopware_Controllers_Frontend_Checkout
{
    public function preDispatch()
    {
        parent::preDispatch();

        if (in_array($this->Request()->getActionName(), [
            'saveOrder'
        ])) {
            $this->Front()
                ->Plugins()
                ->Json()
                ->setRenderer(true);
        }
    }

    public function saveOrderAction()
    {
        $backup = $this->get('wallee_payment.basket')->backupBasket();
        $this->finishAction();
        $this->get('wallee_payment.basket')->restoreBasket($backup);
        $this->get('modules')->Order()->sCreateTemporaryOrder();
    }

    public function forward($action, $controller = null, $module = null, array $params = null)
    {
        if ($action == 'confirm') {
            $this->view->assign([
                'success' => false
            ]);
        } else {
            $this->view->assign([
                'success' => true
            ]);
        }
    }
}
