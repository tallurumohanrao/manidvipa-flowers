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
        Schema::create('profiles', function (Blueprint $table) {
            $table->id();
            $table->string('profile_id');
            $table->string('name')->nullable();
            $table->date('dob')->nullable();
            $table->string('height',20)->nullable();
            $table->string('weight',20)->nullable();
            $table->string('marital_status',30)->nullable();
            $table->string('body_type',20)->nullable();
            $table->string('physical_status',30)->nullable();
            $table->string('mother_tongue',50)->nullable();
            $table->string('eating_habits',30)->nullable();
            $table->string('drinking_habits',30)->nullable();
            $table->string('smoking_habits',30)->nullable();
            $table->string('religion',50)->nullable();
            $table->string('caste',100)->nullable();
            $table->string('sub_caste',100)->nullable();
            $table->string('gothram',100)->nullable();
            $table->string('raasi',100)->nullable();
            $table->string('star',100)->nullable();
            $table->string('padam',10)->nullable();
            $table->string('dosham',20)->nullable();
            $table->time('time_of_birth')->nullable();
            $table->string('country',50)->nullable();
            $table->string('state',50)->nullable();
            $table->string('city',50)->nullable();

            $table->string('education_details',500)->nullable();

            $table->string('citizenship',100)->nullable();
            $table->string('living_country',50)->nullable();
            $table->string('living_state',50)->nullable();
            $table->string('living_city',50)->nullable();

            $table->string('employed_in',30)->nullable();
            $table->string('occupation',255)->nullable();
            $table->string('organization',100)->nullable();
            $table->string('annual_income',50)->nullable();

            $table->string('family_value',20)->nullable();
            $table->string('family_type',20)->nullable();
            $table->string('family_status',20)->nullable();
            $table->string('father_occupation',150)->nullable();
            $table->string('mother_occupation',150)->nullable();

            $table->smallInteger('brothers')->nullable();
            $table->smallInteger('married_brothers')->nullable();
            $table->smallInteger('sisters')->nullable();
            $table->smallInteger('married_sisters')->nullable();

            $table->string('family_living_country',50)->nullable();
            $table->string('family_living_state',50)->nullable();
            $table->string('family_living_city',50)->nullable();

            $table->string('hobbies_interests',255)->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('profiles');
    }
};
