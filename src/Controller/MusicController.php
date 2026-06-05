<?php

namespace App\Controller;

use App\Entity\Track;
use App\Repository\TrackRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class MusicController extends AbstractController
{
    #[Route('/', name: 'app_music_index')]
    public function index(TrackRepository $trackRepository, EntityManagerInterface $em): Response
    {
        $tracks = $trackRepository->findBy([], ['uploadedAt' => 'DESC']);

        if (empty($tracks)) {
            $preloaded = [
                [
                    'source' => 'lofi_chill.mp3',
                    'title' => 'Lofi Chill',
                    'artist' => 'Lofi Dreamer',
                    'album' => 'Chill Study Beats',
                    'duration' => 223,
                    'genre' => 'Lofi'
                ],
                [
                    'source' => 'synthwave_dreams.mp3',
                    'title' => 'Synthwave Dreams',
                    'artist' => 'Retro Wave',
                    'album' => 'Neon Horizon',
                    'duration' => 255,
                    'genre' => 'Synthwave'
                ],
                [
                    'source' => 'cyberpunk_hustle.mp3',
                    'title' => 'Cyberpunk Hustle',
                    'artist' => 'Neon Samurai',
                    'album' => 'Grid City',
                    'duration' => 206,
                    'genre' => 'Cyberpunk'
                ]
            ];

            $publicDir = $this->getParameter('kernel.project_dir') . '/public/music/';
            $uploadDir = $publicDir . 'uploads/';

            foreach ($preloaded as $p) {
                $srcPath = $publicDir . $p['source'];
                if (file_exists($srcPath)) {
                    $uniqueFilename = pathinfo($p['source'], PATHINFO_FILENAME) . '_' . uniqid() . '.mp3';
                    copy($srcPath, $uploadDir . $uniqueFilename);

                    $track = new Track();
                    $track->setTitle($p['title'])
                          ->setArtist($p['artist'])
                          ->setAlbum($p['album'])
                          ->setDuration($p['duration'])
                          ->setFilename($uniqueFilename)
                          ->setGenre($p['genre']);

                    $em->persist($track);
                }
            }
            $em->flush();

            // Refresh tracks
            $tracks = $trackRepository->findBy([], ['uploadedAt' => 'DESC']);
        }

        return $this->render('music/index.html.twig', [
            'tracks' => $tracks,
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

            $em->persist($track);
            $em->flush();

            return new JsonResponse([
                'id' => $track->getId(),
                'title' => $track->getTitle(),
                'artist' => $track->getArtist() ?? 'Artiste inconnu',
                'album' => $track->getAlbum() ?? '',
                'duration' => $track->getDuration(),
                'genre' => $track->getGenre() ?? '',
                'src' => '/music/uploads/' . $uniqueFilename,
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
            $data[] = [
                'id' => $t->getId(),
                'title' => $t->getTitle(),
                'artist' => $t->getArtist() ?? 'Artiste inconnu',
                'album' => $t->getAlbum() ?? '',
                'duration' => $t->getDuration(),
                'genre' => $t->getGenre() ?? '',
                'src' => '/music/uploads/' . $t->getFilename(),
            ];
        }

        return new JsonResponse($data);
    }
}
