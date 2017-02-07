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
 * @author     Tristan Lins <tristan.lins@bit3.de>
 * @copyright  2013-2017 CyberSpectrum.
 * @license    https://github.com/cyberspectrum/contao-toolbox/blob/master/LICENSE LGPL-3.0
 * @filesource
 */

namespace CyberSpectrum\Command\Transifex;

use CyberSpectrum\Command\CommandBase;
use CyberSpectrum\PhpTransifex\Client;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;

/**
 * This class is the base command implementation for all commands interfacing with transifex.
 */
class TransifexBase extends CommandBase
{
    /**
     * The transport client.
     *
     * @var Client
     */
    private $api;

    /**
     * The user name.
     *
     * @var string
     */
    private $user;

    /**
     * The password.
     *
     * @var string
     */
    private $password;

    /**
     * Retrieve the transport client.
     *
     * @return Client
     */
    protected function getApi()
    {
        if (!$this->api) {
            $this->api = new Client();
            $this->api->authenticate($this->user, $this->password);
        }

        return $this->api;
    }

    /**
     * {@inheritDoc}
     */
    protected function configure()
    {
        parent::configure();

        $this->addOption(
            'user',
            'U',
            InputOption::VALUE_OPTIONAL,
            'Username on transifex, if empty prompt on console.',
            null
        );
        $this->addOption(
            'pass',
            'P',
            InputOption::VALUE_OPTIONAL,
            'Password on transifex, if empty prompt on console.',
            null
        );

        $this->setHelp(
            'NOTE: you can also specify username and password via the environment for automated jobs.' . PHP_EOL .
            'user: transifexuser=username' . PHP_EOL .
            'pass: transifexpass=password' . PHP_EOL
        );
    }

    /**
     * {@inheritDoc}
     */
    protected function getLanguageBasePath()
    {
        $path = realpath($this->txlang);
        if (!$path) {
            return $this->txlang;
        }

        return $path;
    }

    /**
     * {@inheritDoc}
     */
    protected function isNotFileToSkip($basename)
    {
        return is_array($this->skipFiles) ? !in_array(substr($basename, 0, -4), $this->skipFiles) : true;
    }

    /**
     * Retrieve all XLIFF for a given language.
     *
     * @param string $language The language to search the xliff files in.
     *
     * @return string[]
     */
    protected function getAllTxFiles($language)
    {
        $iterator = new \DirectoryIterator($this->txlang . DIRECTORY_SEPARATOR . $language);

        $files = array();
        while ($iterator->valid()) {
            if (!$iterator->isDot()
                && $iterator->isFile()
                && $iterator->getExtension() == 'xlf'
                && $this->isNotFileToSkip($iterator->getPathname())
            ) {
                $files[$iterator->getPathname()] = $iterator->getFilename();
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

        $this->user     = $this->getUser($input, $output);
        $this->password = $this->getPassword($input, $output);
    }

    /**
     * Retrieve the user name.
     *
     * @param InputInterface  $input  An InputInterface instance.
     *
     * @param OutputInterface $output An OutputInterface instance.
     *
     * @return string|null
     *
     * @throws \RuntimeException If no username could be determined..
     */
    private function getUser(InputInterface $input, OutputInterface $output)
    {
        if ($user = $input->getOption('user')) {
            return $user;
        }
        if ($user = $this->getTransifexConfigValue('/user')) {
            $this->writelnVerbose($output, 'Using transifex user specified in config.');

            return $user;
        }
        if ($user = getenv('transifexuser')) {
            $this->writelnVerbose($output, 'Using transifex user specified in environment.');

            return $user;
        }
        if ($input->isInteractive()) {
            /** @var \Symfony\Component\Console\Helper\QuestionHelper $dialog */
            $dialog = $this->getHelperSet()->get('question');
            if ($user = $dialog->ask($input, $output, new Question('Transifex user:'))) {
                return $user;
            }
        }

        throw new \RuntimeException(
            'Error: you must either specify an username on the commandline or run interactive.'
        );
    }

    /**
     * Retrieve the password.
     *
     * @param InputInterface  $input  An InputInterface instance.
     *
     * @param OutputInterface $output An OutputInterface instance.
     *
     * @return string|null
     *
     * @throws \RuntimeException If no password could be determined..
     */
    private function getPassword(InputInterface $input, OutputInterface $output)
    {
        if ($pass = $input->getOption('pass')) {
            return $pass;
        }
        if ($pass = $this->getTransifexConfigValue('/pass')) {
            $this->writelnVerbose($output, 'Using transifex password specified in config.');

            return $pass;
        }
        if ($pass = getenv('transifexpass')) {
            $this->writelnVerbose($output, 'Using transifex password specified in environment.');

            return $pass;
        }
        if ($input->isInteractive()) {
            /** @var \Symfony\Component\Console\Helper\QuestionHelper $dialog */
            $dialog = $this->getHelperSet()->get('question');
            if ($pass = $dialog->ask($input, $output, (new Question('Transifex password:'))->setHidden(true))) {
                return $pass;
            }
        }

        throw new \RuntimeException('Error: you must either specify a password on the commandline or run interactive.');
    }
}
