<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - StudentWallet</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f7f6;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
        }
        .login-container {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
            width: 300px;
        }
        h2 { text-align: center; color: #333; }
        input {
            width: 100%;
            padding: 10px;
            margin: 10px 0;
            border: 1px solid #ccc;
            border-radius: 5px;
            box-sizing: border-box;
        }
        button {
            width: 100%;
            padding: 10px;
            background-color: #28a745;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
        }
        button:hover { background-color: #218838; }
        #mensaje {
            margin-top: 15px;
            text-align: center;
            font-weight: bold;
        }
    </style>
</head>
<body>

    <div class="login-container">
        <h2>Iniciar Sesión</h2>
        <form id="loginForm">
            <input type="email" id="email" placeholder="Correo electrónico" required value="admin@test.com">
            <input type="password" id="password" placeholder="Contraseña" required value="123456">
            <button type="submit" id="btnLogin">Ingresar</button>
        </form>
        <div id="mensaje"></div>
    </div>

    <script>
        document.getElementById('loginForm').addEventListener('submit', async function(e) {
            e.preventDefault(); // Evita que la página se recargue
            
            const email = document.getElementById('email').value;
            const password = document.getElementById('password').value;
            const mensajeDiv = document.getElementById('mensaje');
            const btn = document.getElementById('btnLogin');

            mensajeDiv.style.color = 'blue';
            mensajeDiv.innerText = 'Cargando...';
            btn.disabled = true;

            try {
                // Llamamos a tu API de Laravel
                const response = await fetch('/api/login', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({ email: email, password: password })
                });

                const data = await response.json();

                if (response.ok) {
                    mensajeDiv.style.color = 'green';
                    mensajeDiv.innerText = '¡Login exitoso! Token recibido.';
                    // Guardamos el token en el navegador
                    localStorage.setItem('auth_token', data.token);
                    console.log("Token:", data.token);
                    console.log("Usuario:", data.user);
                } else {
                    mensajeDiv.style.color = 'red';
                    mensajeDiv.innerText = data.message || 'Error al iniciar sesión';
                }
            } catch (error) {
                mensajeDiv.style.color = 'red';
                mensajeDiv.innerText = 'Error de conexión con el servidor';
                console.error(error);
            } finally {
                btn.disabled = false;
            }
        });
    </script>
</body>
</html>