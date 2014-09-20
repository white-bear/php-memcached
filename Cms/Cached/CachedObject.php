<?php

namespace Cms\Cached;


/**
 * Class CachedObject
 * @package Cms\Cached
 * @author  Alex Shilkin <shilkin.alexander@gmail.com>
 */
class CachedObject extends BaseCached
{
	/**
	 * @param string $key
	 */
	public function __construct($key)
	{
		$this->key = $key;
	}

	/**
	 * @param  int $max_wait
	 *
	 * @return mixed
	 */
	public function get($max_wait=1)
	{
		$provider = $this->provider;

		if (! $this->isCacheEnabled()) {
			return $provider();
		}

		$cache = $this->getCacheEngine();

		$data = $cache->load($this->key, $expired);
		if ($data === false || $data === null) {
			$cache->save($this->key, $provider, $this->expire, $this->tags, $max_wait);

			$data = $cache->load($this->key, $expired, $wait_release=true, $max_wait);
			if ($data === false || $data === null) {
				return $provider();
			}
		}
		elseif ($expired) {
			$cache->save($this->key, $provider, $this->expire, $this->tags, $max_wait);
		}

		return $data;
	}
}
