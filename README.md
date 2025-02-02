# PHP API for WordNet database [![Build Status](https://travis-ci.org/AdamLutka/PhpWndb.svg?branch=master)](https://travis-ci.org/AdamLutka/PhpWndb)

[WordNet database](https://wordnet.princeton.edu/) provides kind of semantic of words stored in files. This project is PHP API for easy reading of these files. See [examples/wordNet.php](examples/wordNet.php).

## Getting Started

### Prerequisites

The code needs PHP 7.1 or greater.

### Installing

```
composer require adam-lutka/php-wndb
```

## Running the tests

```
composer install
composer phpstan
composer tests
```

## Versioning

We use [SemVer](http://semver.org/) for versioning. For the versions available, see the [tags on this repository](https://github.com/AdamLutka/PhpWndb/tags).
