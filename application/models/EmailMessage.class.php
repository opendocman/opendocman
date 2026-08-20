<?php

/*
 * EmailMessage DTO - the normalized representation of a fetched email.
 * Deliberately free of any webklex/php-imap types so the ingest layer never
 * depends on library specifics. The EmailInbox adapter is the only place that
 * touches the IMAP library and it converts library objects into this DTO.
 */
class EmailMessage
{
    public string $id;
    public string $subject;
    public string $from;
    public array $attachments = [];

    public function __construct(string $id, string $subject, string $from)
    {
        $this->id = $id;
        $this->subject = $subject;
        $this->from = $from;
    }
}