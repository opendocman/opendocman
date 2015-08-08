<?php
/*
Email_class.php - relates email notifications
Copyright (C) 2013-2015 Stephen Lawrence Jr.
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
    public function Email()
    {
    }

    /**
     * @return string
     */
    public function getFullName()
    {
        if (!isset($this->full_name)) {
            return false;
        }
        return $this->full_name;
    }

    /**
     * @param string $full_name
     */
    public function setFullName($full_name)
    {
        $this->full_name = $full_name;
    }

    /**
     * @return string
     */
    public function getFrom()
    {
        return $this->from;
    }

    /**
     * @param string $from
     */
    public function setFrom($from)
    {
        $this->from = $from;
    }

    /**
     * @return string
     */
    public function getSubject()
    {
        return $this->subject;
    }

    /**
     * @param string $subject
     */
    public function setSubject($subject)
    {
        $this->subject = $subject;
    }

    /**
     * @return string
     */
    public function getBody()
    {
        return $this->body;
    }

    /**
     * @param string $body
     */
    public function setBody($body)
    {
        $this->body = $body;
    }

    /**
     * @return string
     */
    public function getHeaders()
    {
        return $this->headers;
    }

    /**
     *
     */
    private function setHeaders()
    {
        if(isset($this->from)) {
            $mail_headers = "From: {$this->getFrom()}" . PHP_EOL;
            $mail_headers .="Content-Type: text/plain; charset=UTF-8" . PHP_EOL;
            $this->headers = $mail_headers;
        }
    }

    /**
     * @return string
     */
    public function getRecipients()
    {
        return $this->recipients;
    }

    /**
     * @param string $recipients
     * @return bool
     */
    public function setRecipients($recipients)
    {
        if (!is_array($recipients)) {
            return false;
        }
        $this->recipients = $recipients;
    }

    /**
     * @return bool
     */
    public function sendEmail()
    {
        if ((count($this->getRecipients()) > 0)) {
            $this->setHeaders();
            email_users_id($this->getRecipients(), $this->getSubject(), $this->getBody(), $this->getHeaders());
        }
        return true;
    }
}
