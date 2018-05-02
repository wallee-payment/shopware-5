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
        $sessionId = $this->container->get('Session')->get('sessionId');
        if (empty($sessionId)) {
            $sessionId = session_id();
        }
        return $sessionId;
    }
}
