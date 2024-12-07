<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('tbl_ppw', function (Blueprint $table) {
            $table->id('ppw_id');
            $table->unsignedBigInteger('id');
            $table->string('name');
            $table->string('session');
            $table->string('project_name');
            $table->date('project_date');
            $table->tinyInteger('status')->default(0);
            $table->timestamp('submission_time')->useCurrent();
            $table->timestamps();

            $table->foreign('id')->references('id')->on('tbl_users')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('paperworks');
    }
};
