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

namespace CyberSpectrum\ContaoToolBox\Console\Command\Convert;

use CyberSpectrum\ContaoToolBox\Converter\FromXliffToPhp;
use Psr\Log\LoggerInterface;

/**
 * This class converts language files from XLIFF format into the Contao PHP array format.
 */
class ConvertFromXliff extends ConvertBase
{
    /**
     * {@inheritDoc}
     */
    protected function configure()
    {
        parent::configure();

        $this->setName('from-xliff');
        $this->setDescription('Update Contao language files from xliff translations.');

        $this->setHelp('Convert the xliff files from the set transifex folder into the contao folder.' . PHP_EOL);
    }

    /**
     * {@inheritDoc}
     */
    protected function createConverter(LoggerInterface $logger)
    {
        return new FromXliffToPhp(
            $this->project->getPhpFileHeader(),
            $this->project->getContaoDirectory(),
            $this->project->getXliffDirectory(),
            $this->project->getBaseLanguage(),
            $logger
        );
    }
}
