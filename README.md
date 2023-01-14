# Docker Client

The Docker client use the stream extension from PHP, which is integrated into the core.

## About this package

This package forked from [php-http/socket-client](https://github.com/php-http/socket-client). The goal of this project
is to provide a Docker client for PHP and is limited to it.

If you need a socker client, please use [php-http/socket-client](https://github.com/php-http/socket-client)!

## Testing

First launch the http server:

```bash
$ ./vendor/bin/http_test_server > /dev/null 2>&1 &
```

Then generate ssh certificates:

```bash
$ composer gen-ssl
```

Note: If you are running this on macOS and get the following error: "Error opening CA Private Key privkey.pem", check [this](ssl-macOS.md) file.

Now run the test suite:

``` bash
$ composer test
```

## License

The MIT License (MIT). Please see [License File](LICENSE) for more information.
