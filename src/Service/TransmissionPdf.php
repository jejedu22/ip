<?php

namespace App\Service;

use Dompdf\Dompdf;
use Dompdf\Options;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Twig\Environment;

/**
 * Génère, en mémoire, le PDF de la fiche (même structure que le formulaire papier de la CRIP).
 * Rien n'est écrit sur le disque hormis le cache de polices de dompdf.
 */
class TransmissionPdf
{
    public function __construct(
        private readonly Environment $twig,
        #[Autowire('%kernel.cache_dir%/dompdf')]
        private readonly string $cacheDir,
        #[Autowire('%kernel.project_dir%/resources/pdf/logo-departement.jpg')]
        private readonly string $logoPath,
    ) {
    }

    /**
     * @param array<string, mixed> $data Données déjà normalisées (anonymat appliqué)
     */
    public function render(array $data): string
    {
        $options = new Options();
        $options->set('isRemoteEnabled', false);
        $options->set('isPhpEnabled', false);
        $options->set('isFontSubsettingEnabled', true);
        $options->set('defaultFont', 'DejaVu Sans');
        $options->set('fontDir', $this->cacheDir);
        $options->set('fontCache', $this->cacheDir);
        $options->set('tempDir', $this->cacheDir);
        $options->set('chroot', [\dirname($this->logoPath)]);
        if (!is_dir($this->cacheDir)) {
            @mkdir($this->cacheDir, 0o775, true);
        }

        $logo = is_file($this->logoPath) ? 'data:image/jpeg;base64,'.base64_encode((string) file_get_contents($this->logoPath)) : null;
        $html = $this->twig->render('pdf/fiche.html.twig', ['d' => $data, 'logo' => $logo]);

        $dompdf = new Dompdf($options);
        $dompdf->setPaper('A4');
        $dompdf->loadHtml($html, 'UTF-8');
        $dompdf->render();

        // Pagination « Page x / y » comme sur le modèle.
        $canvas = $dompdf->getCanvas();
        $font = $dompdf->getFontMetrics()->getFont('DejaVu Sans');
        $canvas->page_text(500, 815, 'Page {PAGE_NUM} / {PAGE_COUNT}', $font, 8, [0.2, 0.2, 0.2]);
        $dompdf->addInfo('Title', 'Fiche de transmission d’une information préoccupante');

        return (string) $dompdf->output();
    }
}
