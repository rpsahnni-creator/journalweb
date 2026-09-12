<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('submission_versions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('submission_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('version_number');
            $table->string('manuscript_path');
            $table->timestamp('uploaded_at');
            $table->timestamps();

            $table->unique(['submission_id', 'version_number']);
            $table->index('uploaded_at');
        });

        $now = now();

        foreach (DB::table('submissions')->orderBy('id')->get() as $submission) {
            if (! filled($submission->manuscript_path)) {
                continue;
            }

            DB::table('submission_versions')->insert([
                'submission_id' => $submission->id,
                'version_number' => 1,
                'manuscript_path' => $submission->manuscript_path,
                'uploaded_at' => $submission->submitted_at ?? $now,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('submission_versions');
    }
};
