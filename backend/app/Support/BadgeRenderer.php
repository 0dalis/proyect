<?php

namespace App\Support;

use App\Models\Company;
use App\Models\Employee;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;
use Barryvdh\DomPDF\Facade\Pdf;
use Barryvdh\DomPDF\PDF as DomPdf;

class BadgeRenderer
{
    /**
     * Credencial CR80 (tarjeta de crédito, 85.6 x 54 mm) medida en puntos.
     *
     * @var array<string, array{0: float, 1: float}>
     */
    private const SIZES = [
        'horizontal' => [242.65, 153.07],
        'vertical' => [153.07, 242.65],
    ];

    /**
     * El QR solo contiene el token de la credencial; el kiosko ya sabe de qué
     * empresa es, y regenerar el token invalida la credencial perdida.
     */
    public function qrDataUri(string $content, int $size = 300): string
    {
        $writer = new Writer(new ImageRenderer(new RendererStyle($size, 1), new SvgImageBackEnd));

        return 'data:image/svg+xml;base64,'.base64_encode($writer->writeString($content));
    }

    /** Normaliza la orientación pedida; cualquier otra cosa va horizontal. */
    public function orientation(?string $value): string
    {
        return array_key_exists($value ?? '', self::SIZES) ? (string) $value : 'horizontal';
    }

    /**
     * PDF impreso: una cara por página (frente y reverso de cada empleado).
     *
     * @param  list<Employee>  $employees
     */
    public function pdf(array $employees, Company $company, string $orientation = 'horizontal'): DomPdf
    {
        $orientation = $this->orientation($orientation);
        $logo = $this->image($company->logo_path);

        $badges = array_map(fn (Employee $employee) => [
            'employee' => $employee,
            'qr' => $this->qrDataUri($employee->badge_token ?? ''),
            'photo' => $this->image($employee->photo_path),
            'logo' => $logo,
        ], $employees);

        [$width, $height] = self::SIZES[$orientation];

        return Pdf::loadView('pdf.badges', compact('badges', 'company', 'orientation'))
            ->setPaper([0, 0, $width, $height]);
    }

    /**
     * PDF de la credencial tal como se ve en pantalla: el frente y el reverso
     * capturados en el navegador se pegan tal cual (mismo CSS que el preview).
     */
    public function capture(string $front, string $back, string $orientation = 'horizontal'): DomPdf
    {
        $orientation = $this->orientation($orientation);
        [$width, $height] = self::SIZES[$orientation];

        return Pdf::loadView('pdf.badge-capture', [
            'front' => $front,
            'back' => $back,
            'width' => $width,
            'height' => $height,
        ])->setPaper([0, 0, $width, $height]);
    }

    /** Ruta absoluta de una imagen privada si ya existe en disco. */
    private function image(?string $path): ?string
    {
        $absolute = app(StoredImage::class)->absolutePath($path);

        return $absolute && file_exists($absolute) ? $absolute : null;
    }
}
