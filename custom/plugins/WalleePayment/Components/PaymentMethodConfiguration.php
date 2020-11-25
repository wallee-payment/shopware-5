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

namespace WalleePayment\Components;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Shopware\Components\Model\ModelManager;
use WalleePayment\Models\PaymentMethodConfiguration as PaymentMethodConfigurationModel;
use WalleePayment\Components\Provider\PaymentMethod as PaymentMethodProvider;
use Shopware\Components\Plugin\ConfigReader;
use Shopware\Components\Plugin\PaymentInstaller;
use Shopware\Models\Shop\Shop;

class PaymentMethodConfiguration
{

    /**
     *
     * @var ContainerInterface
     */
    private $container;

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
     * @var PaymentInstaller
     */
    private $paymentInstaller;

    /**
     *
     * @var \Wallee\Sdk\ApiClient
     */
    private $apiClient;

    /**
     *
     * @var PaymentMethodProvider
     */
    private $paymentMethodProvider;

    /**
     *
     * @var Translator
     */
    private $translator;

    /**
     *
     * @var Resource
     */
    private $resource;

    /**
     * Constructor.
     *
     * @param ContainerInterface $container
     * @param ConfigReader $configReader
     * @param ModelManager $modelManager
     * @param PaymentInstaller $paymentInstaller
     * @param ApiClient $apiClient
     * @param PaymentMethodProvider $paymentMethodProvider
     * @param Translator $translator
     * @param Resource $resource
     */
    public function __construct(ContainerInterface $container, ConfigReader $configReader, ModelManager $modelManager, PaymentInstaller $paymentInstaller, ApiClient $apiClient, PaymentMethodProvider $paymentMethodProvider, Translator $translator, Resource $resource)
    {
        $this->container = $container;
        $this->configReader = $configReader;
        $this->modelManager = $modelManager;
        $this->paymentInstaller = $paymentInstaller;
        $this->apiClient = $apiClient->getInstance();
        $this->paymentMethodProvider = $paymentMethodProvider;
        $this->translator = $translator;
        $this->resource = $resource;
    }

    /**
     * Updates the data of the payment method configuration.
     *
     * @param \Wallee\Sdk\Model\PaymentMethodConfiguration $configuration
     */
    public function updateData(\Wallee\Sdk\Model\PaymentMethodConfiguration $configuration)
    {
        $model = $this->modelManager->getRepository(PaymentMethodConfigurationModel::class)->findOneBy(array(
            'spaceId' => $configuration->getLinkedSpaceId(),
            'configurationId' => $configuration->getId()
        ));
        if ($model instanceof PaymentMethodConfigurationModel) {
            $model->setConfigurationName($configuration->getName());
            $model->setTitle($configuration->getResolvedTitle());
            $model->setDescription($configuration->getResolvedDescription());
            $model->setImage($this->getImagePath($configuration->getResolvedImageUrl()));
            $model->setSortOrder($configuration->getSortOrder());
            $this->modelManager->persist($model);
            $this->modelManager->flush();
        }
    }

    /**
     * Synchronizes the payment method configurations from wallee.
     */
    public function synchronize()
    {
        $existingFound = array();
        $existingConfigurations = $this->modelManager->getRepository(PaymentMethodConfigurationModel::class)->findAll();
        foreach ($existingConfigurations as $existingConfiguration) {
            /* @var PaymentMethodConfigurationModel $existingConfiguration */
            $existingConfiguration->setState(PaymentMethodConfigurationModel::STATE_HIDDEN);
        }

        $spaceIds = array();
        foreach ($this->modelManager->getRepository(Shop::class)->findAll() as $shop) {
            /* @var Shop $shop */
            $pluginConfig = $this->configReader->getByPluginName('WalleePayment', $shop);
            $spaceId = $pluginConfig['spaceId'];
            if ($spaceId && ! in_array($spaceId, $spaceIds)) {
                $paymentMethodConfigurationService = new \Wallee\Sdk\Service\PaymentMethodConfigurationService($this->apiClient);
                $configurations = $paymentMethodConfigurationService->search($spaceId, new \Wallee\Sdk\Model\EntityQuery());
                foreach ($configurations as $configuration) {
                    /* @var PaymentMethodConfigurationModel $method */
                    $method = null;
                    foreach ($existingConfigurations as $existingConfiguration) {
                        /* @var PaymentMethodConfigurationModel $existingConfiguration */
                        if ($existingConfiguration->getSpaceId() == $spaceId && $existingConfiguration->getConfigurationId() == $configuration->getId()) {
                            $method = $existingConfiguration;
                            $existingFound[] = $method->getId();
                            break;
                        }
                    }

                    if ($method == null) {
                        $method = new PaymentMethodConfigurationModel();
                    }

                    $method->setSpaceId($spaceId);
                    $method->setConfigurationId($configuration->getId());
                    $method->setConfigurationName($configuration->getName());
                    $method->setState($this->getConfigurationState($configuration));
                    $method->setTitle($configuration->getResolvedTitle());
                    $method->setDescription($configuration->getResolvedDescription());
                    $method->setImage($this->getImagePath($configuration->getResolvedImageUrl()));
                    $method->setSortOrder($configuration->getSortOrder());
                    $this->modelManager->persist($method);
                }
                $spaceIds[] = $spaceId;
            }
        }

        foreach ($existingConfigurations as $existingConfiguration) {
            if (! in_array($existingConfiguration->getId(), $existingFound)) {
                $existingConfiguration->setState(PaymentMethodConfigurationModel::STATE_HIDDEN);
                $this->modelManager->persist($existingConfiguration);
            }
        }

        $this->modelManager->flush();

        $this->updatePaymentMethods();
        $this->modelManager->flush();
    }

    /**
     * Creates or updates the payment methods in the shop.
     */
    private function updatePaymentMethods()
    {
        $defaultLanguage = $this->modelManager->getRepository(Shop::class)
            ->getActiveDefault()
            ->getLocale()
            ->getLocale();
        $pluginName = $this->container->getParameter('wallee_payment.plugin_name');

        $configurations = $this->modelManager->getRepository(PaymentMethodConfigurationModel::class)->findAll();
        foreach ($configurations as $configuration) {
            /* @var PaymentMethodConfigurationModel $configuration */
            $description = $this->translator->translate($configuration->getTitle(), $defaultLanguage);
            if ($description == null) {
                $description = $configuration->getConfigurationName();
            }
            $payment = $this->paymentInstaller->createOrUpdate($pluginName, [
                'name' => 'wallee_' . $configuration->getId(),
                'description' => $description,
                'active' => $configuration->getState() == PaymentMethodConfigurationModel::STATE_ACTIVE,
                'position' => $configuration->getSortOrder(),
                'additionalDescription' => '<img width="50" src="' . $this->resource->getResourceUrl($configuration->getImage(), $defaultLanguage, $configuration->getSpaceId()) . '" /><div>' . $this->translator->translate($configuration->getDescription(), $defaultLanguage) . '</div>',
                'action' => 'WalleePaymentPay'
            ]);
            $configuration->setPayment($payment);
            $this->modelManager->persist($configuration);
        }
    }

    /**
     * @param string $resolvedImageUrl
     * @return string
     */
    private function getImagePath($resolvedImageUrl)
    {
        $index = strpos($resolvedImageUrl, 'resource/');
        return substr($resolvedImageUrl, $index + strlen('resource/'));
    }

    /**
     * Returns the state for the payment method configuration.
     *
     * @param \Wallee\Sdk\Model\PaymentMethodConfiguration $configuration
     * @return string
     */
    private function getConfigurationState(\Wallee\Sdk\Model\PaymentMethodConfiguration $configuration)
    {
        switch ($configuration->getState()) {
            case \Wallee\Sdk\Model\CreationEntityState::ACTIVE:
                return PaymentMethodConfigurationModel::STATE_ACTIVE;
            case \Wallee\Sdk\Model\CreationEntityState::INACTIVE:
                return PaymentMethodConfigurationModel::STATE_INACTIVE;
            default:
                return PaymentMethodConfigurationModel::STATE_HIDDEN;
        }
    }
}
