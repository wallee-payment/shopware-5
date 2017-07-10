<?php
namespace WalleePayment\Components\ArrayBuilder;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Wallee\Sdk\Model\LabelDescriptor;

class Label extends AbstractArrayBuilder
{
    /**
     *
     * @var LabelDescriptor
     */
    private $descriptor;

    /**
     *
     * @var string
     */
    private $value;

    /**
     * Constructor.
     *
     * @param ContainerInterface $container
     * @param LabelDescriptor $descriptor
     * @param string $value
     */
    public function __construct(ContainerInterface $container, LabelDescriptor $descriptor, $value)
    {
        parent::__construct($container);
        $this->descriptor = $descriptor;
        $this->value = $value;
    }

    /**
     *
     * @return \Wallee\Sdk\Model\LabelDescriptor
     */
    public function getDescriptor()
    {
        return $this->descriptor;
    }

    public function build()
    {
        return [
            'descriptor' => [
                'id' => $this->descriptor->getId(),
                'name' => $this->translate($this->descriptor->getName()),
                'description' => $this->translate($this->descriptor->getDescription()),
                'weight' => $this->descriptor->getWeight()
            ],
            'value' => $this->value
        ];
    }
}
