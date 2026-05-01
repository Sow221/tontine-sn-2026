/* TontineSN — tontine.js */

// ── OTP Input auto-focus ───────────────────────────────────────────────────

document.addEventListener('DOMContentLoaded', () => {
    const digits  = document.querySelectorAll('.otp-digit');
    const hidden  = document.getElementById('otp-hidden');

    if (digits.length) {
        digits[0].focus();

        digits.forEach((input, i) => {
            input.addEventListener('input', () => {
                input.value = input.value.replace(/\D/, '');
                if (input.value && i < digits.length - 1) digits[i + 1].focus();
                syncHidden();
            });

            input.addEventListener('keydown', (e) => {
                if (e.key === 'Backspace' && !input.value && i > 0) {
                    digits[i - 1].focus();
                }
            });

            input.addEventListener('paste', (e) => {
                e.preventDefault();
                const pasted = (e.clipboardData || window.clipboardData).getData('text').replace(/\D/g, '');
                [...pasted].slice(0, 6).forEach((char, j) => {
                    if (digits[j]) digits[j].value = char;
                });
                syncHidden();
                const next = Math.min(pasted.length, digits.length - 1);
                digits[next].focus();
            });
        });

        function syncHidden() {
            if (hidden) hidden.value = [...digits].map(d => d.value).join('');
        }
    }
});

// ── Alpine.js OTP countdown ────────────────────────────────────────────────

function otpTimer() {
    return {
        countdown: 60,
        init() {
            const interval = setInterval(() => {
                if (this.countdown > 0) this.countdown--;
                else clearInterval(interval);
            }, 1000);
        }
    };
}

// ── CSRF token for fetch ───────────────────────────────────────────────────

const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;

async function postJson(url, data) {
    const res = await fetch(url, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken },
        body: JSON.stringify(data),
    });
    return res.json();
}

// ── Auto-dismiss alerts ────────────────────────────────────────────────────

setTimeout(() => {
    document.querySelectorAll('.alert-dismissible').forEach(el => {
        el.classList.remove('show');
        setTimeout(() => el.remove(), 300);
    });
}, 4000);
