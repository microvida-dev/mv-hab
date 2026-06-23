<?php

namespace App\Services\Notifications;

use App\Enums\CommunicationReceiptType;
use App\Models\CommunicationDelivery;
use App\Models\CommunicationLog;
use App\Models\CommunicationReceipt;
use App\Models\User;
use App\Services\Audit\AuditLogger;
use App\Support\AuditEvents;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class CommunicationReceiptService
{
    public function __construct(
        private readonly CommunicationNumberService $numbers,
        private readonly AuditLogger $audit,
    ) {}

    public function generate(CommunicationLog $communication, CommunicationReceiptType $type, ?CommunicationDelivery $delivery = null, ?User $actor = null): CommunicationReceipt
    {
        $number = $this->numbers->receipt();
        $html = view('communications.receipts.send-proof', compact('communication', 'delivery', 'type', 'number'))->render();
        $path = 'communications/receipts/'.now()->format('Y/m').'/'.$number.'.html';
        Storage::disk('local')->put($path, $html);

        $receipt = new CommunicationReceipt([
            'communication_log_id' => $communication->id,
            'communication_delivery_id' => $delivery?->id,
            'receipt_type' => $type,
        ]);
        $receipt->forceFill([
            'receipt_number' => $number,
            'storage_disk' => 'local',
            'storage_path' => $path,
            'mime_type' => 'text/html',
            'file_size' => strlen($html),
            'checksum' => hash('sha256', $html),
            'generated_by' => $actor?->id,
            'generated_at' => now(),
        ])->save();

        return $receipt;
    }

    public function uploadPostal(CommunicationDelivery $delivery, UploadedFile $file, ?User $actor = null): CommunicationReceipt
    {
        $number = $this->numbers->receipt();
        $extension = strtolower($file->getClientOriginalExtension() ?: 'bin');
        $path = $file->storeAs('communications/receipts/'.now()->format('Y/m'), $number.'.'.$extension, 'local');

        $receipt = new CommunicationReceipt([
            'communication_log_id' => $delivery->communication_log_id,
            'communication_delivery_id' => $delivery->id,
            'receipt_type' => CommunicationReceiptType::PostalProof,
        ]);
        $receipt->forceFill([
            'receipt_number' => $number,
            'storage_disk' => 'local',
            'storage_path' => $path,
            'mime_type' => $file->getMimeType() ?: 'application/octet-stream',
            'file_size' => $file->getSize(),
            'checksum' => hash_file('sha256', $file->getRealPath()),
            'generated_by' => $actor?->id,
            'generated_at' => now(),
        ])->save();

        return $receipt;
    }

    public function download(CommunicationReceipt $receipt, User $actor): StreamedResponse
    {
        $this->audit->record(AuditEvents::ACCESS, $receipt, 'notifications', 'communication_receipt_download', 'Comprovativo de comunicação descarregado.');

        return Storage::disk($receipt->storage_disk)->download(
            $receipt->storage_path,
            $receipt->receipt_number.'.'.($receipt->mime_type === 'text/html' ? 'html' : 'bin'),
        );
    }
}
