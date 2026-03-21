<?php

uses(
    Tests\DuskTestCase::class,
    // Illuminate\Foundation\Testing\DatabaseMigrations::class,
)->in('Browser');

use Tests\TestCase;

uses(TestCase::class)->in('Feature', 'Unit');
