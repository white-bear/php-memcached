<?php

namespace Cms\Cache\Adapter;

use Memcached;


/**
 * Адаптер для работы с Memcached
 *
 * Class MemcachedAdapter
 * @package Cms\Cache\Adapter
 * @author  Alex Shilkin <shilkin.alexander@gmail.com>
 */
class MemcachedAdapter implements AdapterInterface
{
	/**
	 * Объект мэмкэша
	 *
	 * @var \Memcached
	 */
	protected $resource = null;

	/**
	 * @var array
	 */
	protected $servers = [];

	/**
	 * @var array
	 */
	static protected $options = [
		/*
		 * Опции, непосредственно связанные с распределением ключей по серверам
		 * консистентное распределение - минимизируется миграция ключей при выпадении/добавлении ноды
		 */
		Memcached::OPT_DISTRIBUTION          => Memcached::DISTRIBUTION_CONSISTENT,
		Memcached::OPT_LIBKETAMA_COMPATIBLE  => true,
		Memcached::OPT_SERVER_FAILURE_LIMIT  => 3,
		Memcached::OPT_REMOVE_FAILED_SERVERS => true,
		Memcached::OPT_RETRY_TIMEOUT         => 600,
		Memcached::OPT_HASH                  => Memcached::HASH_MD5,

		/*
		 * таймауты на все типы операций
		 */
		Memcached::OPT_CONNECT_TIMEOUT => 50,     // Таймаут соединения, миллисекунды
		Memcached::OPT_POLL_TIMEOUT    => 150,    // Таймаут для опроса входных и выходных потоков, миллисекунды
		Memcached::OPT_RECV_TIMEOUT    => 15000, // Таймауты для приема, микросекунды
		Memcached::OPT_SEND_TIMEOUT    => 15000, // Таймауты для передачи, микросекунды

		Memcached::OPT_NO_BLOCK      => true,   // async io
		Memcached::OPT_TCP_NODELAY   => false,  // задержки для подключения к сокету
		Memcached::OPT_BUFFER_WRITES => false,   // буферизация обращений в мемкеш
		Memcached::OPT_COMPRESSION   => false,  // сжатие данных через сверх быстрый fastlz
		Memcached::OPT_PREFIX_KEY    => 'cache',

		Memcached::OPT_SERIALIZER => Memcached::SERIALIZER_PHP,
	];


	/**
	 * Конструктор
	 *
	 * @param array $config массив параметров
	 */
	public function __construct(array $config=[])
	{
		if (isset($config['namespace'])) {
			static::$options[Memcached::OPT_PREFIX_KEY] = $config['namespace'];
		}

		if (isset($config['servers'])) {
			$this->servers = $config['servers'];
		}
	}

	/**
	 * @return \Memcached
	 */
	protected function getMemcached()
	{
		if ($this->resource === null) {
			$this->resource = new Memcached();
			$this->resource->setOptions(static::$options);
			$this->resource->addServers($this->servers);
		}

		return $this->resource;
	}

	/**
	 * Получить данные по ключу
	 *
	 * @param  string $key ключ
	 *
	 * @return mixed|bool
	 */
	public function get($key)
	{
		$this->validateKeys($key);

		return $this->getMemcached()->get($key);
	}

	/**
	 * Получить данные по ключам
	 *
	 * @param  array $keys ключи
	 *
	 * @return array
	 */
	public function getMulti($keys)
	{
		$this->validateKeys($keys);

		return $this->getMemcached()->getMulti($keys);
	}

	/**
	 * Установить данные по ключу
	 *
	 * @param  string $key   ключ
	 * @param  mixed  $value значение
	 * @param  int    $expire
	 *
	 * @return bool
	 */
	public function set($key, $value, $expire=0)
	{
		$this->validateKeys($key);

		return $this->getMemcached()->set($key, $value, $expire);
	}

	/**
	 * Установить данные по ключу
	 *
	 * @param  array $data   ключ
	 *
	 * @return array
	 */
	public function setMulti(array $data)
	{
		$this->validateKeys(array_keys($data));

		return $this->getMemcached()->setMulti($data);
	}

	/**
	 * Добавить запись
	 *
	 * @param  string $key   ключ
	 * @param  mixed  $value значение
	 * @param  int    $expire
	 *
	 * @return bool
	 */
	public function add($key, $value, $expire=0)
	{
		$this->validateKeys($key);

		return $this->getMemcached()->add($key, $value, $expire);
	}

	/**
	 * Увеличить значение ключа
	 *
	 * @param  string  $key     ключ
	 * @param  integer $offset  на сколько увеличить значение
	 * @param  integer $initial начальное значение
	 *
	 * @return int|bool
	 */
	public function inc($key, $offset=1, $initial=0)
	{
		$this->validateKeys($key);

		return $this->getMemcached()->increment($key, $offset, $initial);
	}

	/**
	 * Уменьшить значение ключа
	 *
	 * @param  string  $key    ключ
	 * @param  integer $offset на сколько увеличить значение
	 * @param  integer $initial начальное значение
	 *
	 * @return int|bool
	 */
	public function dec($key, $offset=1, $initial=0)
	{
		$this->validateKeys($key);

		return $this->getMemcached()->decrement($key, $offset, $initial);
	}

	/**
	 * Удалить данные по ключу
	 *
	 * @param  array $keys ключ
	 *
	 * @return bool
	 */
	public function delete(array $keys)
	{
		$this->validateKeys($keys);

		$result = true;
		foreach ($keys as $key) {
			if (! $this->getMemcached()->delete($key)) {
				$result = false;
			}
		}

		return $result;
	}

	/**
	 * Очистить кэш
	 *
	 * @return bool
	 */
	public function flush()
	{
		return $this->getMemcached()->flush();
	}

	/**
	 * Проверка на валидность ключей
	 * - ключ не должен содержать не ascii
	 * - ключ должен быть скалярным
	 *
	 * @param  array|string $keys
	 *
	 * @throws \Cms\Cache\Adapter\AdapterException
	 */
	protected function validateKeys($keys)
	{
		if (! is_array($keys)) {
			$keys = [$keys];
		}

		foreach ($keys as $key) {
			if (! is_scalar($key)) {
				throw new AdapterException("Ключ не является скалярным типом: " . gettype($key));
			}

			if (ltrim($key, "\x1f..\x7f") !== '') {
				throw new AdapterException("В ключе есть не ascii символ: '{$key}'");
			}

			if (strlen($key) > 245) {
				throw new AdapterException("Длина ключа превышает максимум: (" . strlen($key) . ") '{$key}'");
			}
		}
	}
}
