<?php

namespace Tests\Feature;

use App\Tenancy\TenantManager;
use Tests\TestCase;

/**
 * Credencial rediseñada: frente y reverso, vertical u horizontal, y el QR que
 * se previsualiza en la ficha del empleado.
 */
class CredentialTest extends TestCase
{
    private const V1_PNG = 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg==';

    public function test_employee_detail_returns_the_credential_qr(): void
    {
        $company = $this->createCompany('plus', ['employees_can_use_web' => true]);
        app(TenantManager::class)->connect($company);
        $employee = $this->createEmployee($company);

        $response = $this->as($this->ownerOf($company))->getJson("/api/employees/{$employee->getRouteKey()}");

        $response->assertOk();
        $this->assertStringStartsWith('data:image/svg+xml;base64,', (string) $response->json('badge_qr'));
        $response->assertJsonPath('employee_code', $employee->employee_code);
    }

    public function test_badge_pdf_is_horizontal_by_default(): void
    {
        $company = $this->createCompany('plus', ['employees_can_use_web' => true]);
        app(TenantManager::class)->connect($company);
        $employee = $this->createEmployee($company);

        $response = $this->as($this->ownerOf($company))
            ->get("/api/employees/{$employee->getRouteKey()}/badge.pdf");

        $response->assertOk()->assertHeader('content-type', 'application/pdf');
        $this->assertPageMatches($response->getContent(), 242.65, 153.07);
    }

    public function test_badge_pdf_can_be_vertical(): void
    {
        $company = $this->createCompany('plus', ['employees_can_use_web' => true]);
        app(TenantManager::class)->connect($company);
        $employee = $this->createEmployee($company);

        $response = $this->as($this->ownerOf($company))
            ->get("/api/employees/{$employee->getRouteKey()}/badge.pdf?orientation=vertical");

        $response->assertOk()->assertHeader('content-type', 'application/pdf');
        $this->assertPageMatches($response->getContent(), 153.07, 242.65);
    }

    public function test_bulk_badges_accept_orientation(): void
    {
        $company = $this->createCompany('plus', ['employees_can_use_web' => true]);
        app(TenantManager::class)->connect($company);
        $employee = $this->createEmployee($company);

        $response = $this->as($this->ownerOf($company))->postJson('/api/badges.pdf', [
            'employee_ids' => [$employee->id],
            'orientation' => 'vertical',
        ]);

        $response->assertOk()->assertHeader('content-type', 'application/pdf');
        $this->assertPageMatches($response->getContent(), 153.07, 242.65);
    }

    public function test_credential_is_built_from_the_screen_captures(): void
    {
        $company = $this->createCompany('plus', ['employees_can_use_web' => true]);
        app(TenantManager::class)->connect($company);
        $employee = $this->createEmployee($company);

        $response = $this->as($this->ownerOf($company))->post("/api/employees/{$employee->getRouteKey()}/badge.pdf", [
            'front' => self::V1_PNG,
            'back' => self::V1_PNG,
            'orientation' => 'vertical',
        ]);

        $response->assertOk()->assertHeader('content-type', 'application/pdf');
        $this->assertStringContainsString('credencial-', (string) $response->headers->get('content-disposition'));
        $this->assertPageMatches($response->getContent(), 153.07, 242.65);
    }

    /** El MediaBox del PDF confirma la orientación; el conteo, frente y reverso. */
    private function assertPageMatches(string $pdf, float $width, float $height, int $pages = 2): void
    {
        $this->assertStringStartsWith('%PDF', $pdf);

        $found = preg_match('/\/MediaBox\s*\[\s*[\d.]+\s+[\d.]+\s+([\d.]+)\s+([\d.]+)\s*\]/', $pdf, $matches);

        $this->assertSame(1, $found, 'El PDF no expone su tamaño de página.');
        $this->assertEqualsWithDelta($width, (float) $matches[1], 1.0, 'Ancho de página distinto.');
        $this->assertEqualsWithDelta($height, (float) $matches[2], 1.0, 'Alto de página distinto.');

        $this->assertSame(
            $pages,
            preg_match_all('/\/Type\s*\/Page[^s]/', $pdf),
            'La credencial debe tener una página por cara.',
        );
    }
}
