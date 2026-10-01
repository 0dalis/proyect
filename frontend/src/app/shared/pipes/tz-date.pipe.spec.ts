import { TzDatePipe } from './tz-date.pipe';

describe('TzDatePipe', () => {
  const pipe = new TzDatePipe();
  // 15:00 UTC = 9:00 en CDMX (UTC−6) y 10:00 en Cancún (UTC−5)
  const utc = '2026-09-28T15:00:00.000000Z';

  it('shows the same instant in the time zone of each office', () => {
    expect(pipe.transform(utc, 'time', 'America/Mexico_City')).toBe('09:00');
    expect(pipe.transform(utc, 'time', 'America/Cancun')).toBe('10:00');
    expect(pipe.transform(utc, 'time', 'America/Tijuana')).toBe('08:00');
  });

  it('ignores empty values and falls back on unknown zones', () => {
    expect(pipe.transform(null)).toBe('');
    expect(pipe.transform('no es fecha')).toBe('');
    expect(pipe.transform(utc, 'time', 'Zona/Inventada')).toMatch(/^\d{2}:\d{2}$/);
  });
});
