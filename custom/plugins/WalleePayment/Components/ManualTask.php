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

use Symfony\Component\DependencyInjection\ContainerInterface;
use Shopware\Components\Plugin\ConfigReader;
use Shopware\Components\Model\ModelManager;
use Shopware\Models\Shop\Shop;

class ManualTask extends AbstractService
{

    /**
     *
     * @var ModelManager
     */
    private $modelManager;

    /**
     *
     * @var ConfigReader
     */
    private $configReader;

    /**
     *
     * @var \Zend_Cache_Core
     */
    private $cache;

    /**
     *
     * @var string
     */
    private $cacheKey = 'wallee_payment_manual_tasks';

    /**
     *
     * @var \Wallee\Sdk\Service\ManualTaskService
     */
    private $manualTaskService;

    /**
     * Constructor.
     *
     * @param ContainerInterface $container
     * @param ModelManager $modelManager
     * @param ConfigReader $configReader
     * @param \Zend_Cache_Core $cache
     * @param ApiClient $apiClient
     */
    public function __construct(ContainerInterface $container, ModelManager $modelManager, ConfigReader $configReader, \Zend_Cache_Core $cache, ApiClient $apiClient)
    {
        parent::__construct($container);
        $this->modelManager = $modelManager;
        $this->configReader = $configReader;
        $this->cache = $cache;
        $this->manualTaskService = new \Wallee\Sdk\Service\ManualTaskService($apiClient->getInstance());
    }

    /**
     * Returns the number of open manual tasks.
     *
     * @return array
     */
    public function getNumberOfManualTasks()
    {
        $cachedData = $this->cache->load($this->cacheKey);
        if ($cachedData) {
            return $cachedData;
        } else {
            return $this->update();
        }
    }

    /**
     * Updates the number of open manual tasks.
     *
     * @return array
     */
    public function update()
    {
        $numberOfManualTasks = [];
        $spaceIds = [];
        foreach ($this->modelManager->getRepository(Shop::class)->findAll() as $shop) {
            $pluginConfig = $this->configReader->getByPluginName('WalleePayment', $shop);
            $spaceId = $pluginConfig['spaceId'];
            if ($spaceId && ! in_array($spaceId, $spaceIds)) {
                try {
                    $numberBySpace = $this->manualTaskService->count($spaceId, $this->createEntityFilter('state', \Wallee\Sdk\Model\ManualTaskState::OPEN));
                    if ($numberBySpace > 0) {
                        $numberOfManualTasks[$spaceId] = $numberBySpace;
                    }

                    $this->cache->save($numberOfManualTasks, $this->cacheKey);
                } catch (\Exception $e) {
                    // If the number of manual tasks cannot be fetched from wallee, it is ignored as not critical.
                }
                $spaceIds[] = $spaceId;
            }
        }
        return $numberOfManualTasks;
    }
}
