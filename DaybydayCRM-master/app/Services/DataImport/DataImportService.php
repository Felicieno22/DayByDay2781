<?php

namespace App\Services\DataImport;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Log;

class DataImportService
{
    /**
     * Array to store table dependencies
     *
     * @var array
     */
    private $tableDependencies = [];

    /**
     * Array to store sorted tables
     *
     * @var array
     */
    private $sortedTables = [];

    /**
     * Get all tables and their columns from the database
     *
     * @return array
     */
    public function getDatabaseSchema(): array
    {
        $tables = [];
        $tableNames = DB::connection()->getDoctrineSchemaManager()->listTableNames();
        
        foreach ($tableNames as $tableName) {
            $tables[$tableName] = Schema::getColumnListing($tableName);
        }
        
        return $tables;
    }

    /**
     * Get foreign key relationships for all tables
     *
     * @return array
     */
    private function getForeignKeyRelationships(): array
    {
        $relationships = [];
        $schema = DB::connection()->getDoctrineSchemaManager();

        foreach ($schema->listTableNames() as $tableName) {
            $relationships[$tableName] = [];

            try {
                $foreignKeys = $schema->listTableForeignKeys($tableName);
                foreach ($foreignKeys as $foreignKey) {
                    $relationships[$tableName][] = [
                        'foreign_table' => $foreignKey->getForeignTableName(),
                        'local_columns' => $foreignKey->getLocalColumns(),
                        'foreign_columns' => $foreignKey->getForeignColumns()
                    ];
                }
            } catch (\Exception $e) {
                // Skip if table doesn't exist or other issues
                continue;
            }
        }

        return $relationships;
    }

    /**
     * Build dependency graph for tables
     *
     * @return void
     */
    private function buildDependencyGraph(): void
    {
        $this->tableDependencies = [];
        $relationships = $this->getForeignKeyRelationships();

        foreach ($relationships as $table => $foreignKeys) {
            if (!isset($this->tableDependencies[$table])) {
                $this->tableDependencies[$table] = [];
            }

            foreach ($foreignKeys as $foreignKey) {
                $foreignTable = $foreignKey['foreign_table'];
                if (!in_array($foreignTable, $this->tableDependencies[$table])) {
                    $this->tableDependencies[$table][] = $foreignTable;
                }
            }
        }
    }

    /**
     * Sort tables based on dependencies (topological sort)
     *
     * @param string $table
     * @param array $visited
     * @param array $temp
     * @return void
     */
    private function topologicalSort(string $table, array &$visited, array &$temp): void
    {
        if (isset($temp[$table])) {
            // Circular dependency detected
            throw new \RuntimeException("Circular dependency detected for table: $table");
        }

        if (isset($visited[$table])) {
            return;
        }

        $temp[$table] = true;

        if (isset($this->tableDependencies[$table])) {
            foreach ($this->tableDependencies[$table] as $dependency) {
                $this->topologicalSort($dependency, $visited, $temp);
            }
        }

        unset($temp[$table]);
        $visited[$table] = true;
        array_unshift($this->sortedTables, $table);
    }

    /**
     * Get sorted tables based on dependencies
     *
     * @return array
     */
    public function getSortedTables(): array
    {
        $this->sortedTables = [];
        $this->buildDependencyGraph();

        $visited = [];
        $temp = [];

        foreach (array_keys($this->tableDependencies) as $table) {
            if (!isset($visited[$table])) {
                $this->topologicalSort($table, $visited, $temp);
            }
        }

        return $this->sortedTables;
    }

    /**
     * Get columns from CSV file
     */
    public function getFileColumns(string $filepath): array
    {
        $columns = [];
        if (($handle = fopen($filepath, "r")) !== false) {
            // Lire la première ligne qui contient les noms des colonnes
            $columns = fgetcsv($handle);
            fclose($handle);
        }
        
        Log::info('CSV columns found:', $columns);
        return $columns;
    }

    /**
     * Check consistency between file columns and table columns
     */
    public function checkColumnConsistency(array $fileColumns, array $tableColumns): array
    {
        $matchingColumns = [];
        $missingInFile = [];
        $missingInTable = [];

        // Convertir en minuscules pour la comparaison
        $fileColumnsLower = array_map('strtolower', $fileColumns);
        $tableColumnsLower = array_map('strtolower', $tableColumns);

        // Trouver les correspondances
        foreach ($fileColumnsLower as $index => $fileColumn) {
            $found = false;
            foreach ($tableColumnsLower as $tableIndex => $tableColumn) {
                if ($fileColumn === $tableColumn) {
                    $matchingColumns[$fileColumns[$index]] = $tableColumns[$tableIndex];
                    $found = true;
                    break;
                }
            }
            if (!$found) {
                $missingInTable[] = $fileColumns[$index];
            }
        }

        // Trouver les colonnes manquantes dans le fichier
        foreach ($tableColumnsLower as $index => $tableColumn) {
            if (!in_array($tableColumn, $fileColumnsLower)) {
                $missingInFile[] = $tableColumns[$index];
            }
        }

        $result = [
            'matching_columns' => $matchingColumns,
            'missing_in_file' => $missingInFile,
            'missing_in_table' => $missingInTable,
            'can_import' => count($matchingColumns) > 0
        ];

        Log::info('Column consistency check result:', $result);
        return $result;
    }

    /**
     * Import data from CSV file to table
     */
    public function importData(string $filepath, string $table): array
    {
        $imported = 0;
        $errors = [];

        try {
            if (($handle = fopen($filepath, "r")) !== false) {
                // Obtenir les colonnes du fichier et de la table
                $fileColumns = fgetcsv($handle);
                $tableColumns = Schema::getColumnListing($table);
                
                // Vérifier la cohérence des colonnes
                $consistency = $this->checkColumnConsistency($fileColumns, $tableColumns);
                
                if (!$consistency['can_import']) {
                    throw new \Exception("Cannot import: No matching columns found");
                }

                // Commencer la transaction
                DB::beginTransaction();

                // Lire et importer les données
                while (($data = fgetcsv($handle)) !== false) {
                    $row = [];
                    foreach ($consistency['matching_columns'] as $fileCol => $tableCol) {
                        $fileIndex = array_search($fileCol, $fileColumns);
                        if ($fileIndex !== false) {
                            $row[$tableCol] = $data[$fileIndex];
                        }
                    }

                    if (!empty($row)) {
                        DB::table($table)->insert($row);
                        $imported++;
                    }
                }

                fclose($handle);
                DB::commit();

                Log::info("Successfully imported $imported rows to table $table");
            }
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Import error: " . $e->getMessage());
            throw $e;
        }

        return [
            'imported' => $imported,
            'errors' => $errors
        ];
    }
}