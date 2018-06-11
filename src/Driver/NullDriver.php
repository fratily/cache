<?php
/**
 * FratilyPHP Cache
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE
 * Redistributions of files must retain the above copyright notice.
 *
 * @author      Kento Oka <kento-oka@kentoka.com>
 * @copyright   (c) Kento Oka
 * @license     MIT
 * @since       1.0.0
 */
namespace Fratily\Cache\Driver;

use Psr\Cache\CacheItemInterface;

/**
 *
 */
class NullDriver extends AbstractDriver{

    /**
     * @var string
     */
    private $path;

    /**
     * @var int
     */
    private $filePermission;

    /**
     * @var int
     */
    private $dirPermission;

    /**
     * @var int
     */
    private $dirSplit;

    /**
     * @var string
     */
    private $keyHashAlgo;

    public function __construct(array $options = []){
        parent::__construct($options);

        $this->path             = $this->getOption("path", sys_get_temp_dir());
        $this->filePermission   = $this->getOption("filePermission", 0660);
        $this->dirPermission    = $this->getOption("dirPermission", 0770);
        $this->dirSplit         = $this->getOption("dirSplit", 2);
        $this->keyHashAlgo      = $this->getOption("keyHashAlgo", "md5");

        if(!is_dir($this->path)){
            throw new \InvalidArgumentException();
        }else{
            $this->path = realpath($this->path);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function save(CacheItemInterface $item){
        return true;
    }

    /**
     * キャッシュアイテムを削除する
     *
     * @param   string  $key
     *
     * @return  bool
     */
    protected function delete(string $key){
        return true;
    }
}