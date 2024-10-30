<?php
declare(strict_types=1);

namespace CryptoCurrencyChart\Widget;


use CryptoCurrencyChart\API\Client;
use CryptoCurrencyChart\Main;
use DateTime;
use WP_Widget;
use function apply_filters;
use function esc_attr;
use function esc_html;
use function implode;
use function in_array;
use function number_format;
use function vprintf;
use function vsprintf;

defined('ABSPATH') || exit;

class PriceWidget extends WP_Widget {
	protected const COIN_NAME_NONE = 'none';
	protected const COIN_NAME_NAME = 'name';
	protected const COIN_NAME_SYMBOL = 'symbol';
	protected const COIN_NAME_NAME_AND_SYMBOL = 'nameSymbol';

	protected const PRICE_CHANGE_SHOW = 'show';
	protected const PRICE_CHANGE_HIDE = 'hide';


	public function __construct() {
		parent::__construct(
			'cryptocurrencychart_price',
			__('CryptoCurrencyChart Price Widget', 'cryptocurrencychart'),
			['description' => __('Show the current price and a simple chart for any crypto currency.', 'cryptocurrencychart')]
		);
	}

	public function widget($args, $instance): void {
		// Check for missing required settings
		if (!isset($instance['coin'], $instance['currency'])) {
			return;
		}

		$title = apply_filters('widget_title', $instance['title'] ?? '');
		print($args['before_widget']);
		if ($title !== '') {
			print($args['before_title'] . esc_html($title) . $args['after_title']);
		}

		$requestCache = Main::getInstance()->getRequestCache();
		$coin = $requestCache->viewCoin((int) $instance['coin'], null, $instance['currency']);
		$pricePrefix = '';
		switch ($instance['coinName'] ?? static::COIN_NAME_NAME) {
			case static::COIN_NAME_NAME:
				$coinName = $requestCache->getCoinName((int) $instance['coin']);
				$pricePrefix = $coinName->name . ': ';
				break;
			case static::COIN_NAME_SYMBOL:
				$coinName = $requestCache->getCoinName((int) $instance['coin']);
				$pricePrefix = $coinName->symbol . ': ';
				break;
			case static::COIN_NAME_NAME_AND_SYMBOL:
				$coinName = $requestCache->getCoinName((int) $instance['coin']);
				$pricePrefix = vsprintf('%s (%s): ', [$coinName->name, $coinName->symbol]);
				break;
		}

		$priceChange = '';
		if (($instance['priceChange'] ?? static::PRICE_CHANGE_SHOW) === static::PRICE_CHANGE_SHOW) {
			$difference = ($coin->closePrice / $coin->openPrice - 1) * 100;
			$class = '';
			if ($difference > 0) {
				$class = ' green';
			} elseif ($difference < 0) {
				$class = ' red';
			}
			$priceChange = sprintf(
				'<span class="price-change%s">%s%s%%</span>',
				esc_attr($class),
				$difference > 0 ? '+' : '',
				esc_html(number_format($difference, 2))
			);
		}

		printf(
			'<div class="ccc-price">%s%s%s%s</div>',
			esc_html($pricePrefix),
			esc_html(Client::getFiatSymbol($instance['currency'])),
			esc_html(number_format((float) $coin->closePrice, 2)),
			$priceChange // Price changed is already escaped
		);

		$chartDays = $instance['chart_days'] ?? 0;
		if ($chartDays > 1) {
			$coinHistory = $requestCache->viewCoinHistory(
				(int) $instance['coin'],
				new DateTime(vsprintf('-%d days', [$chartDays])),
				new DateTime('-1 day'),
				'price',
				$instance['currency']
			);

			if (count($coinHistory->data) > 0) {
				$maxPrice = 0;
				$minPrice = PHP_FLOAT_MAX;
				foreach ($coinHistory->data as $historyData) {
					if ($historyData->value > $maxPrice) {
						$maxPrice  = $historyData->value;
					}
					if ($historyData->value < $minPrice) {
						$minPrice = $historyData->value;
					}
				}
				if ($coin->closePrice > $maxPrice) {
					$maxPrice = $coin->closePrice;
				} elseif ($coin->closePrice < $minPrice) {
					$minPrice = $coin->closePrice;
				}

				$chartData = [];
				foreach ($coinHistory->data as $historyData) {
					$chartData[] = (int) round(($historyData->value - $minPrice) / ($maxPrice - $minPrice) * 100.0);
				}
				$chartData[] = (int) round(($coin->closePrice - $minPrice) / ($maxPrice - $minPrice) * 100.0);

				vprintf('<div class="ccc-font-chart">{%s}</div>', [implode(',', $chartData)]);
			}
		}

		print($args['after_widget']);
	}

	public function form($instance): void {
		$title = $instance['title'] ?? $title = __('Price', 'cryptocurrencychart');

		vprintf('
            <p>
                <label>%s <input class="widefat" type="text" name="%s" value="%s" /></label>
            </p>',
			[
				__('Title', 'cryptocurrencychart'),
				$this->get_field_name('title'),
				esc_attr($title),
			]
		);

		$options = [
			static::COIN_NAME_NAME => __('Coin name', 'cryptocurrencychart'),
			static::COIN_NAME_SYMBOL => __('Coin symbol', 'cryptocurrencychart'),
			static::COIN_NAME_NAME_AND_SYMBOL => __('Coin name and symbol', 'cryptocurrencychart'),
			static::COIN_NAME_NONE => __('None', 'cryptocurrencychart'),
		];
		print($this->getSettingsSelect(
			'coinName',
			__('Before price', 'cryptocurrencychart'),
			$instance['coinName'] ?? static::COIN_NAME_NAME,
			$options
		));

		$requestCache = Main::getInstance()->getRequestCache();
		$coins = $requestCache->getCoins();
		$options = [];
		foreach ($coins as $coin) {
			$options[(string) $coin->id] = sprintf(
				'%s (%s)',
				$coin->name,
				$coin->symbol
			);
		}
		print($this->getSettingsSelect(
			'coin',
			__('Coin', 'cryptocurrencychart'),
			$instance['coin'] ?? '',
			$options
		));

		$options = [
			static::PRICE_CHANGE_SHOW => __('Show change percentage', 'cryptocurrencychart'),
			static::PRICE_CHANGE_HIDE => __('Do not show change', 'cryptocurrencychart'),
		];
		print($this->getSettingsSelect(
			'priceChange',
			__('Price change', 'cryptocurrencychart'),
			$instance['priceChange'] ?? static::PRICE_CHANGE_SHOW,
			$options
		));

		$currencies = $requestCache->getBaseCurrencies();
		print($this->getSettingsSelect(
			'currency',
			__('Currency', 'cryptocurrencychart'),
			$instance['currency'] ?? '',
			array_combine($currencies, $currencies)
		));

		printf('
            <p>
                <label>%s <input class="widefat" type="number" name="%s" value="%s" /></label>
            </p>',
			__('Days in chart below price (0 for no chart)', 'cryptocurrencychart'),
			$this->get_field_name('chart_days'),
			esc_attr($instance['chart_days'] ?? 0)
		);
	}

	public function update($newInstance, $old_instance): array {
		$coinNameOptions = [static::COIN_NAME_NAME, static::COIN_NAME_SYMBOL, static::COIN_NAME_NAME_AND_SYMBOL, static::COIN_NAME_NONE];

		$instance = [];
		$instance['title'] = isset($newInstance['title']) ? sanitize_title((string) $newInstance['title']) : '';
		$instance['coinName'] = (isset($newInstance['coinName']) && in_array($newInstance['coinName'], $coinNameOptions, true)) ? sanitize_title($newInstance['coinName']) : static::COIN_NAME_NAME;
		$instance['coin'] = isset($newInstance['coin']) ? sanitize_title((string) $newInstance['coin']) : '';
		$instance['priceChange'] = (isset($newInstance['priceChange']) && in_array($newInstance['priceChange'], [static::PRICE_CHANGE_SHOW, static::PRICE_CHANGE_HIDE], true)) ? $newInstance['priceChange'] : static::PRICE_CHANGE_SHOW;
		$instance['currency'] = isset($newInstance['currency']) ? sanitize_title((string) $newInstance['currency']) : '';
		$instance['chart_days'] = isset($newInstance['chart_days']) ? sanitize_title((string) $newInstance['chart_days']) : '0';

		return $instance;
	}

	protected function getSettingsSelect(string $fieldName, string $title, string $value, array $options): string {
		$optionMarkup = [];
		foreach ($options as $optionValue => $optionTitle) {
			$optionMarkup[] = vsprintf(
				'<option value="%s"%s>%s</option>',
				[
					esc_attr($optionValue),
					$optionValue === $value ? ' selected' : '',
					esc_html($optionTitle),
				]
			);
		}

		return vsprintf('
            <p>
                <label class="ccc-select-label">%s <select class="widefat ccc-select2" name="%s">%s</select></label>
            </p>',
			[
				esc_html($title),
				$this->get_field_name($fieldName),
				implode("\n", $optionMarkup),
			]
		);
	}
}