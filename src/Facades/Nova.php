<?php

namespace UnknowSk\Nova\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @see \UnknowSk\Nova\Nova
 */
class Nova extends Facade
{
    protected static function getFacadeAccessor()
    {
        return \UnknowSk\Nova\Nova::class;
    }
}
