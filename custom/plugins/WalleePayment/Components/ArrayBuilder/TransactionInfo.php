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

namespace WalleePayment\Components\ArrayBuilder;

use WalleePayment\Models\TransactionInfo as TransactionInfoModel;
use Wallee\Sdk\Model\Transaction;
use Wallee\Sdk\Model\PaymentMethod;
use Wallee\Sdk\Model\TransactionInvoice;
use Wallee\Sdk\Model\TransactionLineItemVersion;
use Wallee\Sdk\Model\Refund;
use Symfony\Component\DependencyInjection\ContainerInterface;
use WalleePayment\Components\ArrayBuilder\PaymentMethod as PaymentMethodArrayBuilder;
use WalleePayment\Components\ArrayBuilder\LineItemVersion as LineItemVersionArrayBuilder;
use WalleePayment\Components\ArrayBuilder\LineItem as LineItemArrayBuilder;
use WalleePayment\Components\ArrayBuilder\Label as LabelArrayBuilder;
use WalleePayment\Components\ArrayBuilder\LabelGroup as LabelGroupArrayBuilder;
use WalleePayment\Components\ArrayBuilder\Refund as RefundArrayBuilder;

class TransactionInfo extends AbstractArrayBuilder
{
    /**
     *
     * @var TransactionInfoModel
     */
    private $transactionInfo;

    /**
     *
     * @var Transaction
     */
    private $transaction;

    /**
     *
     * @var PaymentMethod
     */
    private $paymentMethod;

    /**
     *
     * @var TransactionInvoice
     */
    private $invoice;

    /**
     *
     * @var TransactionLineItemVersion
     */
    private $lineItemVersion;

    /**
     *
     * @var Refund[]
     */
    private $refunds = [];

    /**
     * Constructor.
     *
     * @param ContainerInterface $container
     * @param TransactionInfoModel $transactionInfo
     */
    public function __construct(ContainerInterface $container, TransactionInfoModel $transactionInfo)
    {
        parent::__construct($container);
        $this->transactionInfo = $transactionInfo;
    }

    /**
     *
     * @param Transaction $transaction
     * @return TransactionInfo
     */
    public function setTransaction(Transaction $transaction = null)
    {
        $this->transaction = $transaction;
        return $this;
    }

    /**
     *
     * @param PaymentMethod $paymentMethod
     * @return TransactionInfo
     */
    public function setPaymentMethod(PaymentMethod $paymentMethod = null)
    {
        $this->paymentMethod = $paymentMethod;
        return $this;
    }

    /**
     *
     * @param TransactionInvoice $invoice
     * @return TransactionInfo
     */
    public function setInvoice(TransactionInvoice $invoice = null)
    {
        $this->invoice = $invoice;
        return $this;
    }

    /**
     *
     * @param TransactionLineItemVersion $lineItemVersion
     * @return TransactionInfo
     */
    public function setLineItemVersion(TransactionLineItemVersion $lineItemVersion = null)
    {
        $this->lineItemVersion = $lineItemVersion;
        return $this;
    }

    /**
     *
     * @param Refund[] $refunds
     * @return TransactionInfo
     */
    public function setRefunds($refunds)
    {
        $this->refunds = $refunds != null ? $refunds : [];
        return $this;
    }

    /**
     *
     * @return array
     */
    public function build()
    {
        $result = [
            'id' => $this->transactionInfo->getId(),
            'transactionId' => $this->transactionInfo->getTransactionId(),
            'orderId' => $this->transactionInfo->getOrderId(),
            'state' => $this->transactionInfo->getState(),
            'spaceId' => $this->transactionInfo->getSpaceId(),
            'spaceViewId' => $this->transactionInfo->getSpaceViewId(),
            'language' => $this->transactionInfo->getLanguage(),
            'currency' => $this->transactionInfo->getCurrency(),
            'currencyDecimals' => $this->container->get('wallee_payment.provider.currency')->getFractionDigits($this->transactionInfo->getCurrency()),
            'createdAt' => $this->transactionInfo->getCreatedAt(),
            'authorizationAmount' => $this->transactionInfo->getAuthorizationAmount(),
            'image' => $this->getImage(),
            'failureReason' => $this->translate($this->transactionInfo->getFailureReason()),
            'labels' => LabelGroupArrayBuilder::buildGrouped($this->container, $this->getLabelBuilders()),
            'transactionUrl' => $this->getTransactionUrl(),
            'customerUrl' => $this->getCustomerUrl(),
            'lineItems' => $this->getLineItems(),
            'lineItemTotalAmount' => $this->lineItemVersion != null ? $this->lineItemVersion->getAmount() : $this->transactionInfo->getAuthorizationAmount(),
            'refundBaseLineItems' => RefundArrayBuilder::buildBaseLineItems($this->container, $this->invoice, $this->refunds),
            'canDownloadInvoice' => $this->transactionInfo->canDownloadInvoice(),
            'canDownloadPackingSlip' => $this->transactionInfo->canDownloadPackingSlip(),
            'canReview' => $this->canReviewTransaction(),
            'canVoid' => $this->canVoidTransaction(),
            'canComplete' => $this->canCompleteTransaction(),
            'canUpdateLineItems' => $this->canUpdateLineItems(),
            'canRefund' => $this->canRefund(),
            'shopId' => $this->transactionInfo->getShopId(),
            'shop' => $this->getShop()
        ];

        if ($this->paymentMethod != null) {
            $paymentMethodBuilder = new PaymentMethodArrayBuilder($this->container, $this->paymentMethod);
            $result['paymentMethod'] = $paymentMethodBuilder->build();
        }

        if ($this->refunds != null) {
            foreach ($this->refunds as $refund) {
                $refundBuilder = new RefundArrayBuilder($this->container, $refund);
                $result['refunds'][] = $refundBuilder->build();
            }
        }

        return $result;
    }

    /**
     *
     * @return array
     */
    private function getShop()
    {
        return [
            'id' => $this->transactionInfo->getShop()->getId(),
            'default' => $this->transactionInfo->getShop()->getDefault(),
            'localeId' => $this->transactionInfo->getShop()->getLocale()->getId(),
            'categoryId' => $this->transactionInfo->getShop()->getCategory()->getId(),
            'name' => $this->transactionInfo->getShop()->getName(),
        ];
    }

    /**
     *
     * @return string
     */
    private function getTransactionUrl()
    {
        return $this->container->getParameter('wallee_payment.base_gateway_url') . '/s/' . $this->transactionInfo->getSpaceId() . '/payment/transaction/view/' . $this->transactionInfo->getTransactionId();
    }
    
    /**
     *
     * @return string
     */
    private function getCustomerUrl()
    {
        return $this->container->getParameter('wallee_payment.base_gateway_url') . '/s/' . $this->transactionInfo->getSpaceId() . '/payment/customer/transaction/view/' . $this->transactionInfo->getTransactionId();
    }

    /**
     *
     * @return string
     */
    private function getImage()
    {
        return $this->container->get('wallee_payment.resource')->getResourceUrl($this->transactionInfo->getImage(), $this->transactionInfo->getLanguage(), $this->transactionInfo->getSpaceId(), $this->transactionInfo->getSpaceViewId());
    }

    /**
     *
     * @return boolean
     */
    private function canReviewTransaction()
    {
        try {
            /* @var \Wallee\Sdk\Model\DeliveryIndication $deliveryIndication */
            $deliveryIndication = $this->container->get('wallee_payment.delivery_indication')->getDeliveryIndication($this->transactionInfo);
            if ($deliveryIndication != null) {
                return $deliveryIndication->getState() == \Wallee\Sdk\Model\DeliveryIndicationState::MANUAL_CHECK_REQUIRED;
            } else {
                return false;
            }
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     *
     * @return boolean
     */
    private function canUpdateLineItems()
    {
        if ($this->transaction != null && $this->transaction->getState() == \Wallee\Sdk\Model\TransactionState::AUTHORIZED) {
            return true;
        } else {
            return false;
        }
    }

    /**
     *
     * @return boolean
     */
    private function canCompleteTransaction()
    {
        if ($this->transaction!= null && $this->transaction->getState() == \Wallee\Sdk\Model\TransactionState::AUTHORIZED) {
            return true;
        } else {
            return false;
        }
    }

    /**
     *
     * @return boolean
     */
    private function canVoidTransaction()
    {
        if ($this->transaction!= null && $this->transaction->getState() == \Wallee\Sdk\Model\TransactionState::AUTHORIZED) {
            return true;
        } else {
            return false;
        }
    }

    /**
     *
     * @return boolean
     */
    private function canRefund()
    {
        if ($this->transaction == null || $this->invoice == null) {
            return false;
        }

        if (! in_array($this->transaction->getState(), [
            \Wallee\Sdk\Model\TransactionState::COMPLETED,
            \Wallee\Sdk\Model\TransactionState::FULFILL,
            \Wallee\Sdk\Model\TransactionState::DECLINE
        ])) {
            return false;
        }

        foreach ($this->refunds as $refund) {
            if (in_array($refund->getState(), [
                \Wallee\Sdk\Model\RefundState::MANUAL_CHECK,
                \Wallee\Sdk\Model\RefundState::PENDING
            ])) {
                return false;
            }
        }

        if ($this->container->get('wallee_payment.line_item')->getTotalAmountIncludingTax($this->container->get('wallee_payment.refund')->getRefundBaseLineItems($this->invoice, $this->refunds)) <= 0) {
            return false;
        }

        return true;
    }

    /**
     *
     * @return LabelArrayBuilder[]
     */
    private function getLabelBuilders()
    {
        /** @var \WalleePayment\Components\Provider\LabelDescriptorProvider $labelDescriptorProvider */
        $labelDescriptorProvider = $this->container->get('wallee_payment.provider.label_descriptor');

        $labels = [];
        try {
            foreach ($this->transactionInfo->getLabels() as $descriptorId => $value) {
                $descriptor = $labelDescriptorProvider->find($descriptorId);
                if ($descriptor) {
                    $labels[] = new LabelArrayBuilder($this->container, $descriptor, $value);
                }
            }
        } catch (\Exception $e) {
            // If label descriptors and label descriptor groups cannot be loaded from wallee, the labels cannot be displayed.
        }
        return $labels;
    }

    /**
     *
     * @return LineItemArrayBuilder[]
     */
    private function getLineItems()
    {
        if ($this->transaction && $this->lineItemVersion) {
            $lineItemVersionBuilder = new LineItemVersionArrayBuilder($this->container, $this->lineItemVersion);
            return $lineItemVersionBuilder->build();
        } elseif ($this->transaction) {
            $result = [];
            foreach ($this->transaction->getLineItems() as $lineItem) {
                $lineItemBuilder = new LineItemArrayBuilder($this->container, $lineItem);
                $result[] = $lineItemBuilder->build();
            }
            return $result;
        } else {
            return [];
        }
    }
}
