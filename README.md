# Prooph Event Store :link: Service Bus

Marry CQRS with Event Sourcing

[![Build Status](https://travis-ci.org/prooph/event-store-bus-bridge.svg?branch=master)](https://travis-ci.org/prooph/event-store-bus-bridge)
[![Coverage Status](https://coveralls.io/repos/prooph/event-store-bus-bridge/badge.svg?branch=master&service=github)](https://coveralls.io/github/prooph/event-store-bus-bridge?branch=master)
[![Gitter](https://badges.gitter.im/Join%20Chat.svg)](https://gitter.im/prooph/improoph)

This package acts as a glue component between [prooph/service-bus](https://github.com/prooph/service-bus) and [prooph/event-store](https://github.com/prooph/event-store).

## Features
- [Transaction handling](docs/transaction_manager.md) based on command dispatch
- [Event publishing](docs/event_publisher.md) after event store commit

## Documentation

Documentation is [in the doc tree](docs/), and can be compiled using [bookdown](http://bookdown.io) and [Docker](https://www.docker.com/)

```console
$ docker run -it --rm -v $(pwd):/app sandrokeil/bookdown docs/bookdown.json
$ docker run -it --rm -p 8080:8080 -v $(pwd):/app php:5.6-cli php -S 0.0.0.0:8080 -t /app/docs/html
```

or make sure bookdown is installed globally via composer and `$HOME/.composer/vendor/bin` is on your `$PATH`.

```console
$ bookdown docs/bookdown.json
$ php -S 0.0.0.0:8080 -t docs/html/
```

Then browse to [http://localhost:8080/](http://localhost:8080/)

## Support

- Ask questions on [prooph-users](https://groups.google.com/forum/?hl=de#!forum/prooph) mailing list.
- File issues at [https://github.com/prooph/event-store-bus-bridge/issues](https://github.com/prooph/event-store-bus-bridge/issues).
- Say hello in the [prooph gitter](https://gitter.im/prooph/improoph) chat.

## Contribute

Please feel free to fork and extend existing or add new plugins and send a pull request with your changes!
To establish a consistent code quality, please provide unit tests for all your changes and may adapt the documentation.

## Dependencies

Please refer to the project [composer.json](composer.json) for the list of dependencies.

## License

Released under the [New BSD License](LICENSE).
