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

namespace WalleePayment\Components;

use Symfony\Component\DependencyInjection\ContainerInterface;
use WalleePayment\Components\Session as SessionService;
use Doctrine\DBAL\Connection;

class Basket
{

    /**
     *
     * @var ContainerInterface
     */
    private $container;

    /**
     *
     * @var Connection
     */
    private $databaseConnection;

    /**
     *
     * @var SessionService
     */
    private $sessionService;

    /**
     * Constructor.
     *
     * @param ContainerInterface $container
     * @param Connection $databaseConnection
     * @param SessionService $sessionService
     */
    public function __construct(ContainerInterface $container, Connection $databaseConnection, SessionService $sessionService)
    {
        $this->container = $container;
        $this->databaseConnection = $databaseConnection;
        $this->sessionService = $sessionService;
    }

    /**
     * Deletes the current session's basket from the database.
     */
    public function deleteBasket()
    {
        $this->databaseConnection->executeQuery('DELETE FROM s_order_basket WHERE sessionID = ?', [$this->sessionService->getSessionId()]);
    }

    /**
     * Returns the current session's basket data as an array.
     *
     * @param array $backup
     */
    public function backupBasket()
    {
        $basket = $this->databaseConnection->executeQuery('SELECT * FROM s_order_basket WHERE sessionID = ?', [$this->sessionService->getSessionId()]);
        $basketBackup = array();
        $basketAttributesBackup = array();
        while ($item = $basket->fetch()) {
            $basketBackup[] = $item;

            $attributes = $this->databaseConnection->executeQuery('SELECT * FROM s_order_basket_attributes WHERE basketID = ?', [$item['id']]);
            while ($attribute = $attributes->fetch()) {
                $basketAttributesBackup[] = $attribute;
            }
        }
        return [
            'basket' => $basketBackup,
            'basketAttributes' => $basketAttributesBackup
        ];
    }

    /**
     * Restores the given basket backup.
     *
     * @param array $backup
     */
    public function restoreBasket(array $backup)
    {
        if (!isset($backup['basket']) || !isset($backup['basketAttributes'])) {
            return;
        }
        $basketIdMap = array();
        foreach ($backup['basket']as $item) {
            $values = array();
            foreach ($item as $key => $value) {
                if ($key == 'id') {
                    continue;
                }
                $values[$key] = $value;
            }
            $this->databaseConnection->insert('s_order_basket', $values);
            $basketId = $this->databaseConnection->lastInsertId();
            $basketIdMap[$item['id']] = $basketId;
        }

        foreach ($backup['basketAttributes']as $item) {
            $values = array();
            foreach ($item as $key => $value) {
                if ($key == 'id') {
                    continue;
                }
                if ($key == 'basketID') {
                    $values[$key] = $basketIdMap[$value];
                } elseif ($key == 'tonur_gift_option_parent_basket_id' && !empty($value)) {
                    $values[$key] = $basketIdMap[$value];
                } else {
                    $values[$key] = $value;
                }
            }
            $this->databaseConnection->insert('s_order_basket_attributes', $values);
        }
    }
}
