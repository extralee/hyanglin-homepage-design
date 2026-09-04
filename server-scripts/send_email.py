#!/usr/bin/env python3
# -*- coding: utf-8 -*-

import os
import sys
import argparse
import smtplib
from email.mime.text import MIMEText
from email.mime.multipart import MIMEMultipart

def parse_settings_conf(conf_path):
    settings = {}
    if not os.path.exists(conf_path):
        return settings
        
    with open(conf_path, "r", encoding="utf-8") as f:
        for line in f:
            line = line.strip()
            if not line or line.startswith("#"):
                continue
            if "=" in line:
                key, val = line.split("=", 1)
                val = val.strip("'\"")
                settings[key.strip()] = val
    return settings

def main():
    parser = argparse.ArgumentParser(description="Hyanglin Church SMTP Email Sender")
    parser.add_argument("--to", help="Recipient email address")
    parser.add_argument("--cc", help="CC email address")
    parser.add_argument("--subject", required=True, help="Email subject")
    parser.add_argument("--body", required=True, help="Email body content")
    parser.add_argument("--html", action="store_true", help="Send email in HTML format")
    
    args = parser.parse_args()
    
    script_dir = os.path.dirname(os.path.abspath(__file__))
    conf_path = os.path.join(script_dir, "settings.conf")
    
    settings = parse_settings_conf(conf_path)
    
    smtp_server = settings.get("SMTP_SERVER", "smtp.gmail.com")
    smtp_port = int(settings.get("SMTP_PORT", 465))
    smtp_user = settings.get("SMTP_USERNAME", "")
    smtp_pass = settings.get("SMTP_PASSWORD", "")
    admin_email = settings.get("ADMIN_EMAIL", "")
    admin_cc = settings.get("ADMIN_EMAIL_CC", "")
    
    recipient = args.to if args.to else admin_email
    cc_recipient = args.cc if args.cc is not None else admin_cc
    
    if not recipient:
        print("[ERROR] Recipient email is not specified and ADMIN_EMAIL is missing in settings.conf", file=sys.stderr)
        sys.exit(1)
        
    if not smtp_pass or not smtp_user:
        print("[ERROR] SMTP_USERNAME or SMTP_PASSWORD is not configured in settings.conf", file=sys.stderr)
        sys.exit(1)
        
    recipients = [r.strip() for r in recipient.split(",") if r.strip()]
    cc_recipients = [r.strip() for r in cc_recipient.split(",") if r.strip()]
    all_targets = recipients + cc_recipients
        
    msg = MIMEMultipart("alternative")
    msg["Subject"] = args.subject
    msg["From"] = f"Hyanglin Church Automation <{smtp_user}>"
    msg["To"] = ", ".join(recipients)
    if cc_recipients:
        msg["Cc"] = ", ".join(cc_recipients)
    
    mime_type = "html" if args.html else "plain"
    part = MIMEText(args.body, mime_type, "utf-8")
    msg.attach(part)
    
    try:
        if smtp_port == 465:
            server = smtplib.SMTP_SSL(smtp_server, 465)
        else:
            server = smtplib.SMTP(smtp_server, smtp_port)
            server.starttls()
            
        server.ehlo()
        server.login(smtp_user, smtp_pass)
        server.sendmail(smtp_user, all_targets, msg.as_string())
        server.quit()
        
        print("✅ Email sent successfully to: " + ", ".join(all_targets))
    except Exception as e:
        print(f"❌ Failed to send email: {e}", file=sys.stderr)
        sys.exit(1)

if __name__ == "__main__":
    main()
