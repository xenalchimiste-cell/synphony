<?php

namespace App\Controller;

use App\Entity\Track;
use App\Repository\TrackRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class MusicController extends AbstractController
{
    #[Route('/', name: 'app_music_index')]
    public function index(TrackRepository $trackRepository, EntityManagerInterface $entityManager): Response
    {
        // Auto-seed if database is empty
        $tracks = $trackRepository->findAll();
        if (empty($tracks)) {
            $this->seedDatabase($entityManager);
            $tracks = $trackRepository->findAll();
        }

        $featuredTrack = null;
        foreach ($tracks as $track) {
            if ($track->isFeatured()) {
                $featuredTrack = $track;
                break;
            }
        }
        if (!$featuredTrack && !empty($tracks)) {
            $featuredTrack = $tracks[0];
        }

        return $this->render('music/index.html.twig', [
            'tracks' => $tracks,
            'featuredTrack' => $featuredTrack,
        ]);
    }

    #[Route('/api/tracks', name: 'api_tracks_list', methods: ['GET'])]
    public function getTracks(TrackRepository $trackRepository): JsonResponse
    {
        $tracks = $trackRepository->findAll();
        $data = [];
        foreach ($tracks as $track) {
            $data[] = [
                'id' => $track->getId(),
                'title' => $track->getTitle(),
                'artist' => $track->getArtist(),
                'album' => $track->getAlbum(),
                'duration' => $track->getDuration(),
                'audioPath' => $track->getAudioPath(),
                'coverPath' => $track->getCoverPath(),
                'genre' => $track->getGenre(),
                'isFeatured' => $track->isFeatured(),
            ];
        }

        return new JsonResponse($data);
    }

    private function seedDatabase(EntityManagerInterface $entityManager): void
    {
        $track1 = new Track();
        $track1->setTitle('Lofi Chill')
            ->setArtist('Cozy Bedroom')
            ->setAlbum('Dusk Beats')
            ->setDuration(372)
            ->setAudioPath('/music/lofi_chill.mp3')
            ->setCoverPath('/images/covers/lofi_dusk.png')
            ->setGenre('Lofi')
            ->setIsFeatured(true);

        $track2 = new Track();
        $track2->setTitle('Synthwave Dreams')
            ->setArtist('Horizon Drive')
            ->setAlbum('Retro Future')
            ->setDuration(450)
            ->setAudioPath('/music/synthwave_dreams.mp3')
            ->setCoverPath('/images/covers/synthwave_horizon.png')
            ->setGenre('Synthwave')
            ->setIsFeatured(false);

        $track3 = new Track();
        $track3->setTitle('Cyberpunk Hustle')
            ->setArtist('Echo Locus')
            ->setAlbum('Synthetic Dreams')
            ->setDuration(512)
            ->setAudioPath('/music/cyberpunk_hustle.mp3')
            ->setCoverPath('/images/covers/cyberpunk_terminal.png')
            ->setGenre('Cyberpunk')
            ->setIsFeatured(false);

        $entityManager->persist($track1);
        $entityManager->persist($track2);
        $entityManager->persist($track3);
        $entityManager->flush();
    }
}
