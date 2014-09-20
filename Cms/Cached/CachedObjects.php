<?php

namespace Cms\Cached;


/**
 * Class CachedObjects
 * @package Cms\Cached
 * @author  Alex Shilkin <shilkin.alexander@gmail.com>
 */
class CachedObjects extends BaseCached
{
	protected $keys = [];


	/**
	 * @param array $keys
	 */
	public function __construct(array $keys)
	{
		$this->keys = $keys;
	}

	/**
	 * @param  int $max_wait
	 *
	 * @return mixed
	 */
	public function get($max_wait=1)
	{
		$provider = $this->provider;
		$key_provider = $this->key_provider;

		if (! $this->isCacheEnabled()) {
			return $provider();
		}

		$one_shot_provider = function () use (&$provider) {
			if ($provider instanceof \Closure) {
				$provider = $provider();
			}

			return $provider;
		};

		$keys = [];
		foreach ($this->keys as $data_key) {
			$cache_key = $key_provider($data_key);
			$keys[$cache_key] = $data_key;
		}

		$cache = $this->getCacheEngine();

		/**
		 * В единственном случае - если не используются теги
		 * можно использовать getMulti, но если результат
		 * не совпадет по количеству с ожидаемым, то обработка
		 * будет построчной, поскольку необходимо инвалидировать данные
		 */
		if (empty($this->tags)) {
			$cache_keys = array_keys($keys);
			$data = $cache->getAdapter()->getMulti($cache_keys);

			if (count($data) == count($keys)) {
				$result = [];

				foreach ($data as $cache_key => &$val) {
					$data_key = $keys[$cache_key];
					$result[$data_key] = $val;
				}

				return $result;
			}
		}

		$data = [];
		foreach ($keys as $cache_key => $data_key) {
			$row = $cache
				->cachedObject($cache_key)
				->tags($this->tags)
				->expire($this->expire)
				->dataProvider(function () use ($data_key, $one_shot_provider) {
					$data = $one_shot_provider();

					return isset($data[$data_key]) ? $data[$data_key] : null;
				})
				->get();

			if ($row !== null) {
				$data[$data_key] = $row;
			}
		}

		return $data;
	}
}
