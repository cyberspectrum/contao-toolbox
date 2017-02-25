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
use CyberSpectrum\PhpTransifex\Model\ProjectModel;
use Psr\Log\LoggerInterface;

/**
 * This class synchronizes all resources from transifex.
 */
abstract class AbstractResourceUploader
{
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
     * An optional closure to filter resources.
     *
     * @var Closure
     */
    private $resourceFilter;

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
    }

    /**
     * Set the domain prefix.
     *
     * @param string $domainPrefix The new prefix.
     *
     * @return AbstractResourceUploader
     */
    public function setDomainPrefix($domainPrefix)
    {
        $this->domainPrefix = (string) $domainPrefix;

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
     * @param Closure $resourceFilter The new value.
     *
     * @return AbstractResourceUploader
     */
    public function setResourceFilter(Closure $resourceFilter = null)
    {
        $this->resourceFilter = $resourceFilter;

        return $this;
    }

    /**
     * Upload the resources.
     *
     * @return void
     */
    public function upload()
    {
        $files     = $this->getResourceFiles();
        $resources = $this->project->resources();
        foreach ($files as $resourceSlug => $fileContent) {
            if ((null !== $this->resourceFilter)
                && !$this->resourceFilter->__invoke($resourceSlug)) {
                continue;
            }
            $prefixedSlug = $this->domainPrefix . $resourceSlug;
            if ($resources->has($prefixedSlug)) {
                $this->logger->notice('Updating resource {resource}', ['resource' => $prefixedSlug]);
                $resource = $resources->get($prefixedSlug);
            } else {
                $this->logger->notice('Creating new resource {resource}', ['resource' => $prefixedSlug]);
                $resource = $resources->add($prefixedSlug, $prefixedSlug, 'XLIFF');
            }
            $resource->setContent($fileContent);
        }
        $this->project->save();
    }

    /**
     * Fetch the translation files to upload.
     *
     * Resulting array has the resource slug as key and the file contents as value.
     *
     * @return string[]
     */
    abstract protected function getResourceFiles();
}
