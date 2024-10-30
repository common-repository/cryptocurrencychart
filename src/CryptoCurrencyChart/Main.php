<?php
declare(strict_types=1);

namespace CryptoCurrencyChart;


use CryptoCurrencyChart\Model\Options;
use CryptoCurrencyChart\Model\RequestCache;
use CryptoCurrencyChart\Widget\PriceWidget;

defined('ABSPATH') || exit;

class Main {
	public const VERSION = 1.01;
	public const REQUIRED_USER_CAPABILITY = 'activate_plugins';

	/** @var Main|null Instance of the plugin */
	protected static $instance;
	/** @var \CryptoCurrencyChart\Model\Options The plugins options */
	protected $options;
	/** @var RequestCache API requests made through this class are automatically cached or fetched from the cache. */
	protected $requestCache;


	public function __construct() {
		add_filter('plugin_action_links_' . $this->getPluginFileAndPath(), [$this, 'pluginActionLinks']);

		add_action('admin_menu', [$this, 'addPluginPage']);
		add_action('widgets_init', [$this, 'widgetsInit']);

		add_action('admin_enqueue_scripts', [$this, 'adminEnqueueScripts']);
		add_action('wp_enqueue_scripts', [$this, 'enqueueScripts']);

		add_action('admin_notices', [$this, 'adminNotices']);

		static::$instance = $this;

		$this->options = new Options();
		$this->requestCache = new RequestCache($this->options->apiKey, $this->options->apiSecret);
	}

	public function addPluginPage(): void {
		add_options_page(
			'Crypto Currency Chart options',
			'CryptoCurrencyChart',
			static::REQUIRED_USER_CAPABILITY,
			Controller\Options::MENU_PAGE_SLUG,
			[new Controller\Options(), 'show']
		);
	}

	public function widgetsInit(): void {
		\register_widget(PriceWidget::class);
	}

	public function getOptions(): Options {
		return $this->options;
	}

	public function enqueueScripts(): void {
		wp_enqueue_style('cryptocurrencychart', plugin_dir_url(__FILE__) . '../../assets/css/cryptocurrencychart.css');
	}

	public function adminEnqueueScripts(): void {
		wp_enqueue_style('select2', plugin_dir_url(__FILE__) . '../../assets/css/select2.min.css', [], static::VERSION);
		wp_enqueue_style('cryptocurrencychart-admin', plugin_dir_url(__FILE__) . '../../assets/css/cryptocurrencychart-admin.css', ['select2'], static::VERSION);

		wp_enqueue_script('select2', plugin_dir_url(__FILE__) . '../../assets/js/select2.min.js', ['jquery'], static::VERSION);
		wp_enqueue_script('cryptocurrencychart-admin', plugin_dir_url(__FILE__) . '../../assets/js/cryptocurrencychart-admin.js', ['select2'], static::VERSION);
	}

	public function getRequestCache(): RequestCache {
		return $this->requestCache;
	}

	public function adminNotices(): void {
		if ($this->options->apiKey !== '' && $this->options->apiSecret !== '') {
			return;
		}

		$screen = get_current_screen();
		if ($screen !== null && $screen->id === 'settings_page_' . Controller\Options::MENU_PAGE_SLUG) {
			return;
		}

		\vprintf(
			'<div class="notice notice-error">
				<h3>%s</h3>
				<p>%s</p>
			</div>',
			[
				__('CryptoCurrencyChart: Set your API key and secret', 'cryptocurrencychart'),
				\vsprintf(
					__('Set your API key and secret in <a href="%s">the options</a> page to be able to retrieve crypto currency price data.', 'cryptocurrencychart'),
					[\menu_page_url(Controller\Options::MENU_PAGE_SLUG, false)]
				),
			]
		);
	}

	public function pluginActionLinks(array $links): array {
		array_unshift($links, sprintf(
			'<a href="%s">%s</a>',
			admin_url('options-general.php?page=' . \CryptoCurrencyChart\Controller\Options::MENU_PAGE_SLUG),
			__('Settings', 'cryptocurrencychart')
		));

		return $links;
	}

	public static function getInstance(): Main {
		return static::$instance;
	}

	public static function addTable(): void {
		global $wpdb;

		$wpdb->query(vsprintf('
			CREATE TABLE IF NOT EXISTS %1$s%2$s
			(
				`id` INT AUTO_INCREMENT,
				`request` VARCHAR(256) NOT NULL,
				`validUntil` DATETIME NOT NULL,
				`data` MEDIUMBLOB NULL,
				constraint `%1$s%2$s_pk`
					primary key (id)
			);', [$wpdb->prefix, RequestCache::TABLE_NAME_REQUEST_CACHE]));
	}

	public static function removeTable(): void {
		global $wpdb;

		$wpdb->query(vsprintf('DROP TABLE TABLE IF EXISTS %s%s;',
				[$wpdb->prefix, RequestCache::TABLE_NAME_REQUEST_CACHE]
		));
	}

	protected function getPluginFileAndPath(): string {
		return plugin_basename(dirname(__FILE__, 3) . '/cryptocurrencychart.php');
	}
}