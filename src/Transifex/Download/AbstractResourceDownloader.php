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
use CyberSpectrum\PhpTransifex\Model\ProjectModel;
use CyberSpectrum\PhpTransifex\Model\ResourceModel;
use InvalidArgumentException;
use Psr\Log\LoggerInterface;

/**
 * This class synchronizes all resources from transifex.
 */
abstract class AbstractResourceDownloader
{
    /**
     * The download mode (one of: default, reviewed, translator).
     *
     * @var string
     */
    private $translationMode = 'default';

    /**
     * The project.
     *
     * @var ProjectModel
     */
    private $project;

    /**
     * The logger to use.
     *
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * The allowed language codes.
     *
     * @var string[]
     */
    protected $allowedLanguages;

    /**
     * The prefix to apply to the translation domain.
     *
     * @var string
     */
    protected $domainPrefix = '';

    /**
     * The base language.
     *
     * @var string
     */
    protected $baseLanguage;

    /**
     * The output directory.
     *
     * @var string
     */
    protected $outputDirectory;

    /**
     * Create a new instance.
     *
     * @param ProjectModel    $project         The project to process.
     * @param string          $outputDirectory The output directory.
     * @param string          $baseLanguage    The base language.
     * @param LoggerInterface $logger          The logger to use.
     */
    public function __construct(ProjectModel $project, $outputDirectory, $baseLanguage, LoggerInterface $logger)
    {
        $this->project         = $project;
        $this->baseLanguage    = (string) $baseLanguage;
        $this->outputDirectory = (string) $outputDirectory;
        $this->logger          = $logger;
        // Initially allow all languages.
        $this->setAllowedLanguages();
    }

    /**
     * Set the allowed language codes.
     *
     * @param string[]|null $allowedLanguages The allowed language codes.
     *
     * @return AbstractResourceDownloader
     */
    public function setAllowedLanguages(array $allowedLanguages = null)
    {
        // Base language must never be polled.
        $this->allowedLanguages = array_diff($this->project->languages()->codes(), [$this->baseLanguage]);
        if (null !== $allowedLanguages) {
            $this->allowedLanguages = array_intersect($this->allowedLanguages, $allowedLanguages);
        }

        return $this;
    }

    /**
     * Set the domain prefix.
     *
     * @param string $domainPrefix The new prefix.
     *
     * @return AbstractResourceDownloader
     */
    public function setDomainPrefix($domainPrefix)
    {
        $this->domainPrefix = (string) $domainPrefix;

        return $this;
    }

    /**
     * Set translation mode.
     *
     * @param string $translationMode The new translation mode (one of: default, reviewed, translator).
     *
     * @return AbstractResourceDownloader
     */
    public function setTranslationMode($translationMode)
    {
        $this->translationMode = (string) $translationMode;

        return $this;
    }

    /**
     * Perform the synchronization.
     *
     * @return void
     */
    public function download()
    {
        foreach ($this->project->resources() as $resource) {
            $this->processResource($resource);
        }
    }

    /**
     * Process a single resource.
     *
     * @param ResourceModel $resource The resource to process.
     *
     * @return void
     */
    private function processResource($resource)
    {
        if (substr($resource->slug(), 0, strlen($this->domainPrefix)) != $this->domainPrefix) {
            $this->logger->notice(
                'Resource {slug} does not match domain prefix {prefix}, skipping...',
                ['slug' => $resource->slug(), 'prefix' => $this->domainPrefix]
            );
            return;
        }
        $this->logger->notice('Processing resource {slug}', ['slug' => $resource->slug()]);
        $files = $this->getFiles($resource->slug());
        $sync  = new ResourceTranslationDownloader($resource, $files, $this->logger);
        $sync
            ->setTranslationMode($this->translationMode)
            ->process();
        $this->saveFiles($files);
    }

    /**
     * Fetch the xliff files for the passed resource.
     *
     * @param string $resource The resource slug.
     *
     * @return TranslationFileInterface[]
     */
    abstract protected function getFiles($resource);

    /**
     * Strip the domain prefix from the passed slug.
     *
     * @param string $resourceSlug The slug to strip the prefix from.
     *
     * @return string
     *
     * @throws InvalidArgumentException When the slug is not prefixed.
     */
    protected function stripDomainPrefix($resourceSlug)
    {
        if (substr($resourceSlug, 0, strlen($this->domainPrefix)) != $this->domainPrefix) {
            throw new InvalidArgumentException('Slug is not prefixed.');
        }

        return substr($resourceSlug, strlen($this->domainPrefix));
    }

    /**
     * Save the passed xliff files.
     *
     * @param TranslationFileInterface[] $files The files to save.
     *
     * @return void
     */
    private function saveFiles($files)
    {
        foreach ($files as $file) {
            if ($file->isChanged()) {
                $file->save();
            }
        }
    }
}
