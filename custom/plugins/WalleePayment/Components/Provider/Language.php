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
 * Provider of language information from the gateway.
 */
class Language extends AbstractProvider
{
    public function __construct(ApiClient $apiClient, \Zend_Cache_Core $cache)
    {
        parent::__construct($apiClient->getInstance(), $cache, 'wallee_payment_languages');
    }

    /**
     * Returns the language by the given code.
     *
     * @param int $code
     * @return \Wallee\Sdk\Model\RestLanguage
     */
    public function find($code)
    {
        return parent::find($code);
    }

    /**
     * Returns the primary language in the given group.
     *
     * @param string $code
     * @return \Wallee\Sdk\Model\RestLanguage
     */
    public function findPrimary($code)
    {
        $code = substr($code, 0, 2);
        foreach ($this->getAll() as $language) {
            if ($language->getIso2Code() == $code && $language->getPrimaryOfGroup()) {
                return $language;
            }
        }

        return false;
    }

    /**
     * Returns a list of languages.
     *
     * @return \Wallee\Sdk\Model\RestLanguage[]
     */
    public function getAll()
    {
        return parent::getAll();
    }

    protected function fetchData()
    {
        $methodService = new \Wallee\Sdk\Service\LanguageService($this->apiClient);
        return $methodService->all();
    }

    protected function getId($entry)
    {
        /* @var \Wallee\Sdk\Model\RestLanguage $entry */
        return $entry->getIetfCode();
    }
}
