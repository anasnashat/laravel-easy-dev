<?php

namespace AnasNashat\EasyDev\Parsers;

use Illuminate\Database\ConnectionResolverInterface as DB;
use AnasNashat\EasyDev\Contracts\SchemaParser;

class SqliteSchemaParser implements SchemaParser
{
    public function __construct(protected DB $db)
    {
    }

    public function getForeignKeysForTable(string $table): array
    {
        $query = "PRAGMA foreign_key_list({$table})";
        $foreignKeys = (array) $this->db->connection()->select($query);

        $result = [];
        foreach ($foreignKeys as $fk) {
            $result[] = (object) [
                'column_name' => $fk->from,
                'referenced_table_name' => $fk->table,
                'referenced_column_name' => $fk->to,
            ];
        }

        return $result;
    }

    public function findPivotTablesForTable(string $table): array
    {
        $tables = $this->getAllTables();
        $pivotTables = [];

        foreach ($tables as $tableName) {
            if ($tableName === $table) {
                continue;
            }

            $foreignKeys = $this->getForeignKeysForTable($tableName);
            $referencedTables = array_column($foreignKeys, 'referenced_table_name');

            // Check if this table references our target table and at least one other table
            if (in_array($table, $referencedTables) && count(array_unique($referencedTables)) >= 2) {
                foreach ($foreignKeys as $fk) {
                    $pivotTables[] = (object) [
                        'pivot_table' => $tableName,
                        'foreign_key' => $fk->column_name,
                        'referenced_table' => $fk->referenced_table_name,
                    ];
                }
            }
        }

        return $pivotTables;
    }

    public function getPolymorphicColumnsForTable(string $table): array
    {
        $query = "PRAGMA table_info({$table})";
        $columns = (array) $this->db->connection()->select($query);

        $result = [];
        foreach ($columns as $column) {
            $columnName = $column->name;
            if (str_ends_with($columnName, '_type') || str_ends_with($columnName, 'able_type')) {
                $result[] = (object) ['column_name' => $columnName];
            }
        }

        return $result;
    }

    public function getAllTables(): array
    {
        $query = "SELECT name FROM sqlite_master WHERE type='table' AND name NOT LIKE 'sqlite_%'";
        return array_column((array) $this->db->connection()->select($query), 'name');
    }

    public function getTableColumns(string $table): array
    {
        $query = "PRAGMA table_info({$table})";
        $columns = (array) $this->db->connection()->select($query);

        $result = [];
        foreach ($columns as $column) {
            $result[] = (object) [
                'column_name' => $column->name,
                'data_type' => $column->type,
                'is_nullable' => $column->notnull ? 'NO' : 'YES',
                'column_default' => $column->dflt_value,
            ];
        }

        return $result;
    }
}
