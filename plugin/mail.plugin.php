<?php

include_once(EXTENSION . 'phpmailer/class.phpmailer.php');

class MailPlugin extends Plugin{

	public function run($options){

		$this->protocol=$options['protocol'];
		$this->to=$options['to'];
		$this->from=$options['username'];
		$this->sender=$options['username'];
		$this->subject=$options['subject'];
		$this->text=$options['text'];
		$this->hostname=$options['host'];
		$this->port=$options['port'];
		$this->username=$options['username'];
		$this->password=$options['password'];
		$this->timeout=$options['timeout'];

		$this->attachments = array();

		 if (!$this->to) {
            trigger_error('Error: E-Mail to required!');
            exit();
        }

        if (!$this->from) {
            trigger_error('Error: E-Mail from required!');
            exit();
        }

        if (!$this->sender) {
            trigger_error('Error: E-Mail sender required!');
            exit();
        }

        if (!$this->subject) {
            trigger_error('Error: E-Mail subject required!');
            exit();
        }

        if ((!$this->text) && (!$this->html)) {
            trigger_error('Error: E-Mail message required!');
            exit();
        }

        $mail  = new PHPMailer();
        $mail->CharSet = "UTF-8";

        if (is_array($this->to)) {
            foreach ($this->to as $toTmp){
                $mail->AddAddress($toTmp);
            }
        } else {
            $mail->AddAddress($this->to);
        }

        if(!empty($this->readreceipt)) {
            $mail->ConfirmReadingTo = $this->readreceipt;
        }

        $mail->Subject = $this->subject;

        $mail->AddReplyTo($this->from, $this->sender);
        $mail->SetFrom($this->from, $this->sender);
        $mail->AddReplyTo($this->from, $this->sender);

        if (!$this->html) {
            $mail->Body = $this->text;
        } else {
            $mail->MsgHTML($this->html);
            if ($this->text) {
                $mail->AltBody = $this->text;
            } else {
                $mail->AltBody = 'This is a HTML email and your email client software does not support HTML email!';
            }
        }

        foreach ($this->attachments as $attachment) {
            if (file_exists($attachment['file'])) {
                $mail->AddAttachment($attachment['file']);
            }
        }

        if ($this->protocol == 'smtp') {
            $mail->IsSMTP();
            $mail->Host = $this->hostname;
            $mail->Port = $this->port;
            if($this->port == '587'){
                $mail->SMTPAuth = true;
                $mail->SMTPSecure = "tls";
            } elseif ($this->port == '465') {
                $mail->SMTPAuth = true;
                $mail->SMTPSecure = "ssl";
            }
            if (!empty($this->username)  && !empty($this->password)) {
                $mail->SMTPAuth = true;
                $mail->Host = $this->hostname;
                $mail->Username = $this->username;
                $mail->Password = $this->password;
            }
        }

        $mail->Send();
        return 1;
	}
}