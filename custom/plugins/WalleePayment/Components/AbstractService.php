<?php

/**
 * wallee Shopware 5
 *
 * This Shopware 5 extension enables to process payments with wallee (https://www.wallee.com/).
 *
 * @package Wallee_Payment
 * @author customweb GmbH (http://www.customweb.com/)
 * @license http://www.apache.org/licenses/LICENSE-2.0  Apache Software License (ASL 2.0)
 */

namespace WalleePayment\Components;

use Symfony\Component\DependencyInjection\ContainerInterface;

abstract class AbstractService
{

    /**
     *
     * @var ContainerInterface
     */
    protected $container;

    /**
     * Constructor.
     *
     * @param ContainerInterface $container
     */
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    /**
     * Creates and returns a new entity filter.
     *
     * @param string $fieldName
     * @param mixed $value
     * @param string $operator
     * @return \Wallee\Sdk\Model\EntityQueryFilter
     */
    protected function createEntityFilter($fieldName, $value, $operator = \Wallee\Sdk\Model\CriteriaOperator::EQUALS)
    {
        $filter = new \Wallee\Sdk\Model\EntityQueryFilter();
        $filter->setType(\Wallee\Sdk\Model\EntityQueryFilterType::LEAF);
        $filter->setOperator($operator);
        $filter->setFieldName($fieldName);
        $filter->setValue($value);
        return $filter;
    }

    /**
     * Creates and returns a new entity order by.
     *
     * @param string $fieldName
     * @param string $sortOrder
     * @return \Wallee\Sdk\Model\EntityQueryOrderBy
     */
    protected function createEntityOrderBy($fieldName, $sortOrder = \Wallee\Sdk\Model\EntityQueryOrderByType::DESC)
    {
        $orderBy = new \Wallee\Sdk\Model\EntityQueryOrderBy();
        $orderBy->setFieldName($fieldName);
        $orderBy->setSorting($sortOrder);
        return $orderBy;
    }

    /**
     * Returns the URL to the given controller action.
     *
     * @param string $controller
     * @param string $action
     * @param string $module
     * @param boolean $secure
     * @return string
     */
    protected function getUrl($controller, $action, $module = 'frontend', $secure = true, $params = [])
    {
        $params['module'] = $module;
        $params['controller'] = $controller;
        $params['action'] = $action;
        $params['forceSecure'] = $secure;
        /* @var \Enlight_Controller_Front $frontController */
        $frontController = $this->container->get('front');
        return $frontController->Router()->assemble($params);
    }
    
    /**
     * Changes the given string to have no more characters as specified.
     *
     * @param string $string
     * @param int $maxLength
     * @return string
     */
    protected function fixLength($string, $maxLength)
    {
        return mb_substr($string, 0, $maxLength, 'UTF-8');
    }
    
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
    
    /**
     * Traverses the stack of the given {@code $exception} to find an exception which matches the given
	 * {@code $exceptionType}.
	 * 
     * @param \Throwable $e
     * @param object $exceptionType
     * @return \Throwable|null
     */
    protected function findCause(\Throwable $exception, $exceptionType) {
        if ($exception instanceof $exceptionType) {
            return $exception;
        } elseif ($exception->getPrevious() != null) {
            return $this->findCause($exception->getPrevious(), $exceptionType);
        } else {
            return null;
        }
    }
}
