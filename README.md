# Prooph Event Store :link: Service Bus

Marry CQRS with Event Sourcing

[![Build Status](https://travis-ci.org/prooph/event-store-bus-bridge.svg?branch=master)](https://travis-ci.org/prooph/event-store-bus-bridge)
[![Coverage Status](https://coveralls.io/repos/prooph/event-store-bus-bridge/badge.svg?branch=master&service=github)](https://coveralls.io/github/prooph/event-store-bus-bridge?branch=master)
[![Gitter](https://badges.gitter.im/Join%20Chat.svg)](https://gitter.im/prooph/improoph)

This package acts as a clue component between [prooph/service-bus](https://github.com/prooph/service-bus) and [prooph/event-store](https://github.com/prooph/event-store).

## Features
- [Transaction handling](docs/transaction_manager.md) based on command dispatch
- [Event publishing](event_publisher.md) after event store commit

# Support

- Ask questions on [prooph-users](https://groups.google.com/forum/?hl=de#!forum/prooph) mailing list.
- File issues at [https://github.com/prooph/event-store-bus-bridge/issues](https://github.com/prooph/event-store-bus-bridge/issues).
- Say hello in the [prooph gitter](https://gitter.im/prooph/improoph) chat.

# Contribute

Please feel free to fork and extend existing or add new plugins and send a pull request with your changes!
To establish a consistent code quality, please provide unit tests for all your changes and may adapt the documentation.

# Dependencies

Please refer to the project [composer.json](composer.json) for the list of dependencies.

# License

Released under the [New BSD License](LICENSE).