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
use Fratily\Cache\SimpleCache\SimpleCacheAdapter;
use Psr\Cache\CacheItemInterface;
use Psr\Cache\CacheItemPoolInterface;

/**
 *
 */
abstract class AbstractDriver implements CacheItemPoolInterface{

    /**
     * @var mixed[]
     */
    private $options;

    /**
     * @var Item[]
     */
    private $items;

    /**
     * @var \SplObjectStorage
     */
    private $data;

    /**
     * @var \SplQueue
     */
    private $lazySave;

    /**
     * 指定されたキーが正常なものか確認する
     *
     * @param   mixed   $key
     *
     * @return  bool
     */
    public static function validKey($key){
        return is_string($key) && preg_match("`\A[0-9A-Za-z_.]{1,128}\z`", $key);
    }

    /**
     * Constructor
     *
     * @param   mixed[] $options
     */
    public function __construct(array $options = []){
        $this->options  = $this->normalizeOptions($options);
        $this->items    = [];
        $this->data     = new \SplObjectStorage();
        $this->lazySave = new \SplQueue();
    }

    /**
     * PSR-16の形式で使用できるキャッシュインスタンスを取得する
     *
     * @return  SimpleCacheAdapter
     */
    public function getSimpleCache(){
        return new SimpleCacheAdapter($this);
    }

    /**
     * {@inheritdoc}
     */
    public function getItem($key){
        if(!static::validKey($key)){
            throw new Exception\InvalidArgumentException();
        }

        if(!array_key_exists($key, $this->items)){
            $this->items[$key]              = new Item($this);
            $this->data[$this->items[$key]] = $this->initItemData($key) + [
                Item::KEY           => $key,
                Item::VALUE         => null,
                Item::HIT           => false,
                Item::EXPIRY_DATE   => null,
            ];
        }

        if($this->data[$this->items[$key]][Item::EXPIRY_DATE] === null){
            $this->items[$key]->expiresAt(null);
        }

        return $this->items[$key];
    }

    /**
     * {@inheritdoc}
     */
    public function getItems(array $keys = array()){
        $items  = [];

        foreach($keys as $key){
            $items[$key]    = $this->getItem();
        }

        return $items;
    }

    /**
     * {@inheritdoc}
     */
    public function hasItem($key){
        if(!static::validKey($key)){
            throw new Exception\InvalidArgumentException();
        }

        return $this->getItem($key)->isHit();
    }

    /**
     * {@inheritdoc}
     */
    public function deleteItem($key){
        if(!static::validKey($key)){
            throw new Exception\InvalidArgumentException();
        }

        if(array_key_exists($key, $this->items)){
            if($this->delete()){
                $this->data[$this->items[$key]] = [
                    Item::VALUE         => null,
                    Item::HIT           => false,
                    Item::EXPIRY_DATE   => null,
                ];

                return true;
            }
        }

        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function deleteItems(array $keys){
        $result = true;

        foreach($keys as $key){
            $result = $result && $this->deleteItem($key);
        }

        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function clear(){
        return $this->deleteItems(array_keys($this->items));
    }

    /**
     * {@inheritdoc}
     */
    public function saveDeferred(CacheItemInterface $item){
        if(isset($this->data[$item])){
            $this->lazySave->push($item);

            return true;
        }

        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function commit(){
        while(!$this->lazySave->isEmpty()){
            $this->save($this->lazySave->pop());
        }
    }

    /**
     * アイテムデータを取得する
     *
     * @param   Item    $item
     * @param   mixed   $tag
     *
     * @return  mixed
     *
     * @throws  \InvalidArgumentException
     */
    public function getItemData(Item $item, $tag){
        if(!isset($this->data[$item])){
            throw new \InvalidArgumentException();
        }

        if(!in_array($tag, [Item::KEY, Item::VALUE, Item::HIT, Item::EXPIRY_DATE])){
            throw new \InvalidArgumentException();
        }

        return $this->data[$item][$tag];
    }

    public function hasItemData(CacheItemInterface $item){
        return isset($this->data[$item]);
    }

    /**
     * アイテムデータを変更する
     *
     * @param   Item    $item
     * @param   mixed   $tag
     * @param   mixed   $value
     *
     * @return  void
     *
     * @throws  \InvalidArgumentException
     */
    public function setItemData(Item $item, $tag, $value){
        if(!isset($this->data[$item])){
            throw new \InvalidArgumentException();
        }

        if(!in_array($tag, [Item::VALUE, Item::HIT, Item::EXPIRY_DATE])){
            throw new \InvalidArgumentException();
        }

        $data               = $this->data[$item];
        $data[$tag]         = $value;
        $this->data[$item]  = $data;
    }

    /**
     * オプションを取得する
     *
     * @param   string  $key
     * @param   mixed   $default
     *
     * @return  mixed
     */
    protected function getOption(string $key, $default = null){
        return $this->options[$key] ?? $default;
    }

    /**
     * キャッシュアイテムのデータを初期化し取得する
     *
     * このメソッドは指定されたキーのキャッシュアイテムを、
     * ドライバー独自の方法(ファイル,DB,memcache,APC等)で取得する処理を書く。
     *
     * Item::VALUE, Item::HIT(, Item::EXPIRY_DATE)をキーとした連想配列を返す。
     *
     * Item::EXPIRY_DATEの指定がなければデフォルト値が用いられる。
     *
     * @param   string  $key
     *
     * @return  mixed[]
     */
    protected function initItemData(string $key){
        return [
            Item::VALUE => null,
            Item::HIT   => false,
        ];
    }

    /**
     * キャッシュアイテムを削除する
     *
     * @param   string  $key
     *
     * @return  bool
     */
    abstract protected function delete(string $key);

    /**
     * ドライバオプションを正規化する
     *
     * @param   mixed[]
     *
     * @return  mixed[]
     */
    abstract protected function normalizeOptions(array $options);
}