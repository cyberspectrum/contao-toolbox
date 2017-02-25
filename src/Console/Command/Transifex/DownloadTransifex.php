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

use CyberSpectrum\ContaoToolBox\Transifex\Download\ContaoResourceDownloader;
use CyberSpectrum\ContaoToolBox\Transifex\Download\XliffResourceDownloader;
use CyberSpectrum\PhpTransifex\Model\ProjectModel;
use CyberSpectrum\PhpTransifex\PhpTransifex;
use InvalidArgumentException;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Logger\ConsoleLogger;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * This command is used for downloading translations from transifex.
 */
class DownloadTransifex extends TransifexBase
{
    /**
     * {@inheritDoc}
     */
    protected function configure()
    {
        parent::configure();
        $this->setName('download-transifex');
        $this->setDescription('Download xliff translations from transifex.');

        $this->addOption(
            'mode',
            'm',
            InputOption::VALUE_OPTIONAL,
            'Download mode to use (reviewed, translated, default).',
            'reviewed'
        );

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
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $transifex  = new PhpTransifex($this->getApi());
        $project    = $transifex->project($this->project->getProject());
        $downloader = $this->createDownloader($input->getOption('destination'), new ConsoleLogger($output), $project);

        if ('all' !== ($languages = $input->getArgument('languages'))) {
            $downloader->setAllowedLanguages(explode(',', $languages));
        }
        $downloader->setDomainPrefix($this->project->getPrefix());
        $downloader->setTranslationMode($this->getTranslationMode($input));
        if ($skipFiles = $this->project->getSkipFiles()) {
            $downloader->setResourceFilter(function ($resourceSlug) use ($skipFiles) {
                return !in_array($resourceSlug, $skipFiles);
            });
        }

        $downloader->download();
    }

    /**
     * Obtain the translation mode to use when downloading.
     *
     * @param InputInterface $input An InputInterface instance.
     *
     * @return string
     *
     * @throws \InvalidArgumentException When the translation mode is not reviewed, translated or default.
     */
    private function getTranslationMode(InputInterface $input)
    {
        $translationMode = $input->getOption('mode');
        $validModes      = ['reviewed', 'translated', 'default'];
        if (!in_array($translationMode, $validModes)) {
            throw new \InvalidArgumentException(
                sprintf(
                    'Invalid translation mode %s specified. Must be one of %s',
                    $translationMode,
                    implode(', ', $validModes)
                )
            );
        }

        // HOTFIX: translated actually appears to be "translator".
        if ($translationMode == 'translated') {
            $translationMode = 'translator';
        }

        return $translationMode;
    }

    /**
     * Create the downloader instance.
     *
     * @param string        $destination The desired destination.
     * @param ConsoleLogger $logger      The logger to use.
     * @param ProjectModel  $project     The project.
     *
     * @return ContaoResourceDownloader|XliffResourceDownloader
     *
     * @throws InvalidArgumentException When the passed destination is invalid.
     */
    private function createDownloader($destination, ConsoleLogger $logger, $project)
    {

        switch ($destination) {
            case 'xliff':
                return new XliffResourceDownloader(
                    $project,
                    $this->project->getXliffDirectory(),
                    $this->project->getBaseLanguage(),
                    $logger
                );
            case 'contao':
                return new ContaoResourceDownloader(
                    $project,
                    $this->project->getContaoDirectory(),
                    $this->project->getBaseLanguage(),
                    $logger
                );
            default:
        }

        throw new InvalidArgumentException(
            'Invalid download destination: ' . $destination . '. Must be xliff or contao'
        );
    }
}
