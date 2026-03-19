<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Str;

class MakeTelegramCommandCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'make:bot-command {name}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Creates telegram command baseplate. Specific for this project.';

    private Filesystem $files;

    public function __construct(Filesystem $files)
    {
        parent::__construct();
        $this->files = $files;
    }

    public function handle()
    {
        [$name, $path] = $this->parsePath($this->argument('name'));
        $this->createServiceClass($name, $path);
    }

    protected function createServiceClass(string $name, string $path)
    {
        // Путь для сохранения файла
        $path = app_path("BotCommands/{$path}/{$name}.php");

        // Проверка существования файла
        if ($this->files->exists($path)) {
            $this->error("Command {$name} already exists!");

            return false;
        }

        // Создание директории если не существует
        $this->makeDirectory($path);

        // Генерация содержимого
        $stub = $this->getStub();
        $content = str_replace(
            ['{{class}}', '{{namespace}}'],
            [$name, 'App\\BotCommands'.$this->generateNamespace($path)],
            $stub
        );

        // Сохранение файла
        $this->files->put($path, $content);
        $this->info("Command {$name} created successfully!");
    }

    protected function makeDirectory($path)
    {
        if (! $this->files->isDirectory(dirname($path))) {
            $this->files->makeDirectory(dirname($path), 0755, true);
        }
    }

    protected function getStub()
    {
        $stubPath = base_path('stubs/telegram-command.stub');

        if ($this->files->exists($stubPath)) {
            return $this->files->get($stubPath);
        }

        return '';
    }

    protected function parsePath(string $str)
    {
        if (Str::contains($str, '\\')) {
            $path = Str::beforeLast($str, '\\');
            $name = Str::afterLast($str, '\\');
        } else {
            $path = '';
            $name = $str;
        }

        return [$name, Str::replace('\\', '/', $path)];
    }

    protected function generateNamespace(string $path)
    {
        if (! $path) {
            return '';
        }

        return '\\'.Str::replace('/', '\\', $path);
    }
}
