# HereApiConnectorBundle
Allows to use the here [geolocation](https://developer.here.com/documentation#geocoder) and [autocomplete api](https://developer.here.com/documentation#geocoding_suggestions) easily inside of symfony projects.

you'll need an APP ID and APP Code to use this service. It is just an easy wrapper to actually have access in the symfony context to this.

The Bundle converts the result of the Here API to [geojson](http://geojson.org/) in order to allow it to work seamlessly with other apis. 

## Installation

### Step 1: Download the Bundle


Open a command console, enter your project directory and execute the
following command to download the latest stable version of this bundle:

```console
$ composer require schoenef/here-api-connector-bundle:~1.0
```

This command requires you to have Composer installed globally, as explained
in the [installation chapter](https://getcomposer.org/doc/00-intro.md)
of the Composer documentation.

### Step 2: Enable the Bundle

Then, enable the bundle by adding it to the list of registered bundles
in the `app/AppKernel.php` file of your project:

```php
<?php
// app/AppKernel.php

// ...
class AppKernel extends Kernel
{
    public function registerBundles()
    {
        $bundles = array(
            // ...

            new Schoenef\HereApiConnectorBundle\HereApiConnectorBundle(), // here api geo coding service wrapper
        );

        // ...
    }

    // ...
}
```

### Step 3: Configure the Bundle

Add the following configuration to your ```app/config/config.yml```:
```yml
here_api_connector:
  timeout: 20
  api_code: "%your-code%"
  api_id: "%your-id%"
  lang: de
  country: DE
```

### Usage

To use the connector, you can use the following inside of symfony controllers:

```php
$connector = $this->get('here_api.connector');
$results = $connector->searchLocation('ber');

// do some filtering
$favoriteResult = $results[0];
 
// get the full place data 
$details = $connector->getDetails($favoriteResult->getId());

```

