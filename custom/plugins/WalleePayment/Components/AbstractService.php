<?php
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
}
