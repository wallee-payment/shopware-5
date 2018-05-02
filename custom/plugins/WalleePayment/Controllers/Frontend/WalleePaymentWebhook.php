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

use WalleePayment\Components\Webhook\Request as WebhookRequest;
use Shopware\Components\CSRFWhitelistAware;
use WalleePayment\Components\Controller\Frontend;

class Shopware_Controllers_Frontend_WalleePaymentWebhook extends Frontend implements CSRFWhitelistAware
{
    public function getWhitelistedCSRFActions()
    {
        return [
            'handle'
        ];
    }

    public function preDispatch()
    {
        parent::preDispatch();

        $this->Front()->throwExceptions(true);

        $this->Front()
            ->Plugins()
            ->Json()
            ->setRenderer(true);
    }

    public function handleAction()
    {
        $this->Response()->setHttpResponseCode(500);
        try {
            $request = new WebhookRequest(json_decode($this->Request()->getRawBody()));
            $this->get('events')->notify('Wallee_Payment_Webhook_' . $request->getListenerEntityTechnicalName(), [
                'request' => $request
            ]);
            if (! $this->Response()->isException()) {
                $this->Response()->setHttpResponseCode(200);
            }
        } catch (\WalleePayment\Components\Webhook\Exception $e) {
            $this->Response()->setHttpResponseCode(500);
        }
    }
}
