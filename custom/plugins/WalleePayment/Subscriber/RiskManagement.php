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

namespace WalleePayment\Subscriber;

use Enlight\Event\SubscriberInterface;
use Shopware\Models\Payment\Payment;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Shopware\Components\Model\ModelManager;
use WalleePayment\Components\Transaction as TransactionService;
use WalleePayment\Components\Session as SessionService;
use Shopware\Models\Plugin\Plugin;
use WalleePayment\Models\PaymentMethodConfiguration as PaymentMethodConfigurationModel;
use Shopware\Components\Plugin\ConfigReader;
use WalleePayment\Components\Registry;
use Psr\Log\LoggerInterface;

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
    
    /**
     * @var LoggerInterface
     */
    private $logger;
    
    public static function getSubscribedEvents()
    {
        return [
            'Shopware_Modules_Admin_GetPaymentMeans_DataFilter' => 'onDataFilter',
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
     * @param LoggerInterface $logger
     */
    public function __construct(ContainerInterface $container, ModelManager $modelManager, ConfigReader $configReader, TransactionService $transactionService, SessionService $sessionService, Registry $registry, LoggerInterface $logger)
    {
        $this->container = $container;
        $this->modelManager = $modelManager;
        $this->configReader = $configReader;
        $this->transactionService = $transactionService;
        $this->sessionService = $sessionService;
        $this->registry = $registry;
        $this->logger = $logger;
    }
    
    public function onDataFilter(\Enlight_Event_EventArgs $args)
    {
        $paymentMeans = $args->getReturn();
        
        if ($this->registry->get('disable_risk_management') === true) {
            return $args->getReturn();
        }
        
        if (Shopware()->Modules()->Basket()->sCountBasket() >= 1
                && $this->container->get('session')->offsetGet('sUserId') != null) {
            $possiblePaymentMethodIds = [];
                    
                    // It is important to disable the risk management here to not end up in an infinite recursion.
                    $this->registry->set('disable_risk_management', true);
            $possiblePaymentMethods = [];
            try {
                $possiblePaymentMethods = $this->transactionService->getPossiblePaymentMethodsByBasket();
            } catch (\Exception $e) {
                $this->logger->critical($e);
            }
            $this->registry->set('disable_risk_management', false);
                    
            foreach ($possiblePaymentMethods as $possiblePaymentMethod) {
                $possiblePaymentMethodIds[] = $possiblePaymentMethod->getId();
            }
                    
                    /* @var Plugin $plugin */
                    $plugin = $this->modelManager->getRepository(Plugin::class)->findOneBy([
                        'name' => $this->container->getParameter('wallee_payment.plugin_name')
                    ]);
            $pluginConfig = $this->configReader->getByPluginName('WalleePayment', $this->container->get('shop'));
            $spaceId = $pluginConfig['spaceId'];
                    
            $filteredPaymentMeans = [];
            foreach ($paymentMeans as $paymentMean) {
                /* @var PaymentMethodConfigurationModel $configuration */
                        $configuration = $this->modelManager->getRepository(PaymentMethodConfigurationModel::class)->findOneBy([
                            'paymentId' => $paymentMean['id'],
                            'spaceId' => $spaceId
                        ]);
                if ($configuration instanceof PaymentMethodConfigurationModel
                            && !in_array($configuration->getConfigurationId(), $possiblePaymentMethodIds)) {
                    continue;
                }
                $filteredPaymentMeans[] = $paymentMean;
            }
            $args->setReturn($filteredPaymentMeans);
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
        $pluginConfig = $this->configReader->getByPluginName('WalleePayment', $this->container->get('shop'));
        $spaceId = $pluginConfig['spaceId'];
        /* @var PaymentMethodConfigurationModel $configuration */
        $configuration = $this->modelManager->getRepository(PaymentMethodConfigurationModel::class)->findOneBy([
            'paymentId' => $payment->getId(),
            'spaceId' => $spaceId
        ]);
        if ($configuration instanceof PaymentMethodConfigurationModel) {
            try {
                $possiblePaymentMethods = [];
                if (Shopware()->Modules()->Basket()->sCountBasket() >= 1
                    && $this->container->get('session')->offsetGet('sUserId') != null) {
                    $possiblePaymentMethods = $this->transactionService->getPossiblePaymentMethodsByBasket();
                } else {
                    return true;
                }
                foreach ($possiblePaymentMethods as $possiblePaymentMethod) {
                    if ($possiblePaymentMethod->getId() == $configuration->getConfigurationId()) {
                        return true;
                    }
                }
            } catch (\Exception $e) {
                $this->logger->critical($e);
            }
        }
        return false;
    }
}
