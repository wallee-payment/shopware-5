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
use WalleePayment\Components\Registry;

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
    
    /**
     * @var Registry
     */
    private $registry;

    public static function getSubscribedEvents()
    {
        return [
            'sAdmin::sManageRisks::after' => 'onAfterManageRisk',
            'sAdmin::sValidateStep3::after' => 'onAfterValidation'
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
     * @param Registry $registry
     */
    public function __construct(ContainerInterface $container, ModelManager $modelManager, ConfigReader $configReader, TransactionService $transactionService, SessionService $sessionService, Registry $registry)
    {
        $this->container = $container;
        $this->modelManager = $modelManager;
        $this->configReader = $configReader;
        $this->transactionService = $transactionService;
        $this->sessionService = $sessionService;
        $this->registry = $registry;
    }

    public function onAfterManageRisk(\Enlight_Hook_HookArgs $args)
    {
        $returnValue = $args->getReturn();

        if ($returnValue || $this->registry->get('disable_risk_management') === true) {
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
                $args->setReturn(! $this->isPaymentMethodAvailable($payment));
            }
        }
        return $args->getReturn();
    }
    
    public function onAfterValidation(\Enlight_Hook_HookArgs $args)
    {
        $returnValue = $args->getReturn();
        
        if (!is_array($returnValue) || $this->registry->get('disable_risk_management') === true) {
            $args->setReturn($returnValue);
            return $args->getReturn();
        }
        
        $paymentId = $returnValue['paymentData']['id'];
        if ($paymentId != null) {
            $payment = $this->modelManager->find(Payment::class, $paymentId);
            /* @var Plugin $plugin */
            $plugin = $this->modelManager->getRepository(Plugin::class)->findOneBy([
                'name' => $this->container->getParameter('wallee_payment.plugin_name')
            ]);
            if ($payment instanceof \Shopware\Models\Payment\Payment && $plugin->getId() == $payment->getPluginId()) {
                $available = $this->isPaymentMethodAvailable($payment);
                if (!$available) {
                    $returnValue['checkPayment'] = array(
                        'sErrorFlag' => [
                            'payment' => true
                        ],
                        'sErrorMessages' => [
                            $this->container->get('snippets')
                                ->getNamespace('frontend/checkout/error_messages')
                                ->get('ConfirmInfoPaymentBlocked')
                        ]
                    );
                }
            }
        }
        
        $args->setReturn($returnValue);
        return $args->getReturn();
    }
    
    private function isPaymentMethodAvailable(\Shopware\Models\Payment\Payment $payment)
    {
        $order = $this->sessionService->getTemporaryOrder();
        $shop = ($order instanceof OrderModel ? $order->getShop() : $this->container->get('shop'));
        $pluginConfig = $this->configReader->getByPluginName('WalleePayment', $shop);
        $spaceId = $pluginConfig['spaceId'];
        /* @var PaymentMethodConfigurationModel $configuration */
        $configuration = $this->modelManager->getRepository(PaymentMethodConfigurationModel::class)->findOneBy([
            'paymentId' => $payment->getId(),
            'spaceId' => $spaceId
        ]);
        if ($configuration instanceof PaymentMethodConfigurationModel) {
            try {
                $possiblePaymentMethods = [];
                if ($order instanceof OrderModel) {
                    $possiblePaymentMethods = $this->transactionService->getPossiblePaymentMethods($order);
                } elseif (Shopware()->Modules()->Basket()->sCountBasket() >= 1
                    && $this->container->get('session')->offsetGet('sUserId') != null) {
                    // It is important to disable the risk management here to not end up in an infinite recursion.
                        $this->registry->set('disable_risk_management', true);
                    $possiblePaymentMethods = $this->transactionService->getPossiblePaymentMethodsByBasket();
                    $this->registry->set('disable_risk_management', false);
                } else {
                    return true;
                }
                foreach ($possiblePaymentMethods as $possiblePaymentMethod) {
                    if ($possiblePaymentMethod->getId() == $configuration->getConfigurationId()) {
                        return true;
                    }
                }
            } catch (\Exception $e) {
            }
        }
        return false;
    }
}
