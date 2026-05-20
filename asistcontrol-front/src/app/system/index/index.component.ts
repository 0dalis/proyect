import { Component } from '@angular/core';
import { RouterOutlet } from '@angular/router';
import { SidebarComponent } from '../../assets/sidebar/sidebar.component';

@Component({
  selector: 'app-index',
  imports: [RouterOutlet, SidebarComponent],
  templateUrl: './index.component.html',
  styleUrl: './index.component.css'
})
export class IndexComponent {

}
