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
namespace Fratily\Cache\SimpleCache;

use Psr\SimpleCache\CacheInterface;
use Psr\Cache\{
    CacheItemPoolInterface,
    CacheException,
    InvalidArgumentException
};

/**
 *
 */
class SimpleCacheAdapter implements CacheInterface{

    /**
     * @var CacheItemPoolInterface
     */
    protected $pool;

    public function __construct(CacheItemPoolInterface $pool){
        $this->pool = $pool;
    }

    /**
     * {@inheritdoc}
     */
    public function get($key, $default = null){
        try{
            $item   = $this->pool->getItem($key);
        }catch(InvalidArgumentException $e){
            throw new Exception\InvalidArgumentException($e->getMessage(), $e->getCode(), $e);
        }catch(CacheException $e){
            throw new Exception\CacheItemPoolException(null, 0, $e);
        }

        return $item->isHit() ? $item->get() : $default;
    }

    /**
     * {@inheritdoc}
     */
    public function set($key, $value, $ttl = null){
        try{
            $item   = $this->pool->getItem($key);

            $item->set($value);
            $item->expiresAfter($ttl);

            $result = $this->pool->save($item);
        }catch(InvalidArgumentException $e){
            throw new Exception\InvalidArgumentException($e->getMessage(), $e->getCode(), $e);
        }catch(CacheException $e){
            throw new Exception\CacheItemPoolException(null, 0, $e);
        }

        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function delete($key){
        try{
            $result = $this->pool->deleteItem($key);
        }catch(InvalidArgumentException $e){
            throw new Exception\InvalidArgumentException($e->getMessage(), $e->getCode(), $e);
        }catch(CacheException $e){
            throw new Exception\CacheItemPoolException(null, 0, $e);
        }

        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function clear(){
        try{
            $result = $this->pool->clear();
        }catch(CacheException $e){
            throw new Exception\CacheItemPoolException(null, 0, $e);
        }

        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function getMultiple($keys, $default = null){
        try{
            $items  = array_map(function($item) use ($default){
                return $item->isHit() ? $item->get() : $default;
            }, $this->pool->getItems($keys));
        }catch(InvalidArgumentException $e){
            throw new Exception\InvalidArgumentException($e->getMessage(), $e->getCode(), $e);
        }catch(CacheException $e){
            throw new Exception\CacheItemPoolException(null, 0, $e);
        }

        return $items;
    }

    /**
     * {@inheritdoc}
     */
    public function setMultiple($values, $ttl = null){
        try{
            foreach($this->pool->getItems(array_keys($values)) as $item){
                $item->set($values[$item->getKey()]);
                $item->expiresAfter($ttl);

                $this->pool->saveDeferred($item);
            }

            $result = $this->pool->commit();
        }catch(InvalidArgumentException $e){
            throw new Exception\InvalidArgumentException($e->getMessage(), $e->getCode(), $e);
        }catch(CacheException $e){
            throw new Exception\CacheItemPoolException(null, 0, $e);
        }

        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function deleteMultiple($keys){
        try{
            $result = $this->pool->deleteItems($keys);
        }catch(InvalidArgumentException $e){
            throw new Exception\InvalidArgumentException($e->getMessage(), $e->getCode(), $e);
        }catch(CacheException $e){
            throw new Exception\CacheItemPoolException(null, 0, $e);
        }

        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function has($key){
        try{
            $result = $this->pool->getItem($key)->isHit();
        }catch(InvalidArgumentException $e){
            throw new Exception\InvalidArgumentException($e->getMessage(), $e->getCode(), $e);
        }catch(CacheException $e){
            throw new Exception\CacheItemPoolException(null, 0, $e);
        }

        return $result;
    }
}