# Requests:

```bash
curl -X POST http://localhost:8000/api/auth/register \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "name": "John Doe",
    "username": "johndoe",
    "email": "john@example.com",
    "password": "DieNooNeNo1",
    "password_confirmation": "DieNooNeNo1",
    "phone": "+1234567890"
  }'
```
