<?php
require_once __DIR__ . '/../src/init.php';

use App\Services\StorageService;

$chunkBase = StorageService::getChunkDir();
echo "Chunk Base: $chunkBase\n";
echo "Base exists: " . (is_dir($chunkBase) ? "YES" : "NO") . "\n";

$testUploadId = 'up_test_' . time();
$chunkDir = $chunkBase . DIRECTORY_SEPARATOR . $testUploadId;
mkdir($chunkDir, 0775, true);

// Create 3 chunks
file_put_contents($chunkDir . '/chunk_0.part', 'chunk 0 content');
file_put_contents($chunkDir . '/chunk_1.part', 'chunk 1 content');
file_put_contents($chunkDir . '/chunk_2.part', 'chunk 2 content');

echo "chunk_0 exists: " . (file_exists($chunkDir . '/chunk_0.part') ? "YES" : "NO") . "\n";
echo "chunk_1 exists: " . (file_exists($chunkDir . '/chunk_1.part') ? "YES" : "NO") . "\n";
echo "chunk_2 exists: " . (file_exists($chunkDir . '/chunk_2.part') ? "YES" : "NO") . "\n";

// Check cleanOldChunks
StorageService::cleanOldChunks(86400);

echo "After cleanOldChunks:\n";
echo "chunk_0 exists: " . (file_exists($chunkDir . '/chunk_0.part') ? "YES" : "NO") . "\n";

// Clean up
@unlink($chunkDir . '/chunk_0.part');
@unlink($chunkDir . '/chunk_1.part');
@unlink($chunkDir . '/chunk_2.part');
@rmdir($chunkDir);
echo "Done.\n";
