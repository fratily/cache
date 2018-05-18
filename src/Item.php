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
namespace Fratily\Cache;

use Fratily\Cache\Exception;

/**
 *
 */
class Item implements \Psr\Cache\CacheItemInterface{

    const KEY           = "key";
    const VALUE         = "val";
    const EXPIRY_DATE   = "exp_date";
    const HIT           = "hit";

    const DEFAULT_TTL   = "P7D";    // one week

    /**
     * @var Driver\AbstractDriver
     */
    protected $driver;

    /**
     * Constructor
     *
     * @param   string  $key
     * @param   mixed   $value
     * @param   bool    $hit
     */
    public function __construct(Driver\AbstractDriver $driver){
        $this->driver   = $driver;
    }

    /**
     * {@inheritdoc}
     */
    public function getKey(){
        return $this->driver->getItemData($this, self::KEY);
    }

    /**
     * {@inheritdoc}
     */
    public function get(){
        return $this->driver->getItemData($this, self::VALUE);
    }

    /**
     * {@inheritdoc}
     */
    public function isHit(){
        return $this->driver->getItemData($this, self::HIT)
            && time() >= $this->driver->getItemData($this, self::EXPIRY_DATE);
    }

    /**
     * {@inheritdoc}
     */
    public function set($value){
        $this->driver->setItemData($this, self::VALUE, $value);

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function expiresAt($expiration){
        if($expiration === null){
            $expiration = (new \DateTime())->add(new \DateInterval(static::DEFAULT_TTL));
        }

        if($expiration !== null && !($expiration instanceof \DateTimeInterface)){
            throw new Exception\InvalidArgumentException();
        }

        $this->driver->setItemData($this, self::EXPIRY_DATE, $expiration->getTimestamp());

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function expiresAfter($time){
        $sub    = false;

        if(is_int($time)){
            $sub    = $time < 0;
            $time   = new \DateInterval(sprintf("PT%dS", abs($time)));
        }else if($time === null){
            $time   = new \DateInterval(static::DEFAULT_TTL);
        }

        if(!($time instanceof \DateInterval)){
            throw new Exception\InvalidArgumentException();
        }

        $expiration = new \DateTime();
        $expiration = $sub ? $datetime->sub($time) : $datetime->add($time);

        $this->driver->setItemData($this, self::EXPIRY_DATE, $expiration->getTimestamp());

        return $this;
    }
}