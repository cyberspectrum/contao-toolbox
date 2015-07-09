<?php

/**
 * This toolbox provides easy ways to generate .xlf (XLIFF) files from Contao language files, push them to transifex
 * and pull translations from transifex and convert them back to Contao language files.
 *
 * @package      cyberspectrum/contao-toolbox
 * @author       Christian Schiffler <c.schiffler@cyberspectrum.de>
 * @author       Yanick Witschi <yanick.witschi@terminal42.ch>
 * @copyright    CyberSpectrum
 * @license      LGPL-3.0+.
 * @filesource
 */

namespace CyberSpectrum\Command;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;

/**
 * This class provides a command to purge the local .tx folder.
 */
class CleanUpTx extends CommandBase
{
    /**
     * {@inheritDoc}
     */
    protected function configure()
    {
        parent::configure();

        $this->setName('cleanup-tx');
        $this->setDescription('Purges the defined .tx folder.');
        $this->setHelp(
            'Purges the defined .tx folder. ' .
            'You can use this little helper command to quickly start from zero again.' . PHP_EOL
        );
    }

    /**
     * {@inheritDoc}
     */
    protected function getLanguageBasePath()
    {
        return $this->txlang;
    }

    /**
     * {@inheritDoc}
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    protected function isNotFileToSkip($basename)
    {
        return true;
    }

    /**
     * {@inheritDoc}
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $finder     = new Finder();
        $filesystem = new Filesystem();
        $filesystem->remove($finder->directories()->in($this->getLanguageBasePath()));
    }
}
