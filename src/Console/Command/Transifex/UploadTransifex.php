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

use CyberSpectrum\PhpTransifex\PhpTransifex;
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

        $transifex = new PhpTransifex($this->getApi());
        $project   = $transifex->project($this->project->getProject());

        $resources = $project->resources();

        $files = $this->getAllTxFiles($this->project->getBaseLanguage());

        $prefix = $this->project->getPrefix();
        foreach ($files as $file => $basename) {
            $noext = basename($basename, '.xlf');
            if ($resources->has($prefix . $noext)) {
                // already present, update.
                $this->writeln($output, sprintf('Updating ressource <info>%s</info>', $prefix . $noext));
                $resource = $resources->get($prefix . $noext);
            } else {
                $this->writeln($output, sprintf('Creating new ressource <info>%s</info>', $prefix . $noext));
                // upload new.
                $resource = $resources->add($prefix . $noext, $prefix . $noext, 'XLIFF');
            }
            $resource->setContent(file_get_contents($file));
        }

        $this->writeln($output, 'Saving all...');
        $project->save();
    }
}
