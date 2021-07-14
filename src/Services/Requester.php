<?php

namespace Inspirum\Balikobot\Services;

use GuzzleHttp\Psr7\Response;
use Inspirum\Balikobot\Contracts\RequesterInterface;
use Inspirum\Balikobot\Definitions\API;
use Inspirum\Balikobot\Exceptions\BadRequestException;
use Inspirum\Balikobot\Exceptions\UnauthorizedException;
use Psr\Http\Message\ResponseInterface;
use RuntimeException;

class Requester implements RequesterInterface
{
    /**
     * API User
     *
     * @var string
     */
    private $apiUser;

    /**
     * API key
     *
     * @var string
     */
    private $apiKey;

    /**
     * Response validator
     *
     * @var \Inspirum\Balikobot\Services\Validator
     */
    private $validator;

    /**
     * Balikobot API client
     *
     * @param string $apiUser
     * @param string $apiKey
     */
    public function __construct(string $apiUser, string $apiKey)
    {
        $this->apiUser = $apiUser;
        $this->apiKey  = $apiKey;

        $this->validator = new Validator();
    }

    /**
     * Call API
     *
     * @param string             $version
     * @param string             $request
     * @param string             $shipper
     * @param array<mixed,mixed> $data
     * @param bool               $shouldHaveStatus
     *
     * @return array<mixed,mixed>
     *
     * @throws \Inspirum\Balikobot\Contracts\ExceptionInterface
     */
    public function call(
        string $version,
        string $shipper,
        string $request,
        array $data = [],
        bool $shouldHaveStatus = true
    ): array {
        // resolve url
        $path = trim($shipper . '/' . $request, '/');
        $path = str_replace('//', '/', $path);
        $host = $this->resolveHostName($version);

        // call API server and get response
        $response = $this->request($host . $path, $data);

        // get status code and content
        $statusCode = $response->getStatusCode();
        $content    = $response->getBody()->getContents();

        // parse response content to assoc array
        $content = json_decode($content, true);

        // return empty array when json_decode fails
        if ($content === null) {
            $content = [];
        }

        // validate response status code
        $this->validateResponse($statusCode, $content, $shouldHaveStatus);

        // return response
        return $content;
    }

    /**
     * Get API response
     *
     * @param string             $url
     * @param array<mixed,mixed> $data
     *
     * @return \Psr\Http\Message\ResponseInterface
     */
    public function request(string $url, array $data = []): ResponseInterface
    {
        // init curl
        $ch = curl_init();

        // set headers
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HEADER, false);

        // set data
        if (count($data) > 0) {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        }

        // set auth
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Basic ' . base64_encode($this->apiUser . ':' . $this->apiKey),
            'Content-Type: application/json',
        ]);

        // execute curl
        $response   = curl_exec($ch);
        $statusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        // check for errors.
        if ($response === false) {
            throw new RuntimeException(curl_error($ch), curl_errno($ch));
        }

        // close curl
        curl_close($ch);

        return new Response((int) $statusCode, [], (string) $response);
    }

    /**
     * Get API url for given version
     *
     * @param string $version
     *
     * @return string
     */
    private function resolveHostName(string $version): string
    {
        return API::URL[$version] ?? API::URL[API::V2V1];
    }

    /**
     * Validate response
     *
     * @param int                $statusCode
     * @param array<mixed,mixed> $response
     * @param bool               $shouldHaveStatus
     *
     * @throws \Inspirum\Balikobot\Contracts\ExceptionInterface
     */
    private function validateResponse(int $statusCode, array $response, bool $shouldHaveStatus): void
    {
        $this->validator->validateStatus($statusCode, $response);

        $this->validator->validateResponseStatus($response, null, $shouldHaveStatus);
    }
}
