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

use RuntimeException;

/**
 * This class holds meta information about the project being processed.
 */
class Project
{
    /** The name of the transifex organization. */
    private string $organization = '';

    /** The name of the transifex project. */
    private string $project = '';

    /**
     * The php doc header to use in php files.
     *
     * @var list<string>
     */
    private array $phpFileHeader = [];

    /** The prefix to apply to all language files. */
    private string $prefix = '';

    /** Location of the transifex (xliff) directories. */
    private string $xliffDirectory = '';

    /** Location of the contao language directories. */
    private string $contaoDirectory = '';

    /** Name of the base language (i.e. en). */
    private string $baseLanguage = '';

    /**
     * Names of files to skip.
     *
     * @var list<string>
     */
    protected array $skipFiles = [];

    /** Retrieve organization. */
    public function getOrganization(): string
    {
        return $this->organization;
    }

    /**
     * Set organization.
     *
     * @param string $organization The new value.
     */
    public function setOrganization(string $organization): self
    {
        $this->organization = $organization;

        return $this;
    }

    /** Retrieve project */
    public function getProject(): string
    {
        return $this->project;
    }

    /**
     * Set project.
     *
     * @param string $project The new value.
     */
    public function setProject(string $project): self
    {
        $this->guardValidSlug($project);

        $this->project = $project;

        return $this;
    }

    /**
     * Retrieve php file header.
     *
     * @return list<string>
     */
    public function getPhpFileHeader(): array
    {
        return $this->phpFileHeader;
    }

    /**
     * Set php file header.
     *
     * @param list<string> $phpFileHeader The new value.
     */
    public function setPhpFileHeader(array $phpFileHeader): self
    {
        $this->phpFileHeader = $phpFileHeader;

        return $this;
    }

    /** Retrieve prefix */
    public function getPrefix(): string
    {
        return $this->prefix;
    }

    /**
     * Set prefix.
     *
     * @param string $prefix The new value.
     */
    public function setPrefix(string $prefix): self
    {
        $this->guardValidSlug($prefix);

        $this->prefix = $prefix;

        return $this;
    }

    /** Retrieve xliff directory. */
    public function getXliffDirectory(): string
    {
        return $this->xliffDirectory;
    }

    /**
     * Set xliff directory.
     *
     * @param string $xliffDirectory The new value.
     */
    public function setXliffDirectory(string $xliffDirectory): self
    {
        $this->xliffDirectory = $xliffDirectory;

        return $this;
    }

    /** Retrieve contao language directory. */
    public function getContaoDirectory(): string
    {
        return $this->contaoDirectory;
    }

    /**
     * Set contao language directory.
     *
     * @param string $contaoDirectory The new value.
     */
    public function setContaoDirectory(string $contaoDirectory): self
    {
        $this->contaoDirectory = $contaoDirectory;

        return $this;
    }

    /**
     * Retrieve base language.
     */
    public function getBaseLanguage(): string
    {
        return $this->baseLanguage;
    }

    /**
     * Set base language.
     *
     * @param string $baseLanguage The new value.
     */
    public function setBaseLanguage(string $baseLanguage): self
    {
        $this->baseLanguage = $baseLanguage;

        return $this;
    }

    /**
     * Retrieve files to skip.
     *
     * @return list<string>
     */
    public function getSkipFiles(): array
    {
        return $this->skipFiles;
    }

    /**
     * Set files to skip.
     *
     * @param list<string> $skipFiles The new value.
     */
    public function setSkipFiles(array $skipFiles): self
    {
        $this->skipFiles = $skipFiles;

        return $this;
    }

    /**
     * Check that the passed project slug complies to the transifex restrictions.
     *
     * @param string $slug The slug to test.
     *
     * @throws RuntimeException When the slug is invalid, an exception is thrown.
     */
    protected function guardValidSlug(string $slug): void
    {
        if (
            preg_match_all('#^([a-zA-Z0-9\-_]*)(.+)?$#', $slug, $matches)
            && ('' !== $matches[2][0])
        ) {
            throw new RuntimeException(
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
