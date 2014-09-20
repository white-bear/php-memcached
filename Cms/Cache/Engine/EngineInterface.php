<?php

namespace Cms\Cache\Engine;


/**
 * Interface EngineInterface
 * @package Cms\Cache\Engine
 * @author  Alex Shilkin <shilkin.alexander@gmail.com>
 */
interface EngineInterface
{
	const LOCK_KEY_PREFIX = 'l:';
	const TAGS_KEY_PREFIX = 't:';
	const
		DATA_KEY = '__data',
		TAGS_KEY = '__tags',
		EXPIRE_KEY = '__expire';


	/**
	 * @param \Cms\Cache\Adapter\AdapterInterface $adapter
	 */
	public function setAdapter($adapter);

	/**
	 * @return \Cms\Cache\Adapter\AdapterInterface|null
	 */
	public function getAdapter();

	/**
	 * @param  string $key
	 * @param  bool   &$expired
	 * @param  bool   $wait_release
	 * @param  int    $max_wait
	 *
	 * @return mixed|bool
	 */
	public function load($key, &$expired=null, $wait_release=false, $max_wait=1);

	/**
	 * @param  string $key
	 * @param  mixed  $value
	 * @param  int    $expire
	 * @param  array  $tags
	 * @param  int    $max_lock
	 *
	 * @return bool
	 */
	public function save($key, $value, $expire=0, array $tags=[], $max_lock=1);

	/**
	 * @param  string $key
	 *
	 * @return bool
	 */
	public function expired($key);

	/**
	 * @param array $tags
	 */
	public function invalidateTags(array $tags);

	/**
	 * @param  string $key
	 * @param  int    $offset
	 * @param  int    $initial
	 *
	 * @return bool|int
	 */
	public function inc($key, $offset=1, $initial=0);

	/**
	 * @param  string $key
	 * @param  int $offset
	 * @param  int $initial
	 *
	 * @return bool|int
	 */
	public function dec($key, $offset=1, $initial=0);

	/**
	 * @param  array $keys
	 *
	 * @return bool
	 */
	public function delete(array $keys);

	/**
	 * @return bool
	 */
	public function flush();

	/**
	 * @param  string $key
	 *
	 * @return \Cms\Cached\CachedInterface
	 */
	public function cachedObject($key);

	/**
	 * @param  array $keys
	 *
	 * @return \Cms\Cached\CachedInterface
	 */
	public function cachedObjects(array $keys);
}
