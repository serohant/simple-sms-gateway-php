<?php
namespace App;

class Logger {
    private string $file;
    public function __construct(string $file) { $this->file = $file; }

    public function write(string $level, array $data = []): void {
        // Kişisel verileri yazma.
        $line = json_encode([
            'ts' => gmdate('c'),
            'level' => $level,
            'data' => $data,
        ], JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE);
        file_put_contents($this->file, $line . PHP_EOL, FILE_APPEND);
    }
}