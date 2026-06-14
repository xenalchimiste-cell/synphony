<?php

namespace App\Command;

use App\Repository\TrackRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:cleanup:tracks',
    description: 'Supprime les entrées de la base de données pour les fichiers supprimés du disque'
)]
class CleanupTracksCommand extends Command
{
    public function __construct(
        private TrackRepository $trackRepository,
        private EntityManagerInterface $em,
        private string $projectDir
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        // Fichiers à conserver
        $filesToKeep = [
            'EXPORT_-_Bello_X_Dallas__clip_officiel__6a22c82edcb09.mp3',
            'EXPORT_-_Bello_X_Dallas__clip_officiel__6a22c9eeee90d.mp3',
            'ISS_-_FEU_VERT__Feat__TK__6a22c9bc176f2.mp3',
            'ISS_-_FEU_VERT__Feat__TK__6a22c9eee1541.mp3',
        ];

        $uploadDir = $this->projectDir . '/public/music/uploads/';

        // Récupérer tous les tracks
        $allTracks = $this->trackRepository->findAll();
        $deletedCount = 0;

        foreach ($allTracks as $track) {
            $filename = $track->getFilename();

            // Si le fichier est dans la liste à conserver, passer
            if (in_array($filename, $filesToKeep)) {
                $io->writeln("✅ Conservé: $filename");
                continue;
            }

            // Vérifier si le fichier existe sur le disque
            $filepath = $uploadDir . $filename;
            if (!file_exists($filepath)) {
                // Le fichier n'existe plus, supprimer l'entrée de la base de données
                $io->writeln("🗑️  Suppression BD: $filename (fichier manquant)");
                $this->em->remove($track);
                $deletedCount++;
            }
        }

        if ($deletedCount > 0) {
            $this->em->flush();
            $io->success("$deletedCount entrée(s) supprimée(s) de la base de données");
        } else {
            $io->info("Aucune entrée à supprimer");
        }

        return Command::SUCCESS;
    }
}
