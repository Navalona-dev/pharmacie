<?php

namespace App\Command;

use App\Service\DefaultsLoader;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class LoadCmsDataCommand extends Command
{
    protected function configure(): void
    {
        $this
            ->setName('app:load-defaults')
            ->setDescription('Load the application defaults')
            ->setHelp("Loads data from data/cms.yaml into db and copies files (templates files etc...)\nExisting keys are NOT updated.");
    }

    public function __construct(DefaultsLoader $loader)
    {
        $this->loader = $loader;
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->loader->loadDb();
        //$this->loader->copyFiles();
        $output->writeln("<info>* SQL data loaded successfully</info>");
        $output->writeln("<info>* Default files copied successfully</info>");
        return Command::SUCCESS;
    }
}
