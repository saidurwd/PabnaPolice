<?php

namespace Tests;

use App\Admin\Options;
use App\Models\Setting;
use App\Policies\AclPolicy;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Tests don't need the compiled admin assets (CI builds them separately to prove the build works).
        $this->withoutVite();

        // These keep per-process static caches; the database is rolled back between tests, so reset them.
        Setting::flush();
        AclPolicy::forget();
        Options::forget();
    }
}
