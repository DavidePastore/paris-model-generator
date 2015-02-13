# paris-model-generator

[Paris](https://github.com/j4mie/paris) model generator from database.

## Installation

### Using command line
```sh
$ composer require davidepastore/paris-model-generator:0.1.*
```

### Editing composer.json

Require this generator:
```json
"require": {
  "davidepastore/paris-model-generator": "0.1.*"
}
```

## Setup

You have to setup your application to be sure that the generated classes will be in the right place and with the right
namespace. 
**paris-model-generator** uses `composer.json` `extra` property to put its configuration:
```json
"extra": {
  "paris-model-generator": {
    "namespace": "VendorName\\MyProject\\Models",
    "destination-folder": "src\\"
	}
}
```

### namespace

It is the namespace in which all classes will be generated. It will be also used to create the folder structure to be PSR-4 compliant.

### destination-folder

It is the folder in which all files (and folder structure) will be generated.

## Usage

Be sure to be in the base directory of the project (where you have your `composer.json` file) and run:
```sh
$ vendor/bin/paris-generator models [--force]
```
The generator will ask you information about the database, to be sure to connect to it and to retrieve the list of tables.
The list of the supported drivers could be found [here](http://docs.doctrine-project.org/projects/doctrine-dbal/en/latest/reference/configuration.html#connection-details).

### force option

The `--force` option will not ask you confirmation to overwrite existing files.

## Issues

If you have issues, just open one [here](https://github.com/DavidePastore/paris-model-generator/issues).
