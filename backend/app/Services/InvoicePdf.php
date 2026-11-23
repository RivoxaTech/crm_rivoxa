<?php

namespace App\Services;

use Dompdf\Dompdf;
use Dompdf\Options;

/** Renders the plain-PHP templates in resources/views/pdf into PDF bytes. */
class InvoicePdf
{
    public function __construct(
        private string $fontsDir,
        private string $cacheDir,
        private string $viewsDir,
        private string $logoFile,
    ) {}

    public static function make(): self
    {
        return new self(resource_path('fonts'), storage_path('app/dompdf'), resource_path('views/pdf'), public_path('images/rivoxa-logo.png'));
    }

    public function invoice(array $data): string
    {
        return $this->render('invoice', $data);
    }

    public function voucher(array $data): string
    {
        return $this->render('voucher', $data);
    }

    private function render(string $view, array $data): string
    {
        if (! is_dir($this->cacheDir)) {
            mkdir($this->cacheDir, 0775, true);
        }

        $options = new Options;
        $options->set('fontDir', $this->cacheDir);
        $options->set('fontCache', $this->cacheDir);
        $options->set('chroot', [$this->fontsDir, dirname($this->logoFile)]);
        $options->set('isRemoteEnabled', false);
        $options->set('defaultFont', 'DM Sans');
        // dompdf scales line boxes by the font's ascent+descent; DM Sans is 1.302em, so this makes px line-heights literal
        $options->set('fontHeightRatio', 0.768);

        $dompdf = new Dompdf($options);
        $dompdf->setPaper('A4');
        $dompdf->loadHtml($this->html($view, $data));
        $dompdf->render();

        return $dompdf->output();
    }

    private function html(string $view, array $data): string
    {
        $fonts = [['Montserrat', 600, 'montserrat-600'], ['Montserrat', 700, 'montserrat-700'],
            ['DM Sans', 400, 'dm-sans-400'], ['DM Sans', 500, 'dm-sans-500'], ['DM Sans', 700, 'dm-sans-700']];
        $fontCss = '';
        foreach ($fonts as [$family, $weight, $file]) {
            $fontCss .= "@font-face{font-family:'$family';font-weight:$weight;src:url('{$this->fontsDir}/$file.ttf') format('truetype');}\n";
        }

        $data['fontCss'] = $fontCss;
        $data['logo'] = 'data:image/png;base64,'.base64_encode((string) file_get_contents($this->logoFile));
        $data['viewsDir'] = $this->viewsDir;

        return (static function (string $__file, array $__data): string {
            extract($__data, EXTR_SKIP);
            ob_start();
            include $__file;

            return (string) ob_get_clean();
        })($this->viewsDir.'/'.$view.'.php', $data);
    }
}
