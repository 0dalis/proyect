declare module 'toastify-js' {
  interface ToastifyOptions {
    text?: string;
    node?: Element;
    duration?: number;
    selector?: string | Element;
    destination?: string;
    newWindow?: boolean;
    close?: boolean;
    gravity?: 'top' | 'bottom';
    position?: 'left' | 'right' | 'center';
    backgroundColor?: string;
    avatar?: string;
    className?: string;
    stopOnFocus?: boolean;
    callback?: () => void;
    onClick?: () => void;
    offset?: { x?: number | string; y?: number | string };
    escapeMarkup?: boolean;
    ariaLive?: string;
    style?: { [key: string]: string };
  }

  interface ToastifyInstance {
    showToast(): void;
    hideToast(): void;
    toastElement: Element | null;
  }

  function Toastify(options: ToastifyOptions): ToastifyInstance;

  export default Toastify;
}
