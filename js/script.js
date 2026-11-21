/* ============================================================
   Cheer you up — Site scripts
   ============================================================ */
document.addEventListener('DOMContentLoaded', () => {

  /* ---------- Header: shrink + progress on scroll ---------- */
  const header = document.getElementById('siteHeader');
  const progress = document.getElementById('scrollProgress');

  const onScroll = () => {
    const scrollY = window.scrollY || window.pageYOffset;

    if (header) {
      header.classList.toggle('is-scrolled', scrollY > 40);
    }

    if (progress) {
      const docHeight = document.documentElement.scrollHeight - window.innerHeight;
      const pct = docHeight > 0 ? (scrollY / docHeight) * 100 : 0;
      progress.style.width = pct + '%';
    }
  };
  window.addEventListener('scroll', onScroll, { passive: true });
  onScroll();

  /* ---------- Mobile menu ---------- */
  const menuToggle = document.getElementById('menuToggle');
  const mobileMenu = document.getElementById('mobileMenu');

  const closeMenu = () => {
    mobileMenu.classList.remove('is-open');
    mobileMenu.setAttribute('aria-hidden', 'true');
    menuToggle.setAttribute('aria-expanded', 'false');
    document.body.style.overflow = '';
  };

  const openMenu = () => {
    mobileMenu.classList.add('is-open');
    mobileMenu.setAttribute('aria-hidden', 'false');
    menuToggle.setAttribute('aria-expanded', 'true');
    document.body.style.overflow = 'hidden';
  };

  if (menuToggle && mobileMenu) {
    menuToggle.addEventListener('click', () => {
      const isOpen = mobileMenu.classList.contains('is-open');
      isOpen ? closeMenu() : openMenu();
    });

    mobileMenu.querySelectorAll('a').forEach((link) => {
      link.addEventListener('click', closeMenu);
    });

    document.addEventListener('keydown', (e) => {
      if (e.key === 'Escape') closeMenu();
    });
  }

  /* ---------- Scroll reveal ---------- */
  const revealEls = document.querySelectorAll('[data-reveal]');

  if ('IntersectionObserver' in window) {
    const io = new IntersectionObserver((entries) => {
      entries.forEach((entry) => {
        if (entry.isIntersecting) {
          // slight stagger for elements revealed together
          entry.target.classList.add('is-visible');
          io.unobserve(entry.target);
        }
      });
    }, { threshold: 0.15, rootMargin: '0px 0px -8% 0px' });

    revealEls.forEach((el) => io.observe(el));
  } else {
    // fallback: show everything immediately
    revealEls.forEach((el) => el.classList.add('is-visible'));
  }

  /* ---------- Contact form (sends via php/contact.php) ---------- */
  const form = document.getElementById('contactForm');
  const formNote = document.getElementById('formNote');

  if (form) {
    const submitBtn = form.querySelector('[type="submit"]');

    form.addEventListener('submit', async (e) => {
      e.preventDefault();

      if (!form.checkValidity()) {
        formNote.textContent = '必須項目をご確認のうえ、もう一度お試しください。';
        formNote.classList.add('is-error');
        form.reportValidity();
        return;
      }

      submitBtn.disabled = true;
      formNote.classList.remove('is-error');
      formNote.textContent = '送信しています…';

      try {
        const res = await fetch(form.action, { method: 'POST', body: new FormData(form) });
        const data = await res.json();
        formNote.textContent = data.message;
        if (data.ok) {
          form.reset();
        } else {
          formNote.classList.add('is-error');
        }
      } catch (err) {
        formNote.classList.add('is-error');
        formNote.textContent = '送信に失敗しました。時間をおいて再度お試しください。';
      } finally {
        submitBtn.disabled = false;
      }
    });
  }

  /* ---------- Product accordion (business.html) ---------- */
  const productToggles = document.querySelectorAll('.product-toggle');

  productToggles.forEach((toggle) => {
    toggle.addEventListener('click', () => {
      const item = toggle.closest('.product-item');
      const isOpen = item.classList.contains('is-open');
      item.classList.toggle('is-open', !isOpen);
      toggle.setAttribute('aria-expanded', String(!isOpen));
    });
  });

});