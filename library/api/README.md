# CryptoCurrencyChart API PHP library

PHP library to access the [CryptoCurrencyChart](https://www.cryptocurrencychart.com) API where you can retrieve historical and current crypto currency price data. Licensed under the MIT license.

* [Get API credentials](https://www.cryptocurrencychart.com/subscription/signup)
* [API information](https://www.cryptocurrencychart.com/api/documentation)
* [API reference](https://www.cryptocurrencychart.com/api/methods)

## Usage
```php
$client = new Client('apiKey', 'apiSecret');
$coins = $client->getCoins();
$firstCoin = reset($coins);
$client->viewCoin($firstCoin->id);
```

## Version 0.1

* Basic library
* Examples