<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>SUNLAB - Centro de Inteligência Operacional | Suntech do Brasil</title>
  <link rel="icon" href="FrontEnd/CSS/imagens/VISTA.png">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
  
  <style>
    /* ===============================================================
       SUNLAB - LANDING PAGE INSTITUCIONAL
       Design: Enterprise Premium Minimalista
       =============================================================== */

    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
    }

    body {
      font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
      background: linear-gradient(135deg, #0a0e1a 0%, #111827 50%, #0a0e1a 100%);
      min-height: 100vh;
      display: flex;
      align-items: center;
      justify-content: center;
      color: #e8f4ff;
      overflow-x: hidden;
      position: relative;
    }

    /* Background sutil com gradientes */
    body::before {
      content: "";
      position: fixed;
      inset: 0;
      background: 
        radial-gradient(circle at 20% 10%, rgba(56, 139, 253, 0.08), transparent 50%),
        radial-gradient(circle at 80% 80%, rgba(17, 207, 255, 0.06), transparent 55%);
      pointer-events: none;
      z-index: 0;
    }

    /* Container central */
    .landing-container {
      position: relative;
      z-index: 1;
      max-width: 680px;
      width: 90%;
      text-align: center;
      animation: fadeIn 0.8s ease-out;
    }

    @keyframes fadeIn {
      from {
        opacity: 0;
        transform: translateY(20px);
      }
      to {
        opacity: 1;
        transform: translateY(0);
      }
    }

    /* Logo */
    .brand-logo {
      width: 180px;
      height: auto;
      margin: 0 auto 32px;
      opacity: 0;
      animation: logoAppear 0.8s ease-out 0.2s forwards;
    }

    @keyframes logoAppear {
      from {
        opacity: 0;
        transform: scale(0.9);
      }
      to {
        opacity: 1;
        transform: scale(1);
      }
    }

    /* Identidade */
    .brand-identity {
      margin-bottom: 48px;
    }

    .brand-title {
      font-size: 56px;
      font-weight: 700;
      color: #e8f4ff;
      letter-spacing: 8px;
      margin-bottom: 12px;
      text-transform: uppercase;
      background: linear-gradient(135deg, #388bfd 0%, #11cfff 100%);
      -webkit-background-clip: text;
      -webkit-text-fill-color: transparent;
      background-clip: text;
    }

    .brand-subtitle {
      font-size: 18px;
      font-weight: 400;
      color: #a8c5e0;
      letter-spacing: 1.5px;
      text-transform: uppercase;
    }

    /* Apresentação */
    .brand-description {
      margin-bottom: 48px;
      padding: 0 20px;
    }

    .brand-description p {
      font-size: 16px;
      line-height: 1.8;
      color: #cbd5e1;
      margin-bottom: 12px;
    }

    .brand-description p:last-child {
      margin-bottom: 0;
    }

    /* Call to Action */
    .cta-section {
      margin-bottom: 64px;
    }

    .btn-access {
      display: inline-block;
      padding: 18px 48px;
      background: linear-gradient(135deg, #388bfd 0%, #2563eb 100%);
      color: #ffffff;
      font-size: 16px;
      font-weight: 600;
      text-decoration: none;
      border: none;
      border-radius: 12px;
      cursor: pointer;
      transition: all 0.3s ease;
      box-shadow: 0 8px 24px rgba(56, 139, 253, 0.25);
      letter-spacing: 0.5px;
    }

    .btn-access:hover {
      transform: translateY(-3px);
      box-shadow: 0 12px 32px rgba(56, 139, 253, 0.4);
      background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%);
    }

    .btn-access:active {
      transform: translateY(-1px);
    }

    /* Rodapé */
    .landing-footer {
      position: fixed;
      bottom: 0;
      left: 0;
      right: 0;
      padding: 20px;
      text-align: center;
      font-size: 13px;
      color: #6b8199;
      background: rgba(10, 14, 26, 0.8);
      backdrop-filter: blur(10px);
      border-top: 1px solid rgba(56, 139, 253, 0.1);
    }

    /* Responsividade */
    @media (max-width: 768px) {
      .brand-title {
        font-size: 42px;
        letter-spacing: 6px;
      }

      .brand-subtitle {
        font-size: 15px;
        letter-spacing: 1px;
      }

      .brand-description p {
        font-size: 15px;
        line-height: 1.6;
      }

      .btn-access {
        padding: 16px 40px;
        font-size: 15px;
      }

      .landing-footer {
        font-size: 12px;
        padding: 16px;
      }
    }

    @media (max-width: 480px) {
      .brand-logo {
        width: 140px;
        margin-bottom: 24px;
      }

      .brand-title {
        font-size: 36px;
        letter-spacing: 4px;
        margin-bottom: 8px;
      }

      .brand-subtitle {
        font-size: 13px;
      }

      .brand-identity {
        margin-bottom: 36px;
      }

      .brand-description {
        margin-bottom: 36px;
        padding: 0 10px;
      }

      .brand-description p {
        font-size: 14px;
      }

      .btn-access {
        width: 100%;
        max-width: 300px;
        padding: 14px 32px;
        font-size: 14px;
      }

      .cta-section {
        margin-bottom: 48px;
      }
    }
  </style>
</head>
<body>

  <!-- Container Central -->
  <main class="landing-container">
    
    <!-- Logo -->
    <img src="Suntech-Sunlab.png" alt="Suntech Sunlab" class="brand-logo">
    
    <!-- Identidade -->
    <div class="brand-identity">
      <h1 class="brand-title">SUNLAB</h1>
      <p class="brand-subtitle">Centro de Inteligência Operacional</p>
    </div>
    
    <!-- Apresentação -->
    <div class="brand-description">
      <p>Rastreamento completo de remessas e equipamentos</p>
      <p>Gestão integrada: Recebimento, Análise, Reparo, Qualidade e Expedição</p>
      <p>Indicadores e insights operacionais em tempo real</p>
    </div>
    
    <!-- Call to Action -->
    <div class="cta-section">
      <a href="./FrontEnd/tela_login.php" class="btn-access">Acessar o Sistema</a>
    </div>
    
  </main>

  <!-- Rodapé -->
  <footer class="landing-footer">
    © 2025 Suntech do Brasil. Todos os direitos reservados.
  </footer>

</body>
</html>
