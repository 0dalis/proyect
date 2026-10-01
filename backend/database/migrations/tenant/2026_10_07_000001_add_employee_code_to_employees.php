<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

/**
 * Código visible del empleado: las 4 primeras letras de la empresa más una
 * secuencia (ALDE-0001). El employee_number interno sigue existiendo porque es
 * lo que teclea quien checa con PIN en el kiosko; el código es lo que se
 * muestra en el panel y se imprime en la credencial.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->string('employee_code', 20)->nullable()->after('employee_number');
            $table->unique(['company_id', 'employee_code']);
        });

        $this->assignCodesToExistingEmployees();
    }

    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->dropUnique(['company_id', 'employee_code']);
            $table->dropColumn('employee_code');
        });
    }

    /**
     * A los empleados que ya existen (incluidos los dados de baja) les pone su
     * código en orden de alta. Las bases pools guardan a varias empresas, así
     * que se recorre una por una; el nombre viene de la base central.
     */
    private function assignCodesToExistingEmployees(): void
    {
        $companyIds = DB::table('employees')->whereNull('employee_code')->distinct()->pluck('company_id');

        foreach ($companyIds as $companyId) {
            $prefix = $this->prefixFor((int) $companyId);
            $taken = DB::table('employees')->where('company_id', $companyId)
                ->where('employee_code', 'like', $prefix.'-%')->pluck('employee_code');

            $next = 1 + (int) $taken
                ->map(fn (string $code): int => (int) mb_substr($code, mb_strlen($prefix) + 1))
                ->max();

            foreach (DB::table('employees')->where('company_id', $companyId)->orderBy('id')->pluck('id') as $id) {
                do {
                    $code = sprintf('%s-%04d', $prefix, $next++);
                } while ($taken->contains($code));

                DB::table('employees')->where('id', $id)->update(['employee_code' => $code]);
                $taken->push($code);
            }
        }
    }

    /**
     * Mismo cálculo que App\Support\EmployeeCode, congelado aquí para que la
     * migración no dependa de clases que puedan cambiar después.
     */
    private function prefixFor(int $companyId): string
    {
        $name = (string) DB::connection('central')->table('companies')->where('id', $companyId)->value('name');
        $words = preg_split('/[^A-Za-z]+/', Str::ascii($name), -1, PREG_SPLIT_NO_EMPTY) ?: [];
        $letters = '';

        foreach ($words as $word) {
            $letters .= mb_substr($word, 0, 2);

            if (mb_strlen($letters) >= 4) {
                break;
            }
        }

        if (count($words) === 1) {
            $letters = mb_substr($words[0], 0, 4);
        }

        if ($letters === '') {
            $letters = 'EMPR';
        }

        return str_pad(strtoupper($letters), 4, 'X');
    }
};
