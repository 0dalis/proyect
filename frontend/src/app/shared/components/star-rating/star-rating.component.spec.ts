import { TestBed } from '@angular/core/testing';
import { clampScore, StarRatingComponent, starStates } from './star-rating.component';

describe('StarRatingComponent', () => {
  function setup(inputs: Record<string, unknown>) {
    TestBed.configureTestingModule({ imports: [StarRatingComponent] });
    const fixture = TestBed.createComponent(StarRatingComponent);
    for (const [key, value] of Object.entries(inputs)) {
      fixture.componentRef.setInput(key, value);
    }
    fixture.detectChanges();
    return { fixture, el: fixture.nativeElement as HTMLElement };
  }

  it('draws full, half and empty stars', () => {
    expect(starStates(4.5)).toEqual(['full', 'full', 'full', 'full', 'half']);
    expect(starStates(1)).toEqual(['full', 'empty', 'empty', 'empty', 'empty']);
    expect(starStates(2.5)).toEqual(['full', 'full', 'half', 'empty', 'empty']);
  });

  it('keeps scores between 1 and 5 in half steps', () => {
    expect(clampScore(0.5)).toBe(1);
    expect(clampScore(4.26)).toBe(4.5);
    expect(clampScore(7)).toBe(5);
  });

  it('shows a read only rating with a half star', () => {
    const { el } = setup({ value: 3.5, readonly: true });

    expect(el.querySelectorAll('.bi-star-fill').length).toBe(3);
    expect(el.querySelectorAll('.bi-star-half').length).toBe(1);
    expect(el.querySelector('[role="img"]')?.getAttribute('aria-label')).toBe('3.5 de 5 estrellas');
    expect(el.querySelector('[role="slider"]')).toBeNull();
  });

  it('gives half a star when clicking the left half', () => {
    const { fixture, el } = setup({ value: 0 });
    const fourth = el.querySelectorAll<HTMLElement>('.star')[3];
    // Sin layout en las pruebas: la estrella mide 40px desde x=100
    fourth.getBoundingClientRect = () => ({ left: 100, width: 40 }) as DOMRect;

    fourth.dispatchEvent(new MouseEvent('click', { clientX: 110 }));
    expect(fixture.componentInstance.value()).toBe(3.5);

    fourth.dispatchEvent(new MouseEvent('click', { clientX: 130 }));
    expect(fixture.componentInstance.value()).toBe(4);
  });

  it('moves in half stars with the keyboard', () => {
    const { fixture, el } = setup({ value: 4 });
    const slider = el.querySelector<HTMLElement>('[role="slider"]')!;

    slider.dispatchEvent(new KeyboardEvent('keydown', { key: 'ArrowRight' }));
    expect(fixture.componentInstance.value()).toBe(4.5);
    slider.dispatchEvent(new KeyboardEvent('keydown', { key: 'ArrowRight' }));
    slider.dispatchEvent(new KeyboardEvent('keydown', { key: 'ArrowRight' }));
    expect(fixture.componentInstance.value()).toBe(5);
    slider.dispatchEvent(new KeyboardEvent('keydown', { key: 'Home' }));
    expect(fixture.componentInstance.value()).toBe(1);
  });
});
