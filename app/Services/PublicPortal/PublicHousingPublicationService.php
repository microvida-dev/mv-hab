<?php

namespace App\Services\PublicPortal;

use App\Enums\PublicVisibilityStatus;
use App\Models\HousingUnit;
use App\Models\HousingUnitImage;
use App\Models\HousingUnitPublicDocument;
use App\Models\User;
use App\Services\Audit\AuditLogger;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;

class PublicHousingPublicationService
{
    public function __construct(private readonly AuditLogger $auditLogger) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function updateProfile(HousingUnit $housingUnit, array $data, User $user): HousingUnit
    {
        if (blank($data['public_slug'] ?? null)) {
            $data['public_slug'] = $this->uniqueSlug($data['public_title'] ?? $housingUnit->displayTitle(), $housingUnit);
        } else {
            $data['public_slug'] = $this->uniqueSlug((string) $data['public_slug'], $housingUnit);
        }

        $old = $housingUnit->only(array_keys($data));
        $housingUnit->fill($data);
        $housingUnit->save();

        $this->auditLogger->record(
            'housing_public_profile_updated',
            $housingUnit,
            'housing_units',
            'update',
            'Ficha pública da habitação atualizada.',
            $old,
            $housingUnit->only(array_keys($data)),
            ['updated_by' => $user->getKey()],
        );

        return $housingUnit;
    }

    public function publish(HousingUnit $housingUnit, User $user): HousingUnit
    {
        $housingUnit->forceFill([
            'is_public' => true,
            'public_visibility_status' => PublicVisibilityStatus::Published,
            'published_at' => $housingUnit->published_at ?? now(),
            'unpublished_at' => null,
        ])->save();

        $this->auditLogger->record(
            'housing_public_profile_published',
            $housingUnit,
            'housing_units',
            'publish',
            'Habitação publicada no portal público.',
            [],
            ['published_by' => $user->getKey()],
        );

        return $housingUnit;
    }

    public function unpublish(HousingUnit $housingUnit, User $user): HousingUnit
    {
        $housingUnit->forceFill([
            'is_public' => false,
            'public_visibility_status' => PublicVisibilityStatus::Hidden,
            'unpublished_at' => now(),
        ])->save();

        $this->auditLogger->record(
            'housing_public_profile_unpublished',
            $housingUnit,
            'housing_units',
            'update',
            'Habitação retirada do portal público.',
            [],
            ['unpublished_by' => $user->getKey()],
        );

        return $housingUnit;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function storeImage(HousingUnit $housingUnit, UploadedFile $file, array $data, User $user): HousingUnitImage
    {
        $path = $file->store('public-housing/images', 'public');

        if (($data['is_cover'] ?? false) === true) {
            $housingUnit->images()->update(['is_cover' => false]);
        }

        $image = $housingUnit->images()->create([
            'uploaded_by' => $user->getKey(),
            'approved_by' => $user->getKey(),
            'title' => $data['title'] ?? null,
            'alt_text' => $data['alt_text'] ?? null,
            'disk' => 'public',
            'path' => $path,
            'mime_type' => $file->getMimeType(),
            'size_bytes' => $file->getSize(),
            'is_cover' => (bool) ($data['is_cover'] ?? false),
            'is_public' => (bool) ($data['is_public'] ?? false),
            'approved_at' => now(),
            'sort_order' => (int) ($data['sort_order'] ?? 0),
        ]);

        if ($image->is_cover) {
            $housingUnit->forceFill(['og_image_path' => $image->path])->save();
        }

        $this->auditLogger->record('housing_public_image_uploaded', $image, 'housing_units', 'update', 'Imagem pública carregada.');

        return $image;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function storeDocument(HousingUnit $housingUnit, UploadedFile $file, array $data, User $user): HousingUnitPublicDocument
    {
        $path = $file->store('public-housing/documents', 'public');

        $document = HousingUnitPublicDocument::query()->create([
            'housing_unit_id' => $housingUnit->getKey(),
            'contest_id' => $data['contest_id'] ?? null,
            'uploaded_by' => $user->getKey(),
            'approved_by' => $user->getKey(),
            'title' => $data['title'],
            'description' => $data['description'] ?? null,
            'document_type' => $data['document_type'] ?? 'other',
            'disk' => 'public',
            'path' => $path,
            'original_filename' => $file->getClientOriginalName(),
            'mime_type' => $file->getMimeType(),
            'size_bytes' => $file->getSize(),
            'checksum' => hash_file('sha256', $file->getRealPath()),
            'is_public' => (bool) ($data['is_public'] ?? false),
            'approved_at' => now(),
            'published_at' => ($data['is_public'] ?? false) ? now() : null,
            'sort_order' => (int) ($data['sort_order'] ?? 0),
        ]);

        $this->auditLogger->record('housing_public_document_uploaded', $document, 'housing_units', 'update', 'Documento público carregado.');

        return $document;
    }

    private function uniqueSlug(string $value, HousingUnit $housingUnit): string
    {
        $base = Str::slug($value) ?: Str::slug($housingUnit->code);
        $slug = $base;
        $counter = 2;

        while (HousingUnit::query()
            ->where('public_slug', $slug)
            ->whereKeyNot($housingUnit->getKey())
            ->exists()) {
            $slug = $base.'-'.$counter++;
        }

        return $slug;
    }
}
