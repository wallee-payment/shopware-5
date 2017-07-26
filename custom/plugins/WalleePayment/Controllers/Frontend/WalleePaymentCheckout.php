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
    private $_orderNumber;
    
    public function preDispatch()
    {
        parent::preDispatch();

        if (in_array($this->Request()->getActionName(), [
            'saveOrder'
        ])) {
            $this->Front()
                ->Plugins()
                ->ViewRenderer()
                ->setNoRender();
        }
    }

    public function saveOrderAction()
    {
        $this->_orderNumber = null;
        $backup = $this->get('wallee_payment.basket')->backupBasket();
        $this->finishAction();
        $this->get('wallee_payment.basket')->restoreBasket($backup);
        if ($this->_orderNumber != null) {
            $this->get('modules')->Order()->sCreateTemporaryOrder();
            echo json_encode([
                'result' => 'success'
            ]);
        }
    }
    
    public function saveOrder()
    {
        $orderNumber = parent::saveOrder();
        $this->_orderNumber = $orderNumber;
        return $orderNumber;
    }

    public function forward($action, $controller = null, $module = null, array $params = null)
    {
    }
}
