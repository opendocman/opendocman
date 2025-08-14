# Mailgun Configuration Guide for OpenDocMan

This guide provides step-by-step instructions for configuring Mailgun SMTP with OpenDocMan's Docker environment.

## Overview

Mailgun is a popular email service provider that offers reliable SMTP relay services. This guide will help you configure OpenDocMan to send emails through Mailgun using the improved msmtp-based mail system.

## Prerequisites

1. A Mailgun account (free tier available)
2. A verified domain in Mailgun (or use Mailgun's sandbox domain for testing)
3. Mailgun SMTP credentials

## Step 1: Get Mailgun SMTP Credentials

### Option A: Using Your Own Domain (Recommended for Production)

1. Log into your Mailgun dashboard
2. Go to **Sending** → **Domains**
3. Click on your verified domain
4. Go to **SMTP** section
5. Note down:
   - **SMTP hostname**: `smtp.mailgun.org`
   - **Port**: `587` (TLS) or `465` (SSL)
   - **Username**: `postmaster@your-domain.com`
   - **Password**: Your SMTP password (not your Mailgun account password)

### Option B: Using Mailgun Sandbox Domain (Testing Only)

1. Log into your Mailgun dashboard
2. Go to **Sending** → **Domains**
3. Click on your sandbox domain (e.g., `sandboxXXXXX.mailgun.org`)
4. Go to **SMTP** section
5. Note down the same credentials as above

## Step 2: Configure Environment Variables

Add the following variables to your `.env` file:

```bash
# Mailgun SMTP Configuration
SMTP_HOST=smtp.mailgun.org
SMTP_PORT=587
SMTP_USERNAME=postmaster@your-domain.mailgun.org
SMTP_PASSWORD=your-smtp-password
SMTP_ENCRYPTION=tls
MAIL_FROM_ADDRESS=noreply@your-domain.com
MAIL_FROM_NAME=OpenDocMan System
```

### Important Notes:

- **SMTP_USERNAME**: This is typically `postmaster@your-domain.mailgun.org`
- **SMTP_PASSWORD**: This is your SMTP password from Mailgun, NOT your account password
- **MAIL_FROM_ADDRESS**: Must be from your verified domain or sandbox domain
- **SMTP_ENCRYPTION**: Use `tls` for port 587, or `ssl` for port 465

## Step 3: Rebuild and Deploy

After updating your environment variables:

```bash
# Rebuild the container to apply changes
docker-compose build --no-cache

# Restart the services
docker-compose down
docker-compose up -d
```

## Step 4: Test Your Configuration

1. Check SMTP environment variables: `docker exec -it <container> env | grep SMTP`
2. Test SMTP connectivity: `docker exec -it <container> msmtp --serverinfo --host=smtp.mailgun.org --port=587`
3. Verify msmtp configuration: `docker exec -it <container> ls -la /etc/msmtprc /usr/local/bin/mail-wrapper`
4. Send a test email: `docker exec -it <container> php -r "mail('test@example.com', 'Test', 'Test message', 'From: admin@yourcompany.com');"`

## Troubleshooting

### Common Issues and Solutions

#### 1. "550 5.7.1 Relaying denied" Error

**Cause**: Authentication failure or incorrect credentials
**Solution**:
- Verify your SMTP username and password are correct
- Ensure you're using the SMTP password, not your account password
- Check that your domain is properly verified in Mailgun

#### 2. "Connection timeout" or "Cannot connect to SMTP server"

**Cause**: Network connectivity issues
**Solution**:
- Verify your firewall allows outbound connections on port 587
- Check that `SMTP_HOST=smtp.mailgun.org` is correct
- Test connectivity: `docker exec -it <container> msmtp --serverinfo --host=smtp.mailgun.org --port=587`

#### 3. "Authentication failed"

**Cause**: Wrong username/password combination
**Solution**:
- Double-check your Mailgun SMTP credentials
- Regenerate your SMTP password in Mailgun dashboard if needed
- Ensure no extra spaces in environment variables

#### 4. "From address not authorized"

**Cause**: Using an email address from an unverified domain
**Solution**:
- Use an email address from your verified Mailgun domain
- For sandbox testing, use the sandbox domain format
- Add authorized recipients in Mailgun dashboard (sandbox only)

#### 5. "contains secrets and therefore must have no more than user read/write permissions"

**Cause**: msmtp configuration file permissions issue
**Solution**:
- This is automatically handled by the mail-wrapper script
- If you see this error, rebuild the container: `docker-compose build --no-cache`
- The script creates temporary config files with proper 600 permissions
- Ensure the configuration script ran properly during container startup

### Debugging Steps

1. **Check Environment Variables**:
   ```bash
   docker exec -it <container_name> env | grep SMTP
   ```

2. **Check Logs**:
   ```bash
   docker exec -it <container_name> tail -f /var/log/mail.log
   docker exec -it <container_name> tail -f /var/log/msmtp.log
   ```

3. **Test SMTP Connectivity**:
   ```bash
   # Using msmtp to test server info
   docker exec -it <container_name> msmtp --serverinfo --host=smtp.mailgun.org --port=587
   
   # Test email sending
   docker exec -it <container> php -r "mail('test@example.com', 'Test', 'Test message');"
   ```

4. **Verify Configuration Files**:
   ```bash
   docker exec -it <container_name> ls -la /etc/msmtprc
   docker exec -it <container_name> ls -la /usr/local/bin/mail-wrapper
   ```

5. **Run Configuration Script Manually**:
   ```bash
   docker exec -it <container_name> bash /configure-simple-mail.sh
   ```

6. **Manual Email Testing**:
   ```bash
   # Test msmtp configuration and connectivity
   docker exec -it <container_name> msmtp --serverinfo --host=smtp.mailgun.org --port=587
   
   # Send test email via PHP (recommended)
   docker exec -it <container_name> php -r "mail('your-email@example.com', 'Test Subject', 'Test message body', 'From: admin@localhost');"
   
   # Check logs
   docker exec -it <container_name> tail -5 /var/log/mail.log
   ```

## Example Working Configuration

Here's a complete example of working Mailgun configuration:

```bash
# .env file example
SMTP_HOST=smtp.mailgun.org
SMTP_PORT=587
SMTP_USERNAME=postmaster@mg.yourcompany.com
SMTP_PASSWORD=abc123def456ghi789
SMTP_ENCRYPTION=tls
MAIL_FROM_ADDRESS=noreply@yourcompany.com
MAIL_FROM_NAME=YourCompany Document System
```

## Mailgun Dashboard Settings

### For Production (Verified Domain):
1. Add and verify your domain in Mailgun
2. Set up proper DNS records (MX, TXT, CNAME)
3. Enable DKIM and SPF for better deliverability

### For Testing (Sandbox Domain):
1. Add authorized recipients in the sandbox domain settings
2. Only authorized email addresses can receive emails
3. Limited to 300 emails per day on free tier

## Security Best Practices

1. **Use Environment Variables**: Never hardcode credentials in your code
2. **Limit Permissions**: Use a dedicated SMTP user in Mailgun if possible
3. **Monitor Usage**: Keep an eye on your Mailgun usage and logs
4. **Rotate Credentials**: Periodically update your SMTP passwords

## Performance Notes

The new msmtp-based system provides:
- **Fast Response**: ~90ms response time (vs 30+ seconds with old system)
- **Reliable Delivery**: Proper SMTP authentication and error handling
- **Background Processing**: Email sending doesn't block web requests
- **Comprehensive Logging**: Detailed logs for troubleshooting

## Support

If you continue to experience issues:

1. Check the OpenDocMan logs: `/var/log/mail.log` and `/var/log/msmtp.log`
2. Verify your Mailgun account status and domain verification
3. Test with built-in tools: `docker exec -it <container> msmtp --serverinfo --host=smtp.mailgun.org --port=587`
4. Consult Mailgun's documentation for additional SMTP configuration options

## Additional Resources

- [Mailgun SMTP Documentation](https://documentation.mailgun.com/en/latest/quickstart-sending.html#send-via-smtp)
- [Mailgun Domain Verification Guide](https://documentation.mailgun.com/en/latest/quickstart-sending.html#verify-your-domain)
- [OpenDocMan Email Configuration Guide](MAIL_CONFIG_GUIDE.md)