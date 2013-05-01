<?php
namespace Mparaiso\Doctrine\ORM\Command;


use Symfony\Component\Console\Command\Command;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\EntityManager;
use Doctrine\Common\Persistence\AbstractManagerRegistry;
use Symfony\Component\Console\Helper\DialogHelper;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputInterface;

class DeleteEntityDataCommand extends Command
{
    protected function configure()
    {
        parent::configure();
        $this->setName("doctrine:delete-managed-datas")
            ->setDescription('Remove all managed entity datas from the database');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        parent::execute($input, $output);
        $input->setInteractive(TRUE);
        $dialog = $this->getHelper("dialog");
        /* @var $dialog DialogHelper */
        #@note @silex commande interactive
        if (!$dialog->askConfirmation($output, "Confirm database managed entities deletion(yes|no):")) {
            return 1;
        }
        $app = $this->getHelper("app");
        $registry = $app['orm.manager_registry'];
        /* @var $registry AbstractManagerRegistry */
        $managers = $registry->getManagers();
        foreach ($managers as $manager) {
            /* @var $manager EntityManager */
            $metadatas = $manager->getMetadataFactory()->getAllMetadata();
            foreach ($metadatas as $metadata) {
                /* @var $metadata ClassMetadata */
                $class = $metadata->getName();
                $entities = $manager->getRepository($class)->findAll();
                foreach ($entities as $entity) {
                    $manager->remove($entity);
                }
            }
            $manager->flush();
        }
    }

}
