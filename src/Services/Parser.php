<?php

namespace Fatrex\LaravelKaleido\Services;

class Parser
{
    public function parse(string $content): array
    {
        $models = [];
        $offset = 0;

        while (preg_match('/model\s+(\w+)\s*\{/s', $content, $matches, PREG_OFFSET_CAPTURE, $offset)) {
            $modelName = $matches[1][0];
            $startPos = $matches[0][1] + strlen($matches[0][0]);

            $braceCount = 1;
            $currentPos = $startPos;

            while ($braceCount > 0 && $currentPos < strlen($content)) {
                $char = $content[$currentPos];
                if ($char === '{') {
                    $braceCount++;
                } else if ($char === '}') {
                    $braceCount--;
                }
                $currentPos++;
            }

            $modelContent = trim(substr($content, $startPos, $currentPos - $startPos - 1));
            $offset = $currentPos;

            $lines = preg_split('/\r\n|\r|\n/', $modelContent);
            $fields = [];

            foreach ($lines as $line) {
                $line = trim($line);
                if (empty($line) || str_starts_with($line, '#')) {
                    continue;
                }

                if ($line === 'timestamps') {
                    $fields['created_at'] = ['type' => 'timestamp', 'attributes' => ['nullable']];
                    $fields['updated_at'] = ['type' => 'timestamp', 'attributes' => ['nullable']];
                    continue;
                }

                if (preg_match('/^(\w+):\s*(\w+)(.*)$/', $line, $fieldMatches)) {
                    $fieldName = $fieldMatches[1];
                    $type = $fieldMatches[2];
                    $rawAttributes = trim($fieldMatches[3]);

                    $attributes = [];
                    if (!empty($rawAttributes)) {
                        preg_match_all('/@(\w+(\([^\)]*\))?)/', $rawAttributes, $attrMatches);
                        $attributes = $attrMatches[1] ?? [];
                    }

                    $fields[$fieldName] = [
                        'type' => $type,
                        'attributes' => $attributes
                    ];
                }
            }

            $models[$modelName] = [
                'name' => $modelName,
                'fields' => $fields
            ];
        }

        return $models;
    }
}
