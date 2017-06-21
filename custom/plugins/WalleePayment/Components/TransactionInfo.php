<?php
namespace WalleePayment\Components;

use \Wallee\Sdk\Model\Transaction as TransactionModel;
use Shopware\Components\Model\ModelManager;
use Shopware\Components\Plugin\ConfigReader;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Wallee\Sdk\Model\PaymentMethod;
use Wallee\Sdk\Model\Refund as RefundModel;
use Wallee\Sdk\Model\TransactionInvoice;
use Wallee\Sdk\Model\TransactionLineItemVersion;
use WalleePayment\Components\ArrayBuilder\TransactionInfo as TransactionInfoArrayBuilder;
use WalleePayment\Models\TransactionInfo as TransactionInfoModel;
use WalleePayment\Models\PaymentMethodConfiguration as PaymentMethodConfigurationModel;
use Shopware\Models\Order\Order;

class TransactionInfo extends AbstractService
{
    /**
     *
     * @var ModelManager
     */
    private $modelManager;

    /**
     *
     * @var ConfigReader
     */
    private $configReader;

    /**
     *
     * @var \Wallee\Sdk\ApiClient
     */
    private $apiClient;

    /**
     * Constructor.
     *
     * @param ContainerInterface $container
     * @param ModelManager $modelManager
     * @param ConfigReader $configReader
     * @param ApiClient $apiClient
     * @param LineItem $lineItem
     */
    public function __construct(ContainerInterface $container, ModelManager $modelManager, ConfigReader $configReader, ApiClient $apiClient)
    {
        parent::__construct($container);
        $this->container = $container;
        $this->modelManager = $modelManager;
        $this->configReader = $configReader;
        $this->apiClient = $apiClient->getInstance();
    }

    /**
     * Stores the transaction data in the database.
     *
     * @param \Wallee\Sdk\Model\Transaction $transaction
     * @param Order $order
     * @return TransactionInfoModel
     */
    public function updateTransactionInfo(\Wallee\Sdk\Model\Transaction $transaction, Order $order)
    {
        $info = $this->modelManager->getRepository(TransactionInfoModel::class)->findOneBy([
            'spaceId' => $transaction->getLinkedSpaceId(),
            'transactionId' => $transaction->getId()
        ]);
        if (! ($info instanceof TransactionInfoModel)) {
            $info = new TransactionInfoModel();
        }
        $info->setTransactionId($transaction->getId());
        $info->setAuthorizationAmount($transaction->getAuthorizationAmount());
        $info->setOrderId($order->getId());
        $info->setShopId($order->getShop()->getId());
        $info->setState($transaction->getState());
        $info->setSpaceId($transaction->getLinkedSpaceId());
        $info->setSpaceViewId($transaction->getSpaceViewId());
        $info->setLanguage($transaction->getLanguage());
        $info->setCurrency($transaction->getCurrency());
        $info->setConnectorId($transaction->getPaymentConnectorConfiguration() != null ? $transaction->getPaymentConnectorConfiguration()
            ->getConnector() : null);
        $info->setPaymentMethodId($transaction->getPaymentConnectorConfiguration() != null && $transaction->getPaymentConnectorConfiguration()
            ->getPaymentMethodConfiguration() != null ? $transaction->getPaymentConnectorConfiguration()
            ->getPaymentMethodConfiguration()
            ->getPaymentMethod() : null);
        $info->setImage($this->getPaymentMethodImage($transaction, $order));
        $info->setLabels($this->getTransactionLabels($transaction));
        if ($transaction->getState() == \Wallee\Sdk\Model\Transaction::STATE_FAILED || $transaction->getState() == \Wallee\Sdk\Model\Transaction::STATE_DECLINE) {
            $failedChargeAttempt = $this->getFailedChargeAttempt($transaction->getLinkedSpaceId(), $transaction->getId());
            if ($failedChargeAttempt != null && $failedChargeAttempt->getFailureReason() != null) {
                $info->setFailureReason($failedChargeAttempt->getFailureReason()->getDescription());
                $info->setUserFailureMessage($failedChargeAttempt->getUserFailureMessage());
            }
        }
        $this->modelManager->persist($info);
        $this->modelManager->flush($info);
        return $info;
    }

    /**
     * Returns an array of the transaction's labels.
     *
     * @param \Wallee\Sdk\Model\Transaction $transaction
     * @return string[]
     */
    private function getTransactionLabels(\Wallee\Sdk\Model\Transaction $transaction)
    {
        $chargeAttempt = $this->getChargeAttempt($transaction);
        if ($chargeAttempt != null) {
            $labels = array();
            foreach ($chargeAttempt->getLabels() as $label) {
                $labels[$label->getDescriptor()->getId()] = $label->getContentAsString();
            }

            return $labels;
        } else {
            return array();
        }
    }

    /**
     * Returns the successful charge attempt of the transaction.
     *
     * @return \Wallee\Sdk\Model\ChargeAttempt
     */
    private function getChargeAttempt(\Wallee\Sdk\Model\Transaction $transaction)
    {
        $chargeAttemptService = new \Wallee\Sdk\Service\ChargeAttemptService($this->apiClient);
        $query = new \Wallee\Sdk\Model\EntityQuery();
        $filter = new \Wallee\Sdk\Model\EntityQueryFilter();
        $filter->setType(\Wallee\Sdk\Model\EntityQueryFilter::TYPE_AND);
        $filter->setChildren(array(
            $this->createEntityFilter('charge.transaction.id', $transaction->getId()),
            $this->createEntityFilter('state', \Wallee\Sdk\Model\ChargeAttempt::STATE_SUCCESSFUL)
        ));
        $query->setFilter($filter);
        $query->setNumberOfEntities(1);
        $result = $chargeAttemptService->search($transaction->getLinkedSpaceId(), $query);
        if ($result != null && ! empty($result)) {
            return current($result);
        } else {
            return null;
        }
    }

    /**
     * Returns the payment method's image.
     *
     * @param \Wallee\Sdk\Model\Transaction $transaction
     * @param Order $order
     * @return string
     */
    private function getPaymentMethodImage(\Wallee\Sdk\Model\Transaction $transaction, Order $order)
    {
        if ($transaction->getPaymentConnectorConfiguration() == null) {
            $payment = $order->getPayment();
            /* @var PaymentMethodConfigurationModel $paymentMethodConfiguration */
            $paymentMethodConfiguration = $this->modelManager->getRepository(PaymentMethodConfigurationModel::class)->findOneBy([
                'paymentId' => $payment->getId()
            ]);
            return $paymentMethodConfiguration->getImage();
        }

        /* @var \WalleePayment\Components\Provider\PaymentConnector $connectorProvider */
        $connectorProvider = $this->container->get('wallee_payment.provider.payment_connector');
        $connector = $connectorProvider->find($transaction->getPaymentConnectorConfiguration()
            ->getConnector());

        /* @var \WalleePayment\Components\Provider\PaymentMethod $methodProvider */
        $methodProvider = $this->container->get('wallee_payment.provider.payment_method');
        $method = $transaction->getPaymentConnectorConfiguration()->getPaymentMethodConfiguration() != null ? $methodProvider->find($transaction->getPaymentConnectorConfiguration()
            ->getPaymentMethodConfiguration()
            ->getPaymentMethod()) : null;

        if ($connector != null && $connector->getPaymentMethodBrand() != null) {
            return $connector->getPaymentMethodBrand()->getImagePath();
        } elseif ($transaction->getPaymentConnectorConfiguration()->getPaymentMethodConfiguration() != null && $transaction->getPaymentConnectorConfiguration()
                ->getPaymentMethodConfiguration()
                ->getImageResourcePath() != null) {
            return $transaction->getPaymentConnectorConfiguration()
                    ->getPaymentMethodConfiguration()
                    ->getImageResourcePath()
                    ->getPath();
        } elseif ($method != null) {
            return $method->getImagePath();
        } else {
            $payment = $order->getPayment();
                /* @var PaymentMethodConfigurationModel $paymentMethodConfiguration */
                $paymentMethodConfiguration = $this->modelManager->getRepository(PaymentMethodConfigurationModel::class)->findOneBy([
                    'paymentId' => $payment->getId()
                ]);
            return $paymentMethodConfiguration->getImage();
        }
    }

    /**
     * Returns the last failed charge attempt of the transaction.
     *
     * @param int $spaceId
     * @param int $transactionId
     * @return \Wallee\Sdk\Model\ChargeAttempt
     */
    private function getFailedChargeAttempt($spaceId, $transactionId)
    {
        $chargeAttemptService = new \Wallee\Sdk\Service\ChargeAttemptService($this->apiClient);
        $query = new \Wallee\Sdk\Model\EntityQuery();
        $filter = new \Wallee\Sdk\Model\EntityQueryFilter();
        $filter->setType(\Wallee\Sdk\Model\EntityQueryFilter::TYPE_AND);
        $filter->setChildren(
            array(
                $this->createEntityFilter('charge.transaction.id', $transactionId),
                $this->createEntityFilter('state', \Wallee\Sdk\Model\ChargeAttempt::STATE_FAILED)
            )
            );
        $query->setFilter($filter);
        $query->setOrderBys(
            array(
                $this->createEntityOrderBy('failedOn')
            )
            );
        $query->setNumberOfEntities(1);
        $result = $chargeAttemptService->search($spaceId, $query);
        if ($result != null && ! empty($result)) {
            return current($result);
        } else {
            return null;
        }
    }

    /**
     *
     * @param TransactionInfoModel $transactionInfo
     * @return array
     */
    public function buildTransactionInfoAsArray(TransactionInfoModel $transactionInfo)
    {
        $builder = new TransactionInfoArrayBuilder($this->container, $transactionInfo);
        $builder->setPaymentMethod($this->getPaymentMethod($transactionInfo));
        $builder->setTransaction($this->getTransaction($transactionInfo));
        $builder->setLineItemVersion($this->getLineItemVersion($transactionInfo));
        $builder->setInvoice($this->getInvoice($transactionInfo));
        $builder->setRefunds($this->getRefunds($transactionInfo));
        return $builder->build();
    }

    /**
     *
     * @param TransactionInfoModel $transactionInfo
     * @return PaymentMethod|NULL
     */
    private function getPaymentMethod(TransactionInfoModel $transactionInfo)
    {
        try {
            return $this->container->get('wallee_payment.provider.payment_method')->find($transactionInfo->getPaymentMethodId());
        } catch (\Exception $e) {
            // If payment methods cannot be loaded from Wallee, information about the payment method cannot be displayed.
            return null;
        }
    }

    /**
     *
     * @param TransactionInfoModel $transactionInfo
     * @return TransactionModel|NULL
     */
    private function getTransaction(TransactionInfoModel $transactionInfo)
    {
        try {
            return $this->container->get('wallee_payment.transaction')->getTransaction($transactionInfo->getSpaceId(), $transactionInfo->getTransactionId());
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     *
     * @param TransactionInfoModel $transactionInfo
     * @return TransactionInvoice|NULL
     */
    private function getInvoice(TransactionInfoModel $transactionInfo)
    {
        try {
            return $this->container->get('wallee_payment.invoice')->getInvoice($transactionInfo->getSpaceId(), $transactionInfo->getTransactionId());
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     *
     * @param TransactionInfoModel $transactionInfo
     * @return TransactionLineItemVersion|NULL
     */
    private function getLineItemVersion(TransactionInfoModel $transactionInfo)
    {
        try {
            return $this->container->get('wallee_payment.transaction')->getLineItemVersion($transactionInfo->getSpaceId(), $transactionInfo->getTransactionId());
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     *
     * @param TransactionInfoModel $transactionInfo
     * @return RefundModel[]|array
     */
    private function getRefunds(TransactionInfoModel $transactionInfo)
    {
        try {
            return $this->container->get('wallee_payment.refund')->getRefunds($transactionInfo->getSpaceId(), $transactionInfo->getTransactionId());
        } catch (\Exception $e) {
            return [];
        }
    }
}
