<?php

namespace Fatrex\LaravelKaleido\Services;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class MigrationGenerator
{
    public function generate(array $changes): string
    {
        foreach ($changes as $change) {
            match($change['type']) {
                'create_table' => $this->generateCreateTable($change['model']),
                //'drop_table' => $this->generateDropTable($change['model']),
                //'update_table' => $this->generateUpdateTable($change['model']),
            };
        }
    }

    private function generateCreateTable(array $model)
    {
        $tableName = Str::snake(Str::plural($model['name']));
        $migrationName = "create_{$tableName}_table";

        Artisan::call('make:migration', ['name' => $migrationName]);
        $migrationFile = $this->findMigrationFile($migrationName);

        if (!$migrationFile) {
            throw new \Exception("Migration file not found for {$migrationName}");
        }
        $schema = $this->buildSchema($model['fields']);

        // Replace the placeholder in the stub
        $stub = File::get($migrationFile);
        $newContent = preg_replace(
            '/Schema::create\(\'[^\']+\', function \(Blueprint \$table\) \{\s*\$table->id\(\);\s*\$table->timestamps\(\);/s',
            "Schema::create('{$tableName}', function (Blueprint \$table) {\n{$schema}\n        });",
            $stub
        );

        File::put($migrationFile, $newContent);
    }

    private function findMigrationFile(string $migrationName): ?string
    {
        $files = File::glob(database_path("migrations/*_{$migrationName}.php"));
        return $files[0] ?? null;
    }

    private function buildSchema(array $fields): string
    {
        $schemaLines = [];
        foreach ($fields as $name => $details) {
            // Simple mapping for now. Can be expanded.
            $line = "            \$table->{$details['type']}('{$name}')";

            foreach ($details['attributes'] as $attribute) {
                if ($attribute === 'primary') {
                    // Handled by id() or specific primary key definition
                    continue;
                } else if ($attribute === 'nullable') {
                    $line .= '->nullable()';
                } else if ($attribute === 'unique') {
                    $line .= '->unique()';
                } else if (str_starts_with($attribute, 'default')) {
                    $line .= "->{$attribute}";
                }
            }
            $schemaLines[] = $line . ';';
        }
        return implode("\n", $schemaLines);
    }
}