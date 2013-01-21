<?php
//@note @symfony créer un controller
// src/Acme/HelloBundle/Controller/HiController.php

namespace Acme\HelloBundle\Controller;

use Symfony\Component\HttpFoundation\Response;

class HiController{
    function indexAction($name){
        return new Response("<html><body>Hi '"
            .$name."' !</body></html>");
    }
}