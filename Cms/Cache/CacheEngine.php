<?php

namespace Cms\Cache;

use Cms\Cache\Engine\EngineInterface,
	Cms\Cache\Engine\LocksEngine;

use Cms\Cache\Adapter\CacheAdapterAccessor;

use Cms\Cached\CachedObject,
	Cms\Cached\CachedObjects;


/**
 * Class CacheEngine
 * @package Cms\Cache
 * @author  Alex Shilkin <shilkin.alexander@gmail.com>
 */
class CacheEngine implements EngineInterface
{
	use CacheAdapterAccessor;
	use LocksEngine;


	/**
	 * @param  string $key
	 * @param  bool   &$expired
	 * @param  bool   $wait_release
	 * @param  int    $max_wait
	 *
	 * @return mixed|bool
	 */
	public function load($key, &$expired=null, $wait_release=false, $max_wait=1)
	{
		if ($wait_release) {
			$this->waitRelease($key, $max_wait);
		}

		$result = $this->getAdapter()->get($key);
		$expired = ! $this->isValidData($result);

		return
			is_array($result) && array_key_exists(self::DATA_KEY, $result) ?
				$result[self::DATA_KEY] :
				$result;
	}

	/**
	 * @param  string $key
	 * @param  mixed  $value
	 * @param  int    $expire
	 * @param  array  $tags
	 * @param  int    $max_lock
	 *
	 * @return bool
	 */
	public function save($key, $value, $expire=0, array $tags=[], $max_lock=1)
	{
		if (! $this->lock($key, $max_lock)) {
			return false;
		}

		if ($value instanceof \Closure) {
			$value = $value();
		}

		if (! empty($tags)) {
			$value = [
				self::DATA_KEY   => $value,
				self::TAGS_KEY   => $this->getTagsWithVersion($tags),
				self::EXPIRE_KEY => $expire > 0 ? time() + $expire : 0,
			];
			$expire = 0;
		}

		$result = $this->getAdapter()->set($key, $value, $expire);

		/*
		 * Проблема: после установки рассчитаного значения мы сохраняем его в кеш и должны снять лок.
		 * Однако если так сделать, то соседи, пытающиеся занять лок для записи получат его и пойдут считать по новой
		 * Если установить небольшой таймаут, то это поможет вывести поток из ветки сохранения и предотвратит
		 * повторные срабатывания на сохранение
		 */
		usleep(1000);

		$this->release($key);

		return $result;
	}

	/**
	 * @param  string $key
	 *
	 * @return bool
	 */
	public function expired($key)
	{
		return ! $this->isValidData( $this->getAdapter()->get($key) );
	}

	/**
	 * @param array $tags
	 */
	public function invalidateTags(array $tags)
	{
		$data = [];
		$new_version = microtime();

		foreach ($tags as $tag) {
			$key = $this->getKeyForTag($tag);
			$data[$key] = $new_version;
		}

		$this->getAdapter()->setMulti($data);
	}

	/**
	 * @param  string $key
	 * @param  int    $offset
	 * @param  int    $initial
	 *
	 * @return bool|int
	 */
	public function inc($key, $offset=1, $initial=0)
	{
		return $this->getAdapter()->inc($key, $offset, $initial);
	}

	/**
	 * @param  string $key
	 * @param  int $offset
	 * @param  int $initial
	 *
	 * @return bool|int
	 */
	public function dec($key, $offset=1, $initial=0)
	{
		return $this->getAdapter()->dec($key, $offset, $initial);
	}

	/**
	 * @param  array $keys
	 *
	 * @return bool
	 */
	public function delete(array $keys)
	{
		return $this->getAdapter()->delete($keys);
	}

	/**
	 * @return bool
	 */
	public function flush()
	{
		return $this->getAdapter()->flush();
	}

	/**
	 * @param  string $key
	 *
	 * @return \Cms\Cached\CachedInterface
	 */
	public function cachedObject($key)
	{
		$cached_obj = new CachedObject($key);
		$cached_obj->setCacheEngine($this);

		return $cached_obj;
	}

	/**
	 * @param  array $keys
	 *
	 * @return \Cms\Cached\CachedInterface
	 */
	public function cachedObjects(array $keys)
	{
		$cached_objs = new CachedObjects($keys);
		$cached_objs->setCacheEngine($this);

		return $cached_objs;
	}


	/**
	 * @param  mixed $data
	 *
	 * @return bool
	 */
	protected function isValidData($data)
	{
		if ($data === false) {
			return false;
		}

		/*
		 * если полученные данные - не массив, значит управление его жизнью
		 * производится напрямую сервером кеширования
		 */
		if (! is_array($data)) {
			return true;
		}

		// если установлен срок жизни данных - проверяем его
		$expire = isset($data[self::EXPIRE_KEY]) ? $data[self::EXPIRE_KEY] : 0;
		if ($expire != 0 && $expire < time()) {
			return false;
		}

		// если установлены теги - проверим их время жизни
		$tags = isset($data[self::TAGS_KEY]) ? $data[self::TAGS_KEY] : [];

		return $this->isValidTags($tags);
	}

	/**
	 * @param  array $tags
	 *
	 * @return bool
	 */
	protected function isValidTags(array $tags)
	{
		if (empty($tags)) {
			return true;
		}

		$keys = [];
		foreach ($tags as $tag => $version) {
			$keys []= $this->getKeyForTag($tag);
		}

		$tags_status = $this->adapter->getMulti($keys);
		foreach ($keys as $key) {
			if (! array_key_exists($key, $tags_status)) {
				return false;
			}

			$tag = $this->getTagFromKey($key);
			if ($tags[$tag] !== $tags_status[$key]) {
				return false;
			}
		}

		return true;
	}

	/**
	 * @param  array $tags
	 *
	 * @return array
	 */
	protected function getTagsWithVersion(array $tags)
	{
		if (empty($tags)) {
			return [];
		}

		$result = [];
		$add_tags = [];
		$new_version = microtime();

		$keys = [];
		foreach ($tags as $tag) {
			$keys []= $this->getKeyForTag($tag);
		}

		$tags_status = $this->adapter->getMulti($keys);
		foreach ($tags as $tag) {
			$version = $new_version;

			$key = $this->getKeyForTag($tag);
			if (array_key_exists($key, $tags_status)) {
				$version = $tags_status[$key];
			}
			else {
				$add_tags[$key] = $version;
			}

			$result[$tag] = $version;
		}

		if (! empty($add_tags)) {
			$this->adapter->setMulti($add_tags);
		}

		return $result;
	}

	/**
	 * @param  string $key
	 *
	 * @return string
	 */
	protected function getLockKey($key)
	{
		return
			strpos($key, self::LOCK_KEY_PREFIX) === 0 ?
				$key :
				self::LOCK_KEY_PREFIX . $key;
	}

	/**
	 * @param  string $tag
	 *
	 * @return string
	 */
	protected function getKeyForTag($tag)
	{
		return
			strpos($tag, self::TAGS_KEY_PREFIX) === 0 ?
				$tag :
				self::TAGS_KEY_PREFIX . $tag;
	}

	/**
	 * @param  string $key
	 *
	 * @return string
	 */
	protected function getTagFromKey($key)
	{
		return
			strpos($key, self::TAGS_KEY_PREFIX) === 0 ?
				substr($key, strlen(self::TAGS_KEY_PREFIX)) :
				$key;
	}
}
