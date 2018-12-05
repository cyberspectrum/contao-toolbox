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

/**
 * This class is the uploader for Xliff resources.
 */
class XliffResourceUploader extends AbstractResourceUploader
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
                $files[substr($iterator->getFilename(), 0, -4)] = file_get_contents($iterator->getPathname());
            }
            $iterator->next();
        }

        return $files;
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
            && 'xlf' === $iterator->getExtension();
    }
}
