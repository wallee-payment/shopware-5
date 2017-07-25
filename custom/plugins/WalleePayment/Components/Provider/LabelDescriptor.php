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
 * Provider of label descriptor information from the gateway.
 */
class LabelDescriptor extends AbstractProvider
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
        parent::__construct($cache, 'wallee_payment_label_descriptors');
        $this->apiClient = $apiClient->getInstance();
    }

    /**
     * Returns the label descriptor by the given code.
     *
     * @param int $code
     * @return \Wallee\Sdk\Model\LabelDescriptor
     */
    public function find($code)
    {
        return parent::find($code);
    }

    /**
     * Returns a list of label descriptors.
     *
     * @return \Wallee\Sdk\Model\LabelDescriptor[]
     */
    public function getAll()
    {
        return parent::getAll();
    }

    protected function fetchData()
    {
        $labelDescriptorService= new \Wallee\Sdk\Service\LabelDescriptionService($this->apiClient);
        return $labelDescriptorService->all();
    }

    protected function getId($entry)
    {
        /* @var \Wallee\Sdk\Model\LabelDescriptor $entry */
        return $entry->getId();
    }
}
