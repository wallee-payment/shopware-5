<?php
namespace WalleePayment\Components;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Shopware\Components\Model\ModelManager;
use Shopware\Models\Order\Order as OrderModel;

class Session
{

    /**
     *
     * @var ContainerInterface
     */
    private $container;

    /**
     *
     * @var ModelManager
     */
    private $modelManager;

    /**
     * Constructor.
     *
     * @param ContainerInterface $container
     * @param ModelManager $modelManager
     */
    public function __construct(ContainerInterface $container, ModelManager $modelManager)
    {
        $this->container = $container;
        $this->modelManager = $modelManager;
    }

    /**
     * Returns the current temporary order.
     *
     * @return OrderModel
     */
    public function getTemporaryOrder()
    {
        return $this->modelManager->getRepository(OrderModel::class)->findOneBy(array(
            'temporaryId' => $this->getSessionId()
        ));
    }

    /**
     * Returns the id of the active session.
     *
     * @return string
     */
    public function getSessionId()
    {
        $sessionId = $this->container->get('session')->offsetGet('sessionId');
        if (empty($sessionId)) {
            $sessionId = session_id();
        }
        return $sessionId;
    }
}
