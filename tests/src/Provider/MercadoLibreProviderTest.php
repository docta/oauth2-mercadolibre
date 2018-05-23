<?php
/**
 * MercadoLibre Provider for OAuth 2.0 Client.
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE file
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright Copyright (c) 2018 Lucas Banegas <lucasconobanegas@gmail.com>
 * @license https://opensource.org/licenses/MIT MIT License
 * @link https://github.com/docta/oauth2-mercadolibre
 */
namespace Docta\OAuth2\Client\Test\Provider;

use Docta\OAuth2\Client\Provider\MercadoLibreProvider;
use League\OAuth2\Client\Provider\Exception\IdentityProviderException;
use League\OAuth2\Client\Tool\QueryBuilderTrait;
use Mockery;

/**
 * Represents a service provider (authorization server).
 *
 * @link http://tools.ietf.org/html/rfc6749#section-1.1 Roles (RFC 6749, §1.1)
 */
class MercadoLibreProviderTest extends \PHPUnit_Framework_TestCase
{
    use QueryBuilderTrait;

    /**
     * @var string Auth site
     */
    protected $authSite = 'MLA';

    /**
     * Client id.
     *
     * @var string Client id
     */
    protected $clientId;

    /**
     * @var string Secret key
     */
    protected $clientSecret;

    /**
     * @var string Redirect uri
     */
    protected $redirectUri = '/test';

    /**
     * @var string Code
     */
    protected $code;

    /**
     * @var string Token type
     */
    protected $tokenType = 'bearer';

    /**
     * @var string Access token
     */
    protected $accessToken;

    /**
     * @var string Refresh token
     */
    protected $refreshToken;

    /**
     * @var string User id
     */
    protected $userId;

    /**
     * @var integer Token expires in
     */
    protected $expiresIn = 10800;

    /**
     * @var string Token scope
     */
    protected $scope = 'write read';

    /**
     * @var string Token json
     */
    protected $jsonToken;

    /**
     * @var string User json
     */
    protected $jsonUser;

    /**
     * @var integer Status code
     */
    protected $status = 400;

    /**
     * @var string Error
     */
    protected $error = 'invalid_grant';

    /**
     * @var string Error message
     */
    protected $message = 'Redirect URI does not match the original';

    /**
     * @var string Error json
     */
    protected $jsonError;

    /**
     * @var \Docta\OAuth2\Client\Provider\MercadoLibre Provider
     */
    protected $provider;

    /**
     * Setup test.
     *
     * @return void
     */
    protected function setUp()
    {
        $this->clientId = uniqid('id_', true);
        $this->clientSecret = uniqid('sk_', true);
        $this->code = uniqid('cd_', true);
        $this->accessToken = uniqid('at_', true);
        $this->refreshToken = uniqid('rt_', true);
        $this->userId = uniqid('ui_', true);

        $this->jsonToken = json_encode([
            'token_type' => $this->tokenType,
            'access_token' => $this->accessToken,
            'refresh_token' => $this->refreshToken,
            'user_id' => $this->userId,
            'expires_in' => $this->expiresIn,
            'scope' => $this->scope
        ]);

        $this->jsonUser = json_encode([
            'id' => $this->userId
        ]);

        $this->jsonError = json_encode([
            'status' => $this->status,
            'error' => $this->error,
            'message' => $this->message
        ]);

        $this->provider = new MercadoLibreProvider([
            'authSite' => $this->authSite,
            'clientId' => $this->clientId,
            'clientSecret' => $this->clientSecret,
            'redirectUri' => $this->redirectUri,
        ]);
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
     * testGetBaseAuthorizationUrl test.
     *
     * @return void
     */
    public function testGetBaseAuthorizationUrl()
    {
        $expected = 'https://auth.mercadolibre.com.ar/authorization';
        $actual = $this->provider->getBaseAuthorizationUrl();
        $this->assertEquals($expected, $actual);
    }

    /**
     * testGetBaseAuthorizationUrl test.
     *
     * @return void
     */
    public function testGetBaseAccessTokenUrl()
    {
        $expected = 'https://api.mercadolibre.com/oauth/token';
        $actual = $this->provider->getBaseAccessTokenUrl();
        $this->assertEquals($expected, $actual);
    }

    /**
     * testGetAuthorizationUrl test.
     *
     * @return void
     */
    public function testGetAuthorizationUrl()
    {
        $actual = $this->provider->getAuthorizationUrl();
        $actual = parse_url($actual);
        $this->assertArrayHasKey('scheme', $actual);
        $this->assertArrayHasKey('host', $actual);
        $this->assertArrayHasKey('path', $actual);
        $this->assertArrayHasKey('query', $actual);
        $this->assertEquals('https', $actual['scheme']);
        $this->assertEquals('auth.mercadolibre.com.ar', $actual['host']);
        $this->assertEquals('/authorization', $actual['path']);

        parse_str($actual['query'], $query);
        $this->assertArrayHasKey('response_type', $query);
        $this->assertArrayHasKey('client_id', $query);
        $this->assertArrayHasKey('redirect_uri', $query);
        $this->assertArrayHasKey('state', $query);
        $this->assertEquals('code', $query['response_type']);
        $this->assertEquals($this->clientId, $query['client_id']);
        $this->assertEquals($this->redirectUri, $query['redirect_uri']);
        $this->assertEquals($this->provider->getState(), $query['state']);
        $this->assertArrayNotHasKey('approval_prompt', $query);
        $this->assertArrayNotHasKey('scope', $query);
    }

    /**
     * testGetAccessToken
     *
     * @return void
     */
    public function testGetAccessToken()
    {
        $response = Mockery::mock('Psr\Http\Message\ResponseInterface');
        $response->shouldReceive('getHeader')->andReturn(['content-type' => 'json']);
        $response->shouldReceive('getBody')->andReturn($this->jsonToken);
        $client = Mockery::mock('GuzzleHttp\ClientInterface');
        $client->shouldReceive('send')->times(1)->andReturn($response);

        $this->provider->setHttpClient($client);
        $token = $this->provider->getAccessToken('authorization_code', ['code' => $this->code]);
        $this->assertEquals($this->accessToken, $token->getToken());
        $this->assertEquals($this->refreshToken, $token->getRefreshToken());
        $this->assertEquals($this->userId, $token->getResourceOwnerId());
        $this->assertInternalType('int', $token->getExpires());
        $this->assertGreaterThan(time(), $token->getExpires());
    }

    /**
     * testGetResourceOwnerDetailsUrl test.
     *
     * @return void
     */
    public function testGetResourceOwnerDetailsUrl()
    {
        $expected = 'https://api.mercadolibre.com/users/me';
        $actual = $this->provider->getResourceOwnerDetailsUrl();
        $this->assertEquals($expected, $actual);
    }

    /**
     * testGetAuthenticatedRequestUrl test.
     *
     * @return void
     */
    public function testGetAuthenticatedRequestUrl()
    {
        $expected = sprintf('https://api.mercadolibre.com/users/me?access_token=%s', $this->accessToken);

        $response = Mockery::mock('Psr\Http\Message\ResponseInterface');
        $response->shouldReceive('getHeader')->andReturn(['content-type' => 'json']);
        $response->shouldReceive('getBody')->andReturn($this->jsonToken);
        $client = Mockery::mock('GuzzleHttp\ClientInterface');
        $client->shouldReceive('send')->times(1)->andReturn($response);

        $this->provider->setHttpClient($client);
        $url = $this->provider->getResourceOwnerDetailsUrl();
        $token = $this->provider->getAccessToken('authorization_code', ['code' => $this->code]);
        $actual = $this->provider->getAuthenticatedRequestUrl($url, $token);
        $this->assertEquals($expected, $actual);
    }

    /**
     * testGetAuthenticatedRequest test.
     *
     * @return void
     */
    public function testGetAuthenticatedRequest()
    {
        $response = Mockery::mock('Psr\Http\Message\ResponseInterface');
        $response->shouldReceive('getHeader')->andReturn(['content-type' => 'json']);
        $response->shouldReceive('getBody')->andReturn($this->jsonToken);
        $client = Mockery::mock('GuzzleHttp\ClientInterface');
        $client->shouldReceive('send')->times(1)->andReturn($response);

        $this->provider->setHttpClient($client);
        $token = $this->provider->getAccessToken('authorization_code', ['code' => $this->code]);

        $response = Mockery::mock('Psr\Http\Message\ResponseInterface');
        $response->shouldReceive('getHeader')->andReturn(['content-type' => 'json']);
        $response->shouldReceive('getBody')->andReturn($this->jsonUser);
        $client = Mockery::mock('GuzzleHttp\ClientInterface');
        $client->shouldReceive('send')->times(1)->andReturn($response);

        $this->provider->setHttpClient($client);
        $user = $this->provider->getResourceOwner($token);
        $userArray = json_decode($this->jsonUser, true);
        $this->assertEquals($this->userId, $user->getId());
        $this->assertEquals($userArray, $user->toArray());
    }

    /**
     * testCheckResponse test.
     *
     * @return void
     */
    public function testCheckResponse()
    {
        $this->expectException(IdentityProviderException::class);
        $this->expectExceptionCode($this->status);
        $this->expectExceptionMessage($this->message);

        $response = Mockery::mock('Psr\Http\Message\ResponseInterface');
        $response->shouldReceive('getStatusCode')->andReturn(400);
        $response->shouldReceive('getHeader')->andReturn(['content-type' => 'json']);
        $response->shouldReceive('getBody')->andReturn($this->jsonError);
        $client = Mockery::mock('GuzzleHttp\ClientInterface');
        $client->shouldReceive('send')->times(1)->andReturn($response);

        $this->provider->setHttpClient($client);
        $token = $this->provider->getAccessToken('authorization_code', ['code' => $this->code]);
    }
}
