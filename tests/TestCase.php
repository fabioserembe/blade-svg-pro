<?php

namespace FabioSerembe\BladeSVGPro\Tests;

use FabioSerembe\BladeSVGPro\BladeSVGProServiceProvider;
use Orchestra\Testbench\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function getPackageProviders($app): array
    {
        return [BladeSVGProServiceProvider::class];
    }
}
