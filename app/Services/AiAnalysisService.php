<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * Thin client for the external "Audio Postmortem" service — duplicate /
 * violence / anti-government detection + transcription, run against every
 * newly ingested audio asset (M16). Async job/poll contract:
 *   POST /analyze     -> { job_id, status: "processing" }   (202)
 *   GET  /jobs/{id}    -> { status: "processing"|"done"|"error", ... }
 */
class AiAnalysisService
{
    private string $baseUrl;

    private int $timeout;

    public function __construct()
    {
        $this->baseUrl = rtrim((string) config('services.audio_postmortem.base_url'), '/');
        $this->timeout = (int) config('services.audio_postmortem.timeout', 30);
    }

    /**
     * Submit a file for analysis. Returns the external job id.
     *
     * @throws RuntimeException on any network/HTTP failure
     */
    public function submit(string $absoluteFilePath): string
    {
        if (! is_file($absoluteFilePath)) {
            throw new RuntimeException("File not found for AI analysis: {$absoluteFilePath}");
        }

        $handle = fopen($absoluteFilePath, 'r');
        if ($handle === false) {
            throw new RuntimeException("Could not open file for AI analysis: {$absoluteFilePath}");
        }

        try {
            $response = Http::timeout($this->timeout)
                ->attach('file', $handle, basename($absoluteFilePath))
                ->post("{$this->baseUrl}/analyze");
        } catch (ConnectionException $e) {
            throw new RuntimeException('Could not reach the audio analysis service: '.$e->getMessage(), previous: $e);
        } finally {
            if (is_resource($handle)) {
                fclose($handle);
            }
        }

        if ($response->failed()) {
            throw new RuntimeException('Audio analysis service returned an error: HTTP '.$response->status().' — '.$response->body());
        }

        $jobId = $response->json('job_id');
        if (! is_string($jobId) || $jobId === '') {
            throw new RuntimeException('Audio analysis service did not return a job_id.');
        }

        return $jobId;
    }

    /**
     * Poll the result for a previously submitted job.
     *
     * @return array<string, mixed> the decoded response body
     *
     * @throws RuntimeException on any network/HTTP failure
     */
    public function poll(string $jobId): array
    {
        try {
            $response = Http::timeout($this->timeout)->get("{$this->baseUrl}/jobs/{$jobId}");
        } catch (ConnectionException $e) {
            throw new RuntimeException('Could not reach the audio analysis service: '.$e->getMessage(), previous: $e);
        }

        try {
            $response->throw();
        } catch (RequestException $e) {
            throw new RuntimeException('Audio analysis service returned an error: HTTP '.$response->status().' — '.$response->body(), previous: $e);
        }

        return (array) $response->json();
    }
}
