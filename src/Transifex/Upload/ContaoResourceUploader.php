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

namespace CyberSpectrum\ContaoToolBox\Transifex\Upload;

use CyberSpectrum\ContaoToolBox\Translation\Contao\ContaoFile;
use CyberSpectrum\ContaoToolBox\Translation\TranslationSync;
use CyberSpectrum\ContaoToolBox\Translation\Xliff\XliffFile;
use DirectoryIterator;

/**
 * This class is the uploader for Contao resources.
 */
final class ContaoResourceUploader extends AbstractResourceUploader
{
    protected function getResourceFiles(): array
    {
        $iterator = new DirectoryIterator($this->outputDirectory . DIRECTORY_SEPARATOR . $this->baseLanguage);
        $files    = [];
        while ($iterator->valid()) {
            if ($this->isValidFile($iterator)) {
                $slug         = $iterator->getBasename('.' . $iterator->getExtension());
                $files[$slug] = $this->createXliffFromContao($slug, $iterator->getPathname());
            }
            $iterator->next();
        }

        return $files;
    }

    /**
     * Create a Xliff file from the passed Contao file.
     *
     * @param string $resourceSlug The resource slug.
     * @param string $filename     The file name.
     */
    private function createXliffFromContao(string $resourceSlug, string $filename): string
    {
        $contao = new ContaoFile($filename);
        $xliff  = new XliffFile();

        // Set base values.
        $xliff->setDataType('php');
        $xliff->setOriginal($resourceSlug);
        $xliff->setSrcLang($this->baseLanguage);
        $xliff->setTgtLang($this->baseLanguage);
        // Copy all keys over.
        TranslationSync::syncFrom($contao, $xliff->setMode('source'), true, $this->logger);

        return $xliff->saveXML();
    }

    /**
     * Test if the current file of the iterator is valid.
     *
     * @param DirectoryIterator $iterator The iterator.
     */
    private function isValidFile(DirectoryIterator $iterator): bool
    {
        return !$iterator->isDot()
            && $iterator->isFile()
            && 'php' === $iterator->getExtension();
    }
}
