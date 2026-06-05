// ── Alert auto-dismiss (8s) ────────────────────────────────────
setTimeout(() => {
    document.querySelectorAll('.alert-dismissible').forEach(el => {
        el.classList.remove('show');
        setTimeout(() => el.remove(), 300);
    });
}, 8000);

// ── Avatar preview ─────────────────────────────────────────────
document.addEventListener('DOMContentLoaded', () => {
    const avatarInput = document.getElementById('avatarInput');
    const avatarPreview = document.getElementById('avatarPreview');
    if (avatarInput && avatarPreview) {
        avatarInput.addEventListener('change', function () {
            const file = this.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = e => {
                    avatarPreview.src = e.target.result;
                    avatarPreview.style.display = 'block';
                };
                reader.readAsDataURL(file);
            }
        });
    }
});

// ── CSRF token for fetch ───────────────────────────────────────
const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;

async function postJson(url, data) {
    try {
        const res = await fetch(url, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken },
            body: JSON.stringify(data),
        });
        if (!res.ok) throw new Error(`HTTP ${res.status}`);
        return res.json();
    } catch (err) {
        console.error('postJson error:', err);
        return { error: err.message };
    }
}

// ── Password strength indicator ────────────────────────────────
document.addEventListener('DOMContentLoaded', () => {
    const pwInputs = document.querySelectorAll('[data-pw-strength]');
    pwInputs.forEach(input => {
        const container = input.closest('[data-pw-container]');
        if (!container) return;
        const segments = container.querySelectorAll('[data-pw-segment]');
        const criteria = container.querySelector('[data-pw-criteria]');

        if (!segments.length) return;

        input.addEventListener('input', () => {
            const val = input.value;
            const hasUpper = /[A-Z]/.test(val);
            const hasLower = /[a-z]/.test(val);
            const hasDigit = /\d/.test(val);
            const hasSpecial = /[^A-Za-z0-9]/.test(val);
            const isLong = val.length >= 8;

            const score = [isLong, hasUpper && hasLower, hasDigit, hasSpecial].filter(Boolean).length;

            segments.forEach((seg, i) => {
                seg.className = 'pw-segment';
                if (i < score) {
                    seg.classList.add('active');
                    if (score <= 1) seg.classList.add('weak');
                    else if (score <= 2) seg.classList.add('medium');
                    else seg.classList.add('strong');
                }
            });

            if (criteria) {
                const checks = [
                    [isLong, 'Au moins 8 caractères'],
                    [hasUpper && hasLower, 'Majuscule & minuscule'],
                    [hasDigit, 'Au moins 1 chiffre'],
                    [hasSpecial, 'Caractère spécial'],
                ];
                criteria.innerHTML = checks.map(([ok, txt]) =>
                    `<span class="${ok ? 'check' : 'cross'} me-2"><i class="fas fa-${ok ? 'check' : 'times'}-circle"></i> ${txt}</span>`
                ).join('');
            }
        });
    });

    // Password match
    const matchInputs = document.querySelectorAll('[data-pw-match]');
    matchInputs.forEach(input => {
        const targetId = input.dataset.pwMatch;
        const target = document.getElementById(targetId);
        const feedback = input.closest('[data-pw-container]')?.querySelector('[data-pw-match-feedback]');
        if (!target) return;

        const check = () => {
            const match = input.value === target.value && input.value.length > 0;
            if (feedback) {
                feedback.innerHTML = match
                    ? '<span class="text-green"><i class="fas fa-check-circle"></i> Mots de passe identiques</span>'
                    : '<span class="text-red"><i class="fas fa-times-circle"></i> Mots de passe différents</span>';
            }
        };
        input.addEventListener('input', check);
        target.addEventListener('input', check);
    });

    // Toggle password visibility
    document.querySelectorAll('[data-toggle-pw]').forEach(btn => {
        btn.addEventListener('click', () => {
            const target = document.getElementById(btn.dataset.togglePw);
            if (target) {
                const isPassword = target.type === 'password';
                target.type = isPassword ? 'text' : 'password';
                btn.innerHTML = isPassword
                    ? '<i class="fas fa-eye-slash"></i>'
                    : '<i class="fas fa-eye"></i>';
            }
        });
    });
});

// ── Partage et copie du lien d'invitation ──────────────────────────────────────
window.copyToClipboard = async function(text) {
    try {
        if (navigator.clipboard && navigator.clipboard.writeText) {
            await navigator.clipboard.writeText(text);
        } else {
            const textarea = document.createElement('textarea');
            textarea.value = text;
            textarea.style.position = 'fixed';
            textarea.style.left = '-9999px';
            document.body.appendChild(textarea);
            textarea.focus();
            textarea.select();
            document.execCommand('copy');
            document.body.removeChild(textarea);
        }
        alert('Copié dans le presse-papiers !');
    } catch (error) {
        console.error('Impossible de copier le texte :', error);
        alert('Échec de la copie. Veuillez copier manuellement.');
    }
};

window.shareInvite = async function(title, code, url) {
    const shareData = {
        title: title,
        text: `Rejoins ma tontine ${title} avec le code ${code}.`,
        url: url,
    };
    if (navigator.share) {
        try {
            await navigator.share(shareData);
        } catch (error) {
            console.warn('Partage annulé ou impossible', error);
        }
        return;
    }
    copyToClipboard(`${shareData.text} ${shareData.url}`);
};
