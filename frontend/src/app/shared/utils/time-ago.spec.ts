import { timeAgo } from './time-ago';

describe('timeAgo', () => {
  const now = new Date(2026, 8, 28, 12, 0);

  it('describes recent moments', () => {
    expect(timeAgo(new Date(2026, 8, 28, 11, 59, 40), now)).toBe('hace un momento');
    expect(timeAgo(new Date(2026, 8, 28, 11, 45), now)).toBe('hace 15 min');
    expect(timeAgo(new Date(2026, 8, 28, 9, 0), now)).toBe('hace 3 h');
  });

  it('uses "ayer" and then the date', () => {
    expect(timeAgo(new Date(2026, 8, 27, 18, 0), now)).toBe('ayer');
    expect(timeAgo(new Date(2026, 8, 20, 18, 0), now)).toContain('20');
  });
});
