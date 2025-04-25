<?php

namespace App\Services\csvImport;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class CsvImportService
{
    private const COLUMN_PATTERNS = [
        'email' => ['email', 'mail', 'courriel', 'e-mail'],
        'phone' => ['phone', 'telephone', 'tel', 'mobile', 'cell'],
        'name' => ['name', 'nom', 'fullname', 'full_name'],
        'address' => ['address', 'adresse', 'location'],
        'date' => ['date', 'created', 'updated', 'deleted', 'birth']
    ];

    /**
     * Analyze CSV file and suggest matching tables
     */
    public function analyzeFile(string $filepath): array
    {
        $handle = fopen($filepath, 'r');
        $headers = array_map('strtolower', fgetcsv($handle));
        $sampleData = fgetcsv($handle);
        fclose($handle);

        // Get database tables
        $tables = $this->getTables();
        $tableMatches = [];

        foreach ($tables as $table) {
            $schema = $this->getTableSchema($table);
            $matches = $this->findColumnMatches($headers, $schema);
            $score = $this->calculateMatchScore($matches, count($headers));

            if ($score > 0) {
                $tableMatches[$table] = [
                    'score' => $score,
                    'matches' => $matches,
                    'missing_columns' => array_diff(
                        array_keys($schema['columns']),
                        array_merge(
                            array_values($matches['exact']),
                            array_values($matches['similar']),
                            array_values($matches['pattern']),
                            array_values($matches['type'])
                        )
                    )
                ];
            }
        }

        // Sort by score descending
        uasort($tableMatches, function($a, $b) {
            return $b['score'] <=> $a['score'];
        });

        return [
            'headers' => $headers,
            'sample_data' => $sampleData,
            'table_matches' => $tableMatches
        ];
    }

    /**
     * Get all database tables
     */
    private function getTables(): array
    {
        return array_map(function($table) {
            return $table->Tables_in_database;
        }, DB::select('SHOW TABLES'));
    }

    /**
     * Get table schema information
     */
    private function getTableSchema(string $table): array
    {
        $columns = [];
        $columnInfo = DB::select("SHOW COLUMNS FROM `{$table}`");
        
        foreach ($columnInfo as $column) {
            $columns[$column->Field] = [
                'type' => $this->normalizeColumnType($column->Type),
                'nullable' => $column->Null === 'YES',
                'key' => $column->Key,
                'default' => $column->Default
            ];
        }

        return [
            'name' => $table,
            'columns' => $columns
        ];
    }

    /**
     * Normalize database column type
     */
    private function normalizeColumnType(string $type): string
    {
        $type = strtolower($type);
        
        if (Str::contains($type, ['char', 'text'])) return 'string';
        if (Str::contains($type, ['int', 'decimal', 'float'])) return 'number';
        if (Str::contains($type, ['date', 'time'])) return 'datetime';
        if ($type === 'tinyint(1)') return 'boolean';
        
        return 'string';
    }

    /**
     * Find matching columns between CSV headers and table schema
     */
    private function findColumnMatches(array $headers, array $schema): array
    {
        $matches = [
            'exact' => [],
            'similar' => [],
            'pattern' => [],
            'type' => []
        ];

        foreach ($headers as $header) {
            $matched = false;

            // 1. Check exact matches
            foreach ($schema['columns'] as $column => $info) {
                if (strtolower($column) === $header) {
                    $matches['exact'][$header] = $column;
                    $matched = true;
                    break;
                }
            }
            if ($matched) continue;

            // 2. Check similar matches
            foreach ($schema['columns'] as $column => $info) {
                similar_text(strtolower($column), $header, $percent);
                if ($percent >= 70) {
                    $matches['similar'][$header] = $column;
                    $matched = true;
                    break;
                }
            }
            if ($matched) continue;

            // 3. Check pattern matches
            foreach (self::COLUMN_PATTERNS as $type => $patterns) {
                if (Str::contains($header, $patterns)) {
                    foreach ($schema['columns'] as $column => $info) {
                        if (Str::contains($column, $patterns)) {
                            $matches['pattern'][$header] = $column;
                            $matched = true;
                            break 2;
                        }
                    }
                }
            }
        }

        return $matches;
    }

    /**
     * Calculate match score for a table
     */
    private function calculateMatchScore(array $matches, int $totalColumns): float
    {
        $weights = [
            'exact' => 1.0,
            'similar' => 0.8,
            'pattern' => 0.6,
            'type' => 0.3
        ];

        $score = 0;
        foreach ($matches as $type => $typeMatches) {
            $score += count($typeMatches) * $weights[$type];
        }

        return ($score / $totalColumns) * 100;
    }

    /**
     * Import CSV data into database
     */
    public function importData(string $filepath): array
    {
        $analysis = $this->analyzeFile($filepath);
        $bestMatch = array_key_first($analysis['table_matches']);
        
        if (!$bestMatch) {
            throw new \Exception("No matching table found for CSV structure");
        }

        $matches = $analysis['table_matches'][$bestMatch]['matches'];
        $columnMap = array_merge(
            $matches['exact'],
            $matches['similar'],
            $matches['pattern'],
            $matches['type']
        );

        $handle = fopen($filepath, 'r');
        $headers = fgetcsv($handle);
        $imported = 0;
        $errors = [];

        DB::beginTransaction();
        try {
            while (($data = fgetcsv($handle)) !== false) {
                $row = array_combine($headers, $data);
                $insertData = [];
                
                foreach ($columnMap as $csvColumn => $dbColumn) {
                    if (isset($row[$csvColumn])) {
                        $insertData[$dbColumn] = $row[$csvColumn];
                    }
                }

                if (!empty($insertData)) {
                    DB::table($bestMatch)->insert($insertData);
                    $imported++;
                }
            }

            DB::commit();
            fclose($handle);

            return [
                'success' => true,
                'imported' => [
                    $bestMatch => $imported
                ],
                'errors' => $errors
            ];

        } catch (\Exception $e) {
            DB::rollBack();
            fclose($handle);
            throw $e;
        }
    }
}