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

use WalleePayment\Components\Controller\Frontend;

class Shopware_Controllers_Frontend_WalleePaymentPay extends Frontend
{
    public function indexAction()
    {
        $namespace = $this->container->get('snippets')->getNamespace('frontend/wallee_payment/main');
        return $this->forward('confirm', 'checkout', null, ['walleeErrors' => $namespace->get('checkout/javascript_error', 'The payment information could not be sent to wallee. Either certain Javascript files were not included or a Javascript error occurred.')]);
    }
}
