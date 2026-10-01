export interface Period {
  from: string;
  to: string;
}

const iso = (date: Date) => date.toLocaleDateString('en-CA');

/**
 * Periodos típicos de nómina en México: quincena (1-15 / 16-fin) y mes.
 */
export function periodPresets(
  today = new Date(),
): { key: string; label: string; period: Period }[] {
  const y = today.getFullYear();
  const m = today.getMonth();
  const firstHalf = today.getDate() <= 15;
  const monthEnd = (year: number, month: number) => new Date(year, month + 1, 0);

  const currentFortnight: Period = firstHalf
    ? { from: iso(new Date(y, m, 1)), to: iso(new Date(y, m, 15)) }
    : { from: iso(new Date(y, m, 16)), to: iso(monthEnd(y, m)) };
  const previousFortnight: Period = firstHalf
    ? { from: iso(new Date(y, m - 1, 16)), to: iso(monthEnd(y, m - 1)) }
    : { from: iso(new Date(y, m, 1)), to: iso(new Date(y, m, 15)) };

  return [
    { key: 'fortnight', label: 'Quincena actual', period: currentFortnight },
    { key: 'previous-fortnight', label: 'Quincena anterior', period: previousFortnight },
    {
      key: 'month',
      label: 'Este mes',
      period: { from: iso(new Date(y, m, 1)), to: iso(monthEnd(y, m)) },
    },
    {
      key: 'previous-month',
      label: 'Mes anterior',
      period: { from: iso(new Date(y, m - 1, 1)), to: iso(monthEnd(y, m - 1)) },
    },
  ];
}
