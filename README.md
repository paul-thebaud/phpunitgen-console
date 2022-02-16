<p align="center"><img src="https://raw.githubusercontent.com/paul-thebaud/phpunitgen-webapp/main/resources/svg/logo.svg?sanitize=true" alt="PhpUnitGen" height="50"></p>

<p align="center">
<a href="https://packagist.org/packages/phpunitgen/console"><img src="https://poser.pugx.org/phpunitgen/console/d/total.svg" alt="Total Downloads"></a>
<a href="https://packagist.org/packages/phpunitgen/console"><img src="https://poser.pugx.org/phpunitgen/console/v/stable.svg" alt="Latest Stable Version"></a>
<a href="https://github.com/paul-thebaud/phpunitgen-console/actions/workflows/main.yml" target="_blank"><img src="https://github.com/paul-thebaud/phpunitgen-console/actions/workflows/main.yml/badge.svg" alt="Build Status"></a>
<a href="https://github.styleci.io/repos/190246776" target="_blank"><img src="https://github.styleci.io/repos/190246776/shield?branch=main&style=flat" alt="StyleCI"></a>
<a href="https://sonarcloud.io/dashboard?id=paul-thebaud_phpunitgen-console" target="_blank"><img src="https://sonarcloud.io/api/project_badges/measure?project=paul-thebaud_phpunitgen-console&metric=alert_status" alt="Quality Gate Status"></a>
<a href="https://sonarcloud.io/dashboard?id=paul-thebaud_phpunitgen-console" target="_blank"><img src="https://sonarcloud.io/api/project_badges/measure?project=paul-thebaud_phpunitgen-console&metric=coverage" alt="Coverage"></a>
</p>

## Installation

The CLI tool can be installed using:
```bash
composer require --dev phpunitgen/console
```

Detailed information and webapp version are available at
[https://phpunitgen.io](https://phpunitgen.io).

## About PhpUnitGen

> **Note:** this repository contains the console code of PhpUnitGen. If you want
> to use the tool on your browser, you can go on the
> [webapp](https://phpunitgen.io). If you want to see the core code, you should
> go on [core package](https://github.com/paul-thebaud/phpunitgen-core).

PhpUnitGen is an online and command line tool to generate your unit tests'
skeletons on your projects.

- [How does it work?](https://phpunitgen.io/docs#/en/how-does-it-work)
- [Configuration](https://phpunitgen.io/docs#/en/configuration)
- [Web application](https://phpunitgen.io/docs#/en/webapp)
- [Command line](https://phpunitgen.io/docs#/en/command-line)
- [Advanced usage](https://phpunitgen.io/docs#/en/advanced-usage)

### Key features

- Generates tests skeletons for your PHP classes
- Binds with Laravel "make" command
- Generates class instantiation using dummy parameters or mocks
- Adapts to PHPUnit or Mockery mocks generation

PhpUnitGen is not meant to generate your tests content but only the skeleton (except for getters/setters).

This is because inspecting your code to generate the appropriate test is
way too complex, and might result in missing some of the code's features
or marking them as "passed unit test" even if it contains errors.

## Roadmap

You can track the tasks we plan to do on our
[Taiga.io project](https://tree.taiga.io/project/paul-thebaud-phpunitgen/kanban).

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for more details.

Informal discussion regarding bugs, new features, and implementation of
existing features takes place in the
[Github issue page of Core repository](https://github.com/paul-thebaud/phpunitgen-core/issues).

## Credits

- [Paul Thébaud](https://github/paul-thebaud)
- [Killian Hascoët](https://github.com/KillianH)
- [Charles Corbel](https://dribbble.com/CorbelC)
- [All Contributors](https://github.com/paul-thebaud/phpunitgen-core/graphs/contributors)

## License

PhpUnitGen is an open-sourced software licensed under the
[MIT license](https://opensource.org/licenses/MIT).
