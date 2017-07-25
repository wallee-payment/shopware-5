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

namespace WalleePayment\Components\Provider;

use WalleePayment\Components\ApiClient;

/**
 * Provider of currency information from the gateway.
 */
class Currency extends AbstractProvider
{

    /**
     *
     * @var \Wallee\Sdk\ApiClient
     */
    private $apiClient;

    /**
     * Constructor.
     *
     * @param \Wallee\Sdk\ApiClient $apiClient
     * @param \Zend_Cache_Core $cache
     */
    public function __construct(ApiClient $apiClient, \Zend_Cache_Core $cache)
    {
        parent::__construct($cache, 'wallee_payment_currencies');
        $this->apiClient = $apiClient->getInstance();
    }

    /**
     * Returns the currency by the given code.
     *
     * @param int $code
     * @return \Wallee\Sdk\Model\RestCurrency
     */
    public function find($code)
    {
        return parent::find($code);
    }

    /**
     * Returns a list of currencies.
     *
     * @return \Wallee\Sdk\Model\RestCurrency[]
     */
    public function getAll()
    {
        return parent::getAll();
    }

    /**
     * Returns the fraction digits of the given currency.
     *
     * @param string $code
     * @return number
     */
    public function getFractionDigits($code)
    {
        $currency = $this->find($code);
        if ($currency) {
            return $currency->getFractionDigits();
        } else {
            return 2;
        }
    }

    protected function fetchData()
    {
        $methodService = new \Wallee\Sdk\Service\CurrencyService($this->apiClient);
        return $methodService->all();
    }

    protected function getId($entry)
    {
        /* @var \Wallee\Sdk\Model\RestCurrency $entry */
        return $entry->getCurrencyCode();
    }
}
