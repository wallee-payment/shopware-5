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

use Wallee\Sdk\Model\TransactionLineItemVersion as LineItemVersionModel;
use WalleePayment\Components\ArrayBuilder\LineItem as LineItemArrayBuilder;
use Symfony\Component\DependencyInjection\ContainerInterface;

class LineItemVersion extends AbstractArrayBuilder
{
    /**
     *
     * @var LineItemVersionModel
     */
    private $lineItemVersion;

    /**
     * Constructor.
     *
     * @param ContainerInterface $container
     * @param LineItemVersionModel $lineItem
     */
    public function __construct(ContainerInterface $container, LineItemVersionModel $lineItemVersion)
    {
        parent::__construct($container);
        $this->lineItemVersion = $lineItemVersion;
    }

    public function build()
    {
        $result = [];
        $transactionLineItems = [];
        foreach ($this->lineItemVersion->getTransaction()->getLineItems() as $lineItem) {
            $transactionLineItems[$lineItem->getUniqueId()] = $lineItem;
        }
        foreach ($this->lineItemVersion->getLineItems() as $lineItem) {
            $lineItemBuilder = new LineItemArrayBuilder($this->container, $lineItem);
            $item = $lineItemBuilder->build();
            $item['originalAmountIncludingTax'] = $transactionLineItems[$lineItem->getUniqueId()]->getAmountIncludingTax();
            $item['originalUnitPriceIncludingTax'] = $transactionLineItems[$lineItem->getUniqueId()]->getUnitPriceIncludingTax();
            $item['originalQuantity'] = $transactionLineItems[$lineItem->getUniqueId()]->getQuantity();
            $result[] = $item;
        }
        return $result;
    }
}
