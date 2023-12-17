<?php

declare(strict_types=1);

namespace Tests\PhpUnitGen\Console\Feature;

use Illuminate\Console\Events\CommandFinished;
use Illuminate\Foundation\Application;
use League\Container\Container;
use Mockery;
use Mockery\Mock;
use PhpUnitGen\Console\Adapters\Laravel\CommandFinishedListener;
use PhpUnitGen\Console\Adapters\Laravel\PhpUnitGenCommand;
use PhpUnitGen\Console\Container\ConsoleContainerFactory;
use PhpUnitGen\Console\Contracts\Config\ConfigResolver;
use PhpUnitGen\Console\Contracts\Execution\Runner;
use PhpUnitGen\Console\Contracts\Files\Filesystem;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Tests\PhpUnitGen\Console\TestCase;

/**
 * Class CommandFinishedListenerTest.
 */
class CommandFinishedListenerTest extends TestCase
{
    /**
     * @var Filesystem|Mock
     */
    protected $filesystem;

    /**
     * @var Application|Mock
     */
    protected $application;

    /**
     * @var InputInterface|Mock
     */
    protected $input;

    /**
     * @var OutputInterface|Mock
     */
    protected $output;

    /**
     * @var CommandFinishedListener
     */
    protected $commandFinishedListener;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->filesystem = Mockery::mock(Filesystem::class);
        $this->application = Mockery::mock(Application::class);
        $this->input = Mockery::mock(InputInterface::class);
        $this->output = Mockery::mock(OutputInterface::class);

        /** @var Container $container */
        $container = ConsoleContainerFactory::make();
        $container->add(Filesystem::class, $this->filesystem);

        $this->commandFinishedListener = new CommandFinishedListener(
            $this->application,
            $container->get(ConfigResolver::class),
            $container->get(PhpUnitGenCommand::class),
            $container->get(Runner::class),
            $container->get(Filesystem::class)
        );
    }

    public function testItIgnoresNonMakeCommand(): void
    {
        $event = new CommandFinished('key:generate', $this->input, $this->output, 0);

        $this->filesystem->shouldReceive('has')->never();

        $this->assertSame(0, $this->commandFinishedListener->handle($event));
    }

    public function testItIgnoresKOExitCode(): void
    {
        $event = new CommandFinished('make:model', $this->input, $this->output, 1);

        $this->filesystem->shouldReceive('has')->never();

        $this->assertSame(0, $this->commandFinishedListener->handle($event));
    }

    public function testItIgnoresWhenHelpRequested(): void
    {
        $event = new CommandFinished('make:model', $this->input, $this->output, 0);

        $this->input->shouldReceive('getOption')
            ->with('help')
            ->andReturnTrue();

        $this->filesystem->shouldReceive('has')->never();

        $this->assertSame(0, $this->commandFinishedListener->handle($event));
    }

    public function testItIgnoresWhenVersionRequested(): void
    {
        $event = new CommandFinished('make:model', $this->input, $this->output, 0);

        $this->input->shouldReceive('getOption')
            ->with('help')
            ->andReturnFalse();
        $this->input->shouldReceive('getOption')
            ->with('version')
            ->andReturnTrue();

        $this->filesystem->shouldReceive('has')->never();

        $this->assertSame(0, $this->commandFinishedListener->handle($event));
    }

    public function testItIgnoresWhenDisableGenerationOnMake(): void
    {
        $event = new CommandFinished('make:model', $this->input, $this->output, 0);

        $this->input->shouldReceive('getOption')
            ->with('help')
            ->andReturnFalse();
        $this->input->shouldReceive('getOption')
            ->with('version')
            ->andReturnFalse();

        $this->filesystem->shouldReceive('has')
            ->with('phpunitgen.php')
            ->andReturnTrue();
        $this->filesystem->shouldReceive('read')
            ->with('phpunitgen.php')
            ->andReturn('<?php return ["generateOnMake" => false];');

        $this->assertSame(0, $this->commandFinishedListener->handle($event));
    }

    public function testItIgnoresWhenUnknownMake(): void
    {
        $event = new CommandFinished('make:unknown', $this->input, $this->output, 0);

        $this->input->shouldReceive('getOption')
            ->with('help')
            ->andReturnFalse();
        $this->input->shouldReceive('getOption')
            ->with('version')
            ->andReturnFalse();
        $this->input->shouldReceive('getArgument')
            ->with('name')
            ->andReturn('Unknown');

        $this->filesystem->shouldReceive('has')
            ->with('phpunitgen.php')
            ->andReturnTrue();
        $this->filesystem->shouldReceive('read')
            ->with('phpunitgen.php')
            ->andReturn('<?php return [];');

        $this->assertSame(0, $this->commandFinishedListener->handle($event));
    }

    public function testItRunsWithModelWithoutControllerOrError(): void
    {
        $event = new CommandFinished('make:model', $this->input, $this->output, 0);

        $this->input->shouldReceive('getOption')
            ->with('help')
            ->andReturnFalse();
        $this->input->shouldReceive('getOption')
            ->with('version')
            ->andReturnFalse();
        $this->input->shouldReceive('getArgument')
            ->with('name')
            ->andReturn('Post');
        $this->input->shouldReceive('getOption')
            ->with('controller')
            ->andReturnFalse();
        $this->output->shouldReceive('isVeryVerbose')
            ->andReturnFalse();
        $this->output->shouldReceive('isQuiet')
            ->andReturnFalse();
        $this->output->shouldReceive('write')
            ->with('<fg=green>Test generated for "Post".</>', true);

        $this->application->shouldReceive('basePath')
            ->with('app/Post.php')
            ->andReturn('/john/app/Post.php');

        $this->filesystem->shouldReceive('has')
            ->with('phpunitgen.php')
            ->andReturnTrue();
        $this->filesystem->shouldReceive('read')
            ->with('phpunitgen.php')
            ->andReturn('<?php return [];');
        $this->filesystem->shouldReceive('isFile')
            ->with('tests')
            ->andReturnFalse();
        $this->filesystem->shouldReceive('has')
            ->with('/john/app/Post.php')
            ->andReturnTrue();
        $this->filesystem->shouldReceive('isFile')
            ->with('/john/app/Post.php')
            ->andReturnTrue();
        $this->filesystem->shouldReceive('read')
            ->with('/john/app/Post.php')
            ->andReturn($this->makeClassCodeWithMethod('Post'));
        $this->filesystem->shouldReceive('getRoot')
            ->andReturn('/john/');
        $this->filesystem->shouldReceive('has')
            ->with('tests/Unit/PostTest.php')
            ->andReturnFalse();
        $this->filesystem->shouldReceive('write')
            ->with('tests/Unit/PostTest.php', Mockery::type('string'));

        $this->assertSame(1, $this->commandFinishedListener->handle($event));
    }

    public function testItRunsWithModelAndWritesNothingWhenQuiet(): void
    {
        $event = new CommandFinished('make:model', $this->input, $this->output, 0);

        $this->input->shouldReceive('getOption')
            ->with('help')
            ->andReturnFalse();
        $this->input->shouldReceive('getOption')
            ->with('version')
            ->andReturnFalse();
        $this->input->shouldReceive('getArgument')
            ->with('name')
            ->andReturn('Post');
        $this->input->shouldReceive('getOption')
            ->with('controller')
            ->andReturnFalse();
        $this->output->shouldReceive('isVeryVerbose')
            ->andReturnFalse();
        $this->output->shouldReceive('isQuiet')
            ->andReturnTrue();
        $this->output->shouldReceive('write')->never();

        $this->application->shouldReceive('basePath')
            ->with('app/Post.php')
            ->andReturn('/john/app/Post.php');

        $this->filesystem->shouldReceive('has')
            ->with('phpunitgen.php')
            ->andReturnTrue();
        $this->filesystem->shouldReceive('read')
            ->with('phpunitgen.php')
            ->andReturn('<?php return [];');
        $this->filesystem->shouldReceive('isFile')
            ->with('tests')
            ->andReturnFalse();
        $this->filesystem->shouldReceive('has')
            ->with('/john/app/Post.php')
            ->andReturnTrue();
        $this->filesystem->shouldReceive('isFile')
            ->with('/john/app/Post.php')
            ->andReturnTrue();
        $this->filesystem->shouldReceive('read')
            ->with('/john/app/Post.php')
            ->andReturn($this->makeClassCodeWithMethod('Post'));
        $this->filesystem->shouldReceive('getRoot')
            ->andReturn('/john/');
        $this->filesystem->shouldReceive('has')
            ->with('tests/Unit/PostTest.php')
            ->andReturnFalse();
        $this->filesystem->shouldReceive('write')
            ->with('tests/Unit/PostTest.php', Mockery::type('string'));

        $this->assertSame(1, $this->commandFinishedListener->handle($event));
    }

    public function testItRunsWithModelAndControllerWithoutError(): void
    {
        $event = new CommandFinished('make:model', $this->input, $this->output, 0);

        $this->input->shouldReceive('getOption')
            ->with('help')
            ->andReturnFalse();
        $this->input->shouldReceive('getOption')
            ->with('version')
            ->andReturnFalse();
        $this->input->shouldReceive('getArgument')
            ->with('name')
            ->andReturn('Post');
        $this->input->shouldReceive('getOption')
            ->with('controller')
            ->andReturnTrue();
        $this->output->shouldReceive('isVeryVerbose')
            ->andReturnFalse();
        $this->output->shouldReceive('isQuiet')
            ->andReturnFalse();
        $this->output->shouldReceive('write')
            ->with('<fg=green>Test generated for "Post".</>', true);
        $this->output->shouldReceive('write')
            ->with('<fg=green>Test generated for "Http/Controllers/PostController".</>', true);

        $this->application->shouldReceive('basePath')
            ->with('app/Post.php')
            ->andReturn('/john/app/Post.php');
        $this->application->shouldReceive('basePath')
            ->with('app/Http/Controllers/PostController.php')
            ->andReturn('/john/app/Http/Controllers/PostController.php');

        $this->filesystem->shouldReceive('has')
            ->with('phpunitgen.php')
            ->andReturnTrue();
        $this->filesystem->shouldReceive('read')
            ->with('phpunitgen.php')
            ->andReturn('<?php return [];');
        $this->filesystem->shouldReceive('isFile')
            ->with('tests')
            ->andReturnFalse();
        $this->filesystem->shouldReceive('has')
            ->with('/john/app/Post.php')
            ->andReturnTrue();
        $this->filesystem->shouldReceive('isFile')
            ->with('/john/app/Post.php')
            ->andReturnTrue();
        $this->filesystem->shouldReceive('read')
            ->with('/john/app/Post.php')
            ->andReturn($this->makeClassCodeWithMethod('Post', 'App'));
        $this->filesystem->shouldReceive('getRoot')
            ->andReturn('/john/');
        $this->filesystem->shouldReceive('has')
            ->with('tests/Unit/PostTest.php')
            ->andReturnFalse();
        $this->filesystem->shouldReceive('write')
            ->with('tests/Unit/PostTest.php', Mockery::type('string'));
        $this->filesystem->shouldReceive('isFile')
            ->with('/john/app/Http/Controllers/PostController.php')
            ->andReturnTrue();
        $this->filesystem->shouldReceive('read')
            ->with('/john/app/Http/Controllers/PostController.php')
            ->andReturn($this->makeClassCodeWithMethod('PostController', 'App\\Http\\Controllers'));
        $this->filesystem->shouldReceive('getRoot')
            ->andReturn('/john/');
        $this->filesystem->shouldReceive('has')
            ->with('tests/Feature/Http/Controllers/PostControllerTest.php')
            ->andReturnFalse();
        $this->filesystem->shouldReceive('write')
            ->with(
                'tests/Feature/Http/Controllers/PostControllerTest.php',
                Mockery::type('string')
            );

        $this->assertSame(2, $this->commandFinishedListener->handle($event));
    }

    public function testItRunsWithModelWithoutControllerOrErrorUsingLaravel8Style(): void
    {
        $event = new CommandFinished('make:model', $this->input, $this->output, 0);

        $this->input->shouldReceive('getOption')
            ->with('help')
            ->andReturnFalse();
        $this->input->shouldReceive('getOption')
            ->with('version')
            ->andReturnFalse();
        $this->input->shouldReceive('getArgument')
            ->with('name')
            ->andReturn('Post');
        $this->input->shouldReceive('getOption')
            ->with('controller')
            ->andReturnFalse();
        $this->output->shouldReceive('isVeryVerbose')
            ->andReturnFalse();
        $this->output->shouldReceive('isQuiet')
            ->andReturnFalse();
        $this->output->shouldReceive('write')
            ->with('<fg=green>Test generated for "Models/Post".</>', true);

        $this->application->shouldReceive('basePath')
            ->with('app/Post.php')
            ->andReturn('/john/app/Post.php');
        $this->application->shouldReceive('basePath')
            ->with('app/Models/Post.php')
            ->andReturn('/john/app/Models/Post.php');

        $this->filesystem->shouldReceive('has')
            ->with('phpunitgen.php')
            ->andReturnTrue();
        $this->filesystem->shouldReceive('read')
            ->with('phpunitgen.php')
            ->andReturn('<?php return [];');
        $this->filesystem->shouldReceive('isFile')
            ->with('tests')
            ->andReturnFalse();
        $this->filesystem->shouldReceive('has')
            ->with('/john/app/Post.php')
            ->andReturnFalse();
        $this->filesystem->shouldReceive('isFile')
            ->with('/john/app/Models/Post.php')
            ->andReturnTrue();
        $this->filesystem->shouldReceive('read')
            ->with('/john/app/Models/Post.php')
            ->andReturn($this->makeClassCodeWithMethod('Post'));
        $this->filesystem->shouldReceive('getRoot')
            ->andReturn('/john/');
        $this->filesystem->shouldReceive('has')
            ->with('tests/Unit/Models/PostTest.php')
            ->andReturnFalse();
        $this->filesystem->shouldReceive('write')
            ->with('tests/Unit/Models/PostTest.php', Mockery::type('string'));

        $this->assertSame(1, $this->commandFinishedListener->handle($event));
    }

    /**
     * @param string $expectedPath
     * @param string $makeName
     *
     * @dataProvider runWithClassDataProvider
     */
    public function testItRunsWithClass(string $expectedPath, string $makeName): void
    {
        $event = new CommandFinished('make:'.$makeName, $this->input, $this->output, 0);

        $this->input->shouldReceive('getOption')
            ->with('help')
            ->andReturnFalse();
        $this->input->shouldReceive('getOption')
            ->with('version')
            ->andReturnFalse();
        $this->input->shouldReceive('getArgument')
            ->with('name')
            ->andReturn('Foo');
        $this->output->shouldReceive('isVeryVerbose')
            ->andReturnFalse();
        $this->output->shouldReceive('isQuiet')
            ->andReturnFalse();
        $this->output->shouldReceive('write')
            ->with('<fg=green>Test generated for "'.$expectedPath.'/Foo".</>', true);

        $this->application->shouldReceive('basePath')
            ->with('app/'.$expectedPath.'/Foo.php')
            ->andReturn('/john/app/'.$expectedPath.'/Foo.php');

        $this->filesystem->shouldReceive('has')
            ->with('phpunitgen.php')
            ->andReturnTrue();
        $this->filesystem->shouldReceive('read')
            ->with('phpunitgen.php')
            ->andReturn('<?php return [];');
        $this->filesystem->shouldReceive('isFile')
            ->with('tests')
            ->andReturnFalse();
        $this->filesystem->shouldReceive('isFile')
            ->with('/john/app/'.$expectedPath.'/Foo.php')
            ->andReturnTrue();
        $this->filesystem->shouldReceive('read')
            ->with('/john/app/'.$expectedPath.'/Foo.php')
            ->andReturn($this->makeClassCodeWithMethod('Foo'));
        $this->filesystem->shouldReceive('getRoot')
            ->andReturn('/john/');
        $this->filesystem->shouldReceive('has')
            ->with('tests/Unit/'.$expectedPath.'/FooTest.php')
            ->andReturnFalse();
        $this->filesystem->shouldReceive('write')
            ->with('tests/Unit/'.$expectedPath.'/FooTest.php', Mockery::type('string'));

        $this->assertSame(1, $this->commandFinishedListener->handle($event));
    }

    public static function runWithClassDataProvider(): array
    {
        return [
            ['Broadcasting', 'channel'],
            ['Console/Commands', 'command'],
            ['Http/Controllers', 'controller'],
            ['Events', 'event'],
            ['Exceptions', 'exception'],
            ['Jobs', 'job'],
            ['Listeners', 'listener'],
            ['Mail', 'mail'],
            ['Http/Middleware', 'middleware'],
            ['Notifications', 'notification'],
            ['Observers', 'observer'],
            ['Policies', 'policy'],
            ['Providers', 'provider'],
            ['Http/Requests', 'request'],
            ['Http/Resources', 'resource'],
            ['Rules', 'rule'],
        ];
    }

    public function testItRunsWithModelWithErrorAndNotVerbose(): void
    {
        $event = new CommandFinished('make:model', $this->input, $this->output, 0);

        $this->input->shouldReceive('getOption')
            ->with('help')
            ->andReturnFalse();
        $this->input->shouldReceive('getOption')
            ->with('version')
            ->andReturnFalse();
        $this->input->shouldReceive('getArgument')
            ->with('name')
            ->andReturn('Post');
        $this->input->shouldReceive('getOption')
            ->with('controller')
            ->andReturnFalse();
        $this->output->shouldReceive('isVeryVerbose')
            ->andReturnFalse();
        $this->output->shouldReceive('isQuiet')
            ->andReturnFalse();
        $this->output->shouldReceive('write')
            ->with('<fg=red>Test generation failed for "Post".</>', true);

        $this->application->shouldReceive('basePath')
            ->with('app/Post.php')
            ->andReturn('/john/app/Post.php');

        $this->filesystem->shouldReceive('has')
            ->with('phpunitgen.php')
            ->andReturnTrue();
        $this->filesystem->shouldReceive('read')
            ->with('phpunitgen.php')
            ->andReturn('<?php return [];');
        $this->filesystem->shouldReceive('has')
            ->with('/john/app/Post.php')
            ->andReturnTrue();
        $this->filesystem->shouldReceive('isFile')
            ->with('tests')
            ->andReturnFalse();
        $this->filesystem->shouldReceive('isFile')
            ->with('/john/app/Post.php')
            ->andReturnTrue();
        $this->filesystem->shouldReceive('read')
            ->with('/john/app/Post.php')
            ->andReturn($this->makeClassCodeWithMethod('Post'));
        $this->filesystem->shouldReceive('getRoot')
            ->andReturn('/john/');
        $this->filesystem->shouldReceive('has')
            ->with('tests/Unit/PostTest.php')
            ->andReturnTrue();
        $this->filesystem->shouldReceive('write')->never();

        $this->commandFinishedListener->handle($event);
    }

    private function makeClassCodeWithMethod(string $name, ?string $namespace = ''): string
    {
        $namespace = $namespace ? " namespace $namespace;" : '';

        return "<?php{$namespace} class $name { public function dummy() {} }";
    }
}
