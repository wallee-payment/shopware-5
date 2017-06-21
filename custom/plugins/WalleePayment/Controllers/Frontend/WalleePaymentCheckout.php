<?php

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
