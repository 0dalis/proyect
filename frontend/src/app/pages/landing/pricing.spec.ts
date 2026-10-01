import { Plan } from '../../core/models';
import { quote, yearlyTotal } from './pricing';

const basic: Plan = {
  name: 'Básico',
  slug: 'basico',
  description: null,
  monthly_price: '499.00',
  included_employees: 25,
  included_offices: 1,
  employee_block_size: 10,
  employee_block_price: '150.00',
  extra_office_price: '199.00',
  features: [],
  includes_payroll: false,
  is_free: false,
  yearly_price: 5489,
  database_tier: 'basic',
  database_label: 'Compartida',
  trial_days: 14,
};

describe('quote', () => {
  it('charges only the base price when the company fits in the plan', () => {
    expect(quote(basic, 25, 1).total).toBe(499);
  });

  it('adds employee blocks and offices on top of the plan (app users are free)', () => {
    const result = quote(basic, 26, 2);

    expect(result.employeeBlocks).toBe(1);
    expect(result.extraOffices).toBe(1);
    expect(result.total).toBe(499 + 150 + 199);
  });

  it('marks the free plan as not fitting when extras are needed', () => {
    const free = { ...basic, monthly_price: '0.00', included_employees: 5 };

    expect(quote(free, 5, 1).fits).toBe(true);
    expect(quote(free, 6, 1).fits).toBe(false);
  });
});

describe('yearlyTotal', () => {
  it('charges 11 months for a year (one month free)', () => {
    expect(yearlyTotal(499)).toBe(5489);
  });
});
