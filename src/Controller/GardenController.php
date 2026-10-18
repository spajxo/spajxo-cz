<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class GardenController extends AbstractController
{
    #[Route('/garden', name: 'garden')]
    public function index(): Response
    {
        $response = $this->render('garden/index.html.twig');
        $response->headers->set('X-Robots-Tag', 'noindex, nofollow');

        return $response;
    }
}
