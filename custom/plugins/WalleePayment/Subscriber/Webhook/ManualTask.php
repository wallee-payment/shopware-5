<?php

/**
 * Wallee Shopware
 *
 * This Shopware extension enables to process payments with Wallee (https://wallee.com/).
 *
 * @package Wallee_Payment
 * @author customweb GmbH (http://www.customweb.com/)
 * @license http://www.apache.org/licenses/LICENSE-2.0  Apache Software License (ASL 2.0)
 * @link https://github.com/wallee-payment/shopware
 */

namespace WalleePayment\Subscriber\Webhook;

use WalleePayment\Components\ManualTask as ManualTaskService;

class ManualTask extends AbstractSubscriber
{
    public static function getSubscribedEvents()
    {
        return [
            'Wallee_Payment_Webhook_ManualTask' => 'handle'
        ];
    }

    /**
     *
     * @var ManualTaskService
     */
    private $manualTaskService;

    /**
     *
     * @param ManualTaskService $manualTaskService
     */
    public function __construct(ManualTaskService $manualTaskService)
    {
        $this->manualTaskService = $manualTaskService;
    }

    public function handle(\Enlight_Event_EventArgs $args)
    {
        $this->manualTaskService->update();
    }
}
