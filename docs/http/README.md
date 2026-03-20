# HTTP Client

A PSR-18 compliant HTTP client with PSR-7 responses, backed by WordPress's HTTP API.

## Overview

The HTTP module provides a standards-based HTTP client that wraps WordPress's `wp_remote_request()` behind PSR-18 (`Psr\Http\Client\ClientInterface`). Responses implement PSR-7 (`Psr\Http\Message\ResponseInterface`).

Developers can use the standard PSR-18 `sendRequest()` method or convenience shortcuts (`get()`, `post()`, `request()`).

## Documentation

| Document | Type | Description |
|---|---|---|
| [Getting started](tutorial.md) | Tutorial | Send your first HTTP request step by step |
| [Common tasks](how-to.md) | How-to | Practical recipes for real-world use cases |
| [API reference](reference.md) | Reference | Complete interface and class documentation |
| [Architecture](explanation.md) | Explanation | Design decisions, PSR compliance, WordPress integration |
