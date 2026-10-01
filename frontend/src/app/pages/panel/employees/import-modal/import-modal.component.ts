import { Component, computed, inject, output, signal } from '@angular/core';
import { FormsModule } from '@angular/forms';
import { EmployeeService } from '../../../../core/services/employee.service';
import { ToastService } from '../../../../core/services/toast.service';
import { errorMessage } from '../../../../core/utils/error-message';
import { ModalComponent } from '../../../../shared/components/modal/modal.component';
import { IMPORT_TEMPLATE, ParsedLine, parseEmployeeImport } from '../../../../shared/utils/employee-import';

/**
 * Carga masiva: se pegan las columnas desde Excel (o se sube un CSV) con
 * nombre, apellidos y sueldo opcional (M:9500 mensual, D:450 diario). Muestra
 * una vista previa con errores antes de crear a nadie.
 */
@Component({
  selector: 'app-import-modal',
  imports: [FormsModule, ModalComponent],
  templateUrl: './import-modal.component.html',
  styleUrl: './import-modal.component.scss',
})
export class ImportModalComponent {
  private readonly employeeService = inject(EmployeeService);
  private readonly toast = inject(ToastService);

  readonly closed = output<void>();
  readonly imported = output<{ created: number; pending: number }>();

  protected text = '';
  protected readonly lines = signal<ParsedLine[]>([]);
  protected readonly saving = signal(false);
  protected readonly serverError = signal<string | null>(null);

  protected readonly errors = computed(() => this.lines().filter((line) => line.errors.length));
  protected readonly canImport = computed(
    () => this.lines().length > 0 && this.errors().length === 0 && !this.saving(),
  );

  protected parse(): void {
    this.serverError.set(null);
    this.lines.set(parseEmployeeImport(this.text));
  }

  protected async loadFile(event: Event): Promise<void> {
    const file = (event.target as HTMLInputElement).files?.[0];
    if (!file) {
      return;
    }
    if (!/\.(csv|txt)$/i.test(file.name)) {
      this.toast.warning('Guarda el archivo de Excel como CSV (Archivo → Guardar como → CSV) o copia y pega las columnas.', {
        title: 'Formato no compatible',
      });
      return;
    }
    this.text = await file.text();
    this.parse();
  }

  protected downloadTemplate(): void {
    const url = URL.createObjectURL(new Blob([IMPORT_TEMPLATE], { type: 'text/csv;charset=utf-8' }));
    const link = document.createElement('a');
    link.href = url;
    link.download = 'plantilla-empleados.csv';
    link.click();
    URL.revokeObjectURL(url);
  }

  protected salaryLabel(line: ParsedLine): string {
    const { salary, salary_period } = line.row;
    if (salary === null) {
      return '—';
    }
    const amount = new Intl.NumberFormat('es-MX', { style: 'currency', currency: 'MXN' }).format(salary);
    return `${amount} ${salary_period === 'monthly' ? 'mensual' : 'diario'}`;
  }

  protected async save(): Promise<void> {
    this.saving.set(true);
    this.serverError.set(null);
    try {
      const result = await this.employeeService.importEmployees(this.lines().map((line) => line.row));
      this.imported.emit(result);
    } catch (error) {
      this.serverError.set(errorMessage(error));
    } finally {
      this.saving.set(false);
    }
  }
}
