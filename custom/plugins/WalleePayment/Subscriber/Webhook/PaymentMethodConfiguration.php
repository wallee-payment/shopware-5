<?php
namespace WalleePayment\Subscriber\Webhook;

use WalleePayment\Components\PaymentMethodConfiguration as PaymentMethodConfigurationService;

class PaymentMethodConfiguration extends AbstractSubscriber
{
    public static function getSubscribedEvents()
    {
        return [
            'Wallee_Payment_Webhook_PaymentMethodConfiguration' => 'handle'
        ];
    }

    /**
     *
     * @var PaymentMethodConfigurationService
     */
    private $paymentMethodConfigurationService;

    /**
     *
     * @param PaymentMethodConfigurationService $paymentMethodConfigurationService
     */
    public function __construct(PaymentMethodConfigurationService $paymentMethodConfigurationService)
    {
        $this->paymentMethodConfigurationService = $paymentMethodConfigurationService;
    }

    public function handle(\Enlight_Event_EventArgs $args)
    {
        $this->paymentMethodConfigurationService->synchronize();
    }
}
