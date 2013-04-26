Doctrine ORM Service Provider
=============================

[![Build Status](https://travis-ci.org/Mparaiso/doctrineormserviceprovider.png?branch=master)](https://travis-ci.org/Mparaiso/doctrineormserviceprovider)

Doctrine ORM silex extension
----------------------------

author : M.Paraiso
contact: mparaiso@online.fr

status: work in progress

helps use Doctrine ORM with silex

##### Configuration

here is a configuration exemple for silex:

    $app->register(new ConsoleServiceProvider); // to manage entities through command line.
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

Services :

+ orm.em : EntityManager
+ orm.manager_registry  : Manager registry

## FEATURES

### Date Function

exemple : `select j from Job j where j.createdAt = date("2008-12-26")`

### FixtureLoader

load  fixtures from a yaml file

#### Usage

given the following yaml file : 


/*
```yaml

## fixture example :

# !!!! all references must be declared BEFORE used !!!!!
 
# the root node
fixtures: 
  # a fixture      
  - entity: Entity\Rdv\Rsvp 
    # the class
    name: jacob
    # the fixture reference name (optional)
    fields:
      # the fixture properties
      attendeeName: jacob
      

  - entity: Entity\Rdv\Dinner
    name: 'German dinner'
    fields:
      country: Germany
      latitude: 10
      longitude: 23
      contactPhone: 911-343-333
      address: Berlin
      description: Sausages
      title: Kraut party
      hostedBy: Von Brohm
      eventDate: { datetime: 2013-10-20 }
      # will be parsed as a DateTime object
      rsvps: [ jacob ]
      # will be parsed as an ArrayCollection , fixtures are references by their names
      # references must be declared BEFORE they are USED !!!!

  - entity: Entity\Rdv\Rsvp
    name: jean
    fields:
      attendeeName: jean
      dinner: %German dinner% 
      # a reference to another fixture  , with its name surrounded by quotes
```

```php
        $em = app['orm.em'] // given a EntityManager
        $loader = new Mparaiso\Doctrine\ORM\FixtureLoader(__DIR__ . '/fixtures/dinners.yml');
        // get entities from fixtures
        $entities = $loader->parse();
        // persist fixtures
        $loader->persistFixtures($em);
        // remove fixtures
        $loader->removeFixtures($em);
```
    
    
    
 