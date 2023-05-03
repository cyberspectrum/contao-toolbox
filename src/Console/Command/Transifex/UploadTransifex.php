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

use CyberSpectrum\ContaoToolBox\Transifex\Upload\AbstractResourceUploader;
use CyberSpectrum\ContaoToolBox\Transifex\Upload\ContaoResourceUploader;
use CyberSpectrum\ContaoToolBox\Transifex\Upload\XliffResourceUploader;
use CyberSpectrum\PhpTransifex\Model\Project;
use InvalidArgumentException;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Logger\ConsoleLogger;
use Symfony\Component\Console\Output\OutputInterface;

use function in_array;

/**
 * This class handles the uploading of language files to transifex.
 */
final class UploadTransifex extends TransifexBase
{
    protected function configure(): void
    {
        parent::configure();
        $this->setName('upload-transifex');
        $this->setDescription('Upload xliff translations to transifex.');

        $this->addOption(
            'source',
            null,
            InputOption::VALUE_OPTIONAL,
            'The upload source to use (either xliff or contao).',
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
        $uploader = $this->createUploader((string) $input->getOption('source'), new ConsoleLogger($output), $txProject);
        $uploader->setDomainPrefix($project->getPrefix());
        if ($skipFiles = $project->getSkipFiles()) {
            $uploader->setResourceFilter(fn (string $resourceSlug): bool => !in_array($resourceSlug, $skipFiles, true));
        }

        $uploader->upload();

        return 0;
    }

    /**
     * Create the downloader instance.
     *
     * @param string        $source  The desired source.
     * @param ConsoleLogger $logger  The logger to use.
     * @param Project       $project The project.
     *
     * @throws InvalidArgumentException When the passed destination is invalid.
     */
    private function createUploader(
        string $source,
        ConsoleLogger $logger,
        Project $project
    ): AbstractResourceUploader {
        $myProject = $this->getProject();
        switch ($source) {
            case 'xliff':
                return new XliffResourceUploader(
                    $project,
                    $myProject->getXliffDirectory(),
                    $myProject->getBaseLanguage(),
                    $logger
                );
            case 'contao':
                return new ContaoResourceUploader(
                    $project,
                    $myProject->getContaoDirectory(),
                    $myProject->getBaseLanguage(),
                    $logger
                );
            default:
        }

        throw new InvalidArgumentException(
            'Invalid upload source: ' . $source . '. Must be xliff or contao'
        );
    }
}
