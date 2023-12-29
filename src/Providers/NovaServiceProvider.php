<?php

namespace UnknowSk\Nova\Providers;

use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Gate;
use Laravel\Nova\Http\Requests\NovaRequest;
use Laravel\Nova\Menu\MenuItem;
use Laravel\Nova\Menu\MenuSection;
use Laravel\Nova\Nova;
use Laravel\Nova\NovaApplicationServiceProvider;

class NovaServiceProvider extends NovaApplicationServiceProvider
{
    protected function menuItemFromArray($item)
    {
        if (isset($item['children'])) {
            $childrens = [];
            foreach ($item['children'] as $key => $child) {
                if (is_array($child)) {
                    $child = $this->menuItemFromArray($child);
                } elseif (is_string($child) && class_exists($child)) {
                    $child = MenuItem::resource($child);
                }

                $childrens[] = $child;
            }

            $item = MenuSection::make('Customers', $childrens)->collapsable();
        } elseif (isset($item['dashboard'])) {
            $item = MenuItem::dashboard($item['dashboard']);

            if (isset($item['name'])) {
                $item->name($item['name']);
            }
        } else {
            $item = MenuItem::make($item['name']);
        }

        if (isset($item['icon'])) {
            $item->icon($item['icon']);
        }

        if (isset($item['path'])) {
            $item->path($item['path']);
        }

        if (isset($item['canSee'])) {
            $item->canSee(function ($request) use ($item) {
                return $request->user()->can($item['canSee']);
            });
        }

        return $item;
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        parent::boot();

        Nova::footer(function ($request) {
            return Blade::render('
        <div class="mt-8 leading-normal text-xs text-gray-500 space-y-1">
            <p class="text-center">© {{date("Y")}} {{config("app.name")}}</p>
        </div>
            ');
        });

        if (config('nova.settings.enable_breadcrumbs')) {
            Nova::withBreadcrumbs(fn (NovaRequest $request) => $request->user()?->wantsBreadcrumbs());
        }

        if (config('nova.settings.enable_rtl')) {
            Nova::enableRTL(fn (Request $request) => $request->user()?->wantsRTL());
        }

        if (!config('nova.settings.enable_global_search')) {
            Nova::withoutGlobalSearch();
        }

        if (!config('nova.settings.enable_theme_switcher')) {
            Nova::withoutThemeSwitcher();
        }

        Nova::report(function ($exception) {
            foreach ((array)config('nova.settings.enable_theme_switcher', []) as $handler) {
                if (app()->bound($handler)) {
                    app($handler)->captureException($exception);
                }
            }
        });

        if ($menus = config('nova.menu.main', [])) {
            Nova::mainMenu(function (Request $request) use ($menus) {
                $return = [
                    MenuSection::dashboard(Main::class)->icon('chart-bar'),
                ];

                foreach ($menus as $key => $item) {
                    if (!($item instanceof MenuItem) && !($item instanceof MenuSection)) {
                        if (is_array($item)) {
                            $item = $this->menuItemFromArray($item);
                        } elseif (is_string($item) && class_exists($item)) {
                            $item = MenuItem::resource($item);
                        }
                    } else {
                        $return[] = $item;
                    }
                }

                return $return;
            });
        }

        if ($menus = config('nova.menu.user', [])) {
            Nova::userMenu(function (Request $request, Menu $menu) use ($menus) {
                foreach ($menus as $key => $item) {
                    if (!($item instanceof MenuItem) && !($item instanceof MenuSection)) {
                        if (is_array($item)) {
                            $item = $this->menuItemFromArray($item);
                        } else {
                            $item = MenuItem::make($key)->path($item);
                        }
                    }
                    $menu->append($item);
                }

                return $menu;
            });
        }
    }

    /**
     * Register the Nova routes.
     *
     * @return void
     */
    protected function routes()
    {
        Nova::routes()
            ->withAuthenticationRoutes()
            ->withPasswordResetRoutes()
            ->register();
    }

    /**
     * Register the Nova gate.
     *
     * This gate determines who can access Nova in non-local environments.
     *
     * @return void
     */
    protected function gate()
    {
        Gate::define('viewNova', function ($user) {
            return in_array($user->email, [
                // @todo
            ]);
        });
    }

    /**
     * Get the dashboards that should be listed in the Nova sidebar.
     *
     * @return array
     */
    protected function dashboards()
    {
        $return = [];
        $dashboards = config('nova.main.dashboards', [\UnknowSk\Nova\Dashboards\Main::class]);

        foreach ($dashboards as $dashboard) {
            if (is_string($dashboard) && class_exists($dashboard)) {
                $return[] = new $dashboard();
            } elseif (is_object($dashboard)) {
                $return[] = $dashboard;
            } elseif (is_callable($dashboard)) {
                $return[] = $dashboard();
            }
        }

        return $return;
    }

    /**
     * Get the tools that should be listed in the Nova sidebar.
     *
     * @return array
     */
    public function tools()
    {
        $return = [];
        $tools = config('nova.main.tools', []);

        foreach ($tools as $tool) {
            if (is_string($tool) && class_exists($tool)) {
                $return[] = new $tool();
            } elseif (is_object($tool)) {
                $return[] = $tool;
            } elseif (is_callable($tool)) {
                $return[] = $tool();
            }
        }

        $return[] = (new \Statikbe\NovaTranslationManager\TranslationManager())
            ->canSee(function ($request) {
                return $request->user()->isSuperAdmin();
            });

        $return[] = (new \UnknowSk\Nova\Tools\NovaPermissionTool())
            ->canSee(function ($request) {
                return $request->user()->isSuperAdmin();
            })
            ->rolePolicy(\UnknowSk\Nova\Policies\RolePolicy::class)
            ->permissionPolicy(\UnknowSk\Nova\Policies\PermissionPolicy::class);

        return $return;
    }

    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }
}
