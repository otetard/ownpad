# PHP Etherpad Lite Client
This PHP Etherpad Lite class allows you to easily interact with Etherpad Lite API with PHP.  
Etherpad Lite is a collaborative editor provided by the Etherpad Foundation (http://etherpad.org)

## Basic Usage

Install from packagist:

```
composer require tomnomnom/etherpad-lite-client
```

A legacy `etherpad-lite-client.php` file is included for people who are unwilling/unable to switch to the new
namespaced version, but it is deprecated and will be removed in future versions.

```php
<?php
require 'vendor/autoload.php';
$instance = new EtherpadLite\Client('EtherpadFTW', 'http://beta.etherpad.org/api');
$revisionCount = $instance->getRevisionsCount('testPad');
$revisionCount = $revisionCount->revisions;
echo "Pad has $revisionCount revisions";
```

# Running The Tests
The full-stack tests can be run by running `make test`.
 
The test suite makes the following assumptions:

* A copy of Etherpad is running at http://localhost:9001
* The data in the running instance of Etherpad can be destroyed
* The APIKey for the running instance is 'dcf118bfc58cc69cdf3ae870071f97149924f5f5a9a4a552fd2921b40830aaae'
* PHPUnit has been installed with [Composer](https://getcomposer.org/) (run `make dev-deps`)

A Dockerfile is provided in `tools/testcontainer` to ease setup of a test instance.

# License

Apache License

# Other Stuff

The Etherpad Foundation also provides a jQuery plugin for Etherpad Lite.  
This can be found at http://etherpad.org/2011/08/14/etherpad-lite-jquery-plugin/

