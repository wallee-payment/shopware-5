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
use Symfony\Component\DependencyInjection\ContainerInterface;
use Shopware\Components\Model\ModelManager;
use Shopware\Models\Plugin\Plugin;
use Shopware\Models\Payment\Payment;
use WalleePayment\Components\Transaction as TransactionService;
use WalleePayment\Components\Session as SessionService;
use WalleePayment\Models\PaymentMethodConfiguration as PaymentMethodConfigurationModel;
use Shopware\Models\Order\Order as OrderModel;
use WalleePayment\Models\TransactionInfo;

class Checkout implements SubscriberInterface
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
            'Shopware_Controllers_Frontend_Checkout::confirmAction::after' => 'onConfirmAction'
        ];
    }

    /**
     * Constructor.
     *
     * @param ContainerInterface $container
     * @param ModelManager $modelManager
     * @param TransactionService $transactionService
     * @param SessionService $sessionService
     */
    public function __construct(ContainerInterface $container, ModelManager $modelManager, TransactionService $transactionService, SessionService $sessionService)
    {
        $this->container = $container;
        $this->modelManager = $modelManager;
        $this->transactionService = $transactionService;
        $this->sessionService = $sessionService;
    }

    public function onConfirmAction(\Enlight_Hook_HookArgs $args)
    {
        /* @var \Shopware_Controllers_Frontend_Checkout $checkoutController */
        $checkoutController = $args->getSubject();

        $view = $checkoutController->View();

        $paymentData = $checkoutController->getSelectedPayment();
        if (! empty($paymentData['id'])) {
            $payment = $this->modelManager->find(Payment::class, $paymentData['id']);
            /* @var Plugin $plugin */
            $plugin = $this->modelManager->getRepository(Plugin::class)->findOneBy([
                'name' => $this->container->getParameter('wallee_payment.plugin_name')
            ]);
            if ($payment instanceof \Shopware\Models\Payment\Payment && $plugin->getId() == $payment->getPluginId()) {
                $paymentMethodConfiguration = $this->modelManager->getRepository(PaymentMethodConfigurationModel::class)->findOneBy([
                    'paymentId' => $payment->getId()
                ]);
                if ($paymentMethodConfiguration instanceof PaymentMethodConfigurationModel) {
                    $order = $this->sessionService->getTemporaryOrder();
                    if ($order instanceof OrderModel) {
                        $view->addTemplateDir($this->container->getParameter('wallee_payment.plugin_dir') . '/Resources/views/');
                        $view->extendsTemplate('frontend/checkout/wallee_payment/confirm.tpl');

                        $view->assign('walleePaymentJavascriptUrl', $this->transactionService->getJavaScriptUrl($order));
                        $view->assign('walleePaymentConfigurationId', $paymentMethodConfiguration->getConfigurationId());

                        $userFailureMessage = $this->getUserFailureMessage();
                        if (!empty($userFailureMessage)) {
                            $view->assign('walleePaymentFailureMessage', $userFailureMessage);
                        }
                    }
                }
            }
        }
    }

    private function getUserFailureMessage()
    {
        /* @var \Enlight_Components_Session_Namespace $session */
        $session = $this->container->get('session');
        if (isset($session['wallee_payment.failed_transaction']) && !empty($session['wallee_payment.failed_transaction'])) {
            /* @var TransactionInfo $transactionInfo */
            $transactionInfo = $this->modelManager
                ->getRepository(TransactionInfo::class)
                ->find($session['wallee_payment.failed_transaction']);
            $session['wallee_payment.failed_transaction'] = '';

            return $transactionInfo->getUserFailureMessage();
        } else {
            return null;
        }
    }
}
