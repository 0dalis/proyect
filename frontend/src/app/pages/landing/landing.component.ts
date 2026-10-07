import { CurrencyPipe } from '@angular/common';
import { Component, computed, inject, OnInit, signal } from '@angular/core';
import { RouterLink } from '@angular/router';
import { Plan, Testimonials } from '../../core/models';
import { AuthService } from '../../core/services/auth.service';
import { RatingService } from '../../core/services/rating.service';
import { StarRatingComponent } from '../../shared/components/star-rating/star-rating.component';
import { quote, yearlyTotal } from './pricing';

@Component({
  selector: 'app-landing',
  imports: [RouterLink, CurrencyPipe, StarRatingComponent],
  templateUrl: './landing.component.html',
  styleUrl: './landing.component.scss',
})
export class LandingComponent implements OnInit {
  protected readonly auth = inject(AuthService);
  private readonly ratings = inject(RatingService);

  protected readonly plans = signal<Plan[]>([]);
  /** Opiniones que el Super Admin eligió mostrar. */
  protected readonly testimonials = signal<Testimonials | null>(null);
  protected readonly plansError = signal(false);
  protected readonly employees = signal(20);
  protected readonly offices = signal(1);
  /** Precios anuales: 11 meses, uno de regalo. */
  protected readonly yearly = signal(false);
  protected readonly yearlyTotal = yearlyTotal;
  protected readonly openFaq = signal<number | null>(0);

  protected readonly quotes = computed(() =>
    this.plans().map((plan) => ({
      plan,
      quote: quote(plan, this.employees(), this.offices()),
    })),
  );

  protected readonly recommended = computed(() => {
    const fitting = this.quotes().filter((item) => item.quote.fits);
    return fitting.length
      ? fitting.reduce((a, b) => (a.quote.total <= b.quote.total ? a : b)).plan.slug
      : null;
  });

  protected readonly features = [
    {
      icon: 'fingerprint',
      title: 'Huella, Face ID o PIN',
      text: 'El empleado checa desde su celular con la biometría del propio teléfono. Sin lectores extra.',
    },
    {
      icon: 'person-badge',
      title: 'Kiosko con credencial QR',
      text: 'Operadores y temporales checan con su credencial impresa o su PIN. No necesitan app ni celular.',
    },
    {
      icon: 'geo-alt',
      title: 'Geocerca por oficina',
      text: 'Define en el mapa un radio de 10 a 100 m. Guardamos la ubicación de cada checada.',
    },
    {
      icon: 'house-door',
      title: 'Home office',
      text: 'Días o empleados sin geocerca, pero con las mismas reglas de turno y retardos.',
    },
    {
      icon: 'moon-stars',
      title: 'Turnos de día y de noche',
      text: 'Tolerancia, retardos y faltas por turno. Turnos que cruzan la medianoche, como el del vigilante.',
    },
    {
      icon: 'check2-circle',
      title: 'Permisos y vacaciones',
      text: 'Justificaciones, avisos de llegada tarde o salida anticipada y vacaciones con aprobación por rol.',
    },
    {
      icon: 'bell',
      title: 'Avisos y noticias',
      text: 'Notificaciones push por área, oficina o rol, y un tablero de noticias en el kiosko.',
    },
    {
      icon: 'cash-coin',
      title: 'Pre-nómina y bonos',
      text: 'Bonos de puntualidad y asistencia con reglas que tú configuras, como "menos de 3 retardos".',
    },
  ];

  protected readonly roles = [
    {
      name: 'Dueño',
      text: 'La cuenta maestra. Todos los permisos, nombra administradores y decide qué puede hacer cada rol.',
    },
    {
      name: 'Administrador',
      text: 'Recursos Humanos: empleados, vacaciones, justificaciones, turnos, sueldos y bonos.',
    },
    {
      name: 'Gerente',
      text: 'Ve a su área y autoriza justificaciones y retardos, si el dueño se lo permite.',
    },
    {
      name: 'Empleado',
      text: 'Checa asistencia, pide permisos y vacaciones, y recibe los avisos de la empresa.',
    },
  ];

  protected readonly faqs = [
    {
      q: '¿Qué pasa si un empleado no quiere instalar la app?',
      a: 'Puede checar en el kiosko con su credencial QR o con su número de empleado y PIN. Todas las checadas llegan al mismo reporte.',
    },
    {
      q: '¿La huella o el rostro se guardan en sus servidores?',
      a: 'No. La biometría nunca sale del celular: el teléfono solo confirma que es su dueño y firma la checada con una llave segura.',
    },
    {
      q: '¿Cómo evitan que alguien cheque por otro en el kiosko?',
      a: 'El kiosko toma una foto en cada checada. Si una credencial se pierde, la regeneras y la anterior deja de funcionar.',
    },
    {
      q: '¿Mis datos están separados de otras empresas?',
      a: 'Sí. Los planes Free y Básico comparten una base aislada por empresa, Plus usa una base exclusiva para clientes Plus, y Premium tiene una base de datos dedicada.',
    },
    {
      q: '¿Necesito tarjeta para la prueba?',
      a: 'No. Registras tu empresa, confirmas tu correo y empiezas tu periodo de prueba.',
    },
  ];

  async ngOnInit(): Promise<void> {
    // Se pregunta por la sesión desde ya: así "Entrar" / "Prueba gratis" no
    // esperan a /me al hacer clic (el guard de invitados ya la tiene en caché).
    this.auth.loadUser().catch(() => undefined);

    // Las opiniones son opcionales: si fallan, la sección no se muestra
    this.ratings
      .testimonials()
      .then((data) => this.testimonials.set(data.items.length ? data : null))
      .catch(() => undefined);

    try {
      this.plans.set(await this.auth.plans());
    } catch {
      this.plansError.set(true);
    }
  }

  protected toggleFaq(index: number): void {
    this.openFaq.update((current) => (current === index ? null : index));
  }

  protected setNumber(target: 'employees' | 'offices', event: Event): void {
    const value = Math.max(1, Number((event.target as HTMLInputElement).value) || 1);
    this[target].set(value);
  }
}
