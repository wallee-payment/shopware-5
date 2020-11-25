<?php

/**
 * wallee Shopware 5
 *
 * This Shopware 5 extension enables to process payments with wallee (https://www.wallee.com/).
 *
 * @package Wallee_Payment
 * @author wallee AG (http://www.wallee.com/)
 * @license http://www.apache.org/licenses/LICENSE-2.0  Apache Software License (ASL 2.0)
 */

namespace WalleePayment\Models;

use Shopware\Components\Model\ModelEntity;
use Doctrine\ORM\Mapping as ORM;
use Shopware\Models\Payment\Payment;

/**
 * @ORM\Table(name="wallee_payment_method_configuration",
 * uniqueConstraints={
 * @ORM\UniqueConstraint(name="UNQ_SPACE_ID_CONFIGURATION_ID", columns={"space_id", "configuration_id"}),
 * },
 * indexes={
 * @ORM\Index(name="IDX_SPACE_ID", columns={"space_id"}),
 * @ORM\Index(name="IDX_CONFIGURATION_ID", columns={"configuration_id"})
 * }
 * )
 * @ORM\Entity
 * @ORM\HasLifecycleCallbacks
 */
class PaymentMethodConfiguration extends ModelEntity
{
    const STATE_ACTIVE = 'ACTIVE';

    const STATE_INACTIVE = 'INACTIVE';

    const STATE_HIDDEN = 'HIDDEN';

    /**
     * @ORM\Column(name="id", type="integer", nullable=false, options={"unsigned"=true})
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     *
     * @var integer $id
     */
    private $id;

    /**
     * @ORM\Column(name="state", type="string", nullable=false)
     *
     * @var string $state
     */
    private $state;

    /**
     * @ORM\Column(name="space_id", type="bigint", nullable=false, options={"unsigned"=true})
     *
     * @var string $spaceId
     */
    private $spaceId;

    /**
     * @ORM\Column(name="created_at", type="datetime", nullable=false)
     *
     * @var \DateTime $createdAt
     */
    private $createdAt = null;

    /**
     * @ORM\Column(name="updated_at", type="datetime", nullable=false)
     *
     * @var \DateTime $updatedAt
     */
    private $updatedAt = null;

    /**
     * @ORM\Column(name="configuration_id", type="bigint", nullable=false, options={"unsigned"=true})
     *
     * @var string $configurationId
     */
    private $configurationId;

    /**
     * @ORM\Column(name="configuration_name", type="string", nullable=false, length=150)
     *
     * @var string $configurationName
     */
    private $configurationName;

    /**
     * @ORM\Column(name="title", type="object", nullable=true)
     *
     * @var string $title
     */
    private $title;

    /**
     * @ORM\Column(name="description", type="object", nullable=true)
     *
     * @var string $description
     */
    private $description;

    /**
     * @ORM\Column(name="image", type="string", nullable=true, length=512)
     *
     * @var string $image
     */
    private $image;

    /**
     * @ORM\Column(name="sort_order", type="integer", nullable=false)
     *
     * @var string $sortOrder
     */
    private $sortOrder;

    /**
     * @ORM\Column(name="payment_id", type="integer", nullable=true)
     *
     * @var string $paymentId
     */
    private $paymentId;

    /**
     * @ORM\OneToOne(targetEntity="\Shopware\Models\Payment\Payment")
     * @ORM\JoinColumn(name="payment_id", referencedColumnName="id")
     *
     * @var Payment
     */
    private $payment;

    /**
     *
     * @return int
     */
    public function getId()
    {
        return $this->id;
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

    public function getCreatedAt()
    {
        return $this->createdAt;
    }

    public function getUpdatedAt()
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt($updatedAt)
    {
        $this->updatedAt = $updatedAt;
        return $this;
    }

    public function getConfigurationId()
    {
        return $this->configurationId;
    }

    public function setConfigurationId($configurationId)
    {
        $this->configurationId = $configurationId;
        return $this;
    }

    public function getConfigurationName()
    {
        return $this->configurationName;
    }

    public function setConfigurationName($configurationName)
    {
        $this->configurationName = $configurationName;
        return $this;
    }

    public function getTitle()
    {
        return $this->title;
    }

    public function setTitle($title)
    {
        $this->title = $title;
        return $this;
    }

    public function getDescription()
    {
        return $this->description;
    }

    public function setDescription($description)
    {
        $this->description = $description;
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

    public function getSortOrder()
    {
        return $this->sortOrder;
    }

    public function setSortOrder($sortOrder)
    {
        $this->sortOrder = $sortOrder;
        return $this;
    }

    public function getPayment()
    {
        return $this->payment;
    }

    public function setPayment(Payment $payment)
    {
        $this->paymentId = $payment->getId();
        $this->payment = $payment;
        return $this;
    }

    /**
     * @ORM\PrePersist
     * @ORM\PreUpdate
     */
    public function updateTimestamps()
    {
        $this->setUpdatedAt(new \DateTime('now'));

        if ($this->getCreatedAt() == null) {
            $this->createdAt = new \DateTime('now');
        }
    }
}
