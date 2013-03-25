<?php

namespace Mparaiso\Provider;

use Silex\ServiceProviderInterface;
use Mparaiso\Doctrine\ORM\DoctrineManagerRegistry;
use Doctrine\ORM\Configuration;
use Doctrine\ORM\Tools\Console\ConsoleRunner;
use Doctrine\DBAL\Tools\Console\Helper\ConnectionHelper;
use Doctrine\ORM\Tools\Console\Helper\EntityManagerHelper;
use Doctrine\ORM\Mapping\Driver\AnnotationDriver;
use Doctrine\ORM\Mapping\Driver\XmlDriver;
use Doctrine\ORM\Mapping\Driver\YamlDriver;
use Symfony\Component\Console\Application as ConsoleApplication;
use Doctrine\Common\Persistence\Mapping\Driver\MappingDriverChain;
use Silex\Application;
use Doctrine\ORM\Tools\Setup;
use Doctrine\ORM\EntityManager;
use Mparaiso\Provider\ConsoleServiceProvider;

/**
 * @see https://github.com/mpmedia/dflydev-doctrine-orm-service-provider/blob/master/src/Dflydev/Pimple/Provider/DoctrineOrm/DoctrineOrmServiceProvider.php
 */
class DoctrineORMServiceProvider implements ServiceProviderInterface
{

    public function boot(Application $app)
    {

    }

    function getDriver($type, array $paths, Configuration $config)
    {
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

    public function register(Application $app)
    {
        $self = $this;
        $app["orm.proxy_dir"]    = NULL;
        $app["orm.cache"]        = NULL;
        $app["orm.is_dev_mode"]  = $app["debug"];
        $app["orm.chain_driver"] = $app->share(function () {
            return new MappingDriverChain();
        });
        /**
         * EN : create entity manager config
         * FR : creer la configuration de l'entity mananger
         */
        $app["orm.config"] = $app->share(function ($app) {
            $config = Setup::createConfiguration($app["orm.is_dev_mode"],
                $app["orm.proxy_dir"],
                $app["orm.cache"]);
            $config->setMetadataDriverImpl($app["orm.chain_driver"]);
            if (isset($app["orm.logger"])) {
                $config->setSQLLogger($app["orm.logger"]);
            }
            return $config;
        });
        /**
         * EN : create the entity manager
         * FR : créer l'entity manager
         */
        $app["orm.em"] = $app->share(function ($app)use($self) {
            foreach ($app["orm.driver.configs"] as $key => $config) {
                if ($key == "default") {
                    $app["orm.chain_driver"]->setDefaultDriver($self->getDriver($config['type'], $config['paths'], $app["orm.config"]));
                }
                $app["orm.chain_driver"]->addDriver($self->getDriver($config['type'], $config['paths'], $app["orm.config"]), $config["namespace"]);
            }
            if (!isset($app["orm.connection"]) && $app["db"]) {
                $app["orm.connection"] = $app["db"];
            }
            $em = EntityManager::create($app["orm.connection"], $app["orm.config"]);

            if (isset($app["console"])) {
                /* @var $console \Symfony\Component\Console\Application */
                $console = $app["console"];
                $console->getHelperSet()->set(new EntityManagerHelper($em), "em");
                $console->getHelperSet()->set(new ConnectionHelper($em->getConnection()), "db");
                ConsoleRunner::addCommands($app["console"]);
            }
            return $em;
        });

        $app['orm.manager_registry'] = $app->share(function ($app) {
            return new DoctrineManagerRegistry(array("default" => $app['orm.em']),
                array("default" => $app['orm.em']->getConnection()));
        });

    }


}
