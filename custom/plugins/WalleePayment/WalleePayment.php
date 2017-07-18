<?php
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

class WalleePayment extends Plugin
{
    public static function getSubscribedEvents()
    {
        return [
            'Enlight_Controller_Front_StartDispatch' => 'onStartDispatch'
        ];
    }

    public function install(InstallContext $context)
    {
        parent::install($context);
        $this->installSchema();
        $this->installWidgets($context);
    }

    public function update(UpdateContext $context)
    {
        parent::update($context);
    }

    public function uninstall(UninstallContext $context)
    {
        parent::uninstall($context);
//         $this->uninstallSchema();
        $this->uninstallWidgets($context);
    }

    public function build(ContainerBuilder $container)
    {
        $container->setParameter($this->getContainerPrefix() . '.base_gateway_url', 'https://app-wallee.com:443');

        parent::build($container);
    }

    public function onStartDispatch()
    {
        if (file_exists($this->getPath() . '/vendor/autoload.php')) {
            require_once $this->getPath() . '/vendor/autoload.php';
        }
    }

    private function installSchema()
    {
        $tool = new SchemaTool($this->container->get('models'));
        $classes = [
            $this->container->get('models')->getClassMetadata(PaymentMethodConfiguration::class),
            $this->container->get('models')->getClassMetadata(TransactionInfo::class),
            $this->container->get('models')->getClassMetadata(OrderTransactionMapping::class)
        ];
        $tool->updateSchema($classes, true);
    }

    private function uninstallSchema()
    {
        $tool = new SchemaTool($this->container->get('models'));
        $classes = [
            $this->container->get('models')->getClassMetadata(PaymentMethodConfiguration::class),
            $this->container->get('models')->getClassMetadata(TransactionInfo::class),
            $this->container->get('models')->getClassMetadata(OrderTransactionMapping::class)
        ];
        $tool->dropSchema($classes);
    }

    private function installWidgets(InstallContext $context)
    {
        $plugin = $context->getPlugin();
        $widget = new Widget();
        $widget->setName('wallee-payment-manual-tasks');
        $widget->setPlugin($plugin);
        $widget->setLabel('Wallee Payment - Manual Tasks');
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
