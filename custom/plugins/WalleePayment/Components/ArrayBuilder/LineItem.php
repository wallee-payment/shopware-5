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

use Symfony\Component\DependencyInjection\ContainerInterface;
use Wallee\Sdk\Model\LineItem as LineItemModel;

class LineItem extends AbstractArrayBuilder
{
    /**
     *
     * @var LineItemModel
     */
    private $lineItem;

    /**
     * Constructor.
     *
     * @param ContainerInterface $container
     * @param LineItemModel $lineItem
     */
    public function __construct(ContainerInterface $container, LineItemModel $lineItem)
    {
        parent::__construct($container);
        $this->lineItem = $lineItem;
    }

    public function build()
    {
        return [
            'id' => $this->lineItem->getUniqueId(),
            'uniqueId' => $this->lineItem->getUniqueId(),
            'sku' => $this->lineItem->getSku(),
            'name' => $this->lineItem->getName(),
            'amountIncludingTax' => $this->lineItem->getAmountIncludingTax(),
            'unitPriceIncludingTax' => $this->lineItem->getUnitPriceIncludingTax(),
            'taxes' => $this->convertTaxesToArray($this->lineItem->getTaxes()),
            'taxRate' => $this->lineItem->getAggregatedTaxRate(),
            'type' => $this->lineItem->getType(),
            'quantity' => $this->lineItem->getQuantity(),
            'shippingRequired' => $this->lineItem->getShippingRequired()
        ];
    }

    /**
     *
     * @param \Wallee\Sdk\Model\Tax[] $taxes
     * @return array
     */
    private function convertTaxesToArray($taxes)
    {
        $result = [];
        foreach ($taxes as $tax) {
            $result[] = [
                'title' => $tax->getTitle(),
                'rate' => $tax->getRate()
            ];
        }
        return $result;
    }
}
