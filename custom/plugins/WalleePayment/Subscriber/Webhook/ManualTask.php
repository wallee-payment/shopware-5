<?php
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
