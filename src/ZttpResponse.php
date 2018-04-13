<?php
namespace Zttp;

use GuzzleHttp\Psr7\Response;
use Illuminate\Support\Traits\Macroable;

class ZttpResponse
{
    use Macroable {
        __call as macroCall;
    }

    /**
     * @var Response
     */
    protected $response;

    /**
     * ZttpResponse constructor.
     *
     * @param Response $response
     */
    public function __construct(Response $response)
    {
        $this->response = $response;
    }

    /**
     * @return string
     */
    public function body(): string
    {
        return (string) $this->response->getBody();
    }

    /**
     * @return array
     */
    public function json(): array
    {
        return json_decode($this->response->getBody(), true);
    }

    /**
     * @param string $header
     *
     * @return string
     */
    public function header(string $header): string
    {
        return $this->response->getHeaderLine($header);
    }

    /**
     * @return array
     */
    public function headers(): array
    {
        return collect($this->response->getHeaders())->mapWithKeys(function ($v, $k) {
            return [$k => $v[0]];
        })->all();
    }

    /**
     * @return int
     */
    public function status(): int
    {
        return $this->response->getStatusCode();
    }

    /**
     * @return bool
     */
    public function isSuccess(): bool
    {
        return $this->status() >= 200 && $this->status() < 300;
    }

    /**
     * @return bool
     */
    public function isOk(): bool
    {
        return $this->isSuccess();
    }

    /**
     * @return bool
     */
    public function isRedirect(): bool
    {
        return $this->status() >= 300 && $this->status() < 400;
    }

    /**
     * @return bool
     */
    public function isClientError(): bool
    {
        return $this->status() >= 400 && $this->status() < 500;
    }

    /**
     * @return bool
     */
    public function isServerError(): bool
    {
        return $this->status() >= 500;
    }

    /**
     * @return string
     */
    public function __toString(): string
    {
        return $this->body();
    }

    /**
     * @param string $method
     * @param array  $args
     *
     * @return mixed
     */
    public function __call($method, $args)
    {
        if (static::hasMacro($method)) {
            return $this->macroCall($method, $args);
        }

        return $this->response->{$method}(...$args);
    }
}
