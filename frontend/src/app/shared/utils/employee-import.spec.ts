import { parseEmployeeImport, parseSalary } from './employee-import';

describe('employee import', () => {
  it('reads rows pasted from Excel and skips the header', () => {
    const rows = parseEmployeeImport('Nombre\tApellidos\tSueldo\nAna\tLópez\tM:9500\nBeto\tRuiz\tD:450\nCaro\tDíaz\t\n');

    expect(rows).toHaveLength(3);
    expect(rows[0].row).toEqual({ first_name: 'Ana', last_name: 'López', salary: 9500, salary_period: 'monthly' });
    expect(rows[1].row.salary_period).toBe('daily');
    expect(rows[2].row.salary).toBeNull();
    expect(rows.every((r) => r.errors.length === 0)).toBe(true);
  });

  it('reads CSV with commas, semicolons and quotes', () => {
    const rows = parseEmployeeImport('"Ana María","López, Jr.","M:9,500.50"\nBeto;Ruiz;d:450');

    expect(rows[0].row).toMatchObject({ first_name: 'Ana María', last_name: 'López, Jr.', salary: 9500.5 });
    expect(rows[1].row).toMatchObject({ first_name: 'Beto', salary: 450, salary_period: 'daily' });
  });

  it('flags missing names and wrong salaries', () => {
    const [noLastName, badSalary] = parseEmployeeImport('Ana\nBeto\tRuiz\t9500');

    expect(noLastName.errors).toContain('Faltan los apellidos');
    expect(badSalary.errors[0]).toContain('M:9500');
  });

  it('understands the salary notation', () => {
    expect(parseSalary('M:9500')).toEqual({ amount: 9500, period: 'monthly' });
    expect(parseSalary('d: $450')).toEqual({ amount: 450, period: 'daily' });
    expect(parseSalary('')).toEqual({ amount: null, period: null });
    expect(parseSalary('X:10').error).toBeTruthy();
    expect(parseSalary('M:0').error).toBeTruthy();
  });
});
