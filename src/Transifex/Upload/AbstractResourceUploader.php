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

use Closure;
use CyberSpectrum\PhpTransifex\Model\Project;
use Psr\Log\LoggerInterface;

/**
 * This class synchronizes all resources from transifex.
 */
abstract class AbstractResourceUploader
{
    /** The prefix to apply to the translation domain. */
    protected string $domainPrefix = '';

    /** An optional closure to filter resources. */
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
        protected readonly string $outputDirectory,
        protected readonly string $baseLanguage,
        protected readonly LoggerInterface $logger
    ) {
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
     * @param Closure|null $resourceFilter The new value.
     */
    public function setResourceFilter(?Closure $resourceFilter = null): self
    {
        $this->resourceFilter = $resourceFilter;

        return $this;
    }

    /** Upload the resources. */
    public function upload(): void
    {
        $files     = $this->getResourceFiles();
        $resources = $this->project->resources();
        foreach ($files as $resourceSlug => $fileContent) {
            if (
                (null !== $this->resourceFilter)
                && !$this->resourceFilter->__invoke($resourceSlug)
            ) {
                continue;
            }
            $prefixedSlug = $this->domainPrefix . $resourceSlug;
            if ($resources->has($prefixedSlug)) {
                $this->logger->notice('Updating resource {resource}', ['resource' => $prefixedSlug]);
                $resource = $resources->getByName($prefixedSlug);
            } else {
                $this->logger->notice('Creating new resource {resource}', ['resource' => $prefixedSlug]);
                $resource = $resources->add($prefixedSlug, $prefixedSlug, 'XLIFF');
            }
            $resource->setContent($fileContent);
        }
    }

    /**
     * Fetch the translation files to upload.
     *
     * Resulting array has the resource slug as key and the file contents as value.
     *
     * @return array<string, string>
     */
    abstract protected function getResourceFiles(): array;
}
