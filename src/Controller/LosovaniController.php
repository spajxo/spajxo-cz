<?php

namespace App\Controller;

use App\Losovani\Criteria;
use App\Losovani\Draw;
use App\Losovani\InstagramClient;
use App\Losovani\PastedComments;
use App\Losovani\SamplePost;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Contracts\HttpClient\Exception\ExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\HttpExceptionInterface;

/**
 * Giveaway draw over the comments of an Instagram post. Access is the Instagram login itself:
 * while the Meta app stays in development mode only accounts with a role on the app can authorize it.
 */
class LosovaniController extends AbstractController
{
    public function __construct(
        private readonly InstagramClient $instagram,
        private readonly Draw $draw,
    ) {
    }

    #[Route('/losovani', name: 'losovani', methods: ['GET', 'POST'])]
    public function index(Request $request): Response
    {
        $session = $request->getSession();
        $account = $this->account($session);
        $sample = !$this->instagram->isConfigured();
        $result = null;
        $error = null;

        if ($request->isMethod('POST')) {
            try {
                $pasted = 'instagram' !== $request->request->getString('source', 'paste');
                $post = match (true) {
                    !$sample && null === $account => throw new \InvalidArgumentException('Přihlášení vypršelo, přihlaste se prosím znovu.'),
                    $pasted => PastedComments::parse($request->request->getString('pasted'), $account['username'] ?? 'ukazkovy_ucet'),
                    $sample => SamplePost::create(),
                    default => $this->instagram->post($request->request->getString('url'), $account['token']),
                };
                $criteria = Criteria::fromRequest($request);
                $result = ['post' => $post, 'criteria' => $criteria] + $this->draw->run($post, $criteria);
            } catch (\InvalidArgumentException $exception) {
                $error = $exception->getMessage();
            } catch (HttpExceptionInterface $exception) {
                $error = 'Instagram odpověděl chybou: '.InstagramClient::errorMessage($exception);
            } catch (ExceptionInterface) {
                $error = 'Instagram odpověděl chybou. Zkuste to prosím znovu, případně se přihlaste znovu.';
            } catch (\Exception) {
                $error = 'Některé z kritérií má neplatnou hodnotu.';
            }
        }

        return $this->noindex($this->render('losovani/index.html.twig', [
            'account' => $account['username'] ?? null,
            'sample' => $sample,
            'form' => $request->request->all(),
            'submitted' => $request->isMethod('POST'),
            'result' => $result,
            'error' => $error,
            'prompt' => PastedComments::PROMPT,
        ]));
    }

    #[Route('/losovani/prihlaseni', name: 'losovani_login')]
    public function login(Request $request): Response
    {
        if (!$this->instagram->isConfigured()) {
            return $this->redirectToRoute('losovani');
        }

        $state = bin2hex(random_bytes(16));
        $request->getSession()->set('losovani_state', $state);

        return new RedirectResponse($this->instagram->authorizeUrl($this->callbackUrl(), $state));
    }

    #[Route('/losovani/callback', name: 'losovani_callback')]
    public function callback(Request $request): Response
    {
        $session = $request->getSession();
        $state = $session->remove('losovani_state');
        $code = $request->query->getString('code');

        if ('' === $code || null === $state || !hash_equals($state, $request->query->getString('state'))) {
            $reason = $request->query->getString('error_description');
            $this->addFlash('error', 'Přihlášení přes Instagram se nepodařilo.'.('' !== $reason ? ' Instagram: '.$reason : ''));

            return $this->redirectToRoute('losovani');
        }

        try {
            // Instagram appends "#_" to the redirect, which some browsers keep in the code.
            $token = $this->instagram->exchangeCode(preg_replace('/#_$/', '', $code), $this->callbackUrl());
            $session->migrate(true);
            $session->set('losovani_account', [
                'token' => $token,
                'username' => $this->instagram->username($token),
                'expires' => time() + 3300,
            ]);
        } catch (HttpExceptionInterface $exception) {
            $this->addFlash('error', 'Přihlášení přes Instagram se nepodařilo. Instagram: '.InstagramClient::errorMessage($exception));
        } catch (ExceptionInterface) {
            $this->addFlash('error', 'Přihlášení přes Instagram se nepodařilo.');
        }

        return $this->redirectToRoute('losovani');
    }

    #[Route('/losovani/odhlaseni', name: 'losovani_logout', methods: ['POST'])]
    public function logout(Request $request): Response
    {
        $request->getSession()->invalidate();

        return $this->redirectToRoute('losovani');
    }

    /**
     * @return array{token: string, username: string, expires: int}|null
     */
    private function account(SessionInterface $session): ?array
    {
        $account = $session->get('losovani_account');
        if (null !== $account && $account['expires'] < time()) {
            $session->remove('losovani_account');

            return null;
        }

        return $account;
    }

    private function callbackUrl(): string
    {
        return $this->generateUrl('losovani_callback', [], UrlGeneratorInterface::ABSOLUTE_URL);
    }

    private function noindex(Response $response): Response
    {
        $response->headers->set('X-Robots-Tag', 'noindex, nofollow');

        return $response;
    }
}
