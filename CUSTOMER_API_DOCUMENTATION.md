# SafeDest Customer API Documentation

## Overview
This documentation covers all API endpoints for the SafeDest Customer Flutter application. The APIs are built using Laravel 10+ with Sanctum authentication.

## Base URL
```
https://your-domain.com/api/customer
```

## Authentication
All protected endpoints require a Bearer token obtained from the login endpoint.

### Headers
```
Authorization: Bearer {token}
Content-Type: application/json
Accept: application/json
```

## API Endpoints

### 1. Authentication APIs

#### POST /auth/login
Login customer and get access token.

**Request:**
```json
{
    "email": "customer@example.com",
    "password": "password123",
    "device_name": "iPhone 14",
    "fcm_token": "fcm_token_here"
}
```

**Response:**
```json
{
    "success": true,
    "message": "Login successful",
    "data": {
        "customer": {
            "id": 1,
            "name": "John Doe",
            "email": "customer@example.com",
            "phone": "+966501234567"
        },
        "token": "1|abc123...",
        "token_type": "Bearer",
        "expires_at": "2024-01-01T00:00:00.000000Z"
    }
}
```

#### POST /auth/register
Register new customer account.

#### POST /auth/logout
Logout and revoke current token.

#### POST /auth/forgot-password
Send password reset email.

#### POST /auth/reset-password
Reset password with token.

#### POST /auth/change-password
Change password for authenticated user.

### 2. Profile Management APIs

#### GET /profile
Get customer profile information.

#### PUT /profile
Update customer profile.

#### POST /profile/avatar
Upload profile avatar.

#### GET /profile/stats
Get customer statistics.

#### DELETE /profile
Delete customer account.

### 3. Dashboard APIs

#### GET /dashboard
Get dashboard overview data.

#### GET /dashboard/stats
Get detailed statistics with filters.

#### GET /dashboard/activities
Get recent activities.

### 4. Task Management APIs

#### GET /tasks
Get customer tasks with filters and pagination.

**Query Parameters:**
- `status`: Filter by task status
- `task_type`: Filter by task type
- `date_from`: Filter from date
- `date_to`: Filter to date
- `search`: Search in addresses/description
- `sort_by`: Sort field (default: created_at)
- `sort_order`: Sort order (asc/desc)
- `per_page`: Items per page (default: 15)

#### POST /tasks
Create new task.

**Request:**
```json
{
    "task_type": "normal",
    "vehicle_type": "small_car",
    "from_address": "Riyadh, Saudi Arabia",
    "to_address": "Jeddah, Saudi Arabia",
    "from_lat": 24.7136,
    "from_lng": 46.6753,
    "to_lat": 21.4858,
    "to_lng": 39.1925,
    "pickup_time": "2024-01-01T10:00:00Z",
    "delivery_time": "2024-01-01T18:00:00Z",
    "description": "Transport electronics",
    "assign_type": "advertised",
    "dynamic_fields": {
        "special_instructions": "Handle with care"
    }
}
```

#### GET /tasks/{id}
Get task details.

#### PUT /tasks/{id}
Update task (if allowed).

#### POST /tasks/{id}/cancel
Cancel task.

#### GET /tasks/{id}/tracking
Get real-time task tracking.

#### POST /tasks/{id}/rate
Rate completed task.

### 5. Wallet Management APIs

#### GET /wallet
Get wallet information and recent transactions.

#### GET /wallet/transactions
Get transaction history with filters.

#### POST /wallet/deposit
Initiate wallet deposit.

#### POST /wallet/withdraw
Request wallet withdrawal.

#### POST /wallet/transfer
Transfer money to another customer.

#### GET /wallet/statements
Generate financial statements.

### 6. Payment APIs

#### GET /payments/methods
Get available payment methods.

#### POST /payments
Initiate payment process.

#### GET /payments/{id}/status
Check payment status.

#### POST /payments/{id}/confirm
Confirm payment (for manual methods).

#### POST /payments/{id}/cancel
Cancel pending payment.

#### GET /payments/history
Get payment history.

#### GET /payments/{id}/receipt
Get payment receipt.

### 7. Customs Clearance APIs

#### GET /customs-clearances
Get customer clearance requests.

#### POST /customs-clearances
Create new clearance request.

#### GET /customs-clearances/{id}
Get clearance details.

#### POST /customs-clearances/{id}/documents
Upload additional documents.

#### GET /customs-clearances/{id}/status
Get clearance status and timeline.

### 8. Ads and Offers APIs

#### GET /ads/tasks
Get available task ads for drivers.

#### GET /ads/tasks/{id}
Get task ad details.

#### POST /ads/tasks/{id}/offer
Submit offer for task (drivers only).

#### GET /ads/clearances
Get customs clearance ads.

#### GET /offers/my-offers
Get my submitted offers.

#### GET /offers/received
Get offers received for my tasks.

#### POST /offers/{id}/accept
Accept an offer.

#### POST /offers/{id}/reject
Reject an offer.

#### POST /offers/{id}/cancel
Cancel my submitted offer.

### 9. Notification APIs

#### GET /notifications
Get customer notifications.

#### GET /notifications/unread-count
Get unread notifications count.

#### POST /notifications/{id}/read
Mark notification as read.

#### POST /notifications/mark-all-read
Mark all notifications as read.

#### DELETE /notifications/{id}
Delete notification.

#### DELETE /notifications/clear-all
Clear all notifications.

#### GET /notifications/settings
Get notification settings.

#### PUT /notifications/settings
Update notification settings.

#### POST /notifications/fcm-token
Update FCM token for push notifications.

#### GET /notifications/statistics
Get notification statistics.

### 10. Settings APIs

#### GET /settings
Get application settings and configuration.

#### PUT /settings
Update customer settings.

#### GET /geofences
Get available service areas.

#### GET /app-version
Check app version and update requirements.

#### POST /form-fields/validate
Validate dynamic form fields.

#### GET /form-templates
Get available form templates.

#### GET /constants
Get system constants and enums.

## Response Format

### Success Response
```json
{
    "success": true,
    "message": "Operation successful",
    "data": {
        // Response data here
    }
}
```

### Error Response
```json
{
    "success": false,
    "message": "Error message",
    "error": "Detailed error information",
    "errors": {
        "field_name": ["Validation error message"]
    }
}
```

## Status Codes
- `200`: Success
- `201`: Created
- `400`: Bad Request
- `401`: Unauthorized
- `403`: Forbidden
- `404`: Not Found
- `422`: Validation Error
- `500`: Internal Server Error

## Pagination
List endpoints return paginated results:

```json
{
    "success": true,
    "data": {
        "items": [...],
        "pagination": {
            "current_page": 1,
            "last_page": 5,
            "per_page": 15,
            "total": 75,
            "from": 1,
            "to": 15
        }
    }
}
```

## File Uploads
File uploads use multipart/form-data:

```
Content-Type: multipart/form-data
```

Supported file types:
- Images: jpg, jpeg, png, webp (max 2MB)
- Documents: pdf, doc, docx (max 5MB)

## Rate Limiting
- Authentication endpoints: 5 requests per minute
- General endpoints: 60 requests per minute
- File upload endpoints: 10 requests per minute

## Error Handling
The API uses consistent error handling with appropriate HTTP status codes and descriptive error messages. All errors include a `success: false` flag and relevant error information.

## Testing
Run the test suite:
```bash
php artisan test tests/Feature/CustomerApiTest.php
```

## Support
For API support, contact: support@safedest.com
