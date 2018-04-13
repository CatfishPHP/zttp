<?php
namespace Zttp;

use GuzzleHttp\Psr7\Request;

class ZttpRequest
{
    /**
     * @var Request
     */
    protected $request;

    /**
     * ZttpRequest constructor.
     *
     * @param Request $request
     */
    public function __construct(Request $request)
    {
        $this->request = $request;
    }

    /**
     * @return string
     */
    public function url(): string
    {
        return (string) $this->request->getUri();
    }

    /**
     * @return string
     */
    public function method(): string
    {
        return $this->request->getMethod();
    }

    /**
     * @return string
     */
    public function body(): string
    {
        return (string) $this->request->getBody();
    }

    /**
     * @return array
     */
    public function headers(): array
    {
        return collect($this->request->getHeaders())->mapWithKeys(function ($values, $header) {
            return [$header => $values[0]];
        })->all();
    }
}
