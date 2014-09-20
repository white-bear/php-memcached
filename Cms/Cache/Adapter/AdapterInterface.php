<?php

namespace Cms\Cache\Adapter;


/**
 * Interface AdapterInterface
 * @package Cms\Cache\Adapter
 * @author  Alex Shilkin <shilkin.alexander@gmail.com>
 */
interface AdapterInterface
{
	/**
	 * Получить данные по ключу
	 *
	 * @param  string $key ключ
	 *
	 * @return mixed|bool
	 */
	public function get($key);

	/**
	 * Получить данные по ключам
	 *
	 * @param  array $keys ключи
	 *
	 * @return array
	 */
	public function getMulti($keys);

	/**
	 * Установить данные по ключу
	 *
	 * @param  string $key   ключ
	 * @param  mixed  $value значение
	 * @param  int    $expire
	 *
	 * @return bool
	 */
	public function set($key, $value, $expire=0);

	/**
	 * Установить данные по ключу
	 *
	 * @param  array $data   ключ
	 *
	 * @return array
	 */
	public function setMulti(array $data);

	/**
	 * Добавить запись
	 *
	 * @param  string $key   ключ
	 * @param  mixed  $value значение
	 * @param  int    $expire
	 *
	 * @return bool
	 */
	public function add($key, $value, $expire=0);

	/**
	 * Увеличить значение ключа
	 *
	 * @param  string  $key     ключ
	 * @param  integer $offset  на сколько увеличить значение
	 * @param  integer $initial начальное значение
	 *
	 * @return int|bool
	 */
	public function inc($key, $offset=1, $initial=0);

	/**
	 * Уменьшить значение ключа
	 *
	 * @param  string  $key    ключ
	 * @param  integer $offset на сколько увеличить значение
	 * @param  integer $initial начальное значение
	 *
	 * @return int|bool
	 */
	public function dec($key, $offset=1, $initial=0);

	/**
	 * Удалить данные по ключу
	 *
	 * @param  array $keys ключ
	 *
	 * @return bool
	 */
	public function delete(array $keys);

	/**
	 * Очистить кэш
	 *
	 * @return bool
	 */
	public function flush();
}
