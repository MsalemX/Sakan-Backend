# Housing-API Endpoints Specification

This document provides details for the API endpoints to be used by the frontend team.

## Base URL
`http://your-domain.com/api`

## Authentication
All authenticated routes require a `Bearer Token` in the `Authorization` header.

---

## 1. Authentication Endpoints

### Register
- **URL**: `/register`
- **Method**: `POST`
- **Body**:
```json
{
  "name": "Jane Doe",
  "email": "jane@example.com",
  "password": "password123",
  "role_name": "Student", // or "Housing Owner"
  // If Student:
  "full_name": "Jane Doe",
  "personal_id_image": "base64/url", // Optional
  "university_name": "University X",
  "major": "Computer Science",
  "university_card_image": "base64/url", // Optional
  "academic_level": "3rd Year",
  "phone_number": "123456789",
  "address": "Street 123",
  "nationality": "Saudi",
  "proof_of_enrollment": "base64/url" // Optional
  // If Housing Owner:
  "commercial_register": "1234567890" // Optional
}
```

### Login
- **URL**: `/login`
- **Method**: `POST`
- **Body**: `{"email": "...", "password": "..."}`
- **Response**: Returns `access_token` and `user` object.

---

## 2. Housing Endpoints

### List Housings (Approved)
- **URL**: `/housing`
- **Method**: `GET`

### Create Housing (Owner Only)
- **URL**: `/housing`
- **Method**: `POST`
- **Body**: `multipart/form-data` (use JSON structure below for other fields)
  - standard fields are the same as when no images are present
  - to upload photos, include one or more `images[]` file parts

Example JSON payload (no images):
```json
{
  "name": "Luxury Dorm",
  "description": "Close to campus...",
  "conditions": "No smoking, keep quiet after 10pm.",
  "base_price": 1500.00,
  "capacity": 50,
  "remaining_capacity": 50,
  "features": ["WiFi", "AC", "Laundry"],
  "services": [
    {"name": "Meals", "extra_price": 200.00}
  ]
}
```

---

## 3. Booking Requests

### List My Requests
- **URL**: `/booking-requests`
- **Method**: `GET`

### Submit Request (Student Only)
- **URL**: `/booking-requests`
- **Method**: `POST`
- **Body**: `{"housing_id": 1}`

### Approve/Reject Request (Owner Only)
- **URL**: `/booking-requests/{id}/status`
- **Method**: `PATCH`
- **Body**: `{"status": "approved"}` // or "rejected"

---

## 4. Interviews

### List My Interviews
- **URL**: `/interviews`
- **Method**: `GET`

### Schedule Interview (Owner Only)
- **URL**: `/interviews`
- **Method**: `POST`
- **Body**:
```json
{
  "request_id": 1,
  "interview_date": "2026-03-01 10:00:00",
  "notes": "Bring your ID."
}
```

---

## 5. Ratings & Notifications

### Rate Housing (Student Only)
- **URL**: `/ratings`
- **Method**: `POST`
- **Body**: `{"housing_id": 1, "rating": 5, "comment": "Great!"}`

### Get Notifications
- **URL**: `/notifications`
- **Method**: `GET`

---

## 6. Admin Endpoints

### Approve Owner
- **URL**: `/admin/approve-owner/{user_id}`
- **Method**: `POST`

### Approve Housing
- **URL**: `/admin/approve-housing/{housing_id}`
- **Method**: `POST`
