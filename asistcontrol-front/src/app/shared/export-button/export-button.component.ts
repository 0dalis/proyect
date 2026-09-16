import { Component, Input } from '@angular/core';
import { CommonModule } from '@angular/common';

import { ExportService } from '../../services/export.service';
import { ExportFormat } from '../../models/api.models';

@Component({
  selector: 'app-export-button',
  standalone: true,
  imports: [CommonModule],
  template: `
    <div class="inline-flex items-center gap-1">
      <button type="button" (click)="download('csv')" class="btn-secondary" title="Exportar CSV">CSV</button>
      <button type="button" (click)="download('xlsx')" class="btn-secondary" title="Exportar Excel">XLSX</button>
      <button type="button" (click)="download('pdf')" class="btn-secondary" title="Exportar PDF">PDF</button>
    </div>
  `
})
export class ExportButtonComponent {

  @Input() resource = '';
  @Input() filters: Record<string, any> = {};

  constructor(private exportService: ExportService) {}

  download(format: ExportFormat): void {
    if (!this.resource) return;
    this.exportService.download(this.resource, format, this.filters);
  }
}
