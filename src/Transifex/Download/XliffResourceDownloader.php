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
use CyberSpectrum\PhpTransifex\Model\Resource;
use LogicException;
use RuntimeException;

use function file_exists;
use function implode;

/**
 * This class synchronizes all XLIFF resources from transifex.
 */
final class XliffResourceDownloader extends AbstractResourceDownloader
{
    /**
     * Fetch the xliff files for the passed resource.
     *
     * @param Resource $resource The resource slug.
     *
     * @return list<XliffFile>
     *
     * @throws RuntimeException When the base language file is missing.
     */
    protected function getFiles(Resource $resource): array
    {
        $domain    = $this->stripDomainPrefix($slug = $resource->getSlug());
        $localFile = implode(DIRECTORY_SEPARATOR, [$this->outputDirectory, $this->baseLanguage, $domain . '.xlf']);
        $baseFile  = new XliffFile($localFile, $this->logger);
        if (!file_exists($localFile)) {
            $baseFile->setDataType('php');
            $baseFile->setOriginal($domain);
            $baseFile->setSrcLang($this->baseLanguage);
            $baseFile->setTgtLang($this->baseLanguage);
        }

        $upstream = new XliffFile(null, $this->logger);
        $upstream->loadXML($resource->content());
        $sync = new TranslationSync($upstream->setMode('source'), $baseFile->setMode('source'), $this->logger);
        $sync->cleanUp();
        $sync->sync();
        $upstream->setMode('target');
        $baseFile->setMode('target');
        $sync->sync();
        $baseFile->setDate($upstream->getDate());

        if ($baseFile->isChanged()) {
            $this->logger->notice('Updated base language from transifex.');
            $baseFile->save();
        }

        $files = [];
        foreach ($this->allowedLanguages as $language) {
            $files[] = $this->createXliffFile($slug, $language, $baseFile);
        }

        return $files;
    }

    /**
     * Create a xliff instance for the passed resource.
     *
     * @param string    $resource     The resource slug.
     * @param string    $languageCode The language code.
     * @param XliffFile $baseFile     The base language file.
     */
    private function createXliffFile(string $resource, string $languageCode, XliffFile $baseFile): XliffFile
    {
        $domain = $this->stripDomainPrefix($resource);
        $local  = new XliffFile(
            implode(DIRECTORY_SEPARATOR, [$this->outputDirectory, $languageCode, $domain . '.xlf']),
            $this->logger
        );
        $fileName = $local->getFileName();
        if (null === $fileName) {
            throw new LogicException('File name not specified');
        }
        if (!file_exists($fileName)) {
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
