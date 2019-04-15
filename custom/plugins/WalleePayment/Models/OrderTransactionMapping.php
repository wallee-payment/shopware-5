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

namespace WalleePayment\Models;

use Shopware\Components\Model\ModelEntity;
use Doctrine\ORM\Mapping as ORM;
use Shopware\Models\Order\Order;
use Shopware\Models\Shop\Shop;

/**
 * @ORM\Table(name="wallee_payment_order_transaction_mapping",
 *      uniqueConstraints={
 *          @ORM\UniqueConstraint(name="UNQ_ORDER_ID", columns={"order_id"}),
 *          @ORM\UniqueConstraint(name="UNQ_TEMPORARY_ID", columns={"temporary_id"})
 *      },
 *      indexes={
 *          @ORM\Index(name="IDX_SPACE_ID", columns={"space_id"}),
 *          @ORM\Index(name="IDX_TRANSACTION_ID", columns={"transaction_id"})
 *      }
 * )
 * @ORM\Entity
 * @ORM\NamedQueries({
 *      @ORM\NamedQuery(
 *          name="getOrderEmailSent",
 *          query="SELECT m.orderEmailSent FROM WalleePayment\Models\OrderTransactionMapping m WHERE m.orderId = :orderId"
 *      ),
 *      @ORM\NamedQuery(
 *          name="getOrderEmailData",
 *          query="SELECT m.orderEmailSent, m.orderEmailVariables FROM WalleePayment\Models\OrderTransactionMapping m WHERE m.orderId = :orderId"
 *      ),
 *      @ORM\NamedQuery(
 *          name="updateOrderEmailSent",
 *          query="UPDATE WalleePayment\Models\OrderTransactionMapping m SET m.orderEmailSent = true, m.orderEmailVariables = null WHERE m.orderId = :orderId"
 *      ),
 *      @ORM\NamedQuery(
 *          name="lock",
 *          query="UPDATE WalleePayment\Models\OrderTransactionMapping m SET m.lockedAt = CURRENT_TIMESTAMP() WHERE m.orderId = :orderId"
 *      )
 * })
 */
class OrderTransactionMapping extends ModelEntity
{

    /**
     * @ORM\Column(name="id", type="integer", nullable=false, options={"unsigned"=true})
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     *
     * @var integer $id
     */
    private $id;

    /**
     * @ORM\Column(name="order_id", type="integer", nullable=true)
     *
     * @var int $orderId
     */
    private $orderId;

    /**
     * @ORM\Column(name="shop_id", type="integer", nullable=false, options={"unsigned"=true})
     *
     * @var int $shopId
     */
    private $shopId;

    /**
     * @ORM\Column(name="temporary_id", type="string", length=255, nullable=true)
     *
     * @var string $temporaryId
     */
    private $temporaryId;

    /**
     * @ORM\Column(name="space_id", type="bigint", nullable=false, options={"unsigned"=true})
     *
     * @var string $spaceId
     */
    private $spaceId = null;

    /**
     * @ORM\Column(name="transaction_id", type="bigint", nullable=false, options={"unsigned"=true})
     *
     * @var string $transactionId
     */
    private $transactionId = null;

    /**
     * @ORM\ManyToOne(targetEntity="\Shopware\Models\Order\Order")
     * @ORM\JoinColumn(name="order_id", referencedColumnName="id")
     *
     * @var Order $order
     */
    private $order;

    /**
     * @ORM\ManyToOne(targetEntity="\Shopware\Models\Shop\Shop")
     * @ORM\JoinColumn(name="shop_id", referencedColumnName="id")
     *
     * @var \Shopware\Models\Shop\Shop
     */
    private $shop;

    /**
     * @ORM\Column(name="order_email_sent", type="boolean", nullable=false)
     *
     * @var boolean $orderEmailSent
     */
    private $orderEmailSent = false;

    /**
     * @ORM\Column(name="order_email_variables", type="object", nullable=true)
     *
     * @var string $orderEmailVariables
     */
    private $orderEmailVariables;

    /**
     * @ORM\Column(name="locked_at", type="datetime", nullable=true)
     *
     * @var \DateTime $lockedAt
     */
    private $lockedAt = null;
    
    /**
     *
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     *
     * @return Order
     */
    public function getOrder()
    {
        return $this->order;
    }

    /**
     *
     * @param Order $order
     * @return OrderTransactionMapping
     */
    public function setOrder(Order $order)
    {
        if (empty($order->getId())) {
            return $this;
        }
        $this->order = $order;
        $this->setShop($order->getShop());
        if ($order->getTemporaryId() != null) {
            $this->temporaryId = $order->getTemporaryId();
        } else {
            $this->orderId = $order->getId();
            $this->temporaryId = null;
        }
        return $this;
    }
    
    /**
     *
     * @return Shop|null
     */
    public function getShop()
    {
        return $this->shop;
    }
    
    public function setShop(Shop $shop)
    {
        $this->shopId = $shop->getId();
        return $this;
    }

    public function getTemporaryId()
    {
        return $this->temporaryId;
    }

    public function setTemporaryId($temporaryId)
    {
        $this->temporaryId = $temporaryId;
        return $this;
    }

    public function getSpaceId()
    {
        return $this->spaceId;
    }

    public function setSpaceId($spaceId)
    {
        $this->spaceId = $spaceId;
        return $this;
    }

    public function getTransactionId()
    {
        return $this->transactionId;
    }

    public function setTransactionId($transactionId)
    {
        $this->transactionId = $transactionId;
        return $this;
    }

    public function isOrderEmailSent()
    {
        return $this->orderEmailSent;
    }

    public function setOrderEmailSent($orderEmailSent)
    {
        $this->orderEmailSent = $orderEmailSent;
    }

    public function getOrderEmailVariables()
    {
        return $this->orderEmailVariables;
    }

    public function setOrderEmailVariables($orderEmailVariables)
    {
        $this->orderEmailVariables = $orderEmailVariables;
    }
}
