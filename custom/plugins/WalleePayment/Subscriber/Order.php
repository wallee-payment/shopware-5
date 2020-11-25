<?php

/**
 * wallee Shopware 5
 *
 * This Shopware 5 extension enables to process payments with wallee (https://www.wallee.com/).
 *
 * @package Wallee_Payment
 * @author wallee AG (http://www.wallee.com/)
 * @license http://www.apache.org/licenses/LICENSE-2.0  Apache Software License (ASL 2.0)
 */

namespace WalleePayment\Subscriber;

use Enlight\Event\SubscriberInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Shopware\Components\Model\ModelManager;
use Shopware\Models\Plugin\Plugin;
use WalleePayment\Components\Transaction as TransactionService;
use WalleePayment\Components\TransactionInfo as TransactionInfoService;
use WalleePayment\Components\Session as SessionService;
use Shopware\Models\Order\Order as OrderModel;
use WalleePayment\Models\OrderTransactionMapping;
use WalleePayment\Components\Registry;
use Shopware\Components\Plugin\ConfigReader;
use Shopware\Models\Order\Status;
use Shopware\Models\Payment\Payment;
use Shopware\Models\Shop\Shop;

class Order implements SubscriberInterface
{

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
     * @var ModelManager
     */
    private $modelManager;

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
     * @var SessionService
     */
    private $sessionService;

    /**
     *
     * @var Registry
     */
    private $registry;

    public static function getSubscribedEvents()
    {
        return [
            'Shopware_Modules_Order_SaveOrder_FilterParams' => 'onFilterParams',
            'Shopware_Modules_Order_SaveOrder_ProcessDetails' => 'onSaveOrder',
            'Shopware_Modules_Order_SendMail_Create' => 'onOrderCreateMail',
            'Shopware_Modules_Order_SendMail_Send' => 'onOrderSendMail'
        ];
    }

    /**
     * Constructor.
     *
     * @param ContainerInterface $container
     * @param ConfigReader $configReader
     * @param ModelManager $modelManager
     * @param TransactionService $transactionService
     * @param TransactionInfoService $transactionInfoService
     * @param SessionService $sessionService
     * @param Registry $registry
     */
    public function __construct(ContainerInterface $container, ConfigReader $configReader, ModelManager $modelManager, TransactionService $transactionService, TransactionInfoService $transactionInfoService, SessionService $sessionService, Registry $registry)
    {
        $this->container = $container;
        $this->configReader = $configReader;
        $this->modelManager = $modelManager;
        $this->transactionService = $transactionService;
        $this->transactionInfoService = $transactionInfoService;
        $this->sessionService = $sessionService;
        $this->registry = $registry;
    }
    
    public function onFilterParams(\Enlight_Event_EventArgs $args)
    {
        $params = $args->getReturn();
        $paymentId = $params['paymentID'];
        if ($paymentId != null) {
            $payment = $this->modelManager->find(Payment::class, $paymentId);
            /* @var Plugin $plugin */
            $plugin = $this->modelManager->getRepository(Plugin::class)->findOneBy([
                'name' => $this->container->getParameter('wallee_payment.plugin_name')
            ]);
            if ($payment instanceof \Shopware\Models\Payment\Payment && $plugin->getId() == $payment->getPluginId()) {
                $shop = $this->modelManager->getRepository(Shop::class)->find($params['subshopID']);
                $params['status'] = $this->getPendingOrderStatusId($shop);
                $args->setReturn($params);
            }
        }
        return $args->getReturn();
    }

    public function onSaveOrder(\Enlight_Event_EventArgs $args)
    {
        $orderNumber = $args->getSubject()->sOrderNumber;
        /* @var OrderModel $order */
        $order = $this->modelManager->getRepository(OrderModel::class)->findOneBy([
            'number' => $orderNumber
        ]);
        if ($order instanceof OrderModel) {
            /* @var Plugin $plugin */
            $plugin = $this->modelManager->getRepository(Plugin::class)->findOneBy([
                'name' => $this->container->getParameter('wallee_payment.plugin_name')
            ]);
            if ($order->getPayment() instanceof \Shopware\Models\Payment\Payment && $plugin->getId() == $order->getPayment()->getPluginId()) {
                //$order->setOrderStatus($this->getStatus($this->getPendingOrderStatusId($order)));
                //$this->modelManager->flush($order);
                /* @var OrderTransactionMapping $orderTransactionMapping */
                $orderTransactionMapping = $this->modelManager->getRepository(OrderTransactionMapping::class)->findOneBy([
                    'orderId' => $order->getId()
                ]);
                if (!($orderTransactionMapping instanceof OrderTransactionMapping)) {
                    $orderTransactionMapping = $this->modelManager->getRepository(OrderTransactionMapping::class)->findOneBy([
                        'temporaryId' => $this->sessionService->getSessionId()
                    ]);
                }
                if ($orderTransactionMapping instanceof OrderTransactionMapping) {
                    $this->transactionService->updateTransaction($order, $orderTransactionMapping->getTransactionId(), $orderTransactionMapping->getSpaceId(), true);
                } else {
                    $this->transactionService->createTransaction($order, true);
                }
            }
        }
    }

    public function onOrderCreateMail(\Enlight_Event_EventArgs $args)
    {
        $context = $args->getContext();
        /* @var OrderModel $order */
        $order = $this->modelManager->getRepository('Shopware\Models\Order\Order')->findOneBy(array(
            'number' => $context['sOrderNumber']
        ));
        if ($order instanceof OrderModel) {
            /* @var Plugin $plugin */
            $plugin = $this->modelManager->getRepository(Plugin::class)->findOneBy([
                'name' => $this->container->getParameter('wallee_payment.plugin_name')
            ]);
            if ($order->getPayment() instanceof \Shopware\Models\Payment\Payment && $plugin->getId() == $order->getPayment()->getPluginId()) {
                /* @var OrderTransactionMapping $orderTransactionMapping */
                $orderTransactionMapping = $this->modelManager->getRepository(OrderTransactionMapping::class)->findOneBy([
                    'orderId' => $order->getId()
                ]);
                $pluginConfig = $this->configReader->getByPluginName('WalleePayment', $order->getShop());
                $sendOrderEmail = $pluginConfig['orderEmail'];
                if (!$sendOrderEmail) {
                    $this->modelManager->getRepository(OrderTransactionMapping::class)->createNamedQuery('updateOrderEmailSent')->setParameter('orderId', $order->getId())->execute();
                } elseif ($orderTransactionMapping->getOrderEmailVariables() == null) {
                    $variables = $args->getVariables();
                    $variables['sBookingID'] = $orderTransactionMapping->getTransactionId();
                    $orderTransactionMapping->setOrderEmailVariables([
                        'variables' => $variables,
                        'sUserData' => $args->getSubject()->sUserData
                    ]);
                    $this->modelManager->persist($orderTransactionMapping);
                    $this->modelManager->flush($orderTransactionMapping);
                }
            }
        }
    }

    public function onOrderSendMail(\Enlight_Event_EventArgs $args)
    {
        $context = $args->getContext();
        /* @var OrderModel $order */
        $order = $this->modelManager->getRepository('Shopware\Models\Order\Order')->findOneBy(array(
            'number' => $context['sOrderNumber']
        ));
        if ($order instanceof OrderModel) {
            /* @var Plugin $plugin */
            $plugin = $this->modelManager->getRepository(Plugin::class)->findOneBy([
                'name' => $this->container->getParameter('wallee_payment.plugin_name')
            ]);
            if ($order->getPayment() instanceof \Shopware\Models\Payment\Payment && $plugin->getId() == $order->getPayment()->getPluginId()) {
                $orderEmailData = $this->modelManager->getRepository(OrderTransactionMapping::class)->createNamedQuery('getOrderEmailSent')->setParameter('orderId', $order->getId())->getResult();
                if ((isset($orderEmailData[0]['orderEmailSent']) && $orderEmailData[0]['orderEmailSent']) || $this->registry->get('force_order_email') == null) {
                    // Disable sending of order email.
                    $args->setReturn(true);
                } else {
                    $this->modelManager->getRepository(OrderTransactionMapping::class)->createNamedQuery('updateOrderEmailSent')->setParameter('orderId', $order->getId())->execute();
                }
            }
        }
        return $args->getReturn();
    }
    
    private function getPendingOrderStatusId(Shop $shop)
    {
        $pluginConfig = $this->configReader->getByPluginName('WalleePayment', $shop);
        $status = $pluginConfig['orderStatusPending'];
        if ($status === null || $status === '' || !is_numeric($status)) {
            return Status::ORDER_STATE_CLARIFICATION_REQUIRED;
        } else {
            return (int)$status;
        }
    }
}
