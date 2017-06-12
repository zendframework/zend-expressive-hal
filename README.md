# Hypertext Application Language (HAL) for PSR-7 Applications

[![Build Status](https://secure.travis-ci.org/weierophinney/hal.svg?branch=master)](https://secure.travis-ci.org/weierophinney/hal)
[![Coverage Status](https://coveralls.io/repos/github/weierophinney/hal/badge.svg?branch=master)](https://coveralls.io/github/weierophinney/hal?branch=master)

> ## NOT READY FOR CONSUMPTION
>
> This component is not yet registered on Packagist, as work is not yet
> complete. It is provided to show progress as it is made.
>
> See the [TODO](TODO.md) file for progress.

This library provides provides utilities for modeling HAL resources with links
and generating [PSR-7](http://www.php-fig.org/psr/psr-7/) responses representing
both JSON and XML serializations of them.

## Installation

Run the following to install this library:

```bash
$ composer require weierophinney/hal
```

## Documentation

Documentation is [in the doc tree](doc/book/), and can be compiled using [mkdocs](http://www.mkdocs.org):

```bash
$ mkdocs build
```

You may also [browse the documentation online](https://weierophinney.github.io/hal/index.html).
