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

namespace CyberSpectrum\Command;

use CyberSpectrum\Translation\Contao\ContaoFile as ContaoFile;
use CyberSpectrum\Translation\Xliff\XliffFile;
use Symfony\Component\Console\Output\OutputInterface;

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
    protected function getLanguageBasePath()
    {
        return $this->ctolang;
    }

    /**
     * {@inheritDoc}
     */
    protected function getDestinationBasePath()
    {
        return $this->txlang;
    }

    /**
     * {@inheritDoc}
     */
    protected function isValidSourceFile($file)
    {
        return (substr($file, -4) == '.php');
    }

    /**
     * {@inheritDoc}
     */
    protected function isValidDestinationFile($file)
    {
        return (substr($file, -4) == '.xlf');
    }

    /**
     * Convert the source file to the destination file.
     *
     * @param OutputInterface $output An OutputInterface instance.
     *
     * @param ContaoFile      $src    The source Contao file.
     *
     * @param XLiffFile       $dst    The destination XLIFF file.
     *
     * @param ContaoFile      $base   The base Contao file.
     *
     * @return void
     */
    protected function convert(OutputInterface $output, ContaoFile $src, XLiffFile $dst, ContaoFile $base)
    {
        $baseKeys = $base->getKeys();
        foreach ($baseKeys as $key) {
            if (!($basVal = $base->getValue($key))) {
                $dst->remove($key);
                continue;
            }
            $dst->setSource($key, $basVal);
            if (($value = $src->getValue($key)) !== null) {
                $dst->setTarget($key, $value);
            }
        }

        foreach ($dst->getKeys() as $key) {
            if (!in_array($key, $baseKeys)) {
                $this->writelnVerbose(
                    $output,
                    sprintf('Language key <info>%s</info> is not present in the source. Removing it.', $key)
                );
                $dst->remove($key);
            }
        }
    }

    /**
     * {@inheritDoc}
     */
    protected function processLanguage(OutputInterface $output, $language)
    {
        $this->writeln($output, sprintf('processing language: <info>%s</info>...', $language));

        $destinationFiles = array();
        foreach ($this->baseFiles as $file) {
            $this->writelnVerbose($output, sprintf('processing file: <info>%s</info>...', $file));

            $basFile = $this->getLanguageBasePath()
                . DIRECTORY_SEPARATOR . $this->baselanguage . DIRECTORY_SEPARATOR . $file;
            $srcFile = $this->getLanguageBasePath() . DIRECTORY_SEPARATOR . $language . DIRECTORY_SEPARATOR . $file;

            $domain = basename($file, '.php');

            $dstFile            = $domain . '.xlf';
            $destinationFiles[] = $dstFile;

            $src  = new ContaoFile($srcFile, ($output->getVerbosity() >= OutputInterface::VERBOSITY_DEBUG));
            $base = new ContaoFile($basFile, ($output->getVerbosity() >= OutputInterface::VERBOSITY_DEBUG));

            $dstDir = $this->getDestinationBasePath() . DIRECTORY_SEPARATOR . $language;
            if (!is_dir($dstDir)) {
                mkdir($dstDir, 0755, true);
            }

            $dest = new XliffFile($dstDir . DIRECTORY_SEPARATOR . $dstFile);
            $dest->setDataType('php');
            $dest->setSrcLang($this->baselanguage);
            $dest->setTgtLang($language);
            $dest->setOriginal($domain);
            if (file_exists($srcFile)) {
                $time = filemtime($srcFile);
            } else {
                $time = filemtime($basFile);
            }
            $dest->setDate($time);

            $this->convert($output, $src, $dest, $base);
            if (is_file($dstDir . DIRECTORY_SEPARATOR . $dstFile) || $dest->getKeys()) {
                $dest->save();
            }

            if ($output->getVerbosity() >= OutputInterface::VERBOSITY_DEBUG) {
                $output->writeln($src->getDebugMessages());
                $output->writeln($base->getDebugMessages());
            }
        }

        $this->cleanupObsoleteFiles($output, $language, $destinationFiles);
    }
}
