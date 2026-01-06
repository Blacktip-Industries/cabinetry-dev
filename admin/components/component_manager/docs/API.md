# Component Manager API Documentation

## Overview

Component Manager provides a RESTful API for programmatic component management.

## Base URL

`/admin/components/component_manager/api/v1/`

## Authentication

API key authentication is required for all endpoints.

## Endpoints

### Registry

- `GET /registry` - List all components
- `GET /registry/{component_name}` - Get component details

### Updates

- `GET /updates` - List available updates
- `POST /updates/{component_name}` - Update component

### Changelog

- `GET /changelog` - Get changelog entries

### Health

- `GET /health/{component_name}` - Get component health

## Response Format

All responses are in JSON format.

## Error Handling

Errors are returned with appropriate HTTP status codes and error messages.

