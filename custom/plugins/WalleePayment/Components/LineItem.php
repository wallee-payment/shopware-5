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

use Shopware\Models\Order\Order;
use Shopware\Models\Order\Detail;
use Shopware\Components\Model\ModelManager;
use Symfony\Component\DependencyInjection\ContainerInterface;
use WalleePayment\Components\Provider\Currency as CurrencyProvider;
use Shopware\Models\Tax\Tax;
use Shopware\Models\Dispatch\Dispatch;

class LineItem extends AbstractService
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
     * @param CurrencyProvider $currencyProvider
     */
    public function __construct(ContainerInterface $container, ModelManager $modelManager, CurrencyProvider $currencyProvider)
    {
        parent::__construct($container);
        $this->modelManager = $modelManager;
        $this->currencyProvider = $currencyProvider;
    }

    /**
     * Returns the line items for the given order.
     *
     * @param Order $order
     * @throws \Exception
     * @return \Wallee\Sdk\Model\LineItemCreate[]
     */
    public function collectLineItems(Order $order)
    {
        $lineItems = [];

        $details = $order->getDetails();
        foreach ($details as $detail) {
            /* @var Detail $detail */
            
            $type = $this->getType($detail->getMode(), $detail->getPrice());
            $lineItem = new \Wallee\Sdk\Model\LineItemCreate();
            $lineItem->setAmountIncludingTax($this->roundAmount($this->getOrderAmountIncludingTax($order, $detail), $order->getCurrency()));
            $lineItem->setName($this->getArticleName($detail));
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
            $attributes = $this->getAttributes($detail->getArticleNumber());
            if (!empty($attributes)) {
                $lineItem->setAttributes($attributes);
            }
            $lineItems[] = $this->cleanLineItem($lineItem);
        }

        if ($order->getInvoiceShipping() != 0) {
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
                $this->getOrderShippingTax($order)
            ]);
            $lineItem->setType(\Wallee\Sdk\Model\LineItemType::SHIPPING);
            $lineItem->setUniqueId('shipping');
            $lineItems[] = $this->cleanLineItem($lineItem);
        }
        
        $lineItemTotalAmount = $this->getTotalAmountIncludingTax($lineItems);
        if (abs($lineItemTotalAmount - $order->getInvoiceAmount()) > 0.0001) {
            throw new \Exception('The line item total amount of ' . $lineItemTotalAmount . ' does not match the order\'s invoice amount of ' . $order->getInvoiceAmount() . '.');
        }

        return $lineItems;
    }
    
    /**
     * Returns the line items for the currency user's basket.
     *
     * @throws \Exception
     * @return \Wallee\Sdk\Model\LineItemCreate
     */
    public function collectBasketLineItems()
    {
        $lineItems = [];
        
        $basketData = Shopware()->Modules()->Basket()->sGetBasketData();
        
        $currency = Shopware()->Shop()->getCurrency()->getCurrency();
        if (empty($currency)) {
            $currency = 'EUR';
        }
        
        $shippingcosts = Shopware()->Modules()->Admin()->sGetPremiumShippingcosts($this->getCountry());
        
        $net = $this->isNet();
        $taxfree = false;
        if ($this->isTaxFree()) {
            $net = true;
            $taxfree = true;
            if (isset($shippingcosts['netto'])) {
                $shippingcosts['brutto'] = $shippingcosts['netto'];
            }
        }
        
        $easyCouponShippingAmount = 0;
        
        $index = 1;
        foreach ($basketData['content'] as $basketRow) {
            if (!isset($basketRow['modus']) || empty($basketRow['modus'])) {
                $basketRow['modus'] = '0';
            }
            
            if (!isset($basketRow['esdarticle']) || empty($basketRow['esdarticle'])) {
                $basketRow['esdarticle'] = false;
            }
            
            if (!isset($basketRow['taxID']) || empty($basketRow['taxID'])) {
                $basketRow['taxID'] = 0;
            }
            
            if ($basketRow['taxID'] != 0) {
                /* @var Tax $tax */
                $tax = $this->modelManager->getRepository(Tax::class)->find($basketRow['taxID']);
            }
            
            $articleName = $basketRow['articlename'];
            if (isset($basketRow['additional_details']['articleName']) && !empty($basketRow['additional_details']['articleName'])) {
                $articleName = Shopware()->Modules()->System()->sMODULES['sArticles']->sOptimizeText(strip_tags(html_entity_decode($basketRow['additional_details']['articleName'])));
            }
            
            $type = $this->getType($basketRow['modus'], $basketRow['priceNumeric']);
            
            $amountIncludingTax = $this->getAmountIncludingTax($basketRow['priceNumeric'], $currency, $basketRow['quantity'], $basketRow['tax_rate'], $net && ! $taxfree);
            if (isset($basketRow['neti_easycoupon_vouchercode']) && !empty($basketRow['neti_easycoupon_vouchercode'])) {
                $easyCouponVoucherAmount = $this->getEasyCouponVoucherAmount();
                if ($easyCouponVoucherAmount > 0) {
                    $easyCouponShippingAmount = $easyCouponVoucherAmount - abs($amountIncludingTax);
                }
            }
            
            $lineItem = new \Wallee\Sdk\Model\LineItemCreate();
            $lineItem->setAmountIncludingTax($this->roundAmount($amountIncludingTax, $currency));
            $lineItem->setName($articleName);
            $lineItem->setQuantity($basketRow['quantity']);
            $lineItem->setShippingRequired($type == \Wallee\Sdk\Model\LineItemType::PRODUCT && ! $basketRow['esdarticle']);
            $lineItem->setSku($basketRow['ordernumber']);
            $lineItem->setTaxes([
                $basketRow['taxID'] != 0 ? $this->getTax($basketRow['tax_rate'], $tax
                    ->getName()) : $this->getBestMatchingTax($basketRow['tax_rate'])
            ]);
            $lineItem->setType($type);
            $lineItem->setUniqueId($index++);
            $attributes = $this->getAttributes($basketRow['ordernumber']);
            if (!empty($attributes)) {
                $lineItem->setAttributes($attributes);
            }
            $lineItems[] = $this->cleanLineItem($lineItem);
        }
        
        $dispatchId = $this->container->get('session')->get('sDispatch');
        if ($dispatchId > 0) {
            $dispatch = $this->modelManager->getRepository(Dispatch::class)->find($dispatchId);
        }
        if (isset($shippingcosts['brutto']) && $shippingcosts['brutto'] != 0) {
            if ($dispatch instanceof \Shopware\Models\Dispatch\Dispatch) {
                $shippingMethodName = $dispatch->getName();
            } else {
                $shippingMethodName = $this->container->get('snippets')
                ->getNamespace('frontend/wallee_payment/main')
                ->get('line_item/shipping', 'Shipping');
            }
            
            $lineItem = new \Wallee\Sdk\Model\LineItemCreate();
            $lineItem->setAmountIncludingTax($this->roundAmount($shippingcosts['brutto'] - $easyCouponShippingAmount, $currency));
            $lineItem->setName($shippingMethodName);
            $lineItem->setQuantity(1);
            $lineItem->setShippingRequired(false);
            $lineItem->setSku('shipping');
            $lineItem->setTaxes([
                $this->getShippingTax($shippingcosts['brutto'], $shippingcosts['netto'])
            ]);
            $lineItem->setType(\Wallee\Sdk\Model\LineItemType::SHIPPING);
            $lineItem->setUniqueId('shipping');
            $lineItems[] = $this->cleanLineItem($lineItem);
        }
                
        $basketTotalAmount = $this->getBasketTotalAmount(isset($basketData['AmountWithTaxNumeric']) ? $basketData['AmountWithTaxNumeric'] : null,
            isset($basketData['AmountNumeric']) ? $basketData['AmountNumeric'] : null,
            isset($shippingcosts['brutto']) ? $shippingcosts['brutto'] : null,
            $easyCouponShippingAmount, $this->isTaxFree());
        
        $lineItemTotalAmount = $this->getTotalAmountIncludingTax($lineItems);
        if (abs($lineItemTotalAmount - $basketTotalAmount) > 0.0001) {
            throw new \Exception('The line item total amount of ' . $lineItemTotalAmount . ' does not match the basket\'s invoice amount of ' . $basketTotalAmount . '.');
        }
        
        return $lineItems;
    }
    
    /**
     * 
     * @param float $amountWithTaxNumeric
     * @param float $amountNumeric
     * @param float $shippingAmount
     * @param float $easyCouponShippingAmount
     * @param boolean $taxFree
     * @return float
     */
    private function getBasketTotalAmount($amountWithTaxNumeric, $amountNumeric, $shippingAmount, $easyCouponShippingAmount, $taxFree)
    {
        $totalAmount = 0;
        if (! empty($amountWithTaxNumeric) && ! $taxFree) {
            $totalAmount = $amountWithTaxNumeric;
        } elseif (! empty($amountNumeric)) {
            $totalAmount = $amountNumeric;
        }
        if ($shippingAmount !== null) {
            $totalAmount += $shippingAmount;
            if ($easyCouponShippingAmount !== null) {
                $totalAmount -= $easyCouponShippingAmount;
            }
        }
        if ($totalAmount < 0) {
            $totalAmount = 0;
        }
        return $totalAmount;
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

    /**
     *
     * @param Order $order
     * @param Detail $detail
     * @return float
     */
    private function getOrderAmountIncludingTax(Order $order, Detail $detail)
    {
        return $this->getAmountIncludingTax($detail->getPrice(), $order->getCurrency(), $detail->getQuantity(), $detail->getTaxRate(), $order->getNet() && ! $order->getTaxFree());
    }
    
    /**
     *
     * @param float $price
     * @param string $currency
     * @param int $quantity
     * @param string $taxRate
     * @param boolean $priceExcludingTax
     * @return float
     */
    private function getAmountIncludingTax($price, $currency, $quantity, $taxRate, $priceExcludingTax)
    {
        $amountIncludingTax = $this->roundAmount($price, $currency) * $quantity;
        if ($priceExcludingTax) {
            $amountIncludingTax = $amountIncludingTax / 100 * (100 + $taxRate);
        }
        return $amountIncludingTax;
    }

    /**
     *
     * @param int $mode
     * @param float $price
     * @return string
     */
    private function getType($mode, $price)
    {
        switch ($mode) {
            case static::ORDER_DETAIL_MODE_VOUCHER:
            case static::ORDER_DETAIL_MODE_CUSTOMERGROUP_DISCOUNT:
            case static::ORDER_DETAIL_MODE_BUNDLE_DISCOUNT:
                return \Wallee\Sdk\Model\LineItemType::DISCOUNT;
            case static::ORDER_DETAIL_MODE_PAYMENT_SURCHARGE_DISCOUNT:
                if ($price > 0) {
                    return \Wallee\Sdk\Model\LineItemType::FEE;
                } else {
                    return \Wallee\Sdk\Model\LineItemType::DISCOUNT;
                }
            case static::ORDER_DETAIL_MODE_DEFAULT_ARTICLE:
            case static::ORDER_DETAIL_MODE_PREMIUM_ARTICLE:
            case static::ORDER_DETAIL_MODE_TRUSTED_SHOP_ARTICLE:
            default:
                return \Wallee\Sdk\Model\LineItemType::PRODUCT;
        }
    }
    
    /**
     * 
     * @return float
     */
    private function getEasyCouponVoucherAmount()
    {
        if ($this->container->has('neti_easy_coupon.service.basket_coupons')) {
            $basketVoucher = $this->container->get('neti_easy_coupon.service.basket_coupons')->getBasketRemainingValues();
            if (isset($basketVoucher['voucher'], $basketVoucher['voucherBasketValue'])) {
                $factor = $this->container->get('shopware_storefront.context_service')->getShopContext()->getCurrency()->getFactor();
                $voucher = $basketVoucher['voucher'];
                $basketVoucher = Shopware()->Session()->get('netiEasyCouponBasketVoucher');
                if (isset($basketVoucher[$voucher->getCode()])) {
	                return ($voucher->getRemainingValue() * $factor) - Shopware()->Session()->get('netiEasyCouponBasketVoucher')[$voucher->getCode()];
                } else {
                    return ($voucher->getRemainingValue() * $factor);
                }
            } else {
                return 0;
            }
        } else {
            return 0;
        }
    }
    
    /**
     *
     * @param Detail $detail
     * @return string
     */
    private function getArticleName($detail)
    {
        /* @var \Shopware\Models\Article\Article $article */
        $article = Shopware()->Models()->getRepository(\Shopware\Models\Article\Article::class)->find($detail->getArticleId());
        if ($article instanceof \Shopware\Models\Article\Article) {
            return $article->getName();
        } else {
            return $detail->getArticleName();
        }
    }
    
    /**
     *
     * @param string $articleNumber
     * @return array
     */
    private function getAttributes($articleNumber)
    {
        /* @var \Shopware\Models\Article\Detail $article */
        $article = Shopware()->Models()->getRepository(\Shopware\Models\Article\Detail::class)->findOneBy(['number' => $articleNumber]);
        
        $options = [];
        if ($article instanceof \Shopware\Models\Article\Detail) {
            foreach ($article->getConfiguratorOptions() as $option) {
                /* @var \Shopware\Models\Article\Configurator\Option $option */
                $options[$option->getGroup()->getPosition()] = $option;
            }
        }
        ksort($options);
        
        $attributes = [];
        foreach ($options as $option) {
            $attribute = new \Wallee\Sdk\Model\LineItemAttributeCreate();
            $attribute->setLabel($option->getGroup()->getName());
            $attribute->setValue($option->getName());
            $attributes['option_' . $option->getId()] = $attribute;
        }
        return $attributes;
    }
    
    /**
     *
     * @param float $rate
     * @param string $title
     * @return \Shopware\Models\Tax\Tax
     */
    private function getTax($rate, $title)
    {
        $tax = new \Wallee\Sdk\Model\TaxCreate();
        $tax->setRate($rate);
        $tax->setTitle($title);
        return $tax;
    }

    /**
     *
     * @param float $inputTaxRate
     * @return \Shopware\Models\Tax\Tax
     */
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

    /**
     *
     * @param Order $order
     * @return \Shopware\Models\Tax\Tax
     */
    private function getOrderShippingTax(Order $order)
    {
        return $this->getShippingTax($order->getInvoiceShipping(), $order->getInvoiceShippingNet());
    }
    
    /**
     *
     * @param float $priceIncludingTax
     * @param float $priceExcludingTax
     * @return \Shopware\Models\Tax\Tax
     */
    private function getShippingTax($priceIncludingTax, $priceExcludingTax)
    {
        $taxAmount = ($priceIncludingTax - $priceExcludingTax);
        $calculatedTaxRate = $taxAmount / $priceExcludingTax * 100;
        return $this->getBestMatchingTax($calculatedTaxRate);
    }
    
    /**
     *
     * @return boolean
     */
    private function isNet()
    {
        $taxId = Shopware()->Modules()->System()->sUSERGROUPDATA['tax'];
        $customerGroupId = Shopware()->Modules()->System()->sUSERGROUPDATA['id'];
        return ($this->container->get('config')->get('sARTICLESOUTPUTNETTO') && !$taxId) || (!$taxId && $customerGroupId);
    }
    
    /**
     *
     * @return boolean
     */
    private function isTaxFree()
    {
        $userData = Shopware()->Modules()->Admin()->sGetUserData();
        if (!empty($userData['additional']['countryShipping']['taxfree'])) {
            return true;
        }
        if (empty($userData['additional']['countryShipping']['taxfree_ustid'])) {
            return false;
        }
        return !empty($userData['shippingaddress']['ustid']);
    }
    
    private function getCountry()
    {
        $userData = Shopware()->Modules()->Admin()->sGetUserData();
        if (!empty($userData['additional']['countryShipping'])) {
            return $userData['additional']['countryShipping'];
        }
        $countries = Shopware()->Modules()->Admin()->sGetCountryList();
        if (empty($countries)) {
            return false;
        }
        return reset($countries);
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
    
    /**
     * Cleans the given line item for it to meet the API's requirements.
     *
     * @param \Wallee\Sdk\Model\LineItemCreate $lineItem
     * @return \Wallee\Sdk\Model\LineItemCreate
     */
    private function cleanLineItem(\Wallee\Sdk\Model\LineItemCreate $lineItem)
    {
        $lineItem->setSku($this->fixLength($lineItem->getSku(), 200));
        $lineItem->setName($this->fixLength($lineItem->getName(), 150));
        return $lineItem;
    }
}
