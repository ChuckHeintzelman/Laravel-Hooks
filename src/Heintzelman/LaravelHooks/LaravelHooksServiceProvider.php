<?php namespace Heintzelman\LaravelHooks;

use File;
use Illuminate\Support\ServiceProvider;

class LaravelHooksServiceProvider extends ServiceProvider {

    protected $defer = false;
    protected $files;

    /**
     * Register other hooks for later in the lifecycle
     */
    public function boot()
    {
        $this->registerOnBooted();
        $this->registerOnDown();
        $this->registerOnBefore();
        $this->registerOnAfter();
        $this->registerOnFinish();
        $this->registerOnShutdown();
    }

    /**
     * Register hooks early in the lifecycle
     */
    public function register()
    {
        $this->files = $this->app['files'];
        $this->registerPreboot();
        $this->registerOnBooting();
    }

    /**
     * Load preboot.php now, if exists
     */
    protected function registerPreboot()
    {
        $file = $this->hookName('preboot');
        if ($file)
        {
            static::load($this->app, $file);
        }
    }

    /**
     * Set up booting callback if found
     */
    protected function registerOnBooting()
    {
        $file = $this->hookName('onbooting');
        if ($file)
        {
            $this->app->booting(function ($app) use ($file)
            {
                static::load($app, $file);
            });
        }
    }

    /**
     * Set up booted callback if found
     */
    protected function registerOnBooted()
    {
        $file = $this->hookName('onbooted');
        if ($file)
        {
            $this->app->booted(function ($app) use ($file)
            {
                static::load($app, $file);
            });
        }
    }

    /**
     * Set up down listener if found
     */
    protected function registerOnDown()
    {
        $file = $this->hookName('ondown');
        if ($file)
        {
            $this->app->down(function () use ($file)
            {
                $result = static::load($this->app, $file);
                if ($result !== 1)
                {
                    return $result;
                }
            });
        }
    }

    /**
     * Set up before listener if found
     */
    protected function registerOnBefore()
    {
        $file = $this->hookName('onbefore');
        if ($file)
        {
            $this->app->before(function ($request) use ($file)
            {
                $result = static::load($this->app, $file, compact('request'));
                if ($result !== 1)
                {
                    return $result;
                }
            });
        }
    }

    /**
     * Set up after listener if found
     */
    protected function registerOnAfter()
    {
        $file = $this->hookName('onafter');
        if ($file)
        {
            $this->app->after(function ($request, $response) use ($file)
            {
                $result = static::load($this->app, $file, compact('request', 'response'));
                if ($result !== 1)
                {
                    return $result;
                }
            });
        }
    }

    /**
     * Set up finish listener if found
     */
    protected function registerOnFinish()
    {
        $file = $this->hookName('onfinish');
        if ($file)
        {
            $this->app->finish(function ($request, $response) use ($file)
            {
                static::load($this->app, $file, compact('request', 'response'));
            });
        }
    }

    /**
     * Set up shutdown listener if found
     */
    protected function registerOnShutdown()
    {
        $file = $this->hookName('onshutdown');
        if ($file)
        {
            $this->app->shutdown(function ($app) use ($file)
            {
                static::load($app, $file);
            });
        }
    }

    /**
     * Require the file, return results
     */
    protected static function load($app, $_file, $variables = array())
    {
        if ($variables)
        {
            extract($variables);
        }
        return require($_file);
    }

    /**
     * Return the hook filename or null if not found
     */
    protected function hookName($hook)
    {
        if ( ! $this->app['config']->get('hooks.'.$hook, true))
        {
            return null;
        }
        $file = app_path().'/hooks/'.$hook.'.php';
        if ($this->files->exists($file))
        {
            return $file;
        }
        $file = app_path().'/start/'.$hook.'.php';
        if ($this->files->exists($file))
        {
            return $file;
        }
        return null;
    }

}