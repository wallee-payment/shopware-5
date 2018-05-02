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

namespace WalleePayment\Components\Provider;

use WalleePayment\Components\ApiClient;

/**
 * Provider of payment method information from the gateway.
 */
class PaymentMethod extends AbstractProvider
{

    /**
     * Constructor.
     *
     * @param \Wallee\Sdk\ApiClient $apiClient
     * @param \Zend_Cache_Core $cache
     */
    public function __construct(ApiClient $apiClient, \Zend_Cache_Core $cache)
    {
        parent::__construct($apiClient->getInstance(), $cache, 'wallee_payment_methods');
    }

    /**
     * Returns the payment method by the given id.
     *
     * @param int $id
     * @return \Wallee\Sdk\Model\PaymentMethod
     */
    public function find($id)
    {
        return parent::find($id);
    }

    /**
     * Returns a list of payment methods.
     *
     * @return \Wallee\Sdk\Model\PaymentMethod[]
     */
    public function getAll()
    {
        return parent::getAll();
    }

    protected function fetchData()
    {
        $methodService = new \Wallee\Sdk\Service\PaymentMethodService($this->apiClient);
        return $methodService->all();
    }

    protected function getId($entry)
    {
        /* @var \Wallee\Sdk\Model\PaymentMethod $entry */
        return $entry->getId();
    }
}
