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
use CyberSpectrum\PhpTransifex\Model\Project;
use CyberSpectrum\PhpTransifex\Model\Resource;
use DateTimeImmutable;
use Psr\Log\LoggerInterface;

use function assert;
use function implode;

/**
 * This class synchronizes all Contao resources from transifex.
 */
final class ContaoResourceDownloader extends AbstractResourceDownloader
{
    /**
     * Create a new instance.
     *
     * @param Project         $project         The project to process.
     * @param string          $outputDirectory The output directory.
     * @param string          $baseLanguage    The base language.
     * @param list<string>    $fileHeader      The file header to use.
     * @param LoggerInterface $logger          The logger to use.
     */
    public function __construct(
        Project $project,
        string $outputDirectory,
        string $baseLanguage,
        /** @var list<string> */
        private readonly array $fileHeader,
        LoggerInterface $logger
    ) {
        parent::__construct($project, $outputDirectory, $baseLanguage, $logger);
    }

    /**
     * Fetch the contao files for the passed resource.
     *
     * @param Resource $resource The resource slug.
     *
     * @return list<ContaoFile>
     */
    protected function getFiles(Resource $resource): array
    {
        $files = [];
        foreach ($this->allowedLanguages as $language) {
            $files[] = $this->createContaoFile($resource->getSlug(), $language);
        }

        return $files;
    }

    /**
     * Create a contao file instance for the passed resource.
     *
     * @param string $resource     The resource slug.
     * @param string $languageCode The language code.
     */
    private function createContaoFile(string $resource, string $languageCode): ContaoFile
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
     */
    protected function postProcess(TranslationFileInterface $file): void
    {
        parent::postProcess($file);
        assert($file instanceof ContaoFile);
        $file->setFileHeader($this->fileHeader);

        if ($file->isChanged()) {
            $file->setLastChange(new DateTimeImmutable());
        }
    }
}
