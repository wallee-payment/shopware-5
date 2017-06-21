<?php
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
     * @param map[string,string]|\Wallee\Sdk\Model\DatabaseTranslatedString $string
     * @return string
     */
    protected function translate($string)
    {
        return $this->container->get('wallee_payment.translator')->translate($string);
    }
}
