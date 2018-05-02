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

namespace WalleePayment\Subscriber\Webhook;

use WalleePayment\Components\Webhook\Request as WebhookRequest;
use Shopware\Components\Model\ModelManager;
use Shopware\Models\Order\Order;
use Shopware\Models\Order\Status;
use WalleePayment\Components\ApiClient;
use Wallee\Sdk\Service\TransactionInvoiceService;

class TransactionInvoice extends AbstractOrderRelatedSubscriber
{
    public static function getSubscribedEvents()
    {
        return [
            'Wallee_Payment_Webhook_TransactionInvoice' => 'handle'
        ];
    }

    /**
     *
     * @var TransactionInvoiceService
     */
    private $transactionInvoiceService;

    /**
     *
     * @param ModelManager $modelManager
     * @param ApiClient $apiClient
     */
    public function __construct(ModelManager $modelManager, ApiClient $apiClient)
    {
        parent::__construct($modelManager);
        $this->transactionInvoiceService = new TransactionInvoiceService($apiClient->getInstance());
    }

    /**
     *
     * @param WebhookRequest $request
     */
    protected function loadEntity(WebhookRequest $request)
    {
        return $this->callApi($this->transactionInvoiceService->getApiClient(), function () use ($request) {
            return $this->transactionInvoiceService->read($request->getSpaceId(), $request->getEntityId());
        });
    }

    /**
     *
     * @param \Wallee\Sdk\Model\TransactionInvoice $transactionInvoice
     * @return string
     */
    protected function getOrderNumber($transactionInvoice)
    {
        return $transactionInvoice->getCompletion()
            ->getLineItemVersion()
            ->getTransaction()
            ->getMerchantReference();
    }

    /**
     *
     * @param \Wallee\Sdk\Model\TransactionInvoice $transactionInvoice
     * @return int
     */
    protected function getTransactionId($transactionInvoice)
    {
        return $transactionInvoice->getLinkedTransaction();
    }

    /**
     *
     * @param Order $order
     * @param \Wallee\Sdk\Model\TransactionInvoice $transactionInvoice
     */
    protected function handleOrderRelatedInner(Order $order, $transactionInvoice)
    {
        switch ($transactionInvoice->getState()) {
            case \Wallee\Sdk\Model\TransactionInvoiceState::NOT_APPLICABLE:
                $this->notApplicable($order, $transactionInvoice);
                break;
            case \Wallee\Sdk\Model\TransactionInvoiceState::PAID:
                $this->paid($order, $transactionInvoice);
                break;
            case \Wallee\Sdk\Model\TransactionInvoiceState::DERECOGNIZED:
                $this->derecognized($order, $transactionInvoice);
                break;
            default:
                // Nothing to do.
                break;
        }
    }

    private function notApplicable(Order $order, \Wallee\Sdk\Model\TransactionInvoice $transactionInvoice)
    {
        $order->setClearedDate($transactionInvoice->getCreatedOn());
        $order->setPaymentStatus($this->getStatus(Status::PAYMENT_STATE_COMPLETELY_PAID));
        $this->modelManager->flush($order);
    }

    private function paid(Order $order, \Wallee\Sdk\Model\TransactionInvoice $transactionInvoice)
    {
        $order->setClearedDate($transactionInvoice->getPaidOn());
        $order->setPaymentStatus($this->getStatus(Status::PAYMENT_STATE_COMPLETELY_PAID));
        $this->modelManager->flush($order);
    }

    private function derecognized(Order $order, \Wallee\Sdk\Model\TransactionInvoice $transactionInvoice)
    {
        $order->setPaymentStatus($this->getStatus(Status::PAYMENT_STATE_REVIEW_NECESSARY));
        $this->modelManager->flush($order);
    }

    private function getStatus($statusId)
    {
        return $this->modelManager->getRepository(Status::class)->find($statusId);
    }
}
