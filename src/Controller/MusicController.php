<?php

namespace App\Controller;

use App\Entity\Track;
use App\Repository\TrackRepository;
use App\Service\MusicSeedService;
use App\Service\YouTubeDownloader;
use App\Service\DeezerDownloader;
use App\Service\SpotifyDownloader;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class MusicController extends AbstractController
{
    #[Route('/', name: 'app_music_index')]
    public function index(
        TrackRepository $trackRepository,
    ): Response {
        // Charger uniquement tes vraies chansons (pas de seed)
        $tracks = $trackRepository->findBy([], ['uploadedAt' => 'DESC']);

        // Grouper par artiste
        $homeSections = [];
        foreach ($tracks as $track) {
            $artist = $track->getArtist() ?? 'Inconnu';
            $homeSections[$artist][] = $track;
        }

        // Afficher les 12 premières en featured
        $featured = array_slice($tracks, 0, 12);

        return $this->render('music/index.html.twig', [
            'tracks' => $tracks,
            'homeSections' => $homeSections,
            'featured' => $featured,
        ]);
    }

    #[Route('/upload', name: 'app_music_upload', methods: ['POST'])]
    public function upload(Request $request, EntityManagerInterface $em): JsonResponse
    {
        try {
            $file = $request->files->get('music');

            if (!$file) {
                return new JsonResponse(['error' => 'Aucun fichier reçu.'], 400);
            }

            $allowedMimes = ['audio/mpeg', 'audio/mp3', 'audio/wav', 'audio/ogg', 'audio/flac', 'audio/aac', 'audio/x-m4a'];
            if (!in_array($file->getMimeType(), $allowedMimes) && !str_contains($file->getMimeType(), 'audio')) {
                return new JsonResponse(['error' => 'Format non supporté. Utilisez MP3, WAV, OGG ou FLAC.'], 400);
            }

            // Generate unique filename
            $originalName = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
            $extension = $file->getClientOriginalExtension() ?: 'mp3';
            $safeFilename = preg_replace('/[^a-zA-Z0-9\-_]/', '_', $originalName);
            $uniqueFilename = $safeFilename . '_' . uniqid() . '.' . $extension;

            $uploadDir = $this->getParameter('kernel.project_dir') . '/public/music/uploads/';
            $file->move($uploadDir, $uniqueFilename);

            // Extract metadata via getID3
            $title = $originalName;
            $artist = null;
            $album = null;
            $duration = null;
            $genre = null;

            try {
                $getID3 = new \getID3();
                $fileInfo = $getID3->analyze($uploadDir . $uniqueFilename);
                \getid3_lib::CopyTagsToComments($fileInfo);

                if (!empty($fileInfo['comments']['title'][0])) {
                    $title = $fileInfo['comments']['title'][0];
                }
                if (!empty($fileInfo['comments']['artist'][0])) {
                    $artist = $fileInfo['comments']['artist'][0];
                }
                if (!empty($fileInfo['comments']['album'][0])) {
                    $album = $fileInfo['comments']['album'][0];
                }
                if (!empty($fileInfo['playtime_seconds'])) {
                    $duration = (int) $fileInfo['playtime_seconds'];
                }
                if (!empty($fileInfo['comments']['genre'][0])) {
                    $genre = $fileInfo['comments']['genre'][0];
                }
            } catch (\Exception $e) {
                // Metadata extraction failed, use filename as title
            }

            $track = new Track();
            $track->setTitle($title)
                ->setArtist($artist)
                ->setAlbum($album)
                ->setDuration($duration)
                ->setFilename($uniqueFilename)
                ->setGenre($genre);

            // Attempt to upload to Vercel Blob
            $blobUrl = $this->uploadToBlob($uploadDir . $uniqueFilename, $uniqueFilename);
            if ($blobUrl) {
                // If blob upload succeeded, delete local file from /tmp to save space
                unlink($uploadDir . $uniqueFilename);
                // On Vercel, the source is the external Blob URL
                $track->setFilename($blobUrl);
            }

            $em->persist($track);
            $em->flush();

            $src = $blobUrl ? $blobUrl : '/music/uploads/' . $uniqueFilename;

            return new JsonResponse([
                'id' => $track->getId(),
                'title' => $track->getTitle(),
                'artist' => $track->getArtist() ?? 'Artiste inconnu',
                'album' => $track->getAlbum() ?? '',
                'duration' => $track->getDuration(),
                'genre' => $track->getGenre() ?? '',
                'src' => $src,
                'cover' => null,
            ]);
        } catch (\Throwable $e) {
            return new JsonResponse([
                'error' => 'Erreur serveur : ' . $e->getMessage() . ' (' . $e->getFile() . ':' . $e->getLine() . ')'
            ], 500);
        }
    }

    #[Route('/delete/{id}', name: 'app_music_delete', methods: ['DELETE'])]
    public function delete(Track $track, EntityManagerInterface $em): JsonResponse
    {
        $filepath = $this->getParameter('kernel.project_dir') . '/public/music/uploads/' . $track->getFilename();
        if (file_exists($filepath)) {
            unlink($filepath);
        }

        $em->remove($track);
        $em->flush();

        return new JsonResponse(['success' => true]);
    }

    #[Route('/api/tracks', name: 'api_tracks_list', methods: ['GET'])]
    public function getTracks(TrackRepository $trackRepository): JsonResponse
    {
        $tracks = $trackRepository->findBy([], ['uploadedAt' => 'DESC']);
        $data = [];
        foreach ($tracks as $t) {
            $filename = $t->getFilename();
            $src = str_starts_with($filename, 'http') ? $filename : '/music/uploads/' . $filename;
            
            $coverPath = $t->getCoverPath();
            $cover = null;
            if ($coverPath) {
                $cover = str_starts_with($coverPath, 'http') ? $coverPath : '/' . ltrim($coverPath, '/');
            }

            $data[] = [
                'id' => $t->getId(),
                'title' => $t->getTitle(),
                'artist' => $t->getArtist() ?? 'Artiste inconnu',
                'album' => $t->getAlbum() ?? '',
                'duration' => $t->getDuration(),
                'genre' => $t->getGenre() ?? '',
                'src' => $src,
                'cover' => $cover,
            ];
        }

        return new JsonResponse($data);
    }

    #[Route('/audio/{filename}', name: 'app_music_stream', methods: ['GET', 'HEAD'], requirements: ['filename' => '.+'])]
    public function streamAudio(string $filename, Request $request): Response
    {
        if (str_contains($filename, '/') || str_contains($filename, '\\') || str_contains($filename, '..')) {
            throw $this->createNotFoundException();
        }

        $filepath = $this->getParameter('kernel.project_dir') . '/public/music/uploads/' . $filename;
        if (!is_file($filepath)) {
            throw $this->createNotFoundException();
        }

        $response = new BinaryFileResponse($filepath);
        $response->headers->set('Content-Type', 'audio/mpeg');
        $response->setAutoEtag();
        $response->setAutoLastModified();
        $response->prepare($request);

        return $response;
    }

    #[Route('/youtube/info', name: 'app_youtube_info', methods: ['POST'])]
    public function youtubeInfo(Request $request, YouTubeDownloader $downloader): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $youtubeUrl = $data['url'] ?? null;

        if (!$youtubeUrl) {
            return new JsonResponse(['error' => 'URL manquante'], 400);
        }

        $info = $downloader->getVideoInfo($youtubeUrl);

        if (!$info) {
            return new JsonResponse(['error' => 'Impossible de récupérer les informations de la vidéo'], 400);
        }

        return new JsonResponse([
            'success' => true,
            'info' => $info
        ]);
    }

    #[Route('/youtube/download', name: 'app_youtube_download', methods: ['POST'])]
    public function youtubeDownload(
        Request $request,
        YouTubeDownloader $downloader,
        EntityManagerInterface $em
    ): JsonResponse {
        $data = json_decode($request->getContent(), true);
        $youtubeUrl = $data['url'] ?? null;

        if (!$youtubeUrl) {
            return new JsonResponse(['error' => 'URL manquante'], 400);
        }

        // Télécharger la vidéo
        $result = $downloader->download($youtubeUrl);

        if (!$result['success']) {
            return new JsonResponse(['error' => $result['error']], 400);
        }

        // Extraire les métadonnées avec getID3 pour plus de précision
        $uploadDir = $this->getParameter('kernel.project_dir') . '/public/music/uploads/';
        $filepath = $uploadDir . $result['filename'];

        $title = $result['metadata']['title'] ?? 'Titre inconnu';
        $artist = $result['metadata']['artist'] ?? null;
        $album = $result['metadata']['album'] ?? null;
        $duration = $result['metadata']['duration'] ?? null;
        $genre = null;

        // Utiliser getID3 pour extraire davantage de métadonnées si disponibles
        try {
            $getID3 = new \getID3();
            $fileInfo = $getID3->analyze($filepath);
            \getid3_lib::CopyTagsToComments($fileInfo);

            if (!empty($fileInfo['comments']['title'][0])) {
                $title = $fileInfo['comments']['title'][0];
            }
            if (!empty($fileInfo['comments']['artist'][0])) {
                $artist = $fileInfo['comments']['artist'][0];
            }
            if (!empty($fileInfo['comments']['album'][0])) {
                $album = $fileInfo['comments']['album'][0];
            }
            if (!empty($fileInfo['playtime_seconds'])) {
                $duration = (int) $fileInfo['playtime_seconds'];
            }
            if (!empty($fileInfo['comments']['genre'][0])) {
                $genre = $fileInfo['comments']['genre'][0];
            }
        } catch (\Exception $e) {
            // Continuer avec les métadonnées de YouTube
        }

        // Créer l'entité Track
        $track = new Track();
        $track->setTitle($title)
            ->setArtist($artist)
            ->setAlbum($album)
            ->setDuration($duration)
            ->setFilename($result['filename'])
            ->setGenre($genre);

        // Attempt Vercel Blob upload
        $blobUrl = $this->uploadToBlob($uploadDir . $result['filename'], $result['filename']);
        if ($blobUrl) {
            unlink($uploadDir . $result['filename']);
            $track->setFilename($blobUrl);
        }

        $em->persist($track);
        $em->flush();

        $src = $blobUrl ? $blobUrl : '/music/uploads/' . $result['filename'];

        return new JsonResponse([
            'success' => true,
            'track' => [
                'id' => $track->getId(),
                'title' => $track->getTitle(),
                'artist' => $track->getArtist() ?? 'Artiste inconnu',
                'album' => $track->getAlbum() ?? '',
                'duration' => $track->getDuration(),
                'genre' => $track->getGenre() ?? '',
                'src' => $src,
                'cover' => null,
            ]
        ]);
    }

    #[Route('/install', name: 'app_install', methods: ['GET'])]
    public function install(EntityManagerInterface $em): Response
    {
        try {
            $schemaTool = new \Doctrine\ORM\Tools\SchemaTool($em);
            $metadata = $em->getMetadataFactory()->getAllMetadata();
            // Drop then create
            $schemaTool->dropSchema($metadata);
            $schemaTool->createSchema($metadata);
            return new Response('Base de données initialisée avec succès ! Tu peux retourner à la page d\'accueil.');
        } catch (\Throwable $e) {
            return new Response('Erreur : ' . $e->getMessage());
        }
    }

    private function uploadToBlob(string $filepath, string $filename): ?string
    {
        $token = $_ENV['BLOB_READ_WRITE_TOKEN'] ?? $_SERVER['BLOB_READ_WRITE_TOKEN'] ?? null;
        if (!$token) {
            return null; // Pas de Vercel Blob configuré, on retournera null pour utiliser le stockage local
        }

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, 'https://blob.vercel-storage.com/' . rawurlencode($filename));
        curl_setopt($ch, CURLOPT_PUT, true);
        
        $fileSize = filesize($filepath);
        $fp = fopen($filepath, 'r');
        curl_setopt($ch, CURLOPT_INFILE, $fp);
        curl_setopt($ch, CURLOPT_INFILESIZE, $fileSize);

        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . $token,
            'x-api-version: 7' // Version API Vercel Blob
        ]);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        
        $response = curl_exec($ch);
        curl_close($ch);
        fclose($fp);

        if ($response) {
            $data = json_decode($response, true);
            if (isset($data['url'])) {
                return $data['url'];
            }
        }
        return null;
    }

    // ─── Helpers ───────────────────────────────────────────────────────────────

    /**
     * Crée une entité Track à partir d'un fichier MP3 + métadonnées
     */
    private function createTrackFromFile(
        string $filename,
        EntityManagerInterface $em,
        array $meta = [],
        ?string $coverPath = null
    ): Track {
        $uploadDir = $this->getParameter('kernel.project_dir') . '/public/music/uploads/';
        $filepath  = $uploadDir . $filename;

        $title    = $meta['title']    ?? pathinfo($filename, PATHINFO_FILENAME);
        $artist   = $meta['artist']   ?? null;
        $album    = $meta['album']    ?? null;
        $duration = $meta['duration'] ?? null;

        // Essayer d'extraire depuis les tags ID3 si pas de meta
        try {
            $getID3   = new \getID3();
            $fileInfo = $getID3->analyze($filepath);
            \getid3_lib::CopyTagsToComments($fileInfo);

            if (empty($meta['title']) && !empty($fileInfo['comments']['title'][0])) {
                $title = $fileInfo['comments']['title'][0];
            }
            if (empty($meta['artist']) && !empty($fileInfo['comments']['artist'][0])) {
                $artist = $fileInfo['comments']['artist'][0];
            }
            if (empty($meta['album']) && !empty($fileInfo['comments']['album'][0])) {
                $album = $fileInfo['comments']['album'][0];
            }
            if (!$duration && !empty($fileInfo['playtime_seconds'])) {
                $duration = (int) $fileInfo['playtime_seconds'];
            }
            // Extraire cover embarquée (spotdl l'embarque dans le MP3)
            if (!$coverPath && !empty($fileInfo['comments']['picture'][0]['data'])) {
                $coverFilename = pathinfo($filename, PATHINFO_FILENAME) . '_cover.jpg';
                file_put_contents($uploadDir . $coverFilename, $fileInfo['comments']['picture'][0]['data']);
                $coverPath = 'music/uploads/' . $coverFilename;
            }
        } catch (\Exception $e) {}

        // Upload audio and cover to Vercel Blob if available
        $blobAudioUrl = $this->uploadToBlob($filepath, $filename);
        if ($blobAudioUrl) {
            unlink($filepath); // Cleanup local file
            $filename = $blobAudioUrl; // Use URL as filename
            
            if ($coverPath) {
                // coverPath might be "music/uploads/dz_..._cover.jpg"
                $coverLocalPath = $this->getParameter('kernel.project_dir') . '/public/' . $coverPath;
                if (file_exists($coverLocalPath)) {
                    $blobCoverUrl = $this->uploadToBlob($coverLocalPath, basename($coverPath));
                    if ($blobCoverUrl) {
                        unlink($coverLocalPath);
                        $coverPath = $blobCoverUrl;
                    }
                }
            }
        }

        $track = new Track();
        $track->setTitle($title)
              ->setArtist($artist)
              ->setAlbum($album)
              ->setDuration($duration)
              ->setFilename($filename)
              ->setCoverPath($coverPath);

        $em->persist($track);
        return $track;
    }

    private function trackToArray(Track $track): array
    {
        $filename = $track->getFilename();
        // If filename starts with http, it's a blob url. Otherwise, it's a local file.
        $src = str_starts_with($filename, 'http') ? $filename : '/music/uploads/' . $filename;
        
        $cover = $track->getCoverPath();
        if ($cover) {
            $coverUrl = str_starts_with($cover, 'http') ? $cover : '/' . ltrim($cover, '/');
        } else {
            $coverUrl = null;
        }

        return [
            'id'       => $track->getId(),
            'title'    => $track->getTitle(),
            'artist'   => $track->getArtist() ?? 'Artiste inconnu',
            'album'    => $track->getAlbum() ?? '',
            'duration' => $track->getDuration(),
            'src'      => $src,
            'cover'    => $coverUrl,
        ];
    }

    // ─── Spotify ───────────────────────────────────────────────────────────────

    #[Route('/spotify/download', name: 'app_spotify_download', methods: ['POST'])]
    public function spotifyDownload(
        Request $request,
        SpotifyDownloader $downloader,
        EntityManagerInterface $em
    ): JsonResponse {
        try {
            $data = json_decode($request->getContent(), true);
            $url  = $data['url'] ?? null;

            if (!$url) return new JsonResponse(['error' => 'URL manquante'], 400);

            $type = $downloader->getUrlType($url);

            if ($type === 'playlist' || $type === 'album') {
                $result = $downloader->downloadPlaylist($url);
                if (!$result['success']) return new JsonResponse(['error' => $result['error']], 400);

                $tracks = [];
                foreach ($result['filenames'] as $filename) {
                    $track = $this->createTrackFromFile($filename, $em);
                    $tracks[] = $this->trackToArray($track);
                }
                $em->flush();
                return new JsonResponse(['success' => true, 'tracks' => $tracks, 'count' => count($tracks)]);
            }

            $result = $downloader->download($url);
            if (!$result['success']) return new JsonResponse(['error' => $result['error']], 400);

            $track = $this->createTrackFromFile($result['filename'], $em);
            $em->flush();
            return new JsonResponse(['success' => true, 'track' => $this->trackToArray($track)]);

        } catch (\Throwable $e) {
            return new JsonResponse(['error' => $e->getMessage()], 500);
        }
    }

    // ─── Deezer ───────────────────────────────────────────────────────────────

    #[Route('/deezer/download', name: 'app_deezer_download', methods: ['POST'])]
    public function deezerDownload(
        Request $request,
        DeezerDownloader $downloader,
        EntityManagerInterface $em
    ): JsonResponse {
        // Désactiver le timeout PHP pour les playlists longues
        set_time_limit(0);
        ini_set('max_execution_time', '0');

        try {
            $data = json_decode($request->getContent(), true);
            $url  = $data['url'] ?? null;

            if (!$url) return new JsonResponse(['error' => 'URL manquante'], 400);
            if (!$downloader->isValidDeezerUrl($url)) return new JsonResponse(['error' => 'URL Deezer invalide'], 400);

            $parsed = $downloader->parseUrl($url);

            if ($parsed['type'] === 'playlist' || $parsed['type'] === 'album') {
                $trackInfoList = $downloader->getPlaylistTracks($parsed['id']);
                if (empty($trackInfoList)) return new JsonResponse(['error' => 'Playlist vide ou introuvable'], 400);

                $tracks = [];
                foreach ($trackInfoList as $trackInfo) {
                    // Utiliser downloadTrackSync() pour les playlists :
                    // retourne 'filename' directement (bloquant, pas de job_id)
                    $result = $downloader->downloadTrackSync($trackInfo);
                    if (!$result['success']) continue;
                    $track = $this->createTrackFromFile(
                        $result['filename'], $em,
                        $result['metadata'],
                        $result['coverPath'] ?? null
                    );
                    $tracks[] = $this->trackToArray($track);
                }
                $em->flush();
                return new JsonResponse(['success' => true, 'tracks' => $tracks, 'count' => count($tracks)]);
            }

            $trackInfo = $downloader->getTrackInfo($parsed['id']);
            if (!$trackInfo) return new JsonResponse(['error' => 'Morceau introuvable sur Deezer'], 400);

            // Lancement asynchrone - retourne job_id immédiatement
            $result = $downloader->downloadTrack($trackInfo);
            if (!$result['success']) return new JsonResponse(['error' => $result['error']], 400);

            return new JsonResponse([
                'success' => true,
                'job_id'  => $result['job_id'],
                'title'   => $trackInfo['title'],
                'artist'  => $trackInfo['artist'] ?? 'Artiste inconnu',
                'cover'   => $result['coverPath'] ? '/' . $result['coverPath'] : null,
            ]);

        } catch (\Throwable $e) {
            return new JsonResponse(['error' => $e->getMessage()], 500);
        }
    }

    #[Route('/job/status/{jobId}', name: 'app_job_status', methods: ['GET'])]
    public function jobStatus(string $jobId, DeezerDownloader $downloader, EntityManagerInterface $em): JsonResponse
    {
        $result = $downloader->checkJob($jobId);

        if ($result['status'] === 'done') {
            // Vérifier si déjà en base
            $existing = $em->getRepository(\App\Entity\Track::class)->findOneBy(['filename' => $result['filename']]);
            if ($existing) {
                return new JsonResponse(['status' => 'done', 'track' => $this->trackToArray($existing)]);
            }

            // Créer le track en base
            $track = $this->createTrackFromFile(
                $result['filename'], $em,
                $result['metadata'] ?? [],
                $result['coverPath'] ?? null
            );
            $em->flush();
            return new JsonResponse(['status' => 'done', 'track' => $this->trackToArray($track)]);
        }

        return new JsonResponse($result);
    }
}
