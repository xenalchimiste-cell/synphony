<?php

namespace App\Service;

class CoverGenerator
{
    public function generate(string $artist, string $album, string $color, string $color2, string $outputPath): string
    {
        $dir = dirname($outputPath);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $initials = $this->getInitials($artist);
        $safeAlbum = htmlspecialchars(mb_substr($album, 0, 28), ENT_XML1);
        $safeArtist = htmlspecialchars(mb_substr($artist, 0, 22), ENT_XML1);

        $svg = <<<SVG
<svg xmlns="http://www.w3.org/2000/svg" width="300" height="300" viewBox="0 0 300 300">
  <defs>
    <linearGradient id="bg" x1="0%" y1="0%" x2="100%" y2="100%">
      <stop offset="0%" style="stop-color:{$color}"/>
      <stop offset="100%" style="stop-color:{$color2}"/>
    </linearGradient>
    <filter id="shadow"><feDropShadow dx="0" dy="2" stdDeviation="4" flood-opacity="0.4"/></filter>
  </defs>
  <rect width="300" height="300" fill="url(#bg)"/>
  <circle cx="240" cy="60" r="80" fill="{$color}" opacity="0.15"/>
  <circle cx="60" cy="260" r="60" fill="{$color2}" opacity="0.25"/>
  <text x="24" y="200" font-family="Inter,Arial,sans-serif" font-size="72" font-weight="700" fill="rgba(255,255,255,0.9)" filter="url(#shadow)">{$initials}</text>
  <text x="24" y="248" font-family="Inter,Arial,sans-serif" font-size="18" font-weight="600" fill="rgba(255,255,255,0.95)">{$safeArtist}</text>
  <text x="24" y="274" font-family="Inter,Arial,sans-serif" font-size="13" fill="rgba(255,255,255,0.65)">{$safeAlbum}</text>
</svg>
SVG;

        file_put_contents($outputPath, $svg);

        return $outputPath;
    }

    private function getInitials(string $artist): string
    {
        $parts = preg_split('/\s+/', trim($artist));
        $initials = '';
        foreach ($parts as $part) {
            if ($part !== '' && $part !== "L'" && $part !== 'L\'') {
                $initials .= mb_strtoupper(mb_substr($part, 0, 1));
            }
            if (mb_strlen($initials) >= 2) {
                break;
            }
        }

        return $initials ?: mb_strtoupper(mb_substr($artist, 0, 2));
    }
}
