<?php

namespace App\Services;

use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;

class FileStorageService
{
    public function disk(?string $disk = null): Filesystem
    {
        return Storage::disk($this->resolveDisk($disk));
    }

    public function resolveDisk(?string $disk = null): string
    {
        return $disk ?: (string) config('media.disk', 'public');
    }

    public function store(UploadedFile $file, string $directory, ?string $disk = null, ?string $filename = null): string
    {
        $diskName = $this->resolveDisk($disk);
        $directory = trim($directory, '/');

        if ($filename !== null) {
            $path = $file->storeAs($directory, $filename, $diskName);
        } else {
            $path = $file->store($directory, $diskName);
        }

        if ($path === false) {
            throw new RuntimeException('Failed to store uploaded file.');
        }

        return $path;
    }

    public function storeAs(UploadedFile $file, string $directory, string $filename, ?string $disk = null): string
    {
        return $this->store($file, $directory, $disk, $filename);
    }

    public function storeWithUniqueName(UploadedFile $file, string $directory, ?string $disk = null): string
    {
        $extension = $file->getClientOriginalExtension() ?: $file->extension() ?: 'bin';
        $filename = Str::uuid()->toString().'.'.strtolower($extension);

        return $this->store($file, $directory, $disk, $filename);
    }

    public function delete(?string $path, ?string $disk = null): bool
    {
        if ($path === null || $path === '') {
            return false;
        }

        return $this->disk($disk)->delete($path);
    }

    public function url(?string $path, ?string $disk = null): ?string
    {
        if ($path === null || $path === '') {
            return null;
        }

        return $this->disk($disk)->url($path);
    }

    public function exists(string $path, ?string $disk = null): bool
    {
        return $this->disk($disk)->exists($path);
    }

    public function temporaryUrl(string $path, \DateTimeInterface $expiration, ?string $disk = null): string
    {
        return $this->disk($disk)->temporaryUrl($path, $expiration);
    }
}
