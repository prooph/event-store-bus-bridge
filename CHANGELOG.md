# Changelog

## [v3.4.0](https://github.com/prooph/event-store-bus-bridge/tree/v3.4.0)

[Full Changelog](https://github.com/prooph/event-store-bus-bridge/compare/v3.3.0...v3.4.0)

**Merged pull requests:**

- Php8 compability [\#43](https://github.com/prooph/event-store-bus-bridge/pull/43) ([fritz-gerneth](https://github.com/fritz-gerneth))
- Change copyright [\#42](https://github.com/prooph/event-store-bus-bridge/pull/42) ([codeliner](https://github.com/codeliner))
- Update cs headers [\#41](https://github.com/prooph/event-store-bus-bridge/pull/41) ([basz](https://github.com/basz))

## [v3.3.0](https://github.com/prooph/event-store-bus-bridge/tree/v3.3.0) (2018-08-06)

[Full Changelog](https://github.com/prooph/event-store-bus-bridge/compare/v3.2.0...v3.3.0)

**Implemented enhancements:**

- Support for configurable causation metadata key [\#39](https://github.com/prooph/event-store-bus-bridge/issues/39)
- Made causation metadata keys configurable [\#40](https://github.com/prooph/event-store-bus-bridge/pull/40) ([pkruithof](https://github.com/pkruithof))

## [v3.2.0](https://github.com/prooph/event-store-bus-bridge/tree/v3.2.0) (2018-04-04)

[Full Changelog](https://github.com/prooph/event-store-bus-bridge/compare/v3.1.0...v3.2.0)

**Implemented enhancements:**

- Dispatch EVENT\_APPEND\_TO and EVENT\_CREATE in transactional event store when not inside transaction [\#38](https://github.com/prooph/event-store-bus-bridge/pull/38) ([destebang](https://github.com/destebang))

**Merged pull requests:**

- doc block [\#37](https://github.com/prooph/event-store-bus-bridge/pull/37) ([basz](https://github.com/basz))

## [v3.1.0](https://github.com/prooph/event-store-bus-bridge/tree/v3.1.0) (2017-12-17)

[Full Changelog](https://github.com/prooph/event-store-bus-bridge/compare/v3.0.2...v3.1.0)

**Implemented enhancements:**

- test php 7.2 on travis [\#36](https://github.com/prooph/event-store-bus-bridge/pull/36) ([prolic](https://github.com/prolic))

**Closed issues:**

- 3.0.2 Release is a BC-Break [\#35](https://github.com/prooph/event-store-bus-bridge/issues/35)

## [v3.0.2](https://github.com/prooph/event-store-bus-bridge/tree/v3.0.2) (2017-09-25)

[Full Changelog](https://github.com/prooph/event-store-bus-bridge/compare/v3.0.1...v3.0.2)

**Fixed bugs:**

- fix causation meta data enricher docs, remove useless factory [\#34](https://github.com/prooph/event-store-bus-bridge/pull/34) ([prolic](https://github.com/prolic))

**Closed issues:**

- CausationMetadataEnricher not invoked when command bus is created before event store [\#32](https://github.com/prooph/event-store-bus-bridge/issues/32)

## [v3.0.1](https://github.com/prooph/event-store-bus-bridge/tree/v3.0.1) (2017-07-08)

[Full Changelog](https://github.com/prooph/event-store-bus-bridge/compare/v3.0.0...v3.0.1)

**Fixed bugs:**

- EventPublisher should not publish events if event contains errors / e… [\#31](https://github.com/prooph/event-store-bus-bridge/pull/31) ([prolic](https://github.com/prolic))

**Closed issues:**

- EventPublisher should not publish events if event contains errors / exceptions  [\#30](https://github.com/prooph/event-store-bus-bridge/issues/30)
- TransactionManager breaks on non found aggregates [\#29](https://github.com/prooph/event-store-bus-bridge/issues/29)

## [v3.0.0](https://github.com/prooph/event-store-bus-bridge/tree/v3.0.0) (2017-03-30)

[Full Changelog](https://github.com/prooph/event-store-bus-bridge/compare/v3.0.0-beta2...v3.0.0)

**Implemented enhancements:**

- small fix in event publisher [\#19](https://github.com/prooph/event-store-bus-bridge/pull/19) ([prolic](https://github.com/prolic))

**Merged pull requests:**

- Composer [\#28](https://github.com/prooph/event-store-bus-bridge/pull/28) ([basz](https://github.com/basz))
- update to use psr\container [\#27](https://github.com/prooph/event-store-bus-bridge/pull/27) ([basz](https://github.com/basz))

## [v3.0.0-beta2](https://github.com/prooph/event-store-bus-bridge/tree/v3.0.0-beta2) (2017-01-12)

[Full Changelog](https://github.com/prooph/event-store-bus-bridge/compare/v3.0.0-beta1...v3.0.0-beta2)

**Implemented enhancements:**

- improve causation metadata enricher [\#25](https://github.com/prooph/event-store-bus-bridge/pull/25) ([prolic](https://github.com/prolic))
- update plugin registration [\#24](https://github.com/prooph/event-store-bus-bridge/pull/24) ([prolic](https://github.com/prolic))

**Merged pull requests:**

- Travis config improvement [\#26](https://github.com/prooph/event-store-bus-bridge/pull/26) ([oqq](https://github.com/oqq))

## [v3.0.0-beta1](https://github.com/prooph/event-store-bus-bridge/tree/v3.0.0-beta1) (2016-12-13)

[Full Changelog](https://github.com/prooph/event-store-bus-bridge/compare/v2.0...v3.0.0-beta1)

**Implemented enhancements:**

- Move causation info setting to MetadataEnricherPlugin [\#18](https://github.com/prooph/event-store-bus-bridge/issues/18)
- update for event store changes [\#23](https://github.com/prooph/event-store-bus-bridge/pull/23) ([prolic](https://github.com/prolic))
- Updates [\#21](https://github.com/prooph/event-store-bus-bridge/pull/21) ([prolic](https://github.com/prolic))
- Support for PHP 7.1 [\#20](https://github.com/prooph/event-store-bus-bridge/pull/20) ([prolic](https://github.com/prolic))

**Closed issues:**

- Update to coveralls ^1.0 [\#17](https://github.com/prooph/event-store-bus-bridge/issues/17)

**Merged pull requests:**

- Origin/improvement/interface names [\#22](https://github.com/prooph/event-store-bus-bridge/pull/22) ([basz](https://github.com/basz))

## [v2.0](https://github.com/prooph/event-store-bus-bridge/tree/v2.0) (2015-11-22)

[Full Changelog](https://github.com/prooph/event-store-bus-bridge/compare/v2.0-beta.2...v2.0)

**Implemented enhancements:**

- attach transaction manager on invoke, not on initialize [\#12](https://github.com/prooph/event-store-bus-bridge/pull/12) ([prolic](https://github.com/prolic))
- update composer.json [\#8](https://github.com/prooph/event-store-bus-bridge/pull/8) ([prolic](https://github.com/prolic))

**Merged pull requests:**

- v2.0 [\#16](https://github.com/prooph/event-store-bus-bridge/pull/16) ([codeliner](https://github.com/codeliner))
- Update docs to support bookdown [\#15](https://github.com/prooph/event-store-bus-bridge/pull/15) ([codeliner](https://github.com/codeliner))
- updated bookdown templates to version 0.2.0 [\#14](https://github.com/prooph/event-store-bus-bridge/pull/14) ([sandrokeil](https://github.com/sandrokeil))
- added bookdown.io documentation [\#13](https://github.com/prooph/event-store-bus-bridge/pull/13) ([sandrokeil](https://github.com/sandrokeil))
- Convert transaction manager to event store plugin [\#11](https://github.com/prooph/event-store-bus-bridge/pull/11) ([codeliner](https://github.com/codeliner))
- event store 6.0-beta dep [\#9](https://github.com/prooph/event-store-bus-bridge/pull/9) ([codeliner](https://github.com/codeliner))
- Check if event store is in transaction [\#6](https://github.com/prooph/event-store-bus-bridge/pull/6) ([codeliner](https://github.com/codeliner))

## [v2.0-beta.2](https://github.com/prooph/event-store-bus-bridge/tree/v2.0-beta.2) (2015-10-22)

[Full Changelog](https://github.com/prooph/event-store-bus-bridge/compare/v2.0-beta.1...v2.0-beta.2)

**Merged pull requests:**

- Update event store service id [\#10](https://github.com/prooph/event-store-bus-bridge/pull/10) ([codeliner](https://github.com/codeliner))

## [v2.0-beta.1](https://github.com/prooph/event-store-bus-bridge/tree/v2.0-beta.1) (2015-10-21)

[Full Changelog](https://github.com/prooph/event-store-bus-bridge/compare/v1.1...v2.0-beta.1)

**Fixed bugs:**

- Only rollback transaction if in active transaction [\#5](https://github.com/prooph/event-store-bus-bridge/issues/5)

**Merged pull requests:**

- Support prooph/event-store v6 [\#7](https://github.com/prooph/event-store-bus-bridge/pull/7) ([codeliner](https://github.com/codeliner))

## [v1.1](https://github.com/prooph/event-store-bus-bridge/tree/v1.1) (2015-09-19)

[Full Changelog](https://github.com/prooph/event-store-bus-bridge/compare/v1.0...v1.1)

## [v1.0](https://github.com/prooph/event-store-bus-bridge/tree/v1.0) (2015-09-08)

[Full Changelog](https://github.com/prooph/event-store-bus-bridge/compare/v0.1...v1.0)

## [v0.1](https://github.com/prooph/event-store-bus-bridge/tree/v0.1) (2015-08-31)

[Full Changelog](https://github.com/prooph/event-store-bus-bridge/compare/e1d7a9ee3f1015f6a98f59c064fe504a6562dc2c...v0.1)

**Implemented enhancements:**

- Add tests [\#4](https://github.com/prooph/event-store-bus-bridge/pull/4) ([prolic](https://github.com/prolic))

**Merged pull requests:**

- Fix Readme [\#3](https://github.com/prooph/event-store-bus-bridge/pull/3) ([prolic](https://github.com/prolic))
- Add event publisher [\#2](https://github.com/prooph/event-store-bus-bridge/pull/2) ([codeliner](https://github.com/codeliner))
- Add transaction manager [\#1](https://github.com/prooph/event-store-bus-bridge/pull/1) ([codeliner](https://github.com/codeliner))



\* *This Changelog was automatically generated by [github_changelog_generator](https://github.com/github-changelog-generator/github-changelog-generator)*
