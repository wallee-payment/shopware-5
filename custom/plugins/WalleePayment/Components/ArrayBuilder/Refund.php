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

namespace WalleePayment\Components\ArrayBuilder;

use Wallee\Sdk\Model\Refund as RefundModel;
use WalleePayment\Components\ArrayBuilder\LineItem as LineItemArrayBuilder;
use WalleePayment\Components\ArrayBuilder\Label as LabelArrayBuilder;
use WalleePayment\Components\ArrayBuilder\LabelGroup as LabelGroupArrayBuilder;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Wallee\Sdk\Model\TransactionInvoice;

class Refund extends AbstractArrayBuilder
{
    /**
     *
     * @var RefundModel
     */
    private $refund;

    /**
     * Constructor.
     *
     * @param ContainerInterface $container
     * @param RefundModel $refund
     */
    public function __construct(ContainerInterface $container, RefundModel $refund)
    {
        parent::__construct($container);
        $this->refund = $refund;
    }

    public function build()
    {
        return [
            'id' => $this->refund->getId(),
            'state' => $this->refund->getState(),
            'createdOn' => $this->refund->getCreatedOn(),
            'amount' => $this->refund->getAmount(),
            'externalId' => $this->refund->getExternalId(),
            'failureReason' => $this->refund->getFailureReason() != null ? $this->translate($this->refund->getFailureReason()
                ->getDescription()) : null,
            'labels' => LabelGroupArrayBuilder::buildGrouped($this->container, $this->getLabelBuilders()),
            'lineItems' => $this->getLineItems()
        ];
    }

    /**
     *
     * @param ContainerInterface $container
     * @param TransactionInvoice $invoice
     * @param Refund[] $refunds
     * @return array
     */
    public static function buildBaseLineItems(ContainerInterface $container, TransactionInvoice $invoice = null, array $refunds = [])
    {
        /* @var \WalleePayment\Components\Refund $refundService */
        $refundService = $container->get('wallee_payment.refund');

        $result = [];
        foreach ($refundService->getRefundBaseLineItems($invoice, $refunds) as $lineItem) {
            $lineItemBuilder = new LineItemArrayBuilder($container, $lineItem);
            $result[] = $lineItemBuilder->build();
        }
        return $result;
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
            foreach ($this->refund->getLabels() as $label) {
                $labels[] = new LabelArrayBuilder($this->container, $label->getDescriptor(), $label->getContentAsString());
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
        $lineItems = [];
        foreach ($this->refund->getLineItems() as $lineItem) {
            $lineItemBuilder = new LineItemArrayBuilder($this->container, $lineItem);
            $lineItems[] = $lineItemBuilder->build();
        }
        return $lineItems;
    }
}
