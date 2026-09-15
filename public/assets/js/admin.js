document.querySelector('[data-sidebar-toggle]')?.addEventListener('click', event => {
    const sidebar = document.querySelector('#admin-sidebar');
    const open = sidebar?.classList.toggle('is-open') ?? false;
    event.currentTarget.setAttribute('aria-expanded', String(open));
});

document.querySelectorAll('[data-confirm]').forEach(button => {
    button.addEventListener('click', event => {
        if (!window.confirm(button.dataset.confirm || 'هل أنت متأكد؟')) {
            event.preventDefault();
        }
    });
});

document.querySelectorAll('[data-auto-submit]').forEach(select => {
    select.addEventListener('change', () => select.form?.requestSubmit());
});

document.querySelectorAll('[data-auto-submit]').forEach(select => {
    select.addEventListener('change', () => select.form?.requestSubmit());
});

document.querySelectorAll('[data-confirm]').forEach(button => {
    button.addEventListener('click', event => {
        if (!window.confirm(button.dataset.confirm || 'هل أنت متأكد؟')) {
            event.preventDefault();
        }
    });
});
