<?php

/**
 * This file is part of cyberspectrum/contao-toolbox.
 *
 * (c) 2013-2017 CyberSpectrum.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * This project is provided in good faith and hope to be usable by anyone.
 *
 * @package    cyberspectrum/contao-toolbox.
 * @author     Christian Schiffler <c.schiffler@cyberspectrum.de>
 * @copyright  2013-2017 CyberSpectrum.
 * @license    https://github.com/cyberspectrum/contao-toolbox/blob/master/LICENSE LGPL-3.0
 * @filesource
 */

namespace CyberSpectrum\ContaoToolBox\Transifex\Download;

use CyberSpectrum\ContaoToolBox\Translation\TranslationSync;
use CyberSpectrum\ContaoToolBox\Translation\Xliff\XliffFile;
use RuntimeException;

/**
 * This class synchronizes all XLIFF resources from transifex.
 */
class XliffResourceDownloader extends AbstractResourceDownloader
{
    /**
     * Fetch the xliff files for the passed resource.
     *
     * @param string $resource The resource slug.
     *
     * @return XliffFile[]
     *
     * @throws RuntimeException When the base language file is missing.
     */
    protected function getFiles($resource)
    {
        $domain    = $this->stripDomainPrefix($resource);
        $localFile = implode(DIRECTORY_SEPARATOR, [$this->outputDirectory, $this->baseLanguage, $domain . '.xlf']);
        if (!file_exists($localFile)) {
            throw new RuntimeException('Base language file ' . $localFile . ' is missing, can not proceed.');
        }
        $baseFile = new XliffFile($localFile, $this->logger);

        $files = [];
        foreach ($this->allowedLanguages as $language) {
            $files[] = $this->createXliffFile($resource, $language, $baseFile);
        }

        return $files;
    }

    /**
     * Create a xliff instance for the passed resource.
     *
     * @param string    $resource     The resource slug.
     * @param string    $languageCode The language code.
     * @param XliffFile $baseFile     The base language file.
     *
     * @return XliffFile
     */
    private function createXliffFile($resource, $languageCode, XliffFile $baseFile)
    {
        $domain = $this->stripDomainPrefix($resource);
        $local  = new XliffFile(
            implode(DIRECTORY_SEPARATOR, [$this->outputDirectory, $languageCode, $domain . '.xlf']),
            $this->logger
        );

        if (!file_exists($local->getFileName())) {
            // Set base values.
            $local->setDataType('php');
            $local->setOriginal($domain);
            $local->setSrcLang($this->baseLanguage);
            $local->setTgtLang($languageCode);
        }
        // Update all source values.
        TranslationSync::syncFrom($baseFile->setMode('source'), $local->setMode('source'), true, $this->logger);

        return $local->setMode('target');
    }
}
