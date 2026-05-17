/* =====================================================
   MASCARADE — MENU RESPONSIVE
===================================================== */

document.addEventListener('DOMContentLoaded', () => {

  const burger  = document.getElementById('burgerBtn');
  const nav     = document.getElementById('siteNav');

  if (!burger || !nav) return;

  burger.addEventListener('click', () => {
    const isOpen = nav.classList.toggle('site-nav--open');
    burger.classList.toggle('burger--open', isOpen);
    burger.setAttribute('aria-expanded', isOpen);
  });

});