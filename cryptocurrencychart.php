<?php
declare(strict_types=1);

/**
 * Plugin Name: CryptoCurrencyChart
 * Plugin URI: https://gitlab.com/bastiaangrutters/cryptocurrencychart-api
 * Description: Adds the option of showing crypto currency price information and charts as widgets. Select from 1000+ crypto currencies to show the actual price and recent price charts.
 * Version: 1.01
 * Requires at least: 5.2.2
 * Requires PHP: 7.2
 * Author: CryptoCurrencyChart B.V.
 * Author URI: https://www.cryptocurrencychart.com
 * License: GPL3
 *
 * CryptoCurrencyChart WordPress plugin is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 2 of the License, or
 * any later version.
 *
 * CryptoCurrencyChart WordPress plugin is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with CryptoCurrencyChart WordPress plugin. If not, see https://www.gnu.org/licenses/gpl-3.0.txt.
 */

defined('ABSPATH') || exit;

use CryptoCurrencyChart\Main;

spl_autoload_register(static function(string $class) {
	if (strpos($class, 'CryptoCurrencyChart') !== 0) {
		return;
	}

	$basePath = plugin_dir_path(__FILE__);
	$class = str_replace('\\', '/', $class);

	if (strpos($class, 'CryptoCurrencyChart/API') === 0) {
		/** @noinspection PhpIncludeInspection */
		require_once $basePath . 'library/api/src/' . $class . '.php';

		return;
	}

	/** @noinspection PhpIncludeInspection */
	require_once $basePath . 'src/' . $class . '.php';
});

register_activation_hook(__FILE__, [Main::class, 'addTable']);
register_deactivation_hook(__FILE__, [Main::class, 'removeTable']);

new Main();