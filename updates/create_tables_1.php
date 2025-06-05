<?php namespace Davox\Company\Updates;

use Schema;
use October\Rain\Database\Schema\Blueprint;
use October\Rain\Database\Updates\Migration;

return new class extends Migration
{
    public function up()
    {
        Schema::create('davox_company_clients', function(Blueprint $table) {
            $table->id();
            $table->string('name')->nullable()->index();
            $table->string('email')->unique()->nullable()->index();
            $table->string('phone')->unique()->nullable()->index();
            $table->string('address')->nullable();
            $table->string('gst')->unique()->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('davox_company_services', function(Blueprint $table) {
            $table->id();
            $table->string('name')->nullable()->index();
            $table->text('description')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('davox_company_invoices', function (Blueprint $table) {
            $table->id();
            $table->string('invoice_number')->nullable()->unique()->index();
            $table->foreignId('client_id')->nullable()->constrained('davox_company_clients');
            $table->date('issue_date')->nullable();
            $table->decimal('subtotal', 10, 2)->nullable();
            $table->decimal('tax', 10, 2)->nullable();
            $table->decimal('total', 10, 2)->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('davox_company_invoice_service', function (Blueprint $table) {
            $table->foreignId('invoice_id')->nullable()->constrained('davox_company_invoices');
            $table->foreignId('service_id')->nullable()->constrained('davox_company_services');
            $table->decimal('price', 10, 2)->nullable();
            $table->text('description')->nullable();
            $table->integer('quantity')->default(1);
            $table->integer('sort_order')->default(0);
            $table->index(['invoice_id', 'service_id']);
            $table->timestamps();
        });

    }

    public function down()
    {
        Schema::dropIfExists('davox_company_invoice_service');
        Schema::dropIfExists('davox_company_invoices');
        Schema::dropIfExists('davox_company_services');
        Schema::dropIfExists('davox_company_clients');
    }
};
