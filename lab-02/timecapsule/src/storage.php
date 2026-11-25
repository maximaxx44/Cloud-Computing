<?php

use Aws\S3\S3Client;

function storage_client(): S3Client
{
    static $client;
    return $client ??= new S3Client([
        'version' => 'latest',
        'region' => config('AWS_REGION', 'eu-central-1'),
    ]);
}

function storage_bucket(): string
{
    $bucket = config('S3_BUCKET', '');
    if ($bucket === '') {
        throw new RuntimeException('S3_BUCKET is not configured.');
    }
    return $bucket;
}

function storage_key(string $storedName): string
{
    return 'uploads/' . basename($storedName);
}

function storage_save(array $uploadedFile, string $extension): string
{
    $storedName = bin2hex(random_bytes(16)) . '.' . $extension;
    storage_client()->putObject([
        'Bucket' => storage_bucket(),
        'Key' => storage_key($storedName),
        'SourceFile' => $uploadedFile['tmp_name'],
        'ContentType' => mime_content_type($uploadedFile['tmp_name']) ?: 'application/octet-stream',
    ]);
    return $storedName;
}

function storage_delete(?string $storedName): void
{
    if ($storedName === null || $storedName === '') {
        return;
    }

    storage_client()->deleteObject([
        'Bucket' => storage_bucket(),
        'Key' => storage_key($storedName),
    ]);
}

function storage_stream(string $storedName, string $mime, string $downloadName, string $disposition): void
{
    $asciiName = preg_replace('/[^A-Za-z0-9._ -]/', '_', $downloadName);
    $contentDisposition = sprintf(
        "%s; filename=\"%s\"; filename*=UTF-8''%s",
        $disposition,
        $asciiName,
        rawurlencode($downloadName)
    );

    $client = storage_client();
    $command = $client->getCommand('GetObject', [
        'Bucket' => storage_bucket(),
        'Key' => storage_key($storedName),
        'ResponseContentType' => $mime,
        'ResponseContentDisposition' => $contentDisposition,
    ]);
    $request = $client->createPresignedRequest($command, '+5 minutes');
    header('Cache-Control: private, no-store');
    header('Referrer-Policy: no-referrer');
    header('Location: ' . (string) $request->getUri(), true, 302);
}
