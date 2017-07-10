<?php
namespace WalleePayment\Components;

use Shopware\Models\Order\Order;
use Shopware\Models\Order\Detail;
use Shopware\Components\Model\ModelManager;
use Symfony\Component\DependencyInjection\ContainerInterface;
use WalleePayment\Components\Provider\Currency as CurrencyProvider;

class LineItem
{
    const ORDER_DETAIL_MODE_DEFAULT_ARTICLE = 0;

    const ORDER_DETAIL_MODE_PREMIUM_ARTICLE = 1;

    const ORDER_DETAIL_MODE_VOUCHER = 2;

    const ORDER_DETAIL_MODE_CUSTOMERGROUP_DISCOUNT = 3;

    const ORDER_DETAIL_MODE_PAYMENT_SURCHARGE_DISCOUNT = 4;

    const ORDER_DETAIL_MODE_BUNDLE_DISCOUNT = 10;

    const ORDER_DETAIL_MODE_TRUSTED_SHOP_ARTICLE = 12;

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
     * @var CurrencyProvider
     */
    private $currencyProvider;

    /**
     * Constructor.
     *
     * @param ContainerInterface $container
     * @param ModelManager $modelManager
     */
    public function __construct(ContainerInterface $container, ModelManager $modelManager, CurrencyProvider $currencyProvider)
    {
        $this->container = $container;
        $this->modelManager = $modelManager;
        $this->currencyProvider = $currencyProvider;
    }

    public function collectLineItems(Order $order)
    {
        $lineItems = [];

        $details = $order->getDetails();
        foreach ($details as $detail) {
            $type = $this->getType($detail);
            /* @var Detail $detail */
            $lineItem = new \Wallee\Sdk\Model\LineItemCreate();
            $lineItem->setAmountIncludingTax($this->roundAmount($this->getAmountIncludingTax($order, $detail), $order->getCurrency()));
            $lineItem->setName($detail->getArticleName());
            $lineItem->setQuantity($detail->getQuantity());
            $lineItem->setShippingRequired($type == \Wallee\Sdk\Model\LineItemType::PRODUCT && ! $detail->getEsdArticle());
            $lineItem->setSku($detail->getArticleNumber());
            $lineItem->setTaxes([
                $detail->getTax()
                    ->getId() != 0 ? $this->getTax($detail->getTaxRate(), $detail->getTax()
                    ->getName()) : $this->getBestMatchingTax($detail->getTaxRate())
            ]);
            $lineItem->setType($type);
            $lineItem->setUniqueId($detail->getId());
            $lineItems[] = $lineItem;
        }

        if ($order->getInvoiceShipping() > 0) {
            if ($order->getDispatch() instanceof \Shopware\Models\Dispatch\Dispatch) {
                $shippingMethodName = $order->getDispatch()->getName();
            } else {
                $shippingMethodName = $this->container->get('snippets')
                    ->getNamespace('frontend/wallee_payment/main')
                    ->get('line_item/shipping', 'Shipping');
            }

            $lineItem = new \Wallee\Sdk\Model\LineItemCreate();
            $lineItem->setAmountIncludingTax($this->roundAmount($order->getInvoiceShipping(), $order->getCurrency()));
            $lineItem->setName($shippingMethodName);
            $lineItem->setQuantity(1);
            $lineItem->setShippingRequired(false);
            $lineItem->setSku('shipping');
            $lineItem->setTaxes([
                $this->getShippingTax($order)
            ]);
            $lineItem->setType(\Wallee\Sdk\Model\LineItemType::SHIPPING);
            $lineItem->setUniqueId('shipping');
            $lineItems[] = $lineItem;
        }

        return $lineItems;
    }

    /**
     * Returns the total amount including tax of the given line items.
     *
     * @param \Wallee\Sdk\Model\LineItem[] $lineItems
     * @return float
     */
    public function getTotalAmountIncludingTax(array $lineItems)
    {
        $sum = 0;
        foreach ($lineItems as $lineItem) {
            $sum += $lineItem->getAmountIncludingTax();
        }

        return $sum;
    }

    private function getAmountIncludingTax(Order $order, Detail $detail)
    {
        $amountIncludingTax = $detail->getPrice() * $detail->getQuantity();
        if ($order->getNet() && ! $order->getTaxFree()) {
            $amountIncludingTax = $amountIncludingTax / 100 * (100 + $detail->getTaxRate());
        }
        return $amountIncludingTax;
    }

    private function getType(Detail $detail)
    {
        switch ($detail->getMode()) {
            case self::ORDER_DETAIL_MODE_VOUCHER:
            case self::ORDER_DETAIL_MODE_CUSTOMERGROUP_DISCOUNT:
            case self::ORDER_DETAIL_MODE_BUNDLE_DISCOUNT:
                return \Wallee\Sdk\Model\LineItemType::DISCOUNT;
            case self::ORDER_DETAIL_MODE_PAYMENT_SURCHARGE_DISCOUNT:
                if ($detail->getPrice() > 0) {
                    return \Wallee\Sdk\Model\LineItemType::FEE;
                } else {
                    return \Wallee\Sdk\Model\LineItemType::DISCOUNT;
                }
            case self::ORDER_DETAIL_MODE_DEFAULT_ARTICLE:
            case self::ORDER_DETAIL_MODE_PREMIUM_ARTICLE:
            case self::ORDER_DETAIL_MODE_TRUSTED_SHOP_ARTICLE:
            default:
                return \Wallee\Sdk\Model\LineItemType::PRODUCT;
        }
    }

    private function getTax($rate, $title)
    {
        $tax = new \Wallee\Sdk\Model\TaxCreate();
        $tax->setRate($rate);
        $tax->setTitle($title);
        return $tax;
    }

    private function getBestMatchingTax($inputTaxRate)
    {
        $matchingRate = null;
        $matchingTitle = null;
        $minDistance = null;
        $taxes = $this->modelManager->getRepository(\Shopware\Models\Tax\Tax::class)->findAll();
        foreach ($taxes as $tax) {
            /* @var \Shopware\Models\Tax\Tax $tax */
            $taxRate = $tax->getTax();
            $distance = abs($taxRate - $inputTaxRate);
            if ($minDistance === null || $distance < $minDistance) {
                $matchingRate = $taxRate;
                $matchingTitle = $tax->getName();
                $minDistance = $distance;
            }
        }
        $taxRules = $this->modelManager->getRepository(\Shopware\Models\Tax\Rule::class)->findAll();
        foreach ($taxRules as $taxRule) {
            /* @var \Shopware\Models\Tax\Rule $taxRule */
            $taxRate = $taxRule->getTax();
            $distance = abs($taxRate - $inputTaxRate);
            if ($minDistance === null || $distance < $minDistance) {
                $matchingRate = $taxRate;
                $matchingTitle = $taxRule->getGroup()->getName();
                $minDistance = $distance;
            }
        }
        return $this->getTax($matchingRate, $matchingTitle);
    }

    private function getShippingTax(Order $order)
    {
        $taxAmount = ($order->getInvoiceShipping() - $order->getInvoiceShippingNet());
        $calculatedTaxRate = $taxAmount / $order->getInvoiceShippingNet() * 100;
        return $this->getBestMatchingTax($calculatedTaxRate);
    }

    /**
     * Rounds the given amount to the currency's format.
     *
     * @param float $amount
     * @param string $currencyCode
     * @return number
     */
    private function roundAmount($amount, $currencyCode)
    {
        return round($amount, $this->currencyProvider->getFractionDigits($currencyCode));
    }
}
