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
    $app->register(new DoctrineORMServiceProvider(), array(
        "em.logger" => function($app) { /** optional use a logger to log requests **/
            return new MonologSQLLogger($app["logger"]);
        },
        "em.metadata" => array(
            "type" => "annotation",
            "path" => array(__DIR__ ."/Ribbit/Entity/"),
            ),
        "em.proxy_dir" => dirname(__DIR__)."/cache",
        "em.is_dev_mode" => $app["debug"]
        ));
