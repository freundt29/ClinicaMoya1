<?php
/**
 * Helper para envío de emails
 * Configurar según tu servidor SMTP
 */

require_once __DIR__ . '/../../vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

class EmailHelper {
    
    /**
     * Envía email de recuperación de contraseña
     */
    public static function sendPasswordReset(string $email, string $resetLink): bool {
        
        $mail = new PHPMailer(true);
        
        try {
            // Configuración SMTP - CONFIGURA TUS CREDENCIALES AQUÍ
            $mail->isSMTP();
            $mail->Host       = 'smtp.gmail.com';  // Servidor SMTP
            $mail->SMTPAuth   = true;
            $mail->Username   = 'tucorreo@gmail.com';  // TU EMAIL
            $mail->Password   = 'xxxx xxxx xxxx xxxx';  // CONTRASEÑA DE APLICACIÓN
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port       = 587;
            $mail->CharSet    = 'UTF-8';

            // Remitente y destinatario
            $mail->setFrom('noreply@clinicamoya.com', 'Clínica Moya');
            $mail->addAddress($email);

            // Contenido
            $mail->isHTML(true);
            $mail->Subject = 'Recuperación de contraseña - Clínica Moya';
            $mail->Body    = self::getPasswordResetEmailBody($resetLink);

            $mail->send();
            return true;
        } catch (Exception $e) {
            error_log("Error enviando email: {$mail->ErrorInfo}");
            return false;
        }
    }
    
    /**
     * Genera el HTML del email de recuperación
     */
    private static function getPasswordResetEmailBody(string $resetLink): string {
        return "
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset='UTF-8'>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background: #556ee6; color: white; padding: 20px; text-align: center; }
                .content { background: #f8f9fa; padding: 30px; }
                .button { 
                    display: inline-block; 
                    padding: 12px 30px; 
                    background: #556ee6; 
                    color: white; 
                    text-decoration: none; 
                    border-radius: 5px;
                    margin: 20px 0;
                }
                .footer { text-align: center; padding: 20px; color: #6c757d; font-size: 12px; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h1>Clínica Moya</h1>
                </div>
                <div class='content'>
                    <h2>Recuperación de contraseña</h2>
                    <p>Has solicitado restablecer tu contraseña.</p>
                    <p>Haz clic en el siguiente botón para crear una nueva contraseña:</p>
                    <p style='text-align: center;'>
                        <a href='$resetLink' class='button'>Restablecer contraseña</a>
                    </p>
                    <p>O copia y pega este enlace en tu navegador:</p>
                    <p style='word-break: break-all; color: #556ee6;'>$resetLink</p>
                    <hr>
                    <p><strong>Importante:</strong></p>
                    <ul>
                        <li>Este enlace expirará en <strong>1 hora</strong></li>
                        <li>Solo puede usarse <strong>una vez</strong></li>
                        <li>Si no solicitaste este cambio, ignora este mensaje</li>
                    </ul>
                </div>
                <div class='footer'>
                    <p>© " . date('Y') . " Clínica Moya. Todos los derechos reservados.</p>
                    <p>Este es un mensaje automático, por favor no respondas a este correo.</p>
                </div>
            </div>
        </body>
        </html>
        ";
    }
    
    /**
     * Envía email de confirmación de registro
     */
    public static function sendWelcomeEmail(string $email, string $fullName): bool {
        $subject = 'Bienvenido a Clínica Moya';
        $message = "
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset='UTF-8'>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background: #556ee6; color: white; padding: 20px; text-align: center; }
                .content { background: #f8f9fa; padding: 30px; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h1>¡Bienvenido a Clínica Moya!</h1>
                </div>
                <div class='content'>
                    <h2>Hola, $fullName</h2>
                    <p>Tu cuenta ha sido creada exitosamente.</p>
                    <p>Ahora puedes:</p>
                    <ul>
                        <li>Reservar citas médicas</li>
                        <li>Ver tu historial de citas</li>
                        <li>Acceder a tus datos médicos</li>
                    </ul>
                    <p>Gracias por confiar en nosotros para tu salud.</p>
                </div>
            </div>
        </body>
        </html>
        ";
        
        $headers = [
            'MIME-Version: 1.0',
            'Content-type: text/html; charset=UTF-8',
            'From: Clínica Moya <noreply@clinicamoya.com>'
        ];
        
        return mail($email, $subject, $message, implode("\r\n", $headers));
    }
}
