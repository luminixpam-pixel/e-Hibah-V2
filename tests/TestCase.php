<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Aset Vite (public/build/manifest.json) belum tentu ada di mesin
        // dev; halaman yang memakai layout guest (@vite) akan 500 tanpanya.
        // Test tidak butuh CSS/JS sungguhan, jadi matikan Vite di semua test.
        $this->withoutVite();
    }
}
