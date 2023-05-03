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

namespace CyberSpectrum\ContaoToolBox\Console\Command\Transifex;

use CyberSpectrum\ContaoToolBox\Transifex\Download\AbstractResourceDownloader;
use CyberSpectrum\ContaoToolBox\Transifex\Download\ContaoResourceDownloader;
use CyberSpectrum\ContaoToolBox\Transifex\Download\XliffResourceDownloader;
use CyberSpectrum\PhpTransifex\Model\Project;
use InvalidArgumentException;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Logger\ConsoleLogger;
use Symfony\Component\Console\Output\OutputInterface;

use function explode;
use function in_array;

/**
 * This command is used for downloading translations from transifex.
 */
final class DownloadTransifex extends TransifexBase
{
    protected function configure(): void
    {
        parent::configure();
        $this->setName('download-transifex');
        $this->setDescription('Download xliff translations from transifex.');

        $this->addOption(
            'destination',
            null,
            InputOption::VALUE_OPTIONAL,
            'The download destination to use (either xliff or contao).',
            'xliff'
        );
    }

    /**
     * {@inheritDoc}
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $project          = $this->getProject();
        $txProject        = $this->getPhpTransifexProject();
        $downloader       = $this->createDownloader(
            (string) $input->getOption('destination'),
            new ConsoleLogger($output),
            $txProject
        );

        /** @psalm-suppress MixedAssignment */
        if ('all' !== ($languages = $input->getArgument('languages'))) {
            $downloader->setAllowedLanguages(explode(',', (string) $languages));
        }
        $downloader->setDomainPrefix($project->getPrefix());
        if ($skipFiles = $project->getSkipFiles()) {
            $downloader->setResourceFilter(
                fn (string $resourceSlug): bool => !in_array($resourceSlug, $skipFiles, true)
            );
        }

        $downloader->download();

        return 0;
    }

    /**
     * Create the downloader instance.
     *
     * @param string        $destination The desired destination.
     * @param ConsoleLogger $logger      The logger to use.
     * @param Project       $project     The project.
     *
     * @throws InvalidArgumentException When the passed destination is invalid.
     */
    private function createDownloader(
        string $destination,
        ConsoleLogger $logger,
        Project $project
    ): AbstractResourceDownloader {
        $myProject = $this->getProject();
        switch ($destination) {
            case 'xliff':
                return new XliffResourceDownloader(
                    $project,
                    $myProject->getXliffDirectory(),
                    $myProject->getBaseLanguage(),
                    $logger
                );
            case 'contao':
                return new ContaoResourceDownloader(
                    $project,
                    $myProject->getContaoDirectory(),
                    $myProject->getBaseLanguage(),
                    $myProject->getPhpFileHeader(),
                    $logger
                );
            default:
        }

        throw new InvalidArgumentException(
            'Invalid download destination: ' . $destination . '. Must be xliff or contao'
        );
    }
}
