<?php

namespace App\Support;

use App\Models\ExportLog;
use Illuminate\Support\Facades\Auth;

class ExportAudit
{
    public static function log(string $exportType, string $format, array $filters = [], array $meta = []): ExportLog
    {
        return ExportLog::create([
            'tenant_id' => 1,
            'user_id' => Auth::id(),
            'export_type' => $exportType,
            'format' => $format,
            'filters' => $filters,
            'file_name' => $meta['file_name'] ?? null,
            'file_size' => $meta['file_size'] ?? null,
            'status' => $meta['status'] ?? 'success',
            'delivery_channel' => $meta['delivery_channel'] ?? null,
            'delivery_target' => $meta['delivery_target'] ?? null,
            'notes' => $meta['notes'] ?? null,
        ]);
    }
}
