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

namespace WalleePayment\Components;

class Resource
{

    /**
     *
     * @var string
     */
    private $baseGatewayUrl;

    /**
     * Constructor.
     *
     * @param string $baseGatewayUrl
     */
    public function __construct(string $baseGatewayUrl)
    {
        $this->baseGatewayUrl = $baseGatewayUrl;
    }

    /**
     * Returns the URL to a resource on Wallee in the given context (space, space view, language).
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
        if (! empty($language)) {
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
}
