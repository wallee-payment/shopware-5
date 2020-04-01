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

        $this->Front()->Plugins()->ViewRenderer()->setNoRender();
    }

    public function handleAction()
    {
        $this->Response()->setHttpResponseCode(500);
        try {
            $request = $this->getWebhookRequest();
            $this->get('events')->notify('Wallee_Payment_Webhook_' . $request->getListenerEntityTechnicalName(), [
                'request' => $request
            ]);
            if (! $this->Response()->isException()) {
                $this->Response()->setHttpResponseCode(200);
            }
        } catch (\WalleePayment\Components\Webhook\Exception $e) {
            $this->get('corelogger')->critical($e);
            echo $e->getMessage();
        } catch (\Exception $e) {
            $this->get('corelogger')->critical($e);
            echo $e->getMessage();
        }
    }
    
    private function getWebhookRequest() {
        $data = $this->Request()->getRawBody();
        if (empty($data)) {
            throw new \Exception('Empty request data.');
        }
        $decodedData = json_decode($data);
        if (json_last_error() != JSON_ERROR_NONE) {
            throw new \Exception('Invalid request data.');
        }
        return new WebhookRequest($decodedData);
    }
}
