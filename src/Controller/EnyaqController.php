<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class EnyaqController extends AbstractController
{
    #[Route('/enyaq', name: 'enyaq')]
    public function index(): Response
    {
        return $this->render('enyaq/index.html.twig');
    }
}
