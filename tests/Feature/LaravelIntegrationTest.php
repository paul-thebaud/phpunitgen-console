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
    protected function getPackageProviders($app)
    {
        return [
            PhpUnitGenServiceProvider::class,
        ];
    }

    public function testArtisanCommandCallWorks(): void
    {
        $this->cleanUpGeneratedFiles();

        File::put(app_path('Http/Controllers/Dummy.php'), "<?php\nnamespace App\Http\Controllers;\nclass Dummy {}");

        $this->artisan('phpunitgen', ['source' => app_path('Http/Controllers/Dummy.php')])
            ->expectsOutput("Starting process using default config.")
            ->expectsOutput("Generation is finished!")
            ->expectsOutput('1 source(s) identified')
            ->expectsOutput('1 success(es)')
            ->expectsOutput('0 warning(s)')
            ->expectsOutput('0 error(s)')
            ->assertExitCode(0);

        $this->cleanUpGeneratedFiles();
    }

    public function testArtisanMakeListenerWorks(): void
    {
        $this->cleanUpGeneratedFiles();

        $this->artisan('make:controller', ['name' => 'Dummy'])
            ->expectsOutput("Controller created successfully.")
            ->expectsOutput("Test generated for \"Http/Controllers/Dummy\".")
            ->assertExitCode(0);

        $this->assertTrue(File::exists(app_path('Http/Controllers/Dummy.php')));

        $this->cleanUpGeneratedFiles();
    }

    /**
     * Clean up the generated files.
     */
    protected function cleanUpGeneratedFiles(): void
    {
        if (File::exists(app_path('Http/Controllers/Dummy.php'))) {
            File::delete(app_path('Http/Controllers/Dummy.php'));
        }

        // Since PhpUnitGen uses relative path, tests will be generated in
        // current tests/Feature directory.
        if (File::exists(__DIR__.'/orchestra')) {
            File::deleteDirectory(__DIR__.'/orchestra');
        }
    }
}
