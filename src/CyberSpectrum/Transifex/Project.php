<?php

/**
 * This toolbox provides easy ways to generate .xlf (XLIFF) files from Contao language files, push them to transifex
 * and pull translations from transifex and convert them back to Contao language files.
 *
 * @package      cyberspectrum/contao-toolbox
 * @author       Christian Schiffler <c.schiffler@cyberspectrum.de>
 * @copyright    CyberSpectrum
 * @license      LGPL-3.0+.
 * @filesource
 */

namespace CyberSpectrum\Transifex;

/**
 * This class abstracts project information retrieved from transifex.
 */
class Project extends BaseObject
{
    /**
     * The project slug on transifex.
     *
     * @var string
     */
    protected $slug;

    /**
     * Set the slug of the project.
     *
     * @param string $slug The slug name.
     *
     * @return Project
     */
    public function setSlug($slug)
    {
        $this->slug = $slug;

        return $this;
    }

    /**
     * Retrieve the slug name.
     *
     * @return string
     */
    public function getSlug()
    {
        return $this->slug;
    }

    /**
     * Retrieve all resources of this project.
     *
     * @return Resource[]
     */
    public function getResources()
    {
        $data = $this->executeJson(sprintf('project/%s/resources/', $this->ensureParameter('slug')));

        $resources = array();
        foreach ($data as $entry) {
            $resource = new TranslationResource($this->getTransport());
            $resource->setProject($this);
            $resource->setFromResult($entry);
            $resources[$resource->getSlug()] = $resource;
        }

        return $resources;
    }

    /**
     * Retrieve a single resource with a given slug.
     *
     * @param string $slug The slug of the resource.
     *
     * @return Resource
     */
    public function getResource($slug)
    {
        $resource = new TranslationResource($this->getTransport());

        $resource->setProject($this->getSlug());
        $resource->setSlug($slug);

        $resource->fetchDetails();

        return $resource;
    }
}
