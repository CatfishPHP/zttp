<?php
namespace Zttp;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Request;
use Illuminate\Support\Collection;

class PendingZttpRequest
{
    /**
     * @var Collection
     */
    protected $beforeSendingCallbacks;

    /**
     * @var string
     */
    protected $bodyFormat;

    /**
     * @var array
     */
    protected $options = [];

    /**
     * PendingZttpRequest constructor.
     */
    public function __construct()
    {
        $this->beforeSendingCallbacks = collect();
        $this->bodyFormat = 'json';
        $this->options = [
            'http_errors' => false,
        ];
    }

    /**
     * @return PendingZttpRequest
     */
    public static function new(): self
    {
        return new self();
    }

    /**
     * @param array $options
     *
     * @return PendingZttpRequest
     */
    public function withOptions(array $options): self
    {
        return tap($this, function ($request) use ($options) {
            return $this->options = array_merge_recursive($this->options, $options);
        });
    }

    /**
     * @return PendingZttpRequest
     */
    public function withoutRedirecting(): self
    {
        return tap($this, function ($request) {
            return $this->options = array_merge_recursive($this->options, [
                'allow_redirects' => false,
            ]);
        });
    }

    /**
     * @return PendingZttpRequest
     */
    public function withoutVerifying(): self
    {
        return tap($this, function ($request) {
            return $this->options = array_merge_recursive($this->options, [
                'verify' => false,
            ]);
        });
    }

    /**
     * @return PendingZttpRequest
     */
    public function asJson(): self
    {
        return $this->bodyFormat('json')
            ->contentType('application/json');
    }

    /**
     * @return PendingZttpRequest
     */
    public function asFormParams(): self
    {
        return $this->bodyFormat('form_params')
            ->contentType('application/x-www-form-urlencoded');
    }

    /**
     * @return PendingZttpRequest
     */
    public function asMultipart(): self
    {
        return $this->bodyFormat('multipart');
    }

    /**
     * @param string $format
     *
     * @return PendingZttpRequest
     */
    public function bodyFormat(string $format): self
    {
        return tap($this, function ($request) use ($format) {
            $this->bodyFormat = $format;
        });
    }

    /**
     * @param string $contentType
     *
     * @return PendingZttpRequest
     */
    public function contentType(string $contentType): self
    {
        return $this->withHeaders(['Content-Type' => $contentType]);
    }

    /**
     * @param string $header
     *
     * @return PendingZttpRequest
     */
    public function accept(string $header): self
    {
        return $this->withHeaders(['Accept' => $header]);
    }

    /**
     * @param array $headers
     *
     * @return PendingZttpRequest
     */
    public function withHeaders(array $headers): self
    {
        return tap($this, function ($request) use ($headers) {
            return $this->options = array_merge_recursive($this->options, [
                'headers' => $headers,
            ]);
        });
    }

    /**
     * @param string $username
     * @param string $password
     *
     * @return PendingZttpRequest
     */
    public function withBasicAuth(string $username, string $password): self
    {
        return tap($this, function ($request) use ($username, $password) {
            return $this->options = array_merge_recursive($this->options, [
                'auth' => [$username, $password],
            ]);
        });
    }

    /**
     * @param string $username
     * @param string $password
     *
     * @return mixed
     */
    public function withDigestAuth(string $username, string $password)
    {
        return tap($this, function ($request) use ($username, $password) {
            return $this->options = array_merge_recursive($this->options, [
                'auth' => [$username, $password, 'digest'],
            ]);
        });
    }

    /**
     * @param int $seconds
     *
     * @return PendingZttpRequest
     */
    public function timeout(int $seconds): self
    {
        return tap($this, function () use ($seconds) {
            $this->options['timeout'] = $seconds;
        });
    }

    /**
     * @param mixed $callback
     *
     * @return PendingZttpRequest
     */
    public function beforeSending($callback): self
    {
        return tap($this, function () use ($callback) {
            $this->beforeSendingCallbacks[] = $callback;
        });
    }

    /**
     * @param string $url
     * @param array  $queryParams
     *
     * @return ZttpResponse
     * @throws ConnectionException
     */
    public function get(string $url, array $queryParams = []): ZttpResponse
    {
        return $this->send('GET', $url, [
            'query' => $queryParams,
        ]);
    }

    /**
     * @param string $url
     * @param array  $params
     *
     * @return ZttpResponse
     * @throws ConnectionException
     */
    public function post(string $url, array $params = []): ZttpResponse
    {
        return $this->send('POST', $url, [
            $this->bodyFormat => $params,
        ]);
    }

    /**
     * @param string $url
     * @param array  $params
     *
     * @return ZttpResponse
     * @throws ConnectionException
     */
    public function patch(string $url, array $params = []): ZttpResponse
    {
        return $this->send('PATCH', $url, [
            $this->bodyFormat => $params,
        ]);
    }

    /**
     * @param string $url
     * @param array  $params
     *
     * @return ZttpResponse
     * @throws ConnectionException
     */
    public function put(string $url, array $params = []): ZttpResponse
    {
        return $this->send('PUT', $url, [
            $this->bodyFormat => $params,
        ]);
    }

    /**
     * @param string $url
     * @param array  $params
     *
     * @return ZttpResponse
     * @throws ConnectionException
     */
    public function delete(string $url, array $params = []): ZttpResponse
    {
        return $this->send('DELETE', $url, [
            $this->bodyFormat => $params,
        ]);
    }

    /**
     * @param string $method
     * @param string $url
     * @param array  $options
     *
     * @return ZttpResponse
     * @throws ConnectionException
     */
    public function send(string $method, string $url, array $options): ZttpResponse
    {
        try {
            $options = $this->mergeOptions(
                ['query' => $this->parseQueryParams($url)],
                $options
            );
            $response = $this->buildClient()
                ->request(
                    $method,
                    $url,
                    $options
                );
            return new ZttpResponse($response);
        } catch (ConnectException $e) {
            throw new ConnectionException($e->getMessage(), 0, $e);
        }
    }

    /**
     * @return Client
     */
    public function buildClient(): Client
    {
        return new Client(['handler' => $this->buildHandlerStack()]);
    }

    /**
     * @return HandlerStack
     */
    public function buildHandlerStack(): HandlerStack
    {
        return tap(HandlerStack::create(), function ($stack) {
            $stack->push($this->buildBeforeSendingHandler());
        });
    }

    /**
     * @return \Closure
     */
    public function buildBeforeSendingHandler(): \Closure
    {
        return function ($handler) {
            return function ($request, $options) use ($handler) {
                return $handler($this->runBeforeSendingCallbacks($request), $options);
            };
        };
    }

    /**
     * @param Request $request
     *
     * @return ZttpRequest
     */
    public function runBeforeSendingCallbacks(Request $request): Request
    {
        return tap($request, function ($request) {
            $this->beforeSendingCallbacks->each->__invoke(new ZttpRequest($request));
        });
    }

    /**
     * @param array ...$options
     *
     * @return array
     */
    public function mergeOptions(...$options): array
    {
        return array_merge_recursive($this->options, ...$options);
    }

    /**
     * @param string $url
     *
     * @return array
     */
    public function parseQueryParams(string $url): array
    {
        return tap([], function (&$query) use ($url) {
            parse_str(parse_url($url, PHP_URL_QUERY), $query);
        });
    }
}
