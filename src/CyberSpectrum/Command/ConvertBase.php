<?php

/**
 * This toolbox provides easy ways to generate .xlf (XLIFF) files from Contao language files, push them to transifex
 * and pull translations from transifex and convert them back to Contao language files.
 *
 * @package      cyberspectrum/contao-toolbox
 * @author       Christian Schiffler <c.schiffler@cyberspectrum.de>
 * @author       Yanick Witschi <yanick.witschi@terminal42.ch>
 * @author       Tristan Lins <tristan.lins@bit3.de>
 * @copyright    CyberSpectrum
 * @license      LGPL-3.0+.
 * @filesource
 */

namespace CyberSpectrum\Command;

use CyberSpectrum\Translation\Contao;
use CyberSpectrum\Translation\Xliff;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * This class provides base methods for converting commands.
 */
abstract class ConvertBase extends CommandBase
{
    /**
     * Flag determining if obsolete files shall get purged at the end of the run.
     *
     * @var bool
     */
    protected $cleanup;

    /**
     * List of base files.
     *
     * @var string[]
     */
    protected $baseFiles;

    /**
     * {@inheritDoc}
     */
    protected function configure()
    {
        parent::configure();
        $this->addOption('cleanup', null, InputOption::VALUE_NONE, 'if set, remove obsolete files.');
    }

    /**
     * Retrieve the destination base path.
     *
     * @return string
     */
    abstract protected function getDestinationBasePath();

    /**
     * {@inheritDoc}
     */
    protected function getBaseFiles()
    {
        $iterator = new \DirectoryIterator($this->getLanguageBasePath() . DIRECTORY_SEPARATOR . $this->baselanguage);

        $files = array();
        while ($iterator->valid()) {
            if (!$iterator->isDot()
                && $iterator->isFile()
                && $this->isValidSourceFile($iterator->getPathname())
                && $this->isNotFileToSkip($iterator->getBasename())
            ) {
                $files[] = $iterator->getFilename();
            }
            $iterator->next();
        }

        $this->baseFiles = $files;
    }

    /**
     * {@inheritDoc}
     */
    abstract protected function isValidSourceFile($file);

    /**
     * {@inheritDoc}
     */
    abstract protected function isValidDestinationFile($file);

    /**
     * {@inheritDoc}
     */
    abstract protected function processLanguage(OutputInterface $output, $language);

    /**
     * Cleanup anything that is obsolete now.
     *
     * @param OutputInterface $output           The output to use.
     *
     * @param string          $language         The language string.
     *
     * @param string[]        $destinationFiles The list of destination files.
     *
     * @return void
     */
    protected function cleanupObsoleteFiles(OutputInterface $output, $language, $destinationFiles)
    {
        if ($this->cleanup && ($files = array_diff($this->determinePresentFiles($language), $destinationFiles))) {
            $this->writeln(
                $output,
                sprintf(
                    'the following obsolete files have been found and will get deleted: <info>%s</info>',
                    implode(', ', $files)
                )
            );

            foreach ($files as $file) {
                unlink($this->getDestinationBasePath() . DIRECTORY_SEPARATOR . $language . DIRECTORY_SEPARATOR . $file);
                $this->writelnVerbose($output, sprintf('deleting obsolete file <info>%s</info>', $file));
            }
        }
    }

    /**
     * {@inheritDoc}
     */
    protected function isNotFileToSkip($basename)
    {
        return is_array($this->skipFiles) ? !in_array(substr($basename, 0, -4), $this->skipFiles) : true;
    }

    /**
     * {@inheritDoc}
     */
    protected function determinePresentFiles($language)
    {
        $iterator = new \DirectoryIterator($this->getDestinationBasePath() . DIRECTORY_SEPARATOR . $language);

        $files = array();
        while ($iterator->valid()) {
            if (!$iterator->isDot() && $iterator->isFile() && $this->isValidDestinationFile($iterator->getPathname())) {
                $files[] = $iterator->getFilename();
            }
            $iterator->next();
        }

        return $files;
    }

    /**
     * {@inheritDoc}
     */
    protected function initialize(InputInterface $input, OutputInterface $output)
    {
        parent::initialize($input, $output);

        $this->cleanup = $input->getOption('cleanup');
    }

    /**
     * {@inheritDoc}
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->getBaseFiles();

        foreach ($this->languages as $lang) {
            $this->processLanguage($output, $lang);
        }
    }
}
