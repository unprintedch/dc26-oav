/**
 * Scroll Behavior Module
 * 
 * Gère le comportement du header (shrink + hide/show au scroll)
 * et déclenche des animations d'apparition pour les éléments au scroll.
 * 
 * @module scroll-behavior
 * @example
 * import scrollBehavior from './scroll-behavior.js';
 * scrollBehavior({
 *   headerSelector: '.site-header',
 *   shrinkAt: 50,
 *   // ... autres options
 * });
 */

/**
 * Options par défaut du module
 */
const defaultOptions = {
  // Header
  headerSelector: '#menu-container', // Utilise le sélecteur du thème
  shrinkClass: 'is-shrunk',
  hideClass: 'is-hidden',
  showClass: 'is-visible',
  shrinkAt: 50, // px de scroll pour activer le shrink
  hideThreshold: 100, // px de scroll pour cacher le header
  scrollUpThreshold: 10, // px de scroll vers le haut pour réafficher
  
  // Scroll animations
  animatedSelector: '[data-animate-on-scroll]',
  animationClass: 'is-animated',
  rootMargin: '0px 0px -100px 0px', // Déclenche quand l'élément est à 100px du viewport
  animationThreshold: 0.1,
  
  // Performance
  throttleDelay: 16, // ~60fps avec requestAnimationFrame
  respectsReducedMotion: true,

  // Parallax background layer
  parallaxSelector: '#bg-pattern-layer',
  parallaxFactor: 0.2, // 0.2 = 20% de la vitesse de défilement
};

/**
 * Vérifie si l'utilisateur préfère les animations réduites
 */
function prefersReducedMotion() {
  return window.matchMedia('(prefers-reduced-motion: reduce)').matches;
}

/**
 * Throttle pour optimiser les événements de scroll
 */
function throttle(func, delay) {
  let timeoutId;
  let lastExecTime = 0;
  return function (...args) {
    const currentTime = Date.now();
    
    if (currentTime - lastExecTime > delay) {
      func.apply(this, args);
      lastExecTime = currentTime;
    } else {
      clearTimeout(timeoutId);
      timeoutId = setTimeout(() => {
        func.apply(this, args);
        lastExecTime = Date.now();
      }, delay - (currentTime - lastExecTime));
    }
  };
}

/**
 * Classe pour gérer le comportement du header
 */
class HeaderBehavior {
  constructor(header, options) {
    this.header = header;
    this.options = options;
    this.lastScrollY = window.scrollY;
    this.isHidden = false;
    this.isShrunk = false;
    
    // Hauteurs du header
    this.normalHeight = null;
    this.shrunkHeight = null;
    
    // Bind pour garder le contexte
    this.handleScroll = this.handleScroll.bind(this);
    
    // Écouteur de scroll throttlé
    this.throttledScroll = throttle(
      this.handleScroll,
      options.throttleDelay
    );
  }
  
  /**
   * Initialise le header behavior
   */
  init() {
    if (!this.header) return;
    
    // Calculer les hauteurs du header
    this.calculateHeights();
    
    // Écouter le scroll (passive pour la performance)
    window.addEventListener('scroll', this.throttledScroll, { passive: true });
    
    // Écouter le redimensionnement pour recalculer les hauteurs
    window.addEventListener('resize', () => {
      this.calculateHeights();
      this.updateBodyPadding();
    }, { passive: true });
    
    // Vérifier l'état initial et mettre à jour le padding
    this.handleScroll();
    this.updateBodyPadding();
  }
  
  /**
   * Calcule les hauteurs du header
   */
  calculateHeights() {
    // Récupérer la hauteur normale et shrunk sans provoquer de flash visuel
    const wasShrunk = this.header.classList.contains(this.options.shrinkClass);
    const prevVisibility = this.header.style.visibility;
    const prevTransition = this.header.style.transition;

    // Empêcher tout clignotement durant la mesure
    this.header.style.visibility = 'hidden';
    this.header.style.transition = 'none';

    // Hauteur normale
    this.header.classList.remove(this.options.shrinkClass);
    this.normalHeight = this.header.offsetHeight;

    // Hauteur shrunk
    this.header.classList.add(this.options.shrinkClass);
    this.shrunkHeight = this.header.offsetHeight;

    // Restaurer l'état initial
    if (!wasShrunk) {
      this.header.classList.remove(this.options.shrinkClass);
    }

    // Restaurer styles
    this.header.style.visibility = prevVisibility;
    this.header.style.transition = prevTransition;
  }
  
  /**
   * Met à jour le padding-top du body selon l'état du header
   */
  updateBodyPadding() {
    if (!document.body) return;
    
    let paddingTop = 0;
    
    if (!this.isHidden) {
      // Si le header est visible, utiliser la hauteur appropriée
      paddingTop = this.isShrunk ? this.shrunkHeight : this.normalHeight;
    }
    
    // Appliquer le padding avec une transition CSS
    document.body.style.paddingTop = `${paddingTop}px`;
  }
  
  /**
   * Gère le scroll pour le header
   */
  handleScroll() {
    const currentScrollY = window.scrollY;
    const scrollDelta = currentScrollY - this.lastScrollY;
    
    // Shrink
    if (currentScrollY >= this.options.shrinkAt && !this.isShrunk) {
      this.shrink();
    } else if (currentScrollY < this.options.shrinkAt && this.isShrunk) {
      this.unshrink();
    }
    
    // Hide/Show
    if (currentScrollY >= this.options.hideThreshold) {
      if (scrollDelta > 0 && !this.isHidden) {
        // Scroll down - cacher
        this.hide();
      } else if (scrollDelta < -this.options.scrollUpThreshold && this.isHidden) {
        // Scroll up - montrer
        this.show();
      }
    }
    
    // Réinitialiser si en haut de page
    if (currentScrollY < this.options.hideThreshold) {
      if (this.isHidden) this.show();
    }
    
    this.lastScrollY = currentScrollY;
  }
  
  /**
   * Réduit la taille du header
   */
  shrink() {
    this.header.classList.add(this.options.shrinkClass);
    this.isShrunk = true;
    this.updateBodyPadding();
  }
  
  /**
   * Restaure la taille normale du header
   */
  unshrink() {
    this.header.classList.remove(this.options.shrinkClass);
    this.isShrunk = false;
    this.updateBodyPadding();
  }
  
  /**
   * Cache le header
   */
  hide() {
    this.header.classList.add(this.options.hideClass);
    this.header.classList.remove(this.options.showClass);
    this.isHidden = true;
    this.updateBodyPadding();
  }
  
  /**
   * Affiche le header
   */
  show() {
    // Mettre à jour d'abord le padding pour éviter un "bump" visuel,
    // puis afficher le header.
    this.isHidden = false;
    this.updateBodyPadding();
    this.header.classList.remove(this.options.hideClass);
    this.header.classList.add(this.options.showClass);
  }
  
  /**
   * Nettoie les écouteurs
   */
  destroy() {
    window.removeEventListener('scroll', this.throttledScroll);
  }
}

/**
 * Classe pour gérer les animations au scroll
 */
class ScrollAnimations {
  constructor(selector, options) {
    this.options = options;
    this.elements = document.querySelectorAll(selector);
    
    if (this.elements.length === 0) return;
    
    // Vérifier les préférences de mouvement
    if (options.respectsReducedMotion && prefersReducedMotion()) {
      // Appliquer immédiatement la classe si réduit mouvement
      this.elements.forEach(el => el.classList.add(options.animationClass));
      return;
    }
    
    this.initObserver();
  }
  
  /**
   * Initialise l'IntersectionObserver
   */
  initObserver() {
    this.observer = new IntersectionObserver(
      this.handleIntersection.bind(this),
      {
        rootMargin: this.options.rootMargin,
        threshold: this.options.animationThreshold,
      }
    );
    
    // Observer tous les éléments
    this.elements.forEach(el => {
      this.observer.observe(el);
    });
  }
  
  /**
   * Gère l'intersection des éléments
   */
  handleIntersection(entries) {
    entries.forEach(entry => {
      if (entry.isIntersecting) {
        entry.target.classList.add(this.options.animationClass);
        // Optionnel : ne plus observer une fois animé
        // this.observer.unobserve(entry.target);
      }
    });
  }
  
  /**
   * Nettoie l'observer
   */
  destroy() {
    if (this.observer) {
      this.elements.forEach(el => {
        this.observer.unobserve(el);
      });
    }
  }
}

/**
 * Parallax controller pour un calque de fond
 */
class ParallaxLayer {
  constructor(selector, options) {
    this.el = document.querySelector(selector);
    this.factor = options.parallaxFactor;
    this.enabled = !!this.el && !(
      options.respectsReducedMotion && prefersReducedMotion()
    );
    this.ticking = false;
    this.lastY = 0;

    if (!this.enabled) return;

    // Liaison
    this.onScroll = this.onScroll.bind(this);
    this.update = this.update.bind(this);
    this.updateHeight = this.updateHeight.bind(this);

    // Mettre à jour la hauteur pour couvrir tout le document
    this.updateHeight();

    // Initial
    this.onScroll();
    window.addEventListener('scroll', this.onScroll, { passive: true });
    window.addEventListener('resize', () => {
      this.updateHeight();
      this.onScroll();
    }, { passive: true });

    // Observer les changements de taille du document
    if (window.ResizeObserver) {
      this.resizeObserver = new ResizeObserver(() => {
        this.updateHeight();
      });
      this.resizeObserver.observe(document.body);
      if (document.documentElement) {
        this.resizeObserver.observe(document.documentElement);
      }
    }
  }

  /**
   * Met à jour la hauteur du layer pour couvrir toute la page
   */
  updateHeight() {
    if (!this.el) return;
    
    // Calculer la hauteur totale du document
    const docHeight = Math.max(
      document.body.scrollHeight,
      document.body.offsetHeight,
      document.documentElement.clientHeight,
      document.documentElement.scrollHeight,
      document.documentElement.offsetHeight
    );
    
    // Ajouter un padding pour le parallax (le layer doit être plus haut)
    const parallaxExtra = docHeight * (1 - this.factor);
    const totalHeight = docHeight + parallaxExtra;
    
    this.el.style.height = `${totalHeight}px`;
  }

  onScroll() {
    this.lastY = window.scrollY || window.pageYOffset;
    if (!this.ticking) {
      this.ticking = true;
      requestAnimationFrame(this.update);
    }
  }

  update() {
    const translateY = Math.round(this.lastY * this.factor);
    this.el.style.transform = `translate(-50%, ${-translateY}px)`;
    this.ticking = false;
  }

  destroy() {
    if (!this.enabled) return;
    window.removeEventListener('scroll', this.onScroll);
    window.removeEventListener('resize', this.onScroll);
    if (this.resizeObserver) {
      this.resizeObserver.disconnect();
    }
  }
}

/**
 * Fonction principale du module
 * 
 * @param {Object} options - Options de configuration
 * @param {string} [options.headerSelector] - Sélecteur du header
 * @param {string} [options.shrinkClass] - Classe CSS pour le shrink
 * @param {string} [options.hideClass] - Classe CSS pour cacher
 * @param {string} [options.showClass] - Classe CSS pour afficher
 * @param {number} [options.shrinkAt] - Pixels de scroll pour shrink
 * @param {number} [options.hideThreshold] - Pixels pour cacher
 * @param {number} [options.scrollUpThreshold] - Pixels pour réafficher
 * @param {string} [options.animatedSelector] - Sélecteur des éléments à animer
 * @param {string} [options.animationClass] - Classe CSS d'animation
 * @param {string} [options.rootMargin] - Root margin pour IntersectionObserver
 * @param {number} [options.animationThreshold] - Threshold pour IntersectionObserver
 * @param {number} [options.throttleDelay] - Délai pour le throttle (ms)
 * @param {boolean} [options.respectsReducedMotion] - Respecter prefers-reduced-motion
 */
export default function scrollBehavior(options = {}) {
  // Fusionner les options avec les défauts
  const config = { ...defaultOptions, ...options };
  
  // Instances
  const instances = {
    header: null,
    animations: null,
    parallax: null,
  };
  
  // Attendre que le DOM soit prêt
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }
  
  /**
   * Initialise le module
   */
  function init() {
    // Header behavior
    const headerElement = document.querySelector(config.headerSelector);
    if (headerElement) {
      instances.header = new HeaderBehavior(headerElement, config);
      instances.header.init();
    }
    
    // Scroll animations
    instances.animations = new ScrollAnimations(
      config.animatedSelector,
      config
    );

    // Parallax background layer
    instances.parallax = new ParallaxLayer(
      config.parallaxSelector,
      config
    );
  }
  
  // Retourner l'API publique
  return {
    /**
     * Détruit toutes les instances et nettoie les écouteurs
     */
    destroy() {
      if (instances.header) instances.header.destroy();
      if (instances.animations) instances.animations.destroy();
      if (instances.parallax) instances.parallax.destroy();
    },
    
    /**
     * Réinitialise le module
     */
    reinit() {
      this.destroy();
      init();
    },
  };
}

