const reducedMotion = document.documentElement.dataset.motion === 'off'
    || window.matchMedia('(prefers-reduced-motion: reduce)').matches;

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

const analyticsEndpoint = document.querySelector('meta[name="analytics-endpoint"]')?.content;
const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;
const analyticsDisabled = navigator.doNotTrack === '1' || navigator.globalPrivacyControl === true;

if (analyticsEndpoint && csrfToken && !analyticsDisabled) {
    const trackAnalyticsEvent = (name, target = null, value = null) => {
        fetch(analyticsEndpoint, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
            },
            keepalive: true,
            body: JSON.stringify({ name, target, value }),
        }).catch(() => {});
    };

    document.addEventListener('click', (event) => {
        const trackedElement = event.target instanceof Element
            ? event.target.closest('[data-analytics-event]')
            : null;

        if (!trackedElement) {
            return;
        }

        trackAnalyticsEvent(
            trackedElement.dataset.analyticsEvent,
            trackedElement.dataset.analyticsTarget ?? null,
        );
    });

    const visibleSections = new Set();
    const viewedSections = new Set();
    const sectionStartedAt = new Map();

    const startSectionEngagement = (sectionId) => {
        if (!viewedSections.has(sectionId)) {
            viewedSections.add(sectionId);
            trackAnalyticsEvent('section_viewed', sectionId);
        }

        if (!sectionStartedAt.has(sectionId)) {
            sectionStartedAt.set(sectionId, window.performance.now());
        }
    };

    const finishSectionEngagement = (sectionId) => {
        const startedAt = sectionStartedAt.get(sectionId);

        if (startedAt === undefined) {
            return;
        }

        sectionStartedAt.delete(sectionId);
        const duration = Math.round(window.performance.now() - startedAt);

        if (duration >= 1000) {
            trackAnalyticsEvent('section_engaged', sectionId, duration);
        }
    };

    if ('IntersectionObserver' in window) {
        const analyticsSectionObserver = new IntersectionObserver((entries) => {
            entries.forEach((entry) => {
                if (entry.isIntersecting) {
                    visibleSections.add(entry.target.id);
                    startSectionEngagement(entry.target.id);
                } else {
                    visibleSections.delete(entry.target.id);
                    finishSectionEngagement(entry.target.id);
                }
            });
        }, {
            rootMargin: '-20% 0px -20% 0px',
            threshold: 0.01,
        });

        observedSections.forEach((section) => analyticsSectionObserver.observe(section));
    }

    document.addEventListener('visibilitychange', () => {
        if (document.visibilityState === 'hidden') {
            [...sectionStartedAt.keys()].forEach(finishSectionEngagement);
        } else {
            visibleSections.forEach(startSectionEngagement);
        }
    });

    window.addEventListener('pagehide', () => {
        [...sectionStartedAt.keys()].forEach(finishSectionEngagement);
    });

    if (window.matchMedia('(hover: hover)').matches) {
        const hoveredProjects = new Set();

        document.querySelectorAll('[data-analytics-hover]').forEach((element) => {
            const target = element.dataset.analyticsHover;
            let hoverTimer;

            element.addEventListener('pointerenter', () => {
                if (hoveredProjects.has(target)) {
                    return;
                }

                hoverTimer = window.setTimeout(() => {
                    hoveredProjects.add(target);
                    trackAnalyticsEvent('project_hovered', target);
                }, 1000);
            });

            element.addEventListener('pointerleave', () => {
                window.clearTimeout(hoverTimer);
            });
        });
    }
}
