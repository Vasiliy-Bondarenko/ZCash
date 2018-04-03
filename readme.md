# ZCash for PHP
Package based on [Bit-Wasp/bitcoin-php](https://github.com/Bit-Wasp/bitcoin-php) and extends it's classes to work with ZCash.

This project is "Work In Progress" for now. Don't use for production just yet.

Motivation for this package: [Bit-Wasp/bitcoin-php](https://github.com/Bit-Wasp/bitcoin-php) can't parse ZCash blocks because ZCash has different block header and transaction structure.

How to parse ZCash block:
```php
ZCashBlockFactory::fromHex($hex);
```