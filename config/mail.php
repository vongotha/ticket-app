<?php
require_once __DIR__ . '/database.php';
/* require_once __DIR__ . '/config/mail.php'; */
require_once __DIR__ . '/../vendor/autoload.php';

// utils/mailer.php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Inclure l'autoloader de Composer pour charger PHPMailer

/**
 * Envoie un e-mail transactionnel via le relais SMTP sécurisé de Gmail.
 *
 * @param string $toEmail Adresse e-mail du destinataire (Ex: tech@gmail.com)
 * @param string $toName Nom d'affichage du destinataire
 * @param string $subject Objet de l'e-mail
 * @param string $bodyHTML Contenu du message au format HTML
 * @return bool True si l'envoi réussit, False sinon
 */
function envoyerEmail($toEmail, $toName, $subject, $bodyHTML) {
    $mail = new PHPMailer(true);

    try {
        // =========================================================================
        // CONFIGURATION DU PROTOCOLE SMTP GMAIL
        // =========================================================================
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';                     // Serveur SMTP de Gmail
        $mail->SMTPAuth   = true;                                 // Activation de l'authentification
        
        // SÉCURITÉ IMPORTANTE : Tes identifiants Gmail
        $mail->Username   = 'samuelhervin@gmail.com';        // Remplacer par ton vrai Gmail
        $mail->Password   = 'gtdn flnx rxhd vzyp';   // Remplacer par le mot de passe d'application de 16 caractères (sans espaces)
        
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;       // Chiffrement requis par Google (TLS)
        $mail->Port       = 587;                                  // Port standard de communication TLS
        $mail->CharSet    = 'UTF-8';                              // Support complet des caractères accentués

        // Options de contournement SSL (Utile sur des environnements de dev locaux comme XAMPP)
        $mail->SMTPOptions = [
            'ssl' => [
                'verify_peer' => false,
                'verify_peer_name' => false,
                'allow_self_signed' => true
            ]
        ];

        // =========================================================================
        // EXPÉDITEUR & DESTINATAIRE
        // =========================================================================
        $mail->setFrom('samuelhervin@gmail.com', 'HelpDesk AI Support');
        $mail->addAddress($toEmail, $toName);

        // =========================================================================
        // CONTENU DU MESSAGE
        // =========================================================================
        $mail->isHTML(true);                                      // Permet d'envoyer un mail structuré en HTML
        $mail->Subject = $subject;
        $mail->Body    = $bodyHTML;

        // Envoi
        $mail->send();
        error_log("[SMTP-SUCCESS] Mail envoyé avec succès à : " . $toEmail);
        return true;

    } catch (Exception $e) {
        // Journalisation de l'erreur dans Apache en cas de blocage sans casser l'application
        error_log("[SMTP-ERROR] Échec de l'envoi d'e-mail : " . $mail->ErrorInfo);
        return false;
    }
}