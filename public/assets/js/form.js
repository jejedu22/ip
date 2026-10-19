(() => {
    'use strict';

    // Lignes répétables (mineurs, autres enfants)
    document.querySelectorAll('[data-collection]').forEach((box) => {
        let index = parseInt(box.dataset.index || '0', 10);
        const min = parseInt(box.dataset.min || '0', 10);
        const addBtn = box.nextElementSibling; // bouton « Ajouter » juste après la liste

        const refresh = () => {
            const rows = box.querySelectorAll('[data-repeat-row]');
            rows.forEach((row) => {
                const btn = row.querySelector('[data-remove-row]');
                if (btn) btn.hidden = rows.length <= min;
            });
        };

        addBtn?.addEventListener('click', () => {
            const html = box.dataset.prototype.replace(/__index__/g, String(index++));
            const tpl = document.createElement('template');
            tpl.innerHTML = html.trim();
            box.appendChild(tpl.content);
            refresh();
            box.querySelector('[data-repeat-row]:last-of-type input')?.focus();
        });

        box.addEventListener('click', (e) => {
            const btn = e.target.closest('[data-remove-row]');
            if (!btn) return;
            btn.closest('[data-repeat-row]').remove();
            refresh();
        });

        refresh();
    });

    // Anonymat : l'identité de l'informateur n'est pas demandée
    const anon = document.querySelector('[data-anonymat]');
    const identite = document.querySelector('[data-identite]');
    if (anon && identite) {
        const sync = () => {
            identite.hidden = anon.checked;
            identite.querySelectorAll('input, textarea').forEach((el) => { el.disabled = anon.checked; });
        };
        anon.addEventListener('change', sync);
        sync();
    }

    // Rubrique 7 : afficher « Qui ? » ou « Pourquoi ? » selon la réponse
    const radios = document.querySelectorAll('input[name$="[parents_informes]"]');
    const blocks = document.querySelectorAll('[data-if]');
    if (radios.length) {
        const sync = () => {
            const checked = document.querySelector('input[name$="[parents_informes]"]:checked');
            blocks.forEach((b) => { b.hidden = !checked || b.dataset.if !== checked.value; });
        };
        radios.forEach((r) => r.addEventListener('change', sync));
        sync();
    }

    // Effacer la saisie
    // Captcha : nouveau code à la demande
    const cImg = document.querySelector('[data-captcha-img]');
    const cId = document.querySelector('[data-captcha-id]');
    document.querySelector('[data-captcha-reload]')?.addEventListener('click', () => {
        if (!cImg || !cId) return;
        const id = Array.from(crypto.getRandomValues(new Uint8Array(16)), (b) => b.toString(16).padStart(2, '0')).join('');
        cId.value = id;
        cImg.src = cImg.src.replace(/[a-f0-9]{32}$/, id);
        document.getElementById('captcha_code')?.focus();
    });

    document.querySelector('[data-reset]')?.addEventListener('click', (e) => {
        if (!window.confirm('Effacer toute la saisie ?')) e.preventDefault();
    });
})();
