<?php

namespace Cms\Cache\Adapter;


/**
 * Trait CacheAdapterAccessor
 * @package Cms\Cache\Adapter
 * @author  Alex Shilkin <shilkin.alexander@gmail.com>
 */
trait CacheAdapterAccessor
{
	/**
	 * @var \Cms\Cache\Adapter\AdapterInterface|null
	 */
	protected $adapter = null;


	/**
	 * @param \Cms\Cache\Adapter\AdapterInterface $adapter
	 */
	public function setAdapter($adapter)
	{
		$this->adapter = $adapter;
	}

	/**
	 * @return \Cms\Cache\Adapter\AdapterInterface|null
	 */
	public function getAdapter()
	{
		return $this->adapter;
	}
}
