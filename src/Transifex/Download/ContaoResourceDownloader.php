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

use CyberSpectrum\ContaoToolBox\Translation\Contao\ContaoFile;

/**
 * This class synchronizes all Contao resources from transifex.
 */
class ContaoResourceDownloader extends AbstractResourceDownloader
{
    /**
     * Fetch the contao files for the passed resource.
     *
     * @param string $resource The resource slug.
     *
     * @return ContaoFile[]
     */
    protected function getFiles($resource)
    {
        $files = [];
        foreach ($this->allowedLanguages as $language) {
            $files[] = $this->createContaoFile($resource, $language);
        }

        return $files;
    }

    /**
     * Create a contao file instance for the passed resource.
     *
     * @param string $resource     The resource slug.
     * @param string $languageCode The language code.
     *
     * @return ContaoFile
     */
    private function createContaoFile($resource, $languageCode)
    {
        $file = new ContaoFile(
            implode(
                DIRECTORY_SEPARATOR,
                [$this->outputDirectory, $languageCode, $this->stripDomainPrefix($resource) . '.php']
            ),
            $this->logger
        );
        $file->setLanguage($languageCode);

        return $file;
    }
}
