<?php

namespace App\Service;

use Symfony\Component\Process\Process;
use Symfony\Component\Process\Exception\ProcessFailedException;

class YouTubeDownloader
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
     * Télécharge une vidéo YouTube et la convertit en MP3
     *
     * @param string $youtubeUrl L'URL de la vidéo YouTube
     * @return array ['success' => bool, 'filename' => string|null, 'metadata' => array, 'error' => string|null]
     */
    public function download(string $youtubeUrl): array
    {
        set_time_limit(0);
        ini_set('max_execution_time', '0');

        // Validation de l'URL YouTube
        if (!$this->isValidYouTubeUrl($youtubeUrl)) {
            return [
                'success' => false,
                'filename' => null,
                'metadata' => [],
                'error' => 'URL YouTube invalide'
            ];
        }

        // Vérifier si yt-dlp est installé
        if (!$this->isYtDlpInstalled()) {
            return [
                'success' => false,
                'filename' => null,
                'metadata' => [],
                'error' => 'yt-dlp n\'est pas installé. Installez-le avec: brew install yt-dlp (macOS) ou pip install yt-dlp'
            ];
        }

        // Vérifier si ffmpeg est installé
        if (!$this->isFfmpegInstalled()) {
            return [
                'success' => false,
                'filename' => null,
                'metadata' => [],
                'error' => 'ffmpeg n\'est pas installé. Installez-le avec: brew install ffmpeg'
            ];
        }

        try {
            // Générer un nom de fichier unique
            $uniqueId = uniqid();
            $outputTemplate = $this->outputDir . $uniqueId . '.%(ext)s';

            // Commande yt-dlp pour télécharger et convertir en MP3
            $ytDlpPath = $this->getYtDlpPath();
            $ffmpegPath = $this->getFfmpegPath();
            $process = new Process([
                $ytDlpPath,
                '--quiet',                    // Réduire la verbosité
                '--no-warnings',              // Supprimer les avertissements
                '--extract-audio',
                '--audio-format', 'mp3',
                '--audio-quality', '192',     // Qualité acceptable (0 peut causer des problèmes)
                '--ffmpeg-location', $ffmpegPath,
                '--output', $outputTemplate,
                $youtubeUrl
            ]);

            $process->setTimeout(300); // 5 minutes de timeout
            $process->run();

            if (!$process->isSuccessful()) {
                throw new ProcessFailedException($process);
            }

            // Le fichier téléchargé
            $filename = $uniqueId . '.mp3';
            $filepath = $this->outputDir . $filename;

            // Vérifier que le fichier existe
            if (!file_exists($filepath)) {
                return [
                    'success' => false,
                    'filename' => null,
                    'metadata' => [],
                    'error' => 'Le fichier n\'a pas été créé correctement'
                ];
            }

            // Récupérer les métadonnées en les interrogeant séparément
            $metadata = $this->getVideoInfo($youtubeUrl) ?? [
                'title' => 'Titre inconnu',
                'artist' => null,
                'album' => null,
                'duration' => null,
                'thumbnail' => null,
            ];

            return [
                'success' => true,
                'filename' => $filename,
                'metadata' => $metadata,
                'error' => null
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'filename' => null,
                'metadata' => [],
                'error' => 'Erreur lors du téléchargement: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Récupère les informations d'une vidéo YouTube sans la télécharger
     *
     * @param string $youtubeUrl L'URL de la vidéo YouTube
     * @return array|null
     */
    public function getVideoInfo(string $youtubeUrl): ?array
    {
        if (!$this->isValidYouTubeUrl($youtubeUrl)) {
            return null;
        }

        if (!$this->isYtDlpInstalled()) {
            return null;
        }

        try {
            $ytDlpPath = $this->getYtDlpPath();
            $process = new Process([
                $ytDlpPath,
                '--dump-json',
                '--no-playlist',
                $youtubeUrl
            ]);

            $process->setTimeout(30);
            $process->run();

            if (!$process->isSuccessful()) {
                return null;
            }

            $output = $process->getOutput();
            $data = json_decode($output, true);

            if (!$data) {
                return null;
            }

            return [
                'title' => $data['title'] ?? 'Titre inconnu',
                'artist' => $data['uploader'] ?? $data['channel'] ?? 'Artiste inconnu',
                'duration' => $data['duration'] ?? 0,
                'thumbnail' => $data['thumbnail'] ?? null,
                'description' => $data['description'] ?? '',
            ];

        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Valide une URL YouTube
     */
    private function isValidYouTubeUrl(string $url): bool
    {
        // Supporter tous les formats YouTube courants:
        // - https://www.youtube.com/watch?v=ID
        // - https://youtu.be/ID
        // - https://www.youtube.com/shorts/ID
        // - https://youtube.com/watch?v=ID (sans www)
        // - Avec paramètres supplémentaires (?list=..., ?t=..., etc.)
        return (strpos($url, 'youtube.com') !== false || strpos($url, 'youtu.be') !== false);
    }

    /**
     * Vérifie si yt-dlp est installé
     */
    private function isYtDlpInstalled(): bool
    {
        // Essayer plusieurs emplacements possibles
        $possiblePaths = [
            '/opt/homebrew/bin/yt-dlp',  // Homebrew sur Apple Silicon
            '/usr/local/bin/yt-dlp',      // Homebrew sur Intel Mac
            'yt-dlp',                      // PATH system
        ];

        foreach ($possiblePaths as $path) {
            $process = new Process([$path, '--version']);
            $process->run();
            if ($process->isSuccessful()) {
                return true;
            }
        }

        return false;
    }

    /**
     * Récupère le chemin complet de yt-dlp
     */
    private function getYtDlpPath(): string
    {
        $possiblePaths = [
            '/opt/homebrew/bin/yt-dlp',  // Homebrew sur Apple Silicon
            '/usr/local/bin/yt-dlp',      // Homebrew sur Intel Mac
            'yt-dlp',                      // PATH system
        ];

        foreach ($possiblePaths as $path) {
            $process = new Process([$path, '--version']);
            $process->run();
            if ($process->isSuccessful()) {
                return $path;
            }
        }

        return 'yt-dlp'; // Fallback
    }

    /**
     * Vérifie si ffmpeg est installé
     */
    private function isFfmpegInstalled(): bool
    {
        $possiblePaths = [
            '/opt/homebrew/bin/ffmpeg',  // Homebrew sur Apple Silicon
            '/usr/local/bin/ffmpeg',      // Homebrew sur Intel Mac
            'ffmpeg',                      // PATH system
        ];

        foreach ($possiblePaths as $path) {
            $process = new Process([$path, '-version']);
            $process->run();
            if ($process->isSuccessful()) {
                return true;
            }
        }

        return false;
    }

    /**
     * Récupère le chemin complet de ffmpeg
     */
    private function getFfmpegPath(): string
    {
        $possiblePaths = [
            '/opt/homebrew/bin/ffmpeg',  // Homebrew sur Apple Silicon
            '/usr/local/bin/ffmpeg',      // Homebrew sur Intel Mac
            'ffmpeg',                      // PATH system
        ];

        foreach ($possiblePaths as $path) {
            $process = new Process([$path, '-version']);
            $process->run();
            if ($process->isSuccessful()) {
                return $path;
            }
        }

        return 'ffmpeg'; // Fallback
    }

    /**
     * Nettoie les fichiers temporaires
     */
    public function cleanup(string $filename): bool
    {
        $filepath = $this->outputDir . $filename;
        if (file_exists($filepath)) {
            return unlink($filepath);
        }
        return false;
    }
}
