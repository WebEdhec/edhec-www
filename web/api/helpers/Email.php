<?php

/**
 * @package   Edhec Connector
 * @author Aurone <info@aurone.com>
 * @copyright Copyright (c)2007-2022 Aurone <https://aurone.com>
 * @license   GNU General Public License version 3, or later
 */

use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\PHPMailer;

class EdhecEmail
{
    /**
     * Send email
     *
     * @param assciative array $data
     *
     * @return boolean
     */
    public static function send($data)
    {
        $mailContent = isset($data['mailContent']) ? $data['mailContent'] : self::createContent($data);

        // Address mail
        if (isset($_GET['debug'])) {
            $mailTo = EDHEC_DEBUGGER_EMAIL;
        } else {
            if (isset($data['mailTo'])) {
                $mailTo = $data['mailTo'];
            } else {
                $mailTo = EDHEC_EMAIL;
            }
        }

        try {
            // Instantiation and passing `true` enables exceptions
            $mail = new PHPMailer();
            // $mail->SMTPDebug = 2; // Enable verbose debug output
            $mail->isSMTP();
            $mail->SMTPAuth = true;
            $mail->SMTPSecure = EDHEC_SMPT_SECURE;
            $mail->Host = EDHEC_SMPT_HOST;
            $mail->Port = EDHEC_SMPT_PORT;
            $mail->isHTML();
            $mail->Username = EDHEC_SMPT_USERNAME;
            $mail->Password = EDHEC_SMPT_PASSWORD;
            $mail->SetFrom(EDHEC_SENDER_EMAIL, EDHEC_SENDER_NAME);
            $mail->Subject = $data['subject'];
            $mail->Body = $mailContent;
            $mail->Encoding = "base64";
            $mail->CharSet = 'utf-8';
            $mail->AddAddress($mailTo);

            if (!isset($_GET['debug'])) {
                foreach (EDHEC_BCC_EMAILS as $email) {
                    if ($email != $mailTo) {
                        $mail->addBCC($email);
                    }
                }
            }

            /*
            // PDF attachment
            $dompdf = new Dompdf([
            'isHtml5ParserEnabled' => true,
            'enable_remote' => true,
            'chroot' => __DIR__,
            ]);

            $pdfContent = str_replace('mailContent', 'pdfContent', $mailContent);

            $dompdf->loadHtml($pdfContent);

            // (Optional) Setup the paper size and orientation
            $dompdf->setPaper('A4', 'portrait');

            $dompdf->set_option('defaultMediaType', 'all');
            $dompdf->set_option('isFontSubsettingEnabled', true);

            // Render the HTML as PDF
            $dompdf->render();

            $attachmentPdf = $dompdf->output();

            // Add pdf attachment in the email
            $mail->addStringAttachment($attachmentPdf, "Document.pdf");
             */

            $result = $mail->Send();

        } catch (Exception $e) {
            echo "Message could not be sent. Mailer Error: {$mail->ErrorInfo}";
        }

        if ($result == 1) {
            return true;
        }

        return false;
    }

    /**
     * Generate Mail content (body)
     *
     * @param assciative array $data
     *
     * @return string
     */
    private static function createContent($data)
    {
        $style = "<style>
                    @font-face {
                        font-family: 'Open Sans';
                        font-style: normal;
                        font-weight: normal;
                        src: url(../assets/fonts/verdana/verdana.ttf) format('truetype');
                    }

                    body {
                        font-family: verdana, sans-serif;
                        font-size: 11pt;
                    }

                    #pdfContent {
                        width: 559px !important;
                        margin: auto !important;
                    }
                </style>";

        $msgTitle = "EDHEC SYNCHRONIZATION";
        $body = "<body id='mailContent'></body>";

        $content = "<!DOCTYPE HTML PUBLIC \"-//W3C//DTD HTML 4.01 Transitional//EN\" \"http://www.w3.org/TR/html4/loose.dtd\">
                <html>
                    <head>
                    <title>" . $msgTitle . "</title>
                    <meta http-equiv=\"Content-Type\" content=\"text/html; charset=Windows-1252\">"
            . $style
            . "</head>"
            . $body
            . "</html>";

        return $content;
    }
}
