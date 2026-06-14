<?php

namespace App\Service;

use App\Data\MusicCatalog;
use App\Entity\Track;
use App\Repository\TrackRepository;
use Doctrine\ORM\EntityManagerInterface;

class MusicSeedService
{
    private const GENRE = 'Rap FR';

    public function __construct(
        private readonly CoverGenerator $coverGenerator,
        private readonly string $projectDir,
    ) {
    }

    public function ensureCatalogSeeded(EntityManagerInterface $em, TrackRepository $repo): int
    {
        $catalog = MusicCatalog::getTracks();
        $existingCount = $repo->count(['genre' => self::GENRE]);

        if ($existingCount >= count($catalog)) {
            return 0;
        }

        $audioPool = $this->getAudioPool();
        if (empty($audioPool)) {
            return 0;
        }

        $added = 0;
        $uploadDir = $this->projectDir . '/public/music/uploads/';
        $coverDir = $this->projectDir . '/public/images/covers/rap/';

        foreach ($catalog as $i => $data) {
            if ($repo->findOneBy(['artist' => $data['artist'], 'title' => $data['title']])) {
                continue;
            }

            $sourceFile = $audioPool[$i % count($audioPool)];
            $slug = $this->slugify($data['artist'] . '_' . $data['title']);
            $uniqueFilename = $slug . '_' . uniqid() . '.mp3';

            if (!copy($sourceFile, $uploadDir . $uniqueFilename)) {
                continue;
            }

            $coverFilename = $slug . '.svg';
            $coverFullPath = $coverDir . $coverFilename;
            $this->coverGenerator->generate(
                $data['artist'],
                $data['album'],
                $data['color'],
                $data['color2'],
                $coverFullPath
            );

            $track = new Track();
            $track->setTitle($data['title'])
                ->setArtist($data['artist'])
                ->setAlbum($data['album'])
                ->setDuration($data['duration'])
                ->setFilename($uniqueFilename)
                ->setCoverPath('images/covers/rap/' . $coverFilename)
                ->setGenre(self::GENRE);

            $em->persist($track);
            $added++;
        }

        if ($added > 0) {
            $em->flush();
        }

        return $added;
    }

    /** @return string[] */
    private function getAudioPool(): array
    {
        $pool = [];
        $dirs = [
            $this->projectDir . '/public/music/',
            $this->projectDir . '/public/music/uploads/',
        ];

        foreach ($dirs as $dir) {
            if (!is_dir($dir)) {
                continue;
            }
            foreach (glob($dir . '*.mp3') as $file) {
                $pool[] = $file;
            }
        }

        return array_values(array_unique($pool));
    }

    private function slugify(string $text): string
    {
        $text = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $text) ?: $text;
        $text = preg_replace('/[^a-zA-Z0-9]+/', '_', $text) ?? $text;

        return trim(strtolower($text), '_') ?: 'track';
    }
}
