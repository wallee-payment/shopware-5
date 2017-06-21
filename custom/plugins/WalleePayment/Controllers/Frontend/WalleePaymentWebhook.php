<?php
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
        $request = new WebhookRequest(json_decode($this->Request()->getRawBody()));
        $this->get('events')->notify('Wallee_Payment_Webhook_' . $request->getListenerEntityTechnicalName(), [
            'request' => $request
        ]);
        if (! $this->Response()->isException()) {
            $this->Response()->setHttpResponseCode(200);
        }
    }
}
