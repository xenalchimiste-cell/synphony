<?php

namespace App\Service;

use Symfony\Component\Process\Process;
use Symfony\Component\Process\Exception\ProcessFailedException;

class SpotifyDownloader
{
    private string $outputDir;

    public function __construct(string $projectDir)
    {
        $this->outputDir = $projectDir . '/public/music/uploads/';

        if (!is_dir($this->outputDir)) {
            mkdir($this->outputDir, 0755, true);
        }
    }

    public function download(string $spotifyUrl): array
    {
        set_time_limit(0);
        ini_set('max_execution_time', '0');

        if (!$this->isValidSpotifyUrl($spotifyUrl)) {
            return ['success' => false, 'error' => 'URL Spotify invalide'];
        }

        if (!$this->isSpotdlInstalled()) {
            return [
                'success' => false,
                'error' => 'spotdl n\'est pas installé. Installe-le avec : pip3 install spotdl'
            ];
        }

        try {
            $uniqueId = uniqid();
            $spotdlPath = $this->getSpotdlPath();

            // spotdl télécharge le son ET embarque cover + métadonnées automatiquement
            $process = new Process([
                $spotdlPath,
                'download',
                $spotifyUrl,
                '--output', $this->outputDir . $uniqueId . '.{output-ext}',
                '--format', 'mp3',
                '--bitrate', '192k',
                '--overwrite', 'skip',
            ]);

            $process->setTimeout(300);
            $process->run();

            if (!$process->isSuccessful()) {
                $errorOutput = $process->getErrorOutput();
                return [
                    'success' => false,
                    'error' => 'Erreur lors du téléchargement : ' . $errorOutput
                ];
            }

            // Chercher le fichier téléchargé (spotdl nomme le fichier avec le titre)
            $filename = $this->findDownloadedFile($uniqueId);

            if (!$filename) {
                return ['success' => false, 'error' => 'Fichier introuvable après téléchargement'];
            }

            return [
                'success' => true,
                'filename' => $filename,
                'error' => null
            ];

        } catch (\Exception $e) {
            return ['success' => false, 'error' => 'Erreur : ' . $e->getMessage()];
        }
    }

    /**
     * Cherche le fichier mp3 créé par spotdl avec l'uniqueId dans le nom
     */
    private function findDownloadedFile(string $uniqueId): ?string
    {
        $files = glob($this->outputDir . $uniqueId . '*.mp3');
        if (!empty($files)) {
            return basename($files[0]);
        }

        // spotdl peut nommer le fichier différemment, chercher les fichiers récents
        $recentFiles = [];
        foreach (glob($this->outputDir . '*.mp3') as $file) {
            if (filemtime($file) > time() - 60) {
                $recentFiles[] = basename($file);
            }
        }

        return !empty($recentFiles) ? end($recentFiles) : null;
    }

    public function isValidSpotifyUrl(string $url): bool
    {
        return strpos($url, 'spotify.com') !== false
            || strpos($url, 'spotify:') !== false;
    }

    /**
     * Retourne le type d'URL Spotify : 'track', 'playlist', 'album' ou null
     */
    public function getUrlType(string $url): ?string
    {
        if (strpos($url, '/track/') !== false || strpos($url, 'spotify:track:') !== false) {
            return 'track';
        }
        if (strpos($url, '/playlist/') !== false || strpos($url, 'spotify:playlist:') !== false) {
            return 'playlist';
        }
        if (strpos($url, '/album/') !== false || strpos($url, 'spotify:album:') !== false) {
            return 'album';
        }
        return null;
    }

    /**
     * Télécharge une playlist Spotify complète
     * Retourne la liste des fichiers téléchargés
     */
    public function downloadPlaylist(string $spotifyUrl): array
    {
        set_time_limit(0);
        ini_set('max_execution_time', '0');

        if (!$this->isSpotdlInstalled()) {
            return ['success' => false, 'error' => 'spotdl non installé. Installe-le avec : pip3 install spotdl'];
        }

        try {
            // Snapshot des fichiers avant téléchargement
            $before = glob($this->outputDir . '*.mp3');
            $beforeSet = array_flip(array_map('basename', $before));

            $spotdlPath = $this->getSpotdlPath();

            $process = new Process([
                $spotdlPath,
                'download',
                $spotifyUrl,
                '--output', $this->outputDir . '{title}.{output-ext}',
                '--format', 'mp3',
                '--bitrate', '192k',
                '--overwrite', 'skip',
            ]);

            $process->setTimeout(1800); // 30 minutes pour les grandes playlists
            $process->run();

            // Fichiers après téléchargement
            $after = glob($this->outputDir . '*.mp3');
            $newFiles = [];
            foreach ($after as $file) {
                $base = basename($file);
                if (!isset($beforeSet[$base])) {
                    $newFiles[] = $base;
                }
            }

            if (empty($newFiles)) {
                return ['success' => false, 'error' => 'Aucun fichier téléchargé'];
            }

            return ['success' => true, 'filenames' => $newFiles, 'error' => null];

        } catch (\Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    private function isSpotdlInstalled(): bool
    {
        foreach ($this->getPossiblePaths() as $path) {
            $process = new Process([$path, '--version']);
            $process->run();
            if ($process->isSuccessful()) {
                return true;
            }
        }
        return false;
    }

    private function getSpotdlPath(): string
    {
        foreach ($this->getPossiblePaths() as $path) {
            $process = new Process([$path, '--version']);
            $process->run();
            if ($process->isSuccessful()) {
                return $path;
            }
        }
        return 'spotdl';
    }

    private function getPossiblePaths(): array
    {
        $home = $_SERVER['HOME'] ?? $_SERVER['USERPROFILE'] ?? '';
        return array_filter([
            '/opt/homebrew/bin/spotdl',
            '/usr/local/bin/spotdl',
            '/usr/bin/spotdl',
            $home ? $home . '/.local/bin/spotdl' : null,
            'spotdl',
        ]);
    }
}
