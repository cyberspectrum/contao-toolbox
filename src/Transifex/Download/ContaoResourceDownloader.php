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

use CyberSpectrum\ContaoToolBox\Translation\Base\TranslationFileInterface;
use CyberSpectrum\ContaoToolBox\Translation\Contao\ContaoFile;
use CyberSpectrum\PhpTransifex\Model\ProjectModel;
use CyberSpectrum\PhpTransifex\Model\ResourceModel;
use Psr\Log\LoggerInterface;

/**
 * This class synchronizes all Contao resources from transifex.
 */
class ContaoResourceDownloader extends AbstractResourceDownloader
{
    /**
     * The file header.
     *
     * @var string[]
     */
    private $fileHeader;

    /**
     * Create a new instance.
     *
     * @param ProjectModel    $project         The project to process.
     * @param string          $outputDirectory The output directory.
     * @param string          $baseLanguage    The base language.
     * @param string[]        $fileHeader      The file header to use.
     * @param LoggerInterface $logger          The logger to use.
     */
    public function __construct(
        ProjectModel $project,
        $outputDirectory,
        $baseLanguage,
        array $fileHeader,
        LoggerInterface $logger
    ) {
        parent::__construct($project, $outputDirectory, $baseLanguage, $logger);
        $this->fileHeader = $fileHeader;
    }

    /**
     * Fetch the contao files for the passed resource.
     *
     * @param ResourceModel $resource The resource slug.
     *
     * @return ContaoFile[]
     */
    protected function getFiles(ResourceModel $resource)
    {
        $files = [];
        foreach ($this->allowedLanguages as $language) {
            $files[] = $this->createContaoFile($resource->slug(), $language);
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

    /**
     * Set the file header and date.
     *
     * @param TranslationFileInterface $file The file.
     *
     * @return void
     */
    protected function postProcess(TranslationFileInterface $file): void
    {
        parent::postProcess($file);
        /** @var ContaoFile $file */
        $file->setFileHeader($this->fileHeader);
        $file->setLastChange(time());
    }
}
