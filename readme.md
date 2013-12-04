# Laravel-Hooks

This package is for Laravel 4.1 and above. It allows your application to hook into various aspects of Laravel's request lifecycle without needing to create a service provider.

## Quick Use Case

Let's say you want some arbitrary code to execute on every request and you want this code to execute _before the application boots._ Normally, you'd create a service provider and place the code in its `register()` method.

With Laravel-Hooks you can create an `app/start/preboot.php` file and add your code there.

Perfect for registering middleware or other low-level code.

## Installation

Edit your project's composer.json to require heintzelman/laravel-hooks.

```
"require" {
    "laravel/framework": "4.1.*",
    "heintzelman/laravel-hooks": "dev-master"
}
```

Next, update your application's dependencies through the console.

```
$ composer update
```

Finally, add the service provider. Edit app/config/app.php and add the new service provider to the end of your providers array.

```
'Heintzelman\LaravelHooks\LaravelHooksServiceProvider',
```

That's it. Now you can create any of the hook files defined below.

## Hooks Available

The existence of any of these files triggers their inclusion at the following points.

* `app/start/preboot.php` - Occurs at the same place service provider `register()` methods are called.
* `app/start/onbooting.php` - Occurs after service providers are booted, but before the application is considered booted.
* `app/start/onbooted.php` - Occurs after the application is booted. (The same place your normal start files start/global.php, filters.php, and routes.php are loaded.)
* `app/start/ondown.php` - Executed when your application is in maintenance mode.
* `app/start/onbefore.php` - Executed as global app "before" filters.
* `app/start/onafter.php` - Executed as global app "after" filters.
* `app/start/onfinish.php` - Executed after the response is sent to the user.
* `app/start/onshutdown.php` - Executed when your application shuts down.

### You can use the `app/hooks` directory too.

Laravel-Hooks looks in two locations for the hook files.

1. First `app/hooks` is examined. If a hook file is found there, it is used.
2. Next `app/start` is examined. If a hook file is found there, it is used.

Note that if a hook file is found in the first location (`app/hooks`) it is used and the `app/start` directory is not examined for that specific hook.

You can mix hook files between the two directories. Say you have `app/start/preboot.php` and `app/hooks/onfinish.php`.

## Callback Hooks vs. Filter Hooks

The `ondown.php`, `onbefore.php`, and `onafter.php` are considered filter hooks. They should operate as Laravel filters do and return a value if needed.

For example, let's say you have the following `app/hooks/ondown.php` file.


```
<?php
return View::make('maintenance.mode');
?>
```

Since this returns a value it operates exactly the same way if you were to use the `App::down()` method to register the filter. Meaning, Laravel aborts the rest of the request dispatching and returns that value to the user.

All other hooks are considered callback hooks. Any return value is ignored.

## Speeding up the hooks

You can speed up checking for the hook files by disabling any hooks you're not used.

Create a `app/config/hooks.php` file and add entries for any hooks you want to disable.

```
<?php
// app/config/hooks.php
return array(
    'onshutdown' => false,      // do not use the onshutdown hook
    'onbefore' => false,        // or the onbefore hook
);
?>
```

## Details on each hook

Each file includes has $app available to it. This is useful if you want to access app components such as `$app['router']`.

### preboot.php

This file is loaded at the point in the request that service providers are registered. This occurs before the service providers are booted (the `boot()` method called) and the application is booted.

Because this is executed so early in the lifecycle you should use caution when accessing other Laravel components. Use of the `App` and `Config` facade are safe, but access to other components should only occur through the `$app['name']` mechanism. And even then, not all components may be loaded.

**This is the ideal place to register middleware!**

The following lists components that can be used because they are part of Laravel's core:

* `$app` - The application
* `$app['config']` - The configuration class
* `$app['events']` - The event dispatcher
* `$app['router']` - The router
* `$app['exception']` - The exception handler

_(Other components may be available for your application depending on the configuration of service providers. You'll need to test for your particular application.)_

### onbooting.php

This file is loaded after all service providers are booted, but before the application is booted. Since service providers are now booted you are safe to use Laravel facades as you normally would.

**Please Note** since your application's `app/start/global.php`, `app/routes.php`, and `app/filters.php` files are not loaded, do not do anything within this hook that requires a functionality set within those files.

### onboot.php

This file is loaded after the application is booted. Your application's `app/start/global.php`, `app/routes.php`, and `app/filters.php` files should now be loaded.

### ondown.php

This file is only loaded if your application is in maintenance mode.

Remember, this is a filter. You should return a Response or View from this file. If you don't return anything then the next registered `down()` filter (if any) will be called.

### onbefore.php

This file is loaded before the route for the request is determined.

In addition to the `$app` variable you also have a `$request` variable available. You can use the `$request` variable or modify it as needed.

Remember, this is a filter. Most times you'll want `onbefore.php` to return a Request, View, or Redirect.

If you don't return a value, then Laravel will next determine the route and dispatch the request to the route.

### onafter.php

This file is loaded after a response has been returned from dispatching. It's the last step for your application to modify the response before it's sent to the user.

In addition to the `$app` variable you also have `$request` and `$response` available to you.

If you want to change the response, one of the most common ways is with the `$response->setContent()` method.

### onfinish.php

This file is loaded after the response is sent to the user.

In addition to the `$app` variable you also have `$request` and `$response` available to you. Although these variables are available, changing them has no affect to what the user receives.

Normally, this callback is used for logging or some other such activity that you don't want to take time for during the request.

### onshutdown.php

This file is loaded only at the very end of all processing. Laravel is in the process of shutting down when this is executed.


