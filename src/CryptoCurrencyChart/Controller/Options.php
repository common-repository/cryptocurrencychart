<?php
declare(strict_types=1);

namespace CryptoCurrencyChart\Controller;


use CryptoCurrencyChart\API\Client;
use CryptoCurrencyChart\API\Exception\Exception;
use CryptoCurrencyChart\Main;

defined('ABSPATH') || exit;

class Options {
	public const FORM_NAME_PREFIX = 'cryptocurrencychart_';
	public const MENU_PAGE_SLUG = 'crypto-currency-chart';
	public const FORM_ACTION_NAME = 'ccc_plugin_settings';
	public const FORM_ACTION_NONCE_NAME = 'ccc_plugin_settings_nonce';


	public function show(): void {
		if (!current_user_can(Main::REQUIRED_USER_CAPABILITY)) {
			return;
		}

		$submitName = static::FORM_NAME_PREFIX . 'submit';
		if (isset($_POST[$submitName])) {
			$this->handleSubmit();
		}

		(new \CryptoCurrencyChart\View\Options())->show();
	}

	protected function handleSubmit(): void {
		if (!isset($_POST[static::FORM_ACTION_NONCE_NAME]) || !wp_verify_nonce($_POST[static::FORM_ACTION_NONCE_NAME], static::FORM_ACTION_NAME)) {
			return;
		}

		$options = Main::getInstance()->getOptions();
		$checkApiConnection = false;

		foreach ($options as $option => $value) {
			if ($option === 'keyAndSecretValid') {
				continue;
			}

			$postName = static::FORM_NAME_PREFIX . $option;
			if ($option === 'apiSecret' && ($_POST[$postName] ?? '') === '') {
				continue;
			}

			$newValue = (string) ($_POST[$postName] ?? $value);
			if (in_array($option, ['apiKey', 'apiSecret'], true)) {
				$newValue = sanitize_key($newValue);
			} else {
				$newValue = sanitize_title($newValue);
				// Make sure this value can be used as a date time constructor parameter, set the value to empty if it is not
				try {
					new \DateTime($newValue);
				} catch (\Throwable $e) {
					$newValue = \CryptoCurrencyChart\Model\Options::getDefault($option);
				}
			}

			if (($option === 'apiSecret' || $option === 'apiKey') && $newValue !== $options->{$option}) {
				$checkApiConnection = true;
			}
			$options->{$option} = $newValue;
		}

		if ($checkApiConnection) {
			$client = new Client($options->apiKey, $options->apiSecret);
			try {
				$client->getDataTypes();
				$options->keyAndSecretValid = true;
			} catch (Exception $e) {
				$options->keyAndSecretValid = false;
			}
		}
		$options->save();
	}
}