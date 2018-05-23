<?php
/**
 * MercadoLibre Provider for OAuth 2.0 Client
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE file
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright Copyright (c) 2018 Lucas Banegas <lucasconobanegas@gmail.com>
 * @license https://opensource.org/licenses/MIT MIT License
 * @link https://github.com/docta/oauth2-mercadolibre
 */
namespace Docta\OAuth2\Client\Provider;

use GuzzleHttp\Psr7\Uri;
use GuzzleHttp\Psr7\UriResolver;
use League\OAuth2\Client\Provider\AbstractProvider;
use League\OAuth2\Client\Provider\Exception\IdentityProviderException;
use League\OAuth2\Client\Token\AccessToken;
use Psr\Http\Message\ResponseInterface;

/**
 * Represents a service provider (authorization server).
 *
 * @link http://tools.ietf.org/html/rfc6749#section-1.1 Roles (RFC 6749, §1.1)
 */
class MercadoLibreProvider extends AbstractProvider
{
    /**
     * @var string Key used in a token response to identify the resource owner.
     */
    const ACCESS_TOKEN_RESOURCE_OWNER_ID = 'user_id';

    /**
     * @var string Site
     */
    protected $site;

    /**
     * @var array authSites
     */
    protected $authSites = [
        'MLA' => 'https://auth.mercadolibre.com.ar',
        'MLB' => 'https://auth.mercadolivre.com.br',
        'MCO' => 'https://auth.mercadolibre.com.co',
        'MCR' => 'https://auth.mercadolibre.com.cr',
        'MEC' => 'https://auth.mercadolibre.com.ec',
        'MLC' => 'https://auth.mercadolibre.cl',
        'MLM' => 'https://auth.mercadolibre.com.mx',
        'MLU' => 'https://auth.mercadolibre.com.uy',
        'MLV' => 'https://auth.mercadolibre.com.ve',
        'MPA' => 'https://auth.mercadolibre.com.pa',
        'MPE' => 'https://auth.mercadolibre.com.pe',
        'MPT' => 'https://auth.mercadolibre.com.pt',
        'MRD' => 'https://auth.mercadolibre.com.do'
    ];

    /**
     * @var string authSite
     */
    protected $authSite;

    /**
     * @var string apiUrl
     */
    protected $apiUrl = 'https://api.mercadolibre.com';

    /**
     * Returns the base URL for authorizing a client.
     *
     * @return string Base authorization URL
     */
    public function getBaseAuthorizationUrl()
    {
        return sprintf('%s/authorization', $this->authSites[$this->authSite]);
    }

    /**
     * Returns the base URL for requesting an access token.
     *
     * @param array $params
     * @return string
     */
    public function getBaseAccessTokenUrl(array $params = [])
    {
        return sprintf('%s/oauth/token', $this->apiUrl);
    }

    /**
     * Builds the authorization URL.
     *
     * @param array $options
     * @return string Authorization URL
     */
    public function getAuthorizationUrl(array $options = [])
    {
        $options['approval_prompt'] = null;
        return parent::getAuthorizationUrl($options);
    }

    /**
     * Returns the default scopes used by this provider.
     *
     * @return null
     */
    protected function getDefaultScopes()
    {
        return null;
    }

    /**
     * Returns the URL for requesting the resource owner's details.
     *
     * @param \League\OAuth2\Client\Token\AccessToken|null $token
     * @return string
     */
    public function getResourceOwnerDetailsUrl(AccessToken $token = null)
    {
        return sprintf('%s/users/me', $this->apiUrl);
    }

    /**
     * Returns the modified url for authenticated requests.
     *
     * @param  string $url
     * @param  \League\OAuth2\Client\Token\AccessToken|string $token
     * @return string
     */
    public function getAuthenticatedRequestUrl($url, $token)
    {
        $url = UriResolver::resolve(new Uri($this->apiUrl), new Uri($url));
        return (string) Uri::withQueryValue($url, 'access_token', $token->getToken());
    }

    /**
     * Returns an authenticated request instance.
     *
     * @param  string $method
     * @param  string $url
     * @param  \League\OAuth2\Client\Token\AccessToken|string $token
     * @param  array $options
     * @return \GuzzleHttp\Psr7\Request
     */
    public function getAuthenticatedRequest($method, $url, $token, array $options = [])
    {
        $url = $this->getAuthenticatedRequestUrl($url, $token);
        return $this->createRequest($method, $url, $token, $options);
    }

    /**
     * Checks a provider response for errors.
     *
     * @param \Psr\Http\Message\ResponseInterface $response
     * @param string|array $data Parsed response data
     * @throws \League\OAuth2\Client\Provider\Exception\IdentityProviderException
     * @return void
     */
    protected function checkResponse(ResponseInterface $response, $data)
    {
        if (isset($data['error'])) {
            $message = $data['message'] ?: $response->getReasonPhrase();
            $code = $data['status'] ?: $response->getReasonPhrase();
            throw new IdentityProviderException((string) $message, (int) $code, (array) $response);
        }
    }

    /**
     * Generates a resource owner object from a successful resource owner
     * details request.
     *
     * @param array $response
     * @param \League\OAuth2\Client\Token\AccessToken $token
     * @return \Docta\OAuth2\Client\Provider\MercadoLibreResourceOwner
     */
    protected function createResourceOwner(array $response, AccessToken $token)
    {
        return new MercadoLibreResourceOwner($response);
    }
}
