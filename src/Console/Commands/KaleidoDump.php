<?php

namespace Fatrex\LaravelKaleido\Console\Commands;

use Fatrex\LaravelKaleido\Services\Parser;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class KaleidoDump extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'kaleidoscope:dump';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Dumps the current database schema to a .kld file';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Dumping database schema...');

        $dlsPath = database_path('schema.kld');

        if (File::exists($dlsPath)) {
            $this->error('Schema file already exists: ' . $dlsPath);
            return;
        }

        $schemaName = Schema::getCurrentSchemaName();
        $currentTables = Schema::getTableListing($schemaName);

        $dslContent = "";
        foreach($currentTables as $table) {
            if (Str::contains($table, ['migrations', 'failed_jobs', 'password_resets', 'cache', 'cache_locks', 'sessions','password_reset_tokens', 'jobs', 'job_batches'])) {
                continue;
            }

            $tableName = Str::replace($schemaName . '.', '', $table);

            $modelName = Str::ucfirst($tableName);
            $modelName = Str::singular($modelName);

            $columns = Schema::getColumns($table);
            $indexes = Schema::getIndexes($table);
            $foreignKeys = Schema::getForeignKeys($table);

            $createdAt = false;
            $updatedAt = false;
            $dslContent .= "model {$modelName} {\n";
            foreach($columns as $column) {

                $columnName = $column['name'];
                $columnType = $this->mapType($column['type']);
                $attributes = [];
                
                foreach ($indexes as $index) {
                    if (in_array($columnName, $index['columns'])) {
                        if ($index['primary']) {
                            $attributes[] = '@primary';
                        } elseif ($index['unique']) {
                            $attributes[] = '@unique';
                        }
                    }
                }

                if ($columnName === 'created_at') {
                    $createdAt = true;
                    continue;
                }
                if ($columnName === 'updated_at') {
                    $updatedAt = true;
                    continue;
                }

                if ($column['nullable']) {
                    $attributes[] = '@nullable';
                }

                if ($column['auto_increment']) {
                    $attributes[] = '@autoIncrement';
                }

                if ($column['default']) {
                    $attributes[] = '@default(' . $column['default'] . ')';
                }

                $dslContent .= "    {$columnName}: {$columnType} " . implode(' ', $attributes) . "\n";
            }

            if ($createdAt && $updatedAt) {
                $dslContent .= "    timestamps\n";
            }


            $foreignKeyColumns = [];
            foreach ($foreignKeys as $foreignKey) {
                $localColumn = $foreignKey['columns'][0];
                $foreignTable = $foreignKey['foreign_table'];

                $relatedModel = Str::ucfirst(Str::singular(Str::camel($foreignTable)));
                $relationshipName = Str::before($localColumn, '_id');

                $column = array_find($columns, fn($col) => $col['name'] === $localColumn);
                $attributes = $column['nullable'] ? ' @nullable' : '';

                $dslContent .= "    {$relationshipName}: belongsTo({$relatedModel}){$attributes}\n";
                $foreignKeyColumns[] = $localColumn;
            }

            $dslContent .= "}" . "\n\n";
        }

        File::put(database_path('schema.kld'), trim($dslContent));

        $parser = new Parser();
        $parsedSchema = $parser->parse($dslContent);

        File::put(database_path('schema.kld.lock'), json_encode($parsedSchema));

        $this->info('Schema file created: ' . $dlsPath);
    }

    private function mapType(string $dbType): string
    {
        return match (strtolower($dbType)) {
            'string', 'text', 'char', 'varchar' => 'string',
            'integer', 'int', 'bigint', 'smallint', 'tinyint' => 'integer',
            'boolean' => 'boolean',
            'datetime', 'timestamp' => 'timestamp',
            'date' => 'date',
            'json', 'jsonb' => 'json',
            'float', 'decimal', 'double' => 'float',
            default => 'string',
        };
    }
}
