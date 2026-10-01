import { Component, model } from '@angular/core';
import { FormsModule } from '@angular/forms';
import { Period, periodPresets } from '../../utils/period';

@Component({
  selector: 'app-period-picker',
  imports: [FormsModule],
  templateUrl: './period-picker.component.html',
  styleUrl: './period-picker.component.scss',
})
export class PeriodPickerComponent {
  readonly period = model.required<Period>();

  protected readonly presets = periodPresets();

  protected isActive(preset: Period): boolean {
    return preset.from === this.period().from && preset.to === this.period().to;
  }
}
