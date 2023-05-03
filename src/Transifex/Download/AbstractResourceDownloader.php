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

use Closure;
use CyberSpectrum\ContaoToolBox\Translation\Base\TranslationFileInterface;
use CyberSpectrum\PhpTransifex\Model\Project;
use CyberSpectrum\PhpTransifex\Model\Resource;
use InvalidArgumentException;
use Psr\Log\LoggerInterface;

/**
 * This class synchronizes all resources from transifex.
 */
abstract class AbstractResourceDownloader
{
    /** The logger to use. */
    protected readonly LoggerInterface $logger;

    /**
     * The allowed language codes.
     *
     * @var list<string>
     */
    protected array $allowedLanguages = [];

    /** The prefix to apply to the translation domain. */
    protected string $domainPrefix = '';

    /** The base language. */
    protected readonly string $baseLanguage;

    /** The output directory. */
    protected readonly string $outputDirectory;

    /**
     * An optional closure to filter resources.
     *
     * @var null|Closure(string): bool
     */
    private ?Closure $resourceFilter = null;

    /**
     * Create a new instance.
     *
     * @param Project         $project         The project to process.
     * @param string          $outputDirectory The output directory.
     * @param string          $baseLanguage    The base language.
     * @param LoggerInterface $logger          The logger to use.
     */
    public function __construct(
        private readonly Project $project,
        string $outputDirectory,
        string $baseLanguage,
        LoggerInterface $logger
    ) {
        $this->baseLanguage    = $baseLanguage;
        $this->outputDirectory = $outputDirectory;
        $this->logger          = $logger;
        // Initially allow all languages.
        $this->setAllowedLanguages();
    }

    /**
     * Set the allowed language codes.
     *
     * @param string[]|null $allowedLanguages The allowed language codes.
     */
    public function setAllowedLanguages(array $allowedLanguages = null): self
    {
        $this->allowedLanguages = $this->project->languages()->codes();
        if (null !== $allowedLanguages) {
            $this->allowedLanguages = array_values(array_intersect($this->allowedLanguages, $allowedLanguages));
        }
        // Base language must never be polled.
        $this->allowedLanguages = array_values(array_diff($this->allowedLanguages, [$this->baseLanguage]));

        return $this;
    }

    /**
     * Set the domain prefix.
     *
     * @param string $domainPrefix The new prefix.
     */
    public function setDomainPrefix(string $domainPrefix): self
    {
        $this->domainPrefix = $domainPrefix;

        return $this;
    }

    /**
     * Set or clear the resource filter.
     *
     * The passed closure should have the following format:
     * function bool ($resourceSlug) {
     *   return true; // When the resource should be processed.
     *   return true; // When the resource should be skipped.
     * }
     *
     * @param null|Closure(string): bool $resourceFilter The new value.
     */
    public function setResourceFilter(?Closure $resourceFilter): self
    {
        $this->resourceFilter = $resourceFilter;

        return $this;
    }

    /** Perform the synchronization. */
    public function download(): void
    {
        foreach ($this->project->resources()->getIterator() as $resource) {
            if (
                (null !== $this->resourceFilter)
                && !$this->resourceFilter->__invoke($this->stripDomainPrefix($resource->getSlug()))
            ) {
                continue;
            }
            $this->processResource($resource);
        }
    }

    /**
     * Process a single resource.
     *
     * @param Resource $resource The resource to process.
     */
    private function processResource(Resource $resource): void
    {
        if (!str_starts_with($slug = $resource->getSlug(), $this->domainPrefix)) {
            $this->logger->info(
                'Resource {slug} does not match domain prefix {prefix}, skipping...',
                ['slug' => $slug, 'prefix' => $this->domainPrefix]
            );
            return;
        }
        $this->logger->notice('Processing resource {slug}', ['slug' => $slug]);
        $files = $this->getFiles($resource);
        $sync  = new ResourceTranslationDownloader($resource, $files, $this->logger);
        $sync->process();
        $this->saveFiles($files);
    }

    /**
     * Fetch the translation files for the passed resource.
     *
     * @param Resource $resource The resource slug.
     *
     * @return list<TranslationFileInterface>
     */
    abstract protected function getFiles(Resource $resource): array;

    /**
     * Strip the domain prefix from the passed slug.
     *
     * @param string $resourceSlug The slug to strip the prefix from.
     *
     * @throws InvalidArgumentException When the slug is not prefixed.
     */
    protected function stripDomainPrefix(string $resourceSlug): string
    {
        if (!str_starts_with($resourceSlug, $this->domainPrefix)) {
            throw new InvalidArgumentException('Slug is not prefixed.');
        }

        return substr($resourceSlug, strlen($this->domainPrefix));
    }

    /**
     * Allows to post process a file.
     *
     * @param TranslationFileInterface $file The file.
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    protected function postProcess(TranslationFileInterface $file): void
    {
    }

    /**
     * Save the passed translation files.
     *
     * @param TranslationFileInterface[] $files The files to save.
     */
    private function saveFiles(array $files): void
    {
        foreach ($files as $file) {
            $this->postProcess($file);
            if ($file->isChanged()) {
                $file->save();
            }
        }
    }
}
