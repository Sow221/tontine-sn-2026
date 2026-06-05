# Security Policy

## Supported Versions

| Version | Supported          |
|---------|--------------------|
| 1.x     | ✅ Active development |

## Reporting a Vulnerability

Contact the maintainer directly. Do not open public issues for security vulnerabilities.

## Security Practices

- **Rate Limiting** : Login (10/min), Register (5/min), Password Reset (3/min), Payment (10/min), KYC Upload (5/min)
- **Input Sanitization** : All webhook payloads sanitized before logging (sensitive keys redacted)
- **CSP Headers** : Content-Security-Policy restricts scripts/styles to trusted CDNs only
- **API Tokens** : Sanctum tokens expire after 24 hours
- **Password** : Bcrypt with 12 rounds, minimum 8 characters
- **OTP** : Links expire after 5 minutes (configurable via `OTP_EXPIRY_MINUTES`)
- **CSRF** : All stateful routes protected by CSRF tokens
- **Account Deletion** : Personal data anonymized before soft-delete (RGPD/BCEAO compliant)
