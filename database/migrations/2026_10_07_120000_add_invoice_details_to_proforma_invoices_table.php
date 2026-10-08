<?php
/**
 * AddInvoiceDetailsToProformaInvoicesTable
 * 
 * @package Database\Migrations
 * @author Achal Sharma
 * @version 1.0.0
 * @since 2026-10-08
 */
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('proforma_invoices', function (Blueprint $table) {
            if (!Schema::hasColumn('proforma_invoices', 'client_name')) {
                $table->string('client_name')->nullable()->after('brand_name');
            }
            if (!Schema::hasColumn('proforma_invoices', 'state_code')) {
                $table->string('state_code', 10)->nullable()->after('gst_no');
            }
            if (!Schema::hasColumn('proforma_invoices', 'invoice_date')) {
                $table->date('invoice_date')->nullable()->after('pi_number');
            }
            if (!Schema::hasColumn('proforma_invoices', 'po_no')) {
                $table->string('po_no', 100)->nullable()->after('address');
            }
            if (!Schema::hasColumn('proforma_invoices', 'po_date')) {
                $table->string('po_date', 50)->nullable()->after('po_no');
            }
            if (!Schema::hasColumn('proforma_invoices', 'period')) {
                $table->string('period', 100)->nullable()->after('po_date');
            }
            if (!Schema::hasColumn('proforma_invoices', 'campaign')) {
                $table->string('campaign', 255)->nullable()->after('period');
            }
            if (!Schema::hasColumn('proforma_invoices', 'kind_attn')) {
                $table->string('kind_attn', 255)->nullable()->after('campaign');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('proforma_invoices', function (Blueprint $table) {
            $columns = [
                'client_name',
                'state_code',
                'invoice_date',
                'po_no',
                'po_date',
                'period',
                'campaign',
                'kind_attn',
            ];

            foreach ($columns as $column) {
                if (Schema::hasColumn('proforma_invoices', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
