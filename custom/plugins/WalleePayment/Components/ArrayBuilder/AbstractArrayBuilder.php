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

namespace WalleePayment\Components\ArrayBuilder;

use Symfony\Component\DependencyInjection\ContainerInterface;

abstract class AbstractArrayBuilder
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
     *
     * @return array
     */
    abstract public function build();

    /**
     *
     * @param array[string,string]|\Wallee\Sdk\Model\DatabaseTranslatedString $string
     * @return string
     */
    protected function translate($string)
    {
        return $this->container->get('wallee_payment.translator')->translate($string);
    }
}
