<?php

namespace App\Losovani;

use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * Business Login for Instagram plus the two Graph API reads the draw needs.
 * The access token lives only in the session; nothing is stored on the server.
 */
class InstagramClient
{
    private const string GRAPH = 'https://graph.instagram.com/v25.0';
    private const string SCOPE = 'instagram_business_basic,instagram_business_manage_comments';
    private const int MAX_PAGES = 40;

    public function __construct(
        private readonly HttpClientInterface $httpClient,
        #[Autowire(env: 'INSTAGRAM_APP_ID')]
        private readonly string $appId,
        #[Autowire(env: 'INSTAGRAM_APP_SECRET')]
        private readonly string $appSecret,
    ) {
    }

    public function isConfigured(): bool
    {
        return '' !== $this->appId && '' !== $this->appSecret;
    }

    public function authorizeUrl(string $redirectUri, string $state): string
    {
        return 'https://www.instagram.com/oauth/authorize?'.http_build_query([
            'client_id' => $this->appId,
            'redirect_uri' => $redirectUri,
            'response_type' => 'code',
            'scope' => self::SCOPE,
            'state' => $state,
        ]);
    }

    /**
     * Exchanges the authorization code for a short-lived (1 hour) access token.
     */
    public function exchangeCode(string $code, string $redirectUri): string
    {
        $data = $this->httpClient->request('POST', 'https://api.instagram.com/oauth/access_token', [
            'body' => [
                'client_id' => $this->appId,
                'client_secret' => $this->appSecret,
                'grant_type' => 'authorization_code',
                'redirect_uri' => $redirectUri,
                'code' => $code,
            ],
        ])->toArray();

        // The docs show the token wrapped in "data", the live API has returned it flat.
        return $data['data'][0]['access_token'] ?? $data['access_token'];
    }

    public function username(string $token): string
    {
        return $this->get('/me', $token, ['fields' => 'username'])['username'];
    }

    /**
     * Finds one of the account's own posts by its URL and loads all its comments.
     */
    public function post(string $url, string $token): Post
    {
        if (!preg_match('~instagram\.com/(?:[^/]+/)?(?:p|reel|tv)/([A-Za-z0-9_-]+)~', $url, $match)) {
            throw new \InvalidArgumentException('Tohle nevypadá jako odkaz na příspěvek na Instagramu.');
        }

        $media = null;
        foreach ($this->pages('/me/media', $token, ['fields' => 'id,shortcode,permalink,caption', 'limit' => 50]) as $item) {
            if ($item['shortcode'] === $match[1]) {
                $media = $item;
                break;
            }
        }
        if (null === $media) {
            throw new \InvalidArgumentException('Příspěvek jsem mezi příspěvky přihlášeného účtu nenašel.');
        }

        $comments = [];
        $fields = 'id,text,timestamp,username,replies{id,text,timestamp,username}';
        foreach ($this->pages('/'.$media['id'].'/comments', $token, ['fields' => $fields, 'limit' => 50]) as $item) {
            $comments[] = $this->comment($item, false);
            foreach ($item['replies']['data'] ?? [] as $reply) {
                $comments[] = $this->comment($reply, true);
            }
        }

        return new Post($this->username($token), $media['permalink'], $media['caption'] ?? null, $comments);
    }

    /**
     * @param array<string, mixed> $item
     */
    private function comment(array $item, bool $isReply): Comment
    {
        return new Comment($item['username'] ?? '?', $item['text'] ?? '', new \DateTimeImmutable($item['timestamp']), $isReply);
    }

    /**
     * Follows paging.next links.
     *
     * @param array<string, mixed> $query
     *
     * @return iterable<array<string, mixed>>
     */
    private function pages(string $path, string $token, array $query): iterable
    {
        $data = $this->get($path, $token, $query);
        for ($page = 1; ; ++$page) {
            yield from $data['data'] ?? [];
            $next = $data['paging']['next'] ?? null;
            if (null === $next || $page >= self::MAX_PAGES) {
                return;
            }
            $data = $this->httpClient->request('GET', $next)->toArray();
        }
    }

    /**
     * @param array<string, mixed> $query
     *
     * @return array<string, mixed>
     */
    private function get(string $path, string $token, array $query = []): array
    {
        return $this->httpClient->request('GET', self::GRAPH.$path, [
            'query' => $query + ['access_token' => $token],
        ])->toArray();
    }
}
