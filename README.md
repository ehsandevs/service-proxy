# Service Proxy

Internal communication between services with useful tools
<br>
Make request by laravel http client

## Installation

```bash
composer require behamin/service-proxy
```

### Publish config

```bash
php artisan vendor:publish --provider="Ehsandevs\ServiceProxy\Providers\ProxyServiceProvider" --tag config
```

### Add services

Add your project's base url and global headers in `proxy.php` config

```php
return [
    /**
     * Headers added to every request
     */
    'global_headers' => [
        'Accept' => 'application/json',
        ...
    ],

    'base_url' => env('PROXY_BASE_URL', env('APP_URL')),
];
```

## Usage

### Normal usage

```php
use Ehsandevs\ServiceProxy\Proxy;

// Http Get
Proxy::withToken('Your bearer token')
    ->acceptJson()
    ->retry(3)
    ->withHeaders([
        "Content-Type" => "application\json"
    ])->get('api/articles');
    
Proxy::post('api/articles', [
    "title" => "Test title",
    "body" => "Test body"
]);

Proxy::patch('api/articles/1', [
    "title" => "Test title",
    "body" => "Test body"
]);

Proxy::put('api/articles', [
    "title" => "Test title",
    "body" => "Test body"
]);

Proxy::delete('api/articles/1');
```

### Using http request

```php

use Ehsandevs\ServiceProxy\Proxy;
use Illuminate\Http\Request;

public function index(Request $request) {
    $serviceName = 'test-service';
    Proxy::request($request, $serviceName);
}

```

### Proxy events

#### On success

```php
use Ehsandevs\ServiceProxy\Proxy;
use Ehsandevs\ServiceProxy\Responses\ProxyResponse;
 
Proxy::get('api/articles/1')->onSuccess(function (ProxyResponse $proxyResponse) {
        $data = $proxyResponse->data();
        $message = $proxyResponse->message();
        $response = $proxyResponse->response();
        $items = $proxyResponse->items();
        $count = $proxyResponse->count();
        ...
    });
```

#### On error

```php
use Ehsandevs\ServiceProxy\Proxy;
use Ehsandevs\ServiceProxy\Exceptions\ProxyException;
 
Proxy::get('api/articles/1')->onSuccess(function (ProxyException $proxyException) {
        $proxyResponse = $proxyException->proxyResponse;
        $trace = $proxyException->getTraceAsString();
        ...
    });
```

#### On data success
```php
use Ehsandevs\ServiceProxy\Proxy;
 
Proxy::get('api/articles/1')->onDataSuccess(function (array $data) {
        $id = $data['id'];
    });
```

#### On data collection success
```php
use Ehsandevs\ServiceProxy\Proxy;
 
Proxy::get('api/articles/1')->onCollectionSuccess(function (array $items, int $count) {
        ...
    });
```


### Proxy response methods
```php
use Ehsandevs\ServiceProxy\Proxy;

$proxyResponse = Proxy::get('api/articles/1');
```

| Method                        | Description                                    |
| ----------------------------- | ---------------------------------------------- |
| data()                        | given data                                     |
| items()                       | give items                                     |
| count()                       | given items count                              |
| errors()                      | given errors if there is                       |
| message()                     | given message                                  |
| onSuccess($closure)           | When http request is successful                |
| onError($closure)             | When http request is with error                |
| onCollectionSuccess($closure) | Get collection when http request is successful |
| onDataSuccess($closure)       | Get data when http request is successful       |
| throw()                       | Throw error if http request failed             |
| toException()                 | Get exception if http request failed           |

### Proxy request methods

| Method                        | Return Type                                    |
| ----------------------------- | ---------------------------------------------- |
fake($callback = null) | \Illuminate\Http\Client\Factory
accept(string $contentType) | \Ehsandevs\ServiceProxy\Http 
acceptJson() | \Ehsandevs\ServiceProxy\Http 
asForm() | \Ehsandevs\ServiceProxy\Http 
asJson() | \Ehsandevs\ServiceProxy\Http 
asMultipart() | \Ehsandevs\ServiceProxy\Http 
async() | \Ehsandevs\ServiceProxy\Http 
attach(string array $name, string $contents = '', string null $filename = null, array $headers = []) | \Ehsandevs\ServiceProxy\Http 
baseUrl(string $url) | \Ehsandevs\ServiceProxy\Http 
beforeSending(callable $callback) | \Ehsandevs\ServiceProxy\Http 
bodyFormat(string $format) | \Ehsandevs\ServiceProxy\Http 
contentType(string $contentType) | \Ehsandevs\ServiceProxy\Http 
dd() | \Ehsandevs\ServiceProxy\Http 
dump() | \Ehsandevs\ServiceProxy\Http 
retry(int $times, int $sleep = 0) | \Ehsandevs\ServiceProxy\Http 
sink(string|resource $to) | \Ehsandevs\ServiceProxy\Http 
stub(callable $callback) | \Ehsandevs\ServiceProxy\Http 
timeout(int $seconds) | \Ehsandevs\ServiceProxy\Http 
withBasicAuth(string $username, string $password) | \Ehsandevs\ServiceProxy\Http 
withBody(resource|string $content, string $contentType) | \Ehsandevs\ServiceProxy\Http 
withCookies(array $cookies, string $domain) | \Ehsandevs\ServiceProxy\Http 
withDigestAuth(string $username, string $password) | \Ehsandevs\ServiceProxy\Http 
withHeaders(array $headers) | \Ehsandevs\ServiceProxy\Http 
withMiddleware(callable $middleware) | \Ehsandevs\ServiceProxy\Http 
withOptions(array $options) | \Ehsandevs\ServiceProxy\Http 
withToken(string $token, string $type = 'Bearer') | \Ehsandevs\ServiceProxy\Http 
withUserAgent(string $userAgent) | \Ehsandevs\ServiceProxy\Http 
withoutRedirecting() | \Ehsandevs\ServiceProxy\Http 
withoutVerifying() | \Ehsandevs\ServiceProxy\Http 
pool(callable $callback) | array
request(Request $request, string $service) | \Ehsandevs\ServiceProxy\Responses\ProxyResponse 
get(string $url, array|string|null $query = null) | \Ehsandevs\ServiceProxy\Responses\ProxyResponse 
delete(string $url, array $data = []) | \Ehsandevs\ServiceProxy\Responses\ProxyResponse 
head(string $url, array|string|null $query = null) | \Ehsandevs\ServiceProxy\Responses\ProxyResponse 
patch(string $url, array $data = []) | \Ehsandevs\ServiceProxy\Responses\ProxyResponse 
post(string $url, array $data = []) | \Ehsandevs\ServiceProxy\Responses\ProxyResponse 
put(string $url, array $data = []) | \Ehsandevs\ServiceProxy\Responses\ProxyResponse 
send(string $method, string $url, array $options = []) | \Ehsandevs\ServiceProxy\Responses\ProxyResponse 
fakeSequence(string $urlPattern = '*') | \Illuminate\Http\Client\ResponseSequence
assertSent(callable $callback) | void 
assertNotSent(callable $callback) | void 
assertNothingSent() | void 
assertSentCount(int $count) | void 
assertSequencesAreEmpty() | void

### Mocking proxy response
You can use `mock()` on Proxy class before calling http methods and pass the json path in your 'tests/mock' directory, to mock a json for faking your Proxy response in test mode.
Example:

```php
use Ehsandevs\ServiceProxy\Proxy;
Proxy::mock('response.json')->get('address');
```
