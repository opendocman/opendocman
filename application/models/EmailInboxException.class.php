<?php

/**
 * EmailInboxException - thrown by EmailInbox when a configured mailbox folder
 * cannot be resolved or any other mailbox operation fails in a catchable way.
 *
 * The ingest layer (Task 6) catches this to surface a clear, actionable error
 * instead of a PHP "call to member on null" or an opaque library exception.
 */
class EmailInboxException extends \Exception
{
}