function handleNavbar() {
  const navbar = document.querySelector(".navbar");
  if (window.scrollY > 80) navbar.classList.add("scrolled");
  else navbar.classList.remove("scrolled");
}

function toggleMenu() {
  const hamburger = document.querySelector(".hamburger");
  const mobileMenu = document.getElementById("mobileMenu");
  hamburger.classList.toggle("active");
  mobileMenu.classList.toggle("active");
  if (mobileMenu.classList.contains("active")) {
    document.body.style.overflow = "hidden";
  } else {
    document.body.style.overflow = "";
  }
}

function toggleFAQ(el) {
  const item = el.closest(".faq-item");
  const isActive = item.classList.contains("active");
  document.querySelectorAll(".faq-item").forEach((other) => {
    other.classList.remove("active");
    const q = other.querySelector(".faq-question");
    if (q) q.setAttribute("aria-expanded", "false");
  });
  if (!isActive) {
    item.classList.add("active");
    const q = item.querySelector(".faq-question");
    if (q) q.setAttribute("aria-expanded", "true");
  }
}

function downloadApp() {
  window.open(
    "https://apps.apple.com/us/app/%D8%AF%D8%B1%D8%A8%D9%8A%D9%86%D9%8A/id6758008373",
    "_blank",
    "noopener,noreferrer",
  );
}

// Copyright year
function updateCopyrightYear() {
  const yearEl = document.getElementById("current-year");
  if (yearEl) {
    yearEl.textContent = new Date().getFullYear();
  }
}

// Stats counter animation
function animateCounter(counter) {
  if (counter.dataset.animated) return;
  counter.dataset.animated = "true";
  const target = parseFloat(counter.dataset.target);
  const suffix = counter.dataset.suffix || "";
  const isDecimal = target % 1 !== 0;
  const duration = 1600;
  const step = target / (duration / 16);
  let current = 0;
  const update = () => {
    current += step;
    if (current >= target) {
      if (isDecimal) {
        counter.textContent =
          target.toLocaleString("ar-SA", {
            minimumFractionDigits: 1,
            maximumFractionDigits: 1,
          }) + suffix;
      } else {
        counter.textContent = target.toLocaleString("ar-SA") + suffix;
      }
      return;
    }
    if (isDecimal) {
      counter.textContent =
        current.toLocaleString("ar-SA", {
          minimumFractionDigits: 1,
          maximumFractionDigits: 1,
        }) + suffix;
    } else {
      counter.textContent =
        Math.floor(current).toLocaleString("ar-SA") + suffix;
    }
    requestAnimationFrame(update);
  };
  requestAnimationFrame(update);
}

document.addEventListener("DOMContentLoaded", () => {
  updateCopyrightYear();

  document.querySelectorAll("section:not(.hero):not(.hero-small), .stats-bar").forEach((el) => {
    if (!el.classList.contains("reveal")) el.classList.add("reveal");
  });

  // Scroll reveal
  const revealObserver = new IntersectionObserver(
    (entries, observer) => {
      entries.forEach((entry) => {
        if (entry.isIntersecting) {
          entry.target.classList.add("visible");
          observer.unobserve(entry.target);
        }
      });
    },
    { threshold: 0.15 },
  );

  document.querySelectorAll(".reveal").forEach((el) => {
    revealObserver.observe(el);
  });

  // Stats counter trigger
  const counterObserver = new IntersectionObserver(
    (entries, observer) => {
      entries.forEach((entry) => {
        if (entry.isIntersecting) {
          animateCounter(entry.target);
          observer.unobserve(entry.target);
        }
      });
    },
    { threshold: 0.15 },
  );

  document.querySelectorAll(".stat-number[data-target]").forEach((counter) => {
    counterObserver.observe(counter);
  });

  // QR & contact animation
  const qrObserver = new IntersectionObserver(
    (entries) => {
      entries.forEach((entry) => {
        if (entry.isIntersecting) {
          entry.target.classList.add("qr-visible");
          entry.target.classList.remove("qr-hidden");
        }
      });
    },
    { threshold: 0.2 },
  );

  document.querySelectorAll(".qr-card, .contact-item").forEach((el) => {
    el.classList.add("qr-hidden");
    qrObserver.observe(el);
  });

  // Privacy page reveal
  if (document.querySelector(".privacy-section")) {
    const privacyObserver = new IntersectionObserver(
      (entries) => {
        entries.forEach((entry) => {
          if (entry.isIntersecting) {
            entry.target.style.opacity = "1";
            entry.target.style.transform = "translateY(0)";
          }
        });
      },
      { threshold: 0.1 },
    );
    document.querySelectorAll(".privacy-section").forEach((el, index) => {
      el.style.opacity = "0";
      el.style.transform = "translateY(20px)";
      el.style.transition = `opacity 0.5s ease ${index * 0.1}s, transform 0.5s ease ${index * 0.1}s`;
      privacyObserver.observe(el);
    });
  }

  // Navbar scroll listener
  window.addEventListener("scroll", handleNavbar, { passive: true });
  handleNavbar();

  // FAQ keyboard support
  document.querySelectorAll(".faq-question").forEach((q) => {
    q.addEventListener("keydown", (e) => {
      if (e.key === "Enter" || e.key === " ") {
        e.preventDefault();
        toggleFAQ(q);
      }
    });
  });
});
