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

namespace CyberSpectrum\ContaoToolBox\Transifex;

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
        $data = $this->getTransport()->resources()->all($this->ensureParameter('slug'));

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
     * @return TranslationResource
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
