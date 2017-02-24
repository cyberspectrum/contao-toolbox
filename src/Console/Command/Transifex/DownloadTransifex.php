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

use CyberSpectrum\ContaoToolBox\Translation\TranslationSync;
use CyberSpectrum\ContaoToolBox\Translation\Xliff\XliffFile;
use CyberSpectrum\PhpTransifex\Model\ResourceModel;
use CyberSpectrum\PhpTransifex\PhpTransifex;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Logger\ConsoleLogger;
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

        $transifex = new PhpTransifex($this->getApi());
        $project   = $transifex->project($this->project->getProject());

        $resources = $project->resources();

        foreach ($resources->names() as $resourceName) {
            $this->handleResource($resources->get($resourceName), $translationMode, $allLanguages, $output);
        }
    }

    /**
     * Handle a single resource.
     *
     * @param ResourceModel   $resource        The resource to process.
     *
     * @param string          $translationMode The translation mode.
     *
     * @param bool            $allLanguages    Boolean flag if all languages shall be fetched.
     *
     * @param OutputInterface $output          The output interface to use.
     *
     * @return void
     */
    private function handleResource(
        ResourceModel $resource,
        $translationMode,
        $allLanguages,
        OutputInterface $output
    ) {
        $prefix = $this->project->getPrefix();
        if (substr($resource->slug(), 0, strlen($prefix)) != $prefix) {
            $this->writelnVerbose(
                $output,
                sprintf(
                    'Received resource <info>%s</info> is not for this repository, ignored.',
                    $resource->slug()
                )
            );
            return;
        }
        $this->writeln($output, sprintf('Processing resource <info>%s</info>', $resource->slug()));

        $this->writelnVerbose(
            $output,
            sprintf('Polling languages from resource <info>%s</info>', $resource->slug())
        );

        foreach ($resource->translations()->codes() as $code) {
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
     * @param ResourceModel   $resource        The resource to process.
     *
     * @param string          $code            The language code.
     *
     * @param string          $translationMode The translation mode.
     *
     * @param OutputInterface $output          The output interface to use.
     *
     * @return void
     */
    private function handleLanguage(ResourceModel $resource, $code, $translationMode, OutputInterface $output)
    {
        $translation = $resource->translations()->get($code);
        $this->writeln(
            $output,
            sprintf('Updating language <info>%s</info> (%s complete)', $code, $translation->statistic()->completed())
        );
        // Pull it.
        $data = $translation->contents($translationMode);
        if ($data) {
            $local  = $this->getLocalXliffFile($resource, $code);
            $logger = new ConsoleLogger($output);

            $new = new XliffFile(null, $logger);
            $new->loadXML($data);

            // Update all target values.
            TranslationSync::syncFrom($new->setMode('target'), $local->setMode('target'), false, $logger);
            // Update all source values.
            // TODO: refactor this to a real initialization from base values in getLocalXliffFile().
            TranslationSync::syncFrom($new->setMode('source'), $local->setMode('source'), true, $logger);

            if ($local->keys()) {
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

        return in_array($code, $this->project->getLanguages());
    }

    /**
     * Create a xliff instance for the passed resource.
     *
     * @param ResourceModel $resource     The resource.
     *
     * @param string        $languageCode The language code.
     *
     * @return XliffFile
     */
    private function getLocalXliffFile(ResourceModel $resource, $languageCode)
    {
        $domain    = substr($resource->slug(), strlen($this->project->getPrefix()));
        $localFile = $this->project->getXliffDirectory() . DIRECTORY_SEPARATOR .
            $languageCode . DIRECTORY_SEPARATOR .
            $domain . '.xlf';

        // FIXME: pass logger here.
        $local = new XliffFile($localFile);
        if (!file_exists($localFile)) {
            // Set base values.
            $local->setDataType('php');
            $local->setOriginal($domain);
            $local->setSrcLang($this->project->getBaseLanguage());
            $local->setTgtLang($languageCode);
        }

        return $local;
    }
}
