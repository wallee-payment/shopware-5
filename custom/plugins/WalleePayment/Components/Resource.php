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

class Resource
{
    
    /**
     *
     * @var \WalleePayment\Components\Provider\Language
     */
    private $languageProvider;

    /**
     *
     * @var string
     */
    private $baseGatewayUrl;

    /**
     * Constructor.
     *
     * @param \WalleePayment\Components\Provider\Language $languageProvider
     * @param string $baseGatewayUrl
     */
    public function __construct(\WalleePayment\Components\Provider\Language $languageProvider, $baseGatewayUrl)
    {
        $this->languageProvider = $languageProvider;
        $this->baseGatewayUrl = $baseGatewayUrl;
    }

    /**
     * Returns the URL to a resource on wallee in the given context (space, space view, language).
     *
     * @param string $path
     * @param string $language
     * @param int $spaceId
     * @param int $spaceViewId
     * @return string
     */
    public function getResourceUrl($path, $language = null, $spaceId = null, $spaceViewId = null)
    {
        $url = $this->baseGatewayUrl;
        if (! empty($language) && $this->getLanguage($language)) {
            $url .= '/' . str_replace('_', '-', $language);
        }

        if (! empty($spaceId)) {
            $url .= '/s/' . $spaceId;
        }

        if (! empty($spaceViewId)) {
            $url .= '/' . $spaceViewId;
        }

        $url .= '/resource/' . $path;
        return $url;
    }
    
    private function getLanguage($shopLanguageCode)
    {
        if ($this->languageProvider->find($shopLanguageCode) !== false) {
            return $shopLanguageCode;
        } else {
            return null;
        }
    }
}
