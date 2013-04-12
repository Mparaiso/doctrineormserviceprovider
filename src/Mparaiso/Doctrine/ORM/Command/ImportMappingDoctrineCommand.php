<?php

/*
 * This file is part of the Doctrine Bundle
 *
 * The code was originally distributed inside the Symfony framework.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 * (c) Doctrine Project, Benjamin Eberlei <kontakt@beberlei.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Mparaiso\Doctrine\ORM\Command;

use Doctrine\ORM\Mapping\Driver\DatabaseDriver;
use Doctrine\ORM\Tools\Console\MetadataFilter;
use Doctrine\ORM\Tools\DisconnectedClassMetadataFactory;
use Doctrine\ORM\Tools\EntityGenerator;
use Doctrine\ORM\Tools\Export\ClassMetadataExporter;
use Symfony\Bridge\Doctrine\ManagerRegistry;
use Symfony\Component\Console\Command\Command as Comm;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Import Doctrine ORM metadata mapping information from an existing database.
 *
 * @author Fabien Potencier <fabien@symfony.com>
 * @author Jonathan H. Wage <jonwage@gmail.com>
 * 
 * modified by Mparaiso
 */
class ImportMappingDoctrineCommand extends Comm {

    /**
     * {@inheritDoc}
     */
    protected function configure() {
        $this
                ->setName('doctrine:mapping:import')
                ->addArgument('path', InputArgument::REQUIRED, 'The folder where to put mapping files')
                ->addArgument('mapping-type', InputArgument::OPTIONAL, 'The mapping type to export the imported mapping information to')
                ->addOption('em', null, InputOption::VALUE_OPTIONAL, 'The entity manager to use for this command')
                ->addOption('filter', null, InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY, 'A string pattern used to match entities that should be mapped.')
                ->addOption('force', null, InputOption::VALUE_NONE, 'Force to overwrite existing mapping files.')
                ->addOption("namespace", "ns", InputOption::VALUE_OPTIONAL, 'The entities namespace')
                ->setDescription('Imports mapping information from an existing database')
                ->setHelp(<<<EOT
The <info>doctrine:mapping:import</info> command imports mapping information
from an existing database:

<info>php app/console doctrine:mapping:import "MyCustomBundle" xml</info>

You can also optionally specify which entity manager to import from with the
<info>--em</info> option:

<info>php app/console doctrine:mapping:import "MyCustomBundle" xml --em=default</info>

If you don't want to map every entity that can be found in the database, use the
<info>--filter</info> option. It will try to match the targeted mapped entity with the
provided pattern string.

<info>php app/console doctrine:mapping:import "MyCustomBundle" xml --filter=MyMatchedEntity</info>

Use the <info>--force</info> option, if you want to override existing mapping files:

<info>php app/console doctrine:mapping:import "MyCustomBundle" xml --force</info>
EOT
        );
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
        //$bundle = $this->getApplication()->getKernel()->getBundle($input->getArgument('bundle'));
//        $destPath = $bundle->getPath();
        $destPath = $input->getArgument("path");
        $type = $input->getArgument('mapping-type') ? $input->getArgument('mapping-type') : 'xml';
        if ('annotation' === $type) {
            $destPath .="/".preg_replace("#\\\#", "/" , $input->getOption("namespace"));
        } else {
            $destPath .= '/Resources/doctrine';
        }
        if ('yaml' === $type) {
            $type = 'yml';
        }

        $cme = new ClassMetadataExporter();
        $exporter = $cme->getExporter($type);
        $exporter->setOverwriteExistingFiles($input->getOption('force'));

        if ('annotation' === $type) {
            $entityGenerator = $this->getEntityGenerator();
            $exporter->setEntityGenerator($entityGenerator);
        }

        //$em = $this->getEntityManager($input->getOption('em'));

        $databaseDriver = new DatabaseDriver($em->getConnection()->getSchemaManager());
        $em->getConfiguration()->setMetadataDriverImpl($databaseDriver);

        //$emName = $input->getOption('em');
//        $emName = $emName ? $emName : 'default';

        $cmf = new DisconnectedClassMetadataFactory();
        $cmf->setEntityManager($em);
        $metadata = $cmf->getAllMetadata();
        $metadata = MetadataFilter::filter($metadata, $input->getOption('filter'));
        if ($metadata) {
            $output->writeln(sprintf('Importing mapping information from "<info>%s</info>" entity manager', $emName));
            foreach ($metadata as $class) {
                $className = $class->name;
                $class->name = $input->getOption("namespace") . "\\" . $className;
                //$classPath = $class-> /* todo fix it */
                if ('annotation' === $type) {
                    $path = $destPath . '/' . $className . '.php';
                } else {
                    $path = $destPath . '/' . preg_replace('#\\\\#', ".", $class->name) . '.dcm.' . $type;
                }
                $output->writeln(sprintf('  > writing <comment>%s</comment>', $path));
                $code = $exporter->exportClassMetadata($class);
                if (!is_dir($dir = dirname($path))) {
                    mkdir($dir, 0777, true);
                }
                file_put_contents($path, $code);
            }
        } else {
            $output->writeln('Database does not have any mapping information.', 'ERROR');
            $output->writeln('', 'ERROR');
        }
    }
    
        protected function getEntityGenerator()
    {
        $entityGenerator = new EntityGenerator();
        $entityGenerator->setGenerateAnnotations(false);
        $entityGenerator->setGenerateStubMethods(true);
        $entityGenerator->setRegenerateEntityIfExists(false);
        $entityGenerator->setUpdateEntityIfExists(true);
        $entityGenerator->setNumSpaces(4);
        //$entityGenerator->setAnnotationPrefix('ORM\\');

        return $entityGenerator;
    }


}
