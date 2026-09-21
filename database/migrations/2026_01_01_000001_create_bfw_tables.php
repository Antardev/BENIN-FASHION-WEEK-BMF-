<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('events', function (Blueprint $t) {
            $t->id();
            $t->string('slug')->unique();
            $t->string('title');
            $t->string('tagline')->nullable();
            $t->text('description')->nullable();
            $t->dateTime('starts_at')->nullable();
            $t->string('venue')->nullable();
            $t->json('images')->nullable();   // noms de fichiers dans public/images
            $t->unsignedSmallInteger('position')->default(0);
            $t->boolean('is_active')->default(true);
            $t->timestamps();
        });

        Schema::create('ticket_types', function (Blueprint $t) {
            $t->id();
            $t->foreignId('event_id')->constrained()->cascadeOnDelete();
            $t->string('name');
            $t->string('description')->nullable();
            $t->unsignedInteger('price');          // en FCFA (XOF), sans décimales
            $t->unsignedInteger('capacity');
            $t->timestamps();
        });

        Schema::create('orders', function (Blueprint $t) {
            $t->id();
            $t->string('reference')->unique();
            $t->foreignId('ticket_type_id')->constrained();
            $t->string('buyer_name');
            $t->string('buyer_email');
            $t->string('buyer_phone');
            $t->unsignedSmallInteger('quantity');
            $t->unsignedInteger('total');
            $t->string('status')->default('pending'); // pending | paid | failed
            $t->string('payment_ref')->nullable();
            $t->timestamps();
        });

        Schema::create('tickets', function (Blueprint $t) {
            $t->id();
            $t->foreignId('order_id')->constrained()->cascadeOnDelete();
            $t->string('code')->unique();
            $t->timestamp('checked_in_at')->nullable();
            $t->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tickets');
        Schema::dropIfExists('orders');
        Schema::dropIfExists('ticket_types');
        Schema::dropIfExists('events');
    }
};
