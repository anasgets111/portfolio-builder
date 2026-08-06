const reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

const revealElements = document.querySelectorAll('[data-reveal]');

if (!reducedMotion && 'IntersectionObserver' in window) {
    const revealObserver = new IntersectionObserver((entries, observer) => {
        entries.forEach((entry) => {
            if (!entry.isIntersecting) {
                return;
            }

            entry.target.classList.remove('is-reveal-pending');
            entry.target.classList.add('is-revealed');
            observer.unobserve(entry.target);
        });
    }, { threshold: 0.12 });

    revealElements.forEach((element) => {
        element.classList.add('is-reveal-pending');
        revealObserver.observe(element);
    });
}

const navigationLinks = [...document.querySelectorAll('[data-nav-link]')];
const observedSections = document.querySelectorAll('[data-observed-section]');

const selectNavigationLink = (sectionId) => {
    navigationLinks.forEach((link) => {
        if (link.getAttribute('href') === `#${sectionId}`) {
            link.setAttribute('aria-current', 'location');
        } else {
            link.removeAttribute('aria-current');
        }
    });
};

if ('IntersectionObserver' in window) {
    const sectionObserver = new IntersectionObserver((entries) => {
        const visibleSection = entries
            .filter((entry) => entry.isIntersecting)
            .sort((firstEntry, secondEntry) => secondEntry.intersectionRatio - firstEntry.intersectionRatio)[0];

        if (visibleSection) {
            selectNavigationLink(visibleSection.target.id);
        }
    }, {
        rootMargin: '-20% 0px -55% 0px',
        threshold: [0.05, 0.2, 0.5],
    });

    observedSections.forEach((section) => sectionObserver.observe(section));
}

navigationLinks.forEach((link) => {
    link.addEventListener('click', () => {
        selectNavigationLink(link.getAttribute('href').slice(1));
    });
});

const dialogTriggers = new WeakMap();

document.querySelectorAll('[data-dialog-open]').forEach((trigger) => {
    trigger.addEventListener('click', (event) => {
        const dialog = document.getElementById(trigger.dataset.dialogOpen);

        if (!(dialog instanceof HTMLDialogElement)) {
            return;
        }

        if (trigger instanceof HTMLAnchorElement) {
            event.preventDefault();
        }

        dialogTriggers.set(dialog, trigger);
        document.documentElement.classList.add('is-dialog-open');
        dialog.showModal();
    });
});

document.querySelectorAll('.project-dialog').forEach((dialog) => {
    dialog.querySelector('[data-dialog-close]')?.addEventListener('click', () => dialog.close());
    dialog.querySelectorAll('[data-dialog-navigate]').forEach((link) => {
        link.addEventListener('click', () => dialog.close());
    });

    dialog.addEventListener('click', (event) => {
        if (event.target === dialog) {
            dialog.close();
        }
    });

    dialog.addEventListener('close', () => {
        if (!document.querySelector('.project-dialog[open]')) {
            document.documentElement.classList.remove('is-dialog-open');
        }

        dialogTriggers.get(dialog)?.focus();
    });
});
