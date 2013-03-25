Doctrine ORM Service Provider
=============================

Doctrine ORM silex extension
----------------------------

author : M.Paraiso
contact: mparaiso@online.fr

status: work in progress

helps use Doctrine ORM with silex

##### Configuration

here is a configuration exemple for silex:

    $app->register(new DoctrineServiceProvider,array(
            "db.options"=> array(
                "dbname"   =>  getenv("DBNAME"),
                "user"     => getenv("USER"),
                "password" => getenv("PASSWORD"),
                "host"     => getenv("_HOST"),
                "driver"   => "pdo_mysql",
    )));

    $app->register(new DoctrineORMServiceProvider, array(
           "orm.driver.configs"    => array(
               "default" => array(
                   "namespace"=>"Entity",  // rootnamespace of your entities
                   "type"  => "yaml", // driver type (yaml,xml,annotation)
                   "paths" => array(__DIR__ . '/doctrine'), // config file path
               )
           )
       ));
