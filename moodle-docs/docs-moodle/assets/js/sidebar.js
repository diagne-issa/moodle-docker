/* ============================================================================
   Documentation Plateforme LMS URDFS — comportements de navigation
   ----------------------------------------------------------------------------
   Trois responsabilités, rien de plus :
     1. Repli de la colonne de navigation (état mémorisé).
     2. Infobulles des entrées quand la colonne est réduite au rail d'icônes.
     3. Affichage du bouton « retour en haut » au-delà d'un seuil de défilement.

   Aucune manipulation du contenu de la documentation. Aucun style en ligne :
   tout passe par des classes lues dans layout.css.
   ========================================================================== */
(function () {
  'use strict';

  var KEY        = 'urdfs-doc-nav-collapsed';
  var SCROLL_MIN = 400;   // px avant apparition du bouton de retour en haut

  var CHEVRON =
    '<svg viewBox="0 0 24 24" aria-hidden="true">' +
    '<path d="M15.4 7.4 14 6l-6 6 6 6 1.4-1.4-4.6-4.6z"/></svg>';

  /* ------------------------------------------------------------------------
     1. Repli de la colonne
     --------------------------------------------------------------------- */

  // Restauré avant le premier rendu, pour éviter le clignotement.
  try {
    if (localStorage.getItem(KEY) === '1') {
      document.body.classList.add('urdfs-nav-collapsed');
    }
  } catch (e) { /* stockage indisponible : on reste déployé */ }

  function syncToggle(btn) {
    var collapsed = document.body.classList.contains('urdfs-nav-collapsed');
    var text = collapsed ? 'Déployer le menu' : 'Réduire le menu';
    btn.title = text;
    btn.setAttribute('aria-label', text);
    btn.setAttribute('aria-expanded', collapsed ? 'false' : 'true');
  }

  function mountToggle() {
    var host = document.querySelector('.md-sidebar--primary .md-sidebar__inner');
    if (!host || host.querySelector('#urdfs-nav-toggle')) { return; }

    var btn = document.createElement('button');
    btn.id = 'urdfs-nav-toggle';
    btn.type = 'button';
    btn.innerHTML = CHEVRON + '<span class="urdfs-toggle-label">Réduire le menu</span>';
    syncToggle(btn);

    btn.addEventListener('click', function () {
      var collapsed = document.body.classList.toggle('urdfs-nav-collapsed');
      try { localStorage.setItem(KEY, collapsed ? '1' : '0'); } catch (e) {}
      syncToggle(btn);
    });

    host.appendChild(btn);
  }

  /* ------------------------------------------------------------------------
     2. Infobulles du rail réduit
     Le libellé est un nœud de texte : CSS ne peut pas le lire. On le recopie
     dans un attribut, que layout.css affiche via content: attr(...).
     --------------------------------------------------------------------- */

  function mountTooltips() {
    var links = document.querySelectorAll('.md-nav--primary .md-nav__item:not(.md-nav__item--section) > .md-nav__link');
    Array.prototype.forEach.call(links, function (link) {
      if (link.hasAttribute('data-urdfs-tip')) { return; }
      var label = link.querySelector('.md-ellipsis');
      var text  = (label ? label.textContent : link.textContent).trim();
      if (text) { link.setAttribute('data-urdfs-tip', text); }
    });
  }

  /* ------------------------------------------------------------------------
     3. Bouton « retour en haut »
     Material ne l'affiche qu'au défilement vers le haut, ce qui surprend.
     On pilote nous-mêmes : visible dès SCROLL_MIN, masqué en haut de page.
     --------------------------------------------------------------------- */

  var DESKTOP = '(min-width: 76.25em)';

  // Sur desktop, l'ossature est fixe : c'est .md-content qui défile, plus la
  // fenêtre. On lit donc le défilement là où il a réellement lieu.
  function scroller() {
    var content = document.querySelector('.md-content');
    if (content && window.matchMedia(DESKTOP).matches) { return content; }
    return null;
  }

  function scrollTop() {
    var el = scroller();
    return el ? el.scrollTop : window.scrollY;
  }

  var ticking = false;
  function onScroll() {
    if (ticking) { return; }
    ticking = true;
    requestAnimationFrame(function () {
      document.body.classList.toggle('urdfs-scrolled', scrollTop() > SCROLL_MIN);
      ticking = false;
    });
  }

  /* ------------------------------------------------------------------------
     4. Pied de page dans la colonne de contenu (desktop)
     L'ossature fixe empêche la fenêtre de défiler : laissé à sa place, le
     pied de page deviendrait inaccessible. On le rapatrie donc dans la
     colonne qui défile. Opération idempotente (rejouée après chaque
     navigation instantanée).
     --------------------------------------------------------------------- */

  function placeFooter() {
    var footer  = document.querySelector('.md-footer');
    var content = document.querySelector('.md-content');
    var container = document.querySelector('.md-container');
    if (!footer || !content || !container) { return; }

    if (window.matchMedia(DESKTOP).matches) {
      if (footer.parentElement !== content) { content.appendChild(footer); }
    } else if (footer.parentElement !== container) {
      container.appendChild(footer);   // retour à sa place sur petit écran
    }
  }

  /* Le bouton « retour en haut » de Material agit sur la FENÊTRE, qui ne
     défile plus. On intercepte le clic au niveau du document, en phase de
     capture : notre gestionnaire passe donc avant celui de Material, quel
     que soit le moment où il a été enregistré. */
  document.addEventListener('click', function (e) {
    var btn = e.target && e.target.closest ? e.target.closest('.md-top') : null;
    if (!btn) { return; }
    var el = scroller();
    if (!el) { return; }              // tablette/mobile : comportement natif
    e.preventDefault();
    e.stopPropagation();
    el.scrollTo({ top: 0, behavior: 'smooth' });
  }, true);

  /* Le défilement se produit dans .md-content : on y attache l'écoute. */
  function bindScroller() {
    var el = document.querySelector('.md-content');
    if (!el || el.hasAttribute('data-urdfs-scroll')) { return; }
    el.setAttribute('data-urdfs-scroll', '1');
    el.addEventListener('scroll', onScroll, { passive: true });
  }

  /* ------------------------------------------------------------------------
     5. Suivi du sommaire (scrollspy)
     Material surligne l'entrée courante en observant le défilement de la
     FENÊTRE. Comme ici c'est .md-content qui défile, son suivi ne se
     déclenche jamais et le sommaire reste figé. On le refait nous-mêmes :
     à chaque défilement, on cherche le dernier titre passé sous le haut de
     la zone de lecture et on surligne l'entrée correspondante.
     --------------------------------------------------------------------- */

  var spyTargets = [];
  var spyTicking = false;

  function refreshSpyTargets() {
    var links = document.querySelectorAll('.md-sidebar--secondary .md-nav__link[href*="#"]');
    spyTargets = [];
    Array.prototype.forEach.call(links, function (link) {
      var hash = link.getAttribute('href').split('#')[1];
      if (!hash) { return; }
      var target = document.getElementById(decodeURIComponent(hash));
      if (target) { spyTargets.push({ link: link, el: target }); }
    });
  }

  function updateSpy() {
    var el = scroller();
    if (!el || !spyTargets.length) { return; }

    // Ligne de déclenchement : 120 px sous le haut de la zone de lecture.
    var line = el.getBoundingClientRect().top + 120;
    var current = spyTargets[0];

    for (var i = 0; i < spyTargets.length; i++) {
      if (spyTargets[i].el.getBoundingClientRect().top <= line) {
        current = spyTargets[i];
      }
    }
    // En bas de course, on force la dernière entrée.
    if (el.scrollTop + el.clientHeight >= el.scrollHeight - 4) {
      current = spyTargets[spyTargets.length - 1];
    }

    for (var j = 0; j < spyTargets.length; j++) {
      spyTargets[j].link.classList.toggle(
        'md-nav__link--active', spyTargets[j] === current
      );
    }
  }

  function bindSpy() {
    refreshSpyTargets();
    updateSpy();

    var el = document.querySelector('.md-content');
    if (!el || el.hasAttribute('data-urdfs-spy')) { return; }
    el.setAttribute('data-urdfs-spy', '1');
    el.addEventListener('scroll', function () {
      if (spyTicking) { return; }
      spyTicking = true;
      requestAnimationFrame(function () { updateSpy(); spyTicking = false; });
    }, { passive: true });
  }

  /* ------------------------------------------------------------------------
     Montage
     --------------------------------------------------------------------- */

  function mount() {
    mountToggle();
    mountTooltips();
    placeFooter();
    bindScroller();
    bindSpy();
    onScroll();
  }

  window.addEventListener('scroll', onScroll, { passive: true });
  // Le passage desktop <-> tablette change le conteneur de défilement.
  window.addEventListener('resize', function () { placeFooter(); onScroll(); });

  // document$ rejoue le montage après chaque navigation instantanée
  // (le DOM de la colonne et du contenu est remplacé, pas le <body>).
  if (window.document$ && typeof window.document$.subscribe === 'function') {
    window.document$.subscribe(mount);
  } else if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', mount);
  } else {
    mount();
  }
})();
