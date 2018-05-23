# MercadoLibre Provider for OAuth 2.0 Client

[![Latest version](https://img.shields.io/github/release/docta/oauth2-mercadolibre.svg?style=flat-square)](https://github.com/docta/oauth2-mercadolibre/releases)
[![Software license](https://img.shields.io/badge/license-MIT-brightgreen.svg?style=flat-square)](LICENSE)
[![Build status](https://img.shields.io/travis/docta/oauth2-mercadolibre/master.svg?style=flat-square)](https://travis-ci.org/docta/oauth2-mercadolibre)
[![Coverage status](https://img.shields.io/scrutinizer/coverage/g/docta/oauth2-mercadolibre.svg?style=flat-square)](https://scrutinizer-ci.com/g/docta/oauth2-mercadolibre/code-structure)
[![Quality score](https://img.shields.io/scrutinizer/g/docta/oauth2-mercadolibre.svg?style=flat-square)](https://scrutinizer-ci.com/g/docta/oauth2-mercadolibre)

This package provides MercadoLibre OAuth 2.0 support for the PHP League's [OAuth 2.0 Client](https://github.com/thephpleague/oauth2-client).

## Install

Via [Composer](https://getcomposer.org/)

```bash
$ composer require docta/oauth2-mercadolibre
```

## Configure

The constructor of this provider requires the following four values, passed in an array as shown below:

```php
$provider = new \Docta\OAuth2\Client\Provider\MercadoLibreProvider([
    'authSite'     => '{mercadolibre-auth-site}',
    'clientId'     => '{mercadolibre-client-id}',
    'clientSecret' => '{mercadolibre-client-secret}',
    'redirectUri'  => 'https://example.com/oauth/'
]);
```

### `authSite`

In general, the configuration is the same as that required by all providers. However, in MercadoLibre, the initial code request depends on the country in which the application was registered. For this reason, to the usual configuration, the `authSite` key must be added to indicate to the client the country in which the application is registered, and consequently, which is the URL that the client will use to obtain the initial code.

For example, if the application was registered in Argentina, the `authSite` that should be used is `MLA`, and this implies that the initial code will be requested to the server located at `https://auth.mercadolibre.com.ar`. The countries currently available are the following:

| Country    |  authSite | authUrl                            |
|------------|:---------:|------------------------------------|
| Argentina  | **`MLA`** | `https://auth.mercadolibre.com.ar` |
| Brasil     | **`MLB`** | `https://auth.mercadolivre.com.br` |
| Colombia   | **`MCO`** | `https://auth.mercadolibre.com.co` |
| Costa Rica | **`MCR`** | `https://auth.mercadolibre.com.cr` |
| Ecuador    | **`MEC`** | `https://auth.mercadolibre.com.ec` |
| Chile      | **`MLC`** | `https://auth.mercadolibre.cl`     |
| Mexico     | **`MLM`** | `https://auth.mercadolibre.com.mx` |
| Uruguay    | **`MLU`** | `https://auth.mercadolibre.com.uy` |
| Venezuela  | **`MLV`** | `https://auth.mercadolibre.com.ve` |
| Panama     | **`MPA`** | `https://auth.mercadolibre.com.pa` |
| Peru       | **`MPE`** | `https://auth.mercadolibre.com.pe` |
| Portugal   | **`MPT`** | `https://auth.mercadolibre.com.pt` |
| Dominicana | **`MRD`** | `https://auth.mercadolibre.com.do` |

## Usage

### Authorization code grant

The authorization code grant type is the most common grant type used when authenticating users with a third-party service. This grant type utilizes a client (this library), a server (the service provider), and a resource owner (the user with credentials to a protected —or owned— resource) to request access to resources owned by the user. This is often referred to as 3-legged OAuth, since there are three parties involved.

```php
$provider = new \Docta\OAuth2\Client\Provider\MercadoLibreProvider([
    'authSite'     => '{mercadolibre-auth-site}',
    'clientId'     => '{mercadolibre-client-id}',
    'clientSecret' => '{mercadolibre-client-secret}',
    'redirectUri'  => 'https://example.com/oauth/'
]);

/**
 * If we don't have an authorization code then get one.
 */
if (!isset($_GET['code'])) {

    /**
     * Fetch the authorization URL from the provider;
     * this returns the authUrl and generates and
     * applies any necessary parameters (e.g. state).
     */
    $authUrl = $provider->getAuthorizationUrl();

    /**
     * Get the state generated for you and
     * store it to the session.
     */
    $_SESSION['oauth2state'] = $provider->getState();

    /**
     * Redirect the user to the authorization URL.
     */
    header('Location: ' . $authUrl);
    exit;

}

/**
 * Check given state against previously
 * stored one to mitigate CSRF attack.
 */
elseif (empty($_GET['state']) || ($_GET['state'] !== $_SESSION['oauth2state'])) {

    unset($_SESSION['oauth2state']);
    exit('Invalid state');

}

/**
 * If a code is received and the state check is correct.
 */
else {

    try {

        /**
         * Try to get an access token using
         * the authorization code grant.
         */
        $token = $provider->getAccessToken('authorization_code', [
            'code' => $_GET['code']
        ]);

        /**
         * Optional: using the token we can find details
         * about the owner of the resource.
         */
        $owner = $provider->getResourceOwner($token);

        /**
         * We can now make requests to API services.
         */
        $items = $provider->getAuthenticatedRequest('POST', '/items', $token);

    }

    /**
     * Failed to get the access token or user details.
     */
    catch (Exception $e) {

        exit($e->getMessage());

    }

}
```

#### Server-Side flow

In short, this is the process to perform:

[![Server-Side flow](https://raw.githubusercontent.com/docta/oauth2-mercadolibre/master/flow.jpg)](http://developers.mercadolibre.com/server-side/)

### Refresh token grant

Once your application is authorized, you can refresh an expired token using a refresh token rather than going through the entire process of obtaining a brand new token. To do so, simply reuse this refresh token from your data store to request a refresh.

```php
$provider = new \Docta\OAuth2\Client\Provider\MercadoLibreProvider([
    'authSite'     => '{mercadolibre-auth-site}',
    'clientId'     => '{mercadolibre-client-id}',
    'clientSecret' => '{mercadolibre-client-secret}',
    'redirectUri'  => 'https://example.com/oauth/'
]);

/**
 * Get the previously stored token.
 */
$storedToken = getStoredToken();

/**
 * Check if the token has expired.
 */
if ($storedToken->hasExpired()) {

    /**
     * If the token has expired, get a new token.
     */
    $newToken = $provider->getAccessToken('refresh_token', [
        'refresh_token' => $storedToken->getRefreshToken()
    ]);

    /**
     * Store the new token for future checks.
     */
    storeToken($newToken);

}
```

## Important

MercadoLibre has not yet published (perhaps because it was not implemented) a way to pass the Bearer token through HTTP headers. And at least, having tried the usual way, it don't work. For this reason, keep in mind that this provider will automatically add the access token to the query parameters of all requested urls.

For example, if request `https://api.mercadolibre.com/users/me`, this url will be automatically converted to `https://api.mercadolibre.com/users/me?access_token={token}`.

## Contributing

Please see [CONTRIBUTING](https://github.com/docta/oauth2-mercadolibre/blob/master/CONTRIBUTING.md) for details.

## License

The MIT License (MIT). Please see [LICENSE](https://github.com/docta/oauth2-mercadolibre/blob/master/LICENSE) for more information.
