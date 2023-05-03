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

namespace CyberSpectrum\ContaoToolBox\Transifex\Download;

use CyberSpectrum\ContaoToolBox\Translation\Base\TranslationFileInterface;
use CyberSpectrum\ContaoToolBox\Translation\TranslationSync;
use CyberSpectrum\ContaoToolBox\Translation\Xliff\XliffFile;
use CyberSpectrum\PhpTransifex\Model\Resource;
use Psr\Log\LoggerInterface;

/**
 * This class synchronizes translations of a resource from transifex into the passed translation files.
 */
final class ResourceTranslationDownloader
{
    /**
     * Create a new instance.
     *
     * @param Resource                       $resource The resource to synchronize.
     * @param list<TranslationFileInterface> $files    The language files to process.
     * @param LoggerInterface                $logger   The logger to use.
     */
    public function __construct(
        private readonly Resource $resource,
        /** @var list<TranslationFileInterface> */
        private readonly array $files,
        private readonly LoggerInterface $logger
    ) {
    }

    /** Process the conversion. */
    public function process(): void
    {
        foreach ($this->files as $file) {
            $this->handleLanguage($file);
        }
    }

    /**
     * Handle a language file.
     *
     * @param TranslationFileInterface $file The file to process.
     */
    private function handleLanguage(TranslationFileInterface $file): void
    {
        $langCode    = $file->getLanguageCode();
        $translation = $this->resource->translations()->get($langCode);
        $this->logger->info(
            'Updating language <info>{langCode}</info> ({completed}% complete)',
            ['langCode' => $langCode, 'completed' => $translation->statistic()->getCompletedPercentage()]
        );
        $new = new XliffFile(null, $this->logger);
        $new->loadXML($translation->getContents());

        TranslationSync::syncFrom($new->setMode('target'), $file, false, $this->logger);
    }
}
