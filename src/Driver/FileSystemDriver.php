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
namespace Fratily\Cache\Driver;

use Fratily\Cache\Item;
use Fratily\Cache\Exception;
use Psr\Cache\CacheItemInterface;

/**
 *
 */
class FileSystemDriver extends AbstractDriver{

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
    private $hashAlgo;

    public function __construct(array $options = []){
        parent::__construct($options);

        $this->path             = $this->getOption("path", sys_get_temp_dir() . DIRECTORY_SEPARATOR . "fratily_cache");
        $this->filePermission   = $this->getOption("filePermission", 0660);
        $this->dirPermission    = $this->getOption("dirPermission", 0770);
        $this->dirSplit         = $this->getOption("dirSplit", 2);
        $this->hashAlgo         = $this->getOption("hashAlgo", "md5");

        if(!is_dir($this->path)){
            if(!mkdir($this->path, $this->dirPermission, true)){
                throw new \LogicException;
            }

            $this->path = realpath($this->path);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function save(CacheItemInterface $item){
        if(!$this->hasItemData($item)){
            $_item  = $item;
            $item   = $this->getItem($item->getKey());

            $item->set($_item->get());
        }

        $key    = hash($this->hashAlgo, $this->getItemData($item, Item::KEY));

        if(0 < $this->dirSplit){
            $path   = $this->path . DIRECTORY_SEPARATOR . substr($key, 0, $this->dirSplit);

            if(is_file($path) || (!is_dir($path) && !mkdir($path, $this->dirPermission, false))){
                return false;
            }

            $path   = $path . DIRECTORY_SEPARATOR . $key;
        }else{
            $path   = $this->path . DIRECTORY_SEPARATOR . $key;
        }

        if(!is_dir($path)){
            if(time() < $this->getItemData($item, Item::EXPIRY_DATE)){
                $put    = file_put_contents($path, json_encode([
                    "v" => serialize($this->getItemData($item, Item::VALUE)),
                    "e" => $this->getItemData($item, Item::EXPIRY_DATE),
                ]));
                $chmod  = $put !== false && chmod($path, $this->filePermission);

                if($chmod){
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * {@inheritdoc}
     */
    protected function initItemData(string $key){
        $key    = hash($this->hashAlgo, $key);

        if(0 < $this->dirSplit){
            $path   = $this->path . DIRECTORY_SEPARATOR
                . substr($key, 0, $this->dirSplit) . DIRECTORY_SEPARATOR . $key;
        }else{
            $path   = $this->path . DIRECTORY_SEPARATOR . $key;
        }

        $result = [
            Item::VALUE         => null,
            Item::HIT           => false,
            Item::EXPIRY_DATE   => null,
        ];

        if(is_file($path)){
            $data   = json_decode(file_get_contents($path));

            if($data instanceof \stdClass && isset($data->v) && isset($data->e)){
                if(is_int($data->e) && time() < $data->e){
                    $result[Item::VALUE]        = unserialize($data->v);
                    $result[Item::HIT]          = true;
                    $result[Item::EXPIRY_DATE]  = $data->e;
                }
            }
        }

        return $result;
    }

    /**
     * {@inheritdoc}
     */
    protected function delete(string $key){
        $key    = hash($this->hashAlgo, $key);
        $dir    = $this->path . DIRECTORY_SEPARATOR . substr($key, 0, 2);
        $path   = $dir . DIRECTORY_SEPARATOR . $key;

        if(is_file($path)){
            unlink($path);
        }

        return true;
    }


    /**
     * {@inheritdoc}
     */
    protected function normalizeOptions(array $options){
        $options    = array_filter($options, function($k){
            return in_array($k, ["path", "filePermission", "dirPermission", "dirSplit", "keyHashAlgo"]);
        }, ARRAY_FILTER_USE_KEY);

        $options["path"]            = $this->normalizePath($options["path"] ?? null);
        $options["filePermission"]  = $this->normalizePermission($options["filePermission"] ?? null);
        $options["dirPermission"]   = $this->normalizePermission($options["dirPermission"] ?? null, true);
        $options["dirSplit"]        = $this->normalizeDirSplit($options["dirSplit"] ?? null);
        $options["hashAlgo"]        = $this->normalizeHashAlgorithm($options["hashAlgo"] ?? null);

        return $options;
    }

    private function normalizePath($path){
        if(!is_string($path) || is_file($path)){
            return null;
        }

        return is_dir($path) ? realpath($path) : $path;
    }

    private function normalizePermission($permission, bool $dir = false){
        if(!is_int($permission)){
            return null;
        }

        // とりあえずこのプログラムの実行者は読み書きできる必要があるよね
        // ディレクトリは実行権限も必要だよね
        return min(0777, max($dir ? 0700 : 0600, $permission));
    }

    private function normalizeDirSplit($split){
        if(!is_int($split) || $split < 0){
            return null;
        }

        return $split;
    }

    private function normalizeHashAlgorithm($algo){
        if(!is_string($algo) || !in_array($algo, hash_algos())){
            return null;
        }

        return $algo;
    }
}