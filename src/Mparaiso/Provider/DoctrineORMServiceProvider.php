<?php

namespace Mparaiso\Provider;

use Silex\ServiceProviderInterface;
use Symfony\Component\Security\Core\Validator\Constraints\UserPasswordValidator;
use Exception;
use Mparaiso\Doctrine\ORM\DoctrineManagerRegistry;
use Doctrine\ORM\Configuration;
use Doctrine\ORM\Tools\Console\ConsoleRunner;
use Doctrine\DBAL\Tools\Console\Helper\ConnectionHelper;
use Doctrine\ORM\Tools\Console\Helper\EntityManagerHelper;
use Doctrine\ORM\Mapping\Driver\XmlDriver;
use Doctrine\ORM\Mapping\Driver\YamlDriver;
use Doctrine\Common\Persistence\Mapping\Driver\MappingDriverChain;
use Silex\Application;
use Doctrine\ORM\Tools\Setup;
use Doctrine\ORM\EntityManager;
use Symfony\Bridge\Doctrine\Form\DoctrineOrmExtension;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntityValidator;
use Mparaiso\Doctrine\ORM\Command\ImportMappingDoctrineCommand;
use Mparaiso\Doctrine\ORM\Command\LoadFixturesCommand;

/**
 * @see https://github.com/mpmedia/dflydev-doctrine-orm-service-provider/blob/master/src/Dflydev/Pimple/Provider/DoctrineOrm/DoctrineOrmServiceProvider.php
 */
class DoctrineORMServiceProvider implements ServiceProviderInterface {

    function getDriver($type, array $paths, Configuration $config) {
        $driver = NULL;
        switch ($type) {
            case 'yaml':
                $driver = new YamlDriver($paths);
                break;
            case 'xml':
                $driver = new XmlDriver($paths);
                break;
            case 'annotation' :
                $driver = $config->newDefaultAnnotationDriver($paths, TRUE);
        }
        return $driver;
    }

    public function register(Application $app) {
        $self = $this;
        $app["orm.proxy_dir"] = NULL;
        $app["orm.cache"] = NULL;
        $app["orm.is_dev_mode"] = $app["debug"];
        $app['orm.driver.configs'] = array();
        $app["orm.chain_driver"] = $app->share(function () {
            return new MappingDriverChain();
        });
        /**
         * EN : create entity manager config
         * FR : creer la configuration de l'entity mananger
         */
        $app["orm.config"] = $app->share(function ($app) {
            $config = Setup::createConfiguration($app["orm.is_dev_mode"], $app["orm.proxy_dir"], $app["orm.cache"]);
            $config->setMetadataDriverImpl($app["orm.chain_driver"]);
            if (isset($app["orm.logger"])) {
                $config->setSQLLogger($app["orm.logger"]);
            }
            $config->addCustomDatetimeFunction("DATE", 'Mparaiso\Doctrine\ORM\Functions\Date');
            return $config;
        });
        /**
         * EN : create the entity manager
         * FR : créer l'entity manager
         */
        $app["orm.em"] = $app->share(function ($app) use ($self) {
            foreach ($app["orm.driver.configs"] as $key => $config) {
                if (!is_array($config['paths']))
                    throw new Exception(' $config["paths"] must be an array of paths ');
                if ($key == "default") {
                    $app["orm.chain_driver"]->setDefaultDriver($self->getDriver($config['type'], $config['paths'], $app["orm.config"]));
                }
                $app["orm.chain_driver"]->addDriver($self->getDriver($config['type'], $config['paths'], $app["orm.config"]), $config["namespace"]);
            }
            if (!isset($app["orm.connection"]) && $app["db"]) {
                $app["orm.connection"] = $app["db"];
            }
            $em = EntityManager::create($app["orm.connection"], $app["orm.config"]);

            return $em;
        });

        $app['orm.manager_registry'] = $app->share(function ($app) {
            return new DoctrineManagerRegistry("manager_registry", array("default" => $app['orm.em']->getConnection()), array("default" => $app['orm.em']));
        });

        /* call this to install Doctrine orm's commands $app['orm.console.boot_commands']() */
        $app['orm.console.boot_commands'] = $app->protect(function () use ($app) {
            if (isset($app["console"])) {
                $em = $app['orm.em'];
                /* @var $console \Symfony\Component\Console\Application */
                $console = $app["console"];
                $console->getHelperSet()->set(new EntityManagerHelper($em), "em");
                $console->getHelperSet()->set(new ConnectionHelper($em->getConnection()), "db");
                ConsoleRunner::addCommands($app["console"]);
                $console->add(new ImportMappingDoctrineCommand);
                $console->add(new LoadFixturesCommand);
            }
        });
    }

    public function boot(Application $app) {
        /* intégration des extensions de formulaire doctrine */
        if (isset($app["form.extensions"])) {
            $app["form.extensions"] = $app->share(
                    #@note @silex utiliser les extensions de formulaire de doctrine
                    $app->extend("form.extensions", function ($extensions, $app) {
                        $extensions[] = new DoctrineOrmExtension($app["orm.manager_registry"]);
                        return $extensions;
                    }
            ));
        }

        /* validator services  */
        if (!$app->offsetExists('validator.validator_service_ids')) {
            $app['validator.validator_service_ids'] = array();
        }
        $a = $app['validator.validator_service_ids'];
        $a["doctrine.orm.validator.unique"] = "validator.unique_entity";
        $a["security.validator.user_password"] = "security.validator.user_password";
        $app['validator.validator_service_ids'] = $a;

        $app["validator.unique_entity"] = function ($app) {
            return new UniqueEntityValidator($app["orm.manager_registry"]);
        };
        $app['security.validator.user_password'] = function ($app) {
            return new UserPasswordValidator(
                    $app["security"], $app['security.encoder_factory']);
        };
    }

}
