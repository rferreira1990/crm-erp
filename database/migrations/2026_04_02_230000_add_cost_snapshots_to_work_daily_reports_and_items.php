<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('work_daily_reports', function (Blueprint $table) {
            if (! Schema::hasColumn('work_daily_reports', 'user_hourly_cost_snapshot')) {
                $table->decimal('user_hourly_cost_snapshot', 14, 2)
                    ->nullable()
                    ->after('hours_spent');
            }

            if (! Schema::hasColumn('work_daily_reports', 'labor_cost_total_snapshot')) {
                $table->decimal('labor_cost_total_snapshot', 14, 2)
                    ->nullable()
                    ->after('user_hourly_cost_snapshot');
            }
        });

        Schema::table('work_daily_report_items', function (Blueprint $table) {
            if (! Schema::hasColumn('work_daily_report_items', 'unit_cost_snapshot')) {
                $table->decimal('unit_cost_snapshot', 12, 2)
                    ->nullable()
                    ->after('quantity');
            }

            if (! Schema::hasColumn('work_daily_report_items', 'total_cost_snapshot')) {
                $table->decimal('total_cost_snapshot', 14, 2)
                    ->nullable()
                    ->after('unit_cost_snapshot');
            }
        });

        $this->backfillReportSnapshots();
        $this->backfillReportItemSnapshots();
    }

    public function down(): void
    {
        Schema::table('work_daily_report_items', function (Blueprint $table) {
            if (Schema::hasColumn('work_daily_report_items', 'total_cost_snapshot')) {
                $table->dropColumn('total_cost_snapshot');
            }

            if (Schema::hasColumn('work_daily_report_items', 'unit_cost_snapshot')) {
                $table->dropColumn('unit_cost_snapshot');
            }
        });

        Schema::table('work_daily_reports', function (Blueprint $table) {
            if (Schema::hasColumn('work_daily_reports', 'labor_cost_total_snapshot')) {
                $table->dropColumn('labor_cost_total_snapshot');
            }

            if (Schema::hasColumn('work_daily_reports', 'user_hourly_cost_snapshot')) {
                $table->dropColumn('user_hourly_cost_snapshot');
            }
        });
    }

    private function backfillReportSnapshots(): void
    {
        if (! Schema::hasTable('work_daily_reports') || ! Schema::hasTable('users')) {
            return;
        }

        $userHourlyCosts = DB::table('users')
            ->select('id', 'hourly_cost')
            ->pluck('hourly_cost', 'id');

        DB::table('work_daily_reports')
            ->select('id', 'user_id', 'hours_spent', 'user_hourly_cost_snapshot')
            ->orderBy('id')
            ->chunkById(500, function ($reports) use ($userHourlyCosts): void {
                foreach ($reports as $report) {
                    $hourlyCost = $report->user_hourly_cost_snapshot !== null
                        ? (float) $report->user_hourly_cost_snapshot
                        : (float) ($userHourlyCosts[$report->user_id] ?? 0);

                    $laborTotal = (float) $report->hours_spent * $hourlyCost;

                    DB::table('work_daily_reports')
                        ->where('id', $report->id)
                        ->update([
                            'user_hourly_cost_snapshot' => round($hourlyCost, 2),
                            'labor_cost_total_snapshot' => round($laborTotal, 2),
                        ]);
                }
            });
    }

    private function backfillReportItemSnapshots(): void
    {
        if (! Schema::hasTable('work_daily_report_items') || ! Schema::hasTable('items')) {
            return;
        }

        DB::table('work_daily_report_items')
            ->select('id', 'item_id', 'quantity', 'unit_cost_snapshot')
            ->orderBy('id')
            ->chunkById(500, function ($rows): void {
                $missingItemIds = collect($rows)
                    ->filter(fn ($row) => $row->unit_cost_snapshot === null && $row->item_id !== null)
                    ->pluck('item_id')
                    ->unique()
                    ->values();

                $itemCosts = $missingItemIds->isNotEmpty()
                    ? DB::table('items')
                        ->whereIn('id', $missingItemIds)
                        ->pluck('cost_price', 'id')
                    : collect();

                foreach ($rows as $row) {
                    $unitCost = $row->unit_cost_snapshot !== null
                        ? (float) $row->unit_cost_snapshot
                        : (float) ($itemCosts[$row->item_id] ?? 0);

                    $totalCost = (float) $row->quantity * $unitCost;

                    DB::table('work_daily_report_items')
                        ->where('id', $row->id)
                        ->update([
                            'unit_cost_snapshot' => round($unitCost, 2),
                            'total_cost_snapshot' => round($totalCost, 2),
                        ]);
                }
            });
    }
};

