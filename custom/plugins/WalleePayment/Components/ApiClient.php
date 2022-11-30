<?php

/**
 * wallee Shopware 5
 *
 * This Shopware 5 extension enables to process payments with wallee (https://www.wallee.com/).
 *
 * @package Wallee_Payment
 * @author wallee AG (http://www.wallee.com/)
 * @license http://www.apache.org/licenses/LICENSE-2.0  Apache Software License (ASL 2.0)
 */

namespace WalleePayment\Components;

use Composer\InstalledVersions;
use Shopware\Components\Plugin\ConfigReader;

class ApiClient
{
	const SHOP_SYSTEM = 'x-meta-shop-system';
	const SHOP_SYSTEM_VERSION = 'x-meta-shop-system-version';
	const SHOP_SYSTEM_AND_VERSION = 'x-meta-shop-system-and-version';

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
     * Returns the instance of the wallee API client.
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
				foreach (self::getDefaultHeaderData() as $key => $value) {
					$this->instance->addDefaultHeader($key, $value);
				}
            } else {
                throw new \Exception('The wallee API user data are incomplete.');
            }
        }
        return $this->instance;
    }
	
	
	/**
	 * @return array
	 */
	protected static function getDefaultHeaderData()
	{
		$version = InstalledVersions::getVersion('shopware/shopware');
		$shop_version = str_replace('v', '', $version);
		[$major_version, $minor_version, $_] = explode('.', $shop_version, 3);
		return [
			self::SHOP_SYSTEM             => 'shopware-5',
			self::SHOP_SYSTEM_VERSION     => $shop_version,
			self::SHOP_SYSTEM_AND_VERSION => 'shopware-' . $major_version . '.' . $minor_version,
		];
	}
}
