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
 * Provider of payment connector information from the gateway.
 */
class PaymentConnector extends AbstractProvider
{

    /**
     * Constructor.
     *
     * @param \Wallee\Sdk\ApiClient $apiClient
     * @param \Zend_Cache_Core $cache
     */
    public function __construct(ApiClient $apiClient, \Zend_Cache_Core $cache)
    {
        parent::__construct($apiClient->getInstance(), $cache, 'wallee_payment_connectors');
    }

    /**
     * Returns the payment connector by the given id.
     *
     * @param int $id
     * @return \Wallee\Sdk\Model\PaymentConnector
     */
    public function find($id)
    {
        return parent::find($id);
    }

    /**
     * Returns a list of payment connectors.
     *
     * @return \Wallee\Sdk\Model\PaymentConnector[]
     */
    public function getAll()
    {
        return parent::getAll();
    }

    protected function fetchData()
    {
        $methodService = new \Wallee\Sdk\Service\PaymentConnectorService($this->apiClient);
        return $methodService->all();
    }

    protected function getId($entry)
    {
        /* @var \Wallee\Sdk\Model\PaymentConnector $entry */
        return $entry->getId();
    }
}
