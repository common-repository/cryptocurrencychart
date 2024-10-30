<?php
declare(strict_types=1);

namespace CryptoCurrencyChart\View;


use CryptoCurrencyChart\Main;
use function esc_attr;

defined('ABSPATH') || exit;

class Options {
	public function show(): void {
		print('<div class="wrap">');
		printf('<h2>%s</h2>', __('CryptoCurrencyChart plugin options', 'cryptocurrencychart'));
		$options = Main::getInstance()->getOptions();
		printf(
			'<p class="notice %s">%s</p>',
			$options->keyAndSecretValid ? 'notice-success' : 'notice-error',
			$options->keyAndSecretValid ? __('Your API key and secret are configured correctly.', 'cryptocurrencychart') : __('Please enter a valid API key and secret.', 'cryptocurrencychart')
		);
		print('<form method="post" action="">');
		print(\wp_nonce_field(\CryptoCurrencyChart\Controller\Options::FORM_ACTION_NAME, \CryptoCurrencyChart\Controller\Options::FORM_ACTION_NONCE_NAME));
		print('<table class="form-table">');

		foreach ($options->getData() as $option => $value) {
			if ($option === 'keyAndSecretValid') {
				continue;
			}
			if ($option === 'apiSecret') {
				$value = '';
			}
			print($this->getInput(
				$options->getLabel($option),
				$option,
				$value ?? '',
				$option === 'apiSecret' ? 'password' : 'text',
				$options->getDescription($option)
			));
		}
		print('</table>');
		vprintf(
			'<p class="submit"><input type="submit" name="%ssubmit" id="submit" class="button button-primary" value="%s"></p>',
			[\CryptoCurrencyChart\Controller\Options::FORM_NAME_PREFIX, __('Save changes', 'cryptocurrencychart')]
		);
		print('</form>');
		print('</div>');
	}

	protected function getInput(string $label, string $name, string $value, string $inputType, ?string $description): string {
		$id = str_replace('_', '-', \CryptoCurrencyChart\Controller\Options::FORM_NAME_PREFIX . $name);
		return sprintf(
			'<tr><th><label for="%s">%s</label></th><td><input type="%s" id="%s" value="%s" name="%s" />%s</td></tr>',
			esc_attr($id),
			esc_html($label),
			esc_attr($inputType),
			esc_attr($id),
			esc_attr($value),
			\CryptoCurrencyChart\Controller\Options::FORM_NAME_PREFIX . $name,
			/** If this value is set it is one of the values from @see \CryptoCurrencyChart\Model\Options::getDescription */
			$description !== null ? sprintf('<p class="description">%s</p>', $description) : ''
		);
	}
}