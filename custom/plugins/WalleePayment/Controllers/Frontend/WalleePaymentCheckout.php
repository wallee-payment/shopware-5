<?php

/**
 * wallee Shopware 5
 *
 * This Shopware 5 extension enables to process payments with wallee (https://www.wallee.com/).
 *
 * @package Wallee_Payment
 * @author customweb GmbH (http://www.customweb.com/)
 * @license http://www.apache.org/licenses/LICENSE-2.0  Apache Software License (ASL 2.0)
 */

class Shopware_Controllers_Frontend_WalleePaymentCheckout extends Shopware_Controllers_Frontend_Checkout
{
    private $_orderNumber;
    
    private $_forward;
    
    private $_requestParams = array();
    
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
        ob_start();
        if (!$this->get('wallee_payment.transaction')->isBasketTransactionPending()) {
            ob_clean();
            
            /* @var \Enlight_Components_Session_Namespace $session */
            $session = $this->get('session');
            $session['wallee_payment.transaction_timeout'] = true;
            echo json_encode([
                'result' => 'timeout'
            ]);
        } else {
            $this->_orderNumber = null;
            $backup = $this->get('wallee_payment.basket')->backupBasket();
            $this->finishAction();
            if ($this->_forward !== 'confirm') {
                $this->get('wallee_payment.basket')->restoreBasket($backup);
            }
            if ($this->_orderNumber != null) {
                $this->get('wallee_payment.registry')->set('disable_risk_management', true);
                $this->get('modules')->Order()->sCreateTemporaryOrder();
                ob_clean();
                echo json_encode([
                    'result' => 'success'
                ]);
            } else {
                if (isset($this->_requestParams['voucherErrors'])) {
                    ob_clean();
                    echo json_encode([
                        'result' => 'error',
                        'error' => current($this->_requestParams['voucherErrors'])
                    ]);
                } elseif (isset($this->_requestParams['agreementErrors']['agbError'])) {
                    ob_clean();
                    echo json_encode([
                        'result' => 'error',
                        'error' => 'agbError'
                    ]);
                }
            }
        }
        ob_end_flush();
    }
    
    public function saveOrder()
    {
        $orderNumber = parent::saveOrder();
        $this->_orderNumber = $orderNumber;
        return $orderNumber;
    }

    public function forward($action, $controller = null, $module = null, array $params = null)
    {
        $this->_forward = $action;
        $this->_requestParams = $params;
    }
}
