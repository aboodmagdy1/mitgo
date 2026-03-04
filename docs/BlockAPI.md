# Block API Documentation

This document describes the Block API endpoints that allow users to block and unblock other users (providers).

## Authentication

All endpoints require authentication using Laravel Sanctum. Include the `Authorization: Bearer {token}` header in your requests.

## Endpoints

### 1. Block a User

**POST** `/api/v1/client/blocks`

Block another user (provider).

**Request Body:**
```json
{
    "blocked_id": 123
}
```

**Response (201 Created):**
```json
{
    "status": true,
    "message": "User blocked successfully",
    "data": {
        "block": {
            "id": 1,
            "blocker_id": 1,
            "blocked_id": 123,
            "blocked_user": {
                "id": 123,
                "name": "John Doe",
                "email": "john@example.com",
                "phone": "+1234567890"
            },
            "created_at": "2024-01-15 10:30:00",
            "updated_at": "2024-01-15 10:30:00"
        }
    }
}
```

**Error Responses:**
- `400 Bad Request`: You cannot block yourself
- `409 Conflict`: User is already blocked
- `422 Unprocessable Entity`: Validation errors

### 2. Unblock a User

**DELETE** `/api/v1/client/blocks/{id}`

Unblock a previously blocked user.

**Parameters:**
- `id`: The ID of the user to unblock

**Response (200 OK):**
```json
{
    "status": true,
    "message": "User unblocked successfully",
    "data": []
}
```

**Error Responses:**
- `404 Not Found`: User is not blocked

### 3. List Blocked Users

**GET** `/api/v1/client/blocks`

Get a list of all users blocked by the authenticated user.

**Response (200 OK):**
```json
{
    "status": true,
    "message": "Blocked users retrieved successfully",
    "data": {
        "blocks": [
            {
                "id": 1,
                "blocker_id": 1,
                "blocked_id": 123,
                "blocked_user": {
                    "id": 123,
                    "name": "John Doe",
                    "email": "john@example.com",
                    "phone": "+1234567890"
                },
                "created_at": "2024-01-15 10:30:00",
                "updated_at": "2024-01-15 10:30:00"
            }
        ]
    }
}
```

### 4. Check Block Status

**GET** `/api/v1/client/blocks/{id}/status`

Check if a specific user is blocked by the authenticated user.

**Parameters:**
- `id`: The ID of the user to check

**Response (200 OK):**
```json
{
    "status": true,
    "message": "Block status checked successfully",
    "data": {
        "is_blocked": true
    }
}
```

## Database Schema

The blocks are stored in the `user_blocks` table with the following structure:

```sql
CREATE TABLE user_blocks (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    blocker_id BIGINT UNSIGNED NOT NULL,
    blocked_id BIGINT UNSIGNED NOT NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    FOREIGN KEY (blocker_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (blocked_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_block (blocker_id, blocked_id)
);
```

## Business Rules

1. **Self-blocking Prevention**: Users cannot block themselves
2. **Duplicate Prevention**: A user cannot block the same person twice
3. **Cascade Deletion**: When a user is deleted, all their blocks are automatically removed
4. **Unique Constraint**: The combination of `blocker_id` and `blocked_id` must be unique

## Usage Examples

### Frontend Integration

```javascript
// Block a user
const blockUser = async (userId) => {
    try {
        const response = await fetch('/api/v1/client/blocks', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Authorization': `Bearer ${token}`,
                'Accept': 'application/json'
            },
            body: JSON.stringify({ blocked_id: userId })
        });
        
        const data = await response.json();
        if (data.status) {
            console.log('User blocked successfully');
        }
    } catch (error) {
        console.error('Error blocking user:', error);
    }
};

// Unblock a user
const unblockUser = async (userId) => {
    try {
        const response = await fetch(`/api/v1/client/blocks/${userId}`, {
            method: 'DELETE',
            headers: {
                'Authorization': `Bearer ${token}`,
                'Accept': 'application/json'
            }
        });
        
        const data = await response.json();
        if (data.status) {
            console.log('User unblocked successfully');
        }
    } catch (error) {
        console.error('Error unblocking user:', error);
    }
};

// Check if user is blocked
const checkBlockStatus = async (userId) => {
    try {
        const response = await fetch(`/api/v1/client/blocks/${userId}/status`, {
            headers: {
                'Authorization': `Bearer ${token}`,
                'Accept': 'application/json'
            }
        });
        
        const data = await response.json();
        return data.data.is_blocked;
    } catch (error) {
        console.error('Error checking block status:', error);
        return false;
    }
};
```

## Testing

Run the tests to verify the functionality:

```bash
php artisan test tests/Feature/BlockControllerTest.php
```

## Notes

- All endpoints are protected by authentication middleware
- The API follows RESTful conventions
- Responses use the standard API response format with `status`, `message`, and `data` fields
- Error responses include appropriate HTTP status codes and descriptive messages
