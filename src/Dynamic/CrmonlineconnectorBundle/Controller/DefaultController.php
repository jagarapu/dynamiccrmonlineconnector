<?php

namespace Dynamic\CrmonlineconnectorBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class DefaultController extends Controller
{
    public function indexAction()
    {
        return $this->render('DynamicCrmonlineconnectorBundle:Default:index.html.twig');
    }
}      