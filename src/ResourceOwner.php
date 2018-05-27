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

use League\OAuth2\Client\Provider\ResourceOwnerInterface;

/**
 * Represents an owner resource.
 */
class ResourceOwner extends ResourceGeneric implements ResourceOwnerInterface
{
    /**
     * Returns the identifier of the owner resource
     *
     * @return string The id value
     */
    public function getId()
    {
        return $this->get('id');
    }
}
