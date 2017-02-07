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

use CyberSpectrum\Translation\Contao\ContaoFile;
use CyberSpectrum\Translation\Xliff\XliffFile;
use Symfony\Component\Console\Output\OutputInterface;

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
    protected function getLanguageBasePath()
    {
        return $this->project->getXliffDirectory();
    }

    /**
     * {@inheritDoc}
     */
    protected function getDestinationBasePath()
    {
        return $this->project->getContaoDirectory();
    }

    /**
     * {@inheritDoc}
     */
    protected function isValidSourceFile($file)
    {
        return (substr($file, -4) == '.xlf');
    }

    /**
     * {@inheritDoc}
     */
    protected function isValidDestinationFile($file)
    {
        return (substr($file, -4) == '.php');
    }

    /**
     * Convert the source file to the destination file.
     *
     * @param XLiffFile  $src The source XLIFF file.
     *
     * @param ContaoFile $dst The destination Contao file.
     *
     * @return bool
     */
    protected function convert(XLiffFile $src, ContaoFile $dst)
    {
        $changed = false;

        foreach ($src->getKeys() as $key) {
            if (($value = $src->getTarget($key)) !== null) {
                if ($dst->getValue($key) != $value) {
                    $changed = true;
                    $dst->setValue($key, $value);
                }
            } else {
                if ($dst->getValue($key) !== null) {
                    $changed = true;
                    $dst->removeValue($key);
                }
            }
        }

        return $changed;
    }

    /**
     * {@inheritDoc}
     *
     * @throws \InvalidArgumentException When an unexpected domain has been found in the xliff file.
     */
    protected function processLanguage(OutputInterface $output, $language)
    {
        $this->writeln($output, sprintf('processing language: <info>%s</info>...', $language));

        $destinationFiles = array();
        foreach ($this->baseFiles as $file) {
            $this->writelnVerbose($output, sprintf('processing file: <info>%s</info>...', $file));

            $srcFile = $this->getLanguageBasePath() . DIRECTORY_SEPARATOR . $language . DIRECTORY_SEPARATOR . $file;

            // not a file from transifex received yet.
            if (!file_exists($srcFile)) {
                continue;
            }

            $src = new XliffFile($srcFile);

            $domain = $src->getOriginal();

            if ($domain != basename($file, '.xlf')) {
                throw new \InvalidArgumentException(
                    sprintf(
                        'Unexpected domain "%s" found in file "%s" instead of domain "%s"',
                        $domain,
                        $srcFile,
                        basename($file, '.xlf')
                    )
                );
            }

            $dstFile            = $domain . '.php';
            $destinationFiles[] = $dstFile;

            $dstDir = $this->getDestinationBasePath() . DIRECTORY_SEPARATOR . $language;
            if (!is_dir($dstDir)) {
                mkdir($dstDir, 0755, true);
            }

            $dest = new ContaoFile($dstDir . DIRECTORY_SEPARATOR . $dstFile);

            $changed = $this->convert($src, $dest);

            if ($changed) {
                $dest->setLanguage($language);
                $dest->setTransifexProject($this->project->getProject());
                $dest->setLastChange($src->getDate());

                if ($dest->getKeys()) {
                    $dest->save();
                } else {
                    unlink($dstDir . DIRECTORY_SEPARATOR . $dstFile);
                    // @codingStandardsIgnoreStart - Catch the error when directory is not empty.
                    @rmdir($dstDir);
                    // @codingStandardsIgnoreEnd
                }
            }
        }

        $this->cleanupObsoleteFiles($output, $language, $destinationFiles);
    }
}
