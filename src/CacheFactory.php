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

/**
 *
 */
class CacheFactory{

    /**
     * ドライバを生成する
     *
     * @param   string  $driver
     * @param   mixed[] $options
     *
     * @return  Driver\AbstractDriver
     *
     * @throws  \InvalidArgumentException
     */
    public function create(string $driver, array $options = []){
        if(!class_exists($driver)){
            throw new \InvalidArgumentException();
        }

        $driver   = new $driver($options);

        if(!($driver instanceof Driver\AbstractDriver)){
            throw new \InvalidArgumentException();
        }

        return $driver;
    }

    /**
     * ファイルキャッシュドライバーを生成する
     *
     * @param   mixed[] $options
     *
     * @return  Driver\FileSystemDriver
     */
    public function createFileSystemDriver(array $options = []){
        return $this->create(Driver\FileSystemDriver::class, $options);
    }
}