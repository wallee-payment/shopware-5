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
