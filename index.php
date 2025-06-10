<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Suntech - Login KPI</title>
  <style>
    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
    }

    body {
      background: radial-gradient(circle, #001e3c, #000000);
      height: 100vh;
      display: flex;
      justify-content: center;
      align-items: center;
      overflow: hidden;
      font-family: Arial, sans-serif;
    }

    .kpi-wrapper {
      position: relative;
      width: 350px;
      height: 350px;
      border-radius: 50%;
      background: radial-gradient(circle, #043b6c 10%, #02101d 90%);
      box-shadow: 0 0 60px rgba(0, 174, 255, 0.5);
    }

    .scanner-ring {
      position: absolute;
      top: 0; left: 0;
      width: 100%; height: 100%;
      border: 2px solid rgba(0, 174, 255, 0.3);
      border-radius: 50%;
      animation: rotate 10s linear infinite;
    }

    @keyframes rotate {
      0% { transform: rotate(0deg); }
      100% { transform: rotate(360deg); }
    }

    .login-button {
      position: absolute;
      top: 50%; left: 50%;
      transform: translate(-50%, -50%);
      width: 300px;
      height: 300px;
      border-radius: 50%;
      background-image: url('Login.png');
      background-size: cover;
      background-position: center;
      border: none;
      cursor: pointer;
      animation: pulse 2s infinite;
    }

    @keyframes pulse {
      0% { box-shadow: 0 0 0px rgba(0,174,255,0.8); }
      50% { box-shadow: 0 0 20px rgba(0,174,255,1); }
      100% { box-shadow: 0 0 0px rgba(0,174,255,0.8); }
    }

    .logo-topo {
      position: absolute;
      top: -220px;
      left: 50%;
      transform: translateX(-50%);
      width: 600px;
      height: 300px;
    }

    @media(max-width: 768px) {
      .kpi-wrapper {
        width: 250px;
        height: 250px;
      }

      .login-button {
        width: 200px;
        height: 200px;
      }

      .logo-topo {
        width: 300px;
        height: 80px;
        top: -100px;
      }
    }
  </style>
</head>
<body>

  <div class="kpi-wrapper">
    <img class="logo-topo" src="Suntech-Sunlab.png" alt="Suntech Logo">
    <div class="scanner-ring"></div>
    <button class="login-button" onclick="location.href='./FrontEnd/tela_login.php'"></button>
  </div>

</body>
</html>
