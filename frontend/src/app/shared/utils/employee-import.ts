/** Fila lista para enviar a Laravel (POST /employees/import). */
export interface ImportRow {
  first_name: string;
  last_name: string;
  salary: number | null;
  salary_period: 'monthly' | 'daily' | null;
}

export interface ParsedLine {
  line: number;
  row: ImportRow;
  /** Texto original del sueldo, para mostrarlo en la vista previa. */
  salaryText: string;
  errors: string[];
}

/**
 * Lee lo que el usuario pegó desde Excel (columnas separadas por tabulador) o
 * un CSV (coma o punto y coma). Columnas: nombre, apellidos y sueldo opcional:
 *
 *   Ana    López   M:9500     → mensual de $9,500
 *   Beto   Ruiz    D:450      → diario de $450
 *   Caro   Díaz               → sin sueldo
 *
 * Si la primera fila dice "nombre" se toma como encabezado y se ignora.
 */
export function parseEmployeeImport(text: string): ParsedLine[] {
  const lines = text
    .replace(/^﻿/, '')
    .split(/\r?\n/)
    .map((line, index) => ({ text: line, number: index + 1 }))
    .filter((line) => line.text.trim() !== '');

  if (lines.length && /^\s*"?nombre/i.test(lines[0].text)) {
    lines.shift();
  }

  return lines.map(({ text: raw, number }) => {
    const cells = splitCells(raw);
    const [firstName = '', lastName = '', salaryText = ''] = cells.map((cell) => cell.trim());
    const errors: string[] = [];

    if (!firstName) errors.push('Falta el nombre');
    if (!lastName) errors.push('Faltan los apellidos');
    if (firstName.length > 80 || lastName.length > 80) errors.push('Nombre o apellidos muy largos');

    const salary = parseSalary(salaryText);
    if (salary.error) errors.push(salary.error);

    return {
      line: number,
      salaryText,
      errors,
      row: {
        first_name: firstName,
        last_name: lastName,
        salary: salary.amount,
        salary_period: salary.period,
      },
    };
  });
}

/**
 * "M:9500" → mensual 9500 · "D:450" → diario 450 · "" → sin sueldo.
 * Acepta minúsculas, espacios y separador de miles ("m: 9,500.50").
 */
export function parseSalary(text: string): {
  amount: number | null;
  period: 'monthly' | 'daily' | null;
  error?: string;
} {
  const value = text.trim();
  if (!value) {
    return { amount: null, period: null };
  }

  const match = /^([mMdD])\s*:\s*\$?\s*([\d.,]+)$/.exec(value);
  if (!match) {
    return { amount: null, period: null, error: `Sueldo "${value}": usa M:9500 (mensual) o D:450 (diario)` };
  }

  const amount = Number(match[2].replace(/,/g, ''));
  if (!Number.isFinite(amount) || amount <= 0) {
    return { amount: null, period: null, error: `Sueldo "${value}" no es un monto válido` };
  }

  return { amount, period: match[1].toUpperCase() === 'M' ? 'monthly' : 'daily' };
}

/** Tabulador (Excel), punto y coma o coma; respeta comillas de CSV. */
function splitCells(line: string): string[] {
  const separator = line.includes('\t') ? '\t' : line.includes(';') ? ';' : ',';
  const cells: string[] = [];
  let current = '';
  let quoted = false;

  for (let i = 0; i < line.length; i++) {
    const char = line[i];
    if (char === '"') {
      if (quoted && line[i + 1] === '"') {
        current += '"';
        i++;
      } else {
        quoted = !quoted;
      }
    } else if (char === separator && !quoted) {
      cells.push(current);
      current = '';
    } else {
      current += char;
    }
  }
  cells.push(current);

  return cells;
}

/** Plantilla para descargar (se abre en Excel). */
export const IMPORT_TEMPLATE = '﻿Nombre,Apellidos,Sueldo\r\nAna,López Pérez,M:9500\r\nBeto,Ruiz,D:450\r\nCaro,Díaz,\r\n';
