Cervo
=====

About Cervo
-----------

A lightweight and highly modular structure framework for PHP.


Requirements
------------

Requires *PHP 5.4.0*.

Does not depend on any other libraries.


Versioning
----------

Version model: (major).(minor).(hotfix)

 - **Major** version changes when there are major rewrites, major changes, non-backward compatible changes, and/or removal of deprecated features.
 - **Minor** version changes are all backward compatible and may contain newly deprecated features, and/or new features.
 - **Hotfix** version changes are for bug fixes and small feature additions.


Installing
----------

### Using composer

```json
"require": {
    "nevraxe/cervo": "3.0.*"
}
```

### Manual install

Copy the *library* folder to your website. Rather then including composer's autoload in the example, you simply have to include the `bootstrap.php` file in the *Cervo* folder.

No packaging is currently made, but may be done if there is enough requests.


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
$config = \Cervo::getLibrary('Cervo/Config');

// This configuration is required. It can be set either here, or in the config.json file directly.
$config->set('Cervo/Application/Directory', realpath(__DIR__ . '/Application') . \DS);


// We initialize Cervo and load config.json.
\Cervo::initConfig(__DIR__ . '/config.json');

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

use Cervo as _;

class My extends _\Libraries\Controller
{
    public function IndexMethod($args = [])
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

use Cervo as _;

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

```
<?php
// Application/My/Libraries/My.php

namespace Application\MyModule\Libraries;

use Cervo as _;

class My
{
    public function sanitize($input)
    {
        // Sanitize $input...
        return $input;
    }
}
```


### The Router.php file

The `Router.php` file in your module's root is loaded automatically by Cervo's Router. It is included from within the Router's object so you can use it's methods.

```
<?php
// Application/My/Router.php

/** @var $this \Cervo\Libraries\Router */

use \Cervo\Libraries\RouterPath\Route as Route;

// The first parameter is the path
// The second one is the Module/Controller/Method, you can put a controller in a sub-folder, so you could load it like Module/SubFolder/Controller/Method.
// The third parameter is the HTTP method. You may use "Route::M_ANY" or "Route::M_ANY ^ Route::M_CLI" as well.
$this->addRoute('', 'My/My/Index', Route::M_HTTP_GET);
$this->addRoute('index', 'My/My/Index', Route::M_HTTP_GET);

// The arguments passed to the controller's method are defined with interogation marks and stars.
// ?  -->  Matches exactly one argument.
// *  -->  Matches zero or more arguments.
// You can use mix of the both if you want to require 2 arguments, but may have up to any.
$this->addRoute('index/?', 'My/My/Index', Router::M_HTTP_GET);

```


Documentation
-----------------

The complete documentation is being worked on.
