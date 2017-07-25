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

use Shopware\Components\Plugin\ConfigReader;

class ApiClient
{

    /**
     *
     * @var ConfigReader
     */
    private $configReader;

    /**
     *
     * @var string
     */
    private $baseGatewayUrl;

    /**
     *
     * @var \Wallee\Sdk\ApiClient
     */
    private $instance;

    /**
     * Constructor.
     *
     * @param ConfigReader $config
     * @apram string $baseGatewayUrl
     */
    public function __construct(ConfigReader $configReader, $baseGatewayUrl)
    {
        $this->configReader = $configReader;
        $this->baseGatewayUrl = $baseGatewayUrl;
    }

    /**
     * Returns the instance of the Wallee API client.
     *
     * @throws \Exception
     * @return \Wallee\Sdk\ApiClient
     */
    public function getInstance()
    {
        if ($this->instance == null) {
            $pluginConfig = $this->configReader->getByPluginName('WalleePayment');
            $userId = $pluginConfig['applicationUserId'];
            $applicationKey = $pluginConfig['applicationUserKey'];
            if ($userId && $applicationKey) {
                $this->instance = new \Wallee\Sdk\ApiClient($userId, $applicationKey);
                $this->instance->setBasePath($this->baseGatewayUrl . '/api');
            } else {
                throw new \Exception('The Wallee API user data are incomplete.');
            }
        }
        return $this->instance;
    }
}
