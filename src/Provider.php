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
namespace Docta\MercadoLibre\OAuth2\Client;

use GuzzleHttp\Psr7\Uri;
use GuzzleHttp\Psr7\UriResolver;
use InvalidArgumentException;
use League\OAuth2\Client\Provider\AbstractProvider;
use League\OAuth2\Client\Provider\Exception\IdentityProviderException;
use League\OAuth2\Client\Token\AccessToken;
use League\OAuth2\Client\Tool\BearerAuthorizationTrait;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * Represents a service provider.
 */
class Provider extends AbstractProvider
{
    use BearerAuthorizationTrait;

    /**
     * @var array
     */
    private $authSites = [
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
     * @var string
     */
    private $authSite;

    /**
     * @var string
     */
    protected $authUrl;

    /**
     * @var string
     */
    protected $apiUrl = 'https://api.mercadolibre.com';

    /**
     * @var string
     */
    protected $clientId;

    /**
     * @var string
     */
    protected $clientSecret;

    /**
     * @var string
     */
    protected $redirectUri;

    /**
     * Constructor.
     *
     * @param array $options
     * @param array $collaborators
     */
    public function __construct(array $options = [], array $collaborators = [])
    {
        // Check options
        $this->assertRequiredOptions($options);
        $this->assertAuthSite($options['authSite']);
        $this->authSite = $options['authSite'];
        $this->authUrl = $this->authSites[$this->authSite];

        // Build and filter options
        $required = $this->getRequiredOptions();
        $configured = array_intersect_key($options, array_flip($required));

        // Set options
        foreach ($configured as $key => $value) {
            $this->$key = $value;
        }

        // Apply the parent constructor
        $options = array_diff_key($options, $configured);
        parent::__construct($options, $collaborators);
    }

    /**
     * Returns all options that are required.
     *
     * @return array
     */
    protected function getRequiredOptions()
    {
        return [
            'authSite',
            'clientId',
            'clientSecret',
            'redirectUri'
        ];
    }

    /**
     * Verifies that all required options have been passed.
     *
     * @param array $options
     * @return void
     * @throws InvalidArgumentException
     */
    private function assertRequiredOptions(array $options)
    {
        $required = $this->getRequiredOptions();
        $missing = array_diff_key(array_flip($required), $options);

        if (!empty($missing)) {
            $missing = array_keys($missing);
            $missing = implode(', ', $missing);
            $template = 'Required options not defined: %s';
            $message = sprintf($template, $missing);
            throw new InvalidArgumentException($message);
        }
    }

    /**
     * Verifies that the `authSite` passed is valid.
     *
     * @param string $authSite
     * @return void
     * @throws InvalidArgumentException
     */
    private function assertAuthSite($authSite)
    {
        if (!array_key_exists($authSite, $this->authSites)) {
            $validValues = array_keys($this->authSites);
            $validValues = implode(', ', $validValues);
            $template = 'Valid values for authSite are only: %s';
            $message = sprintf($template, $validValues);
            throw new InvalidArgumentException($message);
        }
    }

    /**
     * Build and returns the URL for API requests.
     *
     * @param string $path
     * @param array $query
     * @return string
     */
    public function getApiUrl($path = '/', array $query = [])
    {
        $base = new Uri($this->apiUrl);
        $path = new Uri($path);

        foreach ($query as $key => $value) {
            $path = Uri::withQueryValue($path, $key, $value);
        }

        return (string) UriResolver::resolve($base, $path);
    }

    /**
     * Returns the base URL for authorizing a client.
     *
     * @return string
     */
    public function getBaseAuthorizationUrl()
    {
        $base = new Uri($this->authUrl);
        $path = new Uri('/authorization');
        return (string) UriResolver::resolve($base, $path);
        ;
    }

    /**
     * Returns the base URL for requesting an access token.
     *
     * @param array|null $params
     * @return string
     */
    public function getBaseAccessTokenUrl(array $params = null)
    {
        return $this->getApiUrl('/oauth/token');
    }

    /**
     * Returns the URL for requesting the resource owner's details.
     *
     * @param AccessToken|null $token
     * @return string
     */
    public function getResourceOwnerDetailsUrl(AccessToken $token = null)
    {
        return $this->getApiUrl('/users/me');
    }

    /**
     * Returns the default scopes used by this provider.
     *
     * @return null
     */
    public function getDefaultScopes()
    {
        return null;
    }

    /**
     * Returns the key used in the access token
     * response to identify the resource owner.
     *
     * @return string Resource owner identifier key
     */
    protected function getAccessTokenResourceOwnerId()
    {
        return 'user_id';
    }

    /**
     * Returns an authenticated PSR-7 request instance.
     *
     * @todo Use only authorization by HTTP header and remove the query parameter.
     *
     * @param string $method
     * @param string $url
     * @param AccessToken|string $token
     * @param array $options Any of `headers`, `body`, and `protocolVersion`.
     * @return RequestInterface
     */
    public function getAuthenticatedRequest($method, $url, $token, array $options = [])
    {
        $url = Uri::withQueryValue(new Uri($url), 'access_key', (string) $token);
        return $this->createRequest($method, (string) $url, $token, $options);
    }


    /**
     * Checks a provider response for errors.
     *
     * @throws IdentityProviderException
     * @param ResponseInterface $response
     * @param array|string $data Parsed response data
     * @return void
     */
    protected function checkResponse(ResponseInterface $response, $data)
    {
        if (isset($data['error'])) {
            throw new IdentityProviderException((string) $data['message'], (int) $data['status'], $data);
        }
    }

    /**
     * Generates a resource owner object from a
     * successful resource owner details request.
     *
     * @param array $response
     * @param AccessToken $token
     * @return ResourceOwner
     */
    protected function createResourceOwner(array $response, AccessToken $token)
    {
        return new ResourceOwner($response);
    }
}
