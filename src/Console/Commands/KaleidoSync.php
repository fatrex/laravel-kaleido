<?php

namespace Fatrex\LaravelKaleido\Console\Commands;

use Fatrex\LaravelKaleido\Services\MigrationGenerator;
use Fatrex\LaravelKaleido\Services\Parser;
use Fatrex\LaravelKaleido\Services\SchemaDiff;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class KaleidoSync extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'kaleido:sync';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Parse the provided .kld file and update the database schema';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $verbose = $this->option('verbose');

        // TODO:
        // 1. Find and read the schema.kld file.
        // 2. Parse the DSL into an AST (Abstract Syntax Tree).
        // 3. Get the last known schema state.
        // 4. Compare the new schema with the old one to find changes.
        // 5. Generate migrations for the changes.
        // 6. Generate models for the schema.
        // 7. Save the new schema state.

        $this->info('Starting schema sync...');

        $dlsPath = database_path('schema.kld');
        $currentSchemaPath = database_path('schema.kld.lock');

        if (!File::exists($dlsPath)) {
            $this->error('Schema file not found: ' . $dlsPath);
            return;
        }

        $schemaContent = File::get($dlsPath);

        $parser = new Parser();
        $parsedSchema = $parser->parse($schemaContent);

        if ($verbose) {
            $this->info("Parsed schema: \n");
            foreach ($parsedSchema as $model) {
                $this->info($model['name']);
                foreach ($model['fields'] as $field => $attributes) {
                    $this->info($field . ' ' . $attributes['type']);
                }
                $this->info("\n");
            }
            $this->info("\n");
        }

        $oldSchema = json_decode(File::get($currentSchemaPath), true);
        $differ = new SchemaDiff();
        $changes = $differ->diff($oldSchema, $parsedSchema);

        if (empty($changes)) {
            $this->info('No changes detected. Your schema is up to date.');
            return 0;
        }

        $migrationGenerator = new MigrationGenerator();
        $migrationGenerator->generate($changes);

        $this->info('Schema sync completed.');
    }
}
