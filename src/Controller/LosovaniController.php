<?php

namespace App\Controller;

use App\Losovani\Criteria;
use App\Losovani\Draw;
use App\Losovani\PastedComments;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Giveaway draw over comments pasted as "@username | text" lines, usually Claude's transcription
 * of comment screenshots. The Instagram API was dropped: in development mode it only returns
 * comments of accounts with a role on the Meta app.
 */
class LosovaniController extends AbstractController
{
    public function __construct(
        private readonly Draw $draw,
    ) {
    }

    #[Route('/losovani', name: 'losovani', methods: ['GET', 'POST'])]
    public function index(Request $request): Response
    {
        $result = null;
        $error = null;

        if ($request->isMethod('POST')) {
            try {
                $post = PastedComments::parse($request->request->getString('pasted'));
                $criteria = Criteria::fromRequest($request);
                $result = ['post' => $post, 'criteria' => $criteria] + $this->draw->run($post, $criteria);
            } catch (\InvalidArgumentException $exception) {
                $error = $exception->getMessage();
            }
        }

        $response = $this->render('losovani/index.html.twig', [
            'form' => $request->request->all(),
            'submitted' => $request->isMethod('POST'),
            'result' => $result,
            'error' => $error,
            'prompt' => PastedComments::PROMPT,
        ]);
        $response->headers->set('X-Robots-Tag', 'noindex, nofollow');

        return $response;
    }
}
