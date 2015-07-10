<?php

/**
 * This toolbox provides easy ways to generate .xlf (XLIFF) files from Contao language files, push them to transifex
 * and pull translations from transifex and convert them back to Contao language files.
 *
 * @package      cyberspectrum/contao-toolbox
 * @author       Christian Schiffler <c.schiffler@cyberspectrum.de>
 * @copyright    CyberSpectrum
 * @license      LGPL-3.0+.
 * @filesource
 */

namespace CyberSpectrum\Command\Transifex;

use CyberSpectrum\Transifex\Project;
use CyberSpectrum\Transifex\TranslationResource;
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
        if (!($this->project && $this->getApi())) {
            $this->writelnAlways($output, '<error>No project set or no API received, exiting.</error>');

            return;
        }

        $project = new Project($this->getApi());

        $project->setSlug($this->project);

        $resources = $project->getResources();

        $files = $this->getAllTxFiles($this->baselanguage);

        foreach ($files as $file => $basename) {
            $noext = basename($basename, '.xlf');
            if (array_key_exists($this->prefix . $noext, $resources)) {
                // already present, update.
                $this->writeln($output, sprintf('Updating ressource <info>%s</info>', $this->prefix . $noext));
                /** @var \CyberSpectrum\Transifex\TranslationResource $resource */
                $resource = $resources[$this->prefix . $noext];
                $resource->setContent(file_get_contents($file));
                $resource->updateContent();
            } else {
                $this->writeln($output, sprintf('Creating new ressource <info>%s</info>', $this->prefix . $noext));
                // upload new.
                $resource = new TranslationResource($this->getApi());
                $resource->setProject($this->project);
                $resource->setSlug($this->prefix . $noext);
                $resource->setName($resource->getSlug());
                $resource->setSourceLanguageCode($this->baselanguage);

                $resource->setContent(file_get_contents($file));

                $resource->create();
            }
        }
    }
}
