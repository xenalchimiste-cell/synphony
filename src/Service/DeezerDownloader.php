<?php

namespace App\Service;

use Symfony\Component\Process\Process;

class DeezerDownloader
{
    private string $outputDir;

    public function __construct(string $projectDir)
    {
        $this->outputDir = $projectDir . '/public/music/uploads/';
        if (!is_dir($this->outputDir)) {
            mkdir($this->outputDir, 0755, true);
        }
    }

    /**
     * Parse une URL Deezer et retourne le type (track/playlist/album) et l'ID
     */
    public function parseUrl(string $url): ?array
    {
        // https://www.deezer.com/fr/track/123456
        // https://www.deezer.com/fr/playlist/123456
        // https://www.deezer.com/fr/album/123456
        if (preg_match('#deezer\.com(?:/[a-z]{2})?/(track|playlist|album)/(\d+)#', $url, $m)) {
            return ['type' => $m[1], 'id' => $m[2]];
        }
        return null;
    }

    public function isValidDeezerUrl(string $url): bool
    {
        return $this->parseUrl($url) !== null;
    }

    /**
     * Récupère les infos d'un track via l'API publique Deezer (sans auth)
     */
    public function getTrackInfo(string $trackId): ?array
    {
        $ctx  = stream_context_create(['http' => ['timeout' => 10]]);
        $json = @file_get_contents("https://api.deezer.com/track/{$trackId}", false, $ctx);
        if (!$json) return null;
        $data = json_decode($json, true);
        if (!$data || isset($data['error'])) return null;

        return [
            'id'       => $data['id'],
            'title'    => $data['title'],
            'artist'   => $data['artist']['name'] ?? null,
            'album'    => $data['album']['title'] ?? null,
            'cover'    => $data['album']['cover_xl'] ?? $data['album']['cover_big'] ?? null,
            'duration' => $data['duration'] ?? null,
        ];
    }

    /**
     * Récupère tous les tracks d'une playlist Deezer
     */
    public function getPlaylistTracks(string $playlistId): array
    {
        $tracks = [];
        $url = "https://api.deezer.com/playlist/{$playlistId}/tracks?limit=100";
        $ctx = stream_context_create(['http' => ['timeout' => 10]]);

        while ($url) {
            $json = @file_get_contents($url, false, $ctx);
            if (!$json) break;
            $data = json_decode($json, true);
            if (!$data || empty($data['data'])) break;

            foreach ($data['data'] as $t) {
                $tracks[] = [
                    'id'       => $t['id'],
                    'title'    => $t['title'],
                    'artist'   => $t['artist']['name'] ?? null,
                    'album'    => $t['album']['title'] ?? null,
                    'cover'    => $t['album']['cover_xl'] ?? $t['album']['cover_big'] ?? null,
                    'duration' => $t['duration'] ?? null,
                ];
            }

            $url = $data['next'] ?? null;
        }

        return $tracks;
    }

    /**
     * Lance le téléchargement en arrière-plan et retourne un job_id immédiatement
     * (uniquement pour les tracks individuels)
     */
    public function downloadTrack(array $trackInfo): array
    {
        if (!$this->isYtDlpInstalled()) {
            return ['success' => false, 'error' => 'yt-dlp non installé. Installe-le avec : brew install yt-dlp'];
        }

        $jobId    = uniqid('dz_', true);
        $ytDlp    = $this->getYtDlpPath();
        $ffmpeg   = $this->getFfmpegPath();
        $output   = $this->outputDir . $jobId . '.%(ext)s';
        $search   = 'ytsearch1:' . $trackInfo['title'] . ' ' . ($trackInfo['artist'] ?? '');
        $doneFile = $this->outputDir . $jobId . '.done';
        $errFile  = $this->outputDir . $jobId . '.err';

        // Sauvegarder les métadonnées pour les retrouver plus tard
        file_put_contents(
            $this->outputDir . $jobId . '.meta.json',
            json_encode($trackInfo)
        );

        // Télécharger la cover depuis Deezer immédiatement (c'est rapide)
        $coverPath = null;
        if (!empty($trackInfo['cover'])) {
            $ctx = stream_context_create(['http' => ['timeout' => 10]]);
            $coverData = @file_get_contents($trackInfo['cover'], false, $ctx);
            if ($coverData) {
                $coverFilename = $jobId . '_cover.jpg';
                file_put_contents($this->outputDir . $coverFilename, $coverData);
                $coverPath = 'music/uploads/' . $coverFilename;
            }
        }

        if ($coverPath) {
            file_put_contents($this->outputDir . $jobId . '.cover', $coverPath);
        }

        // Lancer yt-dlp en arrière-plan (non-bloquant)
        $cmd = sprintf(
            'nohup %s --quiet --no-warnings --extract-audio --audio-format mp3 --audio-quality 192k --ffmpeg-location %s --output %s %s > %s 2>&1 && touch %s &',
            escapeshellarg($ytDlp),
            escapeshellarg($ffmpeg),
            escapeshellarg($output),
            escapeshellarg($search),
            escapeshellarg($errFile),
            escapeshellarg($doneFile)
        );
        exec($cmd);

        return [
            'success'   => true,
            'job_id'    => $jobId,
            'coverPath' => $coverPath,
            'metadata'  => $trackInfo,
            'error'     => null,
        ];
    }

    /**
     * Téléchargement synchrone (bloquant) — utilisé pour les playlists/albums
     * Attend la fin du téléchargement et retourne le filename directement
     */
    public function downloadTrackSync(array $trackInfo): array
    {
        if (!$this->isYtDlpInstalled()) {
            return ['success' => false, 'error' => 'yt-dlp non installé'];
        }

        $fileId  = uniqid('dz_', true);
        $ytDlp   = $this->getYtDlpPath();
        $ffmpeg  = $this->getFfmpegPath();
        $output  = $this->outputDir . $fileId . '.%(ext)s';
        $search  = 'ytsearch1:' . $trackInfo['title'] . ' ' . ($trackInfo['artist'] ?? '');

        // Télécharger la cover
        $coverPath = null;
        if (!empty($trackInfo['cover'])) {
            $ctx = stream_context_create(['http' => ['timeout' => 10]]);
            $coverData = @file_get_contents($trackInfo['cover'], false, $ctx);
            if ($coverData) {
                $coverFilename = $fileId . '_cover.jpg';
                file_put_contents($this->outputDir . $coverFilename, $coverData);
                $coverPath = 'music/uploads/' . $coverFilename;
            }
        }

        // Lancer yt-dlp de façon synchrone (bloquant)
        $process = new Process([
            $ytDlp,
            '--quiet',
            '--no-warnings',
            '--extract-audio',
            '--audio-format', 'mp3',
            '--audio-quality', '192k',
            '--ffmpeg-location', $ffmpeg,
            '--output', $output,
            $search,
        ]);
        $process->setTimeout(180); // 3 minutes max par morceau
        $process->run();

        $mp3File = $this->outputDir . $fileId . '.mp3';

        if (!$process->isSuccessful() || !file_exists($mp3File)) {
            return [
                'success' => false,
                'error'   => 'Téléchargement échoué : ' . substr($process->getErrorOutput(), 0, 200),
            ];
        }

        return [
            'success'   => true,
            'filename'  => $fileId . '.mp3',
            'coverPath' => $coverPath,
            'metadata'  => $trackInfo,
        ];
    }

    /**
     * Vérifie le statut d'un job de téléchargement
     */
    public function checkJob(string $jobId): array
    {
        if (!preg_match('/^dz_[a-f0-9.]+$/', $jobId)) {
            return ['status' => 'invalid'];
        }

        $doneFile = $this->outputDir . $jobId . '.done';
        $mp3File  = $this->outputDir . $jobId . '.mp3';
        $errFile  = $this->outputDir . $jobId . '.err';
        $metaFile = $this->outputDir . $jobId . '.meta.json';
        $coverFile = $this->outputDir . $jobId . '.cover';

        if (!file_exists($metaFile)) {
            return ['status' => 'not_found'];
        }

        $meta = json_decode(file_get_contents($metaFile), true);
        $coverPath = file_exists($coverFile) ? file_get_contents($coverFile) : null;

        if (file_exists($doneFile) && file_exists($mp3File)) {
            return [
                'status'    => 'done',
                'filename'  => $jobId . '.mp3',
                'coverPath' => $coverPath,
                'metadata'  => $meta,
            ];
        }

        // Vérifier si erreur
        if (file_exists($errFile) && filesize($errFile) > 0) {
            $err = file_get_contents($errFile);
            if (strpos($err, 'ERROR') !== false) {
                return ['status' => 'error', 'error' => substr($err, 0, 200)];
            }
        }

        return ['status' => 'processing'];
    }

    private function isYtDlpInstalled(): bool
    {
        foreach ($this->getPossibleYtDlpPaths() as $path) {
            $p = new Process([$path, '--version']);
            $p->run();
            if ($p->isSuccessful()) return true;
        }
        return false;
    }

    private function getYtDlpPath(): string
    {
        foreach ($this->getPossibleYtDlpPaths() as $path) {
            $p = new Process([$path, '--version']);
            $p->run();
            if ($p->isSuccessful()) return $path;
        }
        return 'yt-dlp';
    }

    private function getFfmpegPath(): string
    {
        foreach (['/opt/homebrew/bin/ffmpeg', '/usr/local/bin/ffmpeg', 'ffmpeg'] as $path) {
            $p = new Process([$path, '-version']);
            $p->run();
            if ($p->isSuccessful()) return $path;
        }
        return 'ffmpeg';
    }

    private function getPossibleYtDlpPaths(): array
    {
        return [
            '/opt/homebrew/bin/yt-dlp',
            '/usr/local/bin/yt-dlp',
            'yt-dlp',
        ];
    }
}
