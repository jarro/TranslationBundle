<?php

namespace Happyr\TranslationBundle\Http;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Pool;
use Happyr\TranslationBundle\Exception\HttpException;

/**
 * @author Tobias Nyholm
 */
class Guzzle5Adapter implements HttpAdapterInterface
{
    /**
     * {@inheritdoc}
     */
    public function send($method, $url, $data)
    {
        $client = new Client([
            'base_url' => HttpAdapterInterface::BASE_URL,
        ]);

        try {
            $response = $client->send($client->createRequest($method, $url, $data));
        } catch (ClientException $e) {
            throw new HttpException('Could not transfer data to Loco', $e->getCode(), $e);
        }

        return $response->json();
    }

    /**
     * @param array $data array($url=>$savePath)
     */
    public function downloadFiles(array $data)
    {
        $client = new Client();

        $requests = array();
        foreach ($data as $url => $path) {
            $requests[] = $client->createRequest('GET', HttpAdapterInterface::BASE_URL.$url, [
                'save_to' => fopen($path, 'w'),
            ]);
        }

        // Results is a GuzzleHttp\BatchResults object.
        $results = Pool::batch($client, $requests, [
            'pool_size' => 5,
        ]);

        // Retrieve all failures.
        foreach ($results->getFailures() as $requestException) {
            //TODO error handling
            throw new \Exception($requestException->getMessage());
        }
    }
}
