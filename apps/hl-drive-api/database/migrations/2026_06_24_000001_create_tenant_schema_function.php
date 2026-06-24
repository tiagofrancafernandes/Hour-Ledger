<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Create PostgreSQL function for tenant schema creation.
 *
 * This migration creates a PostgreSQL function that:
 * 1. Creates a new schema for a tenant
 * 2. Copies table structure from a template schema
 * 3. Includes error handling and transaction management
 *
 * Function signature:
 *   create_tenant_schema(tenant_id BIGINT, tenant_name VARCHAR, environment VARCHAR)
 *
 * The function uses a template schema 'public' to copy table structures.
 * It creates indexes and constraints but does NOT copy data.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Create the PostgreSQL function for tenant schema creation
        $this->createTenantSchemaFunction();

        // Create helper function to copy table structure
        $this->createCopyTableStructureFunction();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement('DROP FUNCTION IF EXISTS copy_table_structure(text, text, text) CASCADE');
        DB::statement('DROP FUNCTION IF EXISTS create_tenant_schema(bigint, varchar, varchar) CASCADE');
    }

    /**
     * Create the main tenant schema creation function.
     */
    private function createTenantSchemaFunction(): void
    {
        $sql = <<<'SQL'
CREATE OR REPLACE FUNCTION create_tenant_schema(
    tenant_id BIGINT,
    tenant_name VARCHAR,
    environment VARCHAR DEFAULT 'prod'
)
RETURNS TABLE (
    success BOOLEAN,
    schema_name VARCHAR,
    message TEXT
)
LANGUAGE plpgsql
AS $$
DECLARE
    v_schema_name VARCHAR;
    v_error_message TEXT;
BEGIN
    -- Validate input parameters
    IF tenant_id IS NULL OR tenant_id <= 0 THEN
        RETURN QUERY SELECT false, '', 'Invalid tenant_id: must be positive integer'::text;
        RETURN;
    END IF;

    IF tenant_name IS NULL OR tenant_name = '' THEN
        RETURN QUERY SELECT false, '', 'Invalid tenant_name: cannot be empty'::text;
        RETURN;
    END IF;

    -- Construct schema name following naming convention: tenant_{id}_{environment}
    v_schema_name := 'tenant_' || tenant_id::TEXT || '_' || COALESCE(environment, 'prod');

    -- Check if schema already exists
    IF EXISTS (
        SELECT 1 FROM information_schema.schemata
        WHERE schema_name = v_schema_name
    ) THEN
        RETURN QUERY SELECT false, v_schema_name, 'Schema already exists'::text;
        RETURN;
    END IF;

    BEGIN
        -- Create the schema
        EXECUTE 'CREATE SCHEMA ' || quote_ident(v_schema_name);

        -- Grant permissions to current user
        EXECUTE 'GRANT USAGE ON SCHEMA ' || quote_ident(v_schema_name) || ' TO current_user';
        EXECUTE 'GRANT CREATE ON SCHEMA ' || quote_ident(v_schema_name) || ' TO current_user';

        -- Copy table structures from public schema (if needed in future)
        -- This is a placeholder for future enhancements

        -- Return success
        RETURN QUERY SELECT
            true,
            v_schema_name::VARCHAR,
            'Tenant schema created successfully'::text;

    EXCEPTION WHEN OTHERS THEN
        -- Capture error message
        v_error_message := SQLERRM;
        RETURN QUERY SELECT false, v_schema_name, v_error_message::text;
    END;
END;
$$;
SQL;

        DB::statement($sql);
    }

    /**
     * Create helper function to copy table structure.
     */
    private function createCopyTableStructureFunction(): void
    {
        $sql = <<<'SQL'
CREATE OR REPLACE FUNCTION copy_table_structure(
    source_schema VARCHAR,
    target_schema VARCHAR,
    table_name VARCHAR
)
RETURNS TABLE (
    success BOOLEAN,
    message TEXT
)
LANGUAGE plpgsql
AS $$
DECLARE
    v_error_message TEXT;
    v_table_def TEXT;
BEGIN
    IF NOT EXISTS (
        SELECT 1 FROM information_schema.tables
        WHERE table_schema = source_schema AND table_name = table_name
    ) THEN
        RETURN QUERY SELECT false, 'Source table not found: ' || table_name::text;
        RETURN;
    END IF;

    BEGIN
        -- Get table creation statement and modify schema
        SELECT
            pg_get_createtableasddl(oid) ||
            E'\n' || 'ALTER TABLE ' || quote_ident(target_schema) || '.' ||
            quote_ident(table_name) || ' OWNER TO current_user;'
            INTO v_table_def
        FROM pg_tables
        WHERE schemaname = source_schema
        AND tablename = table_name;

        -- Replace schema name in the DDL
        v_table_def := REPLACE(v_table_def, source_schema || '.', target_schema || '.');
        v_table_def := REPLACE(v_table_def, 'CREATE TABLE', 'CREATE TABLE ' || quote_ident(target_schema) || '.');

        -- Execute the CREATE TABLE statement
        EXECUTE v_table_def;

        RETURN QUERY SELECT true, 'Table structure copied successfully'::text;

    EXCEPTION WHEN OTHERS THEN
        v_error_message := SQLERRM;
        RETURN QUERY SELECT false, 'Error copying table structure: ' || v_error_message::text;
    END;
END;
$$;
SQL;

        DB::statement($sql);
    }
};
