<?php
namespace WalleePayment\Components;

use Shopware\Models\Order\Order;
use Shopware\Components\Model\ModelManager;
use Symfony\Component\DependencyInjection\ContainerInterface;
use WalleePayment\Models\OrderTransactionMapping;
use WalleePayment\Components\PaymentMethodConfiguration as PaymentMethodConfigurationService;
use Shopware\Components\Plugin\ConfigReader;

class Transaction extends AbstractService
{

    /**
     *
     * @var \Wallee\Sdk\Model\Transaction[]
     */
    private static $transactionCache = array();

    /**
     *
     * @var \Wallee\Sdk\Model\PaymentMethodConfiguration[]
     */
    private static $possiblePaymentMethodCache = array();

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
     *
     * @var LineItem
     */
    private $lineItem;

    /**
     *
     * @var PaymentMethodConfigurationService
     */
    private $paymentMethodConfigurationService;

    /**
     * The transaction API service.
     *
     * @var \Wallee\Sdk\Service\TransactionService
     */
    private $transactionService;

    /**
     * Constructor.
     *
     * @param ContainerInterface $container
     * @param ModelManager $modelManager
     * @param ConfigReader $configReader
     * @param ApiClient $apiClient
     * @param LineItem $lineItem
     */
    public function __construct(ContainerInterface $container, ModelManager $modelManager, ConfigReader $configReader, ApiClient $apiClient, LineItem $lineItem, PaymentMethodConfigurationService $paymentMethodConfigurationService)
    {
        parent::__construct($container);
        $this->container = $container;
        $this->modelManager = $modelManager;
        $this->configReader = $configReader;
        $this->apiClient = $apiClient->getInstance();
        $this->lineItem = $lineItem;
        $this->paymentMethodConfigurationService = $paymentMethodConfigurationService;
        $this->transactionService = new \Wallee\Sdk\Service\TransactionService($this->apiClient);
    }

    /**
     * Returns the transaction with the given id.
     *
     * @param int $spaceId
     * @param int $transactionId
     * @return \Wallee\Sdk\Model\Transaction
     */
    public function getTransaction($spaceId, $transactionId)
    {
        return $this->transactionService->read($spaceId, $transactionId);
    }

    /**
     *
     * @param int $spaceId
     * @param int $transactionId
     */
    public function handleTransactionState($spaceId, $transactionId)
    {
        $transaction = $this->getTransaction($spaceId, $transactionId);
        $this->container->get('wallee_payment.subscriber.webhook.transaction')->process($transaction);
    }

    /**
     * Returns the transaction's latest line item version.
     *
     * @param int $spaceId
     * @param int $transactionId
     * @return \Wallee\Sdk\Model\TransactionLineItemVersion
     */
    public function getLineItemVersion($spaceId, $transactionId)
    {
        return $this->transactionService->getLatestTransactionLineItemVersion($spaceId, $transactionId);
    }

    /**
     * Updates the line items of the given transaction.
     *
     * @param int $spaceId
     * @param int $transactionId
     * @param \Wallee\Sdk\Model\LineItem[] $lineItems
     * @return \Wallee\Sdk\Model\TransactionLineItemVersion
     */
    public function updateLineItems($spaceId, $transactionId, $lineItems)
    {
        $updateRequest = new \Wallee\Sdk\Model\TransactionLineItemUpdateRequest();
        $updateRequest->setTransactionId($transactionId);
        $updateRequest->setNewLineItems($lineItems);
        return $this->transactionService->updateTransactionLineItems($spaceId, $updateRequest);
    }

    /**
     * Returns the URL to Wallee's JavaScript library that is necessary to display the payment form.
     *
     * @param Order $order
     * @return string
     */
    public function getJavaScriptUrl(Order $order)
    {
        $transaction = $this->getTransactionByOrder($order);
        return $this->transactionService->buildJavaScriptUrl($transaction->getLinkedSpaceId(), $transaction->getId());
    }

    /**
     * Returns the payment methods that can be used with the given order.
     *
     * @param Order $order
     * @return \Wallee\Sdk\Model\PaymentMethodConfiguration[]
     */
    public function getPossiblePaymentMethods(Order $order)
    {
        if (! isset(self::$possiblePaymentMethodCache[$order->getId()]) || self::$possiblePaymentMethodCache[$order->getId()] == null) {
            $transaction = $this->getTransactionByOrder($order);
            $paymentMethods = $this->transactionService->fetchPossiblePaymentMethods($transaction->getLinkedSpaceId(), $transaction->getId());

            foreach ($paymentMethods as $paymentMethod) {
                $this->paymentMethodConfigurationService->updateData($paymentMethod);
            }

            self::$possiblePaymentMethodCache[$order->getId()] = $paymentMethods;
        }

        return self::$possiblePaymentMethodCache[$order->getId()];
    }

    /**
     * Returns the transaction for the given order.
     *
     * If no transaction exists, a new one is created.
     *
     * @param Order $order
     * @return \Wallee\Sdk\Model\Transaction
     */
    public function getTransactionByOrder(Order $order)
    {
        if (! isset(self::$transactionCache[$order->getId()]) || self::$transactionCache[$order->getId()] == null) {
            $orderTransactionMapping = $this->getOrderTransactionMapping($order);
            if ($orderTransactionMapping instanceof OrderTransactionMapping) {
                $this->updateTransaction($order, $orderTransactionMapping->getTransactionId(), $orderTransactionMapping->getSpaceId());
            } else {
                $this->createTransaction($order);
            }
        }
        return self::$transactionCache[$order->getId()];
    }

    /**
     * Creates a transaction for the given order.
     *
     * @param Order $order
     * @return \Wallee\Sdk\Model\TransactionCreate
     */
    public function createTransaction(Order $order)
    {
        $transaction = new \Wallee\Sdk\Model\TransactionCreate();
        $transaction->setCustomersPresence(\Wallee\Sdk\Model\CustomersPresence::VIRTUAL_PRESENT);
        $this->assembleTransactionData($transaction, $order);

        $pluginConfig = $this->configReader->getByPluginName('WalleePayment', $order->getShop());
        $spaceId = $pluginConfig['spaceId'];

        $transaction = $this->transactionService->create($spaceId, $transaction);

        $orderTransactionMapping = new OrderTransactionMapping();
        $orderTransactionMapping->setOrder($order);
        $orderTransactionMapping->setSpaceId($transaction->getLinkedSpaceId());
        $orderTransactionMapping->setTransactionId($transaction->getId());
        $this->modelManager->persist($orderTransactionMapping);
        $this->modelManager->flush($orderTransactionMapping);

        self::$transactionCache[$order->getId()] = $transaction;
        return $transaction;
    }

    /**
     * Updates the transaction for the given order.
     *
     * If the transaction is not in pending state, a new one is created.
     *
     * @param Order $order
     * @param int $transactionId
     * @param int $spaceId
     * @return \Wallee\Sdk\Model\TransactionPending
     */
    public function updateTransaction(Order $order, $transactionId, $spaceId)
    {
        $transaction = $this->transactionService->read($spaceId, $transactionId);
        if ($transaction->getState() != \Wallee\Sdk\Model\TransactionState::PENDING) {
            return $this->createTransaction($order);
        }

        $pendingTransaction = new \Wallee\Sdk\Model\TransactionPending();
        $pendingTransaction->setId($transaction->getId());
        $pendingTransaction->setVersion($transaction->getVersion());
        $this->assembleTransactionData($pendingTransaction, $order);
        $updatedTransaction = $this->transactionService->update($spaceId, $pendingTransaction);

        /* @var OrderTransactionMapping $orderTransactionMapping */
        $orderTransactionMapping = $this->modelManager->getRepository(OrderTransactionMapping::class)->findOneBy([
            'transactionId' => $transactionId,
            'spaceId' => $spaceId
        ]);
        $orderTransactionMapping->setOrder($order);
        $this->modelManager->persist($orderTransactionMapping);
        $this->modelManager->flush($orderTransactionMapping);

        self::$transactionCache[$order->getId()] = $updatedTransaction;
        return $updatedTransaction;
    }

    /**
     * Assembles the transaction data for the given order.
     *
     * @param \Wallee\Sdk\Model\TransactionPending $transaction
     * @param Order $order
     */
    private function assembleTransactionData(\Wallee\Sdk\Model\TransactionPending $transaction, Order $order)
    {
        if ($order->getNumber() != '0') {
            $transaction->setMerchantReference($order->getNumber());
        }
        $transaction->setCurrency($order->getCurrency());
        $transaction->setBillingAddress($this->getBillingAddress($order));
        $transaction->setShippingAddress($this->getShippingAddress($order));
        $transaction->setCustomerEmailAddress($order->getCustomer()
            ->getEmail());
        $transaction->setCustomerId($order->getCustomer()
            ->getId());
        $transaction->setLanguage($this->getLanguage($order));
        if ($order->getDispatch() instanceof \Shopware\Models\Dispatch\Dispatch) {
            $transaction->setShippingMethod($this->fixLength($order->getDispatch()
                ->getName(), 200));
        }

        $pluginConfig = $this->configReader->getByPluginName('WalleePayment', $order->getShop());
        $spaceViewId = $pluginConfig['spaceViewId'];
        
        if ($transaction instanceof \Wallee\Sdk\Model\TransactionCreate) {
            $transaction->setSpaceViewId($spaceViewId);
        }

        $transaction->setLineItems($this->lineItem->collectLineItems($order));
        $transaction->setAllowedPaymentMethodConfigurations([]);
        $transaction->setSuccessUrl($this->getUrl('WalleePaymentTransaction', 'success', null, null, ['spaceId' => $pluginConfig['spaceId'], 'transactionId' => $transaction->getId()]));
        $transaction->setFailedUrl($this->getUrl('WalleePaymentTransaction', 'failure', null, null, ['spaceId' => $pluginConfig['spaceId'], 'transactionId' => $transaction->getId()]));
    }

    private function getBillingAddress(Order $order)
    {
        $billingAddressId = $this->container->get('session')->offsetGet('checkoutBillingAddressId', null);
        if (empty($billingAddressId)) {
            $billingAddressId = $order->getCustomer()
                ->getDefaultBillingAddress()
                ->getId();
        }
        $billingAddress = $this->modelManager->getRepository(\Shopware\Models\Customer\Address::class)->getOneByUser($billingAddressId, $order->getCustomer()
            ->getId());

        $address = $this->getAddress($billingAddress);
        if ($order->getCustomer()->getBirthday() instanceof \DateTime && $order->getCustomer()->getBirthday() != new \DateTime('0000-00-00')) {
            $address->setDateOfBirth($order->getCustomer()
                ->getBirthday()
                ->format(\DateTime::W3C));
        }
        $address->setEmailAddress($order->getCustomer()
            ->getEmail());
        return $address;
    }

    private function getShippingAddress(Order $order)
    {
        $shippingAddressId = $this->container->get('session')->offsetGet('checkoutShippingAddressId', null);
        if (empty($shippingAddressId)) {
            $shippingAddressId = $order->getCustomer()
                ->getDefaultShippingAddress()
                ->getId();
        }
        $shippingAddress = $this->modelManager->getRepository(\Shopware\Models\Customer\Address::class)->getOneByUser($shippingAddressId, $order->getCustomer()
            ->getId());

        $address = $this->getAddress($shippingAddress);
        $address->setEmailAddress($order->getCustomer()
            ->getEmail());
        return $address;
    }

    private function getAddress(\Shopware\Models\Customer\Address $customerAddress)
    {
        $address = new \Wallee\Sdk\Model\AddressCreate();
        $address->setSalutation($this->fixLength($customerAddress->getSalutation(), 20));
        $address->setCity($this->fixLength($customerAddress->getCity(), 100));
        $address->setCountry($customerAddress->getCountry()
            ->getIso());
        $address->setFamilyName($this->fixLength($customerAddress->getLastName(), 100));
        $address->setGivenName($this->fixLength($customerAddress->getFirstName(), 100));
        $address->setOrganizationName($this->fixLength($customerAddress->getCompany(), 100));
        $address->setPhoneNumber($customerAddress->getPhone());
        if ($customerAddress->getState() instanceof \Shopware\Models\Country\State) {
            $address->setPostalState($customerAddress->getState()
                ->getShortCode());
        }
        $address->setPostCode($this->fixLength($customerAddress->getZipCode(), 40));
        $address->setStreet($this->fixLength($customerAddress->getStreet(), 300));
        return $address;
    }

    private function getLanguage(Order $order)
    {
        return $order->getLanguageSubShop()
            ->getLocale()
            ->getLocale();
    }

    /**
     *
     * @param Order $order
     * @return OrderTransactionMapping
     */
    private function getOrderTransactionMapping(Order $order)
    {
        $filter = [
            'orderId' => $order->getId()
        ];
        if ($order->getTemporaryId() != null) {
            $filter = [
                'temporaryId' => $order->getTemporaryId(),
                'shopId' => $order->getShop()->getId()
            ];
        }
        return $this->modelManager->getRepository(OrderTransactionMapping::class)->findOneBy($filter);
    }
    
}
