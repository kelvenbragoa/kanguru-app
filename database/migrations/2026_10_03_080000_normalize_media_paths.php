<?php

use App\Support\MediaPath;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $this->normalizeColumn('shops', 'image_url');
        $this->normalizeColumn('products', 'image');
        $this->normalizeColumn('profiles', 'avatar');
    }

    public function down(): void
    {
        // Irreversible: absolute hosts were discarded on purpose.
    }

    private function normalizeColumn(string $table, string $column): void
    {
        DB::table($table)
            ->whereNotNull($column)
            ->where($column, '!=', '')
            ->orderBy('id')
            ->chunkById(100, function ($rows) use ($table, $column) {
                foreach ($rows as $row) {
                    $current = $row->{$column};
                    $normalized = MediaPath::normalize($current);
                    if ($normalized !== null && $normalized !== $current) {
                        DB::table($table)->where('id', $row->id)->update([
                            $column => $normalized,
                        ]);
                    }
                }
            });
    }
};
