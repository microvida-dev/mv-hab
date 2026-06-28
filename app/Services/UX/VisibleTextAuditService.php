<?php

namespace App\Services\UX;

use Illuminate\Support\Facades\File;
use SplFileInfo;

class VisibleTextAuditService
{
    public function __construct(private readonly TerminologyService $terminology) {}

    /**
     * @param  list<string>  $paths
     * @return list<array{term: string, replacement: string, path: string, line: int}>
     */
    public function scan(array $paths): array
    {
        $findings = [];

        foreach ($this->files($paths) as $file) {
            $contents = File::get($file->getPathname());

            foreach (preg_split('/\R/', $contents) ?: [] as $index => $line) {
                foreach ($this->terminology->replacements() as $term => $replacement) {
                    if (str_contains($line, $term) && ! $this->terminology->shouldPreserve($term)) {
                        $findings[] = [
                            'term' => $term,
                            'replacement' => $replacement,
                            'path' => $file->getPathname(),
                            'line' => $index + 1,
                        ];
                    }
                }
            }
        }

        return $findings;
    }

    /**
     * @param  list<string>  $paths
     * @return list<SplFileInfo>
     */
    private function files(array $paths): array
    {
        $files = [];

        foreach ($paths as $path) {
            if (File::isFile($path)) {
                $files[] = new SplFileInfo($path);

                continue;
            }

            if (! File::isDirectory($path)) {
                continue;
            }

            foreach (File::allFiles($path) as $file) {
                if (in_array($file->getExtension(), ['blade.php', 'php', 'md'], true)) {
                    $files[] = $file;
                }
            }
        }

        return $files;
    }
}
