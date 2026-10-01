import { periodPresets } from './period';

describe('periodPresets', () => {
  it('uses the second fortnight after the 15th', () => {
    const presets = periodPresets(new Date(2026, 8, 25));

    expect(presets.find((p) => p.key === 'fortnight')!.period).toEqual({
      from: '2026-09-16',
      to: '2026-09-30',
    });
    expect(presets.find((p) => p.key === 'previous-fortnight')!.period).toEqual({
      from: '2026-09-01',
      to: '2026-09-15',
    });
  });

  it('goes back to the previous month in the first fortnight', () => {
    const presets = periodPresets(new Date(2026, 2, 3));

    expect(presets.find((p) => p.key === 'previous-fortnight')!.period).toEqual({
      from: '2026-02-16',
      to: '2026-02-28',
    });
    expect(presets.find((p) => p.key === 'previous-month')!.period).toEqual({
      from: '2026-02-01',
      to: '2026-02-28',
    });
  });
});
