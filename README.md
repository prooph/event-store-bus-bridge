# Prooph Event Store Bus Bridge

Marry CQRS with Event Sourcing

[![Build Status](https://travis-ci.com/prooph/event-store-bus-bridge.svg?branch=master)](https://travis-ci.com/prooph/event-store-bus-bridge)
[![Coverage Status](https://coveralls.io/repos/prooph/event-store-bus-bridge/badge.svg?branch=master&service=github)](https://coveralls.io/github/prooph/event-store-bus-bridge?branch=master)
[![Gitter](https://badges.gitter.im/Join%20Chat.svg)](https://gitter.im/prooph/improoph)

This package acts as a glue component between [prooph/service-bus](https://github.com/prooph/service-bus) and [prooph/event-store](https://github.com/prooph/event-store).

## Important

This library will receive support until December 31, 2019 and will then be deprecated.

For further information see the official announcement here: [https://www.sasaprolic.com/2018/08/the-future-of-prooph-components.html](https://www.sasaprolic.com/2018/08/the-future-of-prooph-components.html)

## Features

- [Transaction handling](docs/transaction_manager.md) based on command dispatch
- [Event publishing](docs/event_publisher.md) after event store commit
- [Causation Metadata Enricher](docs/causation_metadata_enricher.md) based on command dispatch & event-store create/appendTo

## Documentation

Documentation is [in the doc tree](docs/), and can be compiled using [bookdown](http://bookdown.io).

```console
$ php ./vendor/bin/bookdown docs/bookdown.json
$ php -S 0.0.0.0:8080 -t docs/html/
```

Then browse to [http://localhost:8080/](http://localhost:8080/)

## Support

- Ask questions on Stack Overflow tagged with [#prooph](https://stackoverflow.com/questions/tagged/prooph).
- File issues at [https://github.com/prooph/event-store-bus-bridge/issues](https://github.com/prooph/event-store-bus-bridge/issues).
- Say hello in the [prooph gitter](https://gitter.im/prooph/improoph) chat.

## Contribute

Please feel free to fork and extend existing or add new plugins and send a pull request with your changes!
To establish a consistent code quality, please provide unit tests for all your changes and may adapt the documentation.

## Dependencies

Please refer to the project [composer.json](composer.json) for the list of dependencies.

## License

Released under the [New BSD License](LICENSE).
