<?php

//@note @symfony créer un controller
// src/Acme/HelloBundle/Controller/HiController.php

namespace Acme\HelloBundle\Controller;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class HiController extends Controller {

    function indexAction($name) {
        // @note @symfony render a raw html response body
        //return new Response("<html><body>Hi '"
        //   .$name."' !</body></html>");
        // @note @symfony render html with twig , class must extend Controller
        return $this->render('AcmeHelloBundle:Hi:index.html.twig', array("name" => $name));
        // @note @symfony rendre un template php 
        // return $this->render('AcmeHelloBundle:Hi:index.html.php',array("name"=>$name));
        // @note @symfony convention de nommage NomBundle:NomController:NomTemplate
    }

}