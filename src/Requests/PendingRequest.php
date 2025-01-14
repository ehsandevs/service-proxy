<?php

namespace Ehsandevs\ServiceProxy\Requests;

use Ehsandevs\ServiceProxy\Http;
use Ehsandevs\ServiceProxy\Responses\ProxyResponse;
use Ehsandevs\ServiceProxy\UrlGenerator;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Exception\TransferException;
use GuzzleHttp\Promise\PromiseInterface;
use Illuminate\Http\Client\PendingRequest as HttpPendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use InvalidArgumentException;
use Psr\Http\Message\MessageInterface;
use Symfony\Component\HttpKernel\Exception\NotAcceptableHttpException;
use Illuminate\Support\Facades\Http as HttpFactory;

class PendingRequest extends HttpPendingRequest
{
    private string $service = '';

    private ?string $domain = null;

    public function __construct($factory = null)
    {
        parent::__construct($factory);
    }

    public function domain(string $domain): PendingRequest
    {
        $this->domain = $domain;

        return $this;
    }

    public function request(Request $request, string $service): ProxyResponse
    {
        $this->service = $service;
        $path = $request->path();
        $data = $request->all();

        foreach ($request->allFiles() as $name => $file) {
            unset($data[$name]);
            $this->attach($name, $request->file($name)->getContent(), $request->file($name)->getClientOriginalName());
        }

        switch ($request->method()) {
            case Request::METHOD_GET:
                return $this->get($path, $data);
            case Request::METHOD_POST:
                return $this->post($path, $data);
            case Request::METHOD_DELETE:
                return $this->delete($path, $data);
            case Request::METHOD_HEAD:
                return $this->head($path, $data);
            case Request::METHOD_PATCH:
                return $this->patch($path, $data);
            case Request::METHOD_PUT:
                return $this->put($path, $data);
            default:
                throw new NotAcceptableHttpException();
        }
    }

    public function get(string $url = null, $query = null)
    {
        return $this->respond($url, $query, Request::METHOD_GET);
    }

    public function delete($url = null, $data = [])
    {
        return $this->respond($url, $data, Request::METHOD_DELETE);
    }

    public function head(string $url = null, $query = null)
    {
        return $this->respond($url, $query, Request::METHOD_HEAD);
    }

    public function patch($url, $data = [])
    {
        return $this->respond($url, $data, Request::METHOD_PATCH);
    }

    public function post(string $url, $data = [])
    {
        return $this->respond($url, $data, Request::METHOD_POST);
    }

    public function put($url, $data = [])
    {
        return $this->respond($url, $data, Request::METHOD_PUT);
    }

    public function prepare(): void
    {
        $this->withHeaders(config('proxy.global_headers', []));
    }

    protected function makePromise(string $method, string $url, array $options = [], int $attempt = 1)
    {
        return $this->promise = $this->sendRequest($method, $url, $options)
            ->then(function (MessageInterface $message) {
                return tap($this->newResponse($message), function ($response) {
                    $this->populateResponse($response);
                    $this->dispatchResponseReceivedEvent($response);
                });
            })
            ->otherwise(function (OutOfBoundsException|TransferException $e) {
                if ($e instanceof ConnectException || ($e instanceof RequestException && ! $e->hasResponse())) {
                    $exception = new ConnectionException($e->getMessage(), 0, $e);

                    $this->dispatchConnectionFailedEvent(new Request($e->getRequest()), $exception);

                    return $exception;
                }

                return $e instanceof RequestException && $e->hasResponse() ? $this->populateResponse($this->newResponse($e->getResponse())) : $e;
            })
            ->then(function (Response|ConnectionException|TransferException $response) use ($method, $url, $options, $attempt) {
                return $this->handlePromiseResponse($response, $method, $url, $options, $attempt);
            });
    }

    /**
     * @param $url
     * @return bool
     */
    private function isValidUrl($url): bool
    {
        $pattern = '/^.+?\..+$/';
        return preg_match($pattern, $url) != false;
    }

    /**
     * @param  null|string  $path
     * @return string
     */
    private function fullUrl(?string $path): string
    {
        $baseUrl = UrlGenerator::baseUrl($this->domain);
        $servicePath = $this->service;
        if (Str::endsWith($baseUrl, '/')) {
            $baseUrl = Str::substr($baseUrl, 0, -1);
        }

        if (Str::startsWith($servicePath, '/')) {
            $servicePath = Str::substr($baseUrl, 1);
        }

        $finalPath = Str::startsWith($path, '/') ? Str::substr($path, 1) : $path;

        $prefix = $baseUrl . ($servicePath === '' ? $servicePath : '/' . $servicePath);

        return $this->isValidUrl($path) ? $finalPath : $prefix . '/' . $finalPath;
    }

    private function respond($url, $data, $method)
    {
        try {
            if ($this->factory->isSetMocking() && $this->isHttpRequestMethod($method)) {
                $this->factory->mock([$url => $this->factory->getMockPath()]);
            }

            if (app()->runningUnitTests() && $this->factory instanceof Http && $this->factory->hasFake($url)) {
                /** Http will remove / from start url */
                $result = HttpFactory::$method($url);
            } else {
                $this->prepare();
                $method = Str::lower($method);
                $result = parent::$method($this->fullUrl($url), $data);
            }

            if ($result instanceof PromiseInterface) {
                return $result;
            }
            return new ProxyResponse($result);
        } catch (\Exception $exception) {
            throw new InvalidArgumentException($exception->getMessage());
        }
    }

    private function isHttpRequestMethod($method): bool
    {
        return in_array(Str::lower($method), ['post', 'get', 'head', 'delete', 'put', 'patch']);
    }
}
