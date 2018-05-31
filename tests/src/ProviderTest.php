<?php
/**
 * MercadoLibre Provider for OAuth 2.0 Client
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE file
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright 2018 Lucas Banegas <lucasconobanegas@gmail.com>
 * @license https://opensource.org/licenses/MIT MIT License
 * @author Lucas Banegas <lucasconobanegas@gmail.com>
 * @link https://github.com/docta/oauth2-mercadolibre Repository
 * @link https://docta.github.io/oauth2-mercadolibre Documentation
 */
namespace Docta\MercadoLibre\OAuth2\Client\Test;

use Docta\MercadoLibre\OAuth2\Client\Provider;
use Docta\MercadoLibre\OAuth2\Client\ResourceOwner;
use InvalidArgumentException;
use League\OAuth2\Client\Grant\AbstractGrant;
use League\OAuth2\Client\Provider\Exception\IdentityProviderException;
use Mockery;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\RequestInterface;

/**
 * Represents a service provider that can be used to interact with the MercadoLibre
 * OAuth 2.0 service provider, using bearer token authentication.
 */
class ProviderTest extends TestCase
{
    /**
     * @var array Options
     */
    protected $options = [
        'authSite' => 'MLA',
        'clientId' => 'mockClientId',
        'clientSecret' => 'mockClientSecret',
        'redirectUri' => 'http://mockRedirectUri.com/'
    ];

    /**
     * @var Provider Provider
     */
    protected $provider;

    /**
     * Setup test.
     *
     * @return void
     */
    protected function setUp()
    {
        $this->provider = new Provider($this->options);
    }

    /**
     * Tear down test.
     *
     * @return void
     */
    public function tearDown()
    {
        Mockery::close();
        parent::tearDown();
    }

    /**
     * testAssertRequiredOptions
     *
     * @return void
     */
    public function testAssertRequiredOptions()
    {
        $msg = 'Required options not defined: authSite, clientId, clientSecret, redirectUri';

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage($msg);
        $this->provider = new Provider();
    }

    /**
     * testAssertAuthSite
     *
     * @return void
     */
    public function testAssertAuthSite()
    {
        $msg = 'Valid values for authSite are only: MLA, MLB, MCO, MCR, MEC, MLC, MLM, MLU, MLV, MPA, MPE, MPT, MRD';

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage($msg);
        $this->options['authSite'] = 'invalidAuthSite';
        $this->provider = new Provider($this->options);
    }

    /**
     * testGetAuthorizationUrl
     *
     * @return void
     */
    public function testGetAuthorizationUrl()
    {
        $url = $this->provider->getAuthorizationUrl();
        $this->assertInternalType('string', $url);

        $url = parse_url($url);
        $this->assertArrayHasKey('scheme', $url);
        $this->assertArrayHasKey('host', $url);
        $this->assertArrayHasKey('path', $url);
        $this->assertArrayHasKey('query', $url);
        $this->assertSame('https', $url['scheme']);
        $this->assertSame('auth.mercadolibre.com.ar', $url['host']);
        $this->assertSame('/authorization', $url['path']);

        parse_str($url['query'], $query);
        $this->assertArrayNotHasKey('approval_prompt', $query);
        $this->assertArrayHasKey('response_type', $query);
        $this->assertArrayHasKey('client_id', $query);
        $this->assertArrayHasKey('redirect_uri', $query);
        $this->assertArrayHasKey('state', $query);
        $this->assertSame('code', $query['response_type']);
        $this->assertSame($this->options['clientId'], $query['client_id']);
        $this->assertSame($this->options['redirectUri'], $query['redirect_uri']);
        $this->assertSame($this->provider->getState(), $query['state']);
    }

    /**
     * testGetApiUrl
     *
     * @return void
     */
    public function testGetApiUrl()
    {
        $expected = 'https://api.mercadolibre.com/';
        $this->assertSame($expected, $this->provider->getApiUrl());
    }

    /**
     * testGetApiUrlWithPath
     *
     * @return void
     */
    public function testGetApiUrlWithPath()
    {
        $expected = 'https://api.mercadolibre.com/test';
        $this->assertSame($expected, $this->provider->getApiUrl('/test'));
    }

    /**
     * testGetApiUrlWithPathAndQuery
     *
     * @return void
     */
    public function testGetApiUrlWithPathAndQuery()
    {
        $expected = 'https://api.mercadolibre.com/test?key=value';
        $this->assertSame($expected, $this->provider->getApiUrl('/test', ['key' => 'value']));
    }

    /**
     * testGetBaseAuthorizationUrl
     *
     * @return void
     */
    public function testGetBaseAuthorizationUrl()
    {
        $expected = 'https://auth.mercadolibre.com.ar/authorization';
        $this->assertSame($expected, $this->provider->getBaseAuthorizationUrl());
    }

    /**
     * testGetBaseAccessTokenUrl
     *
     * @return void
     */
    public function testGetBaseAccessTokenUrl()
    {
        $expected = 'https://api.mercadolibre.com/oauth/token';
        $this->assertSame($expected, $this->provider->getBaseAccessTokenUrl());
    }

    /**
     * testGetResourceOwnerDetailsUrl
     *
     * @return void
     */
    public function testGetResourceOwnerDetailsUrl()
    {
        $expected = 'https://api.mercadolibre.com/users/me';
        $this->assertSame($expected, $this->provider->getResourceOwnerDetailsUrl());
    }

    /**
     * testGetDefaultScopes
     *
     * @return void
     */
    public function testGetDefaultScopes()
    {
        $this->assertNull($this->provider->getDefaultScopes());
    }

    /**
     * testGetAuthenticatedRequest
     *
     * @return void
     */
    public function testGetAuthenticatedRequest()
    {
        $url = $this->provider->getResourceOwnerDetailsUrl();
        $req = $this->provider->getAuthenticatedRequest('POST', $url, 'mockToken');
        $this->assertInstanceOf(RequestInterface::class, $req);
        $this->assertSame('POST', $req->getMethod());
        $this->assertSame('https', $req->getUri()->getScheme());
        $this->assertSame('api.mercadolibre.com', $req->getUri()->getHost());
        $this->assertSame('/users/me', $req->getUri()->getPath());
        $this->assertContains('access_token=mockToken', $req->getUri()->getQuery());
    }

    /**
     * testCheckResponse
     *
     * @return void
     */
    public function testCheckResponse()
    {
        $body = [
            'token_type' => 'bearer',
            'access_token' => 'mockAccessToken',
            'refresh_token' => 'mockRefreshToken',
            'expires' => time() + (6 * 60 * 60),
            'user_id' => 'mockUserId'
        ];

        $response = Mockery::mock('Psr\Http\Message\ResponseInterface');
        $response->shouldReceive('getHeader')->andReturn(['content-type' => 'json']);
        $response->shouldReceive('getBody')->andReturn(json_encode($body));
        $client = Mockery::mock('GuzzleHttp\ClientInterface');
        $client->shouldReceive('send')->times(1)->andReturn($response);
        $this->provider->setHttpClient($client);

        $token = $this->provider->getAccessToken('authorization_code', ['code' => 'mockCode']);
        $this->assertSame($body['access_token'], $token->getToken());
        $this->assertSame($body['refresh_token'], $token->getRefreshToken());
        $this->assertSame($body['expires'], $token->getExpires());
        $this->assertSame($body['user_id'], $token->getResourceOwnerId());
        $this->assertFalse($token->hasExpired());
    }

    /**
     * testCheckResponseException
     *
     * @return void
     */
    public function testCheckResponseException()
    {
        $error = [
            'error' => 'mockError',
            'status' => 400
        ];

        $response = Mockery::mock('Psr\Http\Message\ResponseInterface');
        $response->shouldReceive('getStatusCode')->andReturn($error['status']);
        $response->shouldReceive('getHeader')->andReturn(['content-type' => 'json']);
        $response->shouldReceive('getBody')->andReturn(json_encode($error));
        $client = Mockery::mock('GuzzleHttp\ClientInterface');
        $client->shouldReceive('send')->times(1)->andReturn($response);
        $this->provider->setHttpClient($client);

        $this->expectException(IdentityProviderException::class);
        $this->expectExceptionMessage($error['error']);
        $this->expectExceptionCode($error['status']);
        $this->provider->getAccessToken('authorization_code', ['code' => 'mockCode']);
    }

    /**
     * testCreateResourceOwner
     *
     * @return void
     */
    public function testCreateResourceOwner()
    {
        $tokenBody = [
            'token_type' => 'bearer',
            'access_token' => 'mockAccessToken',
            'refresh_token' => 'mockRefreshToken',
            'expires' => time() + (6 * 60 * 60),
            'user_id' => 'mockUserId'
        ];

        $ownerBody = [
            'id' => 'mockId',
            'first_name' => 'mockFirstName',
            'last_name' => 'mockLastName',
            'address' => [
                'address' => 'mockAddress',
                'country' => 'mockCountry'
            ]
        ];

        $response = Mockery::mock('Psr\Http\Message\ResponseInterface');
        $response->shouldReceive('getHeader')->andReturn(['content-type' => 'json']);
        $response->shouldReceive('getBody')->andReturn(json_encode($tokenBody));
        $client = Mockery::mock('GuzzleHttp\ClientInterface');
        $client->shouldReceive('send')->times(1)->andReturn($response);
        $this->provider->setHttpClient($client);
        $token = $this->provider->getAccessToken('authorization_code', ['code' => 'mockCode']);

        $response = Mockery::mock('Psr\Http\Message\ResponseInterface');
        $response->shouldReceive('getHeader')->andReturn(['content-type' => 'json']);
        $response->shouldReceive('getBody')->andReturn(json_encode($ownerBody));
        $client = Mockery::mock('GuzzleHttp\ClientInterface');
        $client->shouldReceive('send')->times(1)->andReturn($response);
        $this->provider->setHttpClient($client);
        $owner = $this->provider->getResourceOwner($token);

        $this->assertInstanceOf(ResourceOwner::class, $owner);
        $this->assertSame($ownerBody['id'], $owner->getId());
        $this->assertSame($ownerBody['first_name'], $owner->get('first_name'));
        $this->assertSame($ownerBody['last_name'], $owner->get('last_name'));
        $this->assertSame($ownerBody['address']['address'], $owner->get('address.address'));
        $this->assertSame($ownerBody['address']['country'], $owner->get('address.country'));
        $this->assertSame($ownerBody, $owner->toArray());
    }
}
