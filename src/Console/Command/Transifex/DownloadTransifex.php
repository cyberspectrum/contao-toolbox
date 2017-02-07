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

use CyberSpectrum\ContaoToolBox\Transifex\Project;
use CyberSpectrum\ContaoToolBox\Transifex\TranslationResource;
use CyberSpectrum\ContaoToolBox\Translation\Xliff\XliffFile;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * This command is used for downloading translations from transifex.
 */
class DownloadTransifex extends TransifexBase
{
    /**
     * {@inheritDoc}
     */
    protected function configure()
    {
        parent::configure();
        $this->setName('download-transifex');
        $this->setDescription('Download xliff translations from transifex.');

        $this->addOption(
            'mode',
            'm',
            InputOption::VALUE_OPTIONAL,
            'Download mode to use (reviewed, translated, default).',
            'reviewed'
        );
    }

    /**
     * {@inheritDoc}
     *
     * @throws \InvalidArgumentException When the translation mode is not reviewed, translated or default.
     */
    protected function initialize(InputInterface $input, OutputInterface $output)
    {

        parent::initialize($input, $output);

        $translationMode = $input->getOption('mode');
        $validModes      = array(
            'reviewed',
            'translated',
            'default'
        );
        if (!in_array($translationMode, $validModes)) {
            throw new \InvalidArgumentException(
                sprintf(
                    'Invalid translation mode %s specified. Must be one of %s',
                    $translationMode,
                    implode(', ', $validModes)
                )
            );
        }
    }

    /**
     * {@inheritDoc}
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $translationMode = $input->getOption('mode');

        // HOTFIX: translated actually appears to be "translator".
        if ($translationMode == 'translated') {
            $translationMode = 'translator';
        }

        $allLanguages = ($input->getArgument('languages') == 'all');

        $project = new Project($this->getApi());

        $project->setSlug($this->project->getProject());

        $resources = $project->getResources();

        foreach ($resources as $resource) {
            $this->handleResource($resource, $translationMode, $allLanguages, $output);
        }
    }

    /**
     * Handle a single resource.
     *
     * @param TranslationResource $resource        The resource to process.
     *
     * @param string              $translationMode The translation mode.
     *
     * @param bool                $allLanguages    Boolean flag if all languages shall be fetched.
     *
     * @param OutputInterface     $output          The output interface to use.
     *
     * @return void
     */
    private function handleResource(
        TranslationResource $resource,
        $translationMode,
        $allLanguages,
        OutputInterface $output
    ) {
        $prefix = $this->project->getPrefix();
        if (substr($resource->getSlug(), 0, strlen($prefix)) != $prefix) {
            $this->writelnVerbose(
                $output,
                sprintf(
                    'Received resource <info>%s</info> is not for this repository, ignored.',
                    $resource->getSlug()
                )
            );
            return;
        }
        $this->writeln($output, sprintf('Processing resource <info>%s</info>', $resource->getSlug()));

        $this->writelnVerbose(
            $output,
            sprintf('Polling languages from resource <info>%s</info>', $resource->getSlug())
        );
        $resource->fetchDetails();

        foreach (array_keys($resource->getAvailableLanguages()) as $code) {
            // We are using 2char iso 639-1 in Contao - what a pity.
            if (!$this->isHandlingLanguage($code, $allLanguages)) {
                $this->writelnVerbose($output, sprintf('skipping language <info>%s</info>', $code));
                continue;
            }

            $this->handleLanguage($resource, $code, $translationMode, $output);
        }
    }

    /**
     * Handle a language for a resource.
     *
     * @param TranslationResource $resource        The resource to process.
     *
     * @param string              $code            The language code.
     *
     * @param string              $translationMode The translation mode.
     *
     * @param OutputInterface     $output          The output interface to use.
     *
     * @return void
     */
    private function handleLanguage(TranslationResource $resource, $code, $translationMode, OutputInterface $output)
    {
        $this->writeln($output, sprintf('Updating language <info>%s</info>', $code));
        // Pull it.
        $data = $resource->fetchTranslation($code, $translationMode);
        if ($data) {
            $local = $this->getLocalXliffFile($resource, $code);

            $new = new XliffFile(null);
            $new->loadXML($data);

            foreach ($new->getKeys() as $key) {
                if ($value = $new->getSource($key)) {
                    $local->setSource($key, $value);
                    if ($value = $new->getTarget($key)) {
                        $local->setTarget($key, $value);
                    }
                }
            }
            foreach (array_diff($new->getKeys(), $local->getKeys()) as $key) {
                $this->writeln(
                    $output,
                    sprintf('Language key <info>%s</info> seems to be orphaned, please check.', $key)
                );
            }

            if ($local->getKeys()) {
                if (!is_dir(dirname($local->getFileName()))) {
                    mkdir(dirname($local->getFileName()), 0755, true);
                }
                $local->save();
            }
        }
    }

    /**
     * Check if we want to handle the given language code.
     *
     * @param string $code         The language code.
     *
     * @param bool   $allLanguages Flag telling if all languages shall be handled.
     *
     * @return bool
     */
    private function isHandlingLanguage($code, $allLanguages)
    {
        if ($code == $this->project->getBaseLanguage()) {
            return false;
        }

        if ($allLanguages) {
            return true;
        }

        return in_array(substr($code, 0, 2), $this->project->getLanguages());
    }

    /**
     * Create a xliff instance for the passed resource.
     *
     * @param TranslationResource $resource     The resource.
     *
     * @param string              $languageCode The language code.
     *
     * @return XliffFile
     */
    private function getLocalXliffFile(TranslationResource $resource, $languageCode)
    {
        $domain    = substr($resource->getSlug(), strlen($this->project->getPrefix()));
        $localFile = $this->project->getXliffDirectory() . DIRECTORY_SEPARATOR .
            substr($languageCode, 0, 2) . DIRECTORY_SEPARATOR .
            $domain . '.xlf';

        $local = new XliffFile($localFile);
        if (!file_exists($localFile)) {
            // Set base values.
            $local->setDataType('php');
            $local->setOriginal($domain);
            $local->setSrcLang($this->project->getBaseLanguage());
            $local->setTgtLang(substr($languageCode, 0, 2));
        }

        return $local;
    }
}
