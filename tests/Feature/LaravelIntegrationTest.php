<?php

declare(strict_types=1);

namespace Tests\PhpUnitGen\Console\Feature;

use Illuminate\Support\Facades\File;
use Orchestra\Testbench\TestCase;
use PhpUnitGen\Console\Adapters\Laravel\PhpUnitGenServiceProvider;

/**
 * Class LaravelIntegrationTest.
 *
 * @author Paul Thébaud <paul.thebaud29@gmail.com>
 */
class LaravelIntegrationTest extends TestCase
{
    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->deleteAffectedDirectories();

        File::makeDirectory(app_path('Http/Controllers'), 0777, true);
        // Since PhpUnitGen uses relative path, tests will be generated in
        // current tests/Feature directory.
        File::makeDirectory(__DIR__.'/orchestra/testbench-core/laravel/app/Http/Controllers', 0777, true);
    }

    /**
     * {@inheritdoc}
     */
    protected function tearDown(): void
    {
        $this->deleteAffectedDirectories();

        parent::tearDown();
    }

    /**
     * Delete the directories used in tests.
     */
    protected function deleteAffectedDirectories(): void
    {
        if (File::exists(app_path('Http/Controllers'))) {
            File::deleteDirectory(app_path('Http/Controllers'));
        }

        if (File::exists(__DIR__.'/orchestra')) {
            File::deleteDirectory(__DIR__.'/orchestra');
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function getPackageProviders($app)
    {
        return [
            PhpUnitGenServiceProvider::class,
        ];
    }

    public function testArtisanCommandCallWorks(): void
    {
        File::put(app_path('Http/Controllers/Dummy.php'), "<?php\nnamespace App\Http\Controllers;\nclass Dummy {}");

        $this->artisan('phpunitgen', ['source' => app_path('Http/Controllers/Dummy.php')])
            ->expectsOutput('Starting process using default config.')
            ->expectsOutput('Generation is finished!')
            ->expectsOutput('1 source(s) identified')
            ->expectsOutput('1 success(es)')
            ->expectsOutput('0 warning(s)')
            ->expectsOutput('0 error(s)')
            ->assertExitCode(0);

        $this->assertTrue(File::exists(__DIR__.'/orchestra/testbench-core/laravel/app/Http/Controllers/DummyTest.php'));
    }

    public function testArtisanMakeListenerWorks(): void
    {
        $this->artisan('make:controller', ['name' => 'Dummy'])
            ->expectsOutput('Controller created successfully.')
            // We won't validate PhpUnitGen written an output, because the
            // Laravel event output is not a testing one when using 5.8.
            //->expectsOutput('Test generated for "Http/Controllers/Dummy".')
            ->assertExitCode(0);

        $this->assertTrue(File::exists(__DIR__.'/orchestra/testbench-core/laravel/app/Http/Controllers/DummyTest.php'));
        $this->assertTrue(File::exists(app_path('Http/Controllers/Dummy.php')));
    }
}
