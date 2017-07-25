<?php

/**
 * Wallee Shopware
 *
 * This Shopware extension enables to process payments with Wallee (https://wallee.com/).
 *
 * @package Wallee_Payment
 * @author customweb GmbH (http://www.customweb.com/)
 * @license http://www.apache.org/licenses/LICENSE-2.0  Apache Software License (ASL 2.0)
 * @link https://github.com/wallee-payment/shopware
 */

namespace WalleePayment\Components\Controller;

abstract class Backend extends \Shopware_Controllers_Backend_ExtJs
{
    public function preDispatch()
    {
        $this->get('template')->addTemplateDir($this->container->getParameter('wallee_payment.plugin_dir') . '/Resources/views/');
        $this->get('snippets')->addConfigDir($this->container->getParameter('wallee_payment.plugin_dir') . '/Resources/snippets/');

        parent::preDispatch();
    }

    /**
     * Sends the data received by calling the given path to the browser.
     *
     * @param string $path
     */
    protected function download(\Wallee\Sdk\Model\RenderedDocument $document)
    {
        $this->Response()
            ->setHttpResponseCode(200)
            ->setHeader('Pragma', 'public', true)
            ->setHeader('Cache-Control', 'must-revalidate, post-check=0, pre-check=0', true)
            ->setHeader('Content-type', 'application/pdf', true)
            ->setHeader('Content-Disposition', 'attachment; filename=' . $document->getTitle() . '.pdf')
            ->setHeader('Content-Description', $document->getTitle());
        $this->Response()->setBody(base64_decode($document->getData()));

        $this->Response()->sendHeaders();
        session_write_close();
        $this->Response()->outputBody();
    }

    /**
     *
     * @param array[string,string]|\Wallee\Sdk\Model\DatabaseTranslatedString $string
     * @return string
     */
    protected function translate($string)
    {
        return $this->get('wallee_payment.translator')->translate($string);
    }
}
