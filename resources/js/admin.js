import 'bootstrap';

const body = document.body;
const sidebar = document.getElementById('sidebar');

// Mobile drawer: open with the hamburger, close with the backdrop, Escape or by choosing a page.
const setOpen = (open) => body.classList.toggle('sb-open', open);
document.querySelectorAll('[data-sidebar-toggle]').forEach((b) => b.addEventListener('click', () => setOpen(!body.classList.contains('sb-open'))));
document.querySelectorAll('[data-sidebar-close]').forEach((b) => b.addEventListener('click', () => setOpen(false)));
document.addEventListener('keydown', (e) => e.key === 'Escape' && setOpen(false));
sidebar?.querySelectorAll('a[href]:not([data-bs-toggle])').forEach((a) => a.addEventListener('click', () => setOpen(false)));

// "Find a page": filters the menu as you type, opening groups that contain a match.
const filter = document.getElementById('sb-filter');
if (filter && sidebar) {
    const nav = document.getElementById('sb-nav');
    const empty = nav.querySelector('.sb-empty');
    const groups = [...nav.querySelectorAll('.sb-group')];
    const wasOpen = new Map(groups.map((g) => [g, g.querySelector('.collapse').classList.contains('show')]));

    filter.addEventListener('input', () => {
        const q = filter.value.trim().toLowerCase();
        let shown = 0;

        nav.querySelectorAll(':scope > .sb-link').forEach((a) => {
            const hit = !q || a.textContent.toLowerCase().includes(q);
            a.classList.toggle('d-none', !hit);
            shown += hit ? 1 : 0;
        });

        groups.forEach((g) => {
            const panel = g.querySelector('.collapse');
            const links = [...panel.querySelectorAll('.sb-link')];
            const titleHit = !q || g.querySelector(':scope > .sb-link').textContent.toLowerCase().includes(q);
            let any = false;
            links.forEach((a) => {
                const hit = !q || titleHit || a.textContent.toLowerCase().includes(q);
                a.classList.toggle('d-none', !hit);
                any = any || hit;
            });
            g.classList.toggle('d-none', !any);
            shown += any ? 1 : 0;
            // Show matches without the animation; put the menu back as it was when the box is cleared.
            panel.classList.toggle('show', q ? any : wasOpen.get(g));
        });

        // A section heading stays only if something under it is visible.
        let heading = null;
        let hasVisible = false;
        const closeHeading = () => heading && heading.classList.toggle('d-none', !!q && !hasVisible);
        [...nav.children].forEach((el) => {
            if (el.classList.contains('sb-heading')) {
                closeHeading();
                heading = el;
                hasVisible = false;
            } else if (!el.classList.contains('d-none') && !el.classList.contains('sb-empty')) {
                hasVisible = true;
            }
        });
        closeHeading();

        empty.classList.toggle('d-none', !q || shown > 0);
    });
}
