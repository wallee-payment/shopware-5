<?php
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
 *      }
 * )
 * @ORM\Entity
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
        $this->order = $order;
        $this->shopId = $order->getShop()->getId();
        $this->shop = $order->getShop();
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

    public function lock()
    {
        $this->lockedAt = new \DateTime('now');
        return $this;
    }
}
