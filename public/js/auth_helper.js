/**
 * DataMind Studio - Helper de Autenticación
 * Controla alternancia entre Login y Registro.
 */
function switchAuthTab(tab) {
    const loginForm = document.getElementById('loginFormPanel');
    const registerForm = document.getElementById('registerFormPanel');
    const tabLoginBtn = document.getElementById('tabLoginBtn');
    const tabRegisterBtn = document.getElementById('tabRegisterBtn');

    if (!loginForm || !registerForm) return;

    if (tab === 'register') {
        loginForm.classList.add('hidden');
        registerForm.classList.remove('hidden');
        tabRegisterBtn.className = 'flex-1 py-2 text-xs font-bold rounded-none bg-datamind-primary text-white shadow-none transition cursor-pointer';
        tabLoginBtn.className = 'flex-1 py-2 text-xs font-bold rounded-none text-slate-600 hover:text-slate-900 transition cursor-pointer';
    } else {
        registerForm.classList.add('hidden');
        loginForm.classList.remove('hidden');
        tabLoginBtn.className = 'flex-1 py-2 text-xs font-bold rounded-none bg-datamind-primary text-white shadow-none transition cursor-pointer';
        tabRegisterBtn.className = 'flex-1 py-2 text-xs font-bold rounded-none text-slate-600 hover:text-slate-900 transition cursor-pointer';
    }
}

function fillDemoCredentials(email, password) {
    switchAuthTab('login');
    const emailEl = document.getElementById('loginEmail');
    const passEl = document.getElementById('loginPassword');
    const formEl = document.getElementById('loginFormPanel');
    if (emailEl) emailEl.value = email;
    if (passEl) passEl.value = password;
    if (formEl) formEl.submit();
}
