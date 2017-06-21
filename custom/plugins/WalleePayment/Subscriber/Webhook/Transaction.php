<?php
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
     *
     * @param ContainerInterface $container
     * @param ConfigReader $configReader
     * @param ModelManager $modelManager
     * @param TransactionService $transactionService
     * @param TransactionInfoService $transactionInfoService
     * @param Registry $registry
     */
    public function __construct(ContainerInterface $container, ConfigReader $configReader, ModelManager $modelManager, TransactionService $transactionService, TransactionInfoService $transactionInfoService, Registry $registry)
    {
        parent::__construct($modelManager);
        $this->container = $container;
        $this->configReader = $configReader;
        $this->transactionService = $transactionService;
        $this->transactionInfoService = $transactionInfoService;
        $this->registry = $registry;
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
     * @return string
     */
    protected function getOrderNumber($transaction)
    {
        return $transaction->getMerchantReference();
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
            'transactionId' => $transaction->getId()
        ]);
        if ($transaction->getState() != $transactionInfo->getState()) {
            switch ($transaction->getState()) {
                case \Wallee\Sdk\Model\Transaction::STATE_AUTHORIZED:
                    $this->authorize($order, $transaction);
                    break;
                case \Wallee\Sdk\Model\Transaction::STATE_DECLINE:
                    $this->decline($order, $transaction);
                    break;
                case \Wallee\Sdk\Model\Transaction::STATE_FAILED:
                    $this->failed($order, $transaction);
                    break;
                case \Wallee\Sdk\Model\Transaction::STATE_FULFILL:
                    $this->fulfill($order, $transaction);
                    break;
                case \Wallee\Sdk\Model\Transaction::STATE_VOIDED:
                    $this->voided($order, $transaction);
                    break;
                case \Wallee\Sdk\Model\Transaction::STATE_COMPLETED:
                default:
                    // Nothing to do.
                    break;
            }
        }
        $this->transactionInfoService->updateTransactionInfo($transaction, $order);
    }

    private function authorize(Order $order, \Wallee\Sdk\Model\Transaction $transaction)
    {
        $order->setPaymentStatus($this->getStatus(Status::PAYMENT_STATE_COMPLETELY_INVOICED));
        $this->modelManager->flush($order);
        $this->sendOrderEmail($order);
    }

    private function decline(Order $order, \Wallee\Sdk\Model\Transaction $transaction)
    {
        $order->setOrderStatus($this->getStatus(Status::ORDER_STATE_CANCELLED_REJECTED));
        $this->modelManager->flush($order);
    }

    private function failed(Order $order, \Wallee\Sdk\Model\Transaction $transaction)
    {
        $order->setOrderStatus($this->getStatus(Status::ORDER_STATE_CANCELLED_REJECTED));
        $order->setPaymentStatus($this->getStatus(Status::PAYMENT_STATE_THE_PROCESS_HAS_BEEN_CANCELLED));
        $this->modelManager->flush($order);
    }

    private function fulfill(Order $order, \Wallee\Sdk\Model\Transaction $transaction)
    {
        $order->setOrderStatus($this->getStatus(Status::ORDER_STATE_READY_FOR_DELIVERY));
        $this->modelManager->flush($order);
        $this->sendOrderEmail($order);
    }

    private function voided(Order $order, \Wallee\Sdk\Model\Transaction $transaction)
    {
        $order->setPaymentStatus($this->getStatus(Status::PAYMENT_STATE_THE_PROCESS_HAS_BEEN_CANCELLED));
        $this->modelManager->flush($order);
    }

    private function getStatus($statusId)
    {
        return $this->modelManager->getRepository(Status::class)->find($statusId);
    }

    private function sendOrderEmail(Order $order)
    {
        /* @var OrderTransactionMapping $orderTransactionMapping */
        $orderTransactionMapping = $this->modelManager->getRepository(OrderTransactionMapping::class)->findOneBy([
            'orderId' => $order->getId()
        ]);
        $pluginConfig = $this->configReader->getByPluginName('WalleePayment', $order->getShop());
        $sendOrderEmail = $pluginConfig['orderEmail'];
        if ($sendOrderEmail && !$orderTransactionMapping->isOrderEmailSent()) {
            /* @var sOrder $orderModule */
            $orderModule = $this->container->get('modules')->Order();
            $sUserDataBackup = $orderModule->sUserData;
            $orderModule->sUserData = $orderTransactionMapping->getOrderEmailVariables()['sUserData'];
            try {
                $this->registry->set('force_order_email', true);
                $orderModule->sendMail($orderTransactionMapping->getOrderEmailVariables()['variables']);
                $this->registry->remove('force_order_email');
            } catch (Exception $e) {
            }
            $orderModule->sUserData = $sUserDataBackup;
        }
    }
}
