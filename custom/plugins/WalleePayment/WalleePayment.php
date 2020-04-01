<?php

/**
 * wallee Shopware 5
 *
 * This Shopware 5 extension enables to process payments with wallee (https://www.wallee.com/).
 *
 * @package Wallee_Payment
 * @author customweb GmbH (http://www.customweb.com/)
 * @license http://www.apache.org/licenses/LICENSE-2.0  Apache Software License (ASL 2.0)
 */

namespace WalleePayment;

use Doctrine\ORM\Tools\SchemaTool;
use Shopware\Components\Plugin;
use Shopware\Components\Plugin\Context\InstallContext;
use Shopware\Components\Plugin\Context\UninstallContext;
use Shopware\Components\Plugin\Context\UpdateContext;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use WalleePayment\Models\OrderTransactionMapping;
use WalleePayment\Models\PaymentMethodConfiguration;
use WalleePayment\Models\TransactionInfo;
use Shopware\Models\Widget\Widget;
use Shopware\Components\Plugin\Context\ActivateContext;

if (\file_exists(dirname(__FILE__) . '/vendor/autoload.php')) {
    require_once dirname(__FILE__) . '/vendor/autoload.php';
}

class WalleePayment extends Plugin
{

    public function install(InstallContext $context)
    {
        parent::install($context);
        $this->updateSchema();
        $this->installWidgets($context);
    }

    public function update(UpdateContext $context)
    {
        parent::update($context);
        $this->updateSchema();
    }

    public function uninstall(UninstallContext $context)
    {
        parent::uninstall($context);
//         $this->uninstallSchema();
        $this->uninstallWidgets($context);
    }
    
    public function activate(ActivateContext $context)
    {
        $context->scheduleClearCache(InstallContext::CACHE_LIST_ALL);
    }

    public function build(ContainerBuilder $container)
    {
        $container->setParameter('wallee_payment.base_gateway_url', 'https://app-wallee.com/');

        parent::build($container);
    }

    private function getModelClasses()
    {
        return [
            $this->container->get('models')->getClassMetadata(PaymentMethodConfiguration::class),
            $this->container->get('models')->getClassMetadata(TransactionInfo::class),
            $this->container->get('models')->getClassMetadata(OrderTransactionMapping::class)
        ];
    }

    private function updateSchema()
    {
        $tool = new SchemaTool($this->container->get('models'));
        $tool->updateSchema($this->getModelClasses(), true);
    }

    private function uninstallSchema()
    {
        $tool = new SchemaTool($this->container->get('models'));
        $tool->dropSchema($this->getModelClasses());
    }

    private function installWidgets(InstallContext $context)
    {
        $plugin = $context->getPlugin();
        $widget = new Widget();
        $widget->setName('wallee-payment-manual-tasks');
        $widget->setPlugin($plugin);
        $plugin->getWidgets()->add($widget);
    }

    private function uninstallWidgets(UninstallContext $context)
    {
        $plugin = $context->getPlugin();
        $widget = $plugin->getWidgets()->first();
        $this->container->get('models')->remove($widget);
        $this->container->get('models')->flush();
    }
}
