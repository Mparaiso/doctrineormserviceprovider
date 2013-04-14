<?php

namespace Mparaiso\Doctrine\ORM\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Mparaiso\Doctrine\ORM\FixtureLoader;

/**
 * Imports fixtures into a database
 * @author M.Paraiso
 */
class LoadFixturesCommand extends Command {

    /**
     * {@inheritDoc}
     */
    function configure() {
        $this
                ->setName('mp:doctrine:fixtures:import')
                ->addArgument('path', InputArgument::REQUIRED, 'The yaml fixture file path')
                ->addOption('entity-manager', "em", InputOption::VALUE_OPTIONAL, 'The entity manager to use for this command')
                ->setDescription('Imports fixtures into a database ')
                ->setHelp(<<<EOT
The <info>mp:doctrine:fixtures:import</info> command imports fixtures into a database 

<info>php app/console mp:doctrine:fixtures:import "folder/fixtures.yml" </info>

EOT
        );
    }

    /**
     * {@inheritDoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output) {
        $file_path = $input->getArgument('path');
        $app = $this->getHelper("app")->getApplication();
        $registry = $app['orm.manager_registry'];
        $emName = $input->getOption("entity-manager") ? $input->getOption("entity-manager") : "default";
        $em = $registry->getManager($emName);
        $loader = new FixtureLoader($file_path);
        $loader->parse();
        $loader->persistFixtures($em);
        $output->write("\n Fixtures from file $file_path haves been persisted to the database !!! \n");
    }

}

