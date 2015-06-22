# Route Collections and Route Model Binding wrappers for Laravel

## Route collections

Crate a new directory that will store all route collections for your application - example would be directory called `Routes/Collections` under `app/Http`.

```
app/Http/Routes/Collections
```

Inside the directory create a new class that will be used with the given section of your system - say for the Front end of your application, we use `FrontCollection.php` and we put it into another directory - to distinguish between sections - because this one belongs to the front end, we call it `Front`.

```
app/Http/Routes/Collections/Front/FrontCollection.php
```

The new `FrontCollection` class will extend the `SSD\LaravelRoutes\RouteCollectionFactory` and needs to have the method `getNameSpace` that returns the current namespace, which will be used to create a fully qualifying name of each class within the `app/Http/Routes/Collections/Front` directory.

```
// app/Http/Routes/Collections/Front/FrontCollection.php

namespace App\Http\Routes\Collections\Front;

use SSD\LaravelRoutes\RouteCollectionFactory;

class FrontCollection extends RouteCollectionFactory
{

    protected static function getNameSpace()
    {
        return __NAMESPACE__;
    }

}
```

Inside the same directory create a new file for each collection of routes - say for the `Blog` module of your Front section you could use:

```
// app/Http/Routes/Collections/Front/Blog.php

namespace App\Http\Routes\Collections\Front;

use SSD\LaravelRoutes\RouteCollectionContract;

class Blog implements RouteCollectionContract
{

    public function routes()
    {

        app('router')->group(['prefix' => 'blog'], function() {

            app('router')->get('/', 'BlogController@index');

            app('router')->get('latest', 'BlogController@latest');

            app('router')->post('comment', 'BlogController@addComment');

            app('router')->get('comment/{id}', 'BlogController@showComment');

        });

    }

}
```

and perhaps another one for the `Contact` Controller with form submission method:

```
// app/Http/Routes/Collections/Front/Contact.php

namespace App\Http\Routes\Collections\Front;

use SSD\LaravelRoutes\RouteCollectionContract;

class Contact implements RouteCollectionContract
{

    public function routes()
    {

        app('router')->group(['prefix' => 'contact'], function() {

            app('router')->get('/', 'ContactController@index');

            app('router')->post('/', 'ContactController@submit');

        });

    }

}
```

Now all you need to do in your `app/Http/routes.php` file is:

```
use App\Http\Routes\Collections\Front\FrontCollection;

FrontCollection::blog();
FrontCollection::contact();
```

The magically called static methods on the `FrontCollection` are names of the collection classes in `camelCase` - say for instance collection with name `FoodRecepies` would be called as `FrontCollection::foodRecepies()` and so on.

If you want to keep your `routes.php` file even cleaner, you could create a master collection for each section and then enclose all separate route collections inside of it

```
// app/Http/Routes/Collections/Front/Master.php

namespace App\Http\Routes\Collections\Front;

use SSD\LaravelRoutes\RouteCollectionContract;

class Master implements RouteCollectionContract
{

    public function routes()
    {

        FrontCollection::blog();
        FrontCollection::contact();

    }

}
```

and for the `Admin` section (make sure you first create `AdminCollection`)

```
// app/Http/Routes/Collections/Admin/Master.php

namespace App\Http\Routes\Collections\Admin;

use SSD\LaravelRoutes\RouteCollectionContract;

class Master implements RouteCollectionContract
{

    public function routes()
    {

        app('router')->group(
            [
                'prefix' => 'admin',
                'namespace' => 'Admin'
            ],
            function() {

                AdminCollection::auth();

                app('router')->group(
                    [
                        'middleware' => ['admin']
                    ],
                    function() {

                        AdminCollection::blog();
                        AdminCollection::pages();

                    }
                );

            }
        );

    }

}
```

Then simply call it from within the routes.php

```
use App\Http\Routes\Collections\Front\FrontCollection;
use App\Http\Routes\Collections\Admin\AdminCollection;

FrontCollection::master();
AdminCollection::master();
```

### Custom exceptions

The abstract `SSD\LaravelRoutes\RouteCollectionFactory` class can throw either `SSD\LaravelRoutes\Exceptions\InvalidClassName` when the static method name does not correspond to the existing class or `SSD\LaravelRoutes\Exceptions\MissingNamespace` when you forget to declare the `getNameSpace()` method on the class extending `SSD\LaravelRoutes\RouteCollectionFactory`.


## Route model binder

Route model binder allows you to group model bindings.

To start, create a new directory under `app/Http/Routes` called `ModelBindings`

```
app/Http/Routes/ModelBindings
```

Inside this directory create a class corresponding to the model you are trying to define bindings for - for instance, if you had a `Blog` model on which you'd like to define two bindings - one for `blog_id` and the other for the `blog_slug`

```
app('router')->get('blog/{blog_id}', 'BlogController@edit');
app('router')->get('blog/{blog_slug}', 'BlogController@show');
```

your model would look like so

```
// app/Http/Routes/ModelBindings/BlogBinder.php

namespace App\Http\Routes\ModelBindings;

use Illuminate\Routing\Router;
use SSD\LaravelRoutes\RouteModelBinderContract;

use App\Blog;

class BlogBinder implements RouteModelBinderContract
{

    /**
     * Bind Blog route parameters.
     *
     * @param Router $router
     */
    public function bind(Router $router)
    {

        $router->model('blog_id', Blog::class);
        // for version of PHP lower than 5.6 use:
        // $router->model('blog_id', 'App\Blog');

        $router->bind('blog_slug', function($slug) {

            return $this->recordBySlug($slug);

        });

    }

    /**
     * Get record by slug.
     *
     * @param $slug
     * @return mixed
     */
    protected function recordBySlug($slug)
    {

        return Blog::whereSlug($slug)->firstOrFail();

    }


}
```

Add the `scopeWhereSlug()` method to your `Blog` model (or, if you're using slugs on more than one model you could extract it to a Trait)

```
// app/Blog.php

namespace App;

class Blog extends Model
{

    protected $table = 'blog';

    public function scopeWhereSlug($query, $slug)
    {

        return $query->where('slug', '=', $slug);

    }

}
```

Now, with the `BlogBinder` ready, we can add it to the `app/Providers/RouteServiceProvider.php'

```
// app/Providers/RouteServiceProvider.php

namespace App\Providers;

use Illuminate\Routing\Router;
use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;

use SSD\LaravelRoutes\RouteModelBinderFactory;
use App\Http\Routes\ModelBindings\BlogBinder;

class RouteServiceProvider extends ServiceProvider
{

    protected $namespace = 'App\Http\Controllers';

    public function boot(Router $router)
    {

        parent::boot($router);

        RouteModelBinderFactory::bind(new BlogBinder, $router);

    }

    public function map(Router $router)
    {
        $router->group(['namespace' => $this->namespace], function ($router) {
            require app_path('Http/routes.php');
        });
    }
}

```

### Model binding tip

You are more likely to use `slug` with the front end of your application - so just to boost the performance a bit, let's cache the model for the `slug` binding.
To do this - create a new, `BaseBinder` class under `App\Http\Routes\ModelBindings` and to make it easier - let's use `nespot/carbon` package. First add `Carbon` dependency with the composer.

```
composer require nesbot/carbon
```

Now create `BaseBinder` class with the `cache` method. I'm caching for just one day, but feel free to make it as long as you need

```
// app/Http/Routes/ModelBindings/BaseBinder.php

namespace App\Http\Routes\ModelBindings;

use Carbon\Carbon;

abstract class BaseBinder {

    /**
     * Get cached record or cache new one if does not exist.
     *
     * @param $key
     * @param callable $default
     * @return mixed
     */
    protected function cache($key, callable $default)
    {

        $value = app('cache.store')->get($key);

        if (is_null($value)) {

            $arguments = func_get_args();

            $value = call_user_func_array($default, array_splice($arguments, 2));

            app('cache.store')->put($key, $value, Carbon::now()->addDay(1));

        }

        return $value;

    }

}
```

Finally modify the `BlogBinder` class

```
// app/Http/Routes/ModelBindings/BlogBinder.php

namespace App\Http\Routes\ModelBindings;

use Illuminate\Routing\Router;
use SSD\LaravelRoutes\RouteModelBinderContract;

use App\Blog;

class BlogBinder extends BaseBinder implements RouteModelBinderContract
{

    /**
     * Bind Blog route parameters.
     *
     * @param Router $router
     */
    public function bind(Router $router)
    {

        $router->model('blog_id', Blog::class);

        $router->bind('blog_slug', function($slug) {

            return $this->cache(
                'blog.slug.'.$slug,
                [
                    $this,
                    'recordBySlug'
                ],
                $slug
            );

        });

    }

    /**
     * Get record by slug.
     *
     * @param $slug
     * @return mixed
     */
    protected function recordBySlug($slug)
    {

        return Blog::whereSlug($slug)->firstOrFail();

    }


}
```

And now your model binding will first be served from the database, then, every sub-sequent call will be read from cache for a length of one day.
Make sure that when you update record - you also update cached version - or simply remove cache for a corresponding key.