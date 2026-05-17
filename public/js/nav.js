const burgerBtn = document.getElementById('burgerBtn');
const siteNav   = document.getElementById('siteNav');

burgerBtn.addEventListener('click', () => {
  const isOpen = siteNav.classList.toggle('is-open');
  burgerBtn.setAttribute('aria-expanded', isOpen);
});

// Fermer le menu au clic sur un lien
siteNav.querySelectorAll('.nav__link').forEach(link => {
  link.addEventListener('click', () => {
    siteNav.classList.remove('is-open');
    burgerBtn.setAttribute('aria-expanded', false);
  });
});