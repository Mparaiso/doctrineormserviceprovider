<?php

namespace Mparaiso\Doctrine\ORM\Command;

use Doctrine\ORM\Mapping\Driver\DatabaseDriver;
use Doctrine\ORM\Tools\Console\MetadataFilter;
use Doctrine\ORM\Tools\DisconnectedClassMetadataFactory;
use Doctrine\ORM\Tools\EntityGenerator;
use Doctrine\ORM\Tools\Export\ClassMetadataExporter;
use Symfony\Bridge\Doctrine\ManagerRegistry;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class LoadFixturesCommand extends Command {

    /**
     * {@inheritDoc}
     */
    protected function configure() {
        $this
                ->setName('doctrine:load-fixtures')
                ->addArgument('path', InputArgument::REQUIRED, 'The fixture filepath.')
                ->addOption('em', null, InputOption::VALUE_OPTIONAL, 'The entity manager to use for this command.')
                ->setDescription('Load a yaml file fixture to the database.');
    }

    /**
     * {@inheritDoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output) {
        $app = $this->getHelper("app")->getApplication();
        $registry = $app['orm.manager_registry'];
        /* @var $registry ManagerRegistry */
        $emName = $input->getOption("em") ? $input->getOption("em") : "default";
        $em = $registry->getManager($emName);
        $filepath = $input->getArgument("path");
        $loader = new \Mparaiso\Doctrine\ORM\FixtureLoader($filepath);
        $entities = $loader->parse();
        $loader->persistFixtures($em);
        $output->writeLn(count($entities)." fixtures loaded into the database successfully !");
    }

}