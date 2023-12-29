<?php

namespace UnknowSk\Nova\Resources;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Laravel\Nova\Fields\DateTime;
use Laravel\Nova\Fields\ID;
use Laravel\Nova\Fields\MorphToMany;
use Laravel\Nova\Fields\Select;
use Laravel\Nova\Fields\Text;
use Laravel\Nova\Http\Requests\NovaRequest;
use Laravel\Nova\Nova;
use Laravel\Nova\Resource;
use Spatie\Permission\PermissionRegistrar;
use UnknowSk\Nova\Fields\HigherOrderTapProxy;
use UnknowSk\Nova\Fields\PermissionBooleanGroup;

class Role extends Resource
{
    /**
     * The model the resource corresponds to.
     *
     * @var string
     */
    public static $model = \Spatie\Permission\Models\Role::class;

    /**
     * The single value that should be used to represent the resource when being displayed.
     *
     * @var string
     */
    public static $title = 'name';

    /**
     * The columns that should be searched.
     *
     * @var array
     */
    public static $search = [
        'name',
    ];

    /**
     * The relationships that should be eager loaded on index queries.
     *
     * @var array
     */
    public static $with = [
        'permissions',
    ];

    public static function getModel()
    {
        $object = app(PermissionRegistrar::class)->getRoleClass();

        return \is_string($object) ? app($object) : $object;
    }

    /**
     * Get the logical group associated with the resource.
     *
     * @return string
     */
    public static function group()
    {
        return __('nova::navigation.sidebar-label');
    }

    /**
     * Determine if this resource is available for navigation.
     *
     * @return bool
     */
    public static function availableForNavigation(Request $request)
    {
        return Gate::allows('viewAny', app(PermissionRegistrar::class)->getRoleClass());
    }

    public static function label()
    {
        return __('nova::resources.Roles');
    }

    public static function singularLabel()
    {
        return __('nova::resources.Role');
    }

    /**
     * Get the fields displayed by the resource.
     *
     * @return array
     */
    public function fields(NovaRequest $request)
    {
        $guardOptions = collect(config('auth.guards'))->mapWithKeys(function ($value, $key) {
            return [$key => $key];
        });

        $userResource = Nova::resourceForModel(getModelForGuard($this->guard_name ?? config('auth.defaults.guard')));

        return [
            ID::make()->sortable(),

            Text::make(__('nova::roles.name'), 'name')
                ->rules(['required', 'string', 'max:255'])
                ->creationRules('unique:'.config('permission.table_names.roles'))
                ->updateRules('unique:'.config('permission.table_names.roles').',name,{{resourceId}}'),

            Select::make(__('nova::roles.guard_name'), 'guard_name')
                ->options($guardOptions->toArray())
                ->rules(['required', Rule::in($guardOptions)]),

            DateTime::make(__('nova::roles.created_at'), 'created_at')->exceptOnForms(),
            DateTime::make(__('nova::roles.updated_at'), 'updated_at')->exceptOnForms(),

            PermissionBooleanGroup::make(__('nova::roles.permissions'), 'permissions'),

            MorphToMany::make($userResource::label(), 'users', $userResource)
                ->searchable()
                ->singularLabel($userResource::singularLabel()),
        ];
    }

    /**
     * Get the cards available for the request.
     *
     * @return array
     */
    public function cards(NovaRequest $request)
    {
        return [];
    }

    /**
     * Get the filters available for the resource.
     *
     * @return array
     */
    public function filters(NovaRequest $request)
    {
        return [];
    }

    /**
     * Get the lenses available for the resource.
     *
     * @return array
     */
    public function lenses(NovaRequest $request)
    {
        return [];
    }

    /**
     * Get the actions available for the resource.
     *
     * @return array
     */
    public function actions(NovaRequest $request)
    {
        return [];
    }

    /**
     * Allow the permissions to replicate with the Role
     *
     * @return Role|HigherOrderTapProxy|mixed
     */
    public function replicate()
    {
        return tap(parent::replicate(), function ($resource) {
            $model = $resource->model();
            $model->name = 'Duplicate of '.$model->name;
            $model->permissions = parent::model()->permissions;
        });
    }
}
