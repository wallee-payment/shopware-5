<?php
namespace WalleePayment\Components\Provider;

use WalleePayment\Components\ApiClient;

/**
 * Provider of language information from the gateway.
 */
class Language extends AbstractProvider
{

    /**
     *
     * @var \Wallee\Sdk\ApiClient
     */
    private $apiClient;

    public function __construct(ApiClient $apiClient, \Zend_Cache_Core $cache)
    {
        parent::__construct($cache, 'wallee_payment_languages');
        $this->apiClient = $apiClient->getInstance();
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
