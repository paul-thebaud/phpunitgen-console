# Change Log

## 1.4.0

**Added**

- Compatibility with Laravel Lumen framework.

## 1.3.2

**Changed**

- Fix source path resolving for single file on Windows (see #2).

## 1.3.1

**Removed**

- Package `ocramius/package-versions`: this avoids the package to break on Composer 2 with PHP 7.1.

## 1.3.0

**Added**

- Support for `tightenco/collect` `8`.
- Support for Laravel `8` (and `Models` directory).

## 1.2.1

**Changed**

- Support for `tightenco/collect` `5.8` to `7.0`.

## 1.2.0

**Added**

- Support for PHP `7.1+` (instead of `7.2+`) and Laravel `5.8+` (instead of `6.0+`).

## 1.1.0

**Added**

- Compatibility with Windows file system on `LeagueFilesystem` class.

## 1.0.0-alpha3

**Added**

- Refactor code by adding the `HasOutput` trait, used by `ProcessHandler` and `CommandFinishedListener`.

## 1.0.0-alpha2

**Changed**

- Argument `target` cannot be an existing file anymore.

## 1.0.0-alpha1

First release of command line package.
