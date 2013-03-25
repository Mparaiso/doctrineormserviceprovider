<?php

namespace Mparaiso\Provider;

use PHPUnit_Framework_TestCase;
use Doctrine\ORM\Tools\EntityGenerator;
use Doctrine\ORM\Tools\SchemaTool;


class DoctrineOrmServiceProviderTest extends PHPUnit_Framework_TestCase
{

    protected $app;

    protected function setUp()
    {
        parent::setUp();
        $this->app = getApp();
    }

    function testApp()
    {
        $this->assertNotNull($this->app);
    }

    function testServiceProvider()
    {
        $this->assertNotNull($this->app["orm.em"]);
    }

    function testCreateSchema()
    {
        /* @var $em \Doctrine\ORM\EntityManager */
        $em        = $this->app["orm.em"];
        $tool      = new SchemaTool($em);
        $metatdata = $em->getMetadataFactory()->getAllMetadata();
        print_r($metatdata);
        $tool->createSchema($metatdata);
        $generator = new EntityGenerator();
        $generator->setRegenerateEntityIfExists(true);
        $generator->setGenerateAnnotations(true);
        $generator->setNumSpaces(4);
        $generator->generate($metatdata,ROOT_TEST_DIR);
        $this->assertFileExists(ROOT_TEST_DIR."/Entity/Post.php");
        //$post = new \Entity\Post;
    }


}