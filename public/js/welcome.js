document.addEventListener('DOMContentLoaded', () => {
    const revealNodes = Array.from(document.querySelectorAll('[data-reveal]'));
    const countNodes = Array.from(document.querySelectorAll('[data-count]'));
    const rotatingWords = Array.from(document.querySelectorAll('.hero-title-word'));

    const revealObserver = new IntersectionObserver((entries) => {
        entries.forEach((entry) => {
            if (!entry.isIntersecting) {
                return;
            }

            entry.target.classList.add('is-visible');
            revealObserver.unobserve(entry.target);
        });
    }, {
        threshold: 0.18,
        rootMargin: '0px 0px -40px 0px',
    });

    revealNodes.forEach((node, index) => {
        node.style.transitionDelay = `${Math.min(index * 55, 260)}ms`;
        revealObserver.observe(node);
    });

    const animateCount = (node) => {
        const target = Number(node.dataset.count || 0);
        const suffix = node.dataset.suffix || '';
        const duration = 1100;
        const start = performance.now();

        const step = (timestamp) => {
            const progress = Math.min((timestamp - start) / duration, 1);
            const eased = 1 - Math.pow(1 - progress, 3);
            const value = target * eased;
            const formatted = Number.isInteger(target)
                ? Math.round(value).toString()
                : value.toFixed(1);

            node.textContent = `${formatted}${suffix}`;

            if (progress < 1) {
                window.requestAnimationFrame(step);
            } else {
                node.textContent = `${target}${suffix}`;
            }
        };

        window.requestAnimationFrame(step);
    };

    const counterObserver = new IntersectionObserver((entries) => {
        entries.forEach((entry) => {
            if (!entry.isIntersecting || entry.target.dataset.counted === 'true') {
                return;
            }

            entry.target.dataset.counted = 'true';
            animateCount(entry.target);
            counterObserver.unobserve(entry.target);
        });
    }, {
        threshold: 0.35,
    });

    countNodes.forEach((node) => counterObserver.observe(node));

    if (rotatingWords.length > 1) {
        let activeIndex = Math.max(0, rotatingWords.findIndex((node) => node.classList.contains('is-active')));

        const activateWord = (index) => {
            rotatingWords.forEach((node, nodeIndex) => {
                const isActive = nodeIndex === index;
                node.classList.toggle('is-active', isActive);

                if (isActive) {
                    const color = node.dataset.color || '#2563eb';
                    node.style.setProperty('--word-color', color);
                }
            });
        };

        activateWord(activeIndex);

        window.setInterval(() => {
            activeIndex = (activeIndex + 1) % rotatingWords.length;
            activateWord(activeIndex);
        }, 1900);
    }
});
