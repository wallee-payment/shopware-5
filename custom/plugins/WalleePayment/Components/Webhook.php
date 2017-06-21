<?php
namespace WalleePayment\Components;

use Symfony\Component\DependencyInjection\ContainerInterface;
use WalleePayment\Components\Webhook\Entity;
use Shopware\Components\Plugin\ConfigReader;
use Shopware\Models\Shop\Shop;
use Shopware\Components\Model\ModelManager;

class Webhook extends AbstractService
{

    /**
     *
     * @var ConfigReader
     */
    private $configReader;

    /**
     *
     * @var ModelManager
     */
    private $modelManager;

    /**
     *
     * @var \Wallee\Sdk\ApiClient
     */
    private $apiClient;

    /**
     * The transaction url API service.
     *
     * @var \Wallee\Sdk\Service\WebhookUrlService
     */
    private $webhookUrlService;

    /**
     * The transaction listener API service.
     *
     * @var \Wallee\Sdk\Service\WebhookListenerService
     */
    private $webhookListenerService;

    private $webhookEntities = array();

    /**
     * Constructor.
     *
     * @param ContainerInterface $container
     * @param ModelManager $modelManager
     * @param ConfigReader $configReader
     * @param ApiClient $apiClient
     */
    public function __construct(ContainerInterface $container, ModelManager $modelManager, ConfigReader $configReader, ApiClient $apiClient)
    {
        parent::__construct($container);
        $this->modelManager = $modelManager;
        $this->configReader = $configReader;
        $this->apiClient = $apiClient->getInstance();
        $this->webhookUrlService = new \Wallee\Sdk\Service\WebhookUrlService($this->apiClient);
        $this->webhookListenerService = new \Wallee\Sdk\Service\WebhookListenerService($this->apiClient);

        $this->webhookEntities[] = new Entity(1487165678181, 'Manual Task', array(
            \Wallee\Sdk\Model\ManualTask::STATE_DONE,
            \Wallee\Sdk\Model\ManualTask::STATE_EXPIRED,
            \Wallee\Sdk\Model\ManualTask::STATE_OPEN
        ));
        $this->webhookEntities[] = new Entity(1472041857405, 'Payment Method Configuration', array(
            \Wallee\Sdk\Model\PaymentMethodConfiguration::STATE_ACTIVE,
            \Wallee\Sdk\Model\PaymentMethodConfiguration::STATE_DELETED,
            \Wallee\Sdk\Model\PaymentMethodConfiguration::STATE_DELETING,
            \Wallee\Sdk\Model\PaymentMethodConfiguration::STATE_INACTIVE
        ), true);
        $this->webhookEntities[] = new Entity(1472041829003, 'Transaction', array(
            \Wallee\Sdk\Model\Transaction::STATE_AUTHORIZED,
            \Wallee\Sdk\Model\Transaction::STATE_DECLINE,
            \Wallee\Sdk\Model\Transaction::STATE_FAILED,
            \Wallee\Sdk\Model\Transaction::STATE_FULFILL,
            \Wallee\Sdk\Model\Transaction::STATE_VOIDED,
            \Wallee\Sdk\Model\Transaction::STATE_COMPLETED,
            \Wallee\Sdk\Model\Transaction::STATE_PROCESSING,
            \Wallee\Sdk\Model\Transaction::STATE_CONFIRMED
        ));
        $this->webhookEntities[] = new Entity(1472041819799, 'Delivery Indication', array(
            \Wallee\Sdk\Model\DeliveryIndication::STATE_MANUAL_CHECK_REQUIRED
        ));
        $this->webhookEntities[] = new Entity(1472041816898, 'Transaction Invoice', array(
            \Wallee\Sdk\Model\TransactionInvoice::STATE_NOT_APPLICABLE,
            \Wallee\Sdk\Model\TransactionInvoice::STATE_PAID,
            \Wallee\Sdk\Model\TransactionInvoice::STATE_DERECOGNIZED
        ));
    }

    /**
     * Installs the necessary webhooks in Wallee.
     */
    public function install()
    {
        $spaceIds = array();
        foreach ($this->modelManager->getRepository(Shop::class)->findAll() as $shop) {
            $pluginConfig = $this->configReader->getByPluginName('WalleePayment', $shop);
            $spaceId = $pluginConfig['spaceId'];
            if ($spaceId && ! in_array($spaceId, $spaceIds)) {
                $webhookUrl = $this->getWebhookUrl($spaceId);
                if ($webhookUrl == null) {
                    $webhookUrl = $this->createWebhookUrl($spaceId);
                }

                $existingListeners = $this->getWebhookListeners($spaceId, $webhookUrl);
                foreach ($this->webhookEntities as $webhookEntity) {
                    /* @var Entity $webhookEntity */
                    $exists = false;
                    foreach ($existingListeners as $existingListener) {
                        if ($existingListener->getEntity() == $webhookEntity->getId()) {
                            $exists = true;
                        }
                    }

                    if (! $exists) {
                        $this->createWebhookListener($webhookEntity, $spaceId, $webhookUrl);
                    }
                }
                $spaceIds[] = $spaceId;
            }
        }
    }

    /**
     * Create a webhook listener.
     *
     * @param Entity $entity
     * @param int $spaceId
     * @param \Wallee\Sdk\Model\WebhookUrl $webhookUrl
     * @return \Wallee\Sdk\Model\WebhookListenerCreate
     */
    private function createWebhookListener(Entity $entity, $spaceId, \Wallee\Sdk\Model\WebhookUrl $webhookUrl)
    {
        $webhookListener = new \Wallee\Sdk\Model\WebhookListenerCreate();
        $webhookListener->setEntity($entity->getId());
        $webhookListener->setEntityStates($entity->getStates());
        $webhookListener->setLinkedSpaceId($spaceId);
        $webhookListener->setName('Shopware ' . $entity->getName());
        $webhookListener->setState(\Wallee\Sdk\Model\WebhookListenerCreate::STATE_ACTIVE);
        $webhookListener->setUrl($webhookUrl->getId());
        $webhookListener->setNotifyEveryChange($entity->isNotifyEveryChange());
        return $this->webhookListenerService->create($spaceId, $webhookListener);
    }

    /**
     * Returns the existing webhook listeners.
     *
     * @param int $spaceId
     * @param \Wallee\Sdk\Model\WebhookUrl $webhookUrl
     * @return \Wallee\Sdk\Model\WebhookListener[]
     */
    private function getWebhookListeners($spaceId, \Wallee\Sdk\Model\WebhookUrl $webhookUrl)
    {
        $query = new \Wallee\Sdk\Model\EntityQuery();
        $filter = new \Wallee\Sdk\Model\EntityQueryFilter();
        $filter->setType(\Wallee\Sdk\Model\EntityQueryFilter::TYPE_AND);
        $filter->setChildren(array(
            $this->createEntityFilter('state', \Wallee\Sdk\Model\WebhookListener::STATE_ACTIVE),
            $this->createEntityFilter('url.id', $webhookUrl->getId())
        ));
        $query->setFilter($filter);
        return $this->webhookListenerService->search($spaceId, $query);
    }

    /**
     * Creates a webhook url.
     *
     * @param int $spaceId
     * @return \Wallee\Sdk\Model\WebhookUrlCreate
     */
    private function createWebhookUrl($spaceId)
    {
        $webhookUrl = new \Wallee\Sdk\Model\WebhookUrlCreate();
        $webhookUrl->setLinkedSpaceId($spaceId);
        $webhookUrl->setUrl($this->getHandleUrl());
        $webhookUrl->setState(\Wallee\Sdk\Model\WebhookUrlCreate::STATE_ACTIVE);
        $webhookUrl->setName('Shopware');
        return $this->webhookUrlService->create($spaceId, $webhookUrl);
    }

    /**
     * Returns the existing webhook url if there is one.
     *
     * @param int $spaceId
     * @return \Wallee\Sdk\Model\WebhookUrl
     */
    private function getWebhookUrl($spaceId)
    {
        $query = new \Wallee\Sdk\Model\EntityQuery();
        $query->setNumberOfEntities(1);
        $filter = new \Wallee\Sdk\Model\EntityQueryFilter();
        $filter->setType(\Wallee\Sdk\Model\EntityQueryFilter::TYPE_AND);
        $filter->setChildren(array(
            $this->createEntityFilter('state', \Wallee\Sdk\Model\WebhookUrl::STATE_ACTIVE),
            $this->createEntityFilter('url', $this->getHandleUrl())
        ));
        $query->setFilter($filter);
        $result = $this->webhookUrlService->search($spaceId, $query);
        if (! empty($result)) {
            return $result[0];
        } else {
            return null;
        }
    }

    /**
     * Returns the webhook endpoint URL.
     *
     * @return string
     */
    private function getHandleUrl()
    {
        return $this->getUrl('WalleePaymentWebhook', 'handle');
    }
}
