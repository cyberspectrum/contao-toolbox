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
 * @copyright  2013-2017 CyberSpectrum.
 * @license    https://github.com/cyberspectrum/contao-toolbox/blob/master/LICENSE LGPL-3.0
 * @filesource
 */

namespace CyberSpectrum\ContaoToolBox\Console\Command;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;

/**
 * This class provides a command to purge the local .tx folder.
 */
final class CleanUpTx extends CommandBase
{
    protected function configure(): void
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
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $finder     = new Finder();
        $filesystem = new Filesystem();
        $filesystem->remove($finder->directories()->in($this->getProject()->getXliffDirectory()));

        return 0;
    }
}
