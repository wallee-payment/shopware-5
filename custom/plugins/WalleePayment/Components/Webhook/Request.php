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

namespace WalleePayment\Components\Webhook;

class Request
{
    private $eventId;

    private $entityId;

    private $listenerEntityId;

    private $listenerEntityTechnicalName;

    private $spaceId;

    private $webhookListenerId;

    private $timestamp;

    /**
     * Constructor.
     *
     * @param \stdClass $model
     */
    public function __construct($model)
    {
        $this->eventId = $model->eventId;
        $this->entityId = $model->entityId;
        $this->listenerEntityId = $model->listenerEntityId;
        $this->listenerEntityTechnicalName = $model->listenerEntityTechnicalName;
        $this->spaceId = $model->spaceId;
        $this->webhookListenerId = $model->webhookListenerId;
        $this->timestamp = $model->timestamp;
    }

    /**
     * Returns the webhook event's id.
     *
     * @return int
     */
    public function getEventId()
    {
        return $this->eventId;
    }

    /**
     * Returns the id of the webhook event's entity.
     *
     * @return int
     */
    public function getEntityId()
    {
        return $this->entityId;
    }

    /**
     * Returns the id of the webhook's listener entity.
     *
     * @return int
     */
    public function getListenerEntityId()
    {
        return $this->listenerEntityId;
    }

    /**
     * Returns the technical name of the webhook's listener entity.
     *
     * @return string
     */
    public function getListenerEntityTechnicalName()
    {
        return $this->listenerEntityTechnicalName;
    }

    /**
     * Returns the space id.
     *
     * @return int
     */
    public function getSpaceId()
    {
        return $this->spaceId;
    }

    /**
     * Returns the id of the webhook listener.
     *
     * @return int
     */
    public function getWebhookListenerId()
    {
        return $this->webhookListenerId;
    }

    /**
     * Returns the webhook's timestamp.
     *
     * @return string
     */
    public function getTimestamp()
    {
        return $this->timestamp;
    }
}
