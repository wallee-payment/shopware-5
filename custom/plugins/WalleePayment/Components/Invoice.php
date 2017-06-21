<?php
namespace WalleePayment\Components;

use Shopware\Components\Model\ModelManager;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Wallee\Sdk\Model\EntityQuery;
use Wallee\Sdk\Service\TransactionInvoiceService;

class Invoice extends AbstractService
{

    /**
     *
     * @var ModelManager
     */
    private $modelManager;

    /**
     *
     * @var TransactionInvoiceService
     */
    private $invoiceService;

    /**
     * Constructor.
     *
     * @param ContainerInterface $container
     * @param ModelManager $modelManager
     * @param ApiClient $apiClient
     */
    public function __construct(ContainerInterface $container, ModelManager $modelManager, ApiClient $apiClient)
    {
        parent::__construct($container);
        $this->modelManager = $modelManager;
        $this->invoiceService = new TransactionInvoiceService($apiClient->getInstance());
    }

    /**
     *
     * @param int $spaceId
     * @param int $transactionId
     * @return \Wallee\Sdk\Model\TransactionInvoice
     */
    public function getInvoice($spaceId, $transactionId)
    {
        $query = new EntityQuery();
        $query->setFilter($this->createEntityFilter('completion.lineItemVersion.transaction.id', $transactionId));
        $query->setNumberOfEntities(1);
        return current($this->invoiceService->search($spaceId, $query));
    }
}
