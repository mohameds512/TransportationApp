<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Register trigonometric functions for SQLite
        if (config('database.default') === 'sqlite') {
            $pdo = \Illuminate\Support\Facades\DB::connection()->getPdo();

            // Register acos function
            $pdo->sqliteCreateFunction(
                'acos',
                function ($value) {
                    return acos($value);
                },
                1
            );

            // Register cos function
            $pdo->sqliteCreateFunction(
                'cos',
                function ($value) {
                    return cos($value);
                },
                1
            );

            // Register sin function
            $pdo->sqliteCreateFunction(
                'sin',
                function ($value) {
                    return sin($value);
                },
                1
            );

            // Register radians function
            $pdo->sqliteCreateFunction(
                'radians',
                function ($value) {
                    return deg2rad((float)$value);
                },
                1
            );
        }
    }
}
