<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateUsersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('users', function (Blueprint $table) {
            $table->integer('id')->autoIncrement();
            $table->string('name');
            $table->string('email')->unique();
            $table->date('date_of_birth');
            $table->string('phone_number')->unique();
            $table->string('gender')->nullable();
            $table->string('education_level')->nullable();
            $table->string('religion')->nullable();
            $table->string('church_attendance')->nullable();
            $table->string('marital_status')->nullable();
            $table->string('height')->nullable();
            $table->string('relationship_type')->nullable();
            $table->string('drinker')->nullable();
            $table->string('smoker')->nullable();
            $table->string('want_kids')->nullable();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->string('profile_photo')->nullable();
            $table->boolean('paid_account_status')->default(0);
            $table->boolean('status')->default(0);
            $table->rememberToken();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('users');
    }
}
