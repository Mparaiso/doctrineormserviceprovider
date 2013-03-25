<?php

namespace Mparaiso\Provider;

use Silex\ServiceProviderInterface;
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

class DoctrineORMServiceProvider implements ServiceProviderInterface
{

    public function boot(Application $app)
    {

    }

    function getDriver($type, array $paths)
    {
        $driver = NULL;
        switch ($type) {
            case 'yaml':
                $driver = new YamlDriver($paths);
                break;
            case 'xml':
                $driver = new XmlDriver($paths);
                break;
            default :
                $driver = new AnnotationDriver();
        }
        return $driver;
    }

    public function register(Application $app)
    {
        $self                               = $this;
        $app["orm.proxy_dir"]               = NULL;
        $app["orm.cache"]                   = NULL;
        $app['drm.default_manager_name']    = "default";
        $app['orm.default_connection_name'] = "default";
        $app["orm.managers"]                = array();
        $app["orm.connections"]             = array();
        $app["orm.is_dev_mode"]             = $app->share(function ($app) {
            return $app["debug"];
        });
        $app["orm.driver.configs"]          = array();
        $app["orm.chain_driver"]            = $app->share(function () {
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
            return $config;
        });
        /**
         * EN : create the entity manager
         * FR : créer l'entity manager
         */
        $app["orm.em"] = $app->share(function ($app) use ($self) {
            /* @var $chain \Doctrine\Common\Persistence\Mapping\Driver\MappingDriverChain */
            $chain = $app["orm.chain_driver"];
            foreach ($app["orm.driver.configs"] as $key => $config) {
                if ($key = "default") {
                    $chain->setDefaultDriver($self->getDriver($config['type'], $config['paths']));
                } else {
                    $chain->addDriver($self->getDriver($config['type'], $config['paths']), $key);

                }
            }
            if (!isset($app["orm.connection"]) && $app["db"]) {
                $app["orm.connection"] = $app["db"];
            }
            $em = EntityManager::create($app["orm.connection"], $app["orm.config"]);
            if (isset($app["orm.logger"])) {
                $em->getConfiguration()->setSQLLogger($app["orm.logger"]);
            }
            if (isset($app["console"])) {
                $app->on(ConsoleServiceProvider::INIT, function () use ($app, $em) {
                    /* @var $console \Symfony\Component\Console\Application */
                    $console = $app["console"];
                    $console->getHelperSet()->set(new EntityManagerHelper($em), "em");
                    $console->getHelperSet()->set(new ConnectionHelper($em->getConnection()), "db");
                    ConsoleRunner::addCommands($console);
                });
            }
            return $em;
        });
    }

}
