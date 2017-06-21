<?php
namespace WalleePayment\Components\ArrayBuilder;

use Wallee\Sdk\Model\PaymentMethod as PaymentMethodModel;
use Symfony\Component\DependencyInjection\ContainerInterface;

class PaymentMethod extends AbstractArrayBuilder
{
    /**
     *
     * @var PaymentMethodModel
     */
    private $paymentMethod;

    /**
     * Constructor.
     *
     * @param ContainerInterface $container
     * @param PaymentMethodModel $paymentMethod
     */
    public function __construct(ContainerInterface $container, PaymentMethodModel $paymentMethod)
    {
        parent::__construct($container);
        $this->paymentMethod = $paymentMethod;
    }

    public function build()
    {
        return [
            'id' => $this->paymentMethod->getId(),
            'name' => $this->translate($this->paymentMethod->getName()),
            'description' => $this->translate($this->paymentMethod->getDescription())
        ];
    }
}
