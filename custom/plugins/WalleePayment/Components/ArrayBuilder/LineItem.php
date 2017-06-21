<?php
namespace WalleePayment\Components\ArrayBuilder;

use Wallee\Sdk\Model\LineItem as LineItemModel;
use Symfony\Component\DependencyInjection\ContainerInterface;

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
