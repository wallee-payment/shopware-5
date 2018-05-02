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

abstract class AbstractProvider
{
    /**
     *
     * @var \Wallee\Sdk\ApiClient
     */
    protected $apiClient;
    
    /**
     *
     * @var \Zend_Cache_Core
     */
    private $cache;

    private $cacheKey;
    
    private $data = null;

    public function __construct(\Wallee\Sdk\ApiClient $apiClient, \Zend_Cache_Core $cache, $cacheKey)
    {
        $this->apiClient = $apiClient;
        $this->cache = $cache;
        $this->cacheKey = $cacheKey;
    }

    /**
     * Fetch the data from the remote server.
     *
     * @return array
     */
    abstract protected function fetchData();

    /**
     * Returns the id of the given entry.
     *
     * @param mixed $entry
     * @return string
     */
    abstract protected function getId($entry);

    /**
     * Returns a single entry by id.
     *
     * @param string $id
     * @return mixed
     */
    public function find($id)
    {
        if ($this->data == null) {
            $this->loadData();
        }

        if (isset($this->data[$id])) {
            return $this->data[$id];
        } else {
            return false;
        }
    }

    /**
     * Returns all entries.
     *
     * @return array
     */
    public function getAll()
    {
        if ($this->data == null) {
            $this->loadData();
        }

        return $this->data;
    }

    private function loadData()
    {
        $cachedData = $this->cache->load($this->cacheKey);
        if ($cachedData) {
            $this->data = $cachedData;
        } else {
            $fetchedData = $this->callApi(function () {
                return $this->fetchData();
            });
            $this->data = array();
            foreach ($fetchedData as $entry) {
                $this->data[$this->getId($entry)] = $entry;
            }

            $this->cache->save($this->data, $this->cacheKey);
        }
    }
    
    private function callApi($callback)
    {
        $lastException = null;
        $this->apiClient->setConnectionTimeout(5);
        for ($i = 0; $i < 5; $i++) {
            try {
                return $callback();
            } catch (\Wallee\Sdk\VersioningException $e) {
                $lastException = $e;
            } catch (\Wallee\Sdk\Http\ConnectionException $e) {
                $lastException = $e;
            } finally {
                $this->apiClient->setConnectionTimeout(20);
            }
        }
        throw $lastException;
    }
}
