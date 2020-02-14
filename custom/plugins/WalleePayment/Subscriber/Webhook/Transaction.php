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
use WalleePayment\Models\TransactionInfo;
use WalleePayment\Components\Transaction as TransactionService;
use WalleePayment\Components\TransactionInfo as TransactionInfoService;
use Shopware\Models\Order\Status;
use WalleePayment\Models\OrderTransactionMapping;
use Symfony\Component\DependencyInjection\ContainerInterface;
use WalleePayment\Components\Registry;
use Shopware\Components\Plugin\ConfigReader;
use Psr\Log\LoggerInterface;

class Transaction extends AbstractOrderRelatedSubscriber
{
    public static function getSubscribedEvents()
    {
        return [
            'Wallee_Payment_Webhook_Transaction' => 'handle'
        ];
    }

    /**
     *
     * @var ContainerInterface
     */
    private $container;

    /**
     *
     * @var ConfigReader
     */
    private $configReader;

    /**
     *
     * @var TransactionService
     */
    private $transactionService;

    /**
     *
     * @var TransactionInfoService
     */
    private $transactionInfoService;

    /**
     *
     * @var Registry
     */
    private $registry;
    
    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     *
     * @param ContainerInterface $container
     * @param ConfigReader $configReader
     * @param ModelManager $modelManager
     * @param TransactionService $transactionService
     * @param TransactionInfoService $transactionInfoService
     * @param Registry $registry
     * @param LoggerInterface $logger
     */
    public function __construct(ContainerInterface $container, ConfigReader $configReader, ModelManager $modelManager, TransactionService $transactionService, TransactionInfoService $transactionInfoService, Registry $registry, LoggerInterface $logger)
    {
        parent::__construct($modelManager);
        $this->container = $container;
        $this->configReader = $configReader;
        $this->transactionService = $transactionService;
        $this->transactionInfoService = $transactionInfoService;
        $this->registry = $registry;
        $this->logger = $logger;
    }

    /**
     *
     * @param WebhookRequest $request
     * @return \Wallee\Sdk\Model\Transaction
     */
    protected function loadEntity(WebhookRequest $request)
    {
        return $this->transactionService->getTransaction($request->getSpaceId(), $request->getEntityId());
    }

    /**
     *
     * @param \Wallee\Sdk\Model\Transaction $transaction
     * @return int
     */
    protected function getTransactionId($transaction)
    {
        return $transaction->getId();
    }

    /**
     *
     * @param Order $order
     * @param \Wallee\Sdk\Model\Transaction $transaction
     */
    protected function handleOrderRelatedInner(Order $order, $transaction)
    {
        /* @var TransactionInfo $transactionInfo */
        $transactionInfo = $this->modelManager->getRepository(TransactionInfo::class)->findOneBy([
            'spaceId' => $transaction->getLinkedSpaceId(),
            'transactionId' => $transaction->getId()
        ]);
        if (!($transactionInfo instanceof TransactionInfo)) {
            $this->transactionInfoService->updateTransactionInfoByOrder($transaction, $order);
            $this->processTransaction($order, $transaction);
        } elseif ($transaction->getState() != $transactionInfo->getState()) {
            $this->processTransaction($order, $transaction);
        }
    }

    private function processTransaction(Order $order, $transaction)
    {
        switch ($transaction->getState()) {
            case \Wallee\Sdk\Model\TransactionState::AUTHORIZED:
                $this->authorize($order, $transaction);
                break;
            case \Wallee\Sdk\Model\TransactionState::DECLINE:
                $this->decline($order, $transaction);
                break;
            case \Wallee\Sdk\Model\TransactionState::FAILED:
                $this->failed($order, $transaction);
                break;
            case \Wallee\Sdk\Model\TransactionState::FULFILL:
                $this->fulfill($order, $transaction);
                break;
            case \Wallee\Sdk\Model\TransactionState::VOIDED:
                $this->voided($order, $transaction);
                break;
            case \Wallee\Sdk\Model\TransactionState::COMPLETED:
                $this->complete($order, $transaction);
                break;
            default:
                break;
        }
    }

    private function authorize(Order $order, \Wallee\Sdk\Model\Transaction $transaction)
    {
        $order->setPaymentStatus($this->getStatus(Status::PAYMENT_STATE_RESERVED));
        $order->setOrderStatus($this->getStatus($this->getAuthorizedOrderStatusId($order)));
        $this->modelManager->flush($order);
        $this->sendOrderEmail($order);
        $this->transactionInfoService->updateTransactionInfoByOrder($transaction, $order);
    }
    
    private function complete(Order $order, \Wallee\Sdk\Model\Transaction $transaction)
    {
        if ($order->getPaymentStatus()->getId() == Status::PAYMENT_STATE_RESERVED) {
            $order->setPaymentStatus($this->getStatus(Status::PAYMENT_STATE_COMPLETELY_INVOICED));
        }
        $order->setOrderStatus($this->getStatus($this->getCompletedOrderStatusId($order)));
        $this->modelManager->flush($order);
        $this->transactionInfoService->updateTransactionInfoByOrder($transaction, $order);
    }

    private function decline(Order $order, \Wallee\Sdk\Model\Transaction $transaction)
    {
        $order->setOrderStatus($this->getStatus($this->getCancelledOrderStatusId($order)));
        $this->modelManager->flush($order);
        $this->transactionInfoService->updateTransactionInfoByOrder($transaction, $order);
    }

    private function failed(Order $order, \Wallee\Sdk\Model\Transaction $transaction)
    {
        try {
            $pluginConfig = $this->configReader->getByPluginName('WalleePayment', $order->getShop());
            if ((boolean) $pluginConfig['orderRemoveFailed']) {
                $this->transactionInfoService->updateTransactionInfoByOrder($transaction, $order);
                $this->modelManager->remove($order);
                $this->modelManager->flush();
            } else {
                $order->setOrderStatus($this->getStatus($this->getCancelledOrderStatusId($order)));
                $order->setPaymentStatus($this->getStatus(Status::PAYMENT_STATE_THE_PROCESS_HAS_BEEN_CANCELLED));
                $this->modelManager->flush($order);
                $this->transactionInfoService->updateTransactionInfoByOrder($transaction, $order);
            }
        } catch (\Doctrine\ORM\EntityNotFoundException $e) {
            // The order was already deleted, so nothing more needs to be done.
        }
    }

    private function fulfill(Order $order, \Wallee\Sdk\Model\Transaction $transaction)
    {
        if ($order->getPaymentStatus()->getId() == Status::PAYMENT_STATE_RESERVED) {
            $order->setPaymentStatus($this->getStatus(Status::PAYMENT_STATE_COMPLETELY_INVOICED));
        }
        $order->setOrderStatus($this->getStatus($this->getFulfillOrderStatusId($order)));
        $this->modelManager->flush($order);
        $this->sendOrderEmail($order);
        $this->transactionInfoService->updateTransactionInfoByOrder($transaction, $order);
    }

    private function voided(Order $order, \Wallee\Sdk\Model\Transaction $transaction)
    {
        $order->setOrderStatus($this->getStatus($this->getVoidOrderStatusId($order)));
        $order->setPaymentStatus($this->getStatus(Status::PAYMENT_STATE_THE_PROCESS_HAS_BEEN_CANCELLED));
        $this->modelManager->flush($order);
        $this->transactionInfoService->updateTransactionInfoByOrder($transaction, $order);
    }
    
    private function getCancelledOrderStatusId(Order $order)
    {
        $pluginConfig = $this->configReader->getByPluginName('WalleePayment', $order->getShop());
        $status = $pluginConfig['orderStatusCancelled'];
        if ($status === null || $status === '' || !is_numeric($status)) {
            return Status::ORDER_STATE_CANCELLED_REJECTED;
        } else {
            return (int)$status;
        }
    }
    
    private function getAuthorizedOrderStatusId(Order $order)
    {
        $pluginConfig = $this->configReader->getByPluginName('WalleePayment', $order->getShop());
        $status = $pluginConfig['orderStatusAuthorized'];
        if ($status === null || $status === '' || !is_numeric($status)) {
            return Status::ORDER_STATE_CLARIFICATION_REQUIRED;
        } else {
            return (int)$status;
        }
    }
    
    private function getVoidOrderStatusId(Order $order)
    {
        $pluginConfig = $this->configReader->getByPluginName('WalleePayment', $order->getShop());
        $status = $pluginConfig['orderStatusVoid'];
        if ($status === null || $status === '' || !is_numeric($status)) {
            return Status::ORDER_STATE_CANCELLED_REJECTED;
        } else {
            return (int)$status;
        }
    }
    
    private function getCompletedOrderStatusId(Order $order)
    {
        $pluginConfig = $this->configReader->getByPluginName('WalleePayment', $order->getShop());
        $status = $pluginConfig['orderStatusCompleted'];
        if ($status === null || $status === '' || !is_numeric($status)) {
            return Status::ORDER_STATE_CLARIFICATION_REQUIRED;
        } else {
            return (int)$status;
        }
    }
    
    private function getFulfillOrderStatusId(Order $order)
    {
        $pluginConfig = $this->configReader->getByPluginName('WalleePayment', $order->getShop());
        $status = $pluginConfig['orderStatusFulfill'];
        if ($status === null || $status === '' || !is_numeric($status)) {
            return Status::ORDER_STATE_READY_FOR_DELIVERY;
        } else {
            return (int)$status;
        }
    }
    
    private function getStatus($statusId)
    {
        return $this->modelManager->getRepository(Status::class)->find($statusId);
    }

    private function sendOrderEmail(Order $order)
    {
        /**
         * @var \Shopware\Models\Shop\Shop $shopBackup
         */
        $shopBackup = $this->container->get('shop');
        
        $shop = $order->getLanguageSubShop();
        $shop->registerResources();
        
        $pluginConfig = $this->configReader->getByPluginName('WalleePayment', $order->getShop());
        $sendOrderEmail = $pluginConfig['orderEmail'];
        $orderEmailData = $this->modelManager->getRepository(OrderTransactionMapping::class)->createNamedQuery('getOrderEmailData')->setParameter('orderId', $order->getId())->getResult();
        if ($sendOrderEmail && (!isset($orderEmailData[0]['orderEmailSent']) || !$orderEmailData[0]['orderEmailSent'])) {
            /* @var \sOrder $orderModule */
            $orderModule = $this->container->get('modules')->Order();
            $sUserDataBackup = $orderModule->sUserData;
            $orderModule->sUserData = $orderEmailData[0]['orderEmailVariables']['sUserData'];
            try {
                $this->registry->set('force_order_email', true);
                $orderModule->sendMail($orderEmailData[0]['orderEmailVariables']['variables']);
                $this->registry->remove('force_order_email');
            } catch (\Exception $e) {
                $this->logger->critical($e);
            }
            $orderModule->sUserData = $sUserDataBackup;
        }
        
        $shopBackup->registerResources();
    }
}
