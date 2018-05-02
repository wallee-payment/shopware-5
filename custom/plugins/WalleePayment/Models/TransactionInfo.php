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
 * @ORM\Table(name="wallee_payment_transaction_info",
 * uniqueConstraints={
 * @ORM\UniqueConstraint(name="UNQ_TRANSACTION_ID_SPACE_ID", columns={"transaction_id", "space_id"}),
 * @ORM\UniqueConstraint(name="UNQ_ORDER_ID", columns={"order_id"})
 * }
 * )
 * @ORM\Entity
 * @ORM\HasLifecycleCallbacks
 */
class TransactionInfo extends ModelEntity
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
     * @ORM\Column(name="transaction_id", type="bigint", nullable=false, options={"unsigned"=true})
     *
     * @var int $transactionId
     */
    private $transactionId;

    /**
     * @ORM\Column(name="state", type="string", nullable=false)
     *
     * @var string $state
     */
    private $state;

    /**
     * @ORM\Column(name="space_id", type="bigint", nullable=false, options={"unsigned"=true})
     *
     * @var int $spaceId
     */
    private $spaceId;

    /**
     * @ORM\Column(name="space_view_id", type="bigint", nullable=true, options={"unsigned"=true})
     *
     * @var int $spaceViewId
     */
    private $spaceViewId;

    /**
     * @ORM\Column(name="language", type="string", nullable=true)
     *
     * @var string $language
     */
    private $language;

    /**
     * @ORM\Column(name="currency", type="string", nullable=false)
     *
     * @var string $currency
     */
    private $currency;

    /**
     * @ORM\Column(name="created_at", type="datetime", nullable=false)
     *
     * @var \DateTime $createdAt
     */
    private $createdAt = null;

    /**
     * @ORM\Column(name="authorization_amount", type="decimal", nullable=false, precision=19, scale=8)
     *
     * @var float $authorizationAmount
     */
    private $authorizationAmount;

    /**
     * @ORM\Column(name="image", type="string", nullable=true, length=512)
     *
     * @var string $image
     */
    private $image;

    /**
     * @ORM\Column(name="labels", type="object", nullable=true)
     *
     * @var string $labels
     */
    private $labels;

    /**
     * @ORM\Column(name="payment_method_id", type="bigint", nullable=true, options={"unsigned"=true})
     *
     * @var int $paymentMethodId
     */
    private $paymentMethodId;

    /**
     * @ORM\Column(name="connector_id", type="bigint", nullable=true, options={"unsigned"=true})
     *
     * @var int $connectorId
     */
    private $connectorId;

    /**
     * @ORM\Column(name="order_id", type="integer", nullable=false, options={"unsigned"=true})
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
     * @ORM\Column(name="failure_reason", type="object", nullable=true)
     *
     * @var string $failureReason
     */
    private $failureReason;

    /**
     * @ORM\Column(name="user_failure_message", type="string", nullable=true, length=512)
     *
     * @var string $userFailureMessage
     */
    private $userFailureMessage;

    /**
     *
     * @return int
     */
    public function getId()
    {
        return $this->id;
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

    public function getState()
    {
        return $this->state;
    }

    public function setState($state)
    {
        $this->state = $state;
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

    public function getSpaceViewId()
    {
        return $this->spaceViewId;
    }

    public function setSpaceViewId($spaceViewId)
    {
        $this->spaceViewId = $spaceViewId;
        return $this;
    }

    public function getLanguage()
    {
        return $this->language;
    }

    public function setLanguage($language)
    {
        $this->language = $language;
        return $this;
    }

    public function getCurrency()
    {
        return $this->currency;
    }

    public function setCurrency($currency)
    {
        $this->currency = $currency;
        return $this;
    }

    public function getCreatedAt()
    {
        return $this->createdAt;
    }

    public function getAuthorizationAmount()
    {
        return $this->authorizationAmount;
    }

    public function setAuthorizationAmount($authorizationAmount)
    {
        $this->authorizationAmount = $authorizationAmount;
        return $this;
    }

    public function getImage()
    {
        return $this->image;
    }

    public function setImage($image)
    {
        $this->image = $image;
        return $this;
    }

    public function getLabels()
    {
        return $this->labels;
    }

    public function setLabels($labels)
    {
        $this->labels = $labels;
        return $this;
    }

    public function getPaymentMethodId()
    {
        return $this->paymentMethodId;
    }

    public function setPaymentMethodId($paymentMethodId)
    {
        $this->paymentMethodId = $paymentMethodId;
        return $this;
    }

    public function getConnectorId()
    {
        return $this->connectorId;
    }

    public function setConnectorId($connectorId)
    {
        $this->connectorId = $connectorId;
        return $this;
    }

    public function getOrderId()
    {
        return $this->orderId;
    }

    public function setOrderId($orderId)
    {
        $this->orderId = $orderId;
        return $this;
    }

    public function getShopId()
    {
        return $this->shopId;
    }

    public function setShopId($shopId)
    {
        $this->shopId = $shopId;
        return $this;
    }

    public function getFailureReason()
    {
        return $this->failureReason;
    }

    public function setFailureReason($failureReason)
    {
        $this->failureReason = $failureReason;
        return $this;
    }

    public function getUserFailureMessage()
    {
        return $this->userFailureMessage;
    }

    public function setUserFailureMessage($userFailureMessage)
    {
        $this->userFailureMessage = $userFailureMessage;
        return $this;
    }

    /**
     * @ORM\PrePersist
     * @ORM\PreUpdate
     */
    public function updateTimestamps()
    {
        if ($this->getCreatedAt() == null) {
            $this->createdAt = new \DateTime('now');
        }
    }

    /**
     *
     * @return Order|null
     */
    public function getOrder()
    {
        return $this->order;
    }

    /**
     *
     * @return Shop|null
     */
    public function getShop()
    {
        return $this->shop;
    }

    /**
     *
     * @return boolean
     */
    public function canDownloadInvoice()
    {
        if (in_array($this->getState(), [
            \Wallee\Sdk\Model\TransactionState::COMPLETED,
            \Wallee\Sdk\Model\TransactionState::FULFILL,
            \Wallee\Sdk\Model\TransactionState::DECLINE
        ])) {
            return true;
        } else {
            return false;
        }
    }

    /**
     *
     * @return boolean
     */
    public function canDownloadPackingSlip()
    {
        if ($this->getState() == \Wallee\Sdk\Model\TransactionState::FULFILL) {
            return true;
        } else {
            return false;
        }
    }
}
