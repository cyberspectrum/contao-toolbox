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

use CyberSpectrum\ContaoToolBox\Transifex\Project;
use CyberSpectrum\ContaoToolBox\Transifex\TranslationResource;
use Symfony\Component\Console\Input\InputInterface;
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
    }

    /**
     * {@inheritDoc}
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        if (!($this->project->getProject() && $this->getApi())) {
            $this->writelnAlways($output, '<error>No project set or no API received, exiting.</error>');

            return;
        }

        $project = new Project($this->getApi());

        $project->setSlug($this->project->getProject());

        $resources = $project->getResources();

        $files = $this->getAllTxFiles($this->project->getBaseLanguage());

        $prefix = $this->project->getPrefix();
        foreach ($files as $file => $basename) {
            $noext = basename($basename, '.xlf');
            if (array_key_exists($prefix . $noext, $resources)) {
                // already present, update.
                $this->writeln($output, sprintf('Updating ressource <info>%s</info>', $prefix . $noext));
                /** @var TranslationResource $resource */
                $resource = $resources[$prefix . $noext];
                $resource->setContent(file_get_contents($file));
                $resource->updateContent();
            } else {
                $this->writeln($output, sprintf('Creating new ressource <info>%s</info>', $prefix . $noext));
                // upload new.
                $resource = new TranslationResource($this->getApi());
                $resource->setProject($this->project->getProject());
                $resource->setSlug($prefix . $noext);
                $resource->setName($resource->getSlug());
                $resource->setSourceLanguageCode($this->project->getBaseLanguage());

                $resource->setContent(file_get_contents($file));

                $resource->create();
            }
        }
    }
}
