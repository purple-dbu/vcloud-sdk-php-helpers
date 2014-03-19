vcloud-sdk-php-helpers
======================

Utility classes for vCloud Director PHP SDK

[![Build Status](https://travis-ci.org/purple-dbu/vcloud-sdk-php-helpers.png?branch=master)](https://travis-ci.org/purple-dbu/vcloud-sdk-php-helpers)
[![Coverage Status](https://coveralls.io/repos/purple-dbu/vcloud-sdk-php-helpers/badge.png?branch=master)](https://coveralls.io/r/purple-dbu/vcloud-sdk-php-helpers?branch=master)
[![Dependency Status](https://www.versioneye.com/user/projects/527fc4d8632bac824100002d/badge.png)](https://www.versioneye.com/user/projects/527fc4d8632bac824100002d)

[![Latest Stable Version](https://poser.pugx.org/purple-dbu/vcloud-sdk-helpers/v/stable.png)](https://packagist.org/packages/purple-dbu/vcloud-sdk-helpers)

Installation
------------

Installation can be done via [Composer](http://getcomposer.org/). All you need
is to add this to your `composer.json`:

```json
  "minimum-stability": "dev",
  "prefer-stable": true,
  "require": {
    "php": ">=5.3.2",
    "vmware/vcloud-sdk-patched": "550.3.*",
    "pear/http_request2": "2.2.*",
    "purple-dbu/vcloud-sdk-helpers": "1.0.*"
  }
```


Usage
-----

For full usage instructions, please read the [API Documentation](http://purple-dbu.github.io/vcloud-sdk-php-helpers/).


### Right Helper

The Right Helper gives you the ability to manipulate user rights with ease. It
helps you determining the current logged user rights.

#### Determine whether the current user is administrator of his organization, or not

```php
$service = \VMware_VCloud_SDK_Service::getService();
$service->login(...);

\VCloud\Helpers\Right::create($service)->isCurrentUserOrganizationAdmin();

// => true|false depending on currently logged user rights
```


### Query Helper

The Query Helper gives you the ability to manipulate the vCloud SDK Query
Service with ease. It provides abstraction for pagination.


#### Get all results for query 'adminVApp'

```php
$service = \VMware_VCloud_SDK_Service::getService();
$service->login(...);

\VCloud\Helpers\Query::create($service)->queryRecords(\VMware_VCloud_SDK_Query_Types::ADMIN_VAPP);

// => array(
//        \VMware_VCloud_API_QueryResultAdminVAppRecordType,
//        ...
//    )
```

#### Get the query result for 'adminVApp' with id 'c47ddf20-05de-44f5-b79e-c463992ffd3f'

```php
$service = \VMware_VCloud_SDK_Service::getService();
$service->login(...);

\VCloud\Helpers\Query::create($service)->queryRecord(
    \VMware_VCloud_SDK_Query_Types::ADMIN_VAPP,
    'id==c47ddf20-05de-44f5-b79e-c463992ffd3f'
);

// => \VMware_VCloud_API_QueryResultAdminVAppRecordType
// OR null if no vApp exist with such id
```


### Exception Helper

The Exception Helper gives you the ability to manipulate vCloud SDK exceptions
(VMware_VCloud_SDK_Exception) with ease. It allows extracting the error codes
and messages from the original exception message, with is just raw XML of the
form:
```xml
<Error
    xmlns="http://www.vmware.com/vcloud/v1.5"
    message="xs:string"
    majorErrorCode="xs:int"
    minorErrorCode="xs:string"
    vendorSpecificErrorCode="xs:string"
    stackTrace="xs:string"
/>
```

#### Get the error message

```php
...
catch(\VMware_VCloud_SDK_Exception $e) {
    \VCloud\Helpers\Exception::create($e)->getMessage($e);

    // => (string) The message contained in the error XML
}
```

#### Get the error code

```php
...
catch(\VMware_VCloud_SDK_Exception $e) {
    \VCloud\Helpers\Exception::create($e)->getMajorErrorCode($e);

    // => (int) The major error code contained in the error XML
}
```


### Metadata helper

The Metadata Helper gives you the ability to manipulate metadata on vCloud
objects with ease. It helps finding objects with a particular metadata (to
either one particular value, or any value).

#### Get all vApp Template with a given metadata set (any value)

```php
$service = \VMware_VCloud_SDK_Service::getService();
$service->login(...);

\VCloud\Helpers\Metadata::create($service)->getObjects(
    \VMware_VCloud_SDK_Query_Types::ADMIN_VAPP_TEMPLATE,
    'myMetadata'
);

// => array(
//        \VMware_VCloud_SDK_VAppTemplate,
//        ...
//    )
// The result array contains all the vApp Templates that hold a metadata named
// "myMetadata"
```

#### Get the first vApp Template with a given metadata set to a particular value

```php
$service = \VMware_VCloud_SDK_Service::getService();
$service->login(...);

\VCloud\Helpers\Metadata::create($service)->getObject(
    \VMware_VCloud_SDK_Query_Types::ADMIN_VAPP_TEMPLATE,
    'someId',
    '23d6deb1-1778-4325-8289-2f150d122675'
);

// => \VMware_VCloud_SDK_VAppTemplate
// 
// The result vApp Template is the first vApp Template that holds a metadata
// named "someId" with the value "23d6deb1-1778-4325-8289-2f150d122675"
```


Licensing
---------

This project is released under [MIT License](LICENSE) license. If this license
does not fit your requirement for whatever reason, but you would be interested
in using the work (as defined below) under another license, please contact
Purple DBU at [dbu.purple@gmail.com](mailto:dbu.purple@gmail.com).


Contributing
------------

Contributions (issues ♥, pull requests ♥♥♥) are more than welcome! Feel free to
clone, fork, modify, extend, etc, as long as you respect the
[license terms](LICENSE).


### Requirements

You need to have the following software installed:
- git
- make
- curl
- php >= 5.3.2

You need to have a working vCloud Director installation.


### Getting started

To start contributing, the best is to follow these steps:

1. Create a GitHub account
2. Create your own fork of this project
3. Clone it to your machine: `git clone https://github.com/<you>/vcloud-sdk-php-helpers.git`
4. Go to the project's root directory: `cd vcloud-sdk-php-helpers`
5. Create the file _tests/config.php_: `cp tests/config.php.dist tests/config.php`
6. Edit `tests/config.php` and set values according to your vCloud Director configuration
7. Install dependencies: `make dependencies`
8. Run tests: `make test`


### Common tasks

- Update dependencies: `make dependencies`
- Clean dependencies: `make clean`
- Check code quality: `make lint`
- Run integration tests: `make integration`
- Generate stubs for unit tests: `make stubs`
- Run unit tests: `make test`
- Generate API documentation: `make doc`
- Publish API documentation: `make publish`


### Versioning

Using [Semver](http://semver.org/) as a base for versioning, this project also
follow additional guidelines for version numbering. each version is in the
format `x.y.z` where:

  - Each modification of `x` introduces backward compatibility breaks
  - Each modification of `y` introduces a new feature
  - Modifications of `y` are simply corrections of existing patches
