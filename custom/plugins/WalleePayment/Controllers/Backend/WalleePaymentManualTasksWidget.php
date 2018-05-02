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

use WalleePayment\Components\Controller\Backend;

class Shopware_Controllers_Backend_WalleePaymentManualTasksWidget extends Backend
{
    public function infoAction()
    {
        $numberOfManualTasks = $this->get('wallee_payment.manual_task')->getNumberOfManualTasks();
        $totalNumber = array_sum($numberOfManualTasks);
        $this->View()->assign(array(
            'success' => true,
            'data' => array(
                'success' => true,
                'number' => $totalNumber,
                'detailUrl' => $this->getManualTasksUrl(count($numberOfManualTasks) == 1 ? key($numberOfManualTasks) : null)
            )
        ));
    }

    /**
     * Returns the URL to check the open manual tasks.
     *
     * @return string
     */
    private function getManualTasksUrl($spaceId)
    {
        $manualTaskUrl = $this->container->getParameter('wallee_payment.base_gateway_url');
        if ($spaceId != null) {
            $manualTaskUrl .= '/s/' . $spaceId . '/manual-task/list';
        }

        return $manualTaskUrl;
    }
}
