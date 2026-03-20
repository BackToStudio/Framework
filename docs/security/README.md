# Security Bundle

A defense-in-depth security layer for WordPress, covering login hardening, two-factor authentication, HTTP security headers, audit logging, file integrity monitoring, and access control.

## Overview

The Security bundle wraps 40+ security rules behind the `SecurityRuleInterface` auto-discovery pattern. Registering the `SecurityExtension` activates sensible defaults — secure headers, XML-RPC disabled, login throttling, file editor disabled, and more. Every rule is independently testable via hexagonal port/adapter interfaces.

**Namespace:** `BackTo\Framework\Bundle\Security`

## Documentation

| Document | Type | Description |
|---|---|---|
| [Getting started](tutorial.md) | Tutorial | Harden a plugin and verify results step by step |
| [Common tasks](how-to/README.md) | How-to | Practical recipes for CSP, CORS, rate limiting, IP control, 2FA, and more |
| [API reference](reference.md) | Reference | Complete class, hook, and configuration documentation |
| [Architecture](explanation.md) | Explanation | Design decisions, defense-in-depth strategy, hook ordering |
