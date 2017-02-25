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

use CyberSpectrum\ContaoToolBox\Converter\FromPhpToXliff;
use Psr\Log\LoggerInterface;

/**
 * This class converts Contao language files to XLIFF format.
 */
class ConvertToXliff extends ConvertBase
{
    /**
     * {@inheritDoc}
     */
    protected function configure()
    {
        parent::configure();

        $this->setName('to-xliff');
        $this->setDescription('Update xliff translations from Contao base language.');

        $this->setHelp('Convert the base language from the contao folder into files in transifex folder' . PHP_EOL);
    }

    /**
     * {@inheritDoc}
     */
    protected function createConverter(LoggerInterface $logger)
    {
        return new FromPhpToXliff(
            $this->project->getContaoDirectory(),
            $this->project->getXliffDirectory(),
            $this->project->getBaseLanguage(),
            $logger
        );
    }
}
