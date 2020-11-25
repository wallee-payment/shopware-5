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

namespace WalleePayment\Subscriber\Webhook;

use Enlight\Event\SubscriberInterface;

abstract class AbstractSubscriber implements SubscriberInterface
{
    
    /**
     * In case a \Wallee\Sdk\Http\ConnectionException or a \Wallee\Sdk\VersioningException occurs, the {@code $callback} function is called again.
     *
     * @param \Wallee\Sdk\ApiClient $apiClient
     * @param callable $callback
     * @throws \Wallee\Sdk\Http\ConnectionException
     * @throws \Wallee\Sdk\VersioningException
     * @return mixed
     */
    protected function callApi(\Wallee\Sdk\ApiClient $apiClient, $callback)
    {
        $lastException = null;
        $apiClient->setConnectionTimeout(5);
        for ($i = 0; $i < 5; $i++) {
            try {
                return $callback();
            } catch (\Wallee\Sdk\VersioningException $e) {
                $lastException = $e;
            } catch (\Wallee\Sdk\Http\ConnectionException $e) {
                $lastException = $e;
            } finally {
                $apiClient->setConnectionTimeout(20);
            }
        }
        throw $lastException;
    }
}
