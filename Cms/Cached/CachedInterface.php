<?php

namespace Cms\Cached;


/**
 * Interface CachedInterface
 * @package Cms\Cached
 * @author  Alex Shilkin <shilkin.alexander@gmail.com>
 */
interface CachedInterface
{
	/**
	 * @param  array $tags
	 *
	 * @return \Cms\Cached\CachedInterface
	 */
	public function tags(array $tags=[]);

	/**
	 * @param  int $expire
	 *
	 * @return \Cms\Cached\CachedInterface
	 */
	public function expire($expire=0);

	/**
	 * @param  callable $provider
	 *
	 * @return \Cms\Cached\CachedInterface
	 */
	public function dataProvider($provider);

	/**
	 * @param  callable $provider
	 *
	 * @return \Cms\Cached\CachedInterface
	 */
	public function keyProvider($provider);

	/**
	 * @param  int $max_wait
	 *
	 * @return mixed
	 */
	public function get($max_wait=1);
}
