<?php

// src\Acme\TestBundle\Controller\TestController.php

namespace Acme\TestBundle\Controller;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;

class TestController extends Controller{
    function indexAction($first_name,$last_name,$color,$_format){
        #@note @symfony acceder à l'object request
        $ref = $this->getRequest()->headers->get("Accept");
        return new Response(" Hi from Test, $first_name $last_name with color $color , the accept header is $ref. $_format");
    }

    function redirectAction(){
    //@note @symfony  rediriger vers l'index 
        return $this->redirect($this->generateUrl("test_test_index"),301);

    }

    function forwardAction()
    {
        // interroger le container d'injection de dépendance
        $container = $this->container("http_kernel");
        //@note @symfony forwarder une requète dans un controleur
       return $this->forward("AcmeHelloBundle:Hi:index",array("name"=>"John Forward"));
    }
}