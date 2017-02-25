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

namespace CyberSpectrum\ContaoToolBox;

/**
 * This class holds meta information about the project being processed.
 */
class Project
{
    /**
     * The name of the transifex project.
     *
     * @var string
     */
    private $project = '';

    /**
     * The prefix to apply to all language files.
     *
     * @var string
     */
    private $prefix = '';

    /**
     * Location of the transifex (xliff) directories.
     *
     * @var string
     */
    private $xliffDirectory = '';

    /**
     * Location of the contao language directories.
     *
     * @var string
     */
    private $contaoDirectory = '';

    /**
     * Name of the base language (i.e. en).
     *
     * @var string
     */
    private $baseLanguage = '';

    /**
     * Names of files to skip.
     *
     * @var string[]
     */
    protected $skipFiles = [];

    /**
     * Retrieve project
     *
     * @return string
     */
    public function getProject()
    {
        return $this->project;
    }

    /**
     * Set project.
     *
     * @param string $project The new value.
     *
     * @return Project
     */
    public function setProject($project)
    {
        $this->guardValidSlug($project);

        $this->project = (string) $project;

        return $this;
    }

    /**
     * Retrieve prefix
     *
     * @return string
     */
    public function getPrefix()
    {
        return $this->prefix;
    }

    /**
     * Set prefix.
     *
     * @param string $prefix The new value.
     *
     * @return Project
     */
    public function setPrefix($prefix)
    {
        $this->guardValidSlug($prefix);

        $this->prefix = (string) $prefix;

        return $this;
    }

    /**
     * Retrieve xliff directory.
     *
     * @return string
     */
    public function getXliffDirectory()
    {
        return $this->xliffDirectory;
    }

    /**
     * Set xliff directory.
     *
     * @param string $xliffDirectory The new value.
     *
     * @return Project
     */
    public function setXliffDirectory($xliffDirectory)
    {
        $this->xliffDirectory = (string) $xliffDirectory;

        return $this;
    }

    /**
     * Retrieve contao language directory.
     *
     * @return string
     */
    public function getContaoDirectory()
    {
        return $this->contaoDirectory;
    }

    /**
     * Set contao language directory.
     *
     * @param string $contaoDirectory The new value.
     *
     * @return Project
     */
    public function setContaoDirectory($contaoDirectory)
    {
        $this->contaoDirectory = (string) $contaoDirectory;

        return $this;
    }

    /**
     * Retrieve base language.
     *
     * @return string
     */
    public function getBaseLanguage()
    {
        return $this->baseLanguage;
    }

    /**
     * Set base language.
     *
     * @param string $baseLanguage The new value.
     *
     * @return Project
     */
    public function setBaseLanguage($baseLanguage)
    {
        $this->baseLanguage = (string) $baseLanguage;

        return $this;
    }

    /**
     * Retrieve files to skip.
     *
     * @return \string[]
     */
    public function getSkipFiles()
    {
        return $this->skipFiles;
    }

    /**
     * Set files to skip.
     *
     * @param \string[] $skipFiles The new value.
     *
     * @return Project
     */
    public function setSkipFiles($skipFiles)
    {
        $this->skipFiles = (array) $skipFiles;

        return $this;
    }

    /**
     * Check that the passed project slug complies to the transifex restrictions.
     *
     * @param string $slug The slug to test.
     *
     * @return void
     *
     * @throws \RuntimeException When the slug is invalid, an exception is thrown.
     */
    protected function guardValidSlug($slug)
    {
        if (preg_match_all('#^([a-z,A-Z,0-9,\-,_]*)(.+)?$#', $slug, $matches)
            && (strlen($matches[2][0]) > 0)
        ) {
            throw new \RuntimeException(
                sprintf(
                    'Error: prefix "%s" is invalid. It must only contain letters, numbers, underscores and hyphens. ' .
                    'Found problem near: "%s"',
                    $slug,
                    $matches[2][0]
                )
            );
        }
    }
}
