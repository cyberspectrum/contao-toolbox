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

namespace CyberSpectrum\ContaoToolBox\Console\Command\Transifex;

use CyberSpectrum\ContaoToolBox\Console\Command\CommandBase;
use CyberSpectrum\PhpTransifex\ApiClient\ClientFactory;
use CyberSpectrum\PhpTransifex\ApiClient\Generated\Authentication\BearerAuthAuthentication;
use CyberSpectrum\PhpTransifex\Model\Project;
use CyberSpectrum\PhpTransifex\PhpTransifex;
use RuntimeException;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Logger\ConsoleLogger;
use Symfony\Component\Console\Output\OutputInterface;

use function getenv;
use function is_string;

/**
 * This class is the base command implementation for all commands interfacing with transifex.
 */
class TransifexBase extends CommandBase
{
    /** The transport client. */
    private ?PhpTransifex $transifex = null;

    /**
     * Retrieve the transport client.
     */
    protected function getPhpTransifex(): PhpTransifex
    {
        if (null === $this->transifex) {
            throw new RuntimeException('No transifex client set - ensure initialize() is called first.');
        }

        return $this->transifex;
    }

    protected function getPhpTransifexProject(): Project
    {
        $project          = $this->getProject();
        $organizationName = $project->getOrganization();
        $projectName      = $project->getProject();

        return $this
            ->getPhpTransifex()
            ->organizations()
            ->getBySlug($organizationName)
            ->projects()
            ->getBySlug($projectName);
    }

    protected function configure(): void
    {
        parent::configure();
        $this->addOption(
            'token',
            'T',
            InputOption::VALUE_REQUIRED,
            'Token for transifex.'
        );

        $this->setHelp(
            'NOTE: you can also specify token via the environment for automated jobs.' . PHP_EOL .
            'token: transifextoken=token' . PHP_EOL
        );
    }

    protected function initialize(InputInterface $input, OutputInterface $output): void
    {
        parent::initialize($input, $output);
        $factory = new ClientFactory(
            new ConsoleLogger($output),
            [new BearerAuthAuthentication($this->getToken($input, $output))]
        );
        $client = $factory->create($factory->createHttpClient());
        $this->transifex = new PhpTransifex($client);
    }

    /**
     * Retrieve the transifex token.
     *
     * @param InputInterface  $input  An InputInterface instance.
     * @param OutputInterface $output An OutputInterface instance.
     *
     * @throws RuntimeException If no username could be determined..
     */
    private function getToken(InputInterface $input, OutputInterface $output): string
    {
        /** @psalm-suppress MixedAssignment */
        if ($this->isValidToken($token = $input->getOption('token'))) {
            return $token;
        }
        /** @psalm-suppress MixedAssignment */
        if ($this->isValidToken($token = $this->getTransifexConfigValue('/token'))) {
            $this->writelnVerbose($output, 'Using transifex token specified in config.');

            return $token;
        }
        if ($this->isValidToken($token = getenv('transifextoken'))) {
            $this->writelnVerbose($output, 'Using transifex token specified in environment.');

            return $token;
        }

        throw new RuntimeException('Token needed since transifex API 3.0');
    }

    /**
     * @psalm-assert-if-true non-empty-string $value
     */
    private function isValidToken(mixed $value): bool
    {
        if (!is_string($value)) {
            return false;
        }

        if ('' === $value) {
            return false;
        }

        return true;
    }
}
