#!/bin/bash

# Robust mail configuration for OpenDocMan using msmtp
# This script sets up reliable mail handling with proper SMTP authentication

set -e

echo "Configuring robust mail delivery with msmtp..."

# Create PHP mail configuration
cat > /usr/local/etc/php/conf.d/99-mail.ini << 'EOF'
; Robust PHP Mail Configuration with msmtp
date.timezone = America/Los_Angeles
log_errors = On
display_errors = Off
error_reporting = E_ALL & ~E_NOTICE & ~E_STRICT & ~E_DEPRECATED
error_log = /dev/stderr
html_errors = Off
upload_max_filesize = 100M
post_max_size = 100M

; Mail configuration - use msmtp
sendmail_path = "/usr/local/bin/mail-wrapper"
mail.log = /var/log/mail.log
mail.add_x_header = On
default_socket_timeout = 60
mail.force_extra_parameters = ""
EOF

# Create msmtp configuration with proper permissions
cat > /etc/msmtprc << 'EOF'
# Default settings
defaults
auth           on
tls            on
tls_trust_file /etc/ssl/certs/ca-certificates.crt
logfile        /var/log/msmtp.log

# Account configuration (will be updated by environment variables)
account        default
host           localhost
port           587
from           admin@localhost
user           ""
password       ""
EOF

# Set proper permissions for msmtp config
chmod 644 /etc/msmtprc
chown root:root /etc/msmtprc

# Create mail wrapper script that handles environment variables
cat > /usr/local/bin/mail-wrapper << 'EOF'
#!/bin/bash

# Mail wrapper that configures msmtp based on environment variables
# and provides fallback logging

MAIL_LOG="/var/log/mail.log"
MAIL_DIR="/var/spool/mail"
MSMTP_LOG="/var/log/msmtp.log"

# Create directories if they don't exist
mkdir -p "$(dirname "$MAIL_LOG")"
mkdir -p "$MAIL_DIR"
mkdir -p "$(dirname "$MSMTP_LOG")"

# Read the email from stdin
EMAIL_CONTENT=$(cat)

# Extract recipient from command line arguments
RECIPIENT=""
for arg in "$@"; do
    if [[ "$arg" != "-t" && "$arg" != "/usr/local/bin/mail-wrapper" ]]; then
        RECIPIENT="$arg"
        break
    fi
done

# If no recipient found in args, try to extract from email headers
if [ -z "$RECIPIENT" ]; then
    RECIPIENT=$(echo "$EMAIL_CONTENT" | grep -i "^To:" | head -1 | sed 's/^To: *//i' | tr -d '\r')
fi

# Fallback to a default if still no recipient
if [ -z "$RECIPIENT" ]; then
    RECIPIENT="unknown@localhost"
fi

# Get timestamp
TIMESTAMP=$(date '+%Y-%m-%d %H:%M:%S')

# Log the email attempt
echo "[$TIMESTAMP] Mail wrapper called for: $RECIPIENT" >> "$MAIL_LOG"

# Always save email locally first for backup/debugging
SAFE_RECIPIENT=$(echo "$RECIPIENT" | tr '@' '_' | tr '/' '_' | tr ' ' '_')
MAIL_FILE="$MAIL_DIR/$(date +%Y%m%d_%H%M%S)_${SAFE_RECIPIENT}.eml"

# Extract subject for logging
SUBJECT=$(echo "$EMAIL_CONTENT" | grep -i "^Subject:" | head -1 | sed 's/^Subject: *//i' | tr -d '\r')
if [ -z "$SUBJECT" ]; then
    SUBJECT="No Subject"
fi

# Create email file with proper headers
{
    echo "Date: $(date -R)"
    echo "To: $RECIPIENT"
    echo "From: ${MAIL_FROM_ADDRESS:-admin@localhost}"
    echo "Subject: $SUBJECT"
    echo "Content-Type: text/plain; charset=UTF-8"
    echo ""
    echo "$EMAIL_CONTENT"
} > "$MAIL_FILE"

echo "[$TIMESTAMP] Email saved to: $MAIL_FILE" >> "$MAIL_LOG"

# Configure msmtp if SMTP settings are provided
if [ ! -z "$SMTP_HOST" ] && [ ! -z "$SMTP_PORT" ]; then
    echo "[$TIMESTAMP] Configuring msmtp for SMTP delivery..." >> "$MAIL_LOG"

    # Create temporary msmtp config with secure permissions
    TEMP_MSMTP_CONFIG="/tmp/msmtprc.$$"
    touch "$TEMP_MSMTP_CONFIG"
    chmod 600 "$TEMP_MSMTP_CONFIG"
    cat > "$TEMP_MSMTP_CONFIG" << MSMTP_EOF
# Runtime msmtp configuration
defaults
auth           on
tls            on
tls_trust_file /etc/ssl/certs/ca-certificates.crt
logfile        $MSMTP_LOG
timeout        30

account        default
host           $SMTP_HOST
port           $SMTP_PORT
from           ${MAIL_FROM_ADDRESS:-$SMTP_USERNAME}
MSMTP_EOF

    # Add authentication if credentials are provided
    if [ ! -z "$SMTP_USERNAME" ] && [ ! -z "$SMTP_PASSWORD" ]; then
        echo "user           $SMTP_USERNAME" >> "$TEMP_MSMTP_CONFIG"
        echo "password       $SMTP_PASSWORD" >> "$TEMP_MSMTP_CONFIG"
        echo "[$TIMESTAMP] SMTP auth configured for user: $SMTP_USERNAME" >> "$MAIL_LOG"
    else
        echo "auth           off" >> "$TEMP_MSMTP_CONFIG"
        echo "[$TIMESTAMP] SMTP configured without authentication" >> "$MAIL_LOG"
    fi

    # Add TLS/SSL configuration
    if [ "$SMTP_ENCRYPTION" = "ssl" ]; then
        echo "tls_starttls   off" >> "$TEMP_MSMTP_CONFIG"
        echo "[$TIMESTAMP] Using SSL encryption" >> "$MAIL_LOG"
    elif [ "$SMTP_ENCRYPTION" = "tls" ] || [ "$SMTP_PORT" = "587" ]; then
        echo "tls_starttls   on" >> "$TEMP_MSMTP_CONFIG"
        echo "[$TIMESTAMP] Using TLS encryption" >> "$MAIL_LOG"
    else
        echo "tls            off" >> "$TEMP_MSMTP_CONFIG"
        echo "[$TIMESTAMP] No encryption configured" >> "$MAIL_LOG"
    fi

    # Try to send via msmtp in background to avoid blocking
    (
        echo "[$TIMESTAMP] Attempting SMTP delivery via msmtp..." >> "$MAIL_LOG"

        # Send email using msmtp with timeout
        if timeout 60 msmtp -C "$TEMP_MSMTP_CONFIG" "$RECIPIENT" < "$MAIL_FILE" 2>>"$MAIL_LOG"; then
            echo "[$TIMESTAMP] SMTP delivery successful to $RECIPIENT" >> "$MAIL_LOG"
        else
            echo "[$TIMESTAMP] SMTP delivery failed to $RECIPIENT, check $MSMTP_LOG for details" >> "$MAIL_LOG"
        fi

        # Clean up temp config
        rm -f "$TEMP_MSMTP_CONFIG"
    ) &

    # Don't wait for background process
    echo "[$TIMESTAMP] SMTP delivery initiated in background" >> "$MAIL_LOG"
else
    echo "[$TIMESTAMP] No SMTP configured, email saved locally only" >> "$MAIL_LOG"
fi

# Always return success to prevent PHP from blocking
exit 0
EOF

# Make scripts executable
chmod +x /usr/local/bin/mail-wrapper

# Create log files with proper permissions
touch /var/log/mail.log /var/log/msmtp.log
chmod 644 /var/log/mail.log /var/log/msmtp.log
chown www-data:www-data /var/log/mail.log /var/log/msmtp.log

# Create mail spool directory
mkdir -p /var/spool/mail
chmod 755 /var/spool/mail
chown www-data:www-data /var/spool/mail

# Set proper permissions for msmtp config (already done above)

echo "Mail configuration completed successfully!"

exit 0
