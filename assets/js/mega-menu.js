(() => {
  'use strict';

  // Elementor editor uses the same markup with an isolated safe mode.
  // Editor mode never creates overlays, fixed panels, page scroll locks or navigation.

  const instances = new WeakMap();
  let current = null;

  class Menu {
    constructor(root) {
      this.root = root;
      this.button = root.querySelector('.ramka-mega__trigger');
      this.panel = root.querySelector('.ramka-mega__panel');
      this.overlay = root.querySelector('.ramka-mega__overlay');
      this.tabs = [...root.querySelectorAll('.ramka-mega__root')];
      this.isOpen = false;
      this.locked = false;
      this.oldBodyOverflow = '';
      this.oldHtmlOverflow = '';
      this.mobile = false;
      this.headerSafe = root.dataset.headerSafe !== 'no';
      const editMode = window.elementorFrontend?.isEditMode;
      const elementorEditMode = typeof editMode === 'function' ? Boolean(editMode.call(window.elementorFrontend)) : Boolean(editMode);
      this.editorMode = root.dataset.editor === 'yes' || elementorEditMode;

      if (!this.button || !this.panel) return;

      // A closed header widget must never leave an interactive/full-screen layer behind.
      this.panel.hidden = true;
      this.panel.setAttribute('aria-hidden', 'true');
      if (this.overlay) this.overlay.hidden = true;
      this.root.classList.remove('is-open');
      this.button.setAttribute('aria-expanded', 'false');

      this.resize = () => this.refresh();
      this.scroll = () => {
        if (this.isOpen && !this.mobile) this.placeDesktop();
      };

      this.button.addEventListener('click', () => {
        if (!this.mobile && root.dataset.trigger === 'hover') {
          this.open();
          if (!this.editorMode) this.firstFocus();
        } else {
          this.toggle();
        }
      });

      this.button.addEventListener('mouseenter', () => {
        if (!this.mobile && root.dataset.trigger === 'hover') this.open();
      });

      root.addEventListener('mouseleave', (event) => {
        if (!this.mobile && root.dataset.trigger === 'hover' && !this.panel.contains(event.relatedTarget)) this.close();
      });

      this.panel.addEventListener('mouseleave', (event) => {
        if (!this.mobile && root.dataset.trigger === 'hover' && !this.button.contains(event.relatedTarget)) this.close();
      });

      this.button.addEventListener('keydown', (e) => {
        if (['ArrowDown', 'Enter', ' '].includes(e.key) && !this.isOpen) {
          e.preventDefault();
          this.open();
          this.firstFocus();
        }
      });

      this.panel.addEventListener('keydown', (e) => this.keys(e));
      this.panel.addEventListener('click', (e) => {
        if (e.target.closest('.ramka-mega__close')) {
          this.close(true);
        } else if (e.target.closest('.ramka-mega__back')) {
          const expanded = [...this.panel.querySelectorAll('.ramka-mega__details[open]')];
          if (expanded.length) expanded[expanded.length - 1].open = false;
          else this.close(true);
        } else if (e.target.closest('a')) {
          if (this.editorMode) {
            e.preventDefault();
            return;
          }
          this.close(false);
        }
      });

      this.overlay?.addEventListener('click', () => this.close(true));

      this.tabs.forEach((tab, i) => {
        tab.addEventListener('mouseenter', () => { if (!this.mobile) this.select(i); });
        tab.addEventListener('focus', () => this.select(i));
        tab.addEventListener('click', () => this.select(i));
      });

      document.addEventListener('pointerdown', (e) => {
        if (this.isOpen && !this.root.contains(e.target)) this.close();
      });

      document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && this.isOpen) {
          e.preventDefault();
          this.close(true);
        }
      });

      window.addEventListener('resize', this.resize, { passive: true });
      window.addEventListener('scroll', this.scroll, { passive: true });
      window.addEventListener('pagehide', () => this.unlockScroll());

      this.refresh();
      if (root.dataset.editorOpen === 'yes') this.open();
    }

    refresh() {
      const wasMobile = this.mobile;
      this.mobile = this.editorMode ? false : window.innerWidth <= Number(this.root.dataset.breakpoint || 767);
      this.root.dataset.responsive = this.mobile ? 'mobile' : 'desktop';
      this.root.dataset.tabletActive = window.innerWidth <= Number(this.root.dataset.tablet || 768) ? 'yes' : 'no';

      if (wasMobile !== this.mobile && this.isOpen) this.close();
      if (!this.mobile) this.unlockScroll();

      this.resetGeometry();
      if (this.isOpen && !this.mobile) this.placeDesktop();
    }

    resetGeometry() {
      this.panel.style.removeProperty('left');
      this.panel.style.removeProperty('right');
      this.panel.style.removeProperty('top');
      this.panel.style.removeProperty('bottom');
      this.panel.style.removeProperty('width');
      this.panel.style.removeProperty('max-height');
    }

    placeDesktop() {
      if (this.mobile || !this.isOpen) return;

      const viewportWidth = document.documentElement.clientWidth || window.innerWidth;
      const anchor = this.button.getBoundingClientRect();
      const rootBox = this.root.getBoundingClientRect();
      const container = this.root.closest('.e-con, .elementor-container, .elementor-section') || this.root;
      const containerBox = container.getBoundingClientRect();
      if (this.editorMode) {
        const editorWidth = Math.max(280, Math.min(containerBox.width || viewportWidth - 24, viewportWidth - 24));
        this.panel.style.top = `${Math.max(0, anchor.bottom - rootBox.top + 8)}px`;
        this.panel.style.width = `${editorWidth}px`;
        this.panel.style.maxHeight = `${Math.min(560, Math.max(280, window.innerHeight - anchor.bottom - 24))}px`;
        if (this.root.dir === 'rtl') {
          this.panel.style.left = 'auto';
          this.panel.style.right = '0px';
        } else {
          this.panel.style.left = '0px';
          this.panel.style.right = 'auto';
        }
        return;
      }

      const mode = this.root.dataset.width;
      const styles = getComputedStyle(this.root);
      const custom = styles.getPropertyValue('--ramka-width').trim();
      const measured = custom.endsWith('vw') ? (parseFloat(custom) * viewportWidth / 100) : parseFloat(custom);
      const wideValue = styles.getPropertyValue('--ramka-wide-width').trim();
      const measuredWide = wideValue.endsWith('vw') ? (parseFloat(wideValue) * viewportWidth / 100) : parseFloat(wideValue);
      const wideLayout = this.root.dataset.wideLayout === 'yes';

      let width = mode === 'viewport'
        ? viewportWidth - 16
        : mode === 'container'
          ? containerBox.width
          : (measured || 900);

      if (wideLayout) {
        width = Math.max(width, measuredWide || 1280);
      }
      width = Math.max(280, Math.min(width, viewportWidth - 24));

      let viewportLeft;
      if (wideLayout) {
        viewportLeft = (viewportWidth - width) / 2;
      } else if (mode === 'viewport') {
        viewportLeft = 8;
      } else if (mode === 'container') {
        viewportLeft = containerBox.left;
      } else {
        viewportLeft = this.root.dir === 'rtl' ? anchor.right - width : anchor.left;
      }
      viewportLeft = Math.max(8, Math.min(viewportLeft, viewportWidth - width - 8));

      // Desktop is a true absolute dropdown anchored to the widget, not a viewport modal.
      const relativeLeft = viewportLeft - rootBox.left;
      const relativeTop = anchor.bottom - rootBox.top + 8;
      const roomBelow = Math.max(240, window.innerHeight - anchor.bottom - 16);
      const desiredHeight = Number.parseInt(getComputedStyle(this.root).getPropertyValue('--ramka-max-height'), 10) || 680;

      this.panel.style.left = `${relativeLeft}px`;
      this.panel.style.top = `${relativeTop}px`;
      this.panel.style.width = `${width}px`;
      this.panel.style.maxHeight = `${Math.min(desiredHeight, roomBelow)}px`;
    }

    // Header widget safety: never mutate page-level overflow.
    lockScroll() {
      this.locked = false;
    }

    unlockScroll() {
      this.locked = false;
    }

    open() {
      if (this.isOpen) return;
      if (current && current !== this) current.close();
      current = this;
      this.isOpen = true;

      this.panel.hidden = false;
      this.panel.setAttribute('aria-hidden', 'false');
      const backdropEnabled = !this.headerSafe && !this.editorMode && (this.mobile
        ? this.root.dataset.mobileBackdrop === 'yes'
        : this.root.dataset.desktopBackdrop === 'yes');

      if (this.overlay && backdropEnabled && this.root.dataset.editorOpen !== 'yes' && !(this.mobile && this.root.dataset.mobileMode === 'accordion')) {
        this.overlay.hidden = false;
      }

      this.button.setAttribute('aria-expanded', 'true');
      this.panel.setAttribute('aria-modal', this.mobile && this.root.dataset.mobileMode !== 'accordion' ? 'true' : 'false');
      this.root.classList.add('is-open');

      if (!this.headerSafe && !this.editorMode && this.mobile && this.root.dataset.mobileMode !== 'accordion' && this.root.dataset.editorOpen !== 'yes') {
        this.lockScroll();
      }

      if (!this.mobile) this.placeDesktop();
    }

    close(focus = false) {
      if (!this.isOpen) {
        this.panel.hidden = true;
          this.panel.setAttribute('aria-hidden', 'true');
        if (this.overlay) this.overlay.hidden = true;
        this.unlockScroll();
        return;
      }

      this.isOpen = false;
      this.panel.hidden = true;
      this.panel.setAttribute('aria-hidden', 'true');
      if (this.overlay) this.overlay.hidden = true;
      this.button.setAttribute('aria-expanded', 'false');
      this.root.classList.remove('is-open');
      this.unlockScroll();
      this.resetGeometry();

      if (current === this) current = null;
      if (focus) this.button.focus();
    }

    toggle() {
      if (this.isOpen) this.close();
      else {
        this.open();
        if (this.mobile && !this.editorMode) this.firstFocus();
      }
    }

    firstFocus() {
      (this.mobile ? this.panel.querySelector('.ramka-mega__close') : this.tabs.find((t) => t.tabIndex === 0))?.focus();
    }

    select(index) {
      this.tabs.forEach((tab, i) => {
        const active = i === index;
        tab.setAttribute('aria-selected', String(active));
        tab.tabIndex = active ? 0 : -1;
        const pane = document.getElementById(tab.getAttribute('aria-controls'));
        if (pane) pane.hidden = !active;
      });
    }

    keys(e) {
      if (this.mobile && this.root.dataset.mobileMode !== 'accordion' && e.key === 'Tab') {
        const focusable = [...this.panel.querySelectorAll('button:not([hidden]), summary, a[href]')]
          .filter((el) => el.getClientRects().length);
        if (!focusable.length) return;
        const first = focusable[0];
        const last = focusable[focusable.length - 1];
        if (e.shiftKey && document.activeElement === first) {
          e.preventDefault();
          last.focus();
        } else if (!e.shiftKey && document.activeElement === last) {
          e.preventDefault();
          first.focus();
        }
      }

      const index = this.tabs.indexOf(document.activeElement);
      if (index < 0) return;
      let next = index;
      if (['ArrowDown', 'ArrowRight'].includes(e.key)) next = (index + 1) % this.tabs.length;
      else if (['ArrowUp', 'ArrowLeft'].includes(e.key)) next = (index - 1 + this.tabs.length) % this.tabs.length;
      else if (e.key === 'Home') next = 0;
      else if (e.key === 'End') next = this.tabs.length - 1;
      else return;

      e.preventDefault();
      this.select(next);
      this.tabs[next].focus();
    }
  }

  function scan(node = document) {
    const roots = node.matches?.('.ramka-mega') ? [node] : [...node.querySelectorAll('.ramka-mega')];
    roots.forEach((root) => {
      if (!instances.has(root)) instances.set(root, new Menu(root));
    });
  }

  if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', () => scan());
  else scan();

  if (window.elementorFrontend?.hooks) {
    window.elementorFrontend.hooks.addAction('frontend/element_ready/ramka_product_mega_menu.default', ($scope) => scan($scope[0]));
  } else {
    window.addEventListener('elementor/frontend/init', () => {
      window.elementorFrontend.hooks.addAction('frontend/element_ready/ramka_product_mega_menu.default', ($scope) => scan($scope[0]));
    });
  }
})();
