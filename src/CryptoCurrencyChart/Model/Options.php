<?php
declare(strict_types=1);

namespace CryptoCurrencyChart\Model;

use CryptoCurrencyChart\Exception\Exception;

defined('ABSPATH') || exit;

class Options {
	public const CACHE_TIME_DEFAULT_VIEW_COIN = '+5 minutes';
	public const CACHE_TIME_DEFAULT_VIEW_COIN_HISTORY = '+1 day';
	public const CACHE_TIME_DEFAULT_GET_COINS = '+1 day';
	public const CACHE_TIME_DEFAULT_GET_DATA_TYPES = '+1 month';
	public const CACHE_TIME_DEFAULT_GET_BASE_CURRENCIES = '+1 month';

	protected const OPTION_KEY = '_cryptocurrencychart_options';

	public $apiKey = '';
	public $apiSecret = '';
	public $keyAndSecretValid = false;
	public $cacheTimeViewCoin = self::CACHE_TIME_DEFAULT_VIEW_COIN;
	public $cacheTimeViewCoinHistory = self::CACHE_TIME_DEFAULT_VIEW_COIN_HISTORY;
	public $cacheTimeGetCoins = self::CACHE_TIME_DEFAULT_GET_COINS;
	public $cacheTimeGetDataTypes = self::CACHE_TIME_DEFAULT_GET_DATA_TYPES;
	public $cacheTimeGetBaseCurrencies = self::CACHE_TIME_DEFAULT_GET_BASE_CURRENCIES;


	public function __construct() {
		$data = \get_option(static::OPTION_KEY);
		if (\is_array($data)) {
			$this->setData($data);
		}
	}

	public function save(): void {
		\update_option(static::OPTION_KEY, $this->getData());
	}

	public function getData(): array {
		$data = [];
		foreach (\get_object_vars($this) as $property => $value) {
			$data[$property] = $value;
		}

		return $data;
	}

	public function getLabel(string $option): string {
		switch ($option) {
			case 'apiKey':
				return __('CryptoCurrencyChart API key', 'cryptocurrencychart');
			case 'apiSecret':
				return __('CryptoCurrencyChart API secret', 'cryptocurrencychart');
			case 'cacheTimeGetCoins':
				return __('Time to cache getCoins requests', 'cryptocurrencychart');
			case 'cacheTimeViewCoin':
				return __('Time to cache viewCoin requests', 'cryptocurrencychart');
			case 'cacheTimeViewCoinHistory':
				return __('Time to cache viewCoinHistory requests', 'cryptocurrencychart');
			case 'cacheTimeGetDataTypes':
				return __('Time to cache getDataTypes requests', 'cryptocurrencychart');
			case 'cacheTimeGetBaseCurrencies':
				return __('Time to cache getBaseCurrencies requests', 'cryptocurrencychart');
		}

		throw new Exception(vsprintf('Unknown option `%s`, missing label information.', [$option]));
	}

	public function getDescription(string $option): ?string {
		switch ($option) {
			case 'apiKey':
				return __('Your CryptoCurrencyChart API key. If you don\'t have an account yet you can get one for free yet <a href="https://www.cryptocurrencychart.com/subscription/signup" target="_blank" rel="noopener">register one now</a>.', 'cryptocurrencychart');
			case 'apiSecret':
				return __('The CryptoCurrencyChart API secret that belongs to your API key. Only fill in this value if you want to change it. It will not show your secret here.', 'cryptocurrencychart');
			case 'cacheTimeViewCoin':
				return __('The time this plugin will remember and re-use the response from getCoins call, to get more up to date data you can lower the cache duration, with lower cache duration more API requests though, check your request limit in <a href="https://www.cryptocurrencychart.com/subscription/statistics" target="_blank" rel="noopener">your account</a>', 'cryptocurrencychart');
		}

		return null;
	}

	public static function getDefault(string $optionName): string {
		switch ($optionName) {
			case 'cacheTimeViewCoin':
				return static::CACHE_TIME_DEFAULT_VIEW_COIN;
			case 'cacheTimeGetCoins':
				return static::CACHE_TIME_DEFAULT_GET_COINS;
			case 'cacheTimeViewCoinHistory':
				return static::CACHE_TIME_DEFAULT_VIEW_COIN_HISTORY;
			case 'cacheTimeGetDataTypes':
				return static::CACHE_TIME_DEFAULT_GET_DATA_TYPES;
			case 'cacheTimeGetBaseCurrencies':
				return static::CACHE_TIME_DEFAULT_GET_BASE_CURRENCIES;
			default:
				return '';
		}
	}

	protected function setData(array $data): void {
		foreach (\get_object_vars($this) as $property => $value) {
			$this->{$property} = $data[$property] ?? null;
		}
	}
}