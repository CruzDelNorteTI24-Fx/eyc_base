(function () {
  if (window.eycDialog) return;

  const iconByVariant = {
    info: 'bi-info-circle-fill',
    success: 'bi-check-circle-fill',
    warning: 'bi-exclamation-triangle-fill',
    danger: 'bi-x-octagon-fill',
    error: 'bi-x-octagon-fill',
  };

  const titleByVariant = {
    info: 'Aviso',
    success: 'Operacion realizada',
    warning: 'Atencion',
    danger: 'No se pudo completar',
    error: 'No se pudo completar',
  };

  const queue = [];
  let active = null;
  let elements = null;

  const normalizeVariant = (variant) => {
    if (variant === 'danger') return 'danger';
    if (variant === 'error') return 'error';
    if (variant === 'success') return 'success';
    if (variant === 'warning') return 'warning';
    return 'info';
  };

  const ensure = () => {
    if (elements) return elements;

    const root = document.createElement('div');
    root.className = 'eyc-dialog';
    root.setAttribute('aria-hidden', 'true');
    root.innerHTML = `
      <div class="eyc-dialog__backdrop" data-eyc-dialog-cancel></div>
      <section class="eyc-dialog__panel" role="dialog" aria-modal="true" aria-labelledby="eycDialogTitle">
        <div class="eyc-dialog__top">
          <div class="eyc-dialog__icon"><i></i></div>
          <div>
            <p class="eyc-dialog__eyebrow">Eyc</p>
            <h2 class="eyc-dialog__title" id="eycDialogTitle"></h2>
          </div>
        </div>
        <p class="eyc-dialog__message"></p>
        <div class="eyc-dialog__actions">
          <button class="eyc-dialog__btn" type="button" data-eyc-dialog-cancel>Cancelar</button>
          <button class="eyc-dialog__btn eyc-dialog__btn--primary" type="button" data-eyc-dialog-ok>Aceptar</button>
        </div>
      </section>
    `;
    document.body.appendChild(root);

    elements = {
      root,
      icon: root.querySelector('.eyc-dialog__icon i'),
      title: root.querySelector('.eyc-dialog__title'),
      message: root.querySelector('.eyc-dialog__message'),
      ok: root.querySelector('[data-eyc-dialog-ok]'),
      cancel: root.querySelector('.eyc-dialog__actions [data-eyc-dialog-cancel]'),
      cancelers: root.querySelectorAll('[data-eyc-dialog-cancel]'),
    };

    elements.ok.addEventListener('click', () => resolveActive(true));
    elements.cancelers.forEach((el) => {
      el.addEventListener('click', () => resolveActive(false));
    });

    document.addEventListener('keydown', (event) => {
      if (!active || !elements.root.classList.contains('is-open')) return;
      if (event.key === 'Escape') resolveActive(false);
    });

    return elements;
  };

  const resolveActive = (value) => {
    if (!active) return;
    const current = active;
    active = null;
    const els = ensure();
    els.root.classList.remove('is-open');
    els.root.setAttribute('aria-hidden', 'true');
    window.setTimeout(() => {
      current.resolve(value);
      runNext();
    }, 120);
  };

  const runNext = () => {
    if (active || !queue.length) return;

    active = queue.shift();
    const opts = active.options;
    const variant = normalizeVariant(opts.variant);
    const els = ensure();

    els.root.dataset.variant = variant;
    els.icon.className = `bi ${iconByVariant[variant] || iconByVariant.info}`;
    els.title.textContent = opts.title || titleByVariant[variant] || titleByVariant.info;
    els.message.textContent = String(opts.message || '');
    els.ok.textContent = opts.confirmText || 'Aceptar';
    els.cancel.textContent = opts.cancelText || 'Cancelar';
    els.cancel.style.display = opts.showCancel ? '' : 'none';

    els.root.classList.add('is-open');
    els.root.setAttribute('aria-hidden', 'false');
    window.setTimeout(() => els.ok.focus(), 40);
  };

  const open = (options) => new Promise((resolve) => {
    queue.push({ options, resolve });
    runNext();
  });

  window.eycDialog = {
    alert(message, options = {}) {
      return open({
        message,
        variant: options.variant || options.type || 'info',
        title: options.title || '',
        confirmText: options.confirmText || 'Aceptar',
        showCancel: false,
      });
    },
    confirm(message, options = {}) {
      return open({
        message,
        variant: options.variant || options.type || 'warning',
        title: options.title || 'Confirmar accion',
        confirmText: options.confirmText || 'Aceptar',
        cancelText: options.cancelText || 'Cancelar',
        showCancel: true,
      });
    },
  };
})();
