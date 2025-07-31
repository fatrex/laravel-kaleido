<?php

namespace Fatrex\LaravelKaleido\Services;


class SchemaDiff
{
    public function diff(array $oldSchema, array $newSchema): array
    {
        $changes = [];

        $oldModels = array_keys($oldSchema);
        $newModels = array_keys($newSchema);

        // Find new models to create
        $modelsToCreate = array_diff($newModels, $oldModels);
        foreach ($modelsToCreate as $model) {
            $changes[] = [
                'type' => 'create_table',
                'model' => $newSchema[$model]
            ];
        }

        // Find models to drop
        $modelsToDrop = array_diff($oldModels, $newModels);
        foreach ($modelsToDrop as $model) {
            $changes[] = [
                'type' => 'drop_table',
                'model' => $model
            ];
        }

        // Find models updates
        $modelsToCheck = array_intersect($newModels, $oldModels);
        foreach ($modelsToCheck as $model) {
            $oldModel = $oldSchema[$model];
            $newModel = $newSchema[$model];

            $jsonOld = json_encode($oldModel);
            $jsonNew = json_encode($newModel);

            if ($jsonOld !== $jsonNew) {
                $changes[] = [
                    'type' => 'update_table',
                    'model' => $model,
                    'old' => $oldModel,
                    'new' => $newModel
                ];
            }
        }

        return $changes;
    }
}