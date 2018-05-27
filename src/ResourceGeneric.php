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

use League\OAuth2\Client\Tool\ArrayAccessorTrait;

/**
 * Represents a generic resource.
 */
class ResourceGeneric
{
    use ArrayAccessorTrait;

    /**
     * @var array
     */
    protected $response;

    /**
     * Creates new resource
     *
     * @param array $response The response
     */
    public function __construct(array $response = [])
    {
        $this->response = $response;
    }

    /**
     * Returns a value by key using dot notation
     *
     * @param string $key The key to look
     * @return mixed The value
     */
    public function get($key)
    {
        return $this->getValueByKey($this->response, $key);
    }

    /**
     * Return all data of the resource as an array.
     *
     * @return array The response converted into an array
     */
    public function toArray()
    {
        return $this->response;
    }
}
