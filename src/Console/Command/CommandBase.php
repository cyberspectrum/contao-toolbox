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

namespace CyberSpectrum\ContaoToolBox\Console\Command;

use CyberSpectrum\ContaoToolBox\Console\ToolBoxApplication;
use CyberSpectrum\ContaoToolBox\Project;
use CyberSpectrum\ContaoToolBox\Util\JsonConfig;
use RuntimeException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

use function array_map;
use function array_values;
use function explode;
use function getcwd;
use function is_array;
use function is_string;
use function sprintf;
use function str_starts_with;
use function trigger_error;

/**
 * Abstract base class for all commands.
 */
abstract class CommandBase extends Command
{
    /** The config from ctb.json. */
    private ?JsonConfig $config = null;

    /** The composer config. */
    private ?JsonConfig $composer = null;

    /** The application config. */
    private ?JsonConfig $appConfig = null;

    /** The project information. */
    private ?Project $project;

    /** The transifex configuration prefix in the config. */
    private string $transifexConfig;

    protected function configure(): void
    {
        parent::configure();

        $this->addOption(
            'contao',
            'c',
            InputOption::VALUE_REQUIRED,
            'Contao language root directory (base to "en","de" etc.), ' .
            'if empty it will get read from the composer.json.'
        );
        $this->addOption(
            'xliff',
            'x',
            InputOption::VALUE_OPTIONAL,
            'Xliff root directory (base to "en","de" etc.), if empty it will get read from the composer.json.'
        );
        $this->addOption(
            'projectname',
            'p',
            InputOption::VALUE_OPTIONAL,
            'The project name, if empty it will get read from the composer.json.'
        );
        $this->addOption(
            'prefix',
            null,
            InputOption::VALUE_OPTIONAL,
            'The prefix for all language files, if empty it will get read from the composer.json.'
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
            'Comma delimited list of language files that should be skipped (e.g. "addresses,default").'
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
     * Write a message to the console if the verbosity is equal or higher than verbose.
     *
     * @param OutputInterface     $output   The output interface to which shall be written.
     * @param string|list<string> $messages The messages to write.
     * @param int                 $type     Type of the message.
     */
    protected function writelnVerbose(OutputInterface $output, array|string $messages, int $type = 0): void
    {
        if ($output->getVerbosity() >= OutputInterface::VERBOSITY_VERBOSE) {
            $output->writeln($messages, $type);
        }
    }

    /**
     * Write a message to the console if the verbosity is equal or higher than verbose.
     *
     * @param OutputInterface     $output   The output interface to which shall be written.
     * @param string|list<string> $messages The messages to write.
     * @param int                 $type     Type of the message.
     */
    protected function writeln(OutputInterface $output, array|string $messages, int $type = 0): void
    {
        if ($output->getVerbosity() >= OutputInterface::VERBOSITY_NORMAL) {
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
    protected function getConfigValue(string $name): mixed
    {
        if (!str_starts_with($name, '/')) {
            $name = '/' . $name;
        }

        /** @psalm-suppress MixedAssignment */
        if ($this->config && (null !== $value = $this->config->getConfigValue($name))) {
            return $value;
        }

        /** @psalm-suppress MixedAssignment */
        if (null !== $this->composer && (null !== $value = $this->composer->getConfigValue('/extra/contao' . $name))) {
            // @codingStandardsIgnoreStart - Deprecations may be silenced.
            @trigger_error('Deprecated configuration from composer.json in use.' .
                ' Please move value ' . $name . ' to ctb.json or global configuration.',
                E_USER_DEPRECATED
            );
            // @codingStandardsIgnoreEnd
            return $value;
        }

        // Fallback to global config.
        /** @psalm-suppress MixedAssignment */
        if ($this->appConfig && null !== $value = $this->appConfig->getConfigValue($name)) {
            return $value;
        }

        return null;
    }

    /**
     * Retrieve a config value from the transifex section of the config.
     *
     * @param string $name The name of the config value.
     */
    protected function getTransifexConfigValue(string $name): mixed
    {
        return $this->getConfigValue('/' . $this->transifexConfig . $name);
    }

    /**
     * {@inheritDoc}
     *
     * @throws RuntimeException When the needed settings can not be determined.
     */
    protected function initialize(InputInterface $input, OutputInterface $output): void
    {
        if (file_exists($configFile = getcwd() . '/composer.json')) {
            $this->composer = new JsonConfig($configFile);
        }
        if (file_exists($configFile = getcwd() . '/ctb.json')) {
            $this->config = new JsonConfig($configFile);
        }
        if (($application = $this->getApplication()) instanceof ToolBoxApplication) {
            $this->appConfig = $application->getConfig();
        }

        $this->transifexConfig = (string) $input->getOption('transifex-config');
        $this->project         = $project = new Project();
        $this->setProject($input, $output);
        $this->setPhpFileHeader();
        $this->setPrefix($input, $output);
        $this->setXliffDirectory($input, $output);
        $this->setContaoLanguageDirectory($input, $output);
        $project->setBaseLanguage((string) $input->getOption('base-language'));
        /** @psalm-suppress MixedAssignment */
        if (is_string($files = $input->getOption('skip-files'))) {
            $project->setSkipFiles(explode(',', $files));
        } elseif (is_array($files = $this->getTransifexConfigValue('/skip_files'))) {
            $project->setSkipFiles(array_values(array_map(fn (mixed $value) => (string) $value, $files)));
        }
    }

    protected function getProject(): Project
    {
        if (null === $this->project) {
            throw new RuntimeException('No project set - ensure initialize() is called first.');
        }
        return $this->project;
    }

    /**
     * Set the project, either from command line parameter or from config.
     *
     * @param InputInterface  $input  The input to use.
     *
     * @param OutputInterface $output The output to use.
     *
     * @throws RuntimeException When the value can not be determined.
     */
    private function setProject(InputInterface $input, OutputInterface $output): void
    {
        /** @psalm-suppress MixedAssignment */
        $projectName = $input->getOption('projectname');
        if (!is_string($projectName) || '' === $projectName) {
            /** @psalm-suppress MixedAssignment */
            $projectName = $this->getTransifexConfigValue('/project');

            if (!is_string($projectName) || '' === $projectName) {
                throw new RuntimeException('Error: unable to determine transifex project name.');
            }

            $this->writelnVerbose($output, sprintf('<info>automatically detected project: %s</info>', $projectName));
        }
        if (!str_contains($projectName, '/')) {
            $this->writeln(
                $output,
                sprintf(
                    '<error>' .
                    'Project name "%1$s" is not in expected format, please specify as "<organization>/<project>". ' .
                    'We will use "%1$s/%1$s" for the moment - This will fail in the future.' .
                    '</error>',
                    $projectName
                )
            );
            $projectName .= '/' . $projectName;
        }

        [$projectName, $organizationName] = explode('/', $projectName);
        $project = $this->getProject();
        $project->setProject($projectName);
        $project->setOrganization($organizationName);
    }

    /**
     * Set the php file header from command config of use default.
     *
     * @throws RuntimeException When the value can not be determined.
     */
    private function setPhpFileHeader(): void
    {
        /** @psalm-suppress MixedAssignment */
        if (null === $fileHeader = $this->getTransifexConfigValue('/php-file-header')) {
            $fileHeader = 'Translations are managed using Transifex. To create a new translation
or to help to maintain an existing one, please register at transifex.com.

@link https://www.transifex.com/signup/?join_project=$$project$$

last-updated: $$lastchanged$$
';
        }
        if (is_string($fileHeader)) {
            $fileHeader = explode("\n", $fileHeader);
        }

        if (!is_array($fileHeader)) {
            throw new RuntimeException('Error: invalid file header provided.');
        }

        $this->getProject()->setPhpFileHeader(
            array_values(array_map(fn (mixed $value) => (string) $value, $fileHeader))
        );
    }

    /**
     * Set the prefix, either from command line parameter or from config.
     *
     * @param InputInterface  $input  The input to use.
     * @param OutputInterface $output The output to use.
     *
     * @throws RuntimeException When the value can not be determined.
     */
    private function setPrefix(InputInterface $input, OutputInterface $output): void
    {
        /** @psalm-suppress MixedAssignment */
        if (!is_string($prefix = $input->getOption('prefix'))) {
            /** @psalm-suppress MixedAssignment */
            if (!is_string($prefix = $this->getTransifexConfigValue('/prefix'))) {
                throw new RuntimeException('Error: unable to determine transifex prefix.');
            }
            $this->writelnVerbose($output, sprintf('<info>automatically using prefix: %s</info>', $prefix));
        }

        $this->getProject()->setPrefix($prefix);
    }

    /**
     * Set the xliff directory, either from command line parameter or from config.
     *
     * @param InputInterface  $input  The input to use.
     * @param OutputInterface $output The output to use.
     *
     * @throws RuntimeException When the value can not be determined.
     */
    private function setXliffDirectory(InputInterface $input, OutputInterface $output): void
    {
        /** @psalm-suppress MixedAssignment */
        if (!is_string($txLang = $input->getOption('xliff'))) {
            /** @psalm-suppress MixedAssignment */
            if (!is_string($txLang = $this->getTransifexConfigValue('/languages_tx'))) {
                throw new RuntimeException('Error: unable to determine transifex root folder.');
            }
            $this->writelnVerbose($output, sprintf('<info>automatically using xliff folder: %s</info>', $txLang));
        }

        $this->getProject()->setXliffDirectory($txLang);
    }

    /**
     * Set the contao language file directory, either from command line parameter or from config.
     *
     * @param InputInterface  $input  The input to use.
     * @param OutputInterface $output The output to use.
     *
     * @throws RuntimeException When the value can not be determined.
     */
    private function setContaoLanguageDirectory(InputInterface $input, OutputInterface $output): void
    {
        /** @psalm-suppress MixedAssignment */
        if (!is_string($ctoLang = $input->getOption('contao'))) {
            /** @psalm-suppress MixedAssignment */
            if (!is_string($ctoLang = $this->getTransifexConfigValue('/languages_cto'))) {
                throw new RuntimeException('Error: unable to determine contao language root folder.');
            }
            $this->writelnVerbose(
                $output,
                sprintf('<info>automatically using Contao language folder: %s</info>', $ctoLang)
            );
        }

        $this->getProject()->setContaoDirectory($ctoLang);
    }
}
