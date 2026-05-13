function handleNavbar() {
  const navbar = document.querySelector('.navbar');
  if (window.scrollY > 80) {
    navbar.classList.add('scrolled');
  } else {
    navbar.classList.remove('scrolled');
  }
}

function toggleFAQ(el) {
  const item = el.closest('.faq-item');
  const isActive = item.classList.contains('active');

  document.querySelectorAll('.faq-item').forEach(other => {
    other.classList.remove('active');
  });

  if (!isActive) {
    item.classList.add('active');
  }
}

function downloadApp() {
  window.open('https://apps.apple.com/us/app/%D8%AF%D8%B1%D8%A8%D9%8A%D9%86%D9%8A/id6758008373', '_blank');
}

function revealOnScroll() {
  const targets = document.querySelectorAll('section, .stats-bar');
  targets.forEach(el => {
    const rect = el.getBoundingClientRect();
    if (rect.top < window.innerHeight * 0.88) {
      el.classList.add('visible');
    }
  });
}

function animateCounters() {
  const counters = document.querySelectorAll('.stat-number[data-target]');
  counters.forEach(counter => {
    if (counter.dataset.animated) return;
    const rect = counter.getBoundingClientRect();
    if (rect.top > window.innerHeight) return;

    counter.dataset.animated = 'true';
    const target = parseInt(counter.dataset.target, 10);
    const suffix = counter.dataset.suffix || '';
    const duration = 1600;
    const step = target / (duration / 16);
    let current = 0;

    const update = () => {
      current += step;
      if (current >= target) {
        counter.textContent = target.toLocaleString('ar') + suffix;
        return;
      }
      counter.textContent = Math.floor(current).toLocaleString('ar') + suffix;
      requestAnimationFrame(update);
    };

    requestAnimationFrame(update);
  });
}

document.addEventListener('DOMContentLoaded', () => {
  window.addEventListener('scroll', () => {
    handleNavbar();
    revealOnScroll();
    animateCounters();
  }, { passive: true });

  handleNavbar();
  revealOnScroll();
  animateCounters();
});
