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
use Symfony\Component\DependencyInjection\ContainerInterface;
use Shopware\Components\Model\ModelManager;
use Shopware\Components\Plugin\ConfigReader;
use WalleePayment\Models\OrderTransactionMapping;
use WalleePayment\Models\TransactionInfo;
use WalleePayment\Components\TransactionInfo as TransactionInfoService;
use Wallee\Sdk\Model\Refund;
use Wallee\Sdk\Model\RefundState;

class Account implements SubscriberInterface
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
     * @var TransactionInfoService
     */
    private $transactionInfoService;

    public static function getSubscribedEvents()
    {
        return [
            'Shopware_Controllers_Frontend_Account::ordersAction::after' => 'onOrdersAction'
        ];
    }

    /**
     * Constructor.
     *
     * @param ContainerInterface $container
     * @param ConfigReader $configReader
     * @param ModelManager $modelManager
     * @param TransactionInfoService $transactionInfoService
     */
    public function __construct(ContainerInterface $container, ConfigReader $configReader, ModelManager $modelManager, TransactionInfoService $transactionInfoService)
    {
        $this->container = $container;
        $this->configReader = $configReader;
        $this->modelManager = $modelManager;
        $this->transactionInfoService = $transactionInfoService;
    }

    public function onOrdersAction(\Enlight_Event_EventArgs $args)
    {
        /* @var \Enlight_Controller_Action $controller */
        $controller = $args->getSubject();
        /* @var \Enlight_View_Default $view */
        $view = $controller->View();

        $orderData = $view->getAssign('sOpenOrders');
        foreach ($orderData as $orderKey => $order) {
            if (isset($order['id'])) {
                $transactionInfo = $this->getTransactionInfoByOrder($order['id']);
                if ($transactionInfo instanceof TransactionInfo) {
                    $orderData[$orderKey]['walleeTransaction'] = $this->getTransactionVariables($transactionInfo);
                }
            }
        }

        $view->assign('sOpenOrders', $orderData);
        $view->addTemplateDir($this->container->getParameter('wallee_payment.plugin_dir') . '/Resources/views/');
        $view->extendsTemplate('frontend/account/wallee_payment/order.tpl');
    }

    /**
     *
     * @param TransactionInfo $transactionInfo
     * @return array
     */
    private function getTransactionVariables(TransactionInfo $transactionInfo)
    {
        return [
            'id' => $transactionInfo->getId(),
            'canDownloadInvoice' => $transactionInfo->canDownloadInvoice() && $this->isDownloadInvoiceAllowed($transactionInfo),
            'canDownloadPackingSlip' => $transactionInfo->canDownloadPackingSlip() && $this->isDownloadPackingSlipAllowed($transactionInfo),
            'canDownloadRefunds' => $this->isDownloadRefundAllowed($transactionInfo),
            'refunds' => $this->getRefundVariables($transactionInfo)
        ];
    }
    
    /**
     * 
     * @param TransactionInfo $transactionInfo
     * @return array
     */
    private function getRefundVariables(TransactionInfo $transactionInfo)
    {
        $variables = [];
        foreach ($this->getRefunds($transactionInfo) as $refund) {
            $variables[] = [
                'id' => $refund->getId(),
                'date' => $refund->getCreatedOn(),
                'amount' => $refund->getAmount(),
                'canDownload' => ($refund->getState() == RefundState::SUCCESSFUL || $refund->getState() == RefundState::FAILED) && $this->isDownloadRefundAllowed($transactionInfo)
            ];
        }
        return $variables;
    }
    
    /**
     *
     * @param TransactionInfo $transactionInfo
     * @return Refund[]|array
     */
    private function getRefunds(TransactionInfo $transactionInfo)
    {
        try {
            $refunds = $this->container->get('wallee_payment.refund')->getRefunds($transactionInfo->getSpaceId(), $transactionInfo->getTransactionId());
            if (is_array($refunds)) {
                return $refunds;
            }
        } catch (\Exception $e) {
        }
        return [];
    }

    /**
     *
     * @param TransactionInfo $transactionInfo
     * @return boolean
     */
    private function isDownloadInvoiceAllowed(TransactionInfo $transactionInfo)
    {
        if ($transactionInfo->getOrder() == null) {
            return false;
        }
        $pluginConfig = $this->configReader->getByPluginName('WalleePayment', $transactionInfo->getOrder()->getShop());
        return (boolean) $pluginConfig['customerDownloadInvoice'];
    }

    /**
     *
     * @param TransactionInfo $transactionInfo
     * @return boolean
     */
    private function isDownloadPackingSlipAllowed(TransactionInfo $transactionInfo)
    {
        if ($transactionInfo->getOrder() == null) {
            return false;
        }
        $pluginConfig = $this->configReader->getByPluginName('WalleePayment', $transactionInfo->getOrder()->getShop());
        return (boolean) $pluginConfig['customerDownloadPackingSlip'];
    }
    
    /**
     *
     * @param TransactionInfo $transactionInfo
     * @return boolean
     */
    private function isDownloadRefundAllowed(TransactionInfo $transactionInfo)
    {
        if ($transactionInfo->getOrder() == null) {
            return false;
        }
        $pluginConfig = $this->configReader->getByPluginName('WalleePayment', $transactionInfo->getOrder()->getShop());
        return (boolean) $pluginConfig['customerDownloadRefund'];
    }

    /**
     *
     * @param int $orderId
     * @return TransactionInfo|null
     */
    private function getTransactionInfoByOrder($orderId)
    {
        $transactionInfo = $this->modelManager
            ->getRepository(TransactionInfo::class)
            ->findOneBy([
                'orderId' => $orderId
            ]);
        if ($transactionInfo instanceof TransactionInfo) {
            return $transactionInfo;
        }

        $mapping = $this->modelManager
            ->getRepository(OrderTransactionMapping::class)
            ->findOneBy([
                'orderId' => $orderId
            ]);
        if ($mapping instanceof OrderTransactionMapping) {
            return $this->transactionInfoService->updateTransactionInfoByOrder($this->container->get('wallee_payment.transaction')
                ->getTransaction($mapping->getSpaceId(), $mapping->getTransactionId()), $mapping->getOrder());
        }
    }
}
