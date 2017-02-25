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

use CyberSpectrum\ContaoToolBox\Transifex\Upload\XliffResourceUploader;
use CyberSpectrum\PhpTransifex\Model\ProjectModel;
use InvalidArgumentException;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Logger\ConsoleLogger;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * This class handles the uploading of language files to transifex.
 */
class UploadTransifex extends TransifexBase
{
    /**
     * {@inheritDoc}
     */
    protected function configure()
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
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $project = $this->getPhpTransifex()->project($this->project->getProject());

        $uploader = $this->createuploader($input->getOption('source'), new ConsoleLogger($output), $project);
        $uploader->setDomainPrefix($this->project->getPrefix());
        if ($skipFiles = $this->project->getSkipFiles()) {
            $uploader->setResourceFilter(function ($resourceSlug) use ($skipFiles) {
                return !in_array($resourceSlug, $skipFiles);
            });
        }

        $uploader->upload();
    }

    /**
     * Create the downloader instance.
     *
     * @param string        $source  The desired source.
     * @param ConsoleLogger $logger  The logger to use.
     * @param ProjectModel  $project The project.
     *
     * @return XliffResourceUploader
     *
     * @throws InvalidArgumentException When the passed destination is invalid.
     */
    private function createuploader($source, ConsoleLogger $logger, $project)
    {
        switch ($source) {
            case 'xliff':
                return new XliffResourceUploader(
                    $project,
                    $this->project->getXliffDirectory(),
                    $this->project->getBaseLanguage(),
                    $logger
                );
            default:
        }

        throw new InvalidArgumentException(
            'Invalid upload source: ' . $source . '. Must be xliff or contao'
        );
    }
}
