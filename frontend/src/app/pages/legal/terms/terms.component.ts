import { Component } from '@angular/core';
import { RouterLink } from '@angular/router';
import { LEGAL } from '../../../shared/constants/legal';

@Component({
  selector: 'app-terms',
  imports: [RouterLink],
  templateUrl: './terms.component.html',
  styleUrl: './terms.component.scss',
})
export class TermsComponent {
  protected readonly legal = LEGAL;
}
