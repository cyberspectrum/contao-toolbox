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
use CyberSpectrum\PhpTransifex\Model\ResourceModel;
use Psr\Log\LoggerInterface;

/**
 * This class synchronizes translations of a resource from transifex into the passed translation files.
 */
class ResourceTranslationDownloader
{
    /**
     * The logger to use.
     *
     * @var LoggerInterface
     */
    private $logger;

    /**
     * The resource to synchronize.
     *
     * @var ResourceModel
     */
    private $resource;

    /**
     * The files to process.
     *
     * @var TranslationFileInterface[]
     */
    private $files;

    /**
     * The download mode (one of: default, reviewed, translator).
     *
     * @var string
     */
    private $translationMode = 'default';

    /**
     * Create a new instance.
     *
     * @param ResourceModel              $resource The resource to synchronize.
     * @param TranslationFileInterface[] $files    The language files.
     * @param LoggerInterface            $logger   The logger to use.
     */
    public function __construct(ResourceModel $resource, $files, LoggerInterface $logger)
    {
        $this->resource = $resource;
        $this->files    = $files;
        $this->logger   = $logger;
    }

    /**
     * Set translation mode.
     *
     * @param string $translationMode The new mode (one of: default, reviewed, translator).
     *
     * @return ResourceTranslationDownloader
     */
    public function setTranslationMode($translationMode)
    {
        $this->translationMode = (string) $translationMode;

        return $this;
    }

    /**
     * Process the conversion.
     *
     * @return void
     */
    public function process()
    {
        foreach ($this->files as $file) {
            $this->handleLanguage($file);
        }
    }

    /**
     * Handle a language file.
     *
     * @param TranslationFileInterface $file The file to process.
     *
     * @return void
     */
    private function handleLanguage(TranslationFileInterface $file)
    {
        $langCode    = $file->getLanguageCode();
        $translation = $this->resource->translations()->get($langCode);
        $this->logger->info(
            'Updating language <info>{langCode}</info> ({completed} complete)',
            ['langCode' => $langCode, 'completed' => $translation->statistic()->completed()]
        );
        $new = new XliffFile(null, $this->logger);
        $new->loadXML($translation->contents($this->translationMode));

        TranslationSync::syncFrom($new->setMode('target'), $file, false, $this->logger);
    }
}
