<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Login Petugas - Antiran</title>
</head>
<body>
    <h1>Login Petugas</h1>
    <nav>
        <a href="/">Halaman Pengunjung</a>
    </nav>
    <hr>

    <p id="error-msg" style="color: red;"></p>

    <form id="login-form">
        <div>
            <label>Email:</label><br>
            <input type="email" id="email" placeholder="Masukan Email" required>
        </div>
        <br>
        <div>
            <label>Password:</label><br>
            <input type="password" id="password" value="password123" placeholder="Masukan Password" required>
        </div>
        <br>
        <button type="submit" id="btn-login">Login</button>
    </form>

    <script>
        const STAFF_TOKEN_KEY = 'antiran_staff_token';

        document.addEventListener('DOMContentLoaded', async () => {
            const token = localStorage.getItem(STAFF_TOKEN_KEY);
            if (!token) return;

            const res = await fetch('/api/staff/me', {
                headers: {
                    'Accept': 'application/json',
                    'Authorization': `Bearer ${token}`
                }
            });

            if (res.ok) {
                window.location.href = '/staff/dashboard';
            } else {
                localStorage.removeItem(STAFF_TOKEN_KEY);
            }
        });

        document.getElementById('login-form').addEventListener('submit', async (e) => {
            e.preventDefault();
            const errEl = document.getElementById('error-msg');
            const btn = document.getElementById('btn-login');
            errEl.textContent = '';
            btn.disabled = true;

            const res = await fetch('/api/staff/login', {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    email: document.getElementById('email').value,
                    password: document.getElementById('password').value
                })
            });

            const json = await res.json();

            if (!res.ok || !json.success) {
                errEl.textContent = json.message || 'Login gagal.';
                btn.disabled = false;
                return;
            }

            localStorage.setItem(STAFF_TOKEN_KEY, json.data.access_token);
            window.location.href = '/staff/dashboard';
        });
    </script>
</body>
</html>