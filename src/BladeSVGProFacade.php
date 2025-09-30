<?php

namespace FabioSerembe\BladeSVGPro;

use Illuminate\Support\Facades\Facade;

/**
 * @see \FabioSerembe\BladeSVGPro\BladeSVGPro
 */
class BladeSVGProFacade extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'blade-svg-pro';
    }
}
