import { Pipe, PipeTransform } from '@angular/core';

export type TzDateFormat = 'time' | 'date' | 'datetime' | 'short';

const OPTIONS: Record<TzDateFormat, Intl.DateTimeFormatOptions> = {
  time: { hour: '2-digit', minute: '2-digit', hour12: false },
  date: { day: 'numeric', month: 'short', year: 'numeric' },
  datetime: { day: 'numeric', month: 'short', hour: '2-digit', minute: '2-digit', hour12: false },
  short: { day: 'numeric', month: 'short' },
};

const formatters = new Map<string, Intl.DateTimeFormat>();

/**
 * Muestra una fecha guardada en UTC con la hora de la oficina donde ocurrió:
 * una checada a las 9:00 en Cancún se ve "09:00" aunque quien la revise esté
 * en CDMX. (DatePipe de Angular no acepta zonas como "America/Cancun").
 *
 *   {{ record.recorded_at | tzDate: 'time' : record.office?.timezone }}
 */
@Pipe({ name: 'tzDate' })
export class TzDatePipe implements PipeTransform {
  transform(
    value: string | Date | null | undefined,
    format: TzDateFormat = 'datetime',
    timeZone?: string | null,
  ): string {
    if (!value) {
      return '';
    }
    const date = value instanceof Date ? value : new Date(value);
    if (Number.isNaN(date.getTime())) {
      return '';
    }
    return formatter(format, timeZone ?? undefined).format(date);
  }
}

function formatter(format: TzDateFormat, timeZone?: string): Intl.DateTimeFormat {
  const key = `${format}|${timeZone ?? ''}`;
  let cached = formatters.get(key);
  if (!cached) {
    try {
      cached = new Intl.DateTimeFormat('es-MX', { ...OPTIONS[format], timeZone });
    } catch {
      // Zona desconocida: se muestra con la del navegador
      cached = new Intl.DateTimeFormat('es-MX', OPTIONS[format]);
    }
    formatters.set(key, cached);
  }
  return cached;
}

/** Zonas horarias de México (y la opción de elegir cualquier otra). */
export const MEXICO_TIMEZONES: { value: string; label: string }[] = [
  { value: 'America/Mexico_City', label: 'Centro (CDMX, Guadalajara, Monterrey, Yucatán) · UTC−6' },
  { value: 'America/Merida', label: 'Mérida (Yucatán y Campeche) · UTC−6' },
  { value: 'America/Cancun', label: 'Quintana Roo (Cancún, Chetumal) · UTC−5' },
  { value: 'America/Monterrey', label: 'Monterrey · UTC−6' },
  { value: 'America/Mazatlan', label: 'Pacífico (Sinaloa, Nayarit, BCS) · UTC−7' },
  { value: 'America/Hermosillo', label: 'Sonora · UTC−7' },
  { value: 'America/Chihuahua', label: 'Chihuahua · UTC−6' },
  { value: 'America/Ciudad_Juarez', label: 'Ciudad Juárez · UTC−7 / horario de verano' },
  { value: 'America/Matamoros', label: 'Frontera norte (Matamoros, Nuevo Laredo) · UTC−6 / horario de verano' },
  { value: 'America/Tijuana', label: 'Baja California (Tijuana, Mexicali) · UTC−8 / horario de verano' },
];
