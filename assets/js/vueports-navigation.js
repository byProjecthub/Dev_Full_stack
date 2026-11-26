/* Accessible disclosure navigation: mouse, touch, keyboard and native mobile accordions. */
(() => {
  'use strict';
  document.querySelectorAll('[data-vp-navigation]').forEach(root => {
    const bar = root.querySelector('.vp-site-nav');
    const triggers = [...root.querySelectorAll('[data-vp-menu]')];
    const mobile = root.querySelector('#mobileNav');
    const toggle = root.querySelector('.vp-nav-toggle');
    const desktop = window.matchMedia('(min-width: 1051px)');
    const hover = window.matchMedia('(hover: hover) and (pointer: fine)');
    let current = null, closeTimer, hoverOpened = false, previousOverflow = '', background = [];
    const panelFor = trigger => root.querySelector('#' + trigger.getAttribute('aria-controls'));
    function closeMenu(restoreFocus = false) {
      clearTimeout(closeTimer);
      if (!current) return;
      const trigger = current;
      panelFor(trigger).hidden = true;
      trigger.setAttribute('aria-expanded', 'false');
      current = null;hoverOpened = false;
      bar.removeAttribute('data-open-menu');
      if (restoreFocus) trigger.focus();
    }
    function openMenu(trigger, focusFirst = false, viaHover = false) {
      if (!desktop.matches) return;
      clearTimeout(closeTimer);
      if (current !== trigger) closeMenu();
      current = trigger;hoverOpened = viaHover;
      panelFor(trigger).hidden = false;
      trigger.setAttribute('aria-expanded', 'true');
      bar.dataset.openMenu = trigger.dataset.vpMenu;
      if (focusFirst) panelFor(trigger).querySelector('a')?.focus();
    }
    triggers.forEach(trigger => {
      trigger.addEventListener('click', () => {
        if (current === trigger && hoverOpened) hoverOpened = false;
        else if (current === trigger) closeMenu();
        else openMenu(trigger);
      });
      trigger.addEventListener('pointerenter', event => {
        if (hover.matches && event.pointerType === 'mouse') openMenu(trigger, false, true);
      });
      trigger.addEventListener('keydown', event => {
        if (event.key === 'ArrowDown') { event.preventDefault(); openMenu(trigger, true); }
      });
    });
    bar.addEventListener('pointerenter', () => clearTimeout(closeTimer));
    bar.addEventListener('pointerleave', event => {
      if (hover.matches && event.pointerType === 'mouse') closeTimer = setTimeout(() => closeMenu(), 180);
    });
    bar.addEventListener('focusout', () => {
      queueMicrotask(() => { if (!bar.contains(document.activeElement)) closeMenu(); });
    });
    root.querySelectorAll('.vp-nav-desktop > a').forEach(link => link.addEventListener('pointerenter', () => closeMenu()));
    function setBackgroundInert(open) {
      if (open) {
        let branch = root;
        while (branch && branch !== document.body) {
          [...branch.parentElement.children].filter(sibling => sibling !== branch && sibling instanceof HTMLElement).forEach(sibling => {
            background.push([sibling, sibling.inert]);
            sibling.inert = true;
          });
          branch = branch.parentElement;
        }
        [...root.children].filter(node => node !== mobile).forEach(node => {
          background.push([node, node.inert]);node.inert = true;
        });
      } else {
        background.forEach(([node, value]) => { node.inert = value; });background = [];
      }
    }
    function setMobile(open, restoreFocus = true) {
      if (!mobile || (open === !mobile.hidden)) return;
      if (open) {
        closeMenu();
        previousOverflow = document.body.style.overflow;
        document.body.style.overflow = 'hidden';
        document.body.classList.add('vp-nav-locked');
        mobile.hidden = false;mobile.inert = false;
        toggle.setAttribute('aria-expanded', 'true');
        toggle.setAttribute('aria-label', 'Close navigation');
        setBackgroundInert(true);
        mobile.querySelector('.vp-mobile-close').focus();
      } else {
        mobile.hidden = true;mobile.inert = true;
        toggle.setAttribute('aria-expanded', 'false');
        toggle.setAttribute('aria-label', 'Open navigation');
        document.body.style.overflow = previousOverflow;
        document.body.classList.remove('vp-nav-locked');
        setBackgroundInert(false);
        if (restoreFocus) toggle.focus();
      }
    }
    toggle?.addEventListener('click', () => setMobile(mobile.hidden));
    mobile?.querySelector('.vp-mobile-close').addEventListener('click', () => setMobile(false));
    mobile?.querySelectorAll('a').forEach(link => link.addEventListener('click', () => setMobile(false, false)));
    mobile?.querySelectorAll('details').forEach(group => group.addEventListener('toggle', () => {
      if (group.open) mobile.querySelectorAll('details').forEach(other => { if (other !== group) other.open = false; });
    }));
    document.addEventListener('click', event => { if (!root.contains(event.target)) closeMenu(); });
    document.addEventListener('keydown', event => {
      if (event.key === 'Escape') {
        if (mobile && !mobile.hidden) { event.preventDefault();setMobile(false); }
        else if (current) { event.preventDefault();closeMenu(true); }
      }
      if (event.key === 'Tab' && mobile && !mobile.hidden) {
        const focusable = [...mobile.querySelectorAll('a,button,summary')].filter(node => node.getClientRects().length && !node.closest('[hidden]'));
        const first = focusable[0], last = focusable[focusable.length - 1];
        if (event.shiftKey && document.activeElement === first) { event.preventDefault();last.focus(); }
        else if (!event.shiftKey && document.activeElement === last) { event.preventDefault();first.focus(); }
      }
    });
    desktop.addEventListener('change', () => {closeMenu();if (!mobile.hidden) setMobile(false, false);});
  });
})();
