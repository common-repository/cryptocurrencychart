<?php
declare(strict_types=1);

namespace CryptoCurrencyChart\Model;


use CryptoCurrencyChart\API\Client;
use CryptoCurrencyChart\API\Struct\Coin;
use CryptoCurrencyChart\API\Struct\CoinHistory;
use CryptoCurrencyChart\API\Struct\Struct;
use CryptoCurrencyChart\Exception\Exception;
use CryptoCurrencyChart\Main;
use function get_class;

defined('ABSPATH') || exit;

class RequestCache {
	public const TABLE_NAME_REQUEST_CACHE = 'ccc_request_cache';

	/** @var \CryptoCurrencyChart\Api\Client The client library to communicate with the CryptoCurrencyChart API */
	protected $client;


	public function __construct(string $key, string $secret) {
		$this->client = new Client($key, $secret);
	}

	/**
	 * @return Coin[]
	 * @throws \CryptoCurrencyChart\Exception\Exception
	 */
	public function getCoins(): array {
		return $this->performRequest(__FUNCTION__);
	}

	/**
	 * @return string[]
	 * @throws \CryptoCurrencyChart\Exception\Exception
	 */
	public function getDataTypes(): array {
		return $this->performRequest(__FUNCTION__);
	}

	/**
	 * @return string[]
	 * @throws \CryptoCurrencyChart\Exception\Exception
	 */
	public function getBaseCurrencies(): array {
		return $this->performRequest(__FUNCTION__);
	}

	/**
	 * @param int            $coinId
	 * @param \DateTime|null $date
	 * @param string         $baseCurrency
	 *
	 * @return Coin
	 * @throws \CryptoCurrencyChart\Exception\Exception
	 */
	public function viewCoin(int $coinId, \DateTime $date = null, string $baseCurrency = 'USD'): Coin {
		return $this->performRequest( __FUNCTION__, [$coinId, $date, $baseCurrency]);
	}

	/**
	 * @param int       $coinId
	 * @param \DateTime $start
	 * @param \DateTime $end
	 * @param string    $dataType
	 * @param string    $baseCurrency
	 *
	 * @return CoinHistory
	 * @throws \CryptoCurrencyChart\Exception\Exception
	 */
	public function viewCoinHistory(int $coinId, \DateTime $start, \DateTime $end, string $dataType = 'price', string $baseCurrency = 'USD'): CoinHistory {
		return $this->performRequest( __FUNCTION__, [$coinId, $start, $end, $dataType, $baseCurrency]);
	}

	public function getCoinName(int $coinId): Coin {
		foreach ($this->getCoins() as $coin) {
			if ($coinId === $coin->id) {
				return $coin;
			}
		}

		throw new Exception(vsprintf('Could not find coin with id `%d`.', [$coinId]));
	}

	protected function performRequest(string $method, array $parameters = []) {
		$requestParts = [$method];
		foreach ($parameters as $parameter) {
			if ($parameter === null) {
				$requestParts[] = 'null';
				continue;
			}
			if ($parameter instanceof \DateTime) {
				$requestParts[] = $parameter->format('Y-m-d');
				continue;
			}
			$requestParts[] = $parameter;
		}
		$request = \implode('::', $requestParts);

		$cachedResponse = $this->getCachedResponse($request);
		if ($cachedResponse !== null) {
			return $cachedResponse;
		}

		$response = $this->client->{$method}(...$parameters);
		$options = Main::getInstance()->getOptions();
		$cacheProperty = 'cacheTime' . ucfirst($method);
		if ($options->{$cacheProperty} === null) {
			throw new Exception(vsprintf('Missing cache property `%s` in options, make sure something is set.', [$cacheProperty]));
		}

		$this->setCachedResponse($request, $response, new \DateTime($options->{$cacheProperty}));

		return $response;
	}

	/**
	 * @param string $request
	 *
	 * @return Coin|string[]|CoinHistory|null
	 * @throws \CryptoCurrencyChart\Exception\Exception
	 */
	protected function getCachedResponse(string $request) {
		$wpdb = $this->getWpdb();

		$result = $wpdb->get_var($wpdb->prepare(
			"SELECT `data` FROM {$this->getTableName()} WHERE request = %s AND validUntil >= %s",
			[$request, \date('Y-m-d H:i:s')]
		));

		if ($result === null) {
			return null;
		}

		$data = json_decode($result, true);
		if ($data === null) {
			return null;
		}

		if (!\is_array($data) || !isset($data['type'], $data['data'])) {
			throw new Exception(sprintf('Unknown cached response for request `%s`.', $request));
		}

		if ($data['type'] === Coin::class . '[]') {
			$coins = [];
			foreach ($data['data'] as $coinData) {
				$coins[] = Coin::fromArray($coinData);
			}

			return $coins;
		}

		if ($data['type'] === 'array') {
			return $data['data'];
		}

		$structClass = $data['type'];
		if (!is_a($structClass, Struct::class, true)) {
			throw new Exception(sprintf('Invalid class type cached for request `%s`.', $request));
		}

		return $structClass::fromArray($data['data']);
	}

	protected function setCachedResponse(string $request, $data, \DateTime $validUntil): void {
		$wpdb = $this->getWpdb();

		if ($data instanceof Struct) {
			$encodeData = [
				'type' => get_class($data),
				'data' => $data->toArray(),
			];
		} elseif (is_array($data)) {
			$firstItem = \reset($data);
			if ($firstItem === false || is_string($firstItem)) {
				$encodeData = [
					'type' => 'array',
					'data' => $data,
				];
			} elseif ($firstItem instanceof Struct) {
				$encodeData = [
					'type' => get_class($firstItem) . '[]',
					'data' => $data,
				];
			} else {
				throw new Exception(sprintf('Unknown array response type for request `%s`.', $request));
			}
		} else {
			throw new Exception(sprintf('Unknown response type for request `%s`.', $request));
		}

		$wpdb->query($wpdb->prepare(
			"INSERT INTO {$this->getTableName()} (`request`, `validUntil`, `data`) VALUES (%s, %s, %s)",
			[$request, $validUntil->format('Y-m-d H:i:s'), json_encode($encodeData)]
		));
	}

	protected function getTableName(): string {
		return $this->getWpdb()->prefix . static::TABLE_NAME_REQUEST_CACHE;
	}

	protected function getWpdb(): \wpdb {
		global $wpdb;

		return $wpdb;
	}
}