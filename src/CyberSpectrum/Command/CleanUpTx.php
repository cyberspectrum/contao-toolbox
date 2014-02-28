<?php

namespace CyberSpectrum\Command;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;

class CleanUpTx extends CommandBase
{
    protected function configure()
    {
        parent::configure();

        $this->setName('cleanuptx');
        $this->setDescription('Purges the defined .tx folder.');
        $this->setHelp('Purges the defined .tx folder. You can use this little helper command to quickly start from zero again.' . PHP_EOL);
    }

    protected function getLanguageBasePath()
    {
        return $this->txlang;
    }

    protected function isNotFileToSkip($basename)
    {
        return true;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $finder = new Finder();
        $fs = new Filesystem();
        $fs->remove($finder->directories()->in($this->getLanguageBasePath()));
    }
}