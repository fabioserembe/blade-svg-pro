<?php

namespace FabioSerembe\BladeSVGPro;

use Illuminate\Support\Facades\Facade;

/**
 * @see \BladeSVGPro\BladeSVGPro\Skeleton\SkeletonClass
 */
class BladeSVGProFacade extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'svg-file-to-blade-component';
    }
}
