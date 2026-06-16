<?php
require_once __DIR__ . '/config/database.php';

$username = 'samuelhervin@gmail.com';
$password = 'gtdn flnx rxhd vzyp';
$hostname = '{imap.gmail.com:993/imap/ssl/novalidate-cert}INBOX';

$inbox = imap_open($hostname, $username, $password) or die('Connexion échouée: ' . imap_last_error());


echo "RTM Worker lancé... en attente de tickets.\n";

// Avant la boucle, on définit des critères
while (true) {
    $emails = imap_search($inbox, 'UNSEEN');

    if ($emails) {
        foreach ($emails as $email_number) {
            $header = imap_headerinfo($inbox, $email_number);
            $subject = $header->subject;
            $from = $header->from[0]->mailbox . "@" . $header->from[0]->host;

            // FILTRE : Ne traiter que si l'objet contient "TICKET" ou provient d'un domaine pro
            // Exemple : Le sujet doit commencer par [TICKET]
            if (strpos($subject, '[TICKET]') !== false) {
                // ... ton code d'insertion en base ...
                echo "Ticket traité: $subject\n";
            } else {
                echo "Mail ignoré (spam/newsletter)\n";
                // Optionnel : Marquer comme lu pour ne plus qu'il revienne
                //imap_setflag_full($inbox, $email_number, "\\Seen");
            }
        }
    }
    sleep(10);
}
