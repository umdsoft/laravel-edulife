import { describe, expect, it } from 'vitest';
import { readFileSync } from 'node:fs';
import { fileURLToPath } from 'node:url';

const read = (path) => readFileSync(fileURLToPath(new URL(`../../../${path}`, import.meta.url)), 'utf8');

// agent L: website login through the Telegram bot (docs/auth/TELEGRAM_LOGIN.md)
describe('Telegram login pages', () => {
    it('the login page opens on the phone tab and keeps email / password as the second tab', () => {
        const login = read('Pages/Auth/Login.vue');
        expect(login).toMatch(/\{ key: 'phone', label: 'Telefon raqam' \},\s*\{ key: 'password', label: 'Email va parol' \}/);
        expect(login).toMatch(/\? 'password' : 'phone'\)/);
        expect(login).toContain("form.post('/login'");
    });

    it('the register page is only the Telegram flow: no password form', () => {
        const register = read('Pages/Auth/Register.vue');
        expect(register).toContain('<TelegramPhoneLogin mode="register"');
        expect(register).not.toContain("'/register'");
        expect(register).not.toContain('type="password"');
    });

    it('the component talks only to the four JSON endpoints and polls the status', () => {
        const c = read('Components/Auth/TelegramPhoneLogin.vue');
        for (const url of ["'/login/phone'", "'/login/phone/verify'", "'/login/telegram'", "'/login/telegram/status'"]) {
            expect(c).toContain(`axios.post(${url}`);
        }
        expect(c).toMatch(/setInterval\(checkStatus, 2500\)/);
        expect(c).toContain('autocomplete="one-time-code"');
    });

    it('the Mini App confirm screen never shows a login form', () => {
        const page = read('Pages/Telegram/LoginConfirm.vue');
        expect(page).not.toMatch(/type="password"|name="password"|name="code"/);
        expect(page).toContain("fetch('/telegram/app/login'");
        expect(page).toContain('requestContact');
    });
});
