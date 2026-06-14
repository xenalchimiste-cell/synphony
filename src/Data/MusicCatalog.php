<?php

namespace App\Data;

final class MusicCatalog
{
    public static function getTracks(): array
    {
        $catalog = [
            'Kaaris' => [
                ['album' => 'Or Noir', 'title' => 'Or Noir', 'duration' => 247, 'color' => '#c9a227', 'color2' => '#111111'],
                ['album' => 'Or Noir', 'title' => 'Charge', 'duration' => 198, 'color' => '#c9a227', 'color2' => '#111111'],
                ['album' => 'Or Noir Part II', 'title' => 'B.O.U.D.O.U', 'duration' => 215, 'color' => '#8b7500', 'color2' => '#0a0a0a'],
                ['album' => 'Le bruit de mon âme', 'title' => '63 Empire', 'duration' => 203, 'color' => '#d4af37', 'color2' => '#1c1c1c'],
                ['album' => '2.7 Zero', 'title' => 'Zéro', 'duration' => 189, 'color' => '#b8860b', 'color2' => '#000000'],
            ],
            'SCH' => [
                ['album' => 'JVLIVS II', 'title' => '911', 'duration' => 231, 'color' => '#8b0000', 'color2' => '#0d0d0d'],
                ['album' => 'JVLIVS II', 'title' => 'Mona Lisa', 'duration' => 198, 'color' => '#8b0000', 'color2' => '#0d0d0d'],
                ['album' => 'Rooftop', 'title' => 'Pub Gabon', 'duration' => 176, 'color' => '#cc0000', 'color2' => '#1a1a1a'],
                ['album' => 'Anarchie', 'title' => 'Gomorra', 'duration' => 224, 'color' => '#990000', 'color2' => '#0f0f0f'],
                ['album' => 'JVLIVS', 'title' => 'Génération SCH', 'duration' => 205, 'color' => '#b30000', 'color2' => '#141414'],
            ],
            'PNL' => [
                ['album' => 'Deux frères', 'title' => 'Au DD', 'duration' => 261, 'color' => '#4a90d9', 'color2' => '#1e3a5f'],
                ['album' => 'Deux frères', 'title' => 'Bené', 'duration' => 243, 'color' => '#4a90d9', 'color2' => '#1e3a5f'],
                ['album' => 'Dans la légende', 'title' => 'Gauchiste', 'duration' => 218, 'color' => '#5ba3e8', 'color2' => '#152a45'],
                ['album' => 'Le monde Chico', 'title' => 'Le monde ou rien', 'duration' => 234, 'color' => '#3d7abf', 'color2' => '#0f2236'],
                ['album' => 'Que la famille', 'title' => 'J\'suis PNL', 'duration' => 207, 'color' => '#6bb5ff', 'color2' => '#1a3050'],
            ],
            'PLK' => [
                ['album' => 'Polakt Arena', 'title' => 'Wola', 'duration' => 192, 'color' => '#9b59b6', 'color2' => '#2d1b69'],
                ['album' => 'Polakt Arena', 'title' => 'Oulala', 'duration' => 185, 'color' => '#9b59b6', 'color2' => '#2d1b69'],
                ['album' => 'Enfant du soleil', 'title' => 'Tout seul', 'duration' => 201, 'color' => '#b07cc6', 'color2' => '#3a2060'],
                ['album' => 'Bifurcation', 'title' => 'Demain', 'duration' => 178, 'color' => '#8e44ad', 'color2' => '#251550'],
            ],
            'Ninho' => [
                ['album' => 'M.I.L.S 3.0', 'title' => 'Goutte d\'eau', 'duration' => 196, 'color' => '#e74c3c', 'color2' => '#2c0a0a'],
                ['album' => 'M.I.L.S 3.0', 'title' => 'La vie qu\'on mène', 'duration' => 212, 'color' => '#e74c3c', 'color2' => '#2c0a0a'],
                ['album' => 'Destin', 'title' => 'Maman ne le sait pas', 'duration' => 188, 'color' => '#c0392b', 'color2' => '#1a0808'],
                ['album' => 'Comme prévu', 'title' => 'Roro', 'duration' => 205, 'color' => '#ff6b5b', 'color2' => '#330f0f'],
            ],
            'Damso' => [
                ['album' => 'QALF', 'title' => 'Σ. Morceau', 'duration' => 228, 'color' => '#ecf0f1', 'color2' => '#2c3e50'],
                ['album' => 'Ipséité', 'title' => 'Feu de bois', 'duration' => 194, 'color' => '#bdc3c7', 'color2' => '#1a252f'],
                ['album' => 'Lithopédie', 'title' => 'Macarena', 'duration' => 201, 'color' => '#95a5a6', 'color2' => '#243342'],
                ['album' => 'Batterie faible', 'title' => 'N. J Respect R', 'duration' => 187, 'color' => '#d5dbdb', 'color2' => '#1c2833'],
            ],
            'Booba' => [
                ['album' => 'ULTRA', 'title' => 'DKR', 'duration' => 219, 'color' => '#f39c12', 'color2' => '#1a1a1a'],
                ['album' => 'ULTRA', 'title' => 'Validé', 'duration' => 203, 'color' => '#f39c12', 'color2' => '#1a1a1a'],
                ['album' => 'Trône', 'title' => 'GG', 'duration' => 195, 'color' => '#e67e22', 'color2' => '#0f0f0f'],
                ['album' => 'Futur 2.0', 'title' => 'Kaaris', 'duration' => 241, 'color' => '#d35400', 'color2' => '#141414'],
            ],
            'Jul' => [
                ['album' => 'Album gratuit vol. 6', 'title' => 'La zone', 'duration' => 183, 'color' => '#3498db', 'color2' => '#0a1628'],
                ['album' => 'Décennie', 'title' => 'La machine', 'duration' => 197, 'color' => '#2980b9', 'color2' => '#081220'],
                ['album' => 'Rien 100 Rien', 'title' => 'Tchikita', 'duration' => 206, 'color' => '#5dade2', 'color2' => '#0d1f35'],
                ['album' => 'My World', 'title' => 'J\'oublie tout', 'duration' => 192, 'color' => '#2471a3', 'color2' => '#060e18'],
            ],
            'Gazo' => [
                ['album' => 'Drill FR', 'title' => 'DIE', 'duration' => 174, 'color' => '#1abc9c', 'color2' => '#0a1f1a'],
                ['album' => 'Drill FR', 'title' => 'Kassav', 'duration' => 168, 'color' => '#1abc9c', 'color2' => '#0a1f1a'],
                ['album' => 'KMT', 'title' => 'Dans ma rue', 'duration' => 181, 'color' => '#16a085', 'color2' => '#071510'],
                ['album' => 'Apocalypse', 'title' => 'MOLLY', 'duration' => 176, 'color' => '#48c9b0', 'color2' => '#0c2820'],
            ],
            'Tiakola' => [
                ['album' => 'Méléna', 'title' => 'Méléna', 'duration' => 189, 'color' => '#e91e63', 'color2' => '#1a0a10'],
                ['album' => 'Gasolina', 'title' => 'Gasolina', 'duration' => 195, 'color' => '#ff4081', 'color2' => '#200810'],
                ['album' => 'BHLM', 'title' => 'X', 'duration' => 178, 'color' => '#c2185b', 'color2' => '#15060c'],
            ],
            'SDM' => [
                ['album' => 'Stade de France', 'title' => 'Bolide allemand', 'duration' => 202, 'color' => '#00bcd4', 'color2' => '#0a1a1f'],
                ['album' => 'Stade de France', 'title' => 'Stade de France', 'duration' => 218, 'color' => '#00bcd4', 'color2' => '#0a1a1f'],
                ['album' => 'OZONE', 'title' => 'Slide', 'duration' => 186, 'color' => '#0097a7', 'color2' => '#061215'],
            ],
            'Laylow' => [
                ['album' => 'Trinity', 'title' => 'Brûle', 'duration' => 207, 'color' => '#ff5722', 'color2' => '#1a0a05'],
                ['album' => 'Trinity', 'title' => 'TrinityVille', 'duration' => 194, 'color' => '#ff5722', 'color2' => '#1a0a05'],
                ['album' => 'L\'Étrange Histoire', 'title' => 'Vampires', 'duration' => 211, 'color' => '#e64a19', 'color2' => '#120804'],
            ],
            'Hamza' => [
                ['album' => 'Paradise Lost', 'title' => 'Drôle d\'ange', 'duration' => 198, 'color' => '#673ab7', 'color2' => '#120a20'],
                ['album' => 'Sincèrement', 'title' => 'Life', 'duration' => 185, 'color' => '#7e57c2', 'color2' => '#1a1028'],
                ['album' => 'LQD', 'title' => 'Cocaïne', 'duration' => 192, 'color' => '#5e35b1', 'color2' => '#0e0818'],
            ],
            'Niska' => [
                ['album' => 'Zifukoro', 'title' => 'Réseaux', 'duration' => 204, 'color' => '#795548', 'color2' => '#1a1008'],
                ['album' => 'Commando', 'title' => 'Médicament', 'duration' => 191, 'color' => '#8d6e63', 'color2' => '#201510'],
                ['album' => 'Le monde est méchant', 'title' => 'Sal', 'duration' => 187, 'color' => '#6d4c41', 'color2' => '#150c06'],
            ],
            'Fresh La Douille' => [
                ['album' => 'Flashback', 'title' => 'Flashback', 'duration' => 196, 'color' => '#00e676', 'color2' => '#0a1f14'],
                ['album' => 'Flashback', 'title' => 'Médicament', 'duration' => 182, 'color' => '#00e676', 'color2' => '#0a1f14'],
                ['album' => 'Zone', 'title' => 'Dans la zone', 'duration' => 178, 'color' => '#00c853', 'color2' => '#081810'],
                ['album' => 'Rap Game', 'title' => 'Douille', 'duration' => 190, 'color' => '#69f0ae', 'color2' => '#0c2018'],
            ],
            'Le Crime' => [
                ['album' => 'Pas de crime', 'title' => 'Pas de crime', 'duration' => 201, 'color' => '#ff1744', 'color2' => '#1a0508'],
                ['album' => 'Pas de crime', 'title' => 'Balaclava', 'duration' => 188, 'color' => '#ff1744', 'color2' => '#1a0508'],
                ['album' => 'Nuit noire', 'title' => 'Nuit noire', 'duration' => 195, 'color' => '#d50000', 'color2' => '#120304'],
                ['album' => 'Rap Elite', 'title' => 'Crime pays', 'duration' => 184, 'color' => '#ff5252', 'color2' => '#200608'],
            ],
            'RnBoi' => [
                ['album' => 'RnB Wave', 'title' => 'Feelings', 'duration' => 193, 'color' => '#7c4dff', 'color2' => '#150a28'],
                ['album' => 'RnB Wave', 'title' => 'Late Night', 'duration' => 186, 'color' => '#7c4dff', 'color2' => '#150a28'],
                ['album' => 'Melrose', 'title' => 'Melrose', 'duration' => 199, 'color' => '#651fff', 'color2' => '#100820'],
                ['album' => 'Vibes', 'title' => 'Toxic', 'duration' => 181, 'color' => '#b388ff', 'color2' => '#1a0c30'],
            ],
            'Vald' => [
                ['album' => 'Valfrouge', 'title' => 'Désaccordé', 'duration' => 208, 'color' => '#cddc39', 'color2' => '#1a1f08'],
                ['album' => 'Horizon vertical', 'title' => 'Eurotrap', 'duration' => 195, 'color' => '#afb42b', 'color2' => '#141806'],
                ['album' => 'XEU', 'title' => 'Vigile', 'duration' => 187, 'color' => '#dce775', 'color2' => '#1e220a'],
            ],
            'Nekfeu' => [
                ['album' => 'Feu', 'title' => 'On verra', 'duration' => 214, 'color' => '#607d8b', 'color2' => '#101820'],
                ['album' => 'Feu', 'title' => 'Elle en avait envie', 'duration' => 201, 'color' => '#607d8b', 'color2' => '#101820'],
                ['album' => 'Les Étoiles vagabondes', 'title' => 'Galatée', 'duration' => 196, 'color' => '#78909c', 'color2' => '#0c1218'],
            ],
            'Lomepal' => [
                ['album' => 'Flipper', 'title' => 'Trop beau', 'duration' => 207, 'color' => '#ff9800', 'color2' => '#1a1005'],
                ['album' => 'Majestic', 'title' => 'Yeux couleur de ton âme', 'duration' => 198, 'color' => '#f57c00', 'color2' => '#150c03'],
                ['album' => 'Palacio', 'title' => 'Palacio', 'duration' => 192, 'color' => '#ffb74d', 'color2' => '#201408'],
            ],
            'Josman' => [
                ['album' => 'J+$', 'title' => 'Avant', 'duration' => 189, 'color' => '#26a69a', 'color2' => '#0a1816'],
                ['album' => 'J+$', 'title' => 'Moshpit', 'duration' => 196, 'color' => '#26a69a', 'color2' => '#0a1816'],
                ['album' => '0003', 'title' => '0003', 'duration' => 183, 'color' => '#00897b', 'color2' => '#061210'],
            ],
            'Maes' => [
                ['album' => 'Les Derniers Salopards', 'title' => 'Galère', 'duration' => 194, 'color' => '#3f51b5', 'color2' => '#0a0e28'],
                ['album' => 'Les Derniers Salopards', 'title' => 'Mama', 'duration' => 201, 'color' => '#3f51b5', 'color2' => '#0a0e28'],
            ],
            'Gradur' => [
                ['album' => 'L\'homme au bob', 'title' => 'Conseil de classe', 'duration' => 198, 'color' => '#ff6f00', 'color2' => '#1a0e00'],
                ['album' => 'Zone et quartiers', 'title' => 'Gradur', 'duration' => 186, 'color' => '#ff8f00', 'color2' => '#201200'],
            ],
            'Lacrim' => [
                ['album' => 'Ripro', 'title' => 'A.W.A', 'duration' => 205, 'color' => '#424242', 'color2' => '#0a0a0a'],
                ['album' => 'Force et honneur', 'title' => 'Force et honneur', 'duration' => 192, 'color' => '#616161', 'color2' => '#121212'],
            ],
            'Heuss L\'Enfoiré' => [
                ['album' => 'La Menace', 'title' => 'Moulaga', 'duration' => 188, 'color' => '#ffeb3b', 'color2' => '#1a1805'],
                ['album' => 'La Menace', 'title' => 'L\'enfoiré', 'duration' => 195, 'color' => '#ffeb3b', 'color2' => '#1a1805'],
            ],
        ];

        $tracks = [];
        foreach ($catalog as $artist => $entries) {
            foreach ($entries as $entry) {
                $tracks[] = array_merge($entry, ['artist' => $artist]);
            }
        }

        return $tracks;
    }

    public static function getArtists(): array
    {
        $artists = [];
        foreach (self::getTracks() as $track) {
            $name = $track['artist'];
            if (!isset($artists[$name])) {
                $artists[$name] = [
                    'name' => $name,
                    'color' => $track['color'],
                    'color2' => $track['color2'],
                    'tracks' => [],
                ];
            }
            $artists[$name]['tracks'][] = $track;
        }

        return array_values($artists);
    }
}
