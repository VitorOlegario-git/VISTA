<?php
/**
 * Classe EmailService - Envio Centralizado de Emails
 * Usa configurações do .env e PHPMailer
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/../PHPMailer/PHPMailer.php';
require_once __DIR__ . '/../PHPMailer/SMTP.php';
require_once __DIR__ . '/../PHPMailer/Exception.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

class EmailService {
    
    private $mailer;
    
    public function __construct() {
        $this->mailer = new PHPMailer(true);
        $this->configurar();
    }
    
    /**
     * Configura o PHPMailer com dados do .env
     */
    private function configurar() {
        $this->mailer->isSMTP();
        $this->mailer->Host = MAIL_HOST;
        $this->mailer->SMTPAuth = true;
        $this->mailer->Username = MAIL_USERNAME;
        $this->mailer->Password = MAIL_PASSWORD;
        $this->mailer->SMTPSecure = 'tls';
        $this->mailer->Port = MAIL_PORT;
        $this->mailer->CharSet = 'UTF-8';
        
        // Define remetente padrão
        $this->mailer->setFrom(MAIL_FROM_ADDRESS, MAIL_FROM_NAME);
    }
    
    /**
     * Envia email simples de texto
     * 
     * @param string $destinatario Email do destinatário
     * @param string $assunto Assunto do email
     * @param string $mensagem Corpo do email (texto)
     * @param string $nomeDestinatario Nome do destinatário (opcional)
     * @return bool True se enviado com sucesso
     */
    public function enviarTexto($destinatario, $assunto, $mensagem, $nomeDestinatario = '') {
        try {
            $this->mailer->clearAddresses();
            $this->mailer->addAddress($destinatario, $nomeDestinatario);
            $this->mailer->isHTML(false);
            $this->mailer->Subject = $assunto;
            $this->mailer->Body = $mensagem;
            
            return $this->mailer->send();
        } catch (Exception $e) {
            error_log("Erro ao enviar email: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Envia email HTML
     * 
     * @param string $destinatario Email do destinatário
     * @param string $assunto Assunto do email
     * @param string $htmlMensagem Corpo do email (HTML)
     * @param string $nomeDestinatario Nome do destinatário (opcional)
     * @return bool True se enviado com sucesso
     */
    public function enviarHTML($destinatario, $assunto, $htmlMensagem, $nomeDestinatario = '') {
        try {
            $this->mailer->clearAddresses();
            $this->mailer->addAddress($destinatario, $nomeDestinatario);
            $this->mailer->isHTML(true);
            $this->mailer->Subject = $assunto;
            $this->mailer->Body = $htmlMensagem;
            
            return $this->mailer->send();
        } catch (Exception $e) {
            error_log("Erro ao enviar email: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Envia email de confirmação de cadastro
     * 
     * @param string $email Email do usuário
     * @param string $nome Nome do usuário
     * @param string $token Token de confirmação
     * @return bool True se enviado com sucesso
     */
    public function enviarConfirmacaoCadastro($email, $nome, $token) {
        $link = url("FrontEnd/confirmar_cadastro.php?token=$token");
        
        $html = "
            <div style='font-family: Arial, sans-serif; padding: 20px;'>
                <h2 style='color: #0066cc;'>Bem-vindo ao Sistema VISTA!</h2>
                <p>Olá, <strong>{$nome}</strong>!</p>
                <p>Obrigado por se cadastrar. Para ativar sua conta, clique no link abaixo:</p>
                <p style='margin: 20px 0;'>
                    <a href='{$link}' style='background: #0066cc; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>
                        Confirmar Cadastro
                    </a>
                </p>
                <p><small>Ou copie e cole este link no navegador:</small></p>
                <p><small>{$link}</small></p>
                <hr style='margin: 20px 0;'>
                <p style='color: #666; font-size: 12px;'>
                    Se você não solicitou este cadastro, ignore este email.
                </p>
            </div>
        ";
        
        return $this->enviarHTML($email, 'Confirme seu cadastro - Sistema VISTA', $html, $nome);
    }
    
    /**
     * Envia email de recuperação de senha
     * 
     * @param string $email Email do usuário
     * @param string $nome Nome do usuário
     * @param string $token Token de recuperação
     * @return bool True se enviado com sucesso
     */
    public function enviarRecuperacaoSenha($email, $nome, $token) {
        $link = url("FrontEnd/NovaSenha.php?token=$token");
        
        $html = "
            <div style='font-family: Arial, sans-serif; padding: 20px;'>
                <h2 style='color: #cc6600;'>Recuperação de Senha</h2>
                <p>Olá, <strong>{$nome}</strong>!</p>
                <p>Você solicitou a redefinição de senha. Clique no link abaixo para continuar:</p>
                <p style='margin: 20px 0;'>
                    <a href='{$link}' style='background: #cc6600; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>
                        Redefinir Senha
                    </a>
                </p>
                <p><small>Ou copie e cole este link no navegador:</small></p>
                <p><small>{$link}</small></p>
                <p style='color: #cc0000; margin-top: 20px;'>
                    <strong>⚠️ Este link expira em 1 hora.</strong>
                </p>
                <hr style='margin: 20px 0;'>
                <p style='color: #666; font-size: 12px;'>
                    Se você não solicitou esta recuperação, ignore este email e sua senha permanecerá inalterada.
                </p>
            </div>
        ";
        
        return $this->enviarHTML($email, 'Recuperação de Senha - Sistema VISTA', $html, $nome);
    }
    
    /**
     * Envia email de notificação customizado
     * 
     * @param string $email Email do destinatário
     * @param string $nome Nome do destinatário
     * @param string $assunto Assunto
     * @param string $titulo Título do email
     * @param string $mensagem Mensagem principal
     * @return bool True se enviado com sucesso
     */
    public function enviarNotificacao($email, $nome, $assunto, $titulo, $mensagem) {
        $html = "
            <div style='font-family: Arial, sans-serif; padding: 20px;'>
                <h2 style='color: #0066cc;'>{$titulo}</h2>
                <p>Olá, <strong>{$nome}</strong>!</p>
                <div style='background: #f5f5f5; padding: 15px; border-radius: 5px; margin: 20px 0;'>
                    {$mensagem}
                </div>
                <hr style='margin: 20px 0;'>
                <p style='color: #666; font-size: 12px;'>
                    Sistema VISTA - Suntech do Brasil
                </p>
            </div>
        ";
        
        return $this->enviarHTML($email, $assunto, $html, $nome);
    }
    
    /**
     * Retorna o último erro do PHPMailer
     */
    public function getErro() {
        return $this->mailer->ErrorInfo;
    }
}

/**
 * Função helper para criar instância do EmailService
 */
function emailService() {
    return new EmailService();
}
?>
