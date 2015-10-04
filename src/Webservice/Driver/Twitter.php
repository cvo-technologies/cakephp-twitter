<?php

namespace CvoTechnologies\Twitter\Webservice\Driver;

use Cake\Cache\Cache;
use Cake\Network\Http\Client;
use Muffin\Webservice\AbstractDriver;

class Twitter extends AbstractDriver
{

    /**
     * {@inheritDoc}
     */
    public function initialize()
    {
        $accessToken = $this->accessToken();
        // The access token is invalid
        if (!$accessToken) {
            // Get rid of the invalid access token
            $this->invalidateAccessToken();

            $accessToken = $this->accessToken();
        }

        $this->client(new Client([
            'host' => 'api.twitter.com',
            'scheme' => 'https',
            'headers' => ['Authorization' => 'Bearer ' . $accessToken]
        ]));
    }

    /**
     * Returns a application access token
     *
     * @return string|bool The access token or false in case of a failure
     */
    public function accessToken()
    {
        $cacheKey = 'twitter-' . $this->config('name') . '-token';
        if (Cache::read($cacheKey) !== false) {
            return Cache::read($cacheKey);
        }

        $client = new Client([
            'headers' => ['Authorization' => 'Basic ' . $this->bearerToken()],
            'host' => 'api.twitter.com',
            'scheme' => 'https',
        ]);

        $response = $client->post('/oauth2/token', [
            'grant_type' => 'client_credentials'
        ]);

        if ((!$response->isOk()) ||  (!$response->json['token_type'])) {
            return false;
        }

        Cache::write($cacheKey, $response->json['access_token']);

        return $response->json['access_token'];
    }

    /**
     * Returns a bearer token for application authentication
     *
     * @return string Bearer token
     */
    public function bearerToken()
    {
        $consumerKey = urlencode($this->config('consumerKey'));
        $consumerSecret = urlencode($this->config('consumerSecret'));

        return base64_encode($consumerKey . ':' . $consumerSecret);
    }

    /**
     * Invalidates the locally stored access token
     *
     * @return void
     */
    public function invalidateAccessToken()
    {
        Cache::delete('twitter-' . $this->config('name') . '-token');
    }
}
