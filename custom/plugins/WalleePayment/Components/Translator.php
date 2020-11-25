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

use Shopware\Models\Shop\Shop;
use Shopware\Components\Model\ModelManager;

class Translator
{

    /**
     *
     * @var ModelManager
     */
    private $modelManager;

    /**
     *
     * @var \WalleePayment\Components\Provider\Language
     */
    private $languageProvider;

    /**
     * Constructor.
     *
     * @param ModelManager $modelManager
     * @param \WalleePayment\Components\Provider\Language $languageProvider
     */
    public function __construct(ModelManager $modelManager, \WalleePayment\Components\Provider\Language $languageProvider)
    {
        $this->modelManager = $modelManager;
        $this->languageProvider = $languageProvider;
    }

    /**
     * Returns the translation in the given language.
     *
     * @param array[string,string]|\Wallee\Sdk\Model\DatabaseTranslatedString $translatedString
     * @param string $language
     * @return string
     */
    public function translate($translatedString, $language = null)
    {
        if ($language == null) {
            $language = $this->modelManager->getRepository(Shop::class)
                ->getActiveDefault()
                ->getLocale()
                ->getLocale();
        }

        if ($translatedString instanceof \Wallee\Sdk\Model\DatabaseTranslatedString) {
            $translations = array();
            foreach ($translatedString->getItems() as $item) {
                $translation = $item->getTranslation();
                if (! empty($translation)) {
                    $translations[$item->getLanguage()] = $item->getTranslation();
                }
            }
            $translatedString = $translations;
        }

        $language = str_replace('_', '-', $language);
        if (isset($translatedString[$language])) {
            return $translatedString[$language];
        }

        $primaryLanguage = $this->languageProvider->findPrimary($language);
        if ($primaryLanguage !== false && isset($translatedString[$primaryLanguage->getIetfCode()])) {
            return $translatedString[$primaryLanguage->getIetfCode()];
        }

        if (isset($translatedString['en-US'])) {
            return $translatedString['en-US'];
        }

        return null;
    }
}
