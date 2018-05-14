<?php
/**
 * FratilyPHP Cache
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE
 * Redistributions of files must retain the above copyright notice.
 *
 * @author      Kento Oka <kento.oka@kentoka.com>
 * @copyright   (c) Kento Oka
 * @license     MIT
 * @since       1.0.0
 */
namespace Fratily\Cache\SimpleCache\Exception;

use Psr\SimpleCache\CacheException;

/**
 *
 */
class CacheItemPoolException extends \LogicException implements CacheException{

    const MSG  = "Cache item pool threw an exception.";

    /**
     * Constructor
     *
     * @param   string  $message    [optional]
     * @param   int $code   [optional]
     * @param   \Throwable  $previous   [optional]
     */
    public function __construct(
        string $message = null,
        int $code = 0,
        \Throwable $previous = null
    ){
        parent::__construct($message ?? self::_MSG, $code, $previous);
    }
}