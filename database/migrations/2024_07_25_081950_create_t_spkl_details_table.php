<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
        /**
         * Run the migrations.
         *
         * @return void
         */
        public function up()
        {
            Schema::create('t_spkl_details', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('id_spkl');
                $table->string('npk');
                $table->string('npk_before')->nullable();
                $table->date('start_date');
                $table->date('end_date')->nullable();
                $table->time('start_planning')->nullable();
                $table->time('end_planning')->nullable();
                $table->time('start_actual')->nullable();
                $table->time('system_in')->nullable();
                $table->time('system_out')->nullable();
                $table->string('npk_edited')->nullable();
                $table->date('date_edited')->nullable();
                $table->time('end_actual')->nullable();
                $table->string('ref_code')->nullable();
                $table->text('notes')->nullable();
                $table->boolean('is_closed')->default(false);
                $table->boolean('is_clv')->default(false);
                $table->decimal('quota_ot', 8, 2)->nullable();
                $table->decimal('quota_ot_actual', 8, 2)->nullable();
                $table->string('sub_section')->nullable();
                $table->string('status')->nullable();
                $table->string('kd_shift_makan')->nullable();
                $table->string('kd_trans')->nullable();
                $table->string('kd_shift_trans')->nullable();
                $table->date('approval_1_planning_date')->nullable();
                $table->date('approval_2_planning_date')->nullable();
                $table->date('approval_3_planning_date')->nullable();
                $table->date('approval_1_realisasi_date')->nullable();
                $table->date('approval_2_realisasi_date')->nullable();
                $table->date('approval_3_realisasi_date')->nullable();
                $table->string('npk_leader')->nullable();
                $table->date('reject_date')->nullable();
                $table->timestamps();

                // Indexing, Foreign Keys, etc. can be added here if necessary
            });
        }

        /**
         * Reverse the migrations.
         *
         * @return void
         */
        public function down()
        {
            Schema::dropIfExists('t_spkl_details');
        }
    };
