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

namespace WalleePayment\Subscriber;

use Enlight\Event\SubscriberInterface;
use WalleePayment\Components\PaymentMethodConfiguration as PaymentMethodConfigurationService;

class PaymentMethodConfiguration implements SubscriberInterface
{

    /**
     *
     * @var PaymentMethodConfigurationService
     */
    private $paymentMethodConfigurationService;

    public static function getSubscribedEvents()
    {
        return [
            'Wallee_Payment_Config_Synchronize' => 'onSynchronize'
        ];
    }

    /**
     * Constructor.
     *
     * @param PaymentMethodConfigurationService $paymentMethodConfigurationService
     */
    public function __construct(PaymentMethodConfigurationService $paymentMethodConfigurationService)
    {
        $this->paymentMethodConfigurationService = $paymentMethodConfigurationService;
    }

    public function onSynchronize()
    {
        $this->paymentMethodConfigurationService->synchronize();
    }
}
