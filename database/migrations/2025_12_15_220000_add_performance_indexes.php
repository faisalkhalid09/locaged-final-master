<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

/**
 * AUDIT FIX #7: Add database indexes for performance optimization
 * 
 * This migration adds indexes on frequently queried columns:
 * - documents.status (filtered in most queries)
 * - documents.expire_at (scheduled task queries)
 * - documents.created_by (user document filtering)
 * - documents.created_at (date range queries)
 * - audit_logs compound index (document_id, occurred_at)
 * - document_versions.ocr_text (FULLTEXT for search)
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Add indexes to documents table
        Schema::table('documents', function (Blueprint $table) {
            $table->index('status', 'idx_documents_status');
            $table->index('expire_at', 'idx_documents_expire_at');
            $table->index('created_by', 'idx_documents_created_by');
            $table->index('created_at', 'idx_documents_created_at');
            // Compound index for common query patterns
            $table->index(['status', 'created_at'], 'idx_documents_status_created');
        });

        // Add compound index to audit_logs table
        Schema::table('audit_logs', function (Blueprint $table) {
            $table->index(['document_id', 'occurred_at'], 'idx_audit_logs_doc_occurred');
            $table->index('occurred_at', 'idx_audit_logs_occurred_at');
        });

        // Add FULLTEXT index for OCR text search (MySQL specific)
        // Using raw SQL as Blueprint doesn't support FULLTEXT directly
        DB::statement('ALTER TABLE document_versions ADD FULLTEXT INDEX idx_document_versions_ocr_fulltext (ocr_text)');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('documents', function (Blueprint $table) {
            $table->dropIndex('idx_documents_status');
            $table->dropIndex('idx_documents_expire_at');
            $table->dropIndex('idx_documents_created_by');
            $table->dropIndex('idx_documents_created_at');
            $table->dropIndex('idx_documents_status_created');
        });

        Schema::table('audit_logs', function (Blueprint $table) {
            $table->dropIndex('idx_audit_logs_doc_occurred');
            $table->dropIndex('idx_audit_logs_occurred_at');
        });

        DB::statement('ALTER TABLE document_versions DROP INDEX idx_document_versions_ocr_fulltext');
    }
};
