<?php
namespace WalleePayment\Components;

use Symfony\Component\DependencyInjection\ContainerInterface;

class Registry
{

    /**
     *
     * @var ContainerInterface
     */
    private $container;

    /**
     *
     * @var array
     */
    private $registry = [];

    /**
     * Constructor.
     *
     * @param ContainerInterface $container
     */
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    public function get($key)
    {
        if (isset($this->registry[$key])) {
            return $this->registry[$key];
        } else {
            return null;
        }
    }

    public function set($key, $value)
    {
        $this->registry[$key] = $value;
    }

    public function remove($key)
    {
        if (isset($this->registry[$key])) {
            unset($this->registry[$key]);
        }
    }
}
