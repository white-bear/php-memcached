<?php

namespace Cms\Cache\Engine;


/**
 * Trait LocksEngine
 * @package Cms\Cache\Engine
 * @author  Alex Shilkin <shilkin.alexander@gmail.com>
 */
trait LocksEngine
{
	protected $locks = [];


	/**
	 * @param  string  $key
	 * @param  integer $expire
	 *
	 * @return bool
	 */
	public function lock($key, $expire=1)
	{
		$lock_key = $this->getLockKey($key);
		if (! empty($this->locks[$lock_key])) {
			return true;
		}

		$result = $this->getAdapter()->add($lock_key, 1, $expire);

		// собираем блокировки, чтобы удалить их потом в деструкторе
		if ($result) {
			$this->locks[$lock_key] = 1;
		}

		return $result;
	}

	/**
	 * @param  string $key
	 *
	 * @return bool
	 */
	public function release($key)
	{
		$lock_key = $this->getLockKey($key);

		unset($this->locks[$lock_key]);

		return $this->getAdapter()->delete([$lock_key]);
	}

	/**
	 * @param  string  $key
	 * @param  integer $max_wait
	 * @param  integer $delay_usec
	 *
	 * @return bool
	 *
	 * @throws \LogicException
	 */
	public function waitRelease($key, $max_wait=1, $delay_usec=100000)
	{
		$lock_key = $this->getLockKey($key);
		$waited = 0;

		do {
			$result = $this->getAdapter()->get($lock_key);

			if ($result !== false) {
				if ($waited > $max_wait) {
					throw new \LogicException("Истекло время ожидания освобождения ресурса, ключ: {$lock_key}");
				}

				usleep($delay_usec);
				$waited += $delay_usec / 1000000;
			}
		} while ($result !== false);

		return true;
	}

	/**
	 * @param  string $key
	 * @param  integer $expire      время жизни ключа в памяти, сек
	 * @param  integer $max_wait    максимальное время ожидания освобождения блокировки, сек
	 * @param  integer $delay_usec
	 *
	 * @return bool
	 *
	 * @throws \LogicException
	 */
	public function acquire($key, $expire=1, $max_wait=1, $delay_usec=100000)
	{
		$lock_key = $this->getLockKey($key);
		$waited = 0;

		do {
			$result = $this->getAdapter()->add($lock_key, 1, $expire);

			if ($result === false) {
				if ($waited > $max_wait) {
					throw new \LogicException("Истекло время ожидания освобождения ресурса при установке лока, ключ: {$lock_key}");
				}

				usleep($delay_usec);
				$waited += $delay_usec / 1000000;
			}
		} while ($result === false);

		$this->locks[$lock_key] = 1;

		return true;
	}

	/**
	 * @param  string $key
	 *
	 * @return string
	 */
	abstract protected function getLockKey($key);

	/**
	 * @return \Cms\Cache\Adapter\AdapterInterface
	 */
	abstract public function getAdapter();
}
