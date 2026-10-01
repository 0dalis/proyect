import { Plan } from '../../core/models';

export interface Quote {
  total: number;
  fits: boolean;
  employeeBlocks: number;
  extraOffices: number;
}

/** Meses que se cobran al pagar un año: uno es de regalo. */
export const YEARLY_BILLED_MONTHS = 11;

export function yearlyTotal(monthly: number): number {
  return monthly * YEARLY_BILLED_MONTHS;
}

/**
 * Precio mensual estimado de un plan para cierto tamaño de empresa.
 * Mismo cálculo que los límites del backend: plan + bloques + extras.
 * Los usuarios de la app no se cobran aparte: cada empleado puede tener uno.
 */
export function quote(plan: Plan, employees: number, offices: number): Quote {
  const employeeBlocks = Math.max(
    0,
    Math.ceil((employees - plan.included_employees) / plan.employee_block_size),
  );
  const extraOffices = Math.max(0, offices - plan.included_offices);
  const isFree = Number(plan.monthly_price) === 0;

  const total =
    Number(plan.monthly_price) +
    employeeBlocks * Number(plan.employee_block_price) +
    extraOffices * Number(plan.extra_office_price);

  return {
    total,
    // El plan gratuito no admite extras
    fits: !isFree || (employeeBlocks === 0 && extraOffices === 0),
    employeeBlocks,
    extraOffices,
  };
}
