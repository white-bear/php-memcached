<?php

namespace Cms\Cached;

use Cms\Cache\CacheEngineAccessor;


/**
 * Class BaseCached
 * @package Cms\Cached
 * @author  Alex Shilkin <shilkin.alexander@gmail.com>
 */
abstract class BaseCached implements CachedInterface
{
	use CacheEngineAccessor;

	protected $key = '';
	protected $tags = [];
	protected $expire = 0;

	/**
	 * @var callable
	 */
	protected $provider = null;

	/**
	 * @var callable
	 */
	protected $key_provider = null;


	/**
	 * @param  array $tags
	 *
	 * @return \Cms\Cached\CachedInterface
	 */
	public function tags(array $tags=[])
	{
		$this->tags = $tags;

		return $this;
	}

	/**
	 * @param  int $expire
	 *
	 * @return \Cms\Cached\CachedInterface
	 */
	public function expire($expire=0)
	{
		$this->expire = $expire;

		return $this;
	}

	/**
	 * @param  callable $provider
	 *
	 * @return \Cms\Cached\CachedInterface
	 */
	public function dataProvider($provider)
	{
		$this->provider = $provider;

		return $this;
	}

	/**
	 * @param  callable $provider
	 *
	 * @return \Cms\Cached\CachedInterface
	 */
	public function keyProvider($provider)
	{
		$this->key_provider = $provider;

		return $this;
	}
}
