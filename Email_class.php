<?php
/*
Email_class.php - relates email notifications
Copyright (C) 2013 Stephen Lawrence Jr.
This program is free software; you can redistribute it and/or
modify it under the terms of the GNU General Public License
as published by the Free Software Foundation; either version 2
of the License, or (at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.
*/
class Email
{
    private $full_name;
    private $from;
    private $subject;
    private $body;
    private $headers;
    private $recipients;
    
    /*
     * Constructor
     */
    function Email ()
    {
        
    }

    public function getFullName()
    {
        if(!isset($this->full_name)){
            return false;
        }
        return $this->full_name;
    }

    public function setFullName($full_name)
    {
        $this->full_name = $full_name;
    }

    public function getFrom()
    {
        return $this->from;
    }

    public function setFrom($from)
    {       
        $this->from = $from;
    }

    public function getSubject()
    {
        return $this->subject;
    }

    public function setSubject($subject)
    {
        $this->subject = $subject;
    }

    public function getBody()
    {
        return $this->body;
    }

    public function setBody($body)
    {
        $this->body = $body;
    }

    public function getHeaders()
    {
        return $this->headers;
    }

    private function setHeaders()
    {
        if(isset($this->from)) {
            $mail_headers = "From: {$this->getFrom()}" . "\r\n";
            $mail_headers .="Content-Type: text/plain; charset=UTF-8" . "\r\n";
            $this->headers = $mail_headers;
        }
    }

    public function getRecipients()
    {
        return $this->recipients;
    }

    public function setRecipients($recipients)
    {
        if(!is_array($recipients)) {
            return false;
        }
        $this->recipients = $recipients;
    }

    public function sendEmail()
    {        
        $this->setHeaders();       
        email_users_id($this->getRecipients(), $this->getSubject(), $this->getBody(), $this->getHeaders());
    }

}
