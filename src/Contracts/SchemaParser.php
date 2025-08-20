<?php

namespace AnasNashat\EasyDev\Contracts;

interface SchemaParser
{
    /**
     * Get all foreign keys for a given table.
     */
    public function getForeignKeysForTable(string $table): array;

    /**
     * Find pivot tables that reference the given table.
     */
    public function findPivotTablesForTable(string $table): array;

    /**
     * Get polymorphic columns for a given table.
     */
    public function getPolymorphicColumnsForTable(string $table): array;

    /**
     * Get all tables in the database.
     */
    public function getAllTables(): array;

    /**
     * Get column information for a table.
     */
    public function getTableColumns(string $table): array;
}
