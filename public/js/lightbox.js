/* =====================================================
   NUCLEUS — LIGHTBOX
   Isolé — remplaçable par toute autre librairie
===================================================== */

document.addEventListener('DOMContentLoaded', () => {

    const links = Array.from(document.querySelectorAll('.gallery-item__link'));
    if (!links.length) return;

    let currentIndex = 0;

    // Créer la lightbox
    const lightbox = document.createElement('div');
    lightbox.className = 'lightbox';
    lightbox.innerHTML = `
        <button class="lightbox__close" aria-label="Fermer">×</button>
        <button class="lightbox__prev" aria-label="Image précédente">&#8592;</button>
        <img class="lightbox__img" src="" alt="">
        <button class="lightbox__next" aria-label="Image suivante">&#8594;</button>
    `;
    document.body.appendChild(lightbox);

    const img = lightbox.querySelector('.lightbox__img');
    const close = lightbox.querySelector('.lightbox__close');
    const prev = lightbox.querySelector('.lightbox__prev');
    const next = lightbox.querySelector('.lightbox__next');

    const show = (index) => {
        currentIndex = (index + links.length) % links.length;
        img.src = links[currentIndex].href;
        img.alt = links[currentIndex].querySelector('img')?.alt || '';
        prev.style.display = links.length > 1 ? 'flex' : 'none';
        next.style.display = links.length > 1 ? 'flex' : 'none';
        lightbox.classList.add('lightbox--open');
    };

    const closeLightbox = () => {
        lightbox.classList.remove('lightbox--open');
        img.src = '';
    };

    // Ouvrir au clic
    links.forEach((link, index) => {
        link.addEventListener('click', (e) => {
            e.preventDefault();
            show(index);
        });
    });

    // Navigation
    prev.addEventListener('click', (e) => { e.stopPropagation(); show(currentIndex - 1); });
    next.addEventListener('click', (e) => { e.stopPropagation(); show(currentIndex + 1); });

    // Fermeture
    close.addEventListener('click', closeLightbox);
    lightbox.addEventListener('click', (e) => { if (e.target === lightbox) closeLightbox(); });

    document.addEventListener('keydown', (e) => {
        if (!lightbox.classList.contains('lightbox--open')) return;
        if (e.key === 'Escape') closeLightbox();
        if (e.key === 'ArrowLeft') show(currentIndex - 1);
        if (e.key === 'ArrowRight') show(currentIndex + 1);
    });

});