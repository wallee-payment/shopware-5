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

namespace WalleePayment\Subscriber;

use Enlight\Event\SubscriberInterface;
use Shopware\Models\Payment\Payment;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Shopware\Components\Model\ModelManager;
use WalleePayment\Components\Transaction as TransactionService;
use WalleePayment\Components\Session as SessionService;
use Shopware\Models\Order\Order as OrderModel;
use Shopware\Models\Plugin\Plugin;
use WalleePayment\Models\PaymentMethodConfiguration as PaymentMethodConfigurationModel;
use Shopware\Components\Plugin\ConfigReader;

class RiskManagement implements SubscriberInterface
{

    /**
     *
     * @var ContainerInterface
     */
    private $container;

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
     * @var TransactionService
     */
    private $transactionService;

    /**
     *
     * @var SessionService
     */
    private $sessionService;

    public static function getSubscribedEvents()
    {
        return [
            'sAdmin::sManageRisks::after' => 'onAfterManageRisk'
        ];
    }

    /**
     * Constructor.
     *
     * @param ContainerInterface $container
     * @param ModelManager $modelManager
     * @param ConfigReader $configReader
     * @param TransactionService $transactionService
     * @param SessionService $sessionService
     */
    public function __construct(ContainerInterface $container, ModelManager $modelManager, ConfigReader $configReader, TransactionService $transactionService, SessionService $sessionService)
    {
        $this->container = $container;
        $this->modelManager = $modelManager;
        $this->configReader = $configReader;
        $this->transactionService = $transactionService;
        $this->sessionService = $sessionService;
    }

    public function onAfterManageRisk(\Enlight_Hook_HookArgs $args)
    {
        $returnValue = $args->getReturn();

        if ($returnValue) {
            $args->setReturn($returnValue);
            return $args->getReturn();
        }

        $parameters = $args->getArgs();

        $paymentId = $parameters[0];
        if ($paymentId != null) {
            $payment = $this->modelManager->find(Payment::class, $paymentId);
            /* @var Plugin $plugin */
            $plugin = $this->modelManager->getRepository(Plugin::class)->findOneBy([
                'name' => $this->container->getParameter('wallee_payment.plugin_name')
            ]);
            if ($payment instanceof \Shopware\Models\Payment\Payment && $plugin->getId() == $payment->getPluginId()) {
                $order = $this->sessionService->getTemporaryOrder();

                $shop = ($order instanceof OrderModel ? $order->getShop() : $this->container->get('shop'));
                $pluginConfig = $this->configReader->getByPluginName('WalleePayment', $shop);
                $spaceId = $pluginConfig['spaceId'];
                /* @var PaymentMethodConfigurationModel $configuration */
                $configuration = $this->modelManager->getRepository(PaymentMethodConfigurationModel::class)->findOneBy([
                    'paymentId' => $paymentId,
                    'spaceId' => $spaceId
                ]);
                if ($configuration instanceof PaymentMethodConfigurationModel) {
                    if ($order instanceof OrderModel) {
                        try {
                            $paymentMethodPossible = false;
                            $possiblePaymentMethods = $this->transactionService->getPossiblePaymentMethods($order);
                            foreach ($possiblePaymentMethods as $possiblePaymentMethod) {
                                if ($possiblePaymentMethod->getId() == $configuration->getConfigurationId()) {
                                    $paymentMethodPossible = true;
                                    break;
                                }
                            }
                            $args->setReturn(! $paymentMethodPossible);
                        } catch (\Exception $e) {
                            $args->setReturn(true);
                        }
                    }
                } else {
                    $args->setReturn(true);
                }
            }
        }
        return $args->getReturn();
    }
}
