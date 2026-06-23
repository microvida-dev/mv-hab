<?php

namespace Database\Factories;

use App\Models\DataExportPackage;
use App\Models\DataSubjectRequest;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/** @extends Factory<DataExportPackage> */
class DataExportPackageFactory extends Factory
{
    public function definition(): array
    {
        $uuid = (string) Str::uuid();

        return [
            'data_subject_request_id' => DataSubjectRequest::factory(),
            'user_id' => User::factory(),
            'package_number' => 'EXP-'.now()->format('YmdHis').'-'.Str::upper(Str::random(5)),
            'status' => 'generated',
            'format' => 'json',
            'storage_disk' => 'local',
            'storage_path' => 'rgpd/exports/test/'.$uuid.'/data-export-'.$uuid.'.json',
            'filename' => 'data-export-'.$uuid.'.json',
            'mime_type' => 'application/json',
            'file_size' => 128,
            'checksum' => hash('sha256', $uuid),
            'generated_at' => now(),
            'expires_at' => now()->addDays(14),
        ];
    }
}
