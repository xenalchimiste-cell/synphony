<?php

namespace App\Command;

use App\Entity\Track;
use App\Service\YouTubeDownloader;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:youtube:download',
    description: 'Télécharge une musique depuis YouTube et l\'ajoute à la bibliothèque',
)]
class YouTubeDownloadCommand extends Command
{
    private string $projectDir;

    public function __construct(
        private readonly YouTubeDownloader $downloader,
        private readonly EntityManagerInterface $em,
        string $projectDir,
    ) {
        parent::__construct();
        $this->projectDir = $projectDir;
    }

    protected function configure(): void
    {
        $this
            ->addArgument('url', InputArgument::REQUIRED, 'URL de la vidéo YouTube')
            ->setHelp(<<<'HELP'
Cette commande permet de télécharger une musique depuis YouTube et de l'ajouter à votre bibliothèque.

Exemples d'utilisation:
  php bin/console app:youtube:download "https://www.youtube.com/watch?v=dQw4w9WgXcQ"
  php bin/console app:youtube:download "https://youtu.be/dQw4w9WgXcQ"
HELP);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $youtubeUrl = $input->getArgument('url');

        $io->title('📥 Téléchargement depuis YouTube');
        $io->text('URL: ' . $youtubeUrl);

        // Récupérer les informations de la vidéo
        $io->section('🔍 Récupération des informations...');
        $info = $this->downloader->getVideoInfo($youtubeUrl);

        if (!$info) {
            $io->error('Impossible de récupérer les informations de la vidéo. Vérifiez l\'URL.');
            return Command::FAILURE;
        }

        $io->table(
            ['Propriété', 'Valeur'],
            [
                ['Titre', $info['title']],
                ['Artiste', $info['artist']],
                ['Durée', gmdate('H:i:s', $info['duration'])],
            ]
        );

        if (!$io->confirm('Voulez-vous télécharger cette vidéo ?', true)) {
            $io->warning('Téléchargement annulé.');
            return Command::SUCCESS;
        }

        // Télécharger
        $io->section('⬇️  Téléchargement en cours...');
        $result = $this->downloader->download($youtubeUrl);

        if (!$result['success']) {
            $io->error('Erreur lors du téléchargement: ' . $result['error']);
            return Command::FAILURE;
        }

        $io->success('✅ Fichier téléchargé: ' . $result['filename']);

        // Extraire les métadonnées avec getID3
        $uploadDir = $this->projectDir . '/public/music/uploads/';
        $filepath = $uploadDir . $result['filename'];

        $title = $result['metadata']['title'] ?? 'Titre inconnu';
        $artist = $result['metadata']['artist'] ?? null;
        $album = $result['metadata']['album'] ?? null;
        $duration = $result['metadata']['duration'] ?? null;
        $genre = null;

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
            $io->warning('Impossible d\'extraire les métadonnées avec getID3: ' . $e->getMessage());
        }

        // Créer l'entité Track
        $io->section('💾 Enregistrement dans la base de données...');
        $track = new Track();
        $track->setTitle($title)
            ->setArtist($artist)
            ->setAlbum($album)
            ->setDuration($duration)
            ->setFilename($result['filename'])
            ->setGenre($genre);

        $this->em->persist($track);
        $this->em->flush();

        $io->success([
            '🎵 Musique ajoutée à la bibliothèque !',
            'ID: ' . $track->getId(),
            'Titre: ' . $track->getTitle(),
            'Artiste: ' . ($track->getArtist() ?? 'Inconnu'),
        ]);

        return Command::SUCCESS;
    }
}
