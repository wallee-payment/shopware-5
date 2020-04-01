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

namespace WalleePayment\Components;

use Shopware\Components\Model\ModelManager;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Wallee\Sdk\Service\RefundService;
use Wallee\Sdk\Model\EntityQuery;
use Shopware\Models\Order\Order;

class Refund extends AbstractService
{

    /**
     *
     * @var ModelManager
     */
    private $modelManager;

    /**
     *
     * @var RefundService
     */
    private $refundService;

    /**
     * Constructor.
     *
     * @param ContainerInterface $container
     * @param ModelManager $modelManager
     * @param ApiClient $apiClient
     */
    public function __construct(ContainerInterface $container, ModelManager $modelManager, ApiClient $apiClient)
    {
        parent::__construct($container);
        $this->modelManager = $modelManager;
        $this->refundService = new RefundService($apiClient->getInstance());
    }

    /**
     *
     * @param int $spaceId
     * @param int $transactionId
     * @return \Wallee\Sdk\Model\Refund[]
     */
    public function getRefunds($spaceId, $transactionId)
    {
        return $this->callApi($this->refundService->getApiClient(), function () use ($spaceId, $transactionId) {
            $query = new EntityQuery();
            $query->setFilter($this->createEntityFilter('transaction.id', $transactionId));
            $query->setOrderBys([
                $this->createEntityOrderBy('createdOn', \Wallee\Sdk\Model\EntityQueryOrderByType::DESC)
            ]);
            $query->setNumberOfEntities(50);
            return $this->refundService->search($spaceId, $query);
        });
    }

    /**
     *
     * @param \Wallee\Sdk\Model\TransactionInvoice $invoice
     * @param \Wallee\Sdk\Model\Refund[] $refunds
     * @return \Wallee\Sdk\Model\LineItem[]
     */
    public function getRefundBaseLineItems(\Wallee\Sdk\Model\TransactionInvoice $invoice = null, array $refunds = [])
    {
        $refund = $this->getLastSuccessfulRefund($refunds);
        if ($refund) {
            return $refund->getReducedLineItems();
        } elseif ($invoice != null) {
            return $invoice->getLineItems();
        } else {
            return [];
        }
    }

    /**
     *
     * @param \Wallee\Sdk\Model\Refund[] $refunds
     */
    private function getLastSuccessfulRefund(array $refunds)
    {
        foreach ($refunds as $refund) {
            if ($refund->getState() == \Wallee\Sdk\Model\RefundState::SUCCESSFUL) {
                return $refund;
            }
        }
        return false;
    }

    /**
     *
     * @param Order $order
     * @param \Wallee\Sdk\Model\Transaction $transaction
     * @param array $reductions
     */
    public function createRefund(Order $order, \Wallee\Sdk\Model\Transaction $transaction, array $reductions)
    {
        $refund = new \Wallee\Sdk\Model\RefundCreate();
        $refund->setExternalId(uniqid($order->getNumber() . '-'));
        $refund->setReductions($reductions);
        $refund->setTransaction($transaction->getId());
        $refund->setType(\Wallee\Sdk\Model\RefundType::MERCHANT_INITIATED_ONLINE);
        return $refund;
    }

    /**
     *
     * @param int $spaceId
     * @param \Wallee\Sdk\Model\RefundCreate $refundRequest
     * @return \Wallee\Sdk\Model\Refund
     */
    public function refund($spaceId, \Wallee\Sdk\Model\RefundCreate $refundRequest)
    {
        return $this->refundService->refund($spaceId, $refundRequest);
    }
}
