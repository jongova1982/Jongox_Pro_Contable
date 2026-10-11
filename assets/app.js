(() => {
  'use strict';

  const $ = (selector, context = document) => context.querySelector(selector);
  const $$ = (selector, context = document) => [...context.querySelectorAll(selector)];

  const navigation = {
    sidebar: $('#sidebar'),
    trigger: $('.menu'),
    overlay: $('.nav-overlay'),

    open() {
      this.sidebar?.classList.add('open');
      document.body.classList.add('menu-open');
      this.trigger?.setAttribute('aria-expanded', 'true');
      this.overlay?.removeAttribute('tabindex');
      $('.sidebar nav a')?.focus();
    },

    close({ restoreFocus = true } = {}) {
      this.sidebar?.classList.remove('open');
      document.body.classList.remove('menu-open');
      this.trigger?.setAttribute('aria-expanded', 'false');
      this.overlay?.setAttribute('tabindex', '-1');
      if (restoreFocus) this.trigger?.focus();
    },

    init() {
      this.trigger?.addEventListener('click', () =>
        this.sidebar?.classList.contains('open') ? this.close() : this.open()
      );
      this.overlay?.addEventListener('click', () => this.close());
      $$('.sidebar nav a').forEach(link =>
        link.addEventListener('click', () => this.close({ restoreFocus: false }))
      );
      window.addEventListener('resize', () => {
        if (window.innerWidth > 700) this.close({ restoreFocus: false });
      });
    }
  };

  const saleForm = {
    product: $('#product'),
    price: $('#price'),
    quantity: $('#qty'),
    total: $('#total'),
    partyField: $('#party-field'),

    calculate() {
      if (!this.total) return;
      const amount = (+this.price?.value || 0) * (+this.quantity?.value || 0);
      this.total.textContent = new Intl.NumberFormat('es-CO', {
        style: 'currency', currency: 'COP', maximumFractionDigits: 0
      }).format(amount);
    },

    setCustomerType(type) {
      const registered = type === 'registrado';
      const select = $('select', this.partyField);
      this.partyField?.classList.toggle('hidden', !registered);
      if (select) {
        select.required = registered;
        select.disabled = !registered;
        if (!registered) select.value = '';
      }
    },

    init() {
      this.product?.addEventListener('change', () => {
        if (this.price) this.price.value = this.product.selectedOptions[0]?.dataset.price || 0;
        this.calculate();
      });
      [this.price, this.quantity].forEach(field => field?.addEventListener('input', () => this.calculate()));
      $$('input[name="tipo_cliente"]').forEach(radio =>
        radio.addEventListener('change', () => this.setCustomerType(radio.value))
      );
      const selected = $('input[name="tipo_cliente"]:checked');
      if (selected) this.setCustomerType(selected.value);
    }
  };

  function improveFormInputs() {
    $$('input[type="number"]').forEach(input => input.inputMode = 'decimal');
    $$('input[type="email"]').forEach(input => input.inputMode = 'email');
    $$('input[name="telefono"]').forEach(input => input.inputMode = 'tel');
  }

  function enableKeyboardNavigation() {
    const routes = ['resumen', 'productos', 'clientes', 'proveedores', 'compras', 'ventas', 'bodega'];
    document.addEventListener('keydown', event => {
      if (event.key === 'Escape') navigation.close();
      if (!event.altKey || event.ctrlKey || event.metaKey) return;
      const index = Number(event.key) - 1;
      if (routes[index]) {
        event.preventDefault();
        window.location.href = `?seccion=${routes[index]}`;
      }
    });
  }

  const systemStatus = {
    element: $('#connection-status'),
    text: $('.connection-text'),
    antenna: $('#antenna-status'),
    timer: null,

    render(connected) {
      this.element?.classList.remove('checking', 'online', 'offline');
      this.element?.classList.add(connected ? 'online' : 'offline');
      if (this.text) this.text.textContent = connected ? 'Operación en línea' : 'Falla · No conectado';
      this.antenna?.classList.remove('online', 'offline');
      this.antenna?.classList.add(connected ? 'online' : 'offline');
      this.antenna?.setAttribute('aria-label', connected ? 'Jongox Contable conectado' : 'Jongox Contable sin conexión');
    },

    async check() {
      if (!navigator.onLine) return this.render(false);
      const controller = new AbortController();
      const timeout = setTimeout(() => controller.abort(), 5000);
      try {
        const response = await fetch(`status.php?t=${Date.now()}`, {
          cache: 'no-store', signal: controller.signal
        });
        const data = response.ok ? await response.json() : { connected: false };
        this.render(Boolean(data.connected));
      } catch (_) {
        this.render(false);
      } finally {
        clearTimeout(timeout);
      }
    },

    init() {
      this.check();
      window.addEventListener('online', () => this.check());
      window.addEventListener('offline', () => this.render(false));
      this.timer = window.setInterval(() => this.check(), 15000);
    }
  };

  navigation.init();
  saleForm.init();
  improveFormInputs();
  enableKeyboardNavigation();
  systemStatus.init();
})();
