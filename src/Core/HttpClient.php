<?php

namespace Viagogo\Core;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Exception\RequestException;

/**
 *
 */
class HttpClient {
	/**
	 * @var array The headers to be sent with the request
	 */
	protected $requestHeaders = array();
	
	/**
	 * @var array The headers received from the response
	 */
	protected $responseHeaders = array();

	/**
	 * @var int The HTTP status code returned from the server
	 */
	protected $responseHttpStatusCode = 0;

	/**
	 * @var Client The Guzzle client
	 */
	protected $guzzleClient;

    protected ViagogoConfiguration $defaultConfiguration;

	/**
	 * @param Client|null The Guzzle client
	 */
	public function __construct($guzzleClient, ViagogoConfiguration $defaultConfiguration) {
		$this->guzzleClient = $guzzleClient;
        $this->defaultConfiguration = $defaultConfiguration;
	}

	/**
	 * The headers we want to send with the request
	 *
	 * @param string $key
	 * @param string $value
	 */
	public function setRequestHeader($key, $value) {
		$this->requestHeaders[$key] = $value;
	}

	/**
	 * The headers returned in the response
	 *
	 * @return array
	 */
	public function getResponseHeaders() {
		return $this->responseHeaders;
	}

	/**
	 * The HTTP status response code
	 *
	 * @return int
	 */
	public function getResponseHttpStatusCode() {
		return $this->responseHttpStatusCode;
	}

	/**
	 * Sends a request to the server
	 *
	 * @param string $url The endpoint to send the request to
	 * @param string $method The request method
	 * @param array  $bodyParameters The key value pairs to be sent in the body
	 *
	 * @return object - response from the server
	 *
	 * @throws \Viagogo\Exceptions\ViagogoException
	 */
	public function send($url, $method = 'GET', $queryParameters = array(), $bodyParameters = []) {
		$options = [
            'headers' => [
                'Content-Type' => 'application/json'
            ],
            'auth' => [
                $this->defaultConfiguration->clientId,
                $this->defaultConfiguration->clientSecret
            ]
        ];

        foreach ($this->requestHeaders as $key => $value) {
            $options['headers'][$key] = $value;
        }

        if ($bodyParameters) {
			$options['form_params'] = $bodyParameters;
		}

		if ($queryParameters) {
			$options['query'] = $queryParameters;
		}

//		$request = $this->guzzleClient->request($method, $url, $options);
//		$request->setHeader('Content-Type', 'application/json');

		try
		{
//			$response = $this->guzzleClient->send($request);
            $response = $this->guzzleClient->request($method, $url, $options);
		} catch (GuzzleException $e) {
			throw ErrorHandler::handleError($e);
		}

		$this->responseHttpStatusCode = $response->getStatusCode();
		$this->responseHeaders = $response->getHeaders();

		return json_decode($response->getBody());
	}

	public function getBytes($url, $queryParameters = array(), $bodyParameters = []) {
		$options = array();
		if ($bodyParameters) {
			$options['body'] = $bodyParameters;
		}

		if ($queryParameters) {
			$options['query'] = $queryParameters;
		}

		$request = $this->guzzleClient->createRequest("get", $url, $options);
		$request->setHeader('Content-Type', 'application/multipart/form-data');
		//$request->setHeader('Accept', 'multipart/form-data');
		foreach ($this->requestHeaders as $k => $v) {
			$request->setHeader($k, $v);
		}

		
		try
		{
			$response = $this->guzzleClient->send($request);
		} catch (RequestException $e) {
			echo ($e);
			throw ErrorHandler::handleError($e);
		}

		$this->responseHttpStatusCode = $response->getStatusCode();
		$this->responseHeaders = $response->getHeaders();

		return $response->getBody()->getContents();
	}


	public function sendFile($url, $fileContent, $fileName) {

		$file = new \GuzzleHttp\Post\PostFile($fileName, $fileContent);
		$multipart = new \GuzzleHttp\Post\MultipartBody([], [$file]);

		$options = array();
		$options['body'] = $multipart;

		$request = $this->guzzleClient->createRequest('POST', $url, $options);
		$request->setHeader("Content-Type", "multipart/form-data; boundary=". $multipart->getBoundary());
		
		foreach ($this->requestHeaders as $k => $v) {
			$request->setHeader($k, $v);
		}

		try
		{
			$response = $this->guzzleClient->send($request);
		} catch (RequestException $e) {
			throw ErrorHandler::handleError($e);
		}

		$this->responseHttpStatusCode = $response->getStatusCode();
		$this->responseHeaders = $response->getHeaders();

		return json_decode($response->getBody());
	}
}
