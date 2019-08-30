<?php

/**
 * wallee Shopware
 *
 * This Shopware extension enables to process payments with wallee (https://www.wallee.com/).
 *
 * @package Wallee_Payment
 * @author customweb GmbH (http://www.customweb.com/)
 * @license http://www.apache.org/licenses/LICENSE-2.0  Apache Software License (ASL 2.0)
 */

namespace WalleePayment\Components;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Wallee\Sdk\Model\Transaction as TransactionModel;
use Wallee\Sdk\Model\TransactionInvoice;
use WalleePayment\Subscriber\Webhook\Transaction as TransactionWebhookService;
use WalleePayment\Subscriber\Webhook\TransactionInvoice as InvoiceWebhookService;

class Payment extends AbstractService
{

    /**
     *
     * @var Transaction
     */
    private $transactionService;
    
    /**
     *
     * @var Invoice
     */
    private $invoiceService;
    
    /**
     *
     * @var TransactionWebhookService
     */
    private $transactionWebhookService;
    
    /**
     *
     * @var InvoiceWebhookService
     */
    private $invoiceWebhookService;
    
    /**
     * Constructor.
     * 
     * @param ContainerInterface $container
     * @param Transaction $transactionService
     * @param Invoice $invoiceService
     * @param TransactionWebhookService $transactionWebhookService
     * @param InvoiceWebhookService $invoiceWebhookService
     */
    public function __construct(ContainerInterface $container, Transaction $transactionService, Invoice $invoiceService, TransactionWebhookService $transactionWebhookService, InvoiceWebhookService $invoiceWebhookService)
    {
        parent::__construct($container);
        $this->transactionService = $transactionService;
        $this->invoiceService = $invoiceService;
        $this->transactionWebhookService = $transactionWebhookService;
        $this->invoiceWebhookService = $invoiceWebhookService;
    }
    
    /**
     *
     * @param int $spaceId
     * @param int $transactionId
     */
    public function fetchPaymentStatus($spaceId, $transactionId)
    {
        $transaction = $this->transactionService->getTransaction($spaceId, $transactionId);
        if ($transaction instanceof TransactionModel) {
            $this->transactionWebhookService->process($transaction);
            
            $invoice = $this->invoiceService->getInvoice($spaceId, $transactionId);
            if ($invoice instanceof TransactionInvoice) {
                $this->invoiceWebhookService->process($invoice);
            }
        }
    }
}
