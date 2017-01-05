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
 * @author     Yanick Witschi <yanick.witschi@terminal42.ch>
 * @copyright  2013-2017 CyberSpectrum.
 * @license    https://github.com/cyberspectrum/contao-toolbox/blob/master/LICENSE LGPL-3.0
 * @filesource
 */

namespace CyberSpectrum\Command;

use CyberSpectrum\JsonConfig;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Abstract base class for all commands.
 */
abstract class CommandBase extends Command
{
    /**
     * The name of the transifex project.
     *
     * @var string
     */
    protected $project;

    /**
     * The prefix to apply to all language files.
     *
     * @var string
     */
    protected $prefix;

    /**
     * Location of the transifex (xliff) directories.
     *
     * @var string
     */
    protected $txlang;

    /**
     * Location of the contao language directories.
     *
     * @var string
     */
    protected $ctolang;

    /**
     * Name of the base language (i.e. en).
     *
     * @var string
     */
    protected $baselanguage;

    /**
     * List of the other languages.
     *
     * @var string[]
     */
    protected $languages;

    /**
     * Names of files to skip.
     *
     * @var string[]
     */
    protected $skipFiles;

    /**
     * The transifex configuration prefix in the config.
     *
     * @var string
     */
    protected $transifexconfig;

    /**
     * {@inheritDoc}
     */
    protected function configure()
    {
        parent::configure();

        $this->addOption(
            'contao',
            'c',
            InputOption::VALUE_REQUIRED,
            'Contao language root directory (base to "en","de" etc.), ' .
            'if empty it will get read from the composer.json.',
            null
        );
        $this->addOption(
            'xliff',
            'x',
            InputOption::VALUE_OPTIONAL,
            'Xliff root directory (base to "en","de" etc.), if empty it will get read from the composer.json.',
            null
        );
        $this->addOption(
            'projectname',
            'p',
            InputOption::VALUE_OPTIONAL,
            'The project name, if empty it will get read from the composer.json.',
            null
        );
        $this->addOption(
            'prefix',
            null,
            InputOption::VALUE_OPTIONAL,
            'The prefix for all language files, if empty it will get read from the composer.json.',
            null
        );
        $this->addOption(
            'base-language',
            'b',
            InputOption::VALUE_OPTIONAL,
            'The base language to use.',
            'en'
        );
        $this->addOption(
            'skip-files',
            's',
            InputOption::VALUE_OPTIONAL,
            'Comma delimited list of language files that should be skipped (e.g. "addresses,default").',
            null
        );
        $this->addOption(
            'transifex-config',
            't',
            InputOption::VALUE_OPTIONAL,
            'The transifex configuration to take.',
            'transifex'
        );

        $this->addArgument(
            'languages',
            InputArgument::OPTIONAL,
            'Languages to process as comma delimited list or "all" for all languages.',
            'all'
        );
    }

    /**
     * Write a message to the console if the verbosity is equal or higher than the passed verbosity level.
     *
     * @param OutputInterface $output    The output interface to which shall be written.
     * @param string[]|string $messages  The messages to write.
     * @param bool            $newline   Flag if a newline shall be written after the messages.
     * @param int             $type      Type of the message.
     * @param int             $verbosity The verbosity level for which the messages shall be logged.
     *
     * @return void
     */
    protected function write(
        OutputInterface $output,
        $messages,
        $newline = false,
        $type = 0,
        $verbosity = OutputInterface::VERBOSITY_NORMAL
    ) {
        if ($output->getVerbosity() >= $verbosity) {
            $output->write($messages, $newline, $type);
        }
    }

    /**
     * Write a message to the console if the verbosity is equal or higher than verbose.
     *
     * @param OutputInterface $output   The output interface to which shall be written.
     * @param string[]|string $messages The messages to write.
     * @param bool            $newline  Flag if a newline shall be written after the messages.
     * @param int             $type     Type of the message.
     *
     * @return void
     */
    protected function writeVerbose(OutputInterface $output, $messages, $newline = false, $type = 0)
    {
        if ($output->getVerbosity() >= OutputInterface::VERBOSITY_VERBOSE) {
            $output->write($messages, $newline, $type);
        }
    }

    /**
     * Write a message always to the console for all verbosity levels higher than quiet.
     *
     * @param OutputInterface $output   The output interface to which shall be written.
     * @param string[]|string $messages The messages to write.
     * @param bool            $newline  Flag if a newline shall be written after the messages.
     * @param int             $type     Type of the message.
     *
     * @return void
     */
    protected function writeAlways(OutputInterface $output, $messages, $newline = false, $type = 0)
    {
        if ($output->getVerbosity() >= OutputInterface::VERBOSITY_QUIET) {
            $output->write($messages, $newline, $type);
        }
    }

    /**
     * Write a message to the console if the verbosity is equal or higher than the passed verbosity level.
     *
     * @param OutputInterface $output    The output interface to which shall be written.
     * @param string[]|string $messages  The messages to write.
     * @param int             $type      Type of the message.
     * @param int             $verbosity The verbosity level for which the messages shall be logged.
     *
     * @return void
     */
    protected function writeln(
        OutputInterface $output,
        $messages,
        $type = 0,
        $verbosity = OutputInterface::VERBOSITY_NORMAL
    ) {
        if ($output->getVerbosity() >= $verbosity) {
            $output->writeln($messages, $type);
        }
    }

    /**
     * Write a message to the console if the verbosity is equal or higher than verbose.
     *
     * @param OutputInterface $output   The output interface to which shall be written.
     * @param string[]|string $messages The messages to write.
     * @param int             $type     Type of the message.
     *
     * @return void
     */
    protected function writelnVerbose(OutputInterface $output, $messages, $type = 0)
    {
        if ($output->getVerbosity() >= OutputInterface::VERBOSITY_VERBOSE) {
            $output->writeln($messages, $type);
        }
    }

    /**
     * Write a message always to the console for all verbosity levels higher than quiet.
     *
     * @param OutputInterface $output   The output interface to which shall be written.
     * @param string[]|string $messages The messages to write.
     * @param int             $type     Type of the message.
     *
     * @return void
     */
    protected function writelnAlways(OutputInterface $output, $messages, $type = 0)
    {
        if ($output->getVerbosity() >= OutputInterface::VERBOSITY_QUIET) {
            $output->writeln($messages, $type);
        }
    }

    /**
     * Fetch some value from the config.
     *
     * First the value will be read from composer.json (section /extra/contao/...) and then from the global ctb config
     * (if any exists).
     *
     * @param string $name The config value to retrieve.
     *
     * @return mixed
     */
    protected function getConfigValue($name)
    {
        if (substr($name, 0, 1) != '/') {
            $name = '/' . $name;
        }
        $config = new JsonConfig(getcwd() . '/composer.json');
        $value  = $config->getConfigValue('/extra/contao' . $name);

        // Fallback to global config.
        if ($value === null) {
            /** @var JsonConfig $config */
            $config = $this->getApplication()->getConfig();
            if ($config !== null) {
                $value = $config->getConfigValue($name);
            }
        }

        return $value;
    }

    /**
     * Retrieve a config value from the transifex section of the config.
     *
     * @param string $name The name of the config value.
     *
     * @return mixed
     */
    protected function getTransifexConfigValue($name)
    {
        return $this->getConfigValue('/' . $this->transifexconfig . $name);
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
    protected function checkValidSlug($slug)
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

    /**
     * Retrieve the base path for languages.
     *
     * @return string
     */
    abstract protected function getLanguageBasePath();

    /**
     * Determine if a file is to be skipped or not.
     *
     * @param string $basename The base name of the file to test.
     *
     * @return bool
     */
    abstract protected function isNotFileToSkip($basename);

    /**
     * Determine the list of languages.
     *
     * @param OutputInterface $output The output interface to receive log messages.
     *
     * @param string          $srcdir The source directory to be examined.
     *
     * @param array           $filter The files to be filtered away (to be ignored).
     *
     * @return void
     *
     * @throws \InvalidArgumentException When the given source directory does not exist, an exception is thrown.
     */
    protected function determineLanguages(OutputInterface $output, $srcdir, $filter = array())
    {
        if (!is_dir($srcdir)) {
            throw new \InvalidArgumentException(sprintf('The path %s does not exist.', $srcdir));
        }

        $this->writelnVerbose($output, sprintf('<info>scanning for languages in: %s</info>', $srcdir));
        $matches  = array();
        $iterator = new \DirectoryIterator($srcdir);
        do {
            $item = $iterator->getFilename();

            if ((!$iterator->isDot()) && (strlen($item) == 2) && ((!$filter) || in_array($item, $filter))) {
                $matches[] = $item;
                $this->writelnVerbose($output, sprintf('<info>using: %s</info>', $item));
            } elseif (!$iterator->isDot()) {
                $this->writelnVerbose($output, sprintf('<info>not using: %s</info>', $item));
            }
            $iterator->next();
        } while ($iterator->valid());

        $this->languages = $matches;
    }

    /**
     * {@inheritDoc}
     *
     * @throws \RuntimeException When the needed settings can not be determined.
     */
    protected function initialize(InputInterface $input, OutputInterface $output)
    {
        $this->transifexconfig = $input->getOption('transifex-config');
        $this->setProject($input, $output);
        $this->setPrefix($input, $output);
        $this->setXliffDirectory($input, $output);
        $this->setContaoLanguageDirectory($input, $output);
        $this->baselanguage = $input->getOption('base-language');
        $this->skipFiles    = $input->getOption('skip-files') ? explode(',', $input->getOption('skip-files')) : null;

        if (!$this->skipFiles) {
            $this->skipFiles = $this->getTransifexConfigValue('/skip_files') ?: array();
        } else {
            // Make sure it is an array.
            $this->skipFiles = array();
        }

        $activeLanguages = array();
        if (($langs = $input->getArgument('languages')) != 'all') {
            $activeLanguages = explode(',', $langs);
        }

        $this->determineLanguages($output, $this->getLanguageBasePath(), $activeLanguages);
    }

    /**
     * Set the project, either from command line parameter or from config.
     *
     * @param InputInterface  $input  The input to use.
     *
     * @param OutputInterface $output The output to use.
     *
     * @return void
     *
     * @throws \RuntimeException When the value can not be determined.
     */
    private function setProject(InputInterface $input, OutputInterface $output)
    {
        $this->project = $input->getOption('projectname');
        if (!$this->project) {
            $this->project = $this->getTransifexConfigValue('/project');

            if (!$this->project) {
                throw new \RuntimeException('Error: unable to determine transifex project name.');
            }

            $this->writelnVerbose($output, sprintf('<info>automatically using project: %s</info>', $this->project));
        }

        $this->checkValidSlug($this->project);
    }

    /**
     * Set the prefix, either from command line parameter or from config.
     *
     * @param InputInterface  $input  The input to use.
     *
     * @param OutputInterface $output The output to use.
     *
     * @return void
     *
     * @throws \RuntimeException When the value can not be determined.
     */
    private function setPrefix(InputInterface $input, OutputInterface $output)
    {
        $this->prefix = $input->getOption('prefix');

        if ($this->prefix === null) {
            $this->prefix = $this->getTransifexConfigValue('/prefix');

            if ($this->prefix === null) {
                throw new \RuntimeException('Error: unable to determine transifex prefix.');
            }
            $this->writelnVerbose($output, sprintf('<info>automatically using prefix: %s</info>', $this->prefix));
        }

        $this->checkValidSlug($this->prefix);
    }

    /**
     * Set the xliff directory, either from command line parameter or from config.
     *
     * @param InputInterface  $input  The input to use.
     *
     * @param OutputInterface $output The output to use.
     *
     * @return void
     *
     * @throws \RuntimeException When the value can not be determined.
     */
    private function setXliffDirectory(InputInterface $input, OutputInterface $output)
    {
        $this->txlang = $input->getOption('xliff');

        if ($this->txlang === null) {
            $this->txlang = $this->getTransifexConfigValue('/languages_tx');

            if ($this->txlang === null) {
                throw new \RuntimeException('Error: unable to determine transifex root folder.');
            }
            $this->writelnVerbose($output, sprintf('<info>automatically using xliff folder: %s</info>', $this->txlang));
        }
    }

    /**
     * Set the contao language file directory, either from command line parameter or from config.
     *
     * @param InputInterface  $input  The input to use.
     *
     * @param OutputInterface $output The output to use.
     *
     * @return void
     *
     * @throws \RuntimeException When the value can not be determined.
     */
    private function setContaoLanguageDirectory(InputInterface $input, OutputInterface $output)
    {
        $this->ctolang = $input->getOption('contao');

        if ($this->ctolang === null) {
            $this->ctolang = $this->getTransifexConfigValue('/languages_cto');

            if ($this->ctolang === null) {
                throw new \RuntimeException('Error: unable to determine contao language root folder.');
            }
            $this->writelnVerbose(
                $output,
                sprintf('<info>automatically using Contao language folder: %s</info>', $this->ctolang)
            );
        }
    }
}
