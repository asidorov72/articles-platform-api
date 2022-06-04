<?php

namespace App\Controller;

use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class DefaultController extends AbstractController
{
    public function index(LoggerInterface $monologLogger): Response
    {
        $monologLogger->error('Index page response: ' . Response::HTTP_NOT_FOUND);

        return new Response(null, Response::HTTP_NOT_FOUND);
    }
}
