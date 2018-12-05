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

/**
 * This class is the uploader for Contao resources.
 */
class ContaoResourceUploader extends AbstractResourceUploader
{
    /**
     * {@inheritDoc}
     */
    protected function getResourceFiles()
    {
        $iterator = new \DirectoryIterator($this->outputDirectory . DIRECTORY_SEPARATOR . $this->baseLanguage);
        $files    = [];
        while ($iterator->valid()) {
            if ($this->isValidFile($iterator)) {
                $slug         = substr($iterator->getFilename(), 0, -4);
                $files[$slug] = $this->createXliffFromContao($slug, $iterator->getPathname());
            }
            $iterator->next();
        }

        return $files;
    }

    /**
     * Create an Xliff file from the passed Contao file.
     *
     * @param string $resourceSlug The resource slug.
     * @param string $filename     The file name.
     *
     * @return string
     */
    private function createXliffFromContao($resourceSlug, $filename)
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
     * @param \DirectoryIterator $iterator The iterator.
     *
     * @return bool
     */
    private function isValidFile($iterator)
    {
        return !$iterator->isDot()
            && $iterator->isFile()
            && 'php' === $iterator->getExtension();
    }
}
