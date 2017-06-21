<?php
namespace WalleePayment\Subscriber;

use Enlight\Event\SubscriberInterface;
use WalleePayment\Components\Webhook as WebhookService;

class Webhook implements SubscriberInterface
{

    /**
     *
     * @var WebhookService
     */
    private $webhookService;

    public static function getSubscribedEvents()
    {
        return [
            'Wallee_Payment_Config_Synchronize' => 'onSynchronize'
        ];
    }

    /**
     * Constructor.
     *
     * @param WebhookService $webhookService
     */
    public function __construct(WebhookService $webhookService)
    {
        $this->webhookService = $webhookService;
    }

    public function onSynchronize()
    {
        $this->webhookService->install();
    }
}
