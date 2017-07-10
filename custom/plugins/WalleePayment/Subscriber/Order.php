<?php
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
            'Shopware_Modules_Order_SaveOrder_FilterDetailAttributes' => 'onSaveOrder',
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
                /* @var OrderTransactionMapping $orderTransactionMapping */
                $orderTransactionMapping = $this->modelManager->getRepository(OrderTransactionMapping::class)->findOneBy([
                    'orderId' => $order->getId(),
                    'shopId' => $order->getShop()->getId()
                ]);
                if (!($orderTransactionMapping instanceof OrderTransactionMapping)) {
                    $orderTransactionMapping = $this->modelManager->getRepository(OrderTransactionMapping::class)->findOneBy([
                        'temporaryId' => $this->sessionService->getSessionId(),
                        'shopId' => $order->getShop()->getId()
                    ]);
                }
                $transaction = $this->transactionService->updateTransaction($order, $orderTransactionMapping->getTransactionId(), $orderTransactionMapping->getSpaceId());
                $this->transactionInfoService->updateTransactionInfo($transaction, $order);
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
                    $orderTransactionMapping->setOrderEmailSent(true);
                    $orderTransactionMapping->setOrderEmailVariables(null);
                    $this->modelManager->persist($orderTransactionMapping);
                    $this->modelManager->flush($orderTransactionMapping);
                } elseif ($orderTransactionMapping->getOrderEmailVariables() == null) {
                    $orderTransactionMapping->setOrderEmailVariables([
                        'variables' => $args->getVariables(),
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
                /* @var OrderTransactionMapping $orderTransactionMapping */
                $orderTransactionMapping = $this->modelManager->getRepository(OrderTransactionMapping::class)->findOneBy([
                    'orderId' => $order->getId()
                ]);
                if ($orderTransactionMapping->isOrderEmailSent() || $this->registry->get('force_order_email') == null) {
                    // Disable sending of order email.
                    $args->setReturn(true);
                } else {
                    $orderTransactionMapping->setOrderEmailSent(true);
                    $orderTransactionMapping->setOrderEmailVariables(null);
                    $this->modelManager->persist($orderTransactionMapping);
                    $this->modelManager->flush($orderTransactionMapping);
                }
            }
        }
        return $args->getReturn();
    }
}
