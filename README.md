Cervo
=====

[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/Nevraxe/Cervo/badges/quality-score.png?b=3.0)](https://scrutinizer-ci.com/g/Nevraxe/Cervo/?branch=3.0)

About Cervo
-----------

A lightweight and highly modular structure framework for PHP.


Requirements
------------

Requires *PHP 5.4.0*.


Versioning
----------

Version model: (MAJOR).(MINOR).(PATCH)

 - **MAJOR** version when we make incompatible API changes,
 - **MINOR** version when we add functionality in a backwards-compatible manner, and
 - **PATCH** version when we make backwards-compatible bug fixes.


Installing
----------

Using composer

```json
"require": {
    "nevraxe/cervo": "3.0.*"
}
```


How to get started
------------------

### Preamble

There is a lot of possible configurations for Cervo, but this guide assumes that you are using defaults.

Most of the structural names (including suffixes and folder names) can be changed through the `Config` library.


### Initializing Cervo

First you need to create your `index.php` file. It's purpuse is to load your composer dependencies (which includes Cervo), to configure Cervo and then to initialize it.

```php
<?php


// An util that is used throughout Cervo (Not required)
if (!defined('DS'))
    define('DS', DIRECTORY_SEPARATOR);


// Include the composer vendor files
require 'vendor/autoload.php';


// We get the Config Cervo library
$config = \Cervo\Core::getLibrary('Cervo/Config');

// This configuration is required. It can be set either here, or in the config.json file directly.
$config->set('Cervo/Application/Directory', realpath(__DIR__ . '/Application') . \DS);


// We initialize Cervo and load config.json.
\Cervo\Core::init(__DIR__ . '/config.json');

```

By default, a `config.json` file is not required unless you want to specify the application directory path in it.


### The folder structure

The application folder needs to contains modules which are simply sub-folders of it's directory.

Inside a module you may have the `Controllers`, `Libraries`, `Templates` and `Views` directories as well as the `Router.php` file.

```
Application/
 -> My/
   -> Controllers/
   -> Libraries/
   -> Templates/
   -> Views/
   -> Router.php
```


### Creating a Controller

Checklist

 - In a controller, you have to set your namespace to `Application/MyModule/Controllers`. As you can notice, the `Module` suffix needs to be added for modules.
 - The filename requires to be __exactly__ the same as the class name (With correct case).
 - The class requires to extend the `\Cervo\Libraries\Controller` class or a derivative.
 - The methods that serves as an end-point for a request needs to be suffixed with `Method`.
 - The end-point methods may have an `$args` parameter to receive the arguments from the `Router`.

```php
<?php
// Application/My/Controllers/My.php

namespace Application\MyModule\Controllers;

use Cervo\Core as _;

class My extends _\Libraries\Controller
{
    public function IndexMethod($args = [], $params = [])
    {
        // We process the user input
        $value = $args[0];
        
        if (strlen($value) == 0)
        {
            $value = 'My default value';
        }
        
        $value = _::getLibrary('My')->sanitize($value);
    
        // We fetch the view, set it's value and render it.
        _::getView('My/Index')->setValue($value)->render();
    }
}

```

The controllers methods purpose are to serve as an end-point for the router. You should include the data input processing in it.


### Creating a View and a Template

#### The View

Checklist

 - In a view, you have to set your namespace to `Application/MyModule/Views`.
 - The filename requires to be __exactly__ the same as the class name (With correct case).
 - The class requires to extend the `\Cervo\Libraries\View` class or a derivative.
 - The class needs to override the `render()` method and it is recommended to render a template in it.
 
```php
<?php
// Application/My/Views/Index.php

namespace Application\MyModule\Views;

use Cervo\Core as _;

class Index extends _\Libraries\Views
{
    protected $show_hello_world = false;
    protected $value = 'My value';
    
    public function setShowHelloWorld($show_hello_world)
    {
        $this->show_hello_world = $show_hello_world;
        return $this;
    }
    
    public function setValue($value)
    {
        $this->value = $value;
        return $this;
    }
    
    public function render()
    {
        // We fetch the tempate, assign the parameters and render it.
        // My/Index will resolve to Application/My/Templates/Index
        _::getTemplate('My/Index')->assign([
            'ShowHelloWorld' => $this->show_hello_world,
            'Value' => $this->value
        ])->render();
    }
}

```


#### The Template

The template file is where you put your HTML and consume the variables for display.

The template wrapper has a magic `__get()` method that you can use to get the assigned parameters.

There is no specific recommendations on what to pass to a template, it may be only strings and booleans, objects with getters or anything of your preference.

```php
<html>
<head>
    <title>My application</title>
</head>
<body>
    <?php if ($this->ShowHelloWorld): ?>
        <p>Hello world!</p>
    <?php endif; ?>
    <p><?=$this->Value?></p>
</body>
</html>
```


### Creating a Library

Checklist

 - In a library, you have to set your namespace to `Application/MyModule/Libraries`.
 - The filename requires to be __exactly__ the same as the class name (With correct case).

A library is simply a singleton class that contains code that is re-used. The Cervo `getLibrary()` function handled the singleton part, so you can use your class directly in your tests.

```php
<?php
// Application/My/Libraries/My.php

namespace Application\MyModule\Libraries;

use Cervo\Core as _;

class My
{
    public function sanitize($input)
    {
        // Sanitize $input...
        return $input;
    }
    
    public function verify(\Cervo\Libraries\Router $router)
    {
        if (\Cervo\Core::getLibrary('Users')->getCurrentUser() === null) {
            // Returning false will prevent the controller/method to be called.
            return false;
        }
        
        return true;
    }
}
```


### The Router.php file

The `Router.php` file in your module's root is loaded automatically by Cervo's Router. It is included from within the Router's object so you can use it's methods.

```php
<?php
// Application/My/Router.php

return function (\Cervo\Libraries\Router $router) {

    // The first parameter is the HTTP method. You may use an array to define multiple.
    // The second one is the path. It supports the nikic/FastRoute default notation.
    // The third parameter is the Module/Controller/Method, you can a controller in a sub-folder, so you could load it like Module/SubFolder/Controller/Method.
    $router->addRoute('GET', '/', 'Test/Test/Test');
    $router->addRoute('GET', '/test/{name}', 'Test/Test/Named');
    
    // The fourth parameter is an array in the format ['MyModule/MyLibrary', 'Method']. The first part is the equivalent of doing \Cervo\Core::getLibrary() and the second part is the method called.
    // You can add an array as the fifth parameter, those informations will be passed to the controller/method as second parameter.
    $router->addRoute('GET', '/admin/test/{name}', 'Test/Admin/Test/Named', ['My', 'verify'], ['param' => 'test']);
    

};

```


Documentation
-----------------

The complete documentation is being worked on.
