<?php

namespace Cms\Cache;


/**
 * Trait CacheEngineAccessor
 * @package Cms\Cache
 * @author  Alex Shilkin <shilkin.alexander@gmail.com>
 */
trait CacheEngineAccessor
{
	/**
	 * @var \Cms\Cache\Engine\EngineInterface|null
	 */
	protected $cache_engine = null;


	/**
	 * @param \Cms\Cache\Engine\EngineInterface $cache_engine
	 */
	public function setCacheEngine($cache_engine)
	{
		$this->cache_engine = $cache_engine;
	}

	/**
	 * @return \Cms\Cache\Engine\EngineInterface|null
	 */
	public function getCacheEngine()
	{
		return $this->cache_engine;
	}

	/**
	 * @return bool
	 */
	public function isCacheEnabled()
	{
		return
			null !== $this->cache_engine &&
			null !== $this->cache_engine->getAdapter();
	}
}
